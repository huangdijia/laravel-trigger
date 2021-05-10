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
     * @var Trigger[]
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
     * @return Trigger
     */
    public function replication(?string $name = null)
    {
        $name = $name ?? $this->config['default'] ?? 'default';

        if (!isset($this->replications[$name])) {
            if (!isset($this->config['replications'][$name])) {
                new InvalidArgumentException("Config 'trigger.replications.{$name}' is undefined", 1);
            }

            // load config
            $config = $this->config['replications'][$name];

            /** @var Trigger[] */
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
