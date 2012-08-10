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
 * A placeholder for future menu items.
 *
 * @param array The parameters passed into the function. This currently takes no parameters.
 * @param Smarty The smarty object rendering the template.
 * @return
 */
function smarty_function_custom_menu($Params, &$Smarty) {
   $Controller = $Smarty->Controller;
   if (is_object($Menu = GetValue('Menu', $Controller))) {
      $Format = GetValue('format', $Params, Wrap('<a href="%url" class="%class">%text</a>', GetValue('wrap', $Params, 'li')));


      $Result = '';
      foreach ($Menu->Items as $Group) {
         foreach ($Group as $Item) {
            // Make sure the item is a custom item.
            if (GetValueR('Attributes.Standard', $Item))
               continue;

            // Make sure the user has permission for the item.
            if ($Permission = GetValue('Permission', $Item)) {
               if (!Gdn::Session()->CheckPermission($Permission))
                  continue;
            }

            if (($Url = GetValue('Url', $Item)) && ($Text = GetValue('Text', $Item))) {
               $Attributes = GetValue('Attributes', $Item);
               $Result .= Gdn_Theme::Link($Url, $Text, $Format, $Attributes)."\r\n";
            }
         }
      }
      return $Result;
   }
   return '';
}

