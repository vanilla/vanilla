<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

define('ADDON_TYPE_PLUGIN', 1);
define('ADDON_TYPE_THEME', 2);
define('ADDON_TYPE_LOCALE', 4);
define('ADDON_TYPE_APPLICATION',  5);
define('ADDON_TYPE_CORE', 10);

class UpdateModel extends Gdn_Model {
   public $AddonSiteUrl = 'http://vanilla.local';

   protected static function _AddAddon($Addon, &$Addons) {
      $Slug = strtolower($Addon['AddonKey']).'-'.strtolower($Addon['AddonType']);
      $Addons[$Slug] = $Addon;
   }

   /**
    * Check an addon's file to extract the addon information out of it.
    *
    * @param string $Path The path to the file.
    * @param bool $Fix Whether or not to fix files that have been zipped incorrectly.
    * @return array An array of addon information.
    */
   public static function AnalyzeAddon($Path, $Fix = FALSE, $ThrowError = TRUE) {
      $Result = array();

      // Extract the zip file so we can make sure it has appropriate information.
      $Zip = NULL;

      if (class_exists('ZipArchive', FALSE)) {
         $Zip = new ZipArchive();
         $ZipOpened = $Zip->open($Path);
         if ($ZipOpened !== TRUE)
            $Zip = NULL;
      }

      if (!$Zip) {
         require_once PATH_LIBRARY."/vendors/pclzip/class.pclzipadapter.php";
         $Zip = new PclZipAdapter();
         $ZipOpened = $Zip->open($Path);
      }

      if ($ZipOpened !== TRUE) {
         if ($ThrowError) {
            $Errors = array(ZIPARCHIVE::ER_EXISTS => 'ER_EXISTS', ZIPARCHIVE::ER_INCONS => 'ER_INCONS', ZIPARCHIVE::ER_INVAL => 'ER_INVAL',
                ZIPARCHIVE::ER_MEMORY => 'ER_MEMORY', ZIPARCHIVE::ER_NOENT => 'ER_NOENT', ZIPARCHIVE::ER_NOZIP => 'ER_NOZIP',
               ZIPARCHIVE::ER_OPEN => 'ER_OPEN', ZIPARCHIVE::ER_READ => 'ER_READ', ZIPARCHIVE::ER_SEEK => 'ER_SEEK');

            throw new Exception(T('Could not open addon file. Addons must be zip files.').' ('.$Path.' '.GetValue($ZipOpened, $Errors, 'Unknown Error').')'.$Worked, 400);
         }
         return FALSE;
      }

      $Entries = array();
      for ($i = 0; $i < $Zip->numFiles; $i++) {
         $Entries[] = $Zip->statIndex($i);
      }

      // Figure out which system files to delete.
      $Deletes = array();

      foreach ($Entries as $Index => $Entry) {
         $Name = $Entry['name'];
         $Delete = strpos($Name, '__MACOSX') !== FALSE
            | strpos($Name, '.DS_Store') !== FALSE
            | strpos($Name, 'thumbs.db') !== FALSE
            | strpos($Name, '.gitignore') !== FALSE;

         if ($Delete) {
            $Deletes[] = $Entry;
            unset($Entries[$Index]);
         }
      }

      // Get a folder ready for checking the addon.
      $FolderPath = dirname($Path).'/'.basename($Path, '.zip').'/';
      if (file_exists($FolderPath))
         Gdn_FileSystem::RemoveFolder($FolderPath);

      // Figure out what kind of addon this is.
      $Root = '';
      $NewRoot = '';
      $Addon = FALSE;
      foreach ($Entries as $Entry) {
         $Name = '/'.ltrim($Entry['name'], '/');
         $Filename = basename($Name);
         $Folder = substr($Name, 0, -strlen($Filename));
         $NewRoot = '';

         // Check to see if the entry is a plugin file.
         if ($Filename == 'default.php' || StringEndsWith($Filename, '.plugin.php')) {
            if (count(explode('/', $Folder)) > 3) {
               // The file is too deep to be a plugin file.
               continue;
            }

            // This could be a plugin file, but we have to examine its info array.
            $Zip->extractTo($FolderPath, $Entry['name']);
            $FilePath = CombinePaths(array($FolderPath, $Name));
            $Info = self::ParseInfoArray($FilePath, 'PluginInfo');
            Gdn_FileSystem::RemoveFolder(dirname($FilePath));

            if (!is_array($Info) || !count($Info))
               continue;

            // Check to see if the info array conforms to a plugin spec.
            $Key = key($Info);
            $Info = $Info[$Key];
            $Root = trim($Folder, '/');

            $Valid = TRUE;

            // Make sure the key matches the folder name.
            if ($Root && strcasecmp($Root, $Key) != 0) {
               $Result[] = "$Name: The plugin's key is not the same as its folder name.";
               $Valid = FALSE;
            } else {
               $NewRoot = $Root;
            }

            if (!GetValue('Description', $Info)) {
               $Result[] = $Name.': '.sprintf(T('ValidateRequired'), T('Description'));
               $Valid = FALSE;
            }

            if (!GetValue('Version', $Info)) {
               $Result[] = $Name.': '.sprintf(T('ValidateRequired'), T('Version'));
               $Valid = FALSE;
            }

            if ($Valid) {
               // The plugin was confirmed.
               $Addon = array(
                   'AddonKey' => $Key,
                   'AddonTypeID' => ADDON_TYPE_PLUGIN,
                   'Name' => GetValue('Name', $Info) ? $Info['Name'] : $Key,
                   'Description' => $Info['Description'],
                   'Version' => $Info['Version'],
                   'Path' => $Path);
               break;
            }
            continue;
         }

         // Check to see if the entry is an application file.
         if (StringEndsWith($Name, '/settings/about.php')) {
            if (count(explode('/', $Folder)) > 4) {
               $Result[] = "$Name: The application's info array was not in the correct location.";
               // The file is too deep to be a plugin file.
               continue;
            }

            // This could be a plugin file, but we have to examine its info array.
            $Zip->extractTo($FolderPath, $Entry['name']);
            $FilePath = CombinePaths(array($FolderPath, $Name));
            $Info = self::ParseInfoArray($FilePath, 'ApplicationInfo');
            Gdn_FileSystem::RemoveFolder(dirname($FilePath));

            if (!is_array($Info) || !count($Info)) {
               $Result[] = "$Name: The application's info array could not be parsed.";
               continue;
            }

            $Key = key($Info);
            $Info = $Info[$Key];
            $Root = trim(substr($Name, 0, -strlen('/settings/about.php')), '/');
            $Valid = TRUE;

            // Make sure the key matches the folder name.
            if ($Root && strcasecmp($Root, $Key) != 0) {
               $Result[] = "$Name: The application's key is not the same as its folder name.";
               $Valid = FALSE;
            } else {
               $NewRoot = $Root;
            }

            if (!GetValue('Description', $Info)) {
               $Result[] = $Name.': '.sprintf(T('ValidateRequired'), T('Description'));
               $Valid = FALSE;
            }

            if (!GetValue('Version', $Info)) {
               $Result[] = $Name.': '.sprintf(T('ValidateRequired'), T('Version'));
               $Valid = FALSE;
            }

            if ($Valid) {
               // The application was confirmed.
               $Addon = array(
                   'AddonKey' => $Key,
                   'AddonTypeID' => ADDON_TYPE_APPLICATION,
                   'Name' => GetValue('Name', $Info) ? $Info['Name'] : $Key,
                   'Description' => $Info['Description'],
                   'Version' => $Info['Version'],
                   'Path' => $Path);
               break;
            }
            continue;
         }

         // Check to see if the entry is a theme file.
         if (StringEndsWith($Name, '/about.php')) {
            if (count(explode('/', $Folder)) > 3) {
               // The file is too deep to be a plugin file.
               continue;
            }

            // This could be a theme file, but we have to examine its info array.
            $Zip->extractTo($FolderPath, $Entry['name']);
            $FilePath = CombinePaths(array($FolderPath, $Name));
            $Info = self::ParseInfoArray($FilePath, 'ThemeInfo');
            Gdn_FileSystem::RemoveFolder(dirname($FilePath));

            if (!is_array($Info) || !count($Info))
               continue;

            $Key = key($Info);
            $Info = $Info[$Key];
            $Valid = TRUE;

            $Root = trim(substr($Name, 0, -strlen('/about.php')), '/');
            // Make sure the theme is at least one folder deep.
            if (strlen($Root) == 0) {
               $Result[] = $Name.': The theme must be in a folder.';
               $Valid = FALSE;
            }

            if (!GetValue('Description', $Info)) {
               $Result[] = $Name.': '.sprintf(T('ValidateRequired'), T('Description'));
               $Valid = FALSE;
            }

            if (!GetValue('Version', $Info)) {
               $Result[] = $Name.': '.sprintf(T('ValidateRequired'), T('Version'));
               $Valid = FALSE;
            }

            if ($Valid) {
               // The application was confirmed.
               $Addon = array(
                   'AddonKey' => $Key,
                   'AddonTypeID' => ADDON_TYPE_THEME,
                   'Name' => GetValue('Name', $Info) ? $Info['Name'] : $Key,
                   'Description' => $Info['Description'],
                   'Version' => $Info['Version'],
                   'Path' => $Path);
               break;
            }
         }

         if (StringEndsWith($Name, '/definitions.php')) {
            if (count(explode('/', $Folder)) > 3) {
               // The file is too deep to be a plugin file.
               continue;
            }

             // This could be a locale pack, but we have to examine its info array.
            $Zip->extractTo($FolderPath, $Entry['name']);
            $FilePath = CombinePaths(array($FolderPath, $Name));
            $Info = self::ParseInfoArray($FilePath, 'LocaleInfo');
            Gdn_FileSystem::RemoveFolder(dirname($FilePath));

            if (!is_array($Info) || !count($Info))
               continue;

            $Key = key($Info);
            $Info = $Info[$Key];
            $Valid = TRUE;

            $Root = trim(substr($Name, 0, -strlen('/definitions.php')), '/');
            // Make sure the locale is at least one folder deep.
            if ($Root != $Key) {
               $Result[] = $Name.': The locale pack\'s key must be the same as its folder name.';
               $Valid = FALSE;
            }

            if (!GetValue('Locale', $Info)) {
               $Result[] = $Name.': '.sprintf(T('ValidateRequired'), T('Locale'));
               $Valud = FALSE;
            } elseif (strcasecmp($Info['Locale'], $Key) == 0) {
               $Result[] = $Name.': '.T('The locale\'s key cannot be the same as the name of the locale.');
               $Valid = FALSE;
            }

            if (!GetValue('Description', $Info)) {
               $Result[] = $Name.': '.sprintf(T('ValidateRequired'), T('Description'));
               $Valid = FALSE;
            }

            if (!GetValue('Version', $Info)) {
               $Result[] = $Name.': '.sprintf(T('ValidateRequired'), T('Version'));
               $Valid = FALSE;
            }

            if ($Valid) {
               // The locale pack was confirmed.
               $Addon = array(
                   'AddonKey' => $Key,
                   'AddonTypeID' => ADDON_TYPE_LOCALE,
                   'Name' => GetValue('Name', $Info) ? $Info['Name'] : $Key,
                   'Description' => $Info['Description'],
                   'Version' => $Info['Version'],
                   'Path' => $Path);
               break;
            }
         }

         // Check to see if the entry is a core file.
         if (StringEndsWith($Name, '/index.php')) {
            if (count(explode('/', $Folder)) != 3) {
               // The file is too deep to be the core's index.php
               continue;
            }

            // This could be a theme file, but we have to examine its info array.
            $Zip->extractTo($FolderPath, $Entry['name']);
            $FilePath = CombinePaths(array($FolderPath, $Name));

            // Get the version number from the core.
            $Version = self::ParseCoreVersion($FilePath);

            if (!$Version)
               continue;

            // The application was confirmed.
            $Addon = array(
                'AddonKey' => 'vanilla',
                'AddonTypeID' => ADDON_TYPE_CORE,
                'Name' => 'Vanilla',
                'Description' => 'Vanilla is an open-source, standards-compliant, multi-lingual, fully extensible discussion forum for the web. Anyone who has web-space that meets the requirements can download and use Vanilla for free!',
                'Version' => $Version,
                'Path' => $Path);
            $Info = array();
            break;
         }

         // Check to see if the entry is the porter.
         if (StringEndsWith($Name, 'vanilla2export.php')) {
            if (count(explode('/', $Folder)) != 2) {
               continue;
            }

            $Zip->extractTo($FolderPath, $Entry['name']);
            $FilePath = CombinePaths(array($FolderPath, $Name));
            $Version = self::ParseCoreVersion($FilePath, 'VERSION');

            if (!$Version)
               continue;

            $Addon = array(
                'AddonKey' => 'porter',
                'AddonTypeID' => ADDON_TYPE_CORE,
                'Name' => 'Vanilla Porter',
                'Description' => 'Drop this script on your existing site and go to it to export your existing forum data to the Vanilla 2 import format. If you want more information on how to use this application go <a href="http://vanillaforums.com/blog/help-topics/importing-data">here</a>',
                'Version' => $Version,
                'Path' => $Path);
            $Info = array();
            break;
         }
      }

      if ($Addon) {
         // Add the requirements.
         $Requirements = ArrayTranslate($Info, array('RequiredApplications' => 'Applications', 'RequiredPlugins' => 'Plugins', 'RequiredThemes' => 'Themes'));
         foreach ($Requirements as $Type => $Items) {
            if (!is_array($Items))
               unset($Requirements[$Type]);
         }
         $Addon['Requirements'] = serialize($Requirements);

         $Addon['Checked'] = TRUE;


         $UploadsPath = PATH_ROOT.'/uploads/';
         if (StringBeginsWith($Addon['Path'], $UploadsPath)) {
            $Addon['File'] = substr($Addon['Path'], strlen($UploadsPath));
         }
         if ($Fix) {
            // Delete extraneous files.
            foreach ($Deletes as $Delete) {
               $Zip->deleteName($Delete['name']);
            }
         }
      }

      $Zip->close();

      if (file_exists($FolderPath))
         Gdn_FileSystem::RemoveFolder($FolderPath);


      if ($Addon) {
         $Addon['MD5'] = md5_file($Path);
         $Addon['FileSize'] = filesize($Path);
         return $Addon;
      } else {
         if ($ThrowError) {
            $Msg = implode("\n", $Result);
            throw new Exception($Msg, 400);
         } else {
            return FALSE;
         }
      }
   }

