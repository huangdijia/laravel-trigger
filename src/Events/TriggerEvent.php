<?php

namespace Huangdijia\Trigger\Events;

use Huangdijia\Trigger\Event;
use Huangdijia\Trigger\Facades\Trigger;
use MySQLReplication\Event\DTO\EventDTO;

class TriggerEvent extends Event
{
    /**
     * @param EventDTO $event
     */
    protected function allEvents(EventDTO $event)
    {
        Trigger::dispatch($event);
    }
}
