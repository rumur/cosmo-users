<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Cache;

use Rumur\WordPress\CosmoUsers\Cache;

/**
 * In Memory Cache Instance.
 * NOTE: It doesn't store anything into a Redis/Memcache it just stores items in runtime memory.
 *
 * @template TValue
 *
 * @phpstan-type CacheItem array{value: TValue, ttl: int|null}
 * @internal
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Support\Cache
 */
class InMemory implements Cache
{
    /**
     * The default capacity of the cache.
     *
     * @var int
     */
    public const CAPACITY = 1000;

    /**
     * The cache storage.
     *
     * @var array<int|string, CacheItem>
     */
    protected array $storage = [];

    /**
     * Checks whether a given key is valid.
     *
     * @param string $key The key to validate.
     *
     * @return void
     * @throws InvalidCacheKey thrown if the $key string is not a legal value.
     *
     */
    private static function validateKey(string $key): void
    {
        if (\preg_match('#[{}()/\\\@:]#', $key)) {
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
        static::validateKey($key);

        if (!\array_key_exists($key, $this->storage)) {
            return $default;
        }

        // Check if the cache item has expired, and delete it if it is.
        if ($this->isExpired($this->storage[$key])) {
            $this->delete($key);

            return $default;
        }

        return $this->storage[$key]['value'] ?? $default;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable<non-empty-string> $keys A list of keys to obtain in a single operation.
     * @param TValue $default Default value to return for keys that do not exist.
     *
     * @return iterable<string, TValue> A list of key => value pairs.
     *                                 Missing and stale keys will have $default as value.
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
        static::validateKey($key);

        // Store the value in the cache.
        $this->storage[$key] = $this->createCacheItem($value, $ttl);

        $this->trimStorage();

        return true;
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
            static::validateKey($key);
            $this->storage[$key] = $this->createCacheItem($value, $ttl);
        }

        $this->trimStorage();

        return true;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * @param string $key The cache key.
     *
     * @return bool
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
        static::validateKey($key);

        if (!\array_key_exists($key, $this->storage)) {
            return false;
        }

        unset($this->storage[$key]);

        return true;
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
        $this->storage = [];

        return true;
    }

    /**
     * Checks if the cache item has expired.
     *
     * @param CacheItem $cacheItem The cache item.
     *
     * @return bool True if the cache item has expired, false otherwise.
     */
    private function isExpired(array $cacheItem): bool
    {
        return \is_int($cacheItem['ttl']) && $cacheItem['ttl'] < \time();
    }

    /**
     * Resolves the TTL value.
     *
     * @param \DateInterval|int|null $ttl The TTL value.
     *
     * @return int|null The resolved TTL value.
     */
    private function resolveTtl(\DateInterval|int|null $ttl): ?int
    {
        if ($ttl instanceof \DateInterval) {
            return (new \DateTime())->add($ttl)->getTimestamp();
        }

        return \is_int($ttl) ? \time() + $ttl : null;
    }

    /**
     * Trims the cache storage by removing the oldest items.
     *
     * @return void
     */
    private function trimStorage(): void
    {
        // Make sure the cache doesn't exceed the capacity.
        if (\count($this->storage) > self::CAPACITY) {
            $this->storage = \array_slice($this->storage, -self::CAPACITY);
        }
    }

    /**
     * Creates a single cache item.
     *
     * @param TValue $value The value of the cache item.
     * @param \DateInterval|int|null $ttl The TTL value of the cache item.
     *
     * @return CacheItem The cache item.
     */
    private function createCacheItem(mixed $value, \DateInterval|int|null $ttl): array
    {
        return [
            'value' => $value,
            'ttl' => $this->resolveTtl($ttl),
        ];
    }
}
