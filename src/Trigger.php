<?php

namespace Huangdijia\Trigger;

use Exception;
use Huangdijia\Trigger\EventSubscriber;
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
    protected $name;
    protected $config;

    /**
     * Cache
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

    public function __construct(string $name = 'default', array $config)
    {
        $this->name     = $name;
        $this->config   = $config;
        $this->bootTime = time();

        $this->resetCacheKey       = sprintf('triggers:%s:reset', $name);
        $this->restartCacheKey     = sprintf('triggers:%s:restart', $name);
        $this->replicationCacheKey = sprintf('triggers:%s:replication', $name);

        $this->cache = Cache::store();
    }

    /**
     * Auto detect databases and tables
     * @return void
     */
    public function detectDatabasesAndTables()
    {
        $this->config['databases'] = $this->getDatabases();
        $this->config['tables']    = $this->getTables();
    }

    /**
     * Get config
     *
     * @param string $key
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
     * Get subscribers
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
     * Builder config
     * @param bool $keepup
     * @return \MySQLReplication\Config\Config
     */
    public function configure($keepup = true)
    {
        return tap(new ConfigBuilder(), function ($builder) use ($keepup) {
            /** @var ConfigBuilder $builder */
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
     * Load routes of trigger
     *
     * @return void
     */
    public function loadRoutes()
    {
        $routeFile = $this->config['route'] ?? '';

        if (!$routeFile || !is_file($routeFile)) {
            return;
        }

        $trigger = $this;

        require $routeFile;
    }

    /**
     * Start
     * @param bool $keepup
     * @return void
     */
    public function start($keepup = true)
    {
        tap(new MySQLReplicationFactory($this->configure($keepup)), function ($binLogStream) {
            /** @var MySQLReplicationFactory $binLogStream */
            collect($this->getSubscribers())
                ->reject(function ($subscriber) {
                    return !is_subclass_of($subscriber, EventSubscriber::class);
                })
                ->unique()
                ->each(function ($subscriber) use ($binLogStream) {
                    $binLogStream->registerSubscriber(new $subscriber($this));
                });
        })->run();
    }

    /**
     * Reset
     * @return void
     */
    public function reset()
    {
        $this->cache->forever($this->resetCacheKey, time());
    }

    /**
     * IsReseted
     * @return bool
     */
    public function isReseted()
    {
        return $this->cache->get($this->resetCacheKey, 0) > $this->bootTime;
    }

    /**
     * Terminate
     *
     * @return void
     */
    public function terminate()
    {
        $this->cache->forever($this->restartCacheKey, time());
    }

    /**
     * Is terminated
     *
     * @return boolean
     */
    public function isTerminated()
    {
        return $this->cache->get($this->restartCacheKey, 0) > $this->bootTime;
    }

    /**
     * Remember current by heartbeat
     *
     * @return void
     */
    public function heartbeat(EventDTO $event)
    {
        $this->rememberCurrent($event->getEventInfo()->getBinLogCurrent());
    }

    /**
     * Remember current
     *
     * @param \MySQLReplication\BinLog\BinLogCurrent $binLogCurrent
     * @return void
     */
    public function rememberCurrent(BinLogCurrent $binLogCurrent)
    {
        $this->cache->put($this->replicationCacheKey, serialize($binLogCurrent), Carbon::now()->addHours(1));
    }

    /**
     * Get current
     *
     * @return \MySQLReplication\BinLog\BinLogCurrent|null
     */
    public function getCurrent()
    {
        return with($this->cache->get($this->replicationCacheKey), function ($cache) {
            if (!$cache) {
                return null;
            }

            return unserialize($cache) ?: null;
        });
    }

    /**
     * Clear current
     *
     * @return void
     */
    public function clearCurrent()
    {
        $this->cache->forget($this->replicationCacheKey);
    }

    /**
     * Bind events
     *
     * @param string $table
     * @param string|array $eventType
     * @param Closure|array|string|callable $action
     * @return void
     */
    public function on(string $table, $eventType, $action = null)
    {
        // table as db.tb1,db.tb2,...
        if (false !== strpos($table, ',')) {
            collect(explode(',', $table))->transform(function ($table) {
                return trim($table);
            })
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
        if (false === strpos($table, '.')) { // table to database.table
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
            if (false !== strpos($eventType, ',')) {
                collect(explode(',', $eventType))
                    ->transform(function ($eventType) {
                        return trim($eventType);
                    })
                    ->filter()
                    ->each(function ($eventType) use ($table, $action) {
                        $this->on($table, $eventType, $action);
                    });

                return;
            }
        }

        $key = sprintf('%s.%s', $table, $eventType);

        // append to actions
        $actions   = Arr::get($this->events, $key) ?: [];
        $actions[] = $action;

        // restore to array
        Arr::set($this->events, $key, $actions);

        return;
    }

    /**
     * Fire events
     *
     * @param \MySQLReplication\Event\DTO\EventDTO $event
     * @return void
     */
    public function dispatch(EventDTO $event)
    {
        $events    = [];
        $eventType = $event->getType();

        if (is_callable([$event, 'getTableMap'])) {
            /** @var \MySQLReplication\Event\DTO\RowsDTO $event */
            $database = $event->getTableMap()->getDatabase();
            $table    = $event->getTableMap()->getTable();
            $events[] = sprintf('%s.%s.%s', $database, $table, $eventType);
            $events[] = sprintf('%s.%s.%s', $database, $table, '*');
            $events[] = sprintf('%s.%s.%s', $database, '*', $eventType);
        }

        $events[] = "*.*.{$eventType}";
        $events[] = "*.*.*";

        $this->fire($events, $event);

        return;
    }

    /**
     * Fire evnets
     *
     * @param mixed $events
     * @param \MySQLReplication\Event\DTO\EventDTO $event
     * @return void
     */
    public function fire($events, EventDTO $event = null)
    {
        collect($events)->each(function ($e) use ($event) {
            collect(Arr::get($this->events, $e))->each(function ($action) use ($event) {
                $this->call(...$this->parseAction($action, $event));
            });
        });

        return;
    }

    /**
     * Parse action
     *
     * @param mixed $action
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
        $class  = $action[0];

        // class is not exists
        if (!class_exists($class)) {
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
        if (!is_callable([$class, $method])) {
            throw new Exception("{$class}::{$method}() is not callable or not exists", 1);
        }

        $reflectionMethod = new ReflectionMethod($class, $method);

        if (!$reflectionMethod->isPublic()) {
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
            [app($class), $method],
            [$event],
        ];
    }

    /**
     * Execute action
     *
     * @param callable $action
     * @param array $parameters
     * @return mixed
     */
    private function call(callable $action, array $parameters = [])
    {
        return call_user_func_array($action, $parameters);
    }

    /**
     * Get all events
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->events ?: [];
    }

    /**
     * Get all databases
     *
     * @return array
     */
    public function getDatabases()
    {
        $databases = array_keys($this->getEvents());
        $databases = array_filter($databases, function ($item) {return $item != '*';});

        return array_values($databases);
    }

    /**
     * Get all tables
     * @return array
     */
    public function getTables()
    {
        $tables = [];

        collect($this->getEvents())->each(function ($listeners, $database) use (&$tables) {
            if (is_array($listeners) && !empty($listeners)) {
                $tables = array_merge($tables, array_filter(array_keys($listeners), function ($item) {return $item != '*';}));
            }
        });

        return $tables;
    }
}
