<?php if (!defined('APPLICATION')) exit();

/**
 * The Locale class is used to load, define, change, and render translations
 * for different locales. It is a singleton class.
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Locale extends Gdn_Pluggable {
   
   /**
    * The name of the currently loaded Locale
    * @var string
    */
   public $Locale = '';
   
   /**
    * Holds all locale sources
    * @var Gdn_Configuration
    */
   public $LocaleContainer = NULL;
   
   /**
    * Whether or not to record core translations
    * @var boolean
    */
   public $DeveloperMode = FALSE;
   
   /**
    * Core translations, and untranslated codes
    * @var Gdn_Configuration
    */
   public $DeveloperContainer = NULL;
   
   public $SavedDeveloperCalls = 0;
   
   public function __construct($LocaleName, $ApplicationWhiteList, $PluginWhiteList, $ForceRemapping = FALSE) {
      parent::__construct();
      $this->ClassName = 'Gdn_Locale';
      
      $this->Set($LocaleName, $ApplicationWhiteList, $PluginWhiteList, $ForceRemapping);
   }
   
   /**
    * Reload the locale system 
    */
   public function Refresh() {
      $LocalName = $this->Current();
      
      $ApplicationWhiteList = Gdn::ApplicationManager()->EnabledApplicationFolders();
      $PluginWhiteList = Gdn::PluginManager()->EnabledPluginFolders();

      $ForceRemapping = TRUE;

      $this->Set($LocalName, $ApplicationWhiteList, $PluginWhiteList, $ForceRemapping);
   }

   public function SaveTranslations($Translations, $LocaleName = FALSE) {
      $this->LocaleContainer->Save();
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
      // Get locale sources
      $this->Locale = $LocaleName;
      $LocaleSources = $this->GetLocaleSources($LocaleName, $ApplicationWhiteList, $PluginWhiteList, $ForceRemapping);
      
      $Codeset = C('Garden.LocaleCodeset', 'UTF8');
      $CurrentLocale = str_replace('-', '_', $LocaleName);
      $SetLocale = $CurrentLocale.'.'.$Codeset;
      setlocale(LC_TIME, $SetLocale, $CurrentLocale);
      
      if (!is_array($LocaleSources))
         $LocaleSources = array();
      
      // Create a locale config container
      $this->Unload();
      
      $ConfLocaleOverride = PATH_CONF.'/locale.php';
      $Count = count($LocaleSources);
      for ($i = 0; $i < $Count; ++$i) {
         if ($ConfLocaleOverride != $LocaleSources[$i] && file_exists($LocaleSources[$i])) // Don't double include the conf override file... and make sure it comes last
            $this->Load($LocaleSources[$i], FALSE);
      }

      // Also load any custom defined definitions from the conf directory
      if (file_exists($ConfLocaleOverride))
         $this->Load($ConfLocaleOverride, TRUE);
      
      // Prepare developer mode if needed
      $this->DeveloperMode = C('Garden.Locales.DeveloperMode', FALSE);
      if ($this->DeveloperMode) {
         $this->DeveloperContainer = new Gdn_Configuration();
         $this->DeveloperContainer->Splitting(FALSE);
         $this->DeveloperContainer->Caching(FALSE);
         
         $DeveloperCodeFile = PATH_CACHE."/locale-developer-{$LocaleName}.php";
         if (!file_exists($DeveloperCodeFile))
            touch($DeveloperCodeFile);
         
         $this->DeveloperContainer->Load($DeveloperCodeFile, 'Definition', TRUE);
      }
      
      // Import core (static) translations
      if ($this->DeveloperMode)
         $this->DeveloperContainer->MassImport($this->LocaleContainer->Get('.'));
      
      // Allow hooking custom definitions
      $this->FireEvent('AfterSet');
   }
   
   public function GetLocaleSources($LocaleName, $ApplicationWhiteList, $PluginWhiteList, $ForceRemapping = FALSE) {
      $SafeLocaleName = preg_replace('/([^\w\d_-])/', '', $LocaleName); // Removes everything from the string except letters, numbers, dashes, and underscores
      $LocaleSources = array();
      if (!is_array($ApplicationWhiteList)) $ApplicationWhiteList = array();
      if (!is_array($PluginWhiteList)) $PluginWhiteList = array();
      
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
         if ($Theme) {
            $ThemeLocalePath = PATH_THEMES."/{$Theme}/locale/{$LocaleName}.php";
            if (file_exists($ThemeLocalePath))
               $LocaleSources[] = $ThemeLocalePath;
         }

         // Get locale-based locale definition files.
         $EnabledLocales = C('EnabledLocales');
         if (is_array($EnabledLocales)) {
            foreach ($EnabledLocales as $Key => $Locale) {
               if ($Locale != $LocaleName)
                  continue; // skip locales that aren't in effect.

               // Grab all of the files in the locale's folder.
               $Paths = SafeGlob(PATH_ROOT."/locales/{$Key}/*.php");
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
         for($i = 0; $i < $Count; ++$i)
            $FileContents[$SafeLocaleName][] = Gdn_Format::ArrayValueForPhp($LocaleSources[$i]);

         // Look for a global locale.
         $ConfigLocale = PATH_CONF.'/locale.php';
         if (file_exists($ConfigLocale))
            $FileContents[$SafeLocaleName][] = $ConfigLocale;

         // Look for a config locale that is locale-specific.
         $ConfigLocale = PATH_CONF."/locale-{$LocaleName}.php";
         if (file_exists($ConfigLocale))
            $FileContents[$SafeLocaleName][] = $ConfigLocale;
         
         Gdn_LibraryMap::PrepareCache('locale', $FileContents);
      }
      
      return $LocaleSources;
   }

   /**
    * Load a locale definition file.
    *
    * @param string $Path The path to the locale.
    * @param boolean $Dynamic Whether this locale file should be the dynamic one.
    */
   public function Load($Path, $Dynamic = FALSE) {
      $this->LocaleContainer->Load($Path, 'Definition', $Dynamic);
   }

   /**
    * Assigns a translation code.
    * 
    * These DO NOT PERSIST.
    *
    * @param mixed $Code The code to provide a translation for, or an array of code => translation
    * values to be set.
    * @param string $Translation The definition associated with the specified code. If $Code is an array
    *  of definitions, this value will not be used.
    */
   public function SetTranslation($Code, $Translation = '', $Save = FALSE) {
      if (!is_array($Code))
         $Code = array($Code => $Translation);

      $this->LocaleContainer->SaveToConfig($Code, NULL, $Save);
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
      if (substr_compare('@', $Code, 0, 1) == 0)
         return substr($Code, 1);
      
      $Translation = $this->LocaleContainer->Get($Code, $Default);
      
      // If developer mode is on, and this translation returned the default value,
      // remember it and save it to the developer locale.
      if ($this->DeveloperMode && $Translation == $Default) {
         $DevKnows = $this->DeveloperContainer->Get($Code, FALSE);
         if ($DevKnows === FALSE)
            $this->DeveloperContainer->SaveToConfig($Code, $Default);
      }
      
      return $Translation;
   }

   /**
    *  Clears out the currently loaded locale settings.
    */
   public function Unload() {
      // If we're unloading, don't save first
      if ($this->LocaleContainer instanceof Gdn_Configuration)
         $this->LocaleContainer->AutoSave(FALSE);
      
      $this->LocaleContainer = new Gdn_Configuration();
      $this->LocaleContainer->Splitting(FALSE);
      $this->LocaleContainer->Caching(FALSE);
   }

   /**
    * Returns the name of the currently loaded locale.
    *
    * @return boolean
    */
   public function Current() {
      if ($this->Locale == '')
         return FALSE;
      else
         return $this->Locale;
   }

   /**
    * Search the garden/locale folder for other locale sources that are
    * available. Returns an array of locale names.
    *
    * @return array
    */
   public function GetAvailableLocaleSources() {
      return Gdn_FileSystem::Folders(PATH_APPLICATIONS.'/dashboard/locale');
   }
   
   /**
    * Get all definitions from the loaded locale
    */
   public function GetDefinitions() {
      return $this->LocaleContainer->Get('.');
   }
   
   /**
    * Get all known core
    */
   public function GetDeveloperDefinitions() {
      if (!$this->DeveloperMode) return FALSE;
      
      return $this->DeveloperContainer->Get('.');
   }
   
}
