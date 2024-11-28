<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

/**
 * The Template Interface to facilitate the template lookup.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers
 */
interface Template
{
    /**
     * Renders the template.
     *
     * @param string $path The template path.
     * @param array $args The template arguments.
     *
     * @return string The rendered template.
     */
    public function render(string $path, array $args = []): string;
}
