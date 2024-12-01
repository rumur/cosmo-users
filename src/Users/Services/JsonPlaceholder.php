<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Users\Services;

use Psr\SimpleCache\InvalidArgumentException;
use Rumur\WordPress\CosmoUsers\Cache;
use Rumur\WordPress\CosmoUsers\Client;
use Rumur\WordPress\CosmoUsers\Collection;
use Rumur\WordPress\CosmoUsers\Transformer;
use Rumur\WordPress\CosmoUsers\Users\Transformers;
use Rumur\WordPress\CosmoUsers\Support\Cache\NullCache;
use Rumur\WordPress\CosmoUsers\Users\ReadService;

/**
 * Class JsonPlaceholder Service to provide the access to JSON Placeholder Service.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Users\Services
 */
class JsonPlaceholder implements ReadService
{
    private string $baseUrl = 'https://jsonplaceholder.typicode.com/users';

    public function __construct(
        protected Client $client,
        protected Transformer $transformer,
        protected ?Cache $cache = null,
    ) {

        $this->cache ??= new NullCache();
    }

    public function users(int $limit, int $offset = 0): Collection
    {
        $users = $this->fetchData(args: ['_limit' => $limit, '_start' => $offset]);

        return $this->transformer->collection($users, new Transformers\JsonPlaceholderUser());
    }

    public function userById(int $id): array
    {
        $user = $this->fetchData("/{$id}");

        // If there was no user found, return an empty array.
        if (empty($user)) {
            return [];
        }

        return $this->transformer->item($user, new Transformers\JsonPlaceholderUser());
    }

    /**
     * Fetches the data from the JSON Placeholder Service.
     *
     * @param string $path Partial path to the endpoint.
     * @param array $args Additional arguments to pass to the endpoint.
     *
     * @return mixed The fetched data.
     */
    protected function fetchData(string $path = '/', array $args = []): mixed
    {
        $cacheKey = 'jsp-users-' . md5($path . serialize($args));

        try {
            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }
        } catch (InvalidArgumentException $error) {
            // Let devs know about the issue, but keep going.
            _doing_it_wrong(__METHOD__, esc_attr($error->getMessage()), '0.1.0');
        }

        [$response] = $this->client->resolve([
            fn (): array => wp_safe_remote_get(add_query_arg($args, $this->baseUrl . $path)),
        ]);

        // If there was an error, return an empty array.
        if (is_wp_error($response)) {
            _doing_it_wrong(__METHOD__, esc_attr($response->get_error_message()), '0.1.0');

            return [];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        // If there was an error, return an empty array.
        if (json_last_error() !== JSON_ERROR_NONE) {
            _doing_it_wrong(__METHOD__, esc_attr(json_last_error_msg()), '0.1.0');

            return [];
        }

        try {
            $this->cache->set($cacheKey, $data, 15 * MINUTE_IN_SECONDS);
        } catch (InvalidArgumentException $error) {
            _doing_it_wrong(__METHOD__, esc_attr($error->getMessage()), '0.1.0');
        }

        return $data;
    }
}
