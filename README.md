# laravel-trigger

[![Latest Stable Version](https://poser.pugx.org/huangdijia/laravel-trigger/version.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![Total Downloads](https://poser.pugx.org/huangdijia/laravel-trigger/d/total.png)](https://packagist.org/packages/huangdijia/laravel-trigger)

MySQL trigger base on MySQLReplication

## Installation

### Laravel

install

~~~bash
composer require huangdijia/laravel-trigger
~~~

publish config

~~~bash
php artisan vendor:publish --provider="Huangdijia\Trigger\LaravelServiceProvider"
~~~

### Lumen

install

~~~bash
composer require huangdijia/laravel-trigger
~~~

copy `trigger.php` to `config/`

~~~bash
cp vendor/huangdijia/laravel-trigger/config/trigger.php config/
~~~

edit `bootstrap/app.php` add:

~~~php
$app->register(Huangdijia\Trigger\LumenServiceProvider::class);
...
$app->configure('trigger');
~~~

### Configure

edit `.env`, add:

~~~env
TRIGGER_HOST=192.168.xxx.xxx
TRIGGER_PORT=3306
TRIGGER_USER=username
TRIGGER_PASSWORD=password
...
~~~

## Usage

~~~bash
php artisan trigger:start
~~~

## Example

~~~php
<?php
namespace App\Events;

use Huangdijia\Trigger\Event;

class ExampeEvent extends Event
{
    public function onUpdate(UpdateRowsDTO $event)
    {
        //
    }

    public function onDelete(DeleteRowsDTO $event)
    {
        //
    }

    public function onWrite(WriteRowsDTO $event)
    {
        //
    }
}
~~~

more usage, look at [EventSubscribers](https://github.com/krowinski/php-mysql-replication/blob/master/src/MySQLReplication/Event/EventSubscribers.php)