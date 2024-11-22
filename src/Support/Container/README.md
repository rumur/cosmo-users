# Container Class

The `Container` class is a primitive implementation of the PSR-11 Container, designed to facilitate auto-wiring for the Cosmo Users plugin.

## Table of Contents

- [Overview](#overview)
- [Methods](#methods)
  - [bind](#bind)
  - [singleton](#singleton)
  - [call](#call)
  - [resolve](#resolve)
  - [get](#get)
  - [has](#has)
- [Exceptions](#exceptions)
    - [Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\ContainerException](#rumurwordpresscosmouserssupportcontainerexceptionscontainerexception)
    - [Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\NotInstantiable](#rumurwordpresscosmouserssupportcontainerexceptionsnotinstantiable)
    - [Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\NotFoundException](#rumurwordpresscosmouserssupportcontainerexceptionsnotfoundexception)
    - [Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\AlreadyInstantiated](#rumurwordpresscosmouserssupportcontainerexceptionsalreadyinstantiated)
## Overview
 
It supports binding classes and interfaces, resolving instances, and calling methods with automatic dependency injection.

> [!TIP]
> The container implements the PSR-11 ContainerInterface, which means it can be used as a dependency injection container in any PSR-11 compatible system.

## Methods

### bind

Binds an abstract class or interface to a concrete implementation.

```php
public function bind(string $abstract, $concrete = null, bool $singleton = false): static
```

- **Parameters:**
  - `string $abstract`: The abstract class or interface.
  - `$concrete`: The concrete implementation or a factory to create an instance.
  - `bool $singleton`: Whether the binding should be treated as a singleton.

- **Returns:** `static`

**Example:**
    
```php
// To associate an interface with its implementation, so when the interface is requested, the implementation is returned. 
$container->bind(EngineInterface::class, Engine::class);

// Same as above, but with a factory function to create the instance with.
$container->bind(EngineInterface::class, static function ($container): EngineInterface {
    return new Engine($container->get(Config::class));
});

// To resolve the instance of the EngineInterface:
$engine = $container->resolve(EngineInterface::class);

// If you request a second instance, it will be a different instance:
$engine2 = $container->resolve(EngineInterface::class);

$engine === $engine2 // false
```

### singleton

Binds an abstract class or interface to a concrete implementation as a singleton.

```php
public function singleton(string $abstract, $concrete = null): static
```

- **Parameters:**
  - `string $abstract`: The abstract class or interface.
  - `$concrete`: The concrete implementation or a factory to create an instance.

- **Returns:** `static`

**Example:**
    
```php
// To associate an interface with its implementation, so when the interface is requested, the implementation is returned.
$container->singleton(EngineInterface::class, Engine::class);

// Same as above, but with a factory function to create the instance with.
$container->singleton(EngineInterface::class, static function ($container): EngineInterface {
    return new Engine($container->get(Config::class));
});

// To resolve the instance of the EngineInterface, you can use the following code:
$engine = $container->resolve(EngineInterface::class);

// If you request a second instance, it will give you an already created instance.
$engine2 = $container->resolve(EngineInterface::class);

$engine === $engine2 // true
```

> [!NOTE]
> The singleton method is a shorthand for the bind method with the `singleton` flag set to `true`.

> [!WARNING]
> If you try to bind a singleton which was already instantiated, it will throw an exception `Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\AlreadyInstantiated`.

### call

Calls a callable and injects its dependencies automatically.

```php
public function call(callable $callback, array $args = []): mixed
```

- **Parameters:**
  - `callable $callback`: The callable to be called.
  - `array $args`: Arguments to override the auto-wired dependencies.

- **Returns:** `mixed`: The result of the callable.

### resolve

Resolves an instance of the given abstract class or interface.

```php
public function resolve(string $abstract, array $args = [])
```

- **Parameters:**
  - `string $abstract`: The abstract class or interface.
  - `array $args`: Arguments to override the auto-wired dependencies.

- **Returns:** The resolved instance.

### get

Finds an entry of the container by its identifier and returns it.

```php
public function get(string $id)
```

- **Parameters:**
  - `string $id`: Identifier of the entry to look for.

- **Returns:** The entry associated with the given identifier.

- **Throws:**
  - `ContainerExceptionInterface`: Error while retrieving the entry.
  - `NotFoundExceptionInterface`: No entry was found for this identifier.

### has

Returns true if the container can return an entry for the given identifier.

> [!WARNING]
> This method does guarantee that the entry will not throw `NotFoundExceptionInterface` exception, but others can be thrown.

```php
public function has(string $id): bool
```

- **Parameters:**
  - `string $id`: Identifier of the entry to look for.

- **Returns:** `bool`: True if the entry exists, false otherwise.

## Exceptions

### Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\ContainerException

Base instance representing a generic exception in a container. All exceptions thrown by the container extends this class.

### Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\NotInstantiable

Thrown when a class or dependency cannot be instantiated.

### Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\NotFoundException

Thrown when an entry is not found in the container.

### Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\AlreadyInstantiated

Thrown when we're trying to bind a singleton instance which was already instantiated.
