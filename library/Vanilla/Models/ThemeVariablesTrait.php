<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Theme\VariablesProviderInterface;

/**
 * Trait ThemeVarialblesTrait
 */
trait ThemeVariablesTrait {
    /** @var VariablesProviderInterface[] */
    private $variableProviders = [];

    /**
     * @inheritdoc
     */
    public function setVariableProviders(array $variableProviders = []) {
        if (empty($this->variableProviders)) {
            $this->variableProviders = $variableProviders;
        }
    }

    /**
     * Add Addons variables to theme variables
     *
     * @param string $assetContent Variables json theme asset string
     * @return string
     */
    public function addAddonVariables(string $assetContent): string {
        // Allow addons to add their own variable overrides. Should be moved into the model when the asset generation is refactored.
        $additionalVariables = [];
        foreach ($this->variableProviders as $variableProvider) {
            $additionalVariables = array_replace_recursive($variableProvider->getVariables(), $additionalVariables);
        }

        if ($additionalVariables) {
            $variables = json_decode($assetContent, true) ?? [];

            $variables = array_replace_recursive($additionalVariables, $variables);
            $assetContent = json_encode($variables);
        }
        return $assetContent;
    }
}
