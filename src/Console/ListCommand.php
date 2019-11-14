<?php

namespace Huangdijia\Trigger\Console;

use Closure;
use Huangdijia\Trigger\Facades\Trigger;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:list {--R|replication=default : replication} {--database= : Filter by database} {--table= : Filter by table} {--event= : Filter by event}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all trigger events';
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $actions = Trigger::replication($this->option('replication'))->getEvents();

        collect(Arr::dot($actions))
            ->transform(function ($action, $key) use ($actions) {
                [$database, $table, $event, $num, $action] = explode('.', $key . '.' . $this->transformActionToString($action));

                $key = sprintf('%s.%s.%s.%s', $database, $table, $event, $num);

                if (is_numeric($action)) {
                    $action = Arr::get($actions, $key);
                    $action = $this->transformActionToString($action);
                }

                return [
                    'key'      => $key,
                    'database' => $database,
                    'table'    => $table,
                    'event'    => $event,
                    'num'      => $num,
                    'action'   => $action,
                ];
            })
            ->when($this->option('database'), function ($collection, $database) {
                return $collection->where('database', $database);
            })
            ->when($this->option('table'), function ($collection, $table) {
                return $collection->where('table', $table);
            })
            ->when($this->option('event'), function ($collection, $event) {
                return $collection->where('event', $event);
            })
            ->unique('key')
            ->transform(function ($item) {
                return [
                    $item['database'],
                    $item['table'],
                    $item['event'],
                    $item['num'],
                    $item['action'],
                ];
            })
            ->tap(function ($items) {
                $this->table(['Database', 'Table', 'Event', 'Num', 'Action'], $items);
            });
    }

    /**
     * Transform action to string
     *
     * @param Closure|object|array|string $action
     * @return string
     */
    public function transformActionToString($action)
    {
        if ($action instanceof Closure) {
            $action = 'Closure';
        } elseif (is_object($action)) {
            $action = get_class($action);
        } elseif (is_array($action)) {
            if (is_object($action[0])) {
                $action[0] = get_class($action[0]);
            }
            $action = sprintf('%s@%s', $action[0], $action[1] ?? 'handle');
        } elseif (is_string($action)) {
            if (false === strpos($action, '@')) {
                if (is_subclass_of($action, ShouldQueue::class)) {
                    //
                } else {
                    $action .= '@handle';
                }
            }
        }

        return $action;
    }
}
