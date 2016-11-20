<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Writes the site logo to the page.
 *
 * @param array $Params The parameters passed into the function.
 * @param Smarty $Smarty The smarty object rendering the template.
 * @return The HTML img tag or site title if no logo is set.
 */
function smarty_function_logo($Params, &$Smarty) {
    $Options = array();

    // Whitelist params to be passed on.
    if (isset($Params['alt'])) {
        $Options['alt'] = $Params['alt'];
    }
    if (isset($Params['class'])) {
        $Options['class']  = $Params['class'];
    }
    if (isset($Params['title'])) {
        $Options['title']  = $Params['title'];
    }
    if (isset($Params['height'])) {
        $Options['height'] = $Params['height'];
    }
    if (isset($Params['width'])) {
        $Options['width']  = $Params['width'];
    }

    $Result = Gdn_Theme::logo($Options);
	return $Result;
}
