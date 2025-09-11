<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Feature;

use Garden\Container\Container;
use Vanilla\Dashboard\Feature\AutomationRulesFeature;
use Vanilla\Dashboard\Feature\InterestsAndSuggestedContentFeature;
use Vanilla\Dashboard\Models\InterestModel;
use Vanilla\InjectableInterface;

/**
 * Service class to hold feature registrations.
 */
class FeatureService
{
    /**
     * @var array<Feature|class-string<Feature>>
     */
    private array $features = [];

    public function __construct(private Container $container)
    {
        // Apply simple config based/core features
        $this->addFeatureFlagFeature("AI Suggested Answers", "AISuggestions");
        $this->addConfigFeature("Email Digest", "Garden.Digest.Enabled");
        $this->addFeatureFlagFeature("Custom Home Pages", "customLayout.home");
        $this->addFeatureFlagFeature("Custom Post List Pages", "customLayout.discussionList");
        $this->addFeatureFlagFeature("Custom Category Pages", "customLayout.categoryList");
        $this->addFeatureFlagFeature("Custom Knowledge Base Pages", "customLayout.knowledgeBase");
        $this->addFeatureFlagFeature("Custom Post Pages", "customLayout.post");
        $this->addFeatureFlagFeature("Custom Event Pages", "customLayout.event");
        $this->addFeature(AutomationRulesFeature::class);
        $this->addFeature(InterestsAndSuggestedContentFeature::class);
    }

    public function addFeature(Feature|string $feature): void
    {
        $this->features[] = $feature;
    }

    /**
     * Get a list of enabled features.
     *
     * @return string[]
     */
    public function getEnabledFeatureIDs(): array
    {
        $result = [];
        foreach ($this->features as $feature) {
            if (is_string($feature)) {
                $feature = $this->container->get($feature);
            } elseif ($feature instanceof InjectableInterface) {
                $this->container->call([$feature, "setDependencies"]);
            }

            if ($feature->isEnabled()) {
                $result[] = $feature->featureID;
            }
        }
        return $result;
    }

    /**
     * Add a configuration based feature.
     *
     * @param string $featureID
     * @param string $configKey
     */
    public function addConfigFeature(string $featureID, string $configKey): void
    {
        $feature = new ConfigFeature($featureID, $configKey);
        $this->addFeature($feature);
    }

    /**
     * Add a feature flag based feature.
     *
     * @param string $featureID
     * @param string $featureFlag
     * @return void
     */
    public function addFeatureFlagFeature(string $featureID, string $featureFlag): void
    {
        $feature = new FeatureFlagFeature($featureID, $featureFlag);
        $this->addFeature($feature);
    }
}
