<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */


/**
 * Render a breadcrumb trail for the user based on the page they are on.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_function_breadcrumbs($params, &$smarty) {
    $breadcrumbs = Gdn::controller()->data('Breadcrumbs');
    if (!is_array($breadcrumbs)) {
        $breadcrumbs = [];
    }

    $options = arrayTranslate($params, ['homeurl' => 'HomeUrl', 'hidelast' => 'HideLast']);

    return Gdn_Theme::breadcrumbs($breadcrumbs, val('homelink', $params, true), $options);
}
