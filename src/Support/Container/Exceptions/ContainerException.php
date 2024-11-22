<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use Rumur\WordPress\CosmoUsers\Exception;

/**
 * Class ContainerException helps to scope the exceptions that are related to the Container itself.
 * All exceptions that are thrown by the Container should be an instance of this class.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Support\Container\Exceptions
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
