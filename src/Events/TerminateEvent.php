<?php

namespace Huangdijia\Trigger\Events;

use Huangdijia\Trigger\Event;
use Huangdijia\Trigger\Facades\Bootstrap;
use MySQLReplication\Event\DTO\EventDTO;

class TerminateEvent extends Event
{
    /**
     * @param EventDTO $event
     */
    protected function allEvents(EventDTO $event)
    {
        if (Bootstrap::isTerminated()) {
            die('Terminated');
        }
    }
}
