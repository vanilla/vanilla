<?php


namespace VanillaTests\Fixtures;


use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\AddonInterface;
use Vanilla\Contracts\AddonProviderInterface;
use Vanilla\Models\ThemeModel;
use Vanilla\Theme\ThemeProviderInterface;

class MockThemeProvider implements ThemeProviderInterface {

    /**
     * Returns type of themeKey used for this provider
     *
     * @return int One of TYPE_FS or TYPE_DB
     */
    public function themeKeyType(): int {
        // TODO: Implement themeKeyType() method.
    }

    /**
     * Return all themes available.
     *
     * @return array
     */
    public function getAllThemes(): array {
        // TODO: Implement getAllThemes() method.
    }

    /**
     * Returns type of theme with all assets
     *
     * @param string|int $themeKey Theme key or id
     * @return array
     */
    public function getThemeWithAssets($themeKey): array {
        // TODO: Implement getThemeWithAssets() method.
    }

    /**
     * Get asset data
     *
     * @param string|int $themeKey Theme key or id
     * @param string $assetKey Asset key
     * @return string Asset data (content)
     */
    public function getAssetData($themeKey, string $assetKey): string {
        // TODO: Implement getAssetData() method.
    }

    /**
     * DELETE theme asset.
     *
     * @param string|int $themeKey The unique theme key or ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     */
    public function deleteAsset($themeKey, string $assetKey) {
        // TODO: Implement deleteAsset() method.
    }

    /**
     * Create new theme.
     *
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return array
     */
    public function postTheme(array $body): array {
        // TODO: Implement postTheme() method.
    }

    /**
     * Update theme name by ID.
     *
     * @param int $themeID Theme ID
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return array
     */
    public function patchTheme(int $themeID, array $body): array {
        // TODO: Implement patchTheme() method.
    }

    /**
     * Delete theme by ID.
     *
     * @param int $themeID Theme ID
     */
    public function deleteTheme(int $themeID) {
        // TODO: Implement deleteTheme() method.
    }

    /**
     * Set current theme.
     *
     * @param int|string $themeID Theme ID to set current.
     * @return array
     */
    public function setCurrent($themeID): array {
        // TODO: Implement setCurrent() method.
    }

    /**
     * Set preview theme.
     * (pseudo current theme for current session user only)
     *
     * @param int|string $themeID Theme ID to set current.
     * @return array
     */
    public function setPreviewTheme($themeID): array {
        // TODO: Implement setPreviewTheme() method.
    }

    /**
     * Get "current" theme.
     *
     * @return array
     */
    public function getCurrent(): ?array {
        // TODO: Implement getCurrent() method.
    }

    /**
     * Get master (parent) theme key.
     *
     * @param strig|int $themeKey Theme key or id
     * @return string
     * @throws NotFoundException Throws an exception when theme is not found.
     */
    public function getMasterThemeKey($themeKey): string {
        // TODO: Implement getMasterThemeKey() method.
    }

    /**
     * Get theme name.
     *
     * @param strig|int $themeKey Theme key or id
     * @return string
     */
    public function getName($themeKey): string {
        // TODO: Implement getName() method.
    }

    /**
     * Set theme asset (replace existing or create new if asset does not exist).
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     * @param string $data Data content for asset.
     *
     * @return array
     */
    public function setAsset(int $themeID, string $assetKey, string $data): array {
        // TODO: Implement setAsset() method.
    }

    /**
     * Sparse update/set theme asset (update existing or create new if asset does not exist).
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetKey Asset key.
     *        Note: only 'variables.json' allowed at the moment
     * @param string $data Data content for asset.
     *        Note: Expect json encoded associative array of values to update.
     *
     * @return array
     */
    public function sparseAsset(int $themeID, string $assetKey, string $data): array {
        // TODO: Implement sparseAsset() method.
    }

    public function getThemeAddon($themeKey): AddonInterface {
        /** @var MockAddonProvider*/
        $addonManager = new MockAddonProvider();
        $theme = $addonManager->lookupTheme($themeKey);

        return $theme;
    }

}
