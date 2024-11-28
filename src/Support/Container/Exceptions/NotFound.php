<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class NotFound extends ContainerException implements NotFoundExceptionInterface
{
    public static function default(string $name): NotFound
    {
        return new static(sprintf("[%s] not found within the container", $name));
    }
}
