<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Users;

use Rumur\WordPress\CosmoUsers\Module as ModuleContract;
use Rumur\WordPress\CosmoUsers\Plugin;

/**
 * User Module for the Cosmo Users.
 *
 * Provides Rest API and Web UI for the Users.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Users
 */
class Module implements ModuleContract
{
    public function register(Plugin $plugin): void
    {
        $plugin->singleton(ReadService::class, Services\JsonPlaceholder::class);

        $plugin->addModules(Api\SubModule::class, Web\SubModule::class);
    }
}
