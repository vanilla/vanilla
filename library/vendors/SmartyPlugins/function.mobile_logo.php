<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Writes the site logo to the page.
 *
 * @param array The parameters passed into the function. This currently takes no parameters.
 * @param Smarty The smarty object rendering the template.
 * @return The url.
 */
function smarty_function_mobile_logo($Params, &$Smarty) {
   $Result = Gdn_Theme::MobileLogo('Title');
	return $Result;
}