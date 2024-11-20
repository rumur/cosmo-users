<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

/**
 * The Plugin's Module Interface.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers
 */
interface Module
{
    /**
     * Register plugin module.
     */
    public function register(Plugin $plugin): void;
}
