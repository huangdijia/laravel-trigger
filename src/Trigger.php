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
     * @param string $eventType
     * @param mixed $action
     * @return void
     */
    public function on(string $table, string $eventType, $action)
    {
        if ($table == '*') {
            $table .= '.*';
        }

        $eventType = strtolower($eventType);

        if (strpos($eventType, '|')) {
            collect(explode('|', $eventType))->filter()->each(function ($eventType) use ($table, $action) {
                $this->on($table, $eventType, $action);
            });

            return;
        }

        Arr::set($this->events, $table . '.' . $eventType, $action);

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
        $keys      = [];
        $eventType = $event->getType();

        if (is_callable([$event, 'getTableMap'])) {
            $database = $event->getTableMap()->getDatabase();
            $table    = $event->getTableMap()->getTable();
            $keys[]   = sprintf('%s.%s.%s', $database, $table, $eventType);
            $keys[]   = sprintf('%s.%s.%s', $database, $table, '*');
            $keys[]   = sprintf('%s.%s.%s', $database, '*', $eventType);
        }

        $keys[] = "*.*.{$eventType}";
        $keys[] = "*.*.*";

        collect($keys)->each(function ($key) use ($event) {
            collect(Arr::get($this->events, $key))->each(function ($action) use ($event) {
                $this->call($this->parseAction($action), [$event]);
            });
        });

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
