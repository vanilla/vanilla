<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Feature;

use Vanilla\FeatureFlagHelper;
use Vanilla\InjectableInterface;

/**
 * Feature determined by if a feature flag is enabled.
 */
class FeatureFlagFeature extends Feature
{
    public function __construct(string $featureID, private string $featureFlag)
    {
        parent::__construct($featureID);
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return FeatureFlagHelper::featureEnabled($this->featureFlag);
    }
}
