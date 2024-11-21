<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use Rumur\WordPress\CosmoUsers\Container as ContainerContract;
use Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\NotInstantiable;

/**
 * Primitive implementation of the PSR-11 Container, to facilitate the auto wiring for this plugin.
 *
 * @template TInstance of object
 * @template TConcrete of class-string<TInstance>|(callable(Container $container):TInstance)
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Support\Container
 */
class Container implements ContainerContract
{
    /**
     * Holds the list of arguments, that will be passed to an instance during
     * resolve step.
     *
     * @var array<array<int|non-empty-string,mixed>>
     */
    protected array $args = [];

    /**
     * Holds resolved instances.
     *
     * @var array<class-string<TInstance>,TInstance>
     */
    protected array $instances = [];

    /**
     * Bind map holds the list of classes that need to be instantiated.
     * The key is an interface and a value is the realization of it.
     *
     * @var array<class-string<TInstance>,TConcrete>
     */
    protected array $bound = [];

    /**
     * Instances that should be treated as singletons,
     * and should be instantiated only once.
     *
     * @var array<class-string<TInstance>,boolean>
     */
    protected array $singletons = [];

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param class-string<TInstance> $id Identifier of the entry to look for.
     *
     * @return TInstance Entry.
     *
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     */
    public function get(string $id) // phpcs:ignore Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType -- We use a generic type for autocomplete.
    {
        if (!$this->has($id)) {
            throw new Exceptions\NotFound(esc_attr($id));
        }

        return $this->resolve($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param class-string<TInstance> $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->bound[$id]);
    }

    /**
     * Binds the abstracts with their implementations as singleton.
     *
     * @param class-string<TInstance> $abstract The abstract key.
     * @param \Closure|TConcrete|null $concrete The class of the implementation.
     *
     * @return static
     */
    public function singleton(string $abstract, $concrete = null): static
    {
        return $this->bind($abstract, $concrete, true);
    }

    /**
     * Binds the abstracts with their implementations.
     *
     * @param class-string<TInstance> $abstract The abstract key.
     * @param TConcrete|class-string<TInstance>|null $concrete The class of the implementation.
     * @param bool $singleton Optional. Marks class as singleton.
     *
     * @return static
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): static
    {
        if (null === $concrete) {
            $concrete = $abstract;
        }

        if ($singleton) {
            $this->singletons[$abstract] = true;
        }

        $this->bound[$abstract] = $concrete;

        return $this;
    }

    /**
     * Calls a Class method or a Closure by instantiating all its dependencies.
     *
     * @param callable $callback Callable to be called and provided with its dependencies.
     * @param array<non-empty-string, mixed> $args Arguments to be overwritten instead of autowired.
     *
     * @return mixed The callable result.
     *
     * @throws NotInstantiable In case if it's impossible to execute the given callable callback.
     */
    public function call(callable $callback, array $args = []): mixed
    {
        try {
            $reflection = new ReflectionFunction($callback(...));
        } catch (ReflectionException) {
            throw new NotInstantiable('The given callback is not instantiable.');
        }

        $this->overrideArgsWith($args);

        $dependencies = $reflection->getParameters();

        $parameters = $this->resolveDependencies($dependencies);

        $this->withdrawLatestArgs();

        return $callback(...$parameters);
    }

