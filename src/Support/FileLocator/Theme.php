<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\FileLocator;

/**
 * Theme Locator class allows to locate files within the theme including the child theme.
 *
 * @since 0.1.0
 *
 * @internal
 * @package Rumur\WordPress\CosmoUsers\Support\FileLocator\Locators
 */
class Theme implements Locator
{
    /**
     * Lookup directories.
     *
     * @var array<non-empty-string>
     */
    protected array $lookupDirs;

    /**
     * Theme Locator constructor.
     *
     * @param non-empty-string ...$lookupDirs The list of directories to look for the file.
     */
    public function __construct(string ...$lookupDirs)
    {
        $this->lookupDirs = $lookupDirs;
    }

    /**
     * Locates the files in the theme.
     *
     * @param string $path The path to the file.
     *
     * @return string The full located file path.
     *
     * @throws NotFound When the file was not found.
     */
    public function locate(string $path): string
    {
        $files = array_reduce(
            $this->lookupDirs,
            static fn (array $places, string $folder): array => array_merge(
                $places,
                [sprintf('%s/%s', $folder, ltrim($path, '/'))]
            ),
            []
        );

        $located = locate_template($files, false, false);

        if ('' === $located) {
            throw new NotFound(sprintf('The file [%s] was not found.', esc_attr($path)));
        }

        return $located;
    }
}
