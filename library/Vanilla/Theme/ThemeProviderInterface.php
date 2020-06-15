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

    /**
     * Whether or not the provider handles themes of a certain type.
     *
     * @param string|int $themeID
     * @return bool
     */
    public function handlesThemeID($themeID): bool;

    /**
     * Return all themes available.
     *
     * @return Theme[]
     */
    public function getAllThemes(): array;

    /**
     * Returns type of theme with all assets
     *
     * @param string|int $themeKey Theme key or id
     * @param array $args Arguments list.
     * @return Theme
     */
    public function getTheme($themeKey, array $args = []): Theme;

    /**
     * Get list of theme revisions
     *
     * @param string|int $themeKey
     * @return Theme[]
     */
    public function getThemeRevisions($themeKey): array;

    /**
     * Set current theme.
     *
     * @param int|string $themeID Theme ID to set current.
     * @return Theme
     */
    public function setCurrentTheme($themeID): Theme;

    /**
     * Set preview theme.
     * (pseudo current theme for current session user only)
     *
     * @param int|string $themeID Theme ID to set current.
     * @param int $revisionID Theme revision ID.
     * @return Theme
     */
    public function setPreviewTheme($themeID, int $revisionID = null): Theme;

    /**
     * Get master (parent) theme key.
     *
     * @param string|int $themeKey Theme key or id
     * @throws NotFoundException Throws an exception when theme is not found.
     * @return string
     */
    public function getMasterThemeKey($themeKey): string;

    /**
     * Check if a theme exists.
     *
     * @param string|int $themeKey
     * @return bool
     */
    public function themeExists($themeKey): bool;
}
