<?php

namespace Huangdijia\Trigger\Subscribers;

use Huangdijia\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\EventDTO;

class Trigger extends EventSubscriber
{
    /**
     * @param EventDTO $event
     */
    protected function allEvents(EventDTO $event): void
    {
        $this->trigger->dispatch($event);
    }
}
