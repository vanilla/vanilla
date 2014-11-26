<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
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
   return file_get_contents(rtrim($Smarty->template_dir, '/').'/'.$Name);
}
