<?php

/** @var \Huangdijia\Trigger\Trigger $trigger */

$trigger->on('*', 'heartbeat', function($event) use ($trigger) {
    $trigger->heartbeat($event);
});
