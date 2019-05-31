<?php

namespace Huangdijia\Trigger\Facades;

use Illuminate\Support\Facades\Facade;
use Huangdijia\Trigger\Bootstrap as Accessor;
use MySQLReplication\Config\ConfigBuilder;

/**
 * @method void terminate()
 * @method bool isTerminated()
 * @method void heartbeat(EventDTO $event)
 * @method void save(BinLogCurrent $binLogCurrent)
 * @method void clear()
 * @method \MySQLReplication\Config\ConfigBuilder startFromPosition(ConfigBuilder $builder, Command $command = null)
 * @see Huangdijia\Trigger\Bootstrap
 */
class Bootstrap extends Facade
{
    public static function getFacadeAccessor()
    {
        return Accessor::class;
    }
}