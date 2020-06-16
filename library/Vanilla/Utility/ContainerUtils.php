<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Container\Callback;
use Garden\Container\Container;
use Garden\Container\ReferenceInterface;
use Psr\Container\ContainerInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Theme\ThemeService;
use Vanilla\Web\Asset\DeploymentCacheBuster;

/**
 * Utility functions for container configuration.
 */
class ContainerUtils {
    /**
     * Lazily load a config value for some container initialization
     *
     * @param string $key The config key to load.
     * @param mixed $defaultValue
     * @return ReferenceInterface A reference for use in the container initialization.
     */
    public static function config(string $key, $defaultValue = false): ReferenceInterface {
        return new Callback(function (ContainerInterface $dic) use ($key, $defaultValue) {
            /** @var ConfigurationInterface $config */
            $config = $dic->get(ConfigurationInterface::class);
            return $config->get($key, $defaultValue);
        });
    }

    /**
     * Lazily load the current locale key for some container initialization.
     *
     * @return ReferenceInterface A reference for use in the container initialization.
     */
    public static function currentLocale(): ReferenceInterface {
        return new Callback(function (ContainerInterface $dic) {
            $locale = $dic->get(\Gdn_Locale::class);
            return $locale->current();
        });
    }

    /**
     * Lazily load the current theme for some container initialization.
     *
     * @return ReferenceInterface A reference for use in the container initialization.
     */
    public static function currentTheme(): ReferenceInterface {
        return new Callback(function (ContainerInterface $dic) {
            /** @type ThemeService $themeService */
            $themeService = $dic->get(ThemeService::class);
            return $themeService->getCurrentThemeAddon();
        });
    }

    /**
     * Lazily load a cache busting string for some configuration.
     *
     * @return ReferenceInterface
     */
    public static function cacheBuster(): ReferenceInterface {
        return new Callback(
            function (ContainerInterface $dic) {
                $cacheBuster = $dic->get(DeploymentCacheBuster::class);
                return $cacheBuster->value();
            }
        );
    }

    /**
     * Replace container instance of a class, if present, and optionally add it as an alias for its replacement.
     *
     * @param Container $container
     * @param string $target
     * @param string $replacement
     */
    public static function replace(
        Container $container,
        string $target,
        string $replacement,
        bool $addAlias = false
    ): void {
        if ($container->hasInstance($target)) {
            $new = $container->get($replacement);
            $container->setInstance($target, $new);
        }
        if ($addAlias) {
            $container->rule($replacement)
                ->addAlias($target);
        }
    }
}
