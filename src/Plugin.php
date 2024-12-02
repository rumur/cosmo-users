<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers;

use Psr\Container\ContainerInterface;
use Rumur\WordPress\CosmoUsers\Support\Container\Exceptions\NotInstantiable;
use SplQueue;

/**
 * The Main Plugin Class, responsible for bootstrapping the plugin,
 * managing its modules, hooks and dependencies.
 *
 * @since 0.1.0
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Rumur\WordPress\CosmoUsers
 */
class Plugin extends Support\Container\Container
{
    /**
     * Plugin's modules to boot with.
     *
     * @var SplQueue<int,Module>
     */
    protected SplQueue $modules;

    /**
     * Plugin's activation callbacks queue.
     *
     * @var SplQueue<callable(Plugin $plugin): void>
     */
    protected SplQueue $activationQueue;

    /**
     * Plugin's deactivation callbacks queue.
     *
     * @var SplQueue<callable(Plugin $plugin): void>
     */
    protected SplQueue $deactivationQueue;

    /**
     * The root plugin file.
     */
    protected string $rootFile;

    /**
     * Plugin constructor.
     */
    protected function __construct()
    {
        $this->rootFile = plugin_dir_path(__DIR__) . 'index.php';
        $this->modules = new SplQueue();
        $this->activationQueue = new SplQueue();
        $this->deactivationQueue = new SplQueue();
    }

    /**
     * Creates an instance of the plugin.
     *
     * @param callable|null $configurator The plugin configurator.
     *
     * @throws NotInstantiable When it's not able to instantiate dependencies of the configurator.
     */
    public static function instance(?callable $configurator = null): static
    {
        static $plugin;

        if ($plugin) {
            return $plugin;
        }

        $plugin = new static();

        $plugin->bootstrap();

        if ($configurator) {
            $plugin->call($configurator);
        }

        $plugin->register();

        return $plugin;
    }

    /**
     * Checks whether the current theme is a block-based theme.
     *
     * @link https://developer.wordpress.org/themes/getting-started/what-is-a-theme/
     */
    public function isBlockBasedTheme(): bool
    {
        return wp_is_block_theme();
    }

