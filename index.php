<?php

/**
 * Plugin Name:  Cosmo Users
 * Description:  Plugin to Show Users via Ajax.
 * Version:      0.1.0
 * Author:       rumur
 * Author URI:   https://github.com/rumur
 * Text Domain:  rumur
 * PHP Version:  8.1
 * License:      MIT License
 */

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

if (!class_exists(Plugin::class) && is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class_exists(Plugin::class) && Plugin::boot();
