<?php

namespace Huangdijia\Trigger\Facades;

use Illuminate\Support\Facades\Facade;
use Huangdijia\Trigger\Bootstrap as Accessor;

class Bootstrap extends Facade
{
    public static function getFacadeAccessor()
    {
        return Accessor::class;
    }
}