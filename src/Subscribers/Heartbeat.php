<?php

namespace Huangdijia\Trigger\Subscribers;

use Huangdijia\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\HeartbeatDTO;
use Huangdijia\Trigger\Facades\Bootstrap;

class Heartbeat extends EventSubscriber
{
    public function onHeartbeat(HeartbeatDTO $event)
    {
        Bootstrap::heartbeat($event);
    }
}
