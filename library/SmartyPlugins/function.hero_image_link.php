<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 1.0
 */

use Vanilla\Dashboard\Models\BannerImageModel;

/**
 * Get the hero image for the page.
 *
 * @param array $params The parameters passed into the smarty function when it is created.
 * @param Gdn_Smarty $smarty The smarty instance
 *
 * @return string
 * @deprecated 4.0
 */
function smarty_function_hero_image_link($params, &$smarty) {
    return BannerImageModel::getCurrentBannerImageLink();
}
