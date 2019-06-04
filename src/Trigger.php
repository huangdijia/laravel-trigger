<?php

namespace Huangdijia\Trigger;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use MySQLReplication\Event\DTO\EventDTO;
use ReflectionException;
use ReflectionMethod;

class Trigger
{
    private $events = [];

    public function __construct()
    {
        //
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

        if (!is_callable($action)) {
            throw new Exception("{$class}::{$method}() is undefined", 1);
        }

        $reflectionMethod = new ReflectionMethod($class, $method);

        if (!$reflectionMethod->isPublic()) {
            throw new ReflectionException("{$class}::{$method}() is not public", 1);
        }

        // static method
        if ($reflectionMethod->isStatic()) {
            return [
                [$class, $method],
                [$event]
            ];
        }

        return [
            [app($class), $method],
            [$event]
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
