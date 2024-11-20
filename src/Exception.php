<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

/**
 * The base plugin exception class, all the rest of the exceptions should extend this class.
 * Makes it easier to catch all the plugin exceptions.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers
 */
class Exception extends \Exception
{
}
