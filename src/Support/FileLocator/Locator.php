<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\FileLocator;

/**
 * Interface Locator
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Support\FileLocator
 *
 * @internal
 */
interface Locator
{
    /**
     * Attempts to locate the file by the given path.
     *
     * @param string $path The path to the file.
     *
     * @return string The full located file path.
     *
     * @throws NotFound When the file was not found.
     */
    public function locate(string $path): string;
}
