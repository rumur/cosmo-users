<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Cache;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class InvalidCacheKey
 *
 * @since 0.1.0
 * @internal
 *
 * @package Rumur\WordPress\CosmoUsers\Support\Cache
 */
final class InvalidCacheKey extends \InvalidArgumentException implements InvalidArgumentException
{
    public function __construct(string $key)
    {
        parent::__construct(\sprintf('"%s" is not a valid PSR-16 cache key', $key));
    }
}
