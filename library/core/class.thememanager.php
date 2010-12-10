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

                  // Add the screenshot.
                  $ScreenshotPath = SafeGlob(PATH_THEMES."/$Folder/screenshot.*", array('gif', 'jpg', 'png'));
                  if (count($ScreenshotPath) > 0) {
                     $ScreenshotPath = $ScreenshotPath[0];
                     $ThemeInfo[$ThemeName]['ScreenshotUrl'] = Asset(str_replace(PATH_ROOT, '', $ScreenshotPath));
                  }
               }
            }
         }
         $this->_AvailableThemes = $ThemeInfo;
      }

      return $this->_AvailableThemes;
   }

   public function CurrentTheme() {
      return C('Garden.Theme', '');
   }
   
   public function EnabledTheme() {
      $ThemeFolder = Gdn::Config('Garden.Theme', 'default');
      return $ThemeFolder;
   }
   
   public function EnabledThemeInfo($ReturnInSourceFormat = FALSE) {
      $AvailableThemes = $this->AvailableThemes();
      $ThemeFolder = $this->EnabledTheme();
      foreach ($AvailableThemes as $ThemeName => $ThemeInfo) {
         if (ArrayValue('Folder', $ThemeInfo, '') == $ThemeFolder) {
            $Info = $ReturnInSourceFormat ? array($ThemeName => $ThemeInfo) : $ThemeInfo;
            // Update the theme info for a format consumable by views.
            if (is_array($Info) & isset($Info['Options'])) {
               $Options =& $Info['Options'];
               if (isset($Options['Styles'])) {
                  foreach ($Options['Styles'] as $Key => $Params) {
                     if (is_string($Params)) {
                        $Options['Styles'][$Key] = array('Basename' => $Params);
                     } elseif (is_array($Params) && isset($Params[0])) {
                        $Params['Basename'] = $Params[0];
                        unset($Params[0]);
                        $Options['Styles'][$Key] = $Params;
                     }
                  }
               }
               if (isset($Options['Text'])) {
                  foreach ($Options['Text'] as $Key => $Params) {
                     if (is_string($Params)) {
                        $Options['Text'][$Key] = array('Type' => $Params);
                     } elseif (is_array($Params) && isset($Params[0])) {
                        $Params['Type'] = $Params[0];
                        unset($Params[0]);
                        $Options['Text'][$Key] = $Params;
                     }
                  }
               }
            }
            return $Info;
         }

      }
      return array();
   }
   
   public function EnableTheme($ThemeName) {
      // Make sure to run the setup
      $this->TestTheme($ThemeName);
      
      // Set the theme
      $ThemeFolder = ArrayValue('Folder', ArrayValue($ThemeName, $this->AvailableThemes(), array()), '');
      if ($ThemeFolder == '') {
         throw new Exception(T('The theme folder was not properly defined.'));
      } else {
         $Options = GetValueR("$ThemeName.Options", $this->AvailableThemes());
         if ($Options) {
            SaveToConfig(array(
               'Garden.Theme' => $ThemeFolder,
               'Garden.ThemeOptions.Name' => GetValueR("$ThemeName.Name", $this->AvailableThemes(), $ThemeFolder)));
         } else {
            SaveToConfig('Garden.Theme', $ThemeFolder);
            RemoveFromConfig('Garden.ThemeOptions');
         }
      }

      // Tell the locale cache to refresh itself.
      $ApplicationManager = new Gdn_ApplicationManager();
      Gdn::Locale()->Refresh();
      return TRUE;
   }
   
   public function TestTheme($ThemeName) {
      // Get some info about the currently enabled theme.
      $EnabledTheme = $this->EnabledThemeInfo();
      $EnabledThemeFolder = GetValue('Folder', $EnabledTheme, '');
      $OldClassName = $EnabledThemeFolder . 'ThemeHooks';
      
      // Make sure that the theme's requirements are met
      $ApplicationManager = new Gdn_ApplicationManager();
      $EnabledApplications = $ApplicationManager->EnabledApplications();
      $AvailableThemes = $this->AvailableThemes();
      $NewThemeInfo = ArrayValue($ThemeName, $AvailableThemes, array());
      $RequiredApplications = ArrayValue('RequiredApplications', $NewThemeInfo, FALSE);
      $ThemeFolder = ArrayValue('Folder', $NewThemeInfo, '');
      CheckRequirements($ThemeName, $RequiredApplications, $EnabledApplications, 'application'); // Applications

      // If there is a hooks file, include it and run the setup method.
      $ClassName = $ThemeFolder . 'ThemeHooks';
      $HooksFile = PATH_THEMES . DS . $ThemeFolder . DS . 'class.' . strtolower($ClassName) . '.php';
      if (file_exists($HooksFile)) {
         include($HooksFile);
         if (class_exists($ClassName)) {
            $ThemeHooks = new $ClassName();
            $ThemeHooks->Setup();
         }
      }

      // If there is a hooks in the old theme, include it and run the ondisable method.
      if (class_exists($OldClassName)) {
         $ThemeHooks = new $OldClassName();
         if (method_exists($ThemeHooks, 'OnDisable')) {
            $ThemeHooks->OnDisable();
         }
      }

      return TRUE;
   }
}