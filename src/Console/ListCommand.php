<?php

namespace Huangdijia\Trigger\Console;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Huangdijia\Trigger\Facades\Trigger;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:list';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List trigger events';
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $actions = collect(Arr::dot(Trigger::getEvents()))->transform(function($action, $key) use (&$actions) {
            return explode('.', $key . '.' . $this->actionToString($action));
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
