<?php

namespace Huangdijia\Trigger\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature   = 'trigger:install {--force}';
    protected $description = 'Install config and routes';

    public function handle()
    {
        collect([
            __DIR__ . '/../../config/trigger.php' => app()->basePath('config/trigger.php'),
            __DIR__ . '/../../routes/trigger.php' => app()->basePath('routes/trigger.php'),
        ])->each(function($target, $source) {
            if (!$this->option('force') && file_exists($target)) {
                $this->warn("{$target} already exists!");
            } else {
                file_put_contents($target, file_get_contents($source));
                $this->info($target . ' installed successfully.');
            }
        });
    }
}
