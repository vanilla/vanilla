<?php if (!defined('APPLICATION')) exit();
/*
*/

/**
 * Garden.Modules
 */

/**
 * Varies functions related to Settings
 */
class SettingsModule extends Module {
   
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
               
               $IsRemovable = is_writable(PATH_APPLICATIONS . DS . $ApplicationFolder);
            }
         break;
         case self::TYPE_PLUGIN:
            $PluginManager = Gdn::Factory('PluginManager');
            
            if ($IsRemovable = !array_key_exists($Name, $PluginManager->EnabledPlugins)) {
               $PluginInfo   = ArrayValue($Name, $PluginManager->AvailablePlugins(), FALSE);
               $PluginFolder = ArrayValue('Folder', $PluginInfo, FALSE);
               
               $IsRemovable = is_writable(PATH_PLUGINS . DS . $PluginFolder);
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