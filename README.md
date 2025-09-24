# laravel-trigger

[![Latest Test](https://github.com/huangdijia/laravel-trigger/workflows/tests/badge.svg)](https://github.com/huangdijia/laravel-trigger/actions)
[![Latest Stable Version](https://poser.pugx.org/huangdijia/laravel-trigger/version.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![Total Downloads](https://poser.pugx.org/huangdijia/laravel-trigger/d/total.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![GitHub license](https://img.shields.io/github/license/huangdijia/laravel-trigger)](https://github.com/huangdijia/laravel-trigger)

Subscribe to MySQL events like jQuery, based on [php-mysql-replication](https://github.com/krowinski/php-mysql-replication)

[中文说明](README-CN.md)

## Table of Contents

- [MySQL Server Configuration](#mysql-server-configuration)
- [Installation](#installation)
- [Usage](#usage)
- [Event Subscribers](#event-subscribers)
- [Event Routes](#event-routes)
- [Management Commands](#management-commands)
- [Thanks to](#thanks-to)

## MySQL Server Configuration

### Replication Settings

In your MySQL server configuration file, you need to enable replication:

~~~bash
[mysqld]
server-id        = 1
log_bin          = /var/log/mysql/mysql-bin.log
expire_logs_days = 10
max_binlog_size  = 100M
binlog_row_image = full
binlog-format    = row #Very important if you want to receive write, update and delete row events
~~~

For more information: [MySQL replication events explained](https://dev.mysql.com/doc/internals/en/event-meanings.html)

### User Privileges

Grant the necessary privileges to your MySQL user:

~~~bash
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'user'@'host';

GRANT SELECT ON `dbName`.* TO 'user'@'host';
~~~

## Installation

### Laravel

Install via Composer:

~~~bash
composer require "huangdijia/laravel-trigger:^4.0"
~~~

Publish the configuration file:

~~~bash
php artisan vendor:publish --provider="Huangdijia\Trigger\TriggerServiceProvider"
~~~

### Lumen

Install via Composer:

~~~bash
composer require "huangdijia/laravel-trigger:^4.0"
~~~

Edit `bootstrap/app.php` and add:

~~~php
$app->register(Huangdijia\Trigger\TriggerServiceProvider::class);
...
$app->configure('trigger');
~~~

Publish configuration and routes:

~~~bash
php artisan trigger:install [--force]
~~~

### Configure

Edit your `.env` file and add the following configuration:

~~~env
TRIGGER_HOST=192.168.xxx.xxx
TRIGGER_PORT=3306
TRIGGER_USER=username
TRIGGER_PASSWORD=password
...
~~~

## Usage

Start the trigger service to begin listening for MySQL events:

~~~bash
php artisan trigger:start [-R=xxx]
~~~

The service will monitor your MySQL binary log and trigger registered event handlers when database changes occur.

## Event Subscribers

Create a custom event subscriber by extending the `EventSubscriber` class:

~~~php
<?php
namespace App\Listeners;

use Huangdijia\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;

class ExampleSubscriber extends EventSubscriber
{
    public function onUpdate(UpdateRowsDTO $event)
    {
        // Handle UPDATE events
    }

    public function onDelete(DeleteRowsDTO $event)
    {
        // Handle DELETE events
    }

    public function onWrite(WriteRowsDTO $event)
    {
        // Handle INSERT events
    }
}
~~~

For more subscriber usage examples, see:
[EventSubscribers](https://github.com/krowinski/php-mysql-replication/blob/master/src/MySQLReplication/Event/EventSubscribers.php)

## Event Routes

### Basic Usage

~~~php
$trigger->on('database.table', 'write', function($event) { /* do something */ });
~~~

### Multi-tables and Multi-events

~~~php
$trigger->on('database.table1,database.table2', 'write,update', function($event) { /* do something */ });
~~~

### Multi-events

~~~php
$trigger->on('database.table1,database.table2', [
    'write'  => function($event) { /* do something */ },
    'update' => function($event) { /* do something */ },
]);
~~~

### Action as Controller

~~~php
$trigger->on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController'); // calls default method 'handle'
$trigger->on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController@write');
~~~

### Action as Callable

~~~php
class Foo
{
    public static function bar($event)
    {
        dump($event);
    }
}

$trigger->on('database.table', 'write', 'Foo@bar');
$trigger->on('database.table', 'write', ['Foo', 'bar']);
~~~

### Action as Job

Define your job class:

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

Register the job route:

~~~php
$trigger->on('database.table', 'write', 'App\Jobs\ExampleJob'); // calls default method 'dispatch'
$trigger->on('database.table', 'write', 'App\Jobs\ExampleJob@dispatch_now');
~~~

## Management Commands

### List Events

View all registered event listeners:

~~~bash
php artisan trigger:list [-R=xxx]
~~~

### Terminate Service

Stop the trigger service gracefully:

~~~bash
php artisan trigger:terminate [-R=xxx]
~~~

## Thanks to

[JetBrains](https://www.jetbrains.com/?from=huangdijia/laravel-trigger)
