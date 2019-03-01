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

/**
 * Utility functions for container configuration.
 */
class ContainerUtils {
    /**
     * Lazily load a config value for some container initialization
     *
     * @param string $configKey The config key to load.
     *
     * @return ReferenceInterface A reference for use in the container initialization.
     */
    public static function config(string $configKey): ReferenceInterface {
        return new Reference([ConfigurationInterface::class, $configKey]);
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
            /** @type AddonManager addonManager */
            $addonManager = $dic->get(AddonManager::class);
            return $addonManager->getTheme();
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
