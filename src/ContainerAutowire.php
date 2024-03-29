<?php

/*
 *  This file is part of the Micro framework package.
 *
 *  (c) Stanislau Komar <kost@micro-php.net>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Micro\Component\DependencyInjection\Autowire;

use Micro\Component\DependencyInjection\Container;

class ContainerAutowire extends Container
{
    private AutowireHelperFactoryInterface $autowireHelperFactory;

    public function __construct(private readonly Container $container)
    {
        $this->autowireHelperFactory = new AutowireHelperFactory($this->container);
    }

    public function get(string $id): object
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    public function register(string $id, callable $service, bool $force = false): void
    {
        $autowiredCallback = $this->autowireHelperFactory->create()->autowire($service);

        $this->container->register($id, $autowiredCallback, $force);
    }

    public function decorate(string $id, callable $service, int $priority = 0): void
    {
        $autowiredCallback = $this->autowireHelperFactory->create()->autowire($service);

        $this->container->decorate($id, $autowiredCallback, $priority);
    }
}
