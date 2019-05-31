<?php

namespace Huangdijia\Trigger;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Event\DTO\EventDTO;

class Bootstrap
{
    private $bootTime            = null;
    private $replicationCacheKey = 'trigger:replication';
    private $restartCacheKey     = 'trigger:restart';

    public function __construct()
    {
        $this->bootTime = time();
    }

    /**
     * Terminate
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
     * set replication position cache
     *
     * @return void
     */
    public function heartbeat(EventDTO $event)
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
        Cache::put($this->replicationCacheKey, serialize($binLogCurrent), Carbon::now()->addHours(1));
    }

    /**
     * clear replication position cache
     *
     * @return void
     */
    public function clear()
    {
        Cache::forget($this->replicationCacheKey);
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
        $binLogCache = Cache::get($this->replicationCacheKey);

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
