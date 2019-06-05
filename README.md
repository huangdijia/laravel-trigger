# laravel-trigger

[![Latest Stable Version](https://poser.pugx.org/huangdijia/laravel-trigger/version.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![Total Downloads](https://poser.pugx.org/huangdijia/laravel-trigger/d/total.png)](https://packagist.org/packages/huangdijia/laravel-trigger)

Subscribe MySQL events like jQuery, base on [php-mysql-replication](https://github.com/krowinski/php-mysql-replication)

[中文说明](README-CN.md)

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

## Subscriber

~~~php
<?php
namespace App\Listeners;

use Huangdijia\Trigger\EventSubscriber;

class ExampeSubscriber extends EventSubscriber
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

more subscriber usage

[EventSubscribers](https://github.com/krowinski/php-mysql-replication/blob/master/src/MySQLReplication/Event/EventSubscribers.php)

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

### action as callable

~~~php
class Foo
{
    public static function bar($event)
    {
        dump($event);
    }
}

Trigger::on('database.table', 'write', 'Foo@bar'); // call default method 'handle'
Trigger::on('database.table', 'write', ['Foo', 'bar']);
~~~

### action as job

Job

~~~php
namespace App\Jobs;

class ExampleJob extends Job
{
    private $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    public function handle()
    {
        dump($this->event);
    }
}

~~~

Route

~~~php
Trigger::on('database.table', 'write', 'App\Jobs\ExampleJob'); // call default method 'dispatch'
Trigger::on('database.table', 'write', 'App\Jobs\ExampleJob@dispatch_now');
~~~

## Event List

~~~bash
php artisan trigger:list
~~~

## Terminate

~~~bash
php artisan trigger:terminate
~~~
