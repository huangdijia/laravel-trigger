<?php

namespace Huangdijia\Trigger\Facades;

use Illuminate\Support\Facades\Facade;
use Huangdijia\Trigger\Trigger as Accessor;

class Trigger extends Facade
{
    public static function getFacadeAccessor()
    {
        return Accessor::class;
    }
}