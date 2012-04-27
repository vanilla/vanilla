<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

function smarty_function_module($Params, &$Smarty) {
   $Name = GetValue('name', $Params);
   unset($Params['name']);
   
   $Result = Gdn_Theme::Module($Name, $Params);
	return $Result;
}