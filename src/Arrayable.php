<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @template TKey
     * @template TValue
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array;
}
