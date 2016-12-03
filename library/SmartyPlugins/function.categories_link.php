<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 *
 *
 * @param array $Params
 * @param object $Smarty
 * @return string
 */
function smarty_function_categories_link($Params, &$Smarty) {
    $Wrap = val('wrap', $Params, 'li');
    return Gdn_Theme::link('categories',
        val('text', $Params, t('Categories')),
        val('format', $Params, wrap('<a href="%url" class="%class">%text</a>', $Wrap)));
}
