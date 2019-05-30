<?php

namespace Huangdijia\Trigger;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Event\DTO\HeartbeatDTO;

class Bootstrap
{
    public function __construct()
    {
        //
    }

    /**
     * get cache key
     *
     * @return string
     */
    public function getCacheKey()
    {
        return config('trigger.cache_key', 'trigger:replication');
    }

    /**
     * set replication position cache
     *
     * @return void
     */
    public function heartbeat(HeartbeatDTO $event)
    {
        $this->save($event->getEventInfo()->getBinLogCurrent());
    }

    /**
     * save replication position cache
     *
     * @return void
     */
    public function save(BinLogCurrent $binLogCurrent)
    {
        Cache::put($this->getCacheKey(), serialize($binLogCurrent), Carbon::now()->addHours(1));
    }

    /**
     * clear replication position cache
     *
     * @return void
     */
    public function clear()
    {
        Cache::forget($this->getCacheKey());
    }

    /**
     * Set replication position
     *
     * @param \MySQLReplication\Config\ConfigBuilder $builder
     * @param \Illuminate\Console\Command $command
     * @return \MySQLReplication\Config\ConfigBuilder
     */
    public function startFromPosition(ConfigBuilder $builder, Command $command = null)
    {
        $binLogCache = Cache::get($this->getCacheKey());

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
