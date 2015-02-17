<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */


/**
 * Includes a file in template. Handy for adding html files to tpl files
 *
 * @param array The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>name</b>: The name of the file.
 * @param Smarty The smarty object rendering the template.
 * @return The rendered asset.
 */
function smarty_function_include_file($Params, &$Smarty) {
   $Name = ltrim(ArrayValue('name', $Params), '/');
   if (strpos($Name, '..') !== false) {
      return '<!-- Error, moving up directory path not allowed -->';
   }
   if (IsUrl($Name)) {
      return '<!-- Error, urls are not allowed -->';
   }
   $filename = rtrim($Smarty->template_dir, '/').'/'.$Name;
   if (!file_exists($filename)) {
      return '<!-- Error, file does not exist -->';
   }
   return file_get_contents($filename);
}