    /**
     * Resolves the abstract.
     * It checks if the abstract is registered as a singleton,
     * if so it returns already instantiated or creates a new one.
     *
     * @param class-string<TInstance> $abstract Desired class for resolving.
     * @param array<non-empty-string, mixed> $args Arguments to override or build the instance with.
     *
     * @return TInstance
     *
     * @throws NotInstantiable In case if it's impossible to instantiate the class.
     */
    public function resolve(string $abstract, array $args = []) // phpcs:ignore Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType -- We use a generic type for autocomplete.
    {
        if (isset($this->singletons[$abstract], $this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $this->overrideArgsWith($args);

        $resolved = $this->build($abstract);

        $this->withdrawLatestArgs();

        // In case it's registered we can store w/i the container, otherwise we ignore to save memory.
        // It may happen when a class is not bound but being resolved via container.
        if ($this->has($abstract)) {
            $this->instances[$abstract] = $resolved;
        }

        return $resolved;
    }

    /**
     * The build process for an abstract instance.
     *
     * @param class-string<TInstance> $abstract The abstract instance to be built.
     *
     * @return TInstance
     *
     * @throws Exceptions\NotInstantiable When abstract is not instantiable.
     */
    protected function build(string $abstract) // phpcs:ignore Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType -- We use a generic type for autocomplete.
    {
        $concrete = $this->bound[$abstract] ?? $abstract;

        try {
            // If we encounter a Closure, it means developer has specified the build process.
            // In this case we need to pass desired args to this Closure and return what it returns.
            if ($concrete instanceof \Closure) {
                $closure = new ReflectionFunction($concrete);

                $dependencies = $closure->getParameters();

                $parameters = $this->resolveDependencies($dependencies);

                // We need to call the Closure with the container as the first argument.
                return $concrete($this, ...$parameters);
            }
        } catch (ReflectionException $exc) {
            throw new Exceptions\NotInstantiable(esc_attr($exc->getMessage()));
        }

        try {
            $reflector = new ReflectionClass($concrete);

            if (!$reflector->isInstantiable()) {
                throw Exceptions\NotInstantiable::default($abstract);
            }

            $constructor = $reflector->getConstructor();

            // It means that the class doesn't have a constructor
            // So we can just return the instance right away.
            if (null === $constructor) {
                return $reflector->newInstanceWithoutConstructor();
            }

            $dependencies = $constructor->getParameters();

            $instances = $this->resolveDependencies($dependencies);

            return $reflector->newInstanceArgs($instances);
        } catch (ReflectionException) {
        }

        throw Exceptions\NotInstantiable::default(esc_attr($abstract));
    }

    /**
     * Sets arguments for a current build.
     *
     * @param array $args Build arguments.
     */
    protected function overrideArgsWith(array $args): void
    {
        $this->args[] = $args;
    }

    /**
     * Discards passed arguments for a current build after it gets built.
     *
     * @return void
     */
    protected function withdrawLatestArgs(): void
    {
        array_pop($this->args);
    }

    /**
     * @param ReflectionParameter[] $dependencies Collection of dependencies.
     *
     * @return array
     * @throws Exceptions\NotInstantiable In case if it's impossible to instantiate a dependency.
     */
    protected function resolveDependencies(array $dependencies): array // phpcs:ignore Inpsyde.CodeQuality.NestingLevel.High -- We do recursive calls.
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if ($this->hasOverrideArgs($dependency)) {
                if ($dependency->isVariadic()) {
                    $results = array_merge($results, (array) $this->overrideArgs($dependency));

                    continue;
                }

                $results[] = $this->overrideArgs($dependency);

                continue;
            }

            if (null === $this->dependencyToParamName($dependency)) {
                $results[] = $this->resolvePrimitive($dependency);

                continue;
            }

            $results[] = $this->resolveClass($dependency);
        }

        return $results;
    }

    /**
     * Checks whether a dependency param has an alternative passed by the dev.
     *
     * @param ReflectionParameter $dependency The dependency parameter.
     *
     * @return bool True if there is an arg to override.
     */
    protected function hasOverrideArgs(ReflectionParameter $dependency): bool
    {
        return array_key_exists($dependency->name, $this->overrideArgsLatest());
    }

    /**
     * Gets an alternative param passed by the dev.
     *
     * @param ReflectionParameter $dependency The dependency parameter.
     *
     * @return mixed
     */
    protected function overrideArgs(ReflectionParameter $dependency): mixed
    {
        return $this->overrideArgsLatest()[$dependency->name];
    }

    /**
     * Safely provides params.
     *
     * @return array
     */
    protected function overrideArgsLatest(): array
    {
        return count($this->args) ? end($this->args) : [];
    }

    /**
     * Resolves primitive dependencies like strings, integers, etc.
     *
     * @param ReflectionParameter $dependency Primitive dependency.
     *
     * @return mixed The resolved primitive null or resolved value.
     *
     * @throws Exceptions\NotInstantiable In case if it's impossible to resolve the primitive.
     */
    protected function resolvePrimitive(ReflectionParameter $dependency): mixed
    {
        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }

        if ($dependency->allowsNull()) {
            return null;
        }

        throw Exceptions\NotInstantiable::primitive(esc_attr($dependency->name));
    }

    /**
     * Resolves the class instance along with its dependencies.
     *
     * @param ReflectionParameter $dependency The dependency class.
     *
     * @return null|TInstance The resolved instance.
     *
     * @throws Exceptions\NotInstantiable In case if it's impossible to instantiate the class.
     */
    protected function resolveClass(ReflectionParameter $dependency) // phpcs:ignore Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType -- We use a generic type for autocomplete.
    {
        try {
            return $this->resolve($this->dependencyToParamName($dependency));
        } catch (NotInstantiable) {
            if ($dependency->isDefaultValueAvailable()) {
                return $dependency->getDefaultValue();
            }

            if ($dependency->allowsNull()) {
                return null;
            }
        }

        throw Exceptions\NotInstantiable::class(esc_attr($dependency->name));
    }

    /**
     * Converts the dependency to a parameter name.
     *
     * @param ReflectionParameter $dependency The dependency parameter.
     *
     * @return string|null
     */
    protected function dependencyToParamName(ReflectionParameter $dependency): ?string
    {
        $type = $dependency->getType();

        // If the parameter doesn't have a type, there is nothing we can do.
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();
        $class = $dependency->getDeclaringClass();

        if (null === $class) {
            return $name;
        }

        return match ($name) {
            'self' => $class->getName(),
            'parent' => $class->getParentClass()?->getName(),
            default => $name,
        };
    }
}
