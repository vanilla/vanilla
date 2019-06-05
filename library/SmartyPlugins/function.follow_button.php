<?php
/**
 * Returns category follow toggle button.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 * @return string
 */

/**
 * Follow button.
 *
 * @return string
 */
function smarty_function_follow_button() {
    $followButton = '';
    $controller = Gdn::controller();
    require_once $controller->fetchViewLocation('helper_functions', 'categories', false, false);
    $categoryID = $controller->Category->CategoryID;

    if ($categoryID) {
        $followButton = followButton($categoryID);
    }

    return $followButton;
}
