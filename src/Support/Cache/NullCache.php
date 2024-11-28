<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Cache;

use Rumur\WordPress\CosmoUsers\Cache;

/**
 * Nullable Cache Instance of cache storage.
 *
 * This class can be used when cache interface is being part of the class,
 * but we don't want to cache anything,
 * or we don't want to have null checks in the code.
 * So we can use this class to avoid caching.
 * This class can be used in unit tests as well.
 * Also known as Null Object Pattern in OOP.
 *
 * @link https://en.wikipedia.org/wiki/Null_object_pattern
 *
 * @since 0.1.0
 * @internal
 *
 * @package Rumur\WordPress\CosmoUsers\Support\Cache
 */
class NullCache implements Cache
{
    /**
     * Makes sure that the key is valid.
     *
     * @param string $key The to validate key.
     *
     * @throws InvalidCacheKey thrown if the $key string is not a legal value.
     *
     * @return void
     */
    private static function validateKey(string $key): void
    {
        if (\preg_match('#[{}()/\\\@:]#', $key)) {
            throw new InvalidCacheKey(\esc_attr($key));
        }
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws InvalidCacheKey thrown if the $key string is not a legal value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        static::validateKey($key);

        return $default;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable<string> $keys A list of keys that can be obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return iterable<string, mixed> A list of key => value pairs.
     *                                 $default for missing or stale keys.
     *
     * @throws InvalidCacheKey thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable // phpcs:ignore Inpsyde.CodeQuality.NoAccessors.NoGetter -- PSR Standard
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /**
     * Persists data in the cache, by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item.
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidCacheKey
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        static::validateKey($key);

        return false;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item.
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidCacheKey thrown if any of the $keys are not a legal value.
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool // phpcs:ignore Inpsyde.CodeQuality.NoAccessors.NoSetter -- PSR Standard
    {
        foreach ($values as $key => $value) {
            \assert(\is_string($key), 'Cache key must be a string');
            $this->set($key, $value, $ttl);
        }

        return false;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws InvalidCacheKey thrown if the $key string is not a legal value.
     */
    public function has(string $key): bool
    {
        return false;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws InvalidCacheKey
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete(string $key): bool
    {
        return false;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable<string> $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws InvalidCacheKey thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key => $ignore) {
            static::validateKey($key);
        }

        return false;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        return false;
    }
}
