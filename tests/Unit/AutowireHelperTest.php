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

namespace Micro\Component\DependencyInjection\Test\Unit\Autowire;

use Micro\Component\DependencyInjection\Autowire\AutowireHelper;
use Micro\Component\DependencyInjection\Autowire\ContainerAutowire;
use Micro\Component\DependencyInjection\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

class AutowireHelperTest extends TestCase
{
    private ContainerAutowire $container;

    private AutowireHelper $autowireHelper;

    protected function setUp(): void
    {
        $this->container = new ContainerAutowire(
            new Container()
        );

        $this->container->register(AutowireService::class,
            fn (AutowireServiceArgument $serviceArgument) => new AutowireService($serviceArgument));

        $this->container->register(AutowireServiceArgument::class,
            fn (Container $container): AutowireServiceArgument => new AutowireServiceArgument());

        $this->autowireHelper = new AutowireHelper($this->container);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAutowire(mixed $autowireArgs, string|null $instanceOf = null)
    {
        if (!$instanceOf) {
            $this->expectException(ContainerExceptionInterface::class);
        }

        $callback = $this->autowireHelper->autowire($autowireArgs);
        $autowired = $callback();

        $this->assertInstanceOf($instanceOf, $autowired);

        if (AutowireServiceArgument::class === $instanceOf) {
            $this->assertEquals(AutowireServiceArgument::class, $autowired->getName());
        }

        if (AutowireService::class === $instanceOf) {
            $this->assertEquals(AutowireServiceArgument::class, $autowired->getService()->getName());
        }

        if (\is_callable($autowired)) {
            $this->assertEquals('HELLO!', $autowired());
        }
    }

    public function dataProvider()
    {
        return [
            [
                AutowireService::class,
                AutowireService::class,
            ],

            [
                [new AutowireService(new AutowireServiceArgument()), 'setService'],
                AutowireService::class,
            ],
            [
                new class() extends AutowireServiceArgument {
                    public function __invoke()
                    {
                        return 'HELLO!';
                    }
                },
                AutowireServiceArgument::class,
            ],
            [
                function (AutowireService $service): string {
                    $this->assertEquals(AutowireServiceArgument::class, $service->getService()->getName());

                    return 'HELLO!';
                },
            ],
            // Should be throw Exception
            [
                fn ($service) => \get_class($service),
            ],
            [
                [new AutowireService(new AutowireServiceArgument())],
            ],

            [
                [
                    AutowireService::class,
                    new AutowireServiceArgument(),
                ],
            ],
            [
                [
                    new AutowireServiceArgument(),
                    new AutowireServiceArgument(),
                ],
            ],
            [
                [
                    new class() extends AutowireServiceArgument {
                        public function __invoke()
                        {
                            return 'Must not returns';
                        }
                    },
                ],
            ],
            [
                [
                    fn (AutowireService $service) => \get_class($service),
                ],
            ],
            [
                [12345],
            ],
            [
                ['ClassNoExists'],
            ],
        ];
    }
}
