<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Users\Services;

use Rumur\WordPress\CosmoUsers\DataTransformer;

/**
 * Class JsonPlaceholderUserTransformer
 *
 * Transforms the data from JSON Placeholder Service into a normalized internal format.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Users\Services
 */
class JsonPlaceholderUserTransformer implements DataTransformer
{
    /**
     * @param object $data
     * @return array{
     *     id: int,
     *     name: string,
     *     username: string,
     *     email: string,
     *     phone: string,
     *     website: string,
     *     company: array{name: string, slogan: string},
     *     address: array{street: string, suite: string, city: string, zipcode: string}
     * }
     */
    public function transform(mixed $data): array
    {
        $data = $this->normalize($data);

        return [
            'id' => absint($data->id),
            'name' => esc_attr($data->name),
            'username' => esc_attr($data->username),
            'email' => esc_attr($data->email),
            'phone' => esc_attr($data->phone),
            'website' => esc_attr($data->website),
            'company' => [
                'name' => esc_attr($data->company->name),
                'slogan' => esc_attr($data->company->catchPhrase),
            ],
            'address' => [
                'street' => esc_attr($data->address->street),
                'suite' => esc_attr($data->address->suite),
                'city' => esc_attr($data->address->city),
                'zipcode' => esc_attr($data->address->zipcode),
            ],
        ];
    }

    /**
     * Normalizes the data.
     *
     * @param mixed $data The data to normalize.
     *
     * @return object
     */
    protected function normalize(mixed $data): object
    {
        return (object) wp_parse_args($data, [
            'id' => 0,
            'name' => '',
            'username' => '',
            'email' => '',
            'phone' => '',
            'website' => '',
            'company' => [
                'name' => '',
                'slogan' => '',
            ],
            'address' => [
                'street' => '',
                'suite' => '',
                'city' => '',
                'zipcode' => '',
            ],
        ]);
    }
}
