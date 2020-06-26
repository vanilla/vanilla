<?php

namespace Vanilla;

use Vanilla\Exception\FeatureNotEnabledException;
use Garden\StaticCacheConfigTrait;

/**
 * A helper class for gatekeeping code behind feature flags.
 */
class FeatureFlagHelper {

    use StaticCacheConfigTrait;

    /**
     * Is a feature enabled?
     *
     * @param string $feature The config-friendly name of the feature.
     * @return bool
     */
    public static function featureEnabled(string $feature): bool {
        // We're going to enforce the root "Feature" namespace.
        $configValue = self::c("Feature.{$feature}.Enabled");
        // Force a true boolean.
        $result = filter_var($configValue, FILTER_VALIDATE_BOOLEAN);
        return $result;
    }

    /**
     * If the feature is not enabled, throw an exception.
     *
     * @param string $feature The config-friendly name of the feature.
     * @param string $message A user-friendly message to used in the exception.
     * @param int $code Numeric code for the exception. Should be a relevant HTTP response code.
     * @throws FeatureNotEnabledException If the feature is not enabled.
     */
    public static function ensureFeature(string $feature, string $message = "", int $code = 403) {
        if (self::featureEnabled($feature) === false) {
            if ($message === "") {
                $message = t("This feature is not enabled.");
            }
            throw new FeatureNotEnabledException($message, $code);
        }
    }

    /**
     * If the feature is not enabled, throw the specified exception.
     *
     * @param string $feature The config-friendly name of the feature.
     * @param string $exceptionClass The fully-qualified class name of the exception to throw.
     * @psalm-param class-string<\Exception> $exceptionClass
     * @param array $exceptionArguments Any parameters to be passed to the exception class's constructor.
     * @deprecated 2.7 Use FeatureFlagHelper::ensureFeature instead.
     */
    public static function throwIfNotEnabled(string $feature, string $exceptionClass = \Exception::class, array $exceptionArguments = []) {
        Utility\Deprecation::log();
        if (self::featureEnabled($feature) === false) {
            if ($exceptionClass === \Exception::class && empty($exceptionArguments)) {
                $exceptionArguments = [t('This feature is not enabled.')];
            }
            throw new $exceptionClass(...$exceptionArguments);
        }
    }

    /**
     * Clear the cache on the feature flag helper.
     */
    public static function clearCache() {
        self::$sCache = [];
    }
}
