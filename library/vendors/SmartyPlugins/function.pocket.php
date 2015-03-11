<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package Dashboard
 */

/**
 * Writes a pocket to the page
 *
 * @param array The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>name</b>: The name of the pocket.
 * @param Smarty The smarty object rendering the template.
 * @return The pocket string.
 */
function smarty_function_pocket($Params, $Smarty) {
   if (!class_exists('PocketsPlugin'))
      return '';

   $Name = GetValue('name', $Params);
   unset($Params['name']);

   $Result = PocketsPlugin::PocketString($Name, $Params);

	return $Result;
}
