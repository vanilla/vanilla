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
 * The Locale class is used to load, define, change, and render translations
 * for different locales. It is a singleton class.
 *
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

/**
 * The Locale class is used to load, define, change, and render translations
 * for different locales. It is a singleton class.
 *
 * This class can be used through the Gdn object.
 * <b>Usage</b>:
 * <code>
 * $Locale = Gdn::Locale()
 * $String = T('Some Code', 'Default Text');
 * </code>
 *
 * @see Gdn::Locale()
 * @see T()
 */
class Gdn_Locale extends Gdn_Pluggable {


   /**
    * Holds the associative array of language definitions.
    * ie. $this->_Definition['Code'] = 'Definition';
    *
    * @var array
    */
   protected $_Definition = array();


   /**
    * The name of the currently loaded Locale
    *
    * @var string
    */
   public $_Locale = '';
   
   public function __construct($LocaleName, $ApplicationWhiteList, $PluginWhiteList, $ForceRemapping = FALSE) {
      $this->Set($LocaleName, $ApplicationWhiteList, $PluginWhiteList, $ForceRemapping);
      parent::__construct();
   }

   public function Refresh() {
      $LocalName = $this->Current();

      $ApplicationManager = Gdn::Factory('ApplicationManager');
      $ApplicationWhiteList = $ApplicationManager->EnabledApplicationFolders();

      $PluginWhiteList = Gdn::PluginManager()->EnabledPluginFolders();

      $ForceRemapping = TRUE;

      $this->Set($LocalName, $ApplicationWhiteList, $PluginWhiteList, $ForceRemapping);
   }

   public function SaveTranslations($Translations, $LocaleName = FALSE) {
      if (!$LocaleName)
         $LocaleName = $this->_Locale;

      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Path = PATH_CONF . DS . "locale-$LocaleName.php";
      $Config->Load($Path, 'Save', 'Definition');

      foreach ($Translations as $k => $v) {
         $Config->Set('Definition.'.$k, $v);
      }
      $this->SetTranslation($Translations);
      return $Config->Save($Path, 'Definition');
   }

