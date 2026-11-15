<?php

declare(strict_types=1);
/**
 * This file is part of huangdijia/laravel-trigger.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/4.x/README.md
 * @contact  huangdijia@gmail.com
 */

namespace Huangdijia\Trigger\Console;

use Huangdijia\Trigger\Facades\Trigger;
use Illuminate\Console\Command;
use LogicException;

class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'trigger:status {--R|replication=default : replication}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Install config and routes.';

    public function handle(): int
    {
        $replication = $this->option('replication');
        $trigger = Trigger::replication($replication);

        try {
            $binLogCurrent = $trigger->getCurrent();
        } catch (LogicException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (is_null($binLogCurrent)) {
            $this->warn('binlog info of ' . $replication . ' is empty.');

            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'Value'],
            [
                ['BinLogPosition', $binLogCurrent->getBinLogPosition()],
                ['BinFileName', $binLogCurrent->getBinFileName()],
                // ['Gtid', $binLogCurrent->getGtid()],
                // ['MariaDbGtid', $binLogCurrent->getMariaDbGtid()],
            ]
        );

        return self::SUCCESS;
    }
}
