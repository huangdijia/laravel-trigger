<?php

namespace Huangdijia\Trigger\Subscribers;

use Huangdijia\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\EventDTO;

class Terminate extends EventSubscriber
{
    /**
     * @param EventDTO $event
     */
    protected function allEvents(EventDTO $event): void
    {
        if ($this->trigger->isTerminated()) {
            die('Terminated');
        }
    }
}
