# laravel-trigger

[![Latest Test](https://github.com/huangdijia/laravel-trigger/workflows/tests/badge.svg)](https://github.com/huangdijia/laravel-trigger/actions)
[![Latest Stable Version](https://poser.pugx.org/huangdijia/laravel-trigger/version.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![Total Downloads](https://poser.pugx.org/huangdijia/laravel-trigger/d/total.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![GitHub license](https://img.shields.io/github/license/huangdijia/laravel-trigger)](https://github.com/huangdijia/laravel-trigger)

像jQuery一样订阅MySQL事件，基于 [php-mysql-replication](https://github.com/krowinski/php-mysql-replication)

[English Document](README.md)

## 快速开始

1. 安装包: `composer require "huangdijia/laravel-trigger:^4.0"`
2. 配置MySQL服务器进行复制 (参见 [MySQL 配置](#mysql-配置))
3. 发布配置: `php artisan vendor:publish --provider="Huangdijia\Trigger\TriggerServiceProvider"`
4. 在 `.env` 文件中配置数据库凭证
5. 开始监听: `php artisan trigger:start`

## 目录

- [快速开始](#快速开始)
- [MySQL 配置](#mysql-配置)
- [安装](#安装)
- [启动服务](#启动服务)
- [事件订阅](#事件订阅)
- [事件路由](#事件路由)
- [管理命令](#管理命令)
- [鸣谢](#鸣谢)

## MySQL 配置

### 同步配置

~~~bash
[mysqld]
server-id        = 1
log_bin          = /var/log/mysql/mysql-bin.log
expire_logs_days = 10
max_binlog_size  = 100M
binlog_row_image = full
binlog-format    = row #Very important if you want to receive write, update and delete row events
~~~

更多信息请参考: [MySQL 复制事件说明](https://dev.mysql.com/doc/internals/en/event-meanings.html)

### 用户权限

~~~bash
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'user'@'host';

GRANT SELECT ON `dbName`.* TO 'user'@'host';
~~~

## 安装

### Laravel

composer 安装

~~~bash
composer require "huangdijia/laravel-trigger:^4.0"
~~~

发布配置

~~~bash
php artisan vendor:publish --provider="Huangdijia\Trigger\TriggerServiceProvider"
~~~

### Lumen

composer 安装

~~~bash
composer require "huangdijia/laravel-trigger:^4.0"
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

创建自定义事件订阅器，继承 `EventSubscriber` 类：

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
        // 处理 UPDATE 事件
    }

    public function onDelete(DeleteRowsDTO $event)
    {
        // 处理 DELETE 事件
    }

    public function onWrite(WriteRowsDTO $event)
    {
        // 处理 INSERT 事件
    }
}
~~~

更多事件说明请参考：
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

## 管理命令

### 查看事件列表

~~~bash
php artisan trigger:list [-R=xxx]
~~~

### 终止服务

~~~bash
php artisan trigger:terminate [-R=xxx]
~~~

## 鸣谢

[JetBrains](https://www.jetbrains.com/?from=huangdijia/laravel-trigger)
