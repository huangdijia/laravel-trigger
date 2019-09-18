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

        return $this->replications[$name];
    }

    public function replications()
    {
        return $this->replications;
    }

    public function __call($method, $parameters)
    {
        return $this->replication()->{$method}(...$parameters);
    }
}
