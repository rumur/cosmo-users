# `Rumur\WordPress\CosmoUsers\Support\Cache` Package

## Overview

The `Rumur\WordPress\CosmoUsers\Support\Cache` package provides a caching mechanism for WordPress applications. 
It includes both in-memory and WordPress-based caching implementations, allowing for flexible and efficient data storage and retrieval.

## Usage

### In-Memory Cache

The `InMemory` class provides a simple in-memory cache implementation. This cache stores items in runtime memory and does not persist data across requests.

#### Example

```php
use Rumur\WordPress\CosmoUsers\Support\Cache\InMemory;

$cache = new InMemory();

// Set a cache item
$cache->set('key', 'value', 3600);

// Get a cache item
$value = $cache->get('key', 'default');

// Check if a cache item exists
$exists = $cache->has('key');

// Delete a cache item
$cache->delete('key');

// Clear the entire cache
$cache->clear();
```

> [!WARNING]
> **The in-memory cache has a max size of 1000 items. If the cache exceeds this limit, the oldest (by the order) items will be removed to make room for new ones.**

### WordPress Cache

The `WordPressCache` class provides a caching implementation that leverages WordPress's built-in caching functions.

#### Example

```php
use Rumur\WordPress\CosmoUsers\Support\Cache\WordPressCache;

$cache = new WordPressCache('my_cache_group');

// Set a cache item
$cache->set('key', 'value', 3600);

// Get a cache item
$value = $cache->get('key', 'default');

// Check if a cache item exists
$exists = $cache->has('key');

// Delete a cache item
$cache->delete('key');

// Clear the entire cache group
$cache->clear();
```

## API

The `InMemory` and `WordPressCache` follow the [PSR-16 Simple Cache interface](https://www.php-fig.org/psr/psr-16/) The following methods are available:

- `get(string $key, mixed $default = null): mixed`
- `getMultiple(iterable $keys, mixed $default = null): iterable`
- `set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool`
- `setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool`
- `has(string $key): bool`
- `delete(string $key): bool`
- `deleteMultiple(iterable $keys): bool`
- `clear(): bool`

## Exceptions

### `InvalidCacheKey`

Cache keys must not contain any of the following characters: `{}`, `()`, `/`, `\`, `@`, `:`.

### Back to [Plugin](./../../../README.md)