   /**
    * Parse the version out of the core's index.php file.
    *
    * @param string $Path The path to the index.php file.
    * @return string|false A string containing the version or false if the file could not be parsed.
    */
   public static function ParseCoreVersion($Path) {
      $fp = fopen($Path, 'rb');
      $Application = FALSE;
      $Version = FALSE;

      while (($Line = fgets($fp)) !== FALSE) {
         if (preg_match("`define\\('(.*?)', '(.*?)'\\);`", $Line, $Matches)) {
            $Name = $Matches[1];
            $Value = $Matches[2];
            switch ($Name) {
               case 'APPLICATION':
                  $Application = $Value;
                  break;
               case 'APPLICATION_VERSION':
                  $Version = $Value;
            }
         }

         if ($Application !== FALSE && $Version !== FALSE)
            break;
      }
      fclose($fp);
      return $Version;
   }

   /**
    * Offers a quick and dirty way of parsing an addon's info array without using eval().
    * @param string $Path The path to the info array.
    * @param string $Variable The name of variable containing the information.
    * @return array|false The info array or false if the file could not be parsed.
    */
   public static function ParseInfoArray($Path, $Variable) {
      $fp = fopen($Path, 'rb');
      $Lines = array();
      $InArray = FALSE;

      // Get all of the lines in the info array.
      while (($Line = fgets($fp)) !== FALSE) {
         // Remove comments from the line.
         $Line = preg_replace('`\s//.*$`', '', $Line);
         if (!$Line)
            continue;

         if (StringBeginsWith(trim($Line), '$'.trim($Variable, '$'))) {
            if (preg_match('`\[\s*[\'"](.+?)[\'"]\s*\]`', $Line, $Matches)) {
               $GlobalKey = $Matches[1];
               $InArray = TRUE;
            }
         } elseif ($InArray && StringEndsWith(trim($Line), ';')) {
            break;
         } elseif ($InArray) {
            $Lines[] = trim($Line);
         }
      }
      fclose($fp);

      if (count($Lines) == 0)
         return FALSE;

      // Parse the name/value information in the arrays.
      $Result = array();
      foreach ($Lines as $Line) {
         // Get the name from the line.
         if (!preg_match('`[\'"](.+?)[\'"]\s*=>`', $Line, $Matches) || !substr($Line, -1) == ',')
            continue;
         $Key = $Matches[1];

         // Strip the key from the line.
         $Line = trim(trim(substr(strstr($Line, '=>'), 2)), ',');

         if (strlen($Line) == 0)
            continue;

         $Value = NULL;
         if (is_numeric($Line))
            $Value = $Line;
         elseif (strcasecmp($Line, 'TRUE') == 0 || strcasecmp($Line, 'FALSE') == 0)
            $Value = $Line;
         elseif (in_array($Line[0], array('"', "'")) && substr($Line, -1) == $Line[0]) {
            $Quote = $Line[0];
            $Value = trim($Line, $Quote);
            $Value = str_replace('\\'.$Quote, $Quote, $Value);
         } elseif (StringBeginsWith($Line, 'array(') && substr($Line, -1) == ')') {
            // Parse the line's array.
            $Line = substr($Line, 6, strlen($Line) - 7);
            $Items = explode(',', $Line);
            $Array = array();
            foreach ($Items as $Item) {
               $SubItems = explode('=>', $Item);
               if (count($SubItems) == 1) {
                  $Array[] = trim(trim($SubItems[0]), '"\'');
               } elseif (count($SubItems) == 2) {
                  $SubKey = trim(trim($SubItems[0]), '"\'');
                  $SubValue = trim(trim($SubItems[1]), '"\'');
                  $Array[$SubKey] = $SubValue;
               }
            }
            $Value = $Array;
         }

         if ($Value != NULL) {
            $Result[$Key] = $Value;
         }
      }
      $Result = array($GlobalKey => $Result);
      return $Result;
   }

