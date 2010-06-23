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
 * Varies functions related to Settings
 */
class SettingsModule extends Gdn_Module {
   
   const TYPE_APPLICATION = 'application';
   const TYPE_PLUGIN      = 'plugin';
   const TYPE_THEME       = 'theme';
   
   /**
    * Is the application/plugin/theme removable?
    *
    * @param string $Type self::TYPE_APPLICATION or self::TYPE_PLUGIN or self::TYPE_THEME
    * @param string $Name 
    * @return boolean
    */
   public static function IsRemovable($Type, $Name) {
      
      switch ($Type) {
         case self::TYPE_APPLICATION:
            $ApplicationManager = Gdn::Factory('ApplicationManager');
            
            if ($IsRemovable = !array_key_exists($Name, $ApplicationManager->EnabledApplications())) {
               $ApplicationInfo   = ArrayValue($Name, $ApplicationManager->AvailableApplications(), array());
               $ApplicationFolder = ArrayValue('Folder', $ApplicationInfo, '');
               
               $IsRemovable = IsWritable(PATH_APPLICATIONS . DS . $ApplicationFolder);
            }
         break;
         case self::TYPE_PLUGIN:
            if ($IsRemovable = !array_key_exists($Name, Gdn::PluginManager()->EnabledPlugins)) {
               $PluginInfo   = ArrayValue($Name, Gdn::PluginManager()->AvailablePlugins(), FALSE);
               $PluginFolder = ArrayValue('Folder', $PluginInfo, FALSE);
               
               $IsRemovable = IsWritable(PATH_PLUGINS . DS . $PluginFolder);
            }
         break;
         case self::TYPE_THEME:
            // TODO
            $IsRemovable = FALSE;
         break;
      }
      
      return $IsRemovable;
   }

}