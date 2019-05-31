<?php

namespace Huangdijia\Trigger\Events;

use Huangdijia\Trigger\Event;
use MySQLReplication\Event\DTO\HeartbeatDTO;
use Huangdijia\Trigger\Facades\Bootstrap;

class HeartbeatEvent extends Event
{
    public function onHeartbeat(HeartbeatDTO $event)
    {
        Bootstrap::heartbeat($event);
    }
}
