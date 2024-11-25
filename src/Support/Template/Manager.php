<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Template;

use Rumur\WordPress\CosmoUsers\Template;
use Rumur\WordPress\CosmoUsers\Support\FileLocator\NotFound;
use Rumur\WordPress\CosmoUsers\Support\FileLocator\Locator;

/**
 * Plugin's Templates manager, also works as a composite locator.
 *
 * @since 0.1.0
 *
 * @internal
 * @package Rumur\WordPress\CosmoUsers\Support\Template
 */
class Manager implements Template, Locator
{
    /**
     * The locators.
     * Order is matter.
     *
     * @var array<Locator>
     */
    protected array $locators = [];

    /**
     * Manager constructor.
     *
     * @param Locator ...$locators The locators to locate template files with.
     */
    public function __construct(Locator ...$locators)
    {
        $this->locators = $locators;
    }

    /**
     * Locates the template.
     *
     * @param string $path The path to the template.
     *
     * @return string located full path to the template.
     *
     * @throws TemplateNotFound When the template was not found.
     */
    public function locate(string $path): string
    {
        // Ensure that the path includes the desired file extension.
        if (false === preg_match('/\.(html|php)?/', $path)) {
            $path .= '.php';
        }

        foreach ($this->locators as $locator) {
            try {
                return $locator->locate($path);
            } catch (NotFound) {
                continue;
            }
        }

        throw new TemplateNotFound(sprintf('[%s] template was not found.', esc_attr($path)));
    }

    /**
     * Renders the template.
     *
     * @param string $path The template path.
     * @param array $args The template arguments.
     *
     * @return string The rendered template.
     */
    public function render(string $path, array $args = []): string
    {
        try {
            $locatedPath = $this->locate($path);
        } catch (TemplateNotFound $error) {
            _doing_it_wrong(__METHOD__, esc_attr($error->getMessage()), '0.1.0');
            // Return a comment with the error message, so Devs could see it in the source.
            return sprintf('<!-- [%s] is not possible to locate. -->', esc_attr($path));
        }

        ob_start();
        load_template($locatedPath, false, $args);
        return ob_get_clean();
    }
}