   /**
    * Defines and loads the locale.
    *
    * @param string $LocaleName The name of the locale to load. Locale definitions are kept in each
    * application's locale folder. For example:
    *  /dashboard/locale/$LocaleName.php
    *  /vanilla/locale/$LocaleName.php
    * @param array $ApplicationWhiteList An array of application folders that are safe to examine for locale
    *  definitions.
    * @param array $PluginWhiteList An array of plugin folders that are safe to examine for locale
    *  definitions.
    * @param bool $ForceRemapping For speed purposes, the application folders are crawled for locale
    *  sources. Once sources are found, they are saved in the
    *  cache/locale_mapppings.php file. If ForceRemapping is true, this file will
    *  be ignored and the folders will be recrawled and the mapping file will be
    *  re-generated. You can also simply delete the file and it will
    *  automatically force a remapping.
    */
   public function Set($LocaleName, $ApplicationWhiteList, $PluginWhiteList, $ForceRemapping = FALSE) {
      $SafeLocaleName = preg_replace('/([^\w\d_-])/', '', $LocaleName); // Removes everything from the string except letters, numbers, dashes, and underscores
      $LocaleSources = array();
      
      if(!is_array($ApplicationWhiteList)) $ApplicationWhiteList = array();
      if(!is_array($PluginWhiteList)) $PluginWhiteList = array();
      
      Gdn_LibraryMap::PrepareCache('locale', NULL, 'tree');
      $LocaleSources = Gdn_LibraryMap::GetCache('locale',$SafeLocaleName);
      if ($ForceRemapping === TRUE || !Gdn_LibraryMap::CacheReady('locale') || $LocaleSources === NULL) {
         $LocaleSources = array();
         // Get application-based locale definition files
         $ApplicationLocaleSources = Gdn_FileSystem::FindAll(PATH_APPLICATIONS, CombinePaths(array('locale', $LocaleName.'.php')), $ApplicationWhiteList);
         if ($ApplicationLocaleSources !== FALSE)
            $LocaleSources = array_merge($LocaleSources, $ApplicationLocaleSources);

         $ApplicationLocaleSources = Gdn_FileSystem::FindAll(PATH_APPLICATIONS, CombinePaths(array('locale', $LocaleName, 'definitions.php')), $ApplicationWhiteList);
         if ($ApplicationLocaleSources !== FALSE)
            $LocaleSources = array_merge($LocaleSources, $ApplicationLocaleSources);

         // Get plugin-based locale definition files
         $PluginLocaleSources = Gdn_FileSystem::FindAll(PATH_PLUGINS, CombinePaths(array('locale', $LocaleName.'.php')), $PluginWhiteList);
         if ($PluginLocaleSources !== FALSE)
            $LocaleSources = array_merge($LocaleSources, $PluginLocaleSources);
            
         $PluginLocaleSources = Gdn_FileSystem::FindAll(PATH_PLUGINS, CombinePaths(array('locale', $LocaleName, 'definitions.php')), $PluginWhiteList);
         if ($PluginLocaleSources !== FALSE)
            $LocaleSources = array_merge($LocaleSources, $PluginLocaleSources);

         // Get theme-based locale definition files.
         $Theme = C('Garden.Theme');
         if($Theme) {
            $ThemeLocalePath = PATH_THEMES."/$Theme/locale/$LocaleName.php";
            if(file_exists($ThemeLocalePath))
               $LocaleSources[] = $ThemeLocalePath;
         }

         // Get locale-based locale definition files.
         $EnabledLocales = C('EnabledLocales');
         if (is_array($EnabledLocales)) {
            foreach ($EnabledLocales as $Key => $Locale) {
               if ($Locale != $LocaleName)
                  continue; // skip locales that aren't in effect.

               // Grab all of the files in the locale's folder.
               $Paths = SafeGlob(PATH_ROOT."/locales/$Key/*.php");
               if (is_array($Paths)) {
                  foreach($Paths as $Path) {
                     $LocaleSources[] = $Path;
                  }
               }
            }
         }
            
         // Save the mappings
         $FileContents = array();
         $Count = count($LocaleSources);
         for($i = 0; $i < $Count; ++$i) {
            $FileContents[$SafeLocaleName][] = Gdn_Format::ArrayValueForPhp($LocaleSources[$i]);
         }

         // Look for a global locale.
         $ConfigLocale = PATH_CONF.'/locale.php';
         if (file_exists($ConfigLocale))
            $FileContents[$SafeLocaleName][] = $ConfigLocale;

         // Look for a config locale that is locale-specific.
         $ConfigLocale = PATH_CONF."/locale-$LocaleName.php";
         if (file_exists($ConfigLocale))
            $FileContents[$SafeLocaleName][] = $ConfigLocale;
         
         Gdn_LibraryMap::PrepareCache('locale', $FileContents);
      }

      // Set up defaults
      $Definition = array();
      $this->_Definition = array();

      // Now set the locale name and import all of the sources.
      $this->_Locale = $LocaleName;
      $LocaleSources = Gdn_LibraryMap::GetCache('locale', $SafeLocaleName);
      if (is_null($SafeLocaleName))
         $LocaleSources = array();

      $ConfLocaleOverride = PATH_CONF . DS . 'locale.php';
      $Count = count($LocaleSources);
      for($i = 0; $i < $Count; ++$i) {
         if ($ConfLocaleOverride != $LocaleSources[$i] && file_exists($LocaleSources[$i])) // Don't double include the conf override file... and make sure it comes last
            include($LocaleSources[$i]);
      }

      // Also load any custom defined definitions from the conf directory
      if (file_exists($ConfLocaleOverride))
         include($ConfLocaleOverride);

      // All of the included files should have contained
      // $Definition['Code'] = 'Definition'; assignments. The overwrote each
      // other in the order they were included. Now assign the $Definition array
      // to the local private _Definition property.
      $this->_Definition = $Definition;
   }

   /**
    * Load a locale definition file.
    *
    * @param string $Path The path to the locale.
    */
   public function Load($Path) {
      $Definition = &$this->_Definition;
      include($Path);
   }

   /**
    * Assigns a translation code.
    *
    * @param mixed $Code The code to provide a translation for, or an array of code => translation
    * values to be set.
    * @param string $Translation The definition associated with the specified code. If $Code is an array
    *  of definitions, this value will not be used.
    */
   public function SetTranslation($Code, $Translation = '') {
      if (!is_array($Code))
         $Code = array($Code => $Translation);

      foreach($Code as $k => $v) {
         $this->_Definition[$k] = $v;
      }
   }

   /**
    * Translates a code into the selected locale's definition.
    *
    * @param string $Code The code related to the language-specific definition.
    *   Codes thst begin with an '@' symbol are treated as literals and not translated.
    * @param string $Default The default value to be displayed if the translation code is not found.
    * @return string
    */
   public function Translate($Code, $Default = FALSE) {
      if ($Default === FALSE)
         $Default = $Code;

      // Codes that begin with @ are considered literals.
      if(substr_compare('@', $Code, 0, 1) == 0)
         return substr($Code, 1);

      if (array_key_exists($Code, $this->_Definition)) {
         return $this->_Definition[$Code];
      } else {
         return $Default;
      }
   }

   /**
    *  Clears out the currently loaded locale settings.
    */
   public function Unload() {
      $this->_Definition = array();
   }

   /**
    * Returns the name of the currently loaded locale.
    *
    * @return boolean
    */
   public function Current() {
      if ($this->_Locale == '')
         return FALSE;
      else
         return $this->_Locale;
   }

   /**
    * Search the garden/locale folder for other locale sources that are
    * available. Returns an array of locale names.
    *
    * @return array
    */
   public function GetAvailableLocaleSources() {
      return Gdn_FileSystem::Folders(PATH_APPLICATIONS . DS . 'dashboard' . DS . 'locale');
   }
}
