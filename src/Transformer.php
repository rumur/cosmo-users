<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

/**
 * Interface DataTransformer
 *
 * @since 0.1.0
 *
 * @template TOrigin
 * @template TTransformed
 *
 * @package Rumur\WordPress\CosmoUsers
 */
interface Transformer
{
    /**
     * Transforms the data into a collection.
     *
     * @param iterable<TOrigin> $data The data to transform.
     * @param DataTransformer|callable(TOrigin):TTransformed $transformer Transformer instance.
     *
     * @return Collection<TTransformed> The transformed data.
     */
    public function collection(iterable $data, callable|DataTransformer $transformer): Collection;

    /**
     * Transforms the data into an item.
     *
     * @param TOrigin $data The data to transform.
     *
     * @return TTransformed The transformed data.
     */
    public function item(mixed $data, callable|DataTransformer $transformer): mixed;
}
