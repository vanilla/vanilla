<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Vanilla\Theme\Asset\ThemeAsset;

/**
 * Interface for providing variables on a theme.
 */
interface ThemeProviderWriteInterface extends ThemeProviderInterface {

    /**
     * Create new theme.
     *
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return Theme
     */
    public function postTheme(array $body): Theme;

    /**
     * Update theme name by ID.
     *
     * @param string|int $themeID Theme ID
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return Theme
     */
    public function patchTheme($themeID, array $body): Theme;

    /**
     * Delete theme by ID.
     *
     * @param string|int $themeID Theme ID
     */
    public function deleteTheme($themeID);

    /**
     * DELETE theme asset.
     *
     * @param string|int $themeKey The unique theme key or ID.
     * @param string $assetName Unique asset name (ex: header, footer, fonts, styles). Extension optional.
     */
    public function deleteAsset($themeKey, string $assetName);

    /**
     * Set theme asset (replace existing or create new if asset does not exist).
     *
     * @param string|int $themeID Theme ID.
     * @param string $assetKey Unique asset name (ex: header, footer, fonts, styles). Extension optional.
     * @param string $content Content for asset.
     *
     * @return ThemeAsset The update asset.
     */
    public function setAsset($themeID, string $assetKey, string $content): ThemeAsset;

    /**
     * Sparse update/set theme asset (update existing or create new if asset does not exist).
     *
     * @param string|int $themeID The unique theme ID.
     * @param string $assetKey Asset key.
     *        Note: only 'variables.json' allowed at the moment
     * @param string $data Data content for asset.
     *        Note: Expect json encoded associative array of values to update.
     *
     * @return ThemeAsset
     */
    public function sparseUpdateAsset($themeID, string $assetKey, string $data): ThemeAsset;
}
