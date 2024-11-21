<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Tests\Unit;

use Rumur\WordPress\CosmoUsers\Support\Container\Container;
use Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\NotFound;
use Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\NotInstantiable;

class TestContainer extends TestCase
{
    public function testCallExecutesCallbackWithDependencies(): void
    {
        $container = new Container();
        $container->bind(Fixtures\SomeDependency::class);

        $result = $container->call(fn(Fixtures\SomeDependency $dep) => $dep->doSomething());

        $this->assertEquals('expected result', $result);
    }

    public function testCallOverridesArguments(): void
    {
        $result = (new Container())->call(
            fn($arg1, $arg2) => $arg1 + $arg2,
            ['arg1' => 1, 'arg2' => 2]
        );

        $this->assertEquals(3, $result);
    }

    public function testCallHandlesVariadicArguments(): void
    {
        $result = (new Container())->call(
            fn(...$args) => array_sum($args),
            ['args' => [1, 2, 3]]
        );

        $this->assertEquals(6, $result);
    }

    public function testCallExecutesStaticMethod(): void
    {
        $container = new Container();
        $container->bind(Fixtures\SomeDependency::class);
        $result = $container->call([Fixtures\SomeClass::class, 'staticMethod']);
        $this->assertEquals('expected result', $result);
    }

    public function testCallExecutesInvokeMethod(): void
    {
        $container = new Container();
        $container->bind(Fixtures\SomeDependency::class);
        $invokable = new class {
            public function __invoke(Fixtures\SomeDependency $dep)
            {
                return $dep->doSomething();
            }
        };
        $result = $container->call($invokable);

        $this->assertEquals('expected result', $result);
    }

    public function testCallExecutesRegularMethod(): void
    {
        $container = new Container();
        $container->bind(Fixtures\SomeDependency::class);
        $object = new Fixtures\SomeClass();
        $result = $container->call([$object, 'regularMethod']);

        $this->assertEquals('expected result', $result);
    }

    public function testResolveReturnsSingletonInstance()
    {
        $container = new Container();
        $container->singleton(Fixtures\SomeClass::class);

        $instance1 = $container->resolve(Fixtures\SomeClass::class);
        $instance2 = $container->resolve(Fixtures\SomeClass::class);

        $this->assertSame($instance1, $instance2);
    }

    public function testResolveCreatesNewInstanceForNonSingleton()
    {
        $container = new Container();
        $container->bind(Fixtures\SomeClass::class);

        $instance1 = $container->resolve(Fixtures\SomeClass::class);
        $instance2 = $container->resolve(Fixtures\SomeClass::class);

        $this->assertNotSame($instance1, $instance2);
    }

    public function testResolveOverridesArguments()
    {
        $container = new Container();
        $container->bind(Fixtures\SomeClass::class);
        $instance = $container->resolve(Fixtures\SomeClass::class, ['arg' => 'value1']);
        $this->assertEquals('value1', $instance->arg);
    }

    public function testResolveThrowsExceptionForUnresolvableClass(): void
    {
        $this->expectException(NotInstantiable::class);
        (new Container)->resolve(\UnresolvableClass::class);
    }

    public function testGetThrowsExceptionForNotFoundAbstract(): void
    {
        $this->expectException(NotFound::class);
        (new Container)->get(\UnresolvableClass::class);
    }
}
