<?php
/**
 * Returns category follow toggle
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 * @return string
 */

function smarty_function_follow_button() {
    $controller = Gdn::controller();
    $controller->fetchViewLocation('helper_functions', 'categories', false, false);
    $categoryID = $controller->Category->CategoryID;

    return followButton($categoryID);
}
