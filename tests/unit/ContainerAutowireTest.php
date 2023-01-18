<?php

declare(strict_types=1);

/*
 *  This file is part of the Micro framework package.
 *
 *  (c) Stanislau Komar <kost@micro-php.net>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Micro\Component\DependencyInjection\Tests;

use Micro\Component\DependencyInjection\Autowire\ContainerAutowire;
use Micro\Component\DependencyInjection\Container;
use PHPUnit\Framework\TestCase;

class ContainerAutowireTest extends TestCase
{
    public function testThatServiceIsAutowired(): void
    {
        $container = new ContainerAutowire(new Container());

        $container->register(ModifierInterface::class, function () {
            return new NamedServiceModifier('Hello:', ':World');
        });

        $container->register('test', function (ModifierInterface $modifier) {
            return new NamedService('success', [$modifier]);
        });

        /** @var NamedInterface $service */
        $service = $container->get('test');
        $this->assertIsObject($service);
        $this->assertInstanceOf(NamedInterface::class, $service);
        $this->assertInstanceOf(NamedService::class, $service);
        $this->assertEquals('Hello:success:World', $service->getName());
    }

    public function testThatDecoratorIsAutowired(): void
    {
        $container = new ContainerAutowire(new Container());

        $container->register('test', function (ModifierInterface $modifier) {
            return new NamedService('success', [$modifier]);
        });

        $container->register(ModifierInterface::class, function () {
            return new NamedServiceModifier('Hello:', ':World');
        });

        $container->register(GreetingsInterface::class, function () {
            return new NiceGreetingsService();
        });

        $container->decorate(ModifierInterface::class, function (ModifierInterface $decorated, GreetingsInterface $greetings) {
            return new NiceNamedServiceModifierDecorator($decorated, $greetings);
        });

        /** @var NamedInterface $service */
        $service = $container->get('test');
        $this->assertIsObject($service);
        $this->assertInstanceOf(NamedInterface::class, $service);
        $this->assertInstanceOf(NamedService::class, $service);
        $this->assertEquals('Hello:success:World. Nice to meet you!', $service->getName());
    }
}

interface NamedInterface
{
    public function getName(): string;
}

class NamedService implements NamedInterface
{
    /**
     * @param ModifierInterface[] $modifiers
     */
    public function __construct(private string $name, private readonly array $modifiers)
    {
    }

    public function getName(): string
    {
        foreach ($this->modifiers as $modifier) {
            $this->name = $modifier->modify($this->name);
        }

        return $this->name;
    }
}

interface ModifierInterface
{
    public function modify(string $name): string;
}

class NamedServiceModifier implements ModifierInterface
{
    public function __construct(
        private readonly string $beforeModifier,
        private readonly string $afterModifier
    ) {
    }

    public function modify(string $name): string
    {
        return sprintf('%s%s%s', $this->beforeModifier, $name, $this->afterModifier);
    }
}

interface GreetingsInterface
{
    public function getGreetings(): string;
}

class NiceGreetingsService implements GreetingsInterface
{
    public function getGreetings(): string
    {
        return 'Nice to meet you!';
    }
}

readonly class NiceNamedServiceModifierDecorator implements ModifierInterface
{
    public function __construct(private ModifierInterface $decorated, private GreetingsInterface $greetings)
    {
    }

    public function modify(string $name): string
    {
        return sprintf('%s. %s', $this->decorated->modify($name), $this->greetings->getGreetings());
    }
}
