<?php

namespace Vanilla;

use Gdn;
use Gdn_Configuration;
use Exception;

/**
 * A helper class for gatekeeping code behind feature flags.
 */
class FeatureFlagHelper {

    /**
     * Is a feature enabled?
     *
     * @param string $feature The config-friendly name of the feature.
     * @return bool
     * @throws \Garden\Container\NotFoundException If unable to find the config class in the container.
     * @throws \Garden\Container\ContainerException If unable to get the config class instance from the container.
     */
    public static function featureEnabled(string $feature): bool {
        /** @var Gdn_Configuration $config */
        $config = Gdn::getContainer()->get(Gdn_Configuration::class);

        // We're going to enforce the root "Feature" namespace.
        $configValue = $config->get("Feature.{$feature}");
        // Force a true boolean.
        $result = filter_var($configValue, FILTER_VALIDATE_BOOLEAN);
        return $result;
    }

    /**
     * If the feature is not enabled, throw the specified exception.
     *
     * @param string $feature The config-friendly name of the feature.
     * @param string $exceptionClass The fully-qualified class name of the exception to throw.
     * @param array $exceptionArguments Any parameters to be passed to the exception class's constructor.
     */
    public static function throwIfNotEnabled(string $feature, string $exceptionClass = Exception::class, array $exceptionArguments = []) {
        if (self::featureEnabled($feature) === false) {
            if ($exceptionClass === Exception::class && empty($exceptionArguments)) {
                $exceptionArguments = [t('This feature is not enabled.')];
            }
            throw new $exceptionClass(...$exceptionArguments);
        }
    }
}
