<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
function smarty_function_signinout_link($params, &$smarty) {
    $wrap = val('wrap', $params, 'li');
    return Gdn_Theme::link('signinout',
        val('text', $params, ''),
        val('format', $params, wrap('<a href="%url" rel="nofollow" class="%class">%text</a>', $wrap)));
}
