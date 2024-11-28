<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Transformer;

use Rumur\WordPress\CosmoUsers\DataTransformer;

/**
 * Class AbstractDataTransformer
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Support\Transformer
 */
abstract class AbstractDataTransformer implements DataTransformer
{
    /**
     * Pluck the value from the data by the given path.
     *
     * @template TDefault
     * @template TValue
     *
     * @param array<TValue>|object<TValue> $data The data retrieved from the source.
     * @param string $path The path to the value. Example: `company.name`
     * @param mixed $default The default value if the path is not found.
     *
     * @return TDefault|TValue The value from the data or the default value.
     */
    protected function pluckValue(mixed $data, string $path, mixed $default = null): mixed
    {
        $path = explode('.', $path);

        foreach ($path as $key) {
            $retrieved = match (true) {
                is_array($data) && isset($data[$key]) => $data[$key],
                is_object($data) && isset($data->{$key}) => $data->{$key},
                default => null,
            };

            if ($retrieved === null) {
                return $default;
            }

            // Keep going.
            $data = $retrieved;
        }

        return $data;
    }
}
