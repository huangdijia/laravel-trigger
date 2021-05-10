# laravel-trigger

[![Latest Stable Version](https://poser.pugx.org/huangdijia/laravel-trigger/version.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![Total Downloads](https://poser.pugx.org/huangdijia/laravel-trigger/d/total.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![GitHub license](https://img.shields.io/github/license/huangdijia/laravel-trigger)](https://github.com/huangdijia/laravel-trigger)

Subscribe MySQL events like jQuery, base on [php-mysql-replication](https://github.com/krowinski/php-mysql-replication)

[中文说明](README-CN.md)

## MySQL server settings

In your MySQL server configuration file you need to enable replication:

~~~bash
[mysqld]
server-id        = 1
log_bin          = /var/log/mysql/mysql-bin.log
expire_logs_days = 10
max_binlog_size  = 100M
binlog_row_image = full
binlog-format    = row #Very important if you want to receive write, update and delete row events
Mysql replication events explained https://dev.mysql.com/doc/internals/en/event-meanings.html
~~~

Mysql user privileges:

~~~bash
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'user'@'host';

GRANT SELECT ON `dbName`.* TO 'user'@'host';
~~~

## Installation

### Laravel

install

~~~bash
composer require "huangdijia/laravel-trigger:^2.0"
~~~

publish config

~~~bash
php artisan vendor:publish --provider="Huangdijia\Trigger\TriggerServiceProvider"
~~~

### Lumen

install

~~~bash
composer require "huangdijia/laravel-trigger:^2.0"
~~~

edit `bootstrap/app.php` add:

~~~php
$app->register(Huangdijia\Trigger\TriggerServiceProvider::class);
...
$app->configure('trigger');
~~~

publish config and route

~~~bash
php artisan trigger:install [--force]
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
php artisan trigger:start [-R=xxx]
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

### common

~~~php
$trigger->on('database.table', 'write', function($event) { /* do something */ });
~~~

### multi-tables and multi-evnets

~~~php
$trigger->on('database.table1,database.table2', 'write,update', function($event) { /* do something */ });
~~~

### multi-events

~~~php
$trigger->on('database.table1,database.table2', [
    'write'  => function($event) { /* do something */ },
    'update' => function($event) { /* do something */ },
]);
~~~

### action as controller

~~~php
$trigger->on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController'); // call default method 'handle'
$trigger->on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController@write');
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

$trigger->on('database.table', 'write', 'Foo@bar'); // call default method 'handle'
$trigger->on('database.table', 'write', ['Foo', 'bar']);
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
$trigger->on('database.table', 'write', 'App\Jobs\ExampleJob'); // call default method 'dispatch'
$trigger->on('database.table', 'write', 'App\Jobs\ExampleJob@dispatch_now');
~~~

## Event List

~~~bash
php artisan trigger:list [-R=xxx]
~~~

## Terminate

~~~bash
php artisan trigger:terminate [-R=xxx]
~~~

## Thanks to

[JetBrains](https://www.jetbrains.com/?from=huangdijia/laravel-trigger)
