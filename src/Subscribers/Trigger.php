<?php

declare(strict_types=1);
/**
 * This file is part of hyperf/helpers.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/4.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Huangdijia\Trigger\Subscribers;

use Huangdijia\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\EventDTO;

class Trigger extends EventSubscriber
{
    protected function allEvents(EventDTO $event): void
    {
        $this->trigger->dispatch($event);
    }
}
