<?php

declare(strict_types=1);
/**
 * This file is part of huangdijia/laravel-trigger.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/4.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Huangdijia\Trigger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Huangdijia\Trigger\Manager
 * @method static \Huangdijia\Trigger\Trigger replication(?string $name = null)
 * @method static array replications()
 * @see Huangdijia\Trigger\Trigger
 * @method static \MySQLReplication\Config\Config configure(bool $keepUp)
 * @method static array getConfig()
 * @method static array getSubscribers()
 * @method static void loadRoutes()
 * @method static void start(bool $keepUp)
 * @method static void terminate()
 * @method static boolean isTerminated()
 * @method static void heartbeat(EventDTO $event)
 * @method static void rememberCurrent(BinLogCurrent $binLogCurrent)
 * @method static \MySQLReplication\BinLog\BinLogCurrent getCurrent()
 * @method static void clearCurrent()
 * @method static void on(string $table, $eventType, $action = null)
 * @method static void dispatch(EventDTO $event)
 * @method static void fire($events, EventDTO $event = null)
 * @method static array getEvents()
 */
class Trigger extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'trigger.manager';
    }
}
