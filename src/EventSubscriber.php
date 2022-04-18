<?php

declare(strict_types=1);
/**
 * This file is part of huangdijia/laravel-trigger.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/4.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Huangdijia\Trigger;

use MySQLReplication\Event\EventSubscribers;

class EventSubscriber extends EventSubscribers
{
    public function __construct(protected Trigger $trigger)
    {
    }
}
