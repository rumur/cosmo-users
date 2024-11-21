<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Tests\Unit\Fixtures;

class SomeDependency
{
    public function doSomething(): string
    {
        return 'expected result';
    }
}
