<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Users\Services;

use Rumur\WordPress\CosmoUsers\Support\Transformer\AbstractDataTransformer;

/**
 * Class JsonPlaceholderUserTransformer
 *
 * Transforms the data from JSON Placeholder Service into a normalized internal format.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Users\Services
 */
class JsonPlaceholderUserTransformer extends AbstractDataTransformer
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
        return [
            'id' => absint($this->pluckValue($data, 'id')),
            'name' => esc_attr($this->pluckValue($data, 'name')),
            'username' => esc_attr($this->pluckValue($data, 'username')),
            'email' => esc_attr($this->pluckValue($data, 'email')),
            'phone' => esc_attr($this->pluckValue($data, 'phone')),
            'website' => esc_attr($this->pluckValue($data, 'website')),
            'company' => [
                'name' => esc_attr($this->pluckValue($data, 'company.name')),
                'slogan' => esc_attr($this->pluckValue($data, 'company.catchPhrase')),
            ],
            'address' => [
                'street' => esc_attr($this->pluckValue($data, 'address.street')),
                'suite' => esc_attr($this->pluckValue($data, 'address.suite')),
                'city' => esc_attr($this->pluckValue($data, 'address.city')),
                'zipcode' => esc_attr($this->pluckValue($data, 'address.zipcode')),
            ],
        ];
    }
}
