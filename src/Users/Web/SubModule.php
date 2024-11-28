<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Users\Web;

use Rumur\WordPress\CosmoUsers\Exception;
use Rumur\WordPress\CosmoUsers\Module as ModuleContract;
use Rumur\WordPress\CosmoUsers\Plugin;
use Rumur\WordPress\CosmoUsers\Template;
use Rumur\WordPress\CosmoUsers\Users\ReadService;

/**
 * Web Users SubModule, to handle the web endpoints and redefine the template.
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Users\Web
 */
class SubModule implements ModuleContract
{
    public const ENDPOINT_USERS = 'cosmo-users';

    public function __construct(
        protected Plugin $plugin,
        protected Template $template,
        protected ReadService $users,
    ) {
    }

    /**
     * Register Users' Web Sub Module, to facilitate the web endpoints.
     *
     * @param Plugin $plugin The plugin instance.
     */
    public function register(Plugin $plugin): void
    {
        /**
         * Registers the block using the metadata loaded from the `block.json` file.
         * Behind the scenes, it registers also all assets so they can be enqueued
         * through the block editor in the corresponding context.
         *
         * @see https://developer.wordpress.org/reference/functions/register_block_type/
         */
        add_action('init', [$this, 'registerBlockAssets']);

        add_action('init', [$this, 'registerWebEndpoints']);

        add_action('template_redirect', [$this, 'parseRequest']);

        /**
         * Register all web endpoints into rewrite rules, when plugin is being activated.
         *
         * @see https://developer.wordpress.org/reference/functions/register_activation_hook/
         */
        $plugin->onActivation(function (): void {
            $this->registerWebEndpoints();
            flush_rewrite_rules();
        });

        /**
         * Clean up the web endpoints from the rewrite rules, when plugin is deactivated.
         *
         * @see https://developer.wordpress.org/reference/functions/register_deactivation_hook/
         */
        $plugin->onDeactivation('flush_rewrite_rules');
    }

    /**
     * Register the block assets.
     *
     * @throws Exception When the block was not registered and the debug mode is on.
     */
    public function registerBlockAssets(): void
    {
        // Prevent registering the block in the admin area, making it available for the web only.
        if (is_admin()) {
            return;
        }

        $registered = register_block_type($this->plugin->pathToBuild('users-table'));

        if (!$registered instanceof \WP_Block_Type) {
            if ($this->plugin->isDebugModeOn()) {
                throw new Exception('The block was not registered, run the `npm run build` command.'); // phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
            }

            _doing_it_wrong(
                __METHOD__,
                'The block was not registered, run the `npm run build` command.',
                '0.1.0'
            );
        }
    }

    /**
     * Register all necessary web endpoints.
     *
     * @globals \WP_Rewrite $wp_rewrite
     *
     * @return void
     */
    public function registerWebEndpoints(): void
    {
        /**
         * Register the users endpoint for the web.
         * It makes the endpoint available for the web:
         *
         * Examples when permalinks are enabled:
         * ```
         * /cosmo-users
         * ```
         *
         * When Permalinks are disabled, it will be available as:
         * ```
         * /index.php?cosmo-users=1
         *```
         */
        add_rewrite_endpoint(static::ENDPOINT_USERS, EP_ROOT);

        // We need point single user endpoints back to the main users' endpoint.
        add_rewrite_rule(
            sprintf('^%s/?', static::ENDPOINT_USERS),
            sprintf('index.php?%s=1', static::ENDPOINT_USERS),
            'top'
        );
    }

    /**
     * Parse the request to handle the web endpoints.
     *
     * @global \WP_Query $wp_query
     *
     * @return void
     */
    public function parseRequest(): void
    {
        global $wp_query;

        if (array_key_exists(static::ENDPOINT_USERS, $wp_query->query_vars)) {
            echo $this->renderPageHtml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's being escaped inside the method.
            die();
        }
    }

    /**
     * Render the page HTML.
     *
     * @return string
     */
    protected function renderPageHtml(): string
    {
        if ($this->plugin->isBlockBasedTheme()) {
            return $this->renderBlockBasedTheme();
        }

        return $this->renderClassicTheme();
    }

    /**
     * Render the block-based theme template.
     *
     * @globals string $_wp_current_template_content
     *
     * @return string
     */
    protected function renderBlockBasedTheme(): string
    {
        global $_wp_current_template_content;

        // Set the content that will be rendered by the template canvas.
        $_wp_current_template_content = sprintf(
            '<!-- wp:template-part {"slug":"header"} /-->%s<!-- wp:template-part {"slug":"footer"} /-->', // phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
            $this->template->render('users-table.html'),
        );

        // Add hooks for template canvas.
        // Add viewport meta tag.
        add_action('wp_head', '_block_template_viewport_meta_tag', 0);

        // Render title tag with content, regardless of whether theme has title-tag support.
        // Remove conditional title tag rendering...
        remove_action('wp_head', '_wp_render_title_tag', 1);
        // ...and make it unconditional.
        add_action('wp_head', '_block_template_render_title_tag', 1);
        // Render the text into a title tag.
        add_filter('pre_get_document_title', [$this, 'renderDocumentTitle']);

        // This file will be included instead of the theme's template file.
        // And it will render the current '_wp_current_template_content' variable.
        ob_start();
        include ABSPATH . WPINC . '/template-canvas.php';
        return ob_get_clean();
    }

    /**
     * Render the classic theme template.
     *
     * @return string
     */
    protected function renderClassicTheme(): string
    {
        ob_start();
        get_header();
        echo wp_kses_post(
            do_blocks(
                $this->template->render('users-table.html')
            )
        );
        get_footer();
        return ob_get_clean();
    }

    /**
     * Filter the document title.
     *
     * @param string $title Default document title.
     *
     * @return string
     */
    public function renderDocumentTitle(string $title): string
    {
        return esc_attr__('Cosmo Users Table', 'cosmo-users') . ' &ndash; ' . esc_attr(get_bloginfo('name')); // phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
    }
}
