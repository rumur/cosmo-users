<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

/**
 * Collection helper class to work with items in object-oriented way.
 *
 * @template TKey
 * @template TValue
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers
 */
class Collection implements Arrayable, \IteratorAggregate, \Countable
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
}
