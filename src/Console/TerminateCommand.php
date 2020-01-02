<?php

namespace Huangdijia\Trigger\Console;

use Huangdijia\Trigger\Facades\Trigger;
use Illuminate\Console\Command;

class TerminateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:terminate {--R|replication=default : replication} {--reset : reset replication position}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate the process so it can be restarted';
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $trigger = Trigger::replication($this->option('replication'));

        $trigger->terminate();
        $this->info('Broadcasting restart signal.');

        if ($this->option('reset')) {
            $trigger->reset();
            $this->info('Replication position reseted.');
        }

    }
}
