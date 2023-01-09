<?php

namespace Micro\Component\DependencyInjection\Autowire;

use Closure;
use Micro\Component\DependencyInjection\Container;

class ContainerAutowire extends Container
{
    /**
     * @var AutowireHelperFactoryInterface
     */
    private AutowireHelperFactoryInterface $autowireHelperFactory;

    /**
     * @param Container $container
     */
    public function __construct(private readonly Container $container)
    {
        $this->autowireHelperFactory = new AutowireHelperFactory($this->container);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $id): object
    {
        return $this->container->get($id);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * {@inheritDoc}
     */
    public function register(string $id, \Closure $service): void
    {
        $autowiredCallback = $this->autowireHelperFactory->create()->autowire($service);

        $this->container->register($id, $autowiredCallback);
    }

    /**
     * {@inheritDoc}
     */
    public function decorate(string $id, Closure $service, int $priority = 0): void
    {
        $autowiredCallback = $this->autowireHelperFactory->create()->autowire($service);

        $this->container->decorate($id, $autowiredCallback, $priority);
    }
}
