<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

/**
 * Interface used to identify AI features.
 */
interface ExternalServiceInterface
{
    /**
     * Provide an array to indicate if a feature is enabled or not.
     *
     * An AI feature can have multiple components that need to be turned on for it to work or it can use different models hence why we are returning an array of the form
     *
     * ["NameOfTheFeature" => [feature1 => true, feature2 => false]]
     *
     * @return array
     */
    public function isEnabled(): array;

    /**
     * Return the human-readable name of the feature.
     *
     * @return string
     */
    public function getName(): string;
}
