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
     * Add Addons variables to theme variables.
     * Addon provided variables will override the theme variables.
     *
     * @param string $baseAssetContent Variables json theme asset string.
     * @return string The updated asset content.
     */
    public function addAddonVariables(string $baseAssetContent): string {
        // Allow addons to add their own variable overrides. Should be moved into the model when the asset generation is refactored.
        $additionalVariables = [];
        foreach ($this->variableProviders as $variableProvider) {
            $additionalVariables = array_replace_recursive($additionalVariables, $variableProvider->getVariables());
        }

        if ($additionalVariables) {
            $variables = json_decode($baseAssetContent, true) ?? [];

            $variables = array_replace_recursive($variables, $additionalVariables);
            $baseAssetContent = json_encode($variables);
        }
        return $baseAssetContent;
    }
}
