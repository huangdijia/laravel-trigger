<?php

namespace Huangdijia\Trigger;

class Manager
{
    protected $config;
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

        if (isset($this->replications[$name])) {
            return $this->replications[$name];
        }

        if (!isset($this->config['replications'][$name])) {
            throw new \Exception("Config 'trigger.replications.{$name}' is undefined", 1);
        }

        $config = $this->config['replications'][$name];

        $this->replications[$name] = new Trigger($name, $config);
        $this->replications[$name]->loadRoutes();

        if ($this->replications[$name]->getConfig('detect')) {
            $this->replications[$name]->detectDatabasesAndTables();
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
