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
    $controller->fetchViewLocation('helper_functions', 'categories', false, false);
    require_once PATH_APPLICATIONS.'/vanilla/views/categories/helper_functions.php';
    $categoryID = $controller->Category->CategoryID;

    if ($categoryID) {
        $followButton = followButton($categoryID);
    }

    return $followButton;
}
