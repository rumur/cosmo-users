<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Container\Exceptions;

class AlreadyInstantiated extends ContainerException
{
    public static function default(string $name): AlreadyInstantiated
    {
        return new static(sprintf("[%s] is already instantiated.", $name));
    }
}
