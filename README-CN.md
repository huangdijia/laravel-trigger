# laravel-trigger

[![Latest Stable Version](https://poser.pugx.org/huangdijia/laravel-trigger/version.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![Total Downloads](https://poser.pugx.org/huangdijia/laravel-trigger/d/total.png)](https://packagist.org/packages/huangdijia/laravel-trigger)

像jQuery一样订阅MySQL事件, 基于 [php-mysql-replication](https://github.com/krowinski/php-mysql-replication)

[English Document](README.md)

## 安装

### Laravel

composer 安装

~~~bash
composer require huangdijia/laravel-trigger
~~~

发布配置

~~~bash
php artisan vendor:publish --provider="Huangdijia\Trigger\TriggerServiceProvider"
~~~

### Lumen

composer 安装

~~~bash
composer require huangdijia/laravel-trigger
~~~

复制配置 `config/trigger.php` 到 `config/`

~~~bash
cp vendor/huangdijia/laravel-trigger/config/trigger.php config/
~~~

复制路由 `routes/trigger.php` 到 `routes/`

~~~bash
cp vendor/huangdijia/laravel-trigger/routes/trigger.php routes/
~~~

编辑 `bootstrap/app.php`，注册服务及加载配置:

~~~php
$app->register(Huangdijia\Trigger\TriggerServiceProvider::class);
...
$app->configure('trigger');
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
php artisan trigger:start
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

~~~php
use Huangdijia\Trigger\Facades\Trigger;
~~~

### 单表单事件

~~~php
Trigger::on('database.table', 'write', function($event) { /* do something */ });
~~~

### 多表多事件

~~~php
Trigger::on('database.table1,database.table2', 'write,update', function($event) { /* do something */ });
~~~

### 多事件

~~~php
Trigger::on('database.table1,database.table2', [
    'write'  => function($event) { /* do something */ },
    'update' => function($event) { /* do something */ },
]);
~~~

### 路由到操作

~~~php
Trigger::on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController'); // call default method 'handle'
Trigger::on('database.table', 'write', 'App\\Http\\Controllers\\ExampleController@write');
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

Trigger::on('database.table', 'write', 'Foo@bar'); // call default method 'handle'
Trigger::on('database.table', 'write', ['Foo', 'bar']);
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
Trigger::on('database.table', 'write', 'App\Jobs\ExampleJob'); // call default method 'dispatch'
Trigger::on('database.table', 'write', 'App\Jobs\ExampleJob@dispatch_now');
~~~

## 查看事件列表

~~~bash
php artisan trigger:list
~~~

## 终止服务

~~~bash
php artisan trigger:terminate
~~~
