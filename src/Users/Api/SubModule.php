<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Users\Api;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Rumur\WordPress\CosmoUsers\Module as ModuleContract;
use Rumur\WordPress\CosmoUsers\Plugin;
use Rumur\WordPress\CosmoUsers\Users\ReadService;

/**
 * Users REST API SubModule, to facilitate the REST API access to users data.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Users\Api
 */
class SubModule implements ModuleContract
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    /**
     * Register Users' REST Api module.
     *
     * @param Plugin $plugin The plugin instance.
     */
    public function register(Plugin $plugin): void
    {
        /**
         * Register the REST Api routes.
         *
         * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
         */
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    /**
     * Register REST Api routes.
     *
     * @return void
     */
    public function registerRestRoutes(): void
    {
        if ($this->container->has(ReadService::class)) {
            try {
                $this->container->resolve(RestController::class)->register_routes();
            } catch (ContainerExceptionInterface $exception) {
                _doing_it_wrong(
                    __METHOD__,
                    esc_attr($exception->getMessage()),
                    '0.1.0'
                );
            }

            return;
        }

        _doing_it_wrong(
            __METHOD__,
            'The [Users\ReadService::class] should be registered before the Users\Api\SubModule::registerRestRoutes is called.', // phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
            '0.1.0'
        );
    }
}
