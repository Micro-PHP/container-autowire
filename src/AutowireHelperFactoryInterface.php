<?php

namespace Micro\Component\DependencyInjection\Autowire;

interface AutowireHelperFactoryInterface
{
    /**
     * @return AutowireHelperInterface
     */
    public function create(): AutowireHelperInterface;
}
