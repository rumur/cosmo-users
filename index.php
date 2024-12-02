<?php

/**
 * Plugin Name:  Cosmo Users
 * Description:  Plugin to Show Users via Ajax.
 * Version:      0.1.0
 * Author:       rumur
 * Author URI:   https://github.com/rumur
 * Text Domain:  rumur
 * PHP Version:  8.1
 * WP Version:   6.5
 * License:      MIT License
 */

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

if (!class_exists(Plugin::class) && is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (!class_exists(Plugin::class)) {
    return;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Register Plugins Services
    |--------------------------------------------------------------------------
    | - Template Locators: To locate the template files.
    | - HTTP Client: To dispatch HTTP requests.
    | - Cache: To cache data for a while, gets cleared upon deactivation.
    | - Modules: To Extend Plugin's capabilities.
    | - Transformer: To allow modules to transform their data.
    */
    Plugin::instance(static function (Plugin $plugin): void {
        // Register the plugin's Template service.
        $plugin->singleton(
            Template::class,
            static fn (): Template => new Support\Template\Manager(
                new Support\FileLocator\Theme(
                    'parts/cosmo-users',
                    'templates/cosmo-users',
                    'template-parts/cosmo-users'
                ),
                new Support\FileLocator\Direct(lookupDir: __DIR__ . '/parts'),
            )
        );

        // Register the plugin's HTTP Client to dispatch/resolve requests.
        $plugin->singleton(
            Client::class,
            static fn (): Client => $plugin->resolve(Support\Http\Concurrent::class)
        );

        // Register the plugin's Cache service.
        $plugin->singleton(
            Cache::class,
            static fn (): Cache => new Support\Cache\WordPressCache('cosmo-users')
        );

        // Set an alias for the PSR-16 Cache Interface.
        $plugin->singleton(
            \Psr\SimpleCache\CacheInterface::class,
            static fn (): \Psr\SimpleCache\CacheInterface => $plugin->get(Cache::class)
        );

        // Clear plugin's Cache upon deactivation.
        $plugin->onDeactivation(
            static fn (Cache $cache): bool => $cache->clear()
        );

        // Register plugin's Transformer service.
        $plugin->singleton(
            Transformer::class,
            static fn (): Transformer => $plugin->resolve(Support\Transformer\FractalAdapter::class)
        );

        // Register plugin's User modules and all its dependencies.
        $plugin->addModules(new Users\Module());
    });
} catch (Support\Container\Exceptions\NotInstantiable $error) {
    _doing_it_wrong(__FILE__, esc_attr($error->getMessage()), '0.1.0');
}
