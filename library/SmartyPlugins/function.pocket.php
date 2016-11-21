<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Writes a pocket to the page
 *
 * @param array $Params The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>name</b>: The name of the pocket.
 * @param Smarty $Smarty The smarty object rendering the template.
 * @return string The pocket string.
 */
function smarty_function_pocket($Params, $Smarty) {
    if (!class_exists('PocketsPlugin')) {
        return '';
    }

    $Name = val('name', $Params);
    unset($Params['name']);

    $Result = PocketsPlugin::pocketString($Name, $Params);

	return $Result;
}
