<?php

namespace Huangdijia\Trigger\Subscribers;

use Huangdijia\Trigger\EventSubscriber;
use Huangdijia\Trigger\Facades\Trigger as TriggerFacade;
use MySQLReplication\Event\DTO\EventDTO;

class Trigger extends EventSubscriber
{
    /**
     * @param EventDTO $event
     */
    protected function allEvents(EventDTO $event): void
    {
        TriggerFacade::dispatch($event);
    }
}
