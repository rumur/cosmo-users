<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Container\Exceptions;

class NotInstantiable extends ContainerException
{
    public static function primitive(string $name): NotInstantiable
    {
        return new static(sprintf("Unresolvable primitive parameter [%s]", $name));
    }

    public static function class(string $name): NotInstantiable
    {
        return new static(sprintf("Unresolvable class [%s]", $name));
    }

    public static function default(string $name): NotInstantiable
    {
        return new static(sprintf("[%s] could not be instantiated.", $name));
    }
}
