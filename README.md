# laravel-trigger

[![Latest Stable Version](https://poser.pugx.org/huangdijia/laravel-trigger/version.png)](https://packagist.org/packages/huangdijia/laravel-trigger)
[![Total Downloads](https://poser.pugx.org/huangdijia/laravel-trigger/d/total.png)](https://packagist.org/packages/huangdijia/laravel-trigger)

Restart the Horizon supervisors of multiple servers like `php artisan queue:restart`

## Installation

### Laravel

~~~bash
composer require huangdijia/laravel-trigger
php artisan vendor:publish --provider="Huangdijia\Trigger\LaravelServiceProvider"
~~~

### Lumen

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