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

        $config = $this->config['replications'][$name] ?? [];

        return $this->replications[$name] = new Trigger($config);
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