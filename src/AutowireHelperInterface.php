<?php

namespace Micro\Component\DependencyInjection\Autowire;

interface AutowireHelperInterface
{
    /**
     * @param string|array|callable $target
     * @return callable
     */
    public function autowire(string|array|callable $target): callable;
}