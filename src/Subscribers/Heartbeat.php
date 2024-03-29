<?php

declare(strict_types=1);
/**
 * This file is part of huangdijia/laravel-trigger.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/4.x/README.md
 * @contact  huangdijia@gmail.com
 */

namespace Huangdijia\Trigger\Subscribers;

use Huangdijia\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\HeartbeatDTO;

class Heartbeat extends EventSubscriber
{
    public function onHeartbeat(HeartbeatDTO $event): void
    {
        $this->trigger->heartbeat($event);
    }
}
