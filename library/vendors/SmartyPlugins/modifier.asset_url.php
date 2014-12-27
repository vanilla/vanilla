<?php if (!defined('APPLICATION')) exit();

/**
 * Converts a string to an asset url.
 *
 * @param string $path The path to the asset.
 * @param bool|string $withDomain Whether or not to include the domain.
 * @param bool $addVersion Whether or not to add a version to the resulting asset to help bust the cache.
 * @return Returns the url to the asset.
 *
 * @see Asset()
 */
function smarty_modifier_asset_url($path, $withDomain = false, $addVersion = false) {
   return Asset($path, $withDomain, $addVersion);
}
