<?php

namespace Huangdijia\Trigger\Console;

use Closure;
use Huangdijia\Trigger\Facades\Trigger;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:list {--database= : Filter by database} {--table= : Filter by table} {--event= : Filter by event}';
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
        $actions = collect(Arr::dot(Trigger::getEvents()))
            ->transform(function ($action, $key) {
                [$database, $table, $event, $num, $action] = explode('.', $key . '.' . $this->actionToString($action));
                return [
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
            ->transform(function ($item) {
                return [
                    $item['database'],
                    $item['table'],
                    $item['event'],
                    $item['num'],
                    $item['action'],
                ];
            });

        $this->table(['Database', 'Table', 'Event', 'Num', 'Action'], $actions);
    }

    public function actionToString($action)
    {
        if ($action instanceof Closure) {
            $action = 'Closure';
        } elseif (is_object($action)) {
            $action = get_class($action);
        } elseif (is_array($action)) {
            $action = sprintf('%s@%s', $action[0], $action[1] ?? 'handle');
        } elseif (is_string($action)) {
            if (false === strpos($action, '@')) {
                $action .= '@handle';
            }
        }

        return $action;
    }
}
