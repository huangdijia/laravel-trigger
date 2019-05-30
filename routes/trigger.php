<?php

use Huangdijia\Trigger\BinLogBootstrap;
use Huangdijia\Trigger\Facades\Trigger;

// Trigger::on('database.table', 'write', function ($event) { dump($evnet); });
// Trigger::on('database.table', 'update', function ($event) { dump($evnet); });
// Trigger::on('database.table', 'delete', function ($event) { dump($evnet); });

Trigger::on('*', 'heartbeat', function($event) {
    BinLogBootstrap::save($event->getEventInfo()->getBinLogCurrent());
});