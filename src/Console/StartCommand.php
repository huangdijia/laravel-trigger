<?php

declare(strict_types=1);
/**
 * This file is part of hyperf/helpers.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Huangdijia\Trigger\Console;

use Huangdijia\Trigger\Facades\Trigger;
use Illuminate\Console\Command;
use MySQLReplication\Exception\MySQLReplicationException;

class StartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:start {--R|replication=default : replication} {--reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start trigger service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $keepup = $this->option('reset') ? false : true;
        $trigger = Trigger::replication($this->option('replication'));

        start:
        try {
            if ($this->option('verbose')) {
                $this->info('Configure');
                $this->table(
                    ['Name', 'Value'],
                    collect($trigger->getConfig())
                        ->merge(['bootat' => date('Y-m-d H:i:s')])
                        ->transform(function ($item, $key) {
                            if (! is_scalar($item)) {
                                $item = json_encode($item, JSON_THROW_ON_ERROR);
                            }

                            return [ucfirst($key), $item];
                        })
                );

                $binLogCurrent = $trigger->getCurrent();

                if ($keepup && ! is_null($binLogCurrent)) {
                    $this->info('BinLog');

                    $this->table(
                        ['Name', 'Value'],
                        [
                            ['BinLogPosition', $binLogCurrent->getBinLogPosition()],
                            ['BinFileName', $binLogCurrent->getBinFileName()],
                        ]
                    );
                }

                $this->info('Subscribers');
                $this->table(
                    ['Subscriber', 'Registerd'],
                    collect($trigger->getSubscribers())
                        ->transform(fn($subscriber) => [$subscriber, '√'])
                );
            }

            $trigger->start($keepup);
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
