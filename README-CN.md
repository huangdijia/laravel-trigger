# laravel-trigger

[![Latest Test](https://github.com/huangdijia/laravel-trigger/workflows/tests/badge.svg)](https://github.com/huangdijia/laravel-trigger/actions)
[![Latest Stable Version](https://poser.pugx.org/huangdijia/laravel-trigger/version.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![Total Downloads](https://poser.pugx.org/huangdijia/laravel-trigger/d/total.png)](https://packagist.org/packages/huangdijia/laravel-trigger)

像jQuery一样订阅MySQL事件, 基于 [php-mysql-replication](https://github.com/krowinski/php-mysql-replication)

[English Document](README.md)

## MySQL 配置

同步配置:

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

用户权限:

~~~bash
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'user'@'host';

GRANT SELECT ON `dbName`.* TO 'user'@'host';
~~~

## 安装

### Laravel

composer 安装

~~~bash
composer require "huangdijia/laravel-trigger:^3.0"
~~~

发布配置

~~~bash
php artisan vendor:publish --provider="Huangdijia\Trigger\TriggerServiceProvider"
~~~

### Lumen

composer 安装

~~~bash
composer require "huangdijia/laravel-trigger:^3.0"
~~~

编辑 `bootstrap/app.php`，注册服务及加载配置:

~~~php
$app->register(Huangdijia\Trigger\TriggerServiceProvider::class);
...
$app->configure('trigger');
~~~

publish config and route

~~~bash
php artisan trigger:install [--force]
~~~

### 配置

编辑 `.env`, 配置以下内容:

~~~env
TRIGGER_HOST=192.168.xxx.xxx
TRIGGER_PORT=3306
TRIGGER_USER=username
TRIGGER_PASSWORD=password
...
~~~

## 启动服务

~~~bash
php artisan trigger:start [-R=xxx]
~~~

## 事件订阅

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

更多事件说明

[EventSubscribers](https://github.com/krowinski/php-mysql-replication/blob/master/src/MySQLReplication/Event/EventSubscribers.php)

## 事件路由

### 单表单事件

~~~php
$trigger->on('database.table', 'write', function($event) { /* do something */ });
~~~

### 多表多事件

~~~php
$trigger->on('database.table1,database.table2', 'write,update', function($event) { /* do something */ });
~~~

### 多事件

~~~php
$trigger->on('database.table1,database.table2', [
    'write'  => function($event) { /* do something */ },
    'update' => function($event) { /* do something */ },
]);
~~~

### 路由到操作

~~~php
$trigger->on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController'); // call default method 'handle'
$trigger->on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController@write');
~~~

### 路由到回调

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

### 路由到任务

任务

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

路由

~~~php
$trigger->on('database.table', 'write', 'App\Jobs\ExampleJob'); // call default method 'dispatch'
$trigger->on('database.table', 'write', 'App\Jobs\ExampleJob@dispatch_now');
~~~

## 查看事件列表

~~~bash
php artisan trigger:list [-R=xxx]
~~~

## 终止服务

~~~bash
php artisan trigger:terminate [-R=xxx]
~~~

## 鸣谢

[JetBrains](https://www.jetbrains.com/?from=huangdijia/laravel-trigger)
