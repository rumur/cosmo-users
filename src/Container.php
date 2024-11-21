<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\NotInstantiable;

/**
 * The interface for the Container implementation.
 * Supports the PSR-11 Container Interface.
 *
 * @template TInstance of object
 * @template TConcrete of class-string<TInstance>|(callable(Container $container):TInstance)
 *
 * @link https://www.php-fig.org/psr/psr-11/
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers
 */
interface Container extends ContainerInterface
{
    /**
     * Binds the abstracts with their implementations.
     *
     * @param class-string<TInstance> $abstract The abstract key.
     * @param TConcrete|null $concrete The implementation or a factory to create an instance with.
     * @param bool $singleton Whether current abstract should be treated as a singleton.
     *
     * @return static
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): static;

    /**
     * Calls any callable callback and attempts to inject all its dependencies automatically.
     *
     * @param callable $callback Any callable to be called and provided with its dependencies automatically.
     * @param array<non-empty-string, mixed> $args The arguments that need to be overwritten instead of autowiring.
     *
     * @return mixed The result of the callable callback.
     *
     * @throws ContainerExceptionInterface In case if it's impossible to execute the given callable callback.
     */
    public function call(callable $callback, array $args = []): mixed;

    /**
     * Resolves the given abstract.
     *
     * @param class-string<TInstance> $abstract Desired abstract for resolving.
     * @param array<non-empty-string, mixed> $args Arguments to override or build the instance with.
     *
     * @return TInstance
     *
     * @throws NotInstantiable In case if it's impossible to instantiate the class.
     */
    public function resolve(string $abstract, array $args = []);

    /**
     * An alias for the `bind` method with predefined arguments.
     *
     * @param class-string<TInstance> $abstract The abstract key.
     * @param TConcrete|null $concrete The implementation or a factory to create an instance with.
     *
     * @return static
     */
    public function singleton(string $abstract, $concrete = null): static;
}
