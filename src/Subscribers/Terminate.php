<?php

namespace Huangdijia\Trigger\Subscribers;

use Huangdijia\Trigger\EventSubscriber;
use Huangdijia\Trigger\Facades\Bootstrap;
use MySQLReplication\Event\DTO\EventDTO;

class Terminate extends EventSubscriber
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
