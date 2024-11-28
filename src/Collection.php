<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

/**
 * Collection helper class to work with items in object-oriented way.
 *
 * @template TKey
 * @template TValue
 *
 * @internal
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers
 */
class Collection implements Arrayable, \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @param array<TKey,TValue> $items The array of items.
     */
    public function __construct(protected array $items = [])
    {
    }

    /**
     * Checks whether the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Provides the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Maps the items into a new collection.
     *
     * @param callable(TValue,TKey):TValue $callback The callback to filter the items.
     *
     * @return static<TKey,TValue> The new instance of the collection.
     */
    public function map(callable $callback): static
    {
        $result = [];

        foreach ($this->items as $key => $item) {
            $result[$key] = $callback($item, $key);
        }

        return new static($result);
    }

    /**
     * Filters the items in the collection by a given callback or keys.
     *
     * @param callable(TValue,TKey):bool|array $keys The list of keys to exclude or a callback.
     *
     * @return static<TKey,TValue> The new instance of the collection.
     */
    public function except(callable|array $keys): static
    {
        if (is_callable($keys)) {
            $keys = array_filter($this->items, $keys, ARRAY_FILTER_USE_BOTH);
        }

        return new static(
            array_diff_key($this->items, array_flip($keys))
        );
    }

    /**
     * Get all the items in the collection.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Returns the array of items.
     *
     * @return array<TKey,TValue> The array of items.
     */
    public function toArray(): array
    {
        return array_map(
            static fn (mixed $item): mixed => match (true) {
                $item instanceof Arrayable => $item->toArray(),
                $item instanceof \Traversable => iterator_to_array($item),
                default => $item,
            },
            $this->items
        );
    }

    /**
     * Provides an iterator for the items.
     *
     * @return \Traversable<TKey,TValue>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Serializes the collection to JSON.
     *
     * @return array<TKey,TValue> The array of items.
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
