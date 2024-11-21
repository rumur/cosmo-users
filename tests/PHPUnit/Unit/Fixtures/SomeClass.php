<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Tests\Unit\Fixtures;

class SomeClass
{
    public function __construct(public string $arg = 'default-value')
    {
    }

    public function regularMethod(SomeDependency $dep): string
    {
        return $dep->doSomething();
    }

    public static function staticMethod(SomeDependency $dep): string
    {
        return $dep->doSomething();
    }
}
