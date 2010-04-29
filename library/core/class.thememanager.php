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
 * Garden.Core
 */

/**
 * Manages available themes, enabling and disabling them.
 */
class Gdn_ThemeManager {
   
   /**
    * An array of available themes. Never access this directly, instead
    * use $this->AvailableThemes();
    */
   private $_AvailableThemes = NULL;
   
   /**
    * Looks through the themes directory for valid themes and returns them as
    * an associative array of "Theme Name" => "Theme Info Array". It also adds
    * a "Folder" definition to the Theme Info Array for each.
    */
   public function AvailableThemes() {
      if (!is_array($this->_AvailableThemes)) {
         $ThemeInfo = array();
         $ThemeFolders = Gdn_FileSystem::Folders(PATH_THEMES);
         $ThemeAboutFiles = Gdn_FileSystem::FindAll(PATH_THEMES, 'about.php', $ThemeFolders);
         // Include them all right here and fill the theme info array
         $ThemeCount = is_array($ThemeAboutFiles) ? count($ThemeAboutFiles) : 0;
         for ($i = 0; $i < $ThemeCount; ++$i) {
            include($ThemeAboutFiles[$i]);
            
            // Define the folder name for the newly added item
            foreach ($ThemeInfo as $ThemeName => $Info) {
               if (array_key_exists('Folder', $ThemeInfo[$ThemeName]) === FALSE) {
                  $Folder = substr($ThemeAboutFiles[$i], strlen(PATH_THEMES));
                  if (substr($Folder, 0, 1) == DS)
                     $Folder = substr($Folder, 1);
                     
                  $Folder = substr($Folder, 0, strpos($Folder, DS));
                  $ThemeInfo[$ThemeName]['Folder'] = $Folder;
               }
            }
         }
         $this->_AvailableThemes = $ThemeInfo;
      }

      return $this->_AvailableThemes;
   }
   
   public function EnabledTheme() {
      $ThemeFolder = Gdn::Config('Garden.Theme', 'default');
      return $ThemeFolder;
   }
   
   public function EnabledThemeInfo() {
      $AvailableThemes = $this->AvailableThemes();
      $ThemeFolder = $this->EnabledTheme();
      foreach ($AvailableThemes as $ThemeName => $ThemeInfo) {
         if (ArrayValue('Folder', $ThemeInfo, '') == $ThemeFolder)
            return array($ThemeName => $ThemeInfo);
      }
      return array();
   }
   
   public function EnableTheme($ThemeName) {
      // 1. Make sure that the theme's requirements are met
      $ApplicationManager = new Gdn_ApplicationManager();
      $EnabledApplications = $ApplicationManager->EnabledApplications();
      $AvailableThemes = $this->AvailableThemes();
      $NewThemeInfo = ArrayValue($ThemeName, $AvailableThemes, array());
      $RequiredApplications = ArrayValue('RequiredApplications', $NewThemeInfo, FALSE);
      CheckRequirements($ThemeName, $RequiredApplications, $EnabledApplications, 'application'); // Applications

      // 5. Set the theme
      $ThemeFolder = ArrayValue('Folder', $NewThemeInfo, '');
      if ($ThemeFolder == '') {
         throw new Exception(T('The theme folder was not properly defined.'));
      } else {
         SaveToConfig('Garden.Theme', $ThemeFolder);
      }
      
      return TRUE;
   }
}