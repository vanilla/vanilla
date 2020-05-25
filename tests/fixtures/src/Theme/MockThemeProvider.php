<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Theme;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Addon;
use Vanilla\Theme\Asset\JsonThemeAsset;
use Vanilla\Theme\Asset\ThemeAsset;
use Vanilla\Theme\Theme;
use Vanilla\Theme\ThemeAssetFactory;
use Vanilla\Theme\ThemeProviderInterface;
use Vanilla\Theme\ThemeProviderWriteInterface;

/**
 * Mock provider for tests.
 */
class MockThemeProvider implements ThemeProviderInterface {

    /** @var Theme[] */
    private $themesByID = [];

    ///
    /// ThemeProviderInterface
    ///

    /**
     * @inheritdoc
     */
    public function handlesThemeID($themeID): bool {
        return stringBeginsWith($themeID, 'mock-');
    }

    /**
     * @inheritdoc
     */
    public function getAllThemes(): array {
        return array_values($this->themesByID);
    }

    /**
     * @inheritdoc
     */
    public function getTheme($themeKey, array $args = []): Theme {
        return $this->themesByID[$themeKey];
    }

    /**
     * @inheritdoc
     */
    public function getThemeRevisions($themeKey): array {
        return [$this->themesByID[$themeKey]];
    }

    /**
     * @inheritdoc
     */
    public function setCurrentTheme($themeID): Theme {
        throw new \Exception('Not Implemented');
    }

    /**
     * @inheritdoc
     */
    public function setPreviewTheme($themeID, int $revisionID = null): Theme {
        throw new \Exception('Not Implemented');
    }

    /**
     * @inheritdoc
     */
    public function getMasterThemeKey($themeKey): string {
        $theme = $this->getTheme($themeKey);
        return $theme->getParentTheme() ?? $theme->getThemeID();
    }

    /**
     * @inheritdoc
     */
    public function themeExists($themeKey): bool {
        return array_key_exists($themeKey, $this->themesByID);
    }

    ///
    /// Utilities
    ///

    /**
     * Add a mock theme to the provider.
     *
     * @param array $body
     * @param Addon $addon
     * @return Theme
     */
    public function addTheme(array $body, Addon $addon): Theme {
        $themeID = $body['themeID'] ?? count($this->themesByID);
        $body = array_replace_recursive([
            'themeID' => (string) $themeID,
            'name' => 'A Mock theme',
            'type' => 'mock',
            'assets' => [
                'variables' => new JsonThemeAsset('{}', ''),
            ],
        ], $body);

        $theme = new Theme($body);
        $theme->setAddon($addon);
        $this->themesByID[$themeID] = $theme;
        return $theme;
    }
}
