<?php

namespace Huangdijia\Trigger\Subscribers;

use Huangdijia\Trigger\EventSubscriber;
use Huangdijia\Trigger\Facades\Bootstrap;
use MySQLReplication\Event\DTO\HeartbeatDTO;

class Heartbeat extends EventSubscriber
{
    public function onHeartbeat(HeartbeatDTO $event): void
    {
        Bootstrap::heartbeat($event);
    }
}
