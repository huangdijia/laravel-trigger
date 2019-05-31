<?php

namespace Huangdijia\Trigger\Console;

use Huangdijia\Trigger\Subscribers\Heartbeat;
use Huangdijia\Trigger\Subscribers\Terminate;
use Huangdijia\Trigger\Subscribers\Trigger;
use Huangdijia\Trigger\Facades\Bootstrap;
use Illuminate\Console\Command;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\MySQLReplicationFactory;

class StartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:start';
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
        start:
        try {
            // print informations
            $this->option('verbose') && $this->info('Configure');
            $this->option('verbose') && $this->table(['Name', 'Value'], [
                ['Host', config('trigger.host')],
                ['Port', config('trigger.port')],
                ['User', config('trigger.user')],
                ['Password', config('trigger.password')],
            ]);

            // create replication
            $binLogStream = new MySQLReplicationFactory(
                Bootstrap::startFromPosition(new ConfigBuilder(), $this)
                    ->withSlaveId(time())
                    ->withHost(config('trigger.host', ''))
                    ->withPort(config('trigger.port', ''))
                    ->withUser(config('trigger.user', ''))
                    ->withPassword(config('trigger.password', ''))
                    ->withDatabasesOnly(config('trigger.databases', []))
                    ->withTablesOnly(config('trigger.tables', []))
                    ->withHeartbeatPeriod(config('trigger.heartbeat', 3))
                    ->build()
            );

            // Register subscribers and run
            collect(config('trigger.subscribers', []))
                ->reject(function ($subscriber) {
                    return !is_subclass_of($subscriber, EventSubscribers::class);
                })
                ->merge([Trigger::class, Terminate::class, Heartbeat::class])
                ->unique()
                ->each(function ($subscriber) use ($binLogStream) {
                    $binLogStream->registerSubscriber(app($subscriber));
                })
                ->tap(function ($subscribers) use ($binLogStream) {
                    $this->info('Registered Subscribers');
                    $this->table(['Subscriber', 'Registerd'], $subscribers->transform(function($subscriber) { return [$subscriber, '√'];}));

                    $this->info("\nTrigger running");
                    $binLogStream->run();
                });

        } catch (MySQLReplicationException $e) {
            $this->error($e->getMessage());

            // clear replication cache
            Bootstrap::clear();

            // retry
            $this->info('Retry now');
            sleep(1);

            goto start;
        }
    }
}
