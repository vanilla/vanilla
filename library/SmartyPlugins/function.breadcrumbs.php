<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
