<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Vanilla\Theme\Asset\JsonThemeAsset;

/**
 * Simple data class to represent a theme preview.
 */
class ThemePreview implements \JsonSerializable {

    /** @var string|null */
    private $imageUrl = null;

    /** @var array Array of infos with a type and a value. */
    private $info = [];

    /** @var array */
    private $variablePreview;

    /**
     * Set the image URL for the preview.
     *
     * @param string $url
     * @return $this Chaining
     */
    public function setImageUrl(string $url): ThemePreview {
        $this->imageUrl = $url;
        return $this;
    }

    /**
     * Add an info value.
     *
     * @param string $type
     * @param string $label
     * @param string $value
     *
     * @return $this Chaining
     */
    public function addInfo(string $type, string $label, string $value): ThemePreview {
        $this->info[$label] = [
            'type' => $type,
            'value' => $value,
        ];
        return $this;
    }

    /**
     * Add a variable preview.
     *
     * @param JsonThemeAsset $jsonAsset
     * @return ThemePreview
     */
    public function addVariablePreview(JsonThemeAsset $jsonAsset): ThemePreview {
        $this->variablePreview = [];
        $variables = $jsonAsset->getValue();

        $preset = $variables['global']['options']['preset'] ?? null;
        $bg = $variables['global']['mainColors']['bg'] ?? $preset === 'dark' ? "#323639" : "#fff";
        $fg = $variables['global']['mainColors']['fg'] ?? $preset === 'dark' ? '#fff' : '#555a62';
        $primary = $variables['global']['mainColors']['primary'] ?? null;
        $this->variablePreview['globalPrimary'] = $primary;
        $this->variablePreview['globalBg'] = $bg;
        $this->variablePreview['globalFg'] = $fg;
        $this->variablePreview['titleBarBg'] = $variables['titleBar']['colors']['bg'] ?? $primary ?? null;
        $this->variablePreview['titleBarFg'] = $variables['titleBar']['colors']['fg'] ?? null;
        $this->variablePreview['backgroundImage'] = $variables['splash']['outerBackground']['image']
            ?? $variables['banner']['outerBackground']['image']
            ?? null;
        return $this;
    }

    /**
     * @return array
     */
    public function asArray(): array {
        return [
            'info' => $this->info,
            'imageUrl' => $this->imageUrl,
            'variables' => $this->variablePreview,
        ];
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->asArray();
    }
}
