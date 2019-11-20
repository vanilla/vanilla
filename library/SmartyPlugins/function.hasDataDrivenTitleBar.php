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
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_function_has_data_driven_title_bar($params, &$smarty) {
    return Gdn::config("Feature.NewFlyouts.Enabled", false);
}