    /**
     * Checks whether the mode is in debug.
     */
    public function isDebugModeOn(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * Normalizes the path.
     *
     * @param string|null $path The path to normalize.
     */
    protected function normalizePath(?string $path = null): string
    {
        return trim($path ?? '', DIRECTORY_SEPARATOR);
    }

    /**
     * Gets the path to the plugin's directory.
     *
     * @param string|null $path The path to the file or null to get the root directory.
     *
     * @return string The full path to the plugin's directory.
     */
    public function pathTo(?string $path = null): string
    {
        return plugin_dir_path($this->rootFile) . $this->normalizePath($path);
    }

    /**
     * Gets the URL to the plugin's directory.
     *
     * @param string|null $path The path to the file or null to get the root directory.
     *
     * @return string The full URL to the plugin's directory.
     */
    public function urlTo(?string $path = null): string
    {
        return plugin_dir_url($this->rootFile) . $this->normalizePath($path);
    }

    /**
     * Provides the full path to the plugin's assets file.
     *
     * @param string|null $path The path to the file or null to get the root directory.
     */
    public function pathToAssets(?string $path = null): string
    {
        return $this->pathTo('/assets/' . $this->normalizePath($path));
    }

    /**
     * Provides the full path to the plugin's build file.
     *
     * @param string|null $path The path to the file or null to get the root directory.
     */
    public function pathToBuild(?string $path = null): string
    {
        return $this->pathTo('/build/' . $this->normalizePath($path));
    }

    /**
     * Sets the plugin file.
     *
     * @param string $rootFile Plugin's root file.
     */
    public function changeRootFile(string $rootFile): static
    {
        assert(is_file($rootFile), 'The plugin file must be a valid file.');

        $this->rootFile = $rootFile;

        return $this;
    }

    /**
     * Adds the list of modules for the plugin.
     *
     * @template TModule of Module
     *
     * @param class-string<TModule>|TModule ...$modules
     */
    public function addModules(string|Module ...$modules): static
    {
        foreach ($modules as $module) {
            $this->modules->push($module);
        }

        return $this;
    }

    /**
     * Adds the activation callbacks, to run when plugin is being activated.
     *
     * @param callable(Plugin $plugin): void $callback The callback to run on activation.
     */
    public function onActivation(callable $callback): static
    {
        $this->activationQueue->push($callback);

        return $this;
    }

    /**
     * Adds the deactivation callbacks, to run when plugin is being deactivated.
     *
     * @param callable(Plugin $plugin): void $callback The callback to run on deactivation.
     */
    public function onDeactivation(callable $callback): static
    {
        $this->deactivationQueue->push($callback);

        return $this;
    }

    /**
     * Bootstraps the plugin.
     */
    protected function bootstrap(): void
    {
        /**
         * Fires before the plugin is bootstrapped.
         *
         * @param Plugin $this The plugin instance.
         *
         * @since 0.1.0
         */
        do_action_ref_array('rumur/cosmo-users/pre-bootstrap', [&$this]);

        /**
         * Register the plugin container bindings, that comes with the plugin.
         *
         * @since 0.1.0
         */
        $this->registerContainerBindings();

        /**
         * Fires after the plugin is bootstrapped.
         *
         * @param Plugin $this The plugin instance.
         *
         * @since 0.1.0
         */
        do_action_ref_array('rumur/cosmo-users/post-bootstrap', [&$this]);
    }

    /**
     * Register the plugin.
     */
    protected function register(): void
    {
        /**
         * Fires before the plugin is registered all its modules and hooks.
         *
         * @param Plugin $this The plugin instance.
         *
         * @since 0.1.0
         */
        do_action_ref_array('rumur/cosmo-users/pre-registered', [&$this]);

        $this->registerModules();
        $this->registerActivationHook();
        $this->registerDeactivationHook();

        /**
         * Fires after the plugin is registered all its modules and hooks.
         *
         * @param Plugin $this The plugin instance.
         *
         * @since 0.1.0
         */
        do_action_ref_array('rumur/cosmo-users/post-registered', [&$this]);
    }

    /**
     * Registers the activation hook.
     */
    protected function registerActivationHook(): void
    {
        try {
            register_activation_hook($this->rootFile, function (): void {
                while (!$this->activationQueue->isEmpty()) {
                    $this->call($this->activationQueue->pop());
                }
            });
        } catch (\Throwable $error) {
            _doing_it_wrong(__METHOD__, esc_attr($error->getMessage()), '0.1.0');
        }
    }

    /**
     * Registers the deactivation hook.
     */
    protected function registerDeactivationHook(): void
    {
        try {
            register_deactivation_hook($this->rootFile, function (): void {
                while (!$this->deactivationQueue->isEmpty()) {
                    $this->call($this->deactivationQueue->pop());
                }
            });
        } catch (\Throwable $error) {
            _doing_it_wrong(__METHOD__, esc_attr($error->getMessage()), '0.1.0');
        }
    }

    /**
     * Registers the plugin's custom endpoints.
     */
    protected function registerModules(): void // phpcs:ignore Inpsyde.CodeQuality.NestingLevel.High -- It's fine to keep it as is.
    {
        while (!$this->modules->isEmpty()) {
            /**
             * Once module is being registered we no longer need it in the queue,
             * so we can safely remove it from there and free up the memory.
             */
            $module = $this->modules->pop();

            // If the module is a string, we need to resolve it as it's a class name.
            if (is_string($module)) {
                try {
                    $module = $this->resolve($module);
                } catch (NotInstantiable $error) {
                    _doing_it_wrong(__METHOD__, esc_attr($error->getMessage()), '0.1.0');
                    continue;
                }
            }

            /**
             * Fires before the module is registered.
             *
             * @since 0.1.0
             *
             * @param Module $module The module instance.
             * @param Plugin $this The plugin instance.
             */
            do_action('rumur/cosmo-users/module/pre-registered', $module, $this);

            /**
             * Modules can register their own submodules,
             * so that they get registered at the end of `$this->modules` queue.
             */
            $module->register($this);

            /**
             * Fires after the module has been registered.
             *
             * @since 0.1.0
             *
             * @param Module $module The module instance.
             * @param Plugin $this The plugin instance.
             */
            do_action('rumur/cosmo-users/module/post-registered', $module, $this);
        }
    }

    /**
     * Registers the plugin container bindings.
     *
     * @globals \wpdb $wpdb
     */
    protected function registerContainerBindings(): void
    {
        global $wpdb;

        // Set the plugin instance and its aliases.
        $this->singleton(ContainerInterface::class, fn (): static => $this);
        $this->singleton(Container::class, fn (): static => $this);
        $this->singleton(static::class, fn (): static => $this);
        // Set the global $wpdb instance.
        $this->singleton(\wpdb::class, static fn () => $wpdb);
    }
}
