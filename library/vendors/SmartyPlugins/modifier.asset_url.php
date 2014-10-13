<?php if (!defined('APPLICATION')) exit();

/**
 * Converts a string to an asset url.
 *
 * @see T()
 */
function smarty_modifier_asset_url($path, $withDomain = false, $addVersion = false) {
   return Asset($path, $withDomain, $withDomain);
}
