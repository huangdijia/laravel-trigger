<?php

namespace Huangdijia\Trigger;

use Illuminate\Support\ServiceProvider;

class TriggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (is_file(app()->basePath('routes/trigger.php'))) {
            include app()->basePath('routes/trigger.php');
        }
    }

    public function register()
    {
        $this->configure();
        $this->registerCommands();

        $this->app->singleton(Trigger::class, function ($app) {
            return new Trigger;
        });
        $this->app->alias(Trigger::class, 'trigger');

        $this->app->singleton(Bootstrap::class, function ($app) {
            return new Bootstrap;
        });
        $this->app->alias(Bootstrap::class, 'trigger.bootstrap');
    }

    public function configure()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/trigger.php', 'trigger');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/trigger.php' => $this->app->basePath('config/trigger.php')]);
            $this->publishes([__DIR__ . '/../routes/trigger.php' => $this->app->basePath('routes/trigger.php')]);
        }
    }

    public function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\StartCommand::class,
                Console\ListCommand::class,
                Console\TerminateCommand::class,
            ]);
        }
    }

    public function provides()
    {
        return [
            Trigger::class,
            'trigger',
            Bootstrap::class,
            'trigger',
        ];
    }
}
