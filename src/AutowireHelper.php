<?php

namespace Micro\Component\DependencyInjection\Autowire;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

class AutowireHelper implements AutowireHelperInterface
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
    public function autowire(string|array|callable $target): callable
    {
        return function () use ($target) {
            try {
                if (is_string($target)) {
                    return new $target(...$this->resolveArguments($target));
                }

                if (is_array($target)) {
                    $object = $target[0];
                    $method = $target[1];

                    $arguments = $this->resolveArguments(get_class($object), $method);

                    return call_user_func($target, ...$arguments);
                }

                if (is_callable($target)) {
                    return $this->autowireCallback($target);
                }
            } catch (ReflectionException $exception) {
                throw $this->throwAutowireException($target, $exception->getMessage(), $exception);
            }

            $this->throwAutowireException($target, '');
        };
    }

    /**
     * @param string|array|callable $target
     * @param string $message
     * @param \Throwable|null $parent
     *
     * @throws ContainerExceptionInterface
     */
    protected function throwAutowireException(string|array|callable $target, string $message, \Throwable $parent = null)
    {
        if(is_array($target)) {
            $target = $target[0];
        }

        if(is_callable($target)) {
            $target = 'Anonymous';
        }

        throw new class((sprintf('Can not autowire "%s". %s', $target, $message)), 0, $parent) extends \RuntimeException implements ContainerExceptionInterface {};
    }

    /**
     * @param callable $target
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function autowireCallback(callable $target): mixed
    {
        $ref = new \ReflectionFunction($target);

        return $target(...$this->resolveArgumentsFromReflectionParametersObject(
            $ref->getParameters()
        ));
    }

    /**
     * @param string $className
     * @param string|null $method
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function resolveArguments(string $className, ?string $method = null): array
    {
        if(!class_exists($className)) {
            throw new class((sprintf('Class %s is not exists', $className))) extends \Exception implements ContainerExceptionInterface {};
        }

        $reflectionClass = new \ReflectionClass($className);

        $reflectionClassMethod = $method === null ?
            $reflectionClass->getConstructor() :
            $reflectionClass->getMethod($method)
        ;

        if($reflectionClassMethod === null) {
            return [];
        }

        return $this->resolveArgumentsFromReflectionParametersObject(
            $reflectionClassMethod->getParameters()
        );
    }

    /**
     * @param array $reflectionParameters
     * @return array
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveArgumentsFromReflectionParametersObject(array $reflectionParameters): array
    {
        $arguments = [];

        foreach($reflectionParameters as $parameter) {
            $parameterType = $parameter->getType();
            if(!$parameterType) {
                $arguments[] = null;

                continue;
            }

            $parameterTypeName = $parameterType->getName();

            if(in_array(ContainerInterface::class, class_implements($parameterTypeName))) {
                $arguments[] = $this->container;

                continue;
            }

            $arguments[] = $this->container->get($parameterTypeName);
        }

        return $arguments;
    }

}
