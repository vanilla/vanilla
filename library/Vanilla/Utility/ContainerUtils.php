<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Container\Callback;
use Garden\Container\Reference;
use Garden\Container\ReferenceInterface;
use Psr\Container\ContainerInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\AddonManager;
use Vanilla\Models\ThemeModel;

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
            /** @type ThemeModel $themeModel */
            $themeModel = $dic->get(ThemeModel::class);
            return $themeModel->getCurrentThemeAddon();
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
                $cacheBuster = $dic->get(\Vanilla\Web\Asset\DeploymentCacheBuster::class);
                return $cacheBuster->value();
            }
        );
    }
}
