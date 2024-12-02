<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Users\Api;

use Rumur\WordPress\CosmoUsers\Exception;
use Rumur\WordPress\CosmoUsers\Users\ReadService;

/**
 * Class RestController
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Users\Api
 */
class RestController extends \WP_REST_Controller
{
    public function __construct(
        protected ReadService $userService,
        protected $namespace = 'cosmo/v1',
        protected $rest_base = 'users' // phpcs:ignore Inpsyde.CodeQuality.VariablesName.SnakeCaseVar, Inpsyde.CodeQuality.LineLength.TooLong
    ) {
    }

    /**
     * Register the routes for the users and associates them with the controller.
     */
    public function register_routes(): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'methods' => 'GET',
            'callback' => $this->get_items(...),
            'permission_callback' => '__return_true',
            'args' => [
                'limit' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ],
                'offset' => [
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => $this->get_item(...),
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Get a single user.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response The response data.
     */
    public function get_items($request): \WP_REST_Response // phpcs:ignore PSR1.Methods.CamelCapsMethodName, Inpsyde.CodeQuality.NoAccessors.NoGetter, Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType -- It's a WP REST API method.
    {
        return new \WP_REST_Response(
            $this->userService->users(
                (int) $request->get_param('limit'),
                (int) $request->get_param('offset')
            ),
            \WP_Http::OK
        );
    }

    /**
     * Get a single user.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return \WP_REST_Response|\WP_Error The response data.
     */
    public function get_item($request): \WP_REST_Response|\WP_Error // phpcs:ignore PSR1.Methods.CamelCapsMethodName, Inpsyde.CodeQuality.NoAccessors.NoGetter, Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType -- It's a WP REST API method.
    {
        $responseError = new \WP_Error(
            'rest_user_not_found',
            __('User not found.', 'cosmo-users'),
            ['status' => \WP_Http::NOT_FOUND]
        );

        try {
            $user = $this->userService->userById((int) $request->get_param('id'));

            if ($user === []) {
                return $responseError;
            }

            return new \WP_REST_Response($user, \WP_Http::OK);
        } catch (Exception $error) {
            _doing_it_wrong(__METHOD__, esc_attr($error->getMessage()), '0.1.0');
        }

        return $responseError;
    }
}
