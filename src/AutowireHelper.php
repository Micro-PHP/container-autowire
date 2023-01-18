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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AutowireHelper implements AutowireHelperInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function autowire(string|array|callable $target): callable
    {
        return function () use ($target) {
            try {
                if (\is_string($target) || \is_callable($target)) {
                    return $this->resolveStringAsObject($target);
                }

                $object = $target[0];
                $method = $target[1] ?? '__invoke';

                if (\is_string($object)) {
                    $object = $this->resolveStringAsObject($object);
                }

                $arguments = $this->resolveArguments($object, $method);

                return \call_user_func($target, ...$arguments);
            } catch (\ReflectionException|\TypeError $exception) {
                throw $this->throwAutowireException($target, $exception->getMessage(), $exception);
            }
        };
    }

    /**
     * @param class-string|callable $target
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function resolveStringAsObject(string|callable $target): object
    {
        $isObject = \is_object($target) && !($target instanceof \Closure);
        $isCallable = \is_callable($target);

        if ($isObject && $isCallable) {
            return $target;
        }

        if ($isCallable) {
            return \call_user_func($target, ...$this->resolveArguments($target));
        }

        if (!$isObject && !class_exists($target)) {
            $this->throwAutowireException($target, 'The target class does not exist or no callable.');
        }

        return new $target(...$this->resolveArguments($target));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    protected function throwAutowireException(string|array|callable $target, string $message, \Throwable $parent = null)
    {
        if (\is_array($target)) {
            $target = $target[0];
        }

        if (\is_callable($target) && !\is_string($target)) {
            $target = 'Anonymous';
        }

        if (\is_object($target)) {
            $target = \get_class($target);
        }

        throw new class(sprintf('Can not autowire "%s". %s', $target, $message), 0, $parent) extends \RuntimeException implements ContainerExceptionInterface {};
    }

    /**
     * @param class-string|object $classNameOrObject
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function resolveArguments(string|array|object $target, ?string $method = null): array
    {
        if (\is_callable($target)) {
            if (\is_array($target) || \is_object($target)) {
                try {
                    $target = \Closure::fromCallable($target);
                } catch (\Throwable $exception) {
                    $this->throwAutowireException($target, $exception->getMessage(), $exception);
                }
            }

            $ref = new \ReflectionFunction($target);

            return $this->resolveArgumentsFromReflectionParametersObject($ref->getParameters());
        }

        $reflectionClass = new \ReflectionClass($target);

        $reflectionClassMethod = null === $method ?
            $reflectionClass->getConstructor() :
            $reflectionClass->getMethod($method)
        ;

        if (null === $reflectionClassMethod) {
            return [];
        }

        return $this->resolveArgumentsFromReflectionParametersObject(
            $reflectionClassMethod->getParameters()
        );
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveArgumentsFromReflectionParametersObject(array $reflectionParameters): array
    {
        $arguments = [];

        foreach ($reflectionParameters as $parameter) {
            $parameterType = $parameter->getType();
            if (!$parameterType) {
                $arguments[] = null;

                continue;
            }

            $parameterTypeName = $parameterType->getName();

            if (\in_array(ContainerInterface::class, class_implements($parameterTypeName))) {
                $arguments[] = $this->container;

                continue;
            }

            $arguments[] = $this->container->get($parameterTypeName);
        }

        return $arguments;
    }
}
