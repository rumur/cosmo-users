<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

/**
 * Interface For a DataTransformer
 *
 * @since 0.1.0
 *
 * @template TOriginalData
 * @template TTransformedData
 *
 * @package Rumur\WordPress\CosmoUsers
 */
interface DataTransformer
{
    /**
     * Transforms the origin data into a new desired one.
     *
     * @param TOriginalData $data The data to transform.
     *
     * @return TTransformedData The transformed data.
     */
    public function transform(mixed $data);
}
