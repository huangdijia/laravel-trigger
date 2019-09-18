<?php

$trigger->on('*', 'heartbeat', function($event) use ($trigger) {
    $trigger->heartbeat($event);
});
