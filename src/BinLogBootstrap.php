<?php

namespace App\Utils;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Config\ConfigBuilder;

class BinLogBootstrap
{
    public static function getCacheKey()
    {
        return env('app_name') . 'bin_log_replicator_last_position_v1';
    }

    public static function save(BinLogCurrent $binLogCurrent)
    {
        Cache::put(self::getCacheKey(), serialize($binLogCurrent), 60);
    }

    public static function clear()
    {
        Cache::forget(self::getCacheKey());
    }

    public static function startFromPosition(ConfigBuilder $builder, Command $command = null)
    {
        $binLogCache = Cache::get(self::getCacheKey());
        if (!$binLogCache) {
            $command->info('cache of position expired');
            return $builder;
        }
        $binLogCurrent = unserialize($binLogCache);
        if (!$binLogCurrent) {
            $command->info('position unserialize faild');
            return $builder;
        }
        $info = sprintf(
            'starting from file:%s, position:%s bin log position',
            $binLogCurrent->getBinFileName(),
            $binLogCurrent->getBinLogPosition()
        );
        $command->info($info);
        return $builder
            ->withBinLogFileName($binLogCurrent->getBinFileName())
            ->withBinLogPosition($binLogCurrent->getBinLogPosition());
    }
}
