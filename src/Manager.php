<?php

namespace Huangdijia\Trigger;

use InvalidArgumentException;

class Manager
{
    /**
     * Configs
     * @var array
     */
    protected $config;
    /**
     * Replications
     * @var array
     */
    protected $replications;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Create new replication
     *
     * @param string|null $name
     * @return \Huangdijia\Trigger\Trigger
     */
    public function replication(?string $name = null)
    {
        $name = $name ?? $this->config['default'] ?? 'default';

        if (!isset($this->replications[$name])) {

            throw_if(
                !isset($this->config['replications'][$name]),
                new InvalidArgumentException("Config 'trigger.replications.{$name}' is undefined", 1)
            );

            $config = $this->config['replications'][$name];

            $this->replications[$name] = tap(new Trigger($name, $config), function ($trigger) {
                $trigger->loadRoutes();

                if ($trigger->getConfig('detect')) {
                    $trigger->detectDatabasesAndTables();
                }
            });
        }

        return $this->replications[$name];
    }

    /**
     * Get all replications
     *
     * @return array
     */
    public function replications()
    {
        return $this->replications;
    }

    /**
     * call
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->replication()->{$method}(...$parameters);
    }
}
