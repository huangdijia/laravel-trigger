<?php

declare(strict_types=1);
/**
 * This file is part of huangdijia/laravel-trigger.
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
    protected array $replications = [];

    public function __construct(protected array $config = [])
    {
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
     */
    public function replication(?string $name = null): Trigger
    {
        $name ??= $this->config['default'] ?? 'default';

        if (! isset($this->replications[$name])) {
            if (! isset($this->config['replications'][$name])) {
                throw new InvalidArgumentException("Config 'trigger.replications.{$name}' is undefined", 1);
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
     * @return Trigger[]
     */
    public function replications(): array
    {
        return $this->replications;
    }
}
