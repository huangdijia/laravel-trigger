<?php

namespace Huangdijia\Trigger;

use Illuminate\Support\ServiceProvider;

class TriggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register()
    {
        $this->configure();
        $this->registerCommands();

        $this->app->bind('trigger.manager', function ($app) {
            return new Manager($app->make('config')->get('trigger'));
        });
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
                Console\InstallCommand::class,
                Console\StatusCommand::class,
                Console\StartCommand::class,
                Console\ListCommand::class,
                Console\TerminateCommand::class,
            ]);
        }
    }

    public function provides()
    {
        return [
            'trigger.manager',
        ];
    }
}
