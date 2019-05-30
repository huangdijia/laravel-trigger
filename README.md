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
php artisan vendor:publish --provider="Huangdijia\Trigger\TriggerServiceProvider"
~~~

### Lumen

install

~~~bash
composer require huangdijia/laravel-trigger
~~~

copy `config/trigger.php` to `config/`

~~~bash
cp vendor/huangdijia/laravel-trigger/config/trigger.php config/
~~~

copy `routes/trigger.php` to `routes/`

~~~bash
cp vendor/huangdijia/laravel-trigger/routes/trigger.php routes/
~~~

edit `bootstrap/app.php` add:

~~~php
$app->register(Huangdijia\Trigger\TriggerServiceProvider::class);
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

## Event Example

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

## Event Route

~~~php
use Huangdijia\Trigger\Facades\Trigger;
~~~

### common

~~~php
Trigger::on('database.table', 'write', function($event) { /* do something */ });
~~~

### multi-tables and multi-evnets

~~~php
Trigger::on('database.table1,database.table2', 'write,update', function($event) { /* do something */ });
~~~

### multi-events

~~~php
Trigger::on('database.table1,database.table2', [
    'write'  => function($event) { /* do something */ },
    'update' => function($event) { /* do something */ },
]);
~~~

### action as controller

~~~php
Trigger::on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController'); // call default method 'handle'
Trigger::on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController@write');
~~~

## Event List

~~~bash
php artisan trigger:list
~~~

more usage, look at [EventSubscribers](https://github.com/krowinski/php-mysql-replication/blob/master/src/MySQLReplication/Event/EventSubscribers.php)