<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 *
 *
 * @param array $params The parameters passed into the function.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return boolean
 */
function smarty_function_has_data_driven_title_bar($params, &$smarty) {
    return Gdn::config("Feature.NewFlyouts.Enabled", false);
}
