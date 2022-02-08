<?php

declare(strict_types=1);
/**
 * This file is part of hyperf/helpers.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Huangdijia\Trigger\Subscribers;

use Huangdijia\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\EventDTO;

class Terminate extends EventSubscriber
{
    protected function allEvents(EventDTO $event): void
    {
        if ($this->trigger->isReseted()) {
            $this->trigger->clearCurrent();
        }

        if ($this->trigger->isTerminated()) {
            exit('Terminated');
        }
    }
}
