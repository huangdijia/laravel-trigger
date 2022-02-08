<?php

declare(strict_types=1);
/**
 * This file is part of hyperf/helpers.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/4.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Huangdijia\Trigger;

use InvalidArgumentException;

class Manager
{
    /**
     * Replications.
     * @var Trigger[]
     */
    protected $replications;

    public function __construct(
        /*
         * Configs.
         */
        protected array $config = []
    ) {
    }

    /**
     * call.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->replication()->{$method}(...$parameters);
    }

    /**
     * Create new replication.
     *
     * @return Trigger
     */
    public function replication(?string $name = null)
    {
        $name ??= $this->config['default'] ?? 'default';

        if (! isset($this->replications[$name])) {
            if (! isset($this->config['replications'][$name])) {
                new InvalidArgumentException("Config 'trigger.replications.{$name}' is undefined", 1);
            }

            // load config
            $config = $this->config['replications'][$name];

            /* @var Trigger[] */
            $this->replications[$name] = new Trigger($name, $config);

            // load routes
            $this->replications[$name]->loadRoutes();

            // auto detect
            if ($this->replications[$name]->getConfig('detect')) {
                $this->replications[$name]->detectDatabasesAndTables();
            }
        }

        return $this->replications[$name];
    }

    /**
     * Get all replications.
     *
     * @return array
     */
    public function replications()
    {
        return $this->replications;
    }
}
