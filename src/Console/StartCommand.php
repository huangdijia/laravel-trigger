<?php

namespace Huangdijia\Trigger\Console;

use Huangdijia\Trigger\Facades\Trigger;
use Illuminate\Console\Command;
use MySQLReplication\Exception\MySQLReplicationException;

class StartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var stringå
     */
    protected $signature = 'trigger:start {--R|replication=default : replication}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start trigger service';
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $trigger = Trigger::replication($this->option('replication'));

        start:
        try {
            // print informations
            $this->option('verbose') && $this->info('Configure');
            $this->option('verbose') && $this->table(['Name', 'Value'], [
                ['Host', $trigger->getConfig('host')],
                ['Port', $trigger->getConfig('port')],
                ['User', $trigger->getConfig('user')],
                ['Password', $trigger->getConfig('password')],
            ]);

            // Register subscribers and run
            $trigger->start();

        } catch (MySQLReplicationException $e) {
            $this->error($e->getMessage());

            // clear replication cache
            $trigger->clearCurrent();

            // retry
            $this->info('Retry now');
            sleep(1);

            goto start;
        }
    }
}
