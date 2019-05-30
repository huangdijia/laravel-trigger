<?php

namespace Huangdijia\Trigger;

use Exception;
use Illuminate\Support\Arr;
use MySQLReplication\Event\DTO\EventDTO;

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
     * @param mixed $events
     * @param \MySQLReplication\Event\DTO\EventDTO $event
     * @return void
     */
    public function trigger($events, EventDTO $event = null)
    {
        collect($events)->each(function ($e) use ($event) {
            collect(Arr::get($this->events, $e))->each(function ($action) use ($event) {
                $this->call($this->parseAction($action), [$event]);
            });
        });

        return;
    }

    /**
     * 执行事件
     *
     * @param \MySQLReplication\Event\DTO\EventDTO $event
     * @return void
     */
    public function fire(EventDTO $event)
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

        $this->trigger($events, $event);

        return;
    }

    /**
     * 解析操作
     *
     * @param mixed $action
     * @param string $defaultHandleMethod
     * @return mixed
     */
    private function parseAction($action, $defaultHandleMethod = 'handle')
    {
        if (is_callable($action)) {
            return $action;
        }

        if (is_string($action)) {
            $action = explode('@', $action);
            $object = app($action[0]);
            $method = $action[1] ?? $defaultHandleMethod;

            return [$object, $method];
        } elseif (is_object($action)) {
            return [$action, $defaultHandleMethod];
        }

        if (!is_callable($action)) {
            throw new Exception("Error Action Type", 1);
        }

        return $action;
    }

    /**
     * 执行操作
     *
     * @param callable $action
     * @param array $parameters
     * @return void
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
