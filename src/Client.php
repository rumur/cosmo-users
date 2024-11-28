<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

/**
 * The HTTP Client Interface.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers
 */
interface Client
{
    /**
     * Resolves a given list of requests.
     *
     * @param array<(callable():\WP_Error|array)> $requests The list of requests to dispatch.
     *
     * @return iterable<\WP_Error|array> The list of dispatched responses.
     */
    public function resolve(iterable $requests): array;
}
