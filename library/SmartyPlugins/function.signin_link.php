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
function smarty_function_signin_link($Params, &$Smarty) {
    if (!Gdn::session()->isValid()) {
        $Wrap = val('wrap', $Params, 'li');
        return Gdn_Theme::link(
            'signinout',
            val('text', $Params, ''),
            val('format', $Params, wrap('<a href="%url" rel="nofollow" class="%class">%text</a>', $Wrap)),
            $Params
        );
    }
}
