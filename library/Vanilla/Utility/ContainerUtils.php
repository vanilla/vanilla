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
     * Replace one type of object in the container with another type of object. Existing shared instances will be
     * overwritten. An alias will be created from the original type to the new type.
     *
     * Sometimes an object has limitations or shortcomings that could be resolved by something like a decorator, where
     * a drop-in replacement wraps existing functionality in enhancements or customizations. This method could configure
     * a container to use the decorator, replacing stored instances and ensuring new requests to the container would
     * receive the decorator instead of the original class.
     *
     * @param Container $container Container to configure.
     * @param string $old Container rule to target for replacement. Shared instances will be overwritten.
     * @param string $new Container rule used to determine what will be replace the target in the container.
     */
    public static function replace(Container $container, string $old, string $new): void {
        if ($container->hasInstance($old)) {
            $container->setInstance($old, null);
        }

        $container->rule($new)
            ->addAlias($old);
    }

    /**
     * Add a call to the container, but also make that call if the container has an existing instance.
     *
     * Sometimes you want to add a call to a container rule, but the container may have already instantiated a shared instance.
     * This method will let you add the rule, but also make sure the call is replicated if there is already an instance.
     *
     * @param Container $container The container to configure.
     * @param string $rule The name of the rule to configure.
     * @param string $method The name of the method to call.
     * @param array $args The method's arguments.
     */
    public static function addCall(Container $container, string $rule, string $method, array $args) {
        $container->rule($rule)->addCall($method, $args);
        if ($container->hasInstance($rule)) {
            $obj = $container->get($rule);
            $container->call([$obj, $method], $args);
        }
    }
}
