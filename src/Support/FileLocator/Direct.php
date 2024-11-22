<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\FileLocator;

/**
 * Files Locator by a direct path.
 *
 * @since 0.1.0
 *
 * @internal
 * @package Rumur\WordPress\CosmoUsers\Support\FileLocator
 */
class Direct implements Locator
{
    /**
     * Direct Locator constructor.
     *
     * @param string $lookupDir The path to the plugin's directory.
     */
    public function __construct(protected string $lookupDir)
    {
    }

    /**
     * Attempts to locate the file by the given path.
     *
     * @param string $path The path to the file.
     *
     * @return string The full located file path.
     *
     * @throws NotFound When the file was not found.
     */
    public function locate(string $path): string
    {
        $located = sprintf('%s/%s', $this->lookupDir, ltrim($path, '/'));

        if (!file_exists($located) || !is_readable($located)) {
            throw new NotFound(sprintf('The file [%s] was not found.', esc_attr($path)));
        }

        return $located;
    }
}
