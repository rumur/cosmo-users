<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Transformer;

use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Serializer\ArraySerializer;
use Rumur\WordPress\CosmoUsers\Collection;
use Rumur\WordPress\CosmoUsers\Transformer;
use Rumur\WordPress\CosmoUsers\DataTransformer;

/**
 * Class FractalDriver hides any Fractal traces from the codebase
 * by implementing internal Transformer interface.
 *
 * @since 0.1.0
 *
 * @template TOrigin
 * @template TTransformed
 *
 * @package Rumur\WordPress\CosmoUsers\Support\Transformer
 */
final class FractalAdapter implements Transformer
{
    public function __construct(protected FractalManager $fractal)
    {
        /**
         * This is a workaround to make sure Fractal doesn't wrap with `data` key.
         */
        $this->fractal->setSerializer(new class extends ArraySerializer {
            public function collection(?string $resourceKey, array $data): array
            {
                return $data;
            }
        });
    }

    /**
     * Transforms the data into a collection of items.
     *
     * @param iterable<TOrigin> $data The data to transform.
     * @param DataTransformer|callable(TOrigin):TTransformed $transformer The transformer instance.
     *
     * @return Collection<TTransformed> The transformed data.
     */
    public function collection(iterable $data, callable|DataTransformer $transformer): Collection
    {
        return new Collection(
            $this->fractal->createData(
                new FractalCollection($data, $this->adaptToFractalTransformer($transformer))
            )->toArray()
        );
    }

    /**
     * Transforms the data into a single item.
     *
     * @param TOrigin $data The data to transform.
     *
     * @return TTransformed The transformed data.
     */
    public function item(mixed $data, callable|DataTransformer $transformer): array
    {
        return $this->fractal->createData(
            new Item($data, $this->adaptToFractalTransformer($transformer))
        )->toArray();
    }

    /**
     * @return callable(TOrigin):array<TTransformed>
     */
    private function adaptToFractalTransformer(callable|DataTransformer $transformer): callable
    {
        // We need to cast into an array to make sure,
        // that the Fractal gets the format it expects.
        if (is_callable($transformer)) {
            return static fn (mixed $data): array => (array) $transformer($data);
        }

        return static fn (mixed $data): array => (array) $transformer->transform($data);
    }
}
