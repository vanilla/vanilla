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
 * Writes the site logo to the page.
 *
 * @param array The parameters passed into the function.
 * @param Smarty The smarty object rendering the template.
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

   $Result = Gdn_Theme::Logo($Options);
	return $Result;
}