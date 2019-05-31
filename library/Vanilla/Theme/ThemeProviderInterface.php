<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

 /**
  * Interface for providing variables on a theme.
  */
interface ThemeProviderInterface {
    const TYPE_INT = true;
    const TYPE_STRING = false;


    /**
     * Returns type of themeKey used for this provider
     *
     * @return bool When theme key is numeric return TRUE if alphanumeric FALSE
     */
    public function themeKeyType(): bool;

    /**
     * Returns type of theme with all assets
     *
     * @param string|int $themeKey Theme key or id
     * @return array
     */
    public function getThemeWithAssets($themeKey): array;

    /**
     * Get asset data
     *
     * @param string|int $themeKey Theme key or id
     * @param string $assetKey Asset key
     * @return string Asset data (content)
     */
    public function getAssetData($themeKey, string $assetKey): string;

    /**
     * DELETE theme asset.
     *
     * @param string|int $themeKey The unique theme key or ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     */
    public function deleteAsset($themeKey, string $assetKey);

    /**
     * Create new theme.
     *
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return array
     */
    public function postTheme(array $body): array;

    /**
     * Update theme name by ID.
     *
     * @param int $themeID Theme ID
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return array
     */
    public function patchTheme(int $themeID, array $body): array;

    /**
     * Delete theme by ID.
     *
     * @param int $themeID Theme ID
     */
    public function deleteTheme(int $themeID);

    /**
     * Set current theme.
     *
     * @param int $themeID Theme ID to set current.
     * @return array
     */
    public function setCurrent(int $themeID): array;

    /**
     * Get "current" theme.
     *
     * @return array
     */
    public function getCurrent(): ?array;

    /**
     * Set theme asset (update existing or create new if asset does not exist).
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     * @param string $data Data content for asset.
     *
     * @return array
     */
    public function setAsset(int $themeID, string $assetKey, string $data): array;

    /**
     * Set variable providers
     *
     * @param array $variableProviders
     * @return mixed
     */
    public function setVariableProviders(array $variableProviders = []);
}
