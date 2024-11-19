<?php
/**
 * Bootstrap the WP test environment.
 *
 * @package CosmoUsers
 */

// WP core test suite will make these the option values automatically.
global $wp_tests_options;

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv('WP_PHPUNIT__DIR') . '/includes/functions.php';

// Enable plugin-template.
tests_add_filter(
    'plugins_loaded',
    static fn() => require dirname( __DIR__ ) . '/index.php',
);

// Load the WP testing environment configuration.
define('WP_TESTS_CONFIG_FILE_PATH', getenv('WP_TESTS_DIR') . '/wp-tests-config.php');

// Start up the WP testing environment.
require getenv('WP_PHPUNIT__DIR') . '/includes/bootstrap.php';
