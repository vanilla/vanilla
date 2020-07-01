<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Converts a string to an asset url.
 *
 * @param string $path The path to the asset.
 * @param bool|string $withDomain Whether or not to include the domain.
 * @param bool $addVersion Whether or not to add a version to the resulting asset to help bust the cache.
 * @return string Returns the url to the asset.
 *
 * @see asset()
 */
function smarty_modifier_asset_url($path, $withDomain = false, $addVersion = false) {
    return asset($path, $withDomain, $addVersion);
}