   public function CompareAddons($MyAddons, $LatestAddons, $OnlyUpdates = TRUE) {
      $UpdateAddons = false;

      // Join the site addons with my addons.
      foreach ($LatestAddons as $Addon) {
         $Key = GetValue('AddonKey', $Addon);
         $Type = GetValue('Type', $Addon);
         $Slug = strtolower($Key).'-'.strtolower($Type);
         $Version = GetValue('Version', $Addon);
         $FileUrl = GetValue('Url', $Addon);

         if (isset($MyAddons[$Slug])) {
            $MyAddon = $MyAddons[$Slug];

            if (version_compare($Version, GetValue('Version', $MyAddon, '999'), '>')) {
               $MyAddon['NewVersion'] = $Version;
               $MyAddon['NewDownloadUrl'] = $FileUrl;
               $UpdateAddons[$Slug] = $MyAddon;
            }
         } else {
            unset($MyAddons[$Slug]);
         }
      }

      return $UpdateAddons;
   }

   public function GetAddons($Enabled = FALSE) {
      $Addons = array();

      // Get the core.
      self::_AddAddon(array('AddonKey' => 'vanilla', 'AddonType' => 'core', 'Version' => APPLICATION_VERSION, 'Folder' => '/'), $Addons);

      // Get a list of all of the applications.
      $ApplicationManager = new Gdn_ApplicationManager();
      if ($Enabled) {
         $Applications = $ApplicationManager->AvailableApplications();
      } else {
         $Applications = $ApplicationManager->EnabledApplications();
      }

      foreach ($Applications as $Key => $Info) {
         // Exclude core applications.
         if (in_array(strtolower($Key), array('conversations', 'dashboard', 'skeleton', 'vanilla')))
            continue;

         $Addon = array('AddonKey' => $Key, 'AddonType' => 'application', 'Version' => GetValue('Version', $Info, '0.0'), 'Folder' => '/applications/'.GetValue('Folder', $Info, strtolower($Key)));
         self::_AddAddon($Addon, $Addons);
      }

      // Get a list of all of the plugins.
      $PluginManager = Gdn::PluginManager();
      if ($Enabled)
         $Plugins = $PluginManager->EnabledPlugins();
      else
         $Plugins = $PluginManager->AvailablePlugins();

      foreach ($Plugins as $Key => $Info) {
         // Exclude core plugins.
         if (in_array(strtolower($Key), array()))
            continue;

         $Addon = array('AddonKey' => $Key, 'AddonType' => 'plugin', 'Version' => GetValue('Version', $Info, '0.0'), 'Folder' => '/applications/'.GetValue('Folder', $Info, $Key));
         self::_AddAddon($Addon, $Addons);
      }

      // Get a list of all the themes.
      $ThemeManager = new Gdn_ThemeManager();
      if ($Enabled)
         $Themes = $ThemeManager->EnabledThemeInfo(TRUE);
      else
         $Themes = $ThemeManager->AvailableThemes();

      foreach ($Themes as $Key => $Info) {
         // Exclude core themes.
         if (in_array(strtolower($Key), array('default')))
            continue;

         $Addon = array('AddonKey' => $Key, 'AddonType' => 'theme', 'Version' => GetValue('Version', $Info, '0.0'), 'Folder' => '/themes/'.GetValue('Folder', $Info, $Key));
         self::_AddAddon($Addon, $Addons);
      }

      // Get a list of all locales.
      $LocaleModel = new LocaleModel();
      if ($Enabled)
         $Locales = $LocaleModel->EnabledLocalePacks(TRUE);
      else
         $Locales = $LocaleModel->AvailableLocalePacks();

      foreach ($Locales as $Key => $Info) {
         // Exclude core themes.
         if (in_array(strtolower($Key), array('skeleton')))
            continue;

         $Addon = array('AddonKey' => $Key, 'AddonType' => 'locale', 'Version' => GetValue('Version', $Info, '0.0'), 'Folder' => '/locales/'.GetValue('Folder', $Info, $Key));
         self::_AddAddon($Addon, $Addons);
      }

      return $Addons;
   }

   public function GetAddonUpdates($Enabled = FALSE, $OnlyUpdates = TRUE) {
      // Get the addons on this site.
      $MyAddons = $this->GetAddons($Enabled);

      // Build the query for them.
      $Slugs = array_keys($MyAddons);
      array_map('urlencode', $Slugs);
      $SlugsString = implode(',', $Slugs);

      $Url = $this->AddonSiteUrl.'/addon/getlist.json?ids='.$SlugsString;
      $SiteAddons = ProxyRequest($Url);
      $UpdateAddons = array();
      
      if ($SiteAddons) {
         $SiteAddons = GetValue('Addons', json_decode($SiteAddons, TRUE));
         $UpdateAddons = $this->CompareAddons($MyAddons, $SiteAddons);
      }
      return $UpdateAddons;
   }
}