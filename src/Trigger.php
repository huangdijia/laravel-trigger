<?php

declare(strict_types=1);
/**
 * This file is part of hyperf/helpers.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/4.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Huangdijia\Trigger;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\MySQLReplicationFactory;
use ReflectionException;
use ReflectionMethod;

class Trigger
{
    /**
     * Cache.
     *
     * @var \Illuminate\Support\Facades\Cache
     */
    protected $cache;

    protected $events = [];

    protected $bootTime;

    protected $replicationCacheKey;

    protected $resetCacheKey;

    protected $restartCacheKey;

    protected $defaultSubscribers = [
        \Huangdijia\Trigger\Subscribers\Trigger::class,
        \Huangdijia\Trigger\Subscribers\Terminate::class,
        \Huangdijia\Trigger\Subscribers\Heartbeat::class,
    ];

    public function __construct(protected string $name = 'default', protected array $config = [])
    {
        $this->bootTime = time();

        $this->resetCacheKey = sprintf('triggers:%s:reset', $name);
        $this->restartCacheKey = sprintf('triggers:%s:restart', $name);
        $this->replicationCacheKey = sprintf('triggers:%s:replication', $name);

        $this->cache = Cache::store();
    }

    /**
     * Auto detect databases and tables.
     */
    public function detectDatabasesAndTables()
    {
        $this->config['databases'] = $this->getDatabases();
        $this->config['tables'] = $this->getTables();
    }

    /**
     * Get config.
     *
     * @param mixed $default
     *
     * @return array|mixed
     */
    public function getConfig(string $key = '', $default = null)
    {
        if ($key) {
            return $this->config[$key] ?? $default;
        }

        return $this->config;
    }

    /**
     * Get subscribers.
     *
     * @return array
     */
    public function getSubscribers()
    {
        return array_merge(
            $this->getConfig('subscribers') ?: [],
            $this->defaultSubscribers
        );
    }

    /**
     * Builder config.
     * @param bool $keepup
     * @return \MySQLReplication\Config\Config
     */
    public function configure($keepup = true)
    {
        return tap(new ConfigBuilder(), function ($builder) use ($keepup) {
            /* @var ConfigBuilder $builder */
            $builder->withSlaveId(time())
                ->withHost($this->getConfig('host'))
                ->withPort($this->getConfig('port'))
                ->withUser($this->getConfig('user'))
                ->withPassword($this->getConfig('password'))
                ->withDatabasesOnly($this->getConfig('databases'))
                ->withTablesOnly($this->getConfig('tables'))
                ->withHeartbeatPeriod($this->getConfig('heartbeat') ?: 3);

            if ($keepup && $binLogCurrent = $this->getCurrent()) {
                $builder->withBinLogFileName($binLogCurrent->getBinFileName())
                    ->withBinLogPosition($binLogCurrent->getBinLogPosition());
            }
        })->build();
    }

    /**
     * Load routes of trigger.
     */
    public function loadRoutes()
    {
        $routeFile = $this->config['route'] ?? '';

        if (! $routeFile || ! is_file($routeFile)) {
            return;
        }

        $trigger = $this;

        require $routeFile;
    }

    /**
     * Start.
     * @param bool $keepup
     */
    public function start($keepup = true)
    {
        tap(new MySQLReplicationFactory($this->configure($keepup)), function ($binLogStream) {
            /* @var MySQLReplicationFactory $binLogStream */
            collect($this->getSubscribers())
                ->reject(fn ($subscriber) => ! is_subclass_of($subscriber, EventSubscriber::class))
                ->unique()
                ->each(function ($subscriber) use ($binLogStream) {
                    $binLogStream->registerSubscriber(new $subscriber($this));
                });
        })->run();
    }

    /**
     * Reset.
     */
    public function reset()
    {
        $this->cache->forever($this->resetCacheKey, time());
    }

    /**
     * IsReseted.
     * @return bool
     */
    public function isReseted()
    {
        return $this->cache->get($this->resetCacheKey, 0) > $this->bootTime;
    }

    /**
     * Terminate.
     */
    public function terminate()
    {
        $this->cache->forever($this->restartCacheKey, time());
    }

    /**
     * Is terminated.
     *
     * @return bool
     */
    public function isTerminated()
    {
        return $this->cache->get($this->restartCacheKey, 0) > $this->bootTime;
    }

    /**
     * Remember current by heartbeat.
     */
    public function heartbeat(EventDTO $event)
    {
        $this->rememberCurrent($event->getEventInfo()->getBinLogCurrent());
    }

    /**
     * Remember current.
     */
    public function rememberCurrent(BinLogCurrent $binLogCurrent)
    {
        $this->cache->put($this->replicationCacheKey, serialize($binLogCurrent), Carbon::now()->addHours(1));
    }

    /**
     * Get current.
     *
     * @return null|\MySQLReplication\BinLog\BinLogCurrent
     */
    public function getCurrent()
    {
        return with($this->cache->get($this->replicationCacheKey));
    }

    /**
     * Clear current.
     */
    public function clearCurrent()
    {
        $this->cache->forget($this->replicationCacheKey);
    }

    /**
     * Bind events.
     *
     * @param array|callable|Closure|string $action
     */
    public function on(string $table, array|string $eventType, array|callable | \Closure | string $action = null)
    {
        // table as db.tb1,db.tb2,...
        if (str_contains($table, ',')) {
            collect(explode(',', $table))->transform(fn ($table) => trim($table))
                ->filter()
                ->each(function ($table) use ($eventType, $action) {
                    $this->on($table, $eventType, $action);
                });
            return;
        }

        // * to *.*
        if ($table == '*') {
            $table .= '.*';
        }

        // default database
        $table = ltrim($table, '.');
        if (! str_contains($table, '.')) { // table to database.table
            $table = sprintf('%s.%s', ($this->config['databases'][0] ?? '*'), $table);
        } elseif (substr($table, -1) == '.') { // database. to database.*
            $table .= '*';
        }

        // eventType as array
        if (is_array($eventType)) {
            collect($eventType)->each(function ($action, $eventType) use ($table) {
                $this->on($table, $eventType, $action);
            });

            return;
        }

        // eventType as string
        if (is_string($eventType)) {
            // to lower
            $eventType = strtolower($eventType);

            // eventType as write,update,delete...
            if (str_contains($eventType, ',')) {
                collect(explode(',', $eventType))
                    ->transform(fn ($eventType) => trim($eventType))
                    ->filter()
                    ->each(function ($eventType) use ($table, $action) {
                        $this->on($table, $eventType, $action);
                    });

                return;
            }
        }

        $key = sprintf('%s.%s', $table, $eventType);

        // append to actions
        $actions = Arr::get($this->events, $key) ?: [];
        $actions[] = $action;

        // restore to array
        Arr::set($this->events, $key, $actions);
    }

    /**
     * Fire events.
     */
    public function dispatch(EventDTO $event)
    {
        $events = [];
        $eventType = $event->getType();

        if (is_callable([$event, 'getTableMap'])) {
            /** @var \MySQLReplication\Event\DTO\RowsDTO $event */
            $database = $event->getTableMap()->getDatabase();
            $table = $event->getTableMap()->getTable();
            $events[] = sprintf('%s.%s.%s', $database, $table, $eventType);
            $events[] = sprintf('%s.%s.%s', $database, $table, '*');
            $events[] = sprintf('%s.%s.%s', $database, '*', $eventType);
        }

        $events[] = "*.*.{$eventType}";
        $events[] = '*.*.*';

        $this->fire($events, $event);
    }

    /**
     * Fire evnets.
     *
     * @param mixed $events
     */
    public function fire($events, EventDTO $event = null)
    {
        collect($events)->each(function ($e) use ($event) {
            collect(Arr::get($this->events, $e))->each(function ($action) use ($event) {
                $this->call(...$this->parseAction($action, $event));
            });
        });
    }

    /**
     * Get all events.
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->events ?: [];
    }

    /**
     * Get all databases.
     *
     * @return array
     */
    public function getDatabases()
    {
        $databases = array_keys($this->getEvents());
        $databases = array_filter($databases, fn ($item) => $item != '*');

        return array_values($databases);
    }

    /**
     * Get all tables.
     * @return array
     */
    public function getTables()
    {
        $tables = [];

        collect($this->getEvents())->each(function ($listeners, $database) use (&$tables) {
            if (is_array($listeners) && ! empty($listeners)) {
                $tables = [...$tables, ...array_filter(array_keys($listeners), fn ($item) => $item != '*')];
            }
        });

        return $tables;
    }

    /**
     * Parse action.
     *
     * @param mixed $action
     * @param mixed $event
     * @return array [callable $callback, array $parameters]
     */
    private function parseAction($action, $event)
    {
        // callable
        if (is_callable($action)) {
            return [$action, [$event]];
        }

        // parse class from action
        $action = explode('@', $action);
        $class = $action[0];

        // class is not exists
        if (! class_exists($class)) {
            throw new Exception("class '{$class}' is not exists", 1);
        }

        // action as job
        if (is_subclass_of($class, ShouldQueue::class)) {
            $method = $action[1] ?? '';
            $method = in_array($method, ['dispatch', 'dispatch_now']) ? $method : 'dispatch';

            return [$method, [new $class($event)]];
        }

        // action as common callable
        $method = $action[1] ?? 'handle';

        // check is method callable
        if (! is_callable([$class, $method])) {
            throw new Exception("{$class}::{$method}() is not callable or not exists", 1);
        }

        $reflectionMethod = new ReflectionMethod($class, $method);

        if (! $reflectionMethod->isPublic()) {
            throw new ReflectionException("{$class}::{$method}() is not public", 1);
        }

        // static method
        if ($reflectionMethod->isStatic()) {
            return [
                [$class, $method],
                [$event],
            ];
        }

        return [
            [Container::getInstance()->make($class), $method],
            [$event],
        ];
    }

    /**
     * Execute action.
     *
     * @return mixed
     */
    private function call(callable $action, array $parameters = [])
    {
        return call_user_func_array($action, $parameters);
    }
}
