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

use Doctrine\DBAL\Exception as DbalException;
use Huangdijia\Trigger\Facades\Trigger;
use Illuminate\Console\Command;
use MySQLReplication\Exception\MySQLReplicationException;
use PDOException;
use Throwable;

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
    protected $description = 'Start trigger service.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->listenForSignals();

        $keepUp = $this->option('reset') ? false : true;
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

                if ($keepUp && ! is_null($binLogCurrent)) {
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
                    ['Subscriber', 'Registered'],
                    collect($trigger->getSubscribers())
                        ->transform(fn ($subscriber) => [$subscriber, '√'])
                );
            }

            $trigger->start($keepUp);
        } catch (MySQLReplicationException $e) {
            $this->error($e->getMessage());

            // clear replication cache
            $trigger->clearCurrent();

            // retry
            $this->info('Retry now');
            sleep(1);

            goto start;
        } catch (DbalException|PDOException $e) {
            $this->error($e->getMessage());

            if (! $this->shouldRetry($e)) {
                throw $e;
            }

            // Keep current binlog position so we can resume after reconnect.
            $this->info('Retry now');
            sleep(1);

            goto start;
        }

        return Command::SUCCESS;
    }

    /**
     * Register SIGTERM/SIGINT handlers for immediate shutdown.
     *
     * The start() method blocks inside MySQLReplicationFactory::run() reading
     * from a socket. Setting a flag is insufficient because the blocking read
     * never returns to check it. We must exit directly from the signal handler,
     * matching the pattern used by the library's own Terminate subscriber.
     */
    protected function listenForSignals(): void
    {
        if (! function_exists('pcntl_async_signals') || ! function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(true);

        $handler = function (int $signal) {
            $name = $signal === SIGTERM ? 'SIGTERM' : 'SIGINT';
            $this->info("Received {$name}, shutting down...");

            exit(0);
        };

        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }

    private function shouldRetry(Throwable $e): bool
    {
        $pdo = $e instanceof PDOException ? $e : null;

        if ($pdo === null && $e->getPrevious() instanceof PDOException) {
            $pdo = $e->getPrevious();
        }

        if ($pdo !== null && isset($pdo->errorInfo[1])) {
            $driverCode = (int) $pdo->errorInfo[1];

            return in_array($driverCode, [2006, 2013, 2055, 4031], true);
        }

        $message = strtolower($e->getMessage());

        if (str_contains($message, 'access denied') || str_contains($message, 'unknown database')) {
            return false;
        }

        return str_contains($message, 'server has gone away')
            || str_contains($message, 'lost connection')
            || str_contains($message, 'disconnected by the server')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'connection timed out')
            || str_contains($message, 'broken pipe');
    }
}
