<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

 use Garden\Web\Exception\NotFoundException;

 /**
  * Interface for providing variables on a theme.
  */
interface ThemeProviderInterface {
    const TYPE_FS = 0;
    const TYPE_DB = 1;

    /**
     * Returns type of themeKey used for this provider
     *
     * @return int One of TYPE_FS or TYPE_DB
     */
    public function themeKeyType(): int;

    /**
     * Return all themes available.
     *
     * @return array
     */
    public function getAllThemes(): array;

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
     * @param int|string $themeID Theme ID to set current.
     * @return array
     */
    public function setCurrent($themeID): array;

    /**
     * Set preview theme.
     * (pseudo current theme for current session user only)
     *
     * @param int|string $themeID Theme ID to set current.
     * @return array
     */
    public function setPreviewTheme($themeID): array;

    /**
     * Get "current" theme.
     * @return array
     */
    public function getCurrent(): ?array;

    /**
     * Get master (parent) theme key.
     *
     * @param strig|int $themeKey Theme key or id
     * @throws NotFoundException Throws an exception when theme is not found.
     * @return string
     */
    public function getMasterThemeKey($themeKey): string;

    /**
     * Get theme name.
     *
     * @param strig|int $themeKey Theme key or id
     * @return string
     */
    public function getName($themeKey): string;

    /**
     * Set theme asset (replace existing or create new if asset does not exist).
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     * @param string $data Data content for asset.
     *
     * @return array
     */
    public function setAsset(int $themeID, string $assetKey, string $data): array;

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
    public function sparseAsset(int $themeID, string $assetKey, string $data): array;
}
