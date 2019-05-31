<?php

namespace Huangdijia\Trigger\Facades;

use Illuminate\Support\Facades\Facade;
use Huangdijia\Trigger\Trigger as Accessor;

/**
 * @method void on(string $table, $eventType, $action = null)
 * @method void fire($events, EventDTO $event = null)
 * @method void dispatch(EventDTO $event)
 * @method void getEvents()
 * @see Huangdijia\Trigger\Trigger
 */
class Trigger extends Facade
{
    public static function getFacadeAccessor()
    {
        return Accessor::class;
    }
}