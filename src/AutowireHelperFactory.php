<?php

namespace Micro\Component\DependencyInjection\Autowire;

use Psr\Container\ContainerInterface;

class AutowireHelperFactory implements AutowireHelperFactoryInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(): AutowireHelperInterface
    {
        return new AutowireHelper($this->container);
    }
}