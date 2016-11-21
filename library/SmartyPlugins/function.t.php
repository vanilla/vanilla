<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Returns the  custom text from a theme.
 *
 * @param array $Params The parameters passed into the function. This currently takes no parameters.
 *  - <b>code</b>: The text code set in the theme's information.
 *  - <b>default</b>: The default text if the user hasn't overridden.
 * @param Smarty $Smarty The smarty object rendering the template.
 * @return The text.
 */
function smarty_function_t($Params, &$Smarty) {
    $Code = val('c', $Params, '');
    $Result = t($Code, val('d', $Params, $Code));
	return $Result;
}
