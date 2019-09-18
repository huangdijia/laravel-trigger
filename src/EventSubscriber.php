<?php

namespace Huangdijia\Trigger;

use MySQLReplication\Event\EventSubscribers;

class EventSubscriber extends EventSubscribers
{
    protected $trigger;

    public function __construct(Trigger $trigger)
    {
        $this->trigger = $trigger;
    }
}
