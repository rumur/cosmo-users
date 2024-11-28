<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Cache;

use Rumur\WordPress\CosmoUsers\Cache;

/**
 * WordPress Object Cache Instance.
 *
 * @template TValue
 * @internal
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Support\Cache
 */
class WordPressCache implements Cache
{
    /**
     * Cache constructor.
     *
     * @param non-empty-string $group The cache group.
     */
    public function __construct(readonly string $group)
    {
    }

    /**
     * Checks whether a given key is valid.
     *
     * @param string $key The key to validate.
     *
     * @throws InvalidCacheKey thrown if the $key string is not a legal value.
     */
    private function validateKey(string $key): void
    {
        if (preg_match('#[{}()/\\\@:]#', $key)) {
            throw new InvalidCacheKey(esc_attr($key));
        }
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return TValue The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws InvalidCacheKey thrown if the $key string is not a legal value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $value = \wp_cache_get($key, $this->group);

        if (false === $value) {
            return $default;
        }

        return $value;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable<non-empty-string> $keys A list of keys to obtain in a single operation.
     * @param TValue $default Default value to return for keys that do not exist.
     *
     * @return iterable<string, TValue> A list of key => value pairs.
     *                                 $default for missing or stale keys.
     *
     * @throws InvalidCacheKey thrown if any of the $keys are not a legal value.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable // phpcs:ignore Inpsyde.CodeQuality.NoAccessors.NoGetter -- PSR Standard
    {
        foreach ($keys as $key) {
            \assert(\is_string($key), 'Cache key must be a string');
            yield $key => $this->get($key, $default);
        }
    }

    /**
     * Persists data in the cache, by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param TValue $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item.
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidCacheKey thrown if the $key string is not a legal value.
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        return \wp_cache_set( // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
            $key,
            $value,
            $this->group,
            $this->resolveTtl($ttl)
        );
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
        $atLeastOneFailed = false;

        foreach ($values as $key => $value) {
            \assert(\is_string($key), 'Cache key must be a string');
            if (!$this->set($key, $value, $ttl)) {
                $atLeastOneFailed = true;
            }
        }

        return false === $atLeastOneFailed;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * @param string $key The cache key.
     *
     *
     * @throws InvalidCacheKey thrown if the $key string is not a legal value.
     */
    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws InvalidCacheKey thrown if the $key string is not a legal value.
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);
        return wp_cache_delete($key, $this->group);
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
        $atLeastOneFailed = false;

        foreach ($keys as $key) {
            \assert(\is_string($key), 'Cache key must be a string');
            if (!$this->delete($key)) {
                $atLeastOneFailed = true;
            }
        }

        return false === $atLeastOneFailed;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        return wp_cache_flush_group($this->group);
    }

    /**
     * Resolves the TTL value.
     *
     * @param \DateInterval|int|null $ttl The TTL value.
     *
     * @return int The resolved TTL value.
     */
    private function resolveTtl(\DateInterval|int|null $ttl): int
    {
        if ($ttl instanceof \DateInterval) {
            return (new \DateTime())->add($ttl)->getTimestamp();
        }

        return is_int($ttl) ? time() + $ttl : 0;
    }
}
