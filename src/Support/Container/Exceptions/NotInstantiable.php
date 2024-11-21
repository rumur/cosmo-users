<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use Rumur\WordPress\CosmoUsers\Exception;

class NotInstantiable extends Exception implements ContainerExceptionInterface
{
    public static function primitive(string $name): NotInstantiable
    {
        return new static(sprintf("Unresolvable primitive parameter [%s]", esc_attr($name)));
    }

    public static function class(string $name): NotInstantiable
    {
        return new static(sprintf("Unresolvable class [%s]", esc_attr($name)));
    }

    public static function default(string $name): NotInstantiable
    {
        return new static(sprintf("[%s] could not be instantiated.", esc_attr($name)));
    }
}
