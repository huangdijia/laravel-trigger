<?php

namespace Huangdijia\Trigger;

use Illuminate\Support\ServiceProvider;

class LaravelTriggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->configure();
        $this->registerCommands();
    }

    public function configure()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/trigger.php', 'trigger'
        );
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/trigger.php' => config_path('trigger.php')]);
        }
    }

    public function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\StartCommand::class,
            ]);
        }
    }
}
