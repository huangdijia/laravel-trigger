<?php

namespace Huangdijia\Trigger;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;
use ReflectionException;
use ReflectionMethod;

class Trigger
{
    protected $name;
    protected $config;
    protected $events = [];
    protected $bootTime;
    protected $replicationCacheKey;
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

        $this->restartCacheKey     = sprintf('triggers:%s:restart', $name);
        $this->replicationCacheKey = sprintf('triggers:%s:replication', $name);
    }

    /**
     * Get config
     *
     * @param string $key
     * @param mixed $default
     *
     * @return array
     */
    public function getConfig(string $key = '', $default = null)
    {
        if ($key) {
            return $this->config[$key] ?? null;
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
     * 生成配置
     *
     * @return \MySQLReplication\Config\Config
     */
    public function configure()
    {
        $builder = new ConfigBuilder();

        $builder->withSlaveId(time())
            ->withHost($this->config['host'] ?? '')
            ->withPort($this->config['port'] ?? '')
            ->withUser($this->config['user'] ?? '')
            ->withPassword($this->config['password'] ?? '')
            ->withDatabasesOnly($this->config['databases'] ?? [])
            ->withTablesOnly($this->config['tables'] ?? [])
            ->withHeartbeatPeriod($this->config['heartbeat'] ?? 3);

        if ($binLogCurrent = $this->getCurrent()) {
            $builder->withBinLogFileName($binLogCurrent->getBinFileName())
                ->withBinLogPosition($binLogCurrent->getBinLogPosition());
        }

        return $builder->build();
    }

    /**
     * 加载路由
     *
     * @return void
     */
    public function loadRoutes()
    {
        $routeFile = $this->config['route'] ?? '';

        if (!$routeFile || !is_file($routeFile)) {
            return;
        }

        $router = $trigger = $this;

        require $routeFile;
    }

    /**
     * Start
     *
     * @return void
     */
    public function start()
    {
        // $this->loadRouters();

        $binLogStream = new MySQLReplicationFactory($this->configure());

        collect($this->getSubscribers())
            ->reject(function ($subscriber) {
                return !is_subclass_of($subscriber, EventSubscribers::class);
            })
            ->unique()
            ->each(function ($subscriber) use ($binLogStream) {
                dump($subscriber);
                $binLogStream->registerSubscriber(new $subscriber($this));
            })
            ->tap(function ($subscribers) use ($binLogStream) {
                $binLogStream->run();
            });
    }

    /**
     * terminate
     *
     * @return void
     */
    public function terminate()
    {
        Cache::forever($this->restartCacheKey, time());
    }

    /**
     * Is terminated
     *
     * @return boolean
     */
    public function isTerminated()
    {
        return Cache::get($this->restartCacheKey, 0) > $this->bootTime;
    }

    /**
     * @return \MySQLReplication\BinLog\BinLogCurrent
     */
    public function heartbeat(EventDTO $event)
    {
        $this->rememberCurrent($event->getEventInfo()->getBinLogCurrent());
    }

    /**
     * Remember Current
     *
     * @param \MySQLReplication\BinLog\BinLogCurrent $binLogCurrent
     * @return void
     */
    public function rememberCurrent(BinLogCurrent $binLogCurrent)
    {
        Cache::put($this->replicationCacheKey, serialize($binLogCurrent), Carbon::now()->addHours(1));
    }

    /**
     * Get Current
     *
     * @return \MySQLReplication\BinLog\BinLogCurrent|null
     */
    public function getCurrent()
    {
        $binLogCache = Cache::get($this->replicationCacheKey);

        if (!$binLogCache) {
            return null;
        }

        $binLogCurrent = unserialize($binLogCache);

        if (!$binLogCurrent) {
            return null;
        }

        return $binLogCurrent;
    }

    /**
     * Clear Current
     *
     * @return void
     */
    public function clearCurrent()
    {
        Cache::forget($this->replicationCacheKey);
    }

    /**
     * 绑定事件
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
            $table = sprintf('%s.%s', (config('trigger.databases')[0] ?? '*'), $table);
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
     * 执行事件
     *
     * @param \MySQLReplication\Event\DTO\EventDTO $event
     * @return void
     */
    public function dispatch(EventDTO $event)
    {
        $events    = [];
        $eventType = $event->getType();

        if (is_callable([$event, 'getTableMap'])) {
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
     * 执行事件
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
     * 解析操作
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
     * 执行操作
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
     * 获取全部事件
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }
}
