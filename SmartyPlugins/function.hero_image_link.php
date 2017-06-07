<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 1.0
 *
 * Get Terms of Service link
 *
 * @return string
 */
function smarty_function_hero_image_link($params, &$smarty) {
    $imageSlug = HeroImagePlugin::getHeroImageSlug();
    $url = $imageSlug ? Gdn_Upload::url($imageSlug) : '';
    return $url;
}
