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
 * The Configuration class can be used to load configuration arrays from files,
 * retrieve settings from the arrays, assign new values to the arrays, and save
 * the arrays back to the files.
 *
 * Usage:
 * <code>
 *  $Configuration->LoadFromFile($Name, $FileName); // Loads the configuration array $GroupName['Setting'] = $Value;
 *  $Setting = $Configuration->Get('Setting');
 *  $Configuration->Set('Setting2', 'Value');
 *  $Configuration->Set('Setting3', $Object);
 *  $Configuration->Set('Setting4', $Array);
 * </code>
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_Configuration extends Gdn_Pluggable {
   
   public $NotFound = 'NOT_FOUND';
   
   /**
    * Holds the associative array of configuration data.
    * ie. <code>$this->_Data['Group0']['Group1']['ConfigurationName'] = 'Value';</code>
    *
    * @var array
    */
   protected $Data = array();
   
   /**
    * Configuration Source List
    * 
    * This is an associative array of Gdn_ConfigurationSource objects indexed
    * by their respective types and source URIs.
    * 
    * E.g:
    *   file:/path/to/config/file.php => ...
    *   string:tagname => ...
    * 
    * @var array
    */
   protected $Sources = array();
   
   /**
    * Dynamic (writable) config source
    * 
    * This is the configuration source that is written to when saves or removes
    * are occuring.
    * 
    * @var Gdn_ConfigurationSource
    */
   protected $Dynamic = NULL;
   
   /**
    * Use caching to load and save configs?
    * 
    * @var boolean
    */
   protected $UseCaching = FALSE;
   const CONFIG_FILE_CACHE_KEY = 'garden.config.%s';
   
   public function __construct() {
      parent::__construct();
   }
   
   public function ClearSaveData() {
      throw new Exception('DEPRECATED');
   }
   
   /**
    * Use caching when loading/saving configs
    * 
    * @param boolean $Caching Whether to use caching
    * @return boolean
    */
   public function Caching($Caching = NULL) {
      if (!is_null($Caching))
         $this->UseCaching = (bool)$Caching;
      return $this->UseCaching;
   }
   
   /**
    * Clear cache entry for this config file
    * 
    * @param type $ConfigFile
    * @return void
    */
   public function ClearCache($ConfigFile) {
      $FileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $ConfigFile);
      if (Gdn::Cache()->Type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::Cache()->ActiveEnabled()) {
         Gdn::Cache()->Remove($FileKey,array(
             Gdn_Cache::FEATURE_NOPREFIX => TRUE
         ));
      }
   }
   
   /**
    * Finds the data at a given position and returns a reference to it.
    *
    * @param string $Name The name of the configuration using dot notation.
    * @param boolean $Create Whether or not to create the data if it isn't there already.
    * @return mixed A reference to the configuration data node.
    */
   public function &Find($Name, $Create = TRUE) {
      $Array = &$this->Data;
      
      if($Name == '')
         return $Array;
      
      $Keys = explode('.', $Name);
      $KeyCount = count($Keys);
      for($i = 0; $i < $KeyCount; ++$i) {
         $Key = $Keys[$i];
         
         if(!array_key_exists($Key, $Array)) {
            if($Create) {
               if($i == $KeyCount - 1)
                  $Array[$Key] = NULL;
               else
                  $Array[$Key] = array();
            } else {
               $Array = &$this->NotFound;
               break;
            }
         }
         $Array = &$Array[$Key];
      }
      return $Array;
   }

   public static function Format($Data, $Options = array()) {
      if (is_string($Options))
         $Options = array('VariableName' => $Options);

      $Defaults = array(
         'VariableName' => 'Configuration',
         'WrapPHP'      => TRUE,
         'ByLine'       => TRUE
      );
      $Options = array_merge($Defaults, $Options);
      $VariableName = GetValue('VariableName', $Options);
      $WrapPHP = GetValue('WrapPHP', $Options, TRUE);
      $ByLine = GetValue('ByLine', $Options, FALSE);
      
      $FirstLine = '';
      $Lines = array();
      if ($WrapPHP)
         $FirstLine .= "<?php ";
      $FirstLine .= "if (!defined('APPLICATION')) exit();";
      $Lines[] = $FirstLine;
      
      if (!is_array($Data))
         return $Lines[0];

      $LastKey = FALSE;
      foreach ($Data as $Key => $Value) {
         if($LastKey != $Key && is_array($Value)) {
            $Lines[] = '';
            $Lines[] = '// '.$Key;
            $LastKey = $Key;
         }

         $Prefix = '$'.$VariableName."['".addslashes($Key)."']";
         FormatArrayAssignment($Lines, $Prefix, $Value);
      }

      if ($ByLine) {
         $Session = Gdn::Session();
         $User = $Session->UserID > 0 && is_object($Session->User) ? $Session->User->Name : 'Unknown';
         $Lines[] = '';
         $Lines[] = '// Last edited by '.$User.' (' . RemoteIp() . ')' . Gdn_Format::ToDateTime();
      }
      
      $Result = implode(PHP_EOL, $Lines);
      return $Result;
   }

   /**
    * Gets a setting from the configuration array. Returns $DefaultValue if the value isn't found.
    *
    * @param string $Name The name of the configuration setting to get. If the setting is contained
    * within an associative array, use dot denomination to get the setting. ie.
    * <code>$this->Get('Database.Host')</code> would retrieve <code>$Configuration[$Group]['Database']['Host']</code>.
    * @param mixed $DefaultValue If the parameter is not found in the group, this value will be returned.
    * @return mixed The configuration value.
    */
   public function Get($Name, $DefaultValue = FALSE) {
      $Path = explode('.', $Name);
      
      $Value = $this->Data;
      $Count = count($Path);
      for ($i = 0; $i < $Count; ++$i) {
         if (is_array($Value) && array_key_exists($Path[$i], $Value)) {
            $Value = $Value[$Path[$i]];
         } else {
            return $DefaultValue;
         }
      }
      
      if (is_string($Value))
         $Result = Gdn_Format::Unserialize($Value);
      else
         $Result = $Value;
         
      return $Result;
   }
   
   /**
    * Get a reference to the internal ConfigurationSource for a given type and ID
    * 
    * @param string $Type 'file' or 'string'
    * @param string $Identifier filename or string tag
    * @return ConfigurationSource
    */
   public function GetSource($Type, $Identifier) {
      $SourceTag = "{$Type}:{$Identifier}";
      if (!array_key_exists($SourceTag, $this->Sources)) return FALSE;
      
      return $this->Sources[$SourceTag];
   }

   /**
    * Assigns a setting to the configuration array.
    *
    * @param string $Name The name of the configuration setting to assign. If the setting is
    *   contained within an associative array, use dot denomination to get the
    *   setting. ie. $this->Set('Database.Host', $Value) would set
    *   $Configuration[$Group]['Database']['Host'] = $Value
    * @param mixed $Value The value of the configuration setting.
    * @param boolean $Overwrite If the setting already exists, should it's value be overwritten? Defaults to true.
    * @param boolean $AddToSave Whether or not to queue the value up for the next call to Gdn_Config::Save().
    */
   public function Set($Name, $Value, $Overwrite = TRUE, $Save = TRUE) {
      // Make sure the config settings are in the right format
      if (!is_array($this->Data))
         $this->Data = array();

      if (!is_array($Name)) {
         $Name = array(
            $Name => $Value
         );
      } else {
         $Overwrite = $Value;
      }
      
      $Data = $Name;
      foreach ($Data as $Name => $Value) {

         $KeyParts = explode('.', $Name);
         $KeyCount = count($KeyParts);
         $Settings = &$this->Data;

         for ($i = 0; $i < $KeyCount; ++$i) {
            $Key = $KeyParts[$i];
            
            if (!is_array($Settings)) $Settings = array();
            $KeyExists = array_key_exists($Key, $Settings);
   
            if ($i == $KeyCount - 1) {   
               // If we are on the last iteration of the key, then set the value.
               if ($KeyExists === FALSE || $Overwrite === TRUE) {
                  $Settings[$Key] = Gdn_Format::Serialize($Value);
               }
            } else {
               // Build the array as we loop over the key. Doucement.
               if ($KeyExists === FALSE)
                  $Settings[$Key] = array();
               
               // Advance the pointer
               $Settings = &$Settings[$Key];
            }
         }
      }
      
      if ($Save) {
         $this->Dynamic->Set($Name, $Value, $Overwrite);
      }
   }

   /**
    * Removes the specified key from the specified group (if it exists).
    * Returns FALSE if the key is not found for removal, TRUE otherwise.
    *
    * @param string $Name The name of the configuration setting with dot notation.
    * @return boolean Wether or not the key was found.
    * @todo This method may have to be recursive to remove empty arrays.
    */
   public function Remove($Name, $Save = TRUE) {
      // Make sure the config settings are in the right format
      if (!is_array($this->Data))
         return FALSE;
      
      $Found = FALSE;
      $KeyParts = explode('.', $Name);
      $KeyPartsCount = count($KeyParts);
      $Settings = &$this->Data;
      
      for ($i = 0; $i < $KeyPartsCount; ++$i) {
         
         $Key = $KeyParts[$i];
         
         // Key will always be in here if it is anywhere at all
         if (array_key_exists($Key, $Settings)) {
            if ($i == ($KeyPartsCount - 1)) {
               // We are at the setting, so unset it.
               $Found = TRUE;
               unset($Settings[$Key]);
            } else {
               // Advance the pointer
               $Settings =& $Settings[$Key];
            }
         } else {
            $Found = FALSE;
            break;
         }
      }
      
      if ($Save && $this->Dynamic)
         $this->Dynamic->Remove($Name);
      
      return $Found;
   }

   /**
    * Loads an array of settings from a file into the object with the specified name;
    *
    * @param string $File A string containing the path to a file that contains the settings array.
    * @param string $Name The name of the variable and initial group settings.
    *   Note: When $Name is 'Configuration' then the data will be set to the root of the config.
    * @param boolean $Dynamic Optional, whether to treat this as the request's "dynamic" config, and
    *   to save config changes here. These settings will also be re-applied later when "OverlayDynamic" 
    *   is called after all defaults are loaded.
    * @return boolean
    */
   public function Load($File, $Name = 'Configuration', $Dynamic = FALSE) {
      $ConfigurationSource = Gdn_ConfigurationSource::FromFile($this, $File, $Name);
      if (!$ConfigurationSource) return FALSE;
      $SourceTag = "file:{$File}";
      $this->Sources[$SourceTag] = $ConfigurationSource;
      
      self::MergeConfig($this->Data, $ConfigurationSource->Export());
      if ($Dynamic)
         $this->Dynamic = $ConfigurationSource;
   }
   
   /**
    * Loads settings from a string into the object with the specified name;
    *
    * This string should be a textual representation of a PHP config array, ready
    * to be eval()'d.
    * 
    * @param string $String A string containing the php settings array.
    * @param string $Name The name of the variable and initial group settings.
    *   Note: When $Name is 'Configuration' then the data will be set to the root of the config.
    * @param boolean $Dynamic Optional, whether to treat this as the request's "dynamic" config, and
    *   to save config changes here. These settings will also be re-applied later when "OverlayDynamic" 
    *   is called after all defaults are loaded.
    * @return boolean
    */
   public function LoadString($String, $Tag, $Name = 'Configuration', $Dynamic = TRUE) {
      $ConfigurationSource = Gdn_ConfigurationSource::FromString($this, $String, $Tag, $Name);
      $SourceTag = "string:{$Tag}";
      $this->Sources[$SourceTag] = $ConfigurationSource;
      
      self::MergeConfig($this->Data, $ConfigurationSource->Export());
      if ($Dynamic)
         $this->Dynamic = $ConfigurationSource;
   }

   /**
    * Loads an array of settings into the object with the specified group name.
    *
    * @param string $Name The name of this group of configuration settings.
    * <b>Note</b>: When $Name is 'Configuration' then the data will be set to the root of the config.
    * @param array $Settings The array of settings being loaded.
    * @param boolean $Overwrite A boolean value indicating if the loaded settings should overwrite the
    * existing settings in $Group.
    * @return boolean
    */
   public function LoadArray($Name, $Settings, $Overwrite = FALSE) {
      if (!is_array($this->Data))
         $this->Data = array();
      
      if ($Name == 'Configuration')
         $Name == '';
         
      // Find the spot to insert the settings.
      $Loc = &$this->Find($Name, TRUE);
      
      if (is_null($Loc) || $Overwrite === TRUE) {
         $Loc = $Settings;
         return TRUE;
      } else {
         return FALSE;
      }
   }

   /**
    * Load and parse a file based config
    * 
    * DO NOT USE, THIS IS RUBBISH
    * 
    * @deprecated
    * @param type $Path
    * @param type $Options
    * @return array 
    */
   public static function LoadFile($Path, $Options = array()) {
      if (is_string($Options))
         $Options = array('VariableName' => $Options);

      $Defaults = array('VariableName' => 'Configuration');
      $Options = array_merge($Defaults, $Options);
      $VariableName = $Options['VariableName'];

      $$VariableName = array();
      if (file_exists($Path)) {
         require $Path;
      }
      return $$VariableName;
   }
   
   protected static function MergeConfig(&$Data, &$Loaded) {
      foreach ($Loaded as $Key => $Value) {
         if (!array_key_exists($Key, $Data)) {
            $Data[$Key] = $Value;
         } elseif(is_array($Data[$Key]) && is_array($Value)) {
            self::MergeConfig($Data[$Key], $Value);
         } else {
            $Data[$Key] = $Value;
         }
      }
   }

   /**
    * Re-apply the settings from the current dynamic config source
    * 
    * It may be necessary to load some default configs after loading the client
    * config. These defaults may be overridden in the client's initial config,
    * so that will need to be re-applied once the correct defaults are included.
    * 
    * This method does that
    */
   public function OverlayDynamic() {
      if ($this->Dynamic instanceof Gdn_ConfigurationSource)
         self::MergeConfig($this->Data, $this->Dynamic->Export());
   }
   
   /**
    * Remove key (or keys) from config
    * 
    * @param string|array $Name Key name, or assoc array of keys to unset
    * @param bool|array $Options Bool = whether to save or not. Assoc array =
    *   Save => Whether to save or not
    */
   public function RemoveFromConfig($Name, $Options = array()) {
      $Save = $Options === FALSE ? FALSE : GetValue('Save', $Options, TRUE);
      
      if (!is_array($Name))
         $Name = array($Name);
      
      // Remove specified entries
      foreach ($Name as $k)
         $this->Remove($k, $Save);
   }
   
   /**
    * Saves all settings in $Group to $File.
    *
    * @param string $File The full path to the file where the Settings should be saved.
    * @param string $Group The name of the settings group to be saved to the $File.
    * @return boolean
    */
   public function Save($File = NULL, $Group = NULL) {
      
      // Plain calls to Gdn::Config()->Save() simply save the dynamic config and return
      if (is_null($File))
         return $this->Dynamic->Save();
      
      // ... otherwise we're trying to extract some of the config for some reason
      if ($File == '')
         trigger_error(ErrorMessage('You must specify a file path to be saved.', 'Configuration', 'Save'), E_USER_ERROR);

      if (!is_writable($File))
         throw new Exception(sprintf(T("Unable to write to config file '%s' when saving."),$File));

      if (empty($Group))
         $Group = 'Configuration';
         
      $Data = &$this->Data;
      ksort($Data);
      
      // Check for the case when the configuration is the group.
      if (is_array($Data) && count($Data) == 1 && array_key_exists($Group, $Data))
         $Data = $Data[$Group];

      // Do a sanity check on the config save.
      if ($File == PATH_CONF.'/config.php') {
         if (!isset($Data['Database'])) {
            if ($Pm = Gdn::PluginManager()) {
               $Pm->EventArguments['Data'] = $Data;
               $Pm->EventArguments['Backtrace'] = debug_backtrace();
               $Pm->FireEvent('ConfigError');
            }
            return FALSE;
         }
      }

      $NewLines = array();
      $NewLines[] = "<?php if (!defined('APPLICATION')) exit();";
      $LastName = '';
      foreach ($Data as $Name => $Value) {
         // Write a newline to seperate sections.
         if ($LastName != $Name && is_array($Value)) {
            $NewLines[] = '';
            $NewLines[] = '// '.$Name;
         }
         
         $Line = "\${$Group}['{$Name}']";
         FormatArrayAssignment($NewLines, $Line, $Value);
      }
      
      // Record who made the change and when
      if (is_array($NewLines)) {
         $Session = Gdn::Session();
         $User = $Session->UserID > 0 && is_object($Session->User) ? $Session->User->Name : 'Unknown';
         $NewLines[] = '';
         $NewLines[] = '// Last edited by '.$User.' (' . RemoteIp() . ') ' . Gdn_Format::ToDateTime();
      }

      $FileContents = FALSE;
      if ($NewLines !== FALSE)
         $FileContents = implode("\n", $NewLines);

      if ($FileContents === FALSE)
         trigger_error(ErrorMessage('Failed to define configuration file contents.', 'Configuration', 'Save'), E_USER_ERROR);

      $FileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $File);
      if ($this->Caching() && Gdn::Cache()->Type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::Cache()->ActiveEnabled())
         $CachedConfigData = Gdn::Cache()->Store($FileKey, $Data, array(
             Gdn_Cache::FEATURE_NOPREFIX => TRUE
         ));

      // Infrastructure deployment. Use old method.
      $TmpFile = tempnam(PATH_CONF, 'config');
      $Result = FALSE;
      if (file_put_contents($TmpFile, $FileContents) !== FALSE) {
         chmod($TmpFile, 0775);
         $Result = rename($TmpFile, $File);
      }

      if ($Result && function_exists('apc_delete_file')) {
         // This fixes a bug with some configurations of apc.
         @apc_delete_file($File);
      }
      
      return $Result;
   }

   public static function SaveFile($Path, $Data, $Options = array()) {
      throw new Exception("DEPRECATED");
      
      if (is_string($Options))
         $Options = array('VariableName' => $Options);

      // Load the current data.
      $Loaded = self::LoadFile($Path, $Options);

      // Merge the save data in.
      self::MergeConfig($Data, $Loaded);

      // Save the data back out to the path.
      $Contents = self::Format($Data, $Options);
      
      $TmpFile = tempnam(PATH_CONF, 'config');
      $Result = FALSE;
      if (file_put_contents($TmpFile, $Contents) !== FALSE) {
         chmod($TmpFile, 0775);
         $Result = rename($TmpFile, $Path);
      }
      
      return $Result;
   }
   
   public function SaveToConfig($Name, $Value = '', $Options = array()) {
      $Save = $Options === FALSE ? FALSE : GetValue('Save', $Options, TRUE);
      $RemoveEmpty = GetValue('RemoveEmpty', $Options);
      
      if (!is_array($Name))
         $Name = array($Name => $Value);
      
      // Apply changes one by one
      $Result = TRUE;
      foreach ($Name as $k => $v) {
         if (!$v && $RemoveEmpty) {
            $this->Remove($k);
         } else {
            $Result = $Result & $this->Set($k, $v, TRUE, $Save);
         }
      }
      
      return $Result;
   }
   
   public function Shutdown() {
      foreach ($this->Sources as $Source)
         $Source->Shutdown();
   }
   
   public function __destruct() {
      $this->Shutdown();
   }
   
}

/**
 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
 * keys to arrays rather than overwriting the value in the first array with the duplicate
 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
 * this happens (documented behavior):
 * 
 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('org value', 'new value'));
 * 
 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
 * Matching keys' values in the second array overwrite those in the first array, as is the
 * case with array_merge, i.e.:
 * 
 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('new value'));
 * 
 * Parameters are passed by reference, though only for performance reasons. They're not
 * altered by this function.
 * 
 * @param array $array1
 * @param mixed $array2
 * @return array
 * @author daniel@danielsmedegaardbuus.dk
 */
function &ArrayMergeRecursiveDistinct(array &$array1, &$array2 = null)
{
  $merged = $array1;
  
  if (is_array($array2))
    foreach ($array2 as $key => $val)
      if (is_array($array2[$key]))
        $merged[$key] = is_array($merged[$key]) ? ArrayMergeRecursiveDistinct($merged[$key], $array2[$key]) : $array2[$key];
      else
        $merged[$key] = $val;
  
  return $merged;
}

class Gdn_ConfigurationSource extends Gdn_Pluggable {
   
   /**
    * Top level configuration object to reference
    * @var Gdn_Configuration
    */
   protected $Configuration;
   
   /**
    * Type of source (e.g. file or string)
    * @var string
    */
   protected $Type;
   
   /**
    * Name of source (e.g. filename, or string source tag)
    * @var string
    */
   protected $Source;
   
   /**
    * Group name for this config source (e.g. Configuration)
    * @var string
    */
   protected $Group;
   
   /**
    * Settings as they were when loaded (to facilitate logging config change diffs)
    * @var array
    */
   protected $Initial;
   
   /**
    * Current array of live config settings
    * @var array
    */
   protected $Settings;
   
   /**
    * Whether this config source has been modified since loading
    * @var boolean
    */
   protected $Dirty;
   
   public function __construct($Configuration, $Type, $Source, $Group, $Settings) {
      parent::__construct();
      
      $this->Configuration = $Configuration;
      
      $this->Type = $Type;
      $this->Source = $Source;
      $this->Group = $Group;
      $this->Initial = $Settings;
      $this->Settings = $Settings;
      $this->Dirty = FALSE;
   }
   
   public function Identify() {
      return __METHOD__.":{$this->Type}:{$this->Source}:".(int)$this->Dirty;
   }
   
   /**
    * Source Loaders
    */
   
   /**
    * Load config fata from a file
    * 
    * @param Gdn_Configuration $Parent Parent config object
    * @param string $File Path to config file to load
    * @param string $Name Optional setting name
    * @return Gdn_ConfigurationSource
    */
   public static function FromFile($Parent, $File, $Name = 'Configuration') {
      $LoadedFromCache = FALSE; $UseCache = FALSE;
      if ($Parent && $Parent->Caching()) {
         $FileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $File);
         if (Gdn::Cache()->Type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::Cache()->ActiveEnabled()) {
            $UseCache = TRUE;
            $CachedConfigData = Gdn::Cache()->Get($FileKey,array(
                Gdn_Cache::FEATURE_NOPREFIX => TRUE
            ));
            $LoadedFromCache = ($CachedConfigData !== Gdn_Cache::CACHEOP_FAILURE);
         }
      }
      
      // If we're not loading config from cache, check that the file exists
      if (!$LoadedFromCache && !file_exists($File))
         return FALSE;
         
      // Define the variable properly.
      $$Name = NULL;
      
      // If we're not loading config from cache, directly include the conf file
      if ($LoadedFromCache)
         $$Name = $CachedConfigData;
      
      if (is_null($$Name) || !is_array($$Name)) {
         $LoadedFromCache = FALSE;
         // Include the file.
         require($File);
      }
      
      // Make sure the config variable is here and is an array.
      if (!is_array($$Name))
         $$Name = array();
      
      // We're caching, using the cache, and this data was not loaded from cache.
      // Write it there now.
      if ($Parent && $Parent->Caching() && $UseCache && !$LoadedFromCache) {
         Gdn::Cache()->Store($FileKey, $$Name, array(
             Gdn_Cache::FEATURE_NOPREFIX => TRUE
         ));
      }
      
      return new Gdn_ConfigurationSource($Parent, 'file', $File, $Name, $$Name);
   }
   
   /**
    * Load config data from a string
    * 
    * @param Gdn_Configuration $Parent Parent config object
    * @param string $String Config data string
    * @param string $Tag Internal friendly name
    * @param string $Name Optional setting name
    * @return Gdn_ConfigurationSource 
    */
   public static function FromString($Parent, $String, $Tag, $Name = 'Configuration') {
      // Define the variable properly.
      $$Name = NULL;
      
      // Parse the string
      $String = str_replace(array('<?php','<?','?>'), '', $String);
      $Parsed = eval($String);
      if ($Parsed === FALSE)
         throw new Exception('Could not parse config string.');
      
      // Make sure the config variable is here and is an array.
      if (is_null($$Name) || !is_array($$Name))
         $$Name = array();
      
      return new Gdn_ConfigurationSource($Parent, 'string', $Tag, $Name, $$Name);
   }
   
   public function ToFile($File) {
      $this->Type = 'file';
      $this->Source = $File;
      $this->Dirty = TRUE;
   }
   
   public function Export() {
      if ($this->Group == 'Configuration')
         return $this->Settings;
      
      return array($this->Group => $this->Settings);
   }
   
   /**
    * Removes the specified key from the config (if it exists).
    * Returns FALSE if the key is not found for removal, TRUE otherwise.
    *
    * @param string $Name The name of the configuration setting with dot notation.
    * @return boolean Whether or not the key was found.
    */
   public function Remove($Name) {
      // Make sure this source' config settings are in the right format
      if (!is_array($this->Settings))
         $this->Settings = array();
      
      $Found = FALSE;
      $KeyParts = explode('.', $Name);
      $KeyPartsCount = count($KeyParts);
      $Settings = &$this->Settings;
      
      for ($i = 0; $i < $KeyPartsCount; ++$i) {
         
         $Key = $KeyParts[$i];
         
         // Key will always be in here if it is anywhere at all
         if (array_key_exists($Key, $Settings)) {
            if ($i == ($KeyPartsCount - 1)) {
               // We are at the setting, so unset it.
               $Found = TRUE;
               unset($Settings[$Key]);
               $this->Dirty = TRUE;
            } else {
               // Advance the pointer
               $Settings =& $Settings[$Key];
            }
         } else {
            $Found = FALSE;
            break;
         }
      }
      
      return $Found;
   }
   
   public function Set($Name, $Value = NULL, $Overwrite = TRUE) {
      // Make sure this source' config settings are in the right format
      if (!is_array($this->Settings))
         $this->Settings = array();
      
      if (!is_array($Name)) {
         $Name = array(
            $Name => $Value
         );
      } else {
         $Overwrite = $Value;
      }
      
      $Data = $Name;
      foreach ($Data as $Name => $Value) {

         $KeyParts = explode('.', $Name);
         $KeyCount = count($KeyParts);
         $Settings = &$this->Settings;

         for ($i = 0; $i < $KeyCount; ++$i) {
            $Key = $KeyParts[$i];
            
            if (!is_array($Settings)) $Settings = array();
            $KeyExists = array_key_exists($Key, $Settings);
   
            if ($i == $KeyCount - 1) {   
               // If we are on the last iteration of the key, then set the value.
               if ($KeyExists === FALSE || $Overwrite === TRUE) {
                  $Settings[$Key] = Gdn_Format::Serialize($Value);
                  $this->Dirty = TRUE;
               }
            } else {
               // Build the array as we loop over the key. Doucement.
               if ($KeyExists === FALSE)
                  $Settings[$Key] = array();
               
               // Advance the pointer
               $Settings = &$Settings[$Key];
            }
         }
      }
   }
   
   public function Save() {
      if (!$this->Dirty) return;
      
      $this->EventArguments['ConfigDirty'] = &$this->Dirty;
      $this->EventArguments['ConfigNoSave'] = FALSE;
      $this->EventArguments['ConfigType'] = $this->Type;
      $this->EventArguments['ConfigSource'] = $this->Source;
      $this->EventArguments['ConfigData'] = $this->Settings;
      $this->FireEvent('BeforeSave');
      
      if ($this->EventArguments['ConfigNoSave']) return NULL;
      
      switch ($this->Type) {
         case 'file':
            if (empty($this->Source))
               trigger_error(ErrorMessage('You must specify a file path to be saved.', 'Configuration', 'Save'), E_USER_ERROR);

            if (!is_writable($this->Source))
               throw new Exception(sprintf(T("Unable to write to config file '%s' when saving."), $this->Source));

            $Group = $this->Group;
            $Data = &$this->Settings;
            ksort($Data);

            // Check for the case when the configuration is the group.
            if (is_array($Data) && count($Data) == 1 && array_key_exists($Group, $Data))
               $Data = $Data[$Group];

            // Do a sanity check on the config save.
            if ($this->Source == PATH_CONF.'/config.php') {
               
               // Log root config changes
               try {
                  $LogData = $this->Initial;
                  $LogData['_New'] = $this->Settings;
                  LogModel::Insert('Edit', 'Configuration', $LogData);
               } catch (Exception $Ex){}
               
               if (!isset($Data['Database'])) {
                  if ($Pm = Gdn::PluginManager()) {
                     $Pm->EventArguments['Data'] = $Data;
                     $Pm->EventArguments['Backtrace'] = debug_backtrace();
                     $Pm->FireEvent('ConfigError');
                  }
                  return FALSE;
               }
            }
            
            // Write config data to string format, ready for saving
            $FileContents = Gdn_Configuration::Format($Data, array(
               'VariableName'    => $Group,
               'WrapPHP'         => TRUE,
               'ByLine'          => TRUE
            ));

            if ($FileContents === FALSE)
               trigger_error(ErrorMessage('Failed to define configuration file contents.', 'Configuration', 'Save'), E_USER_ERROR);

            // Save to cache if we're into that sort of thing
            $FileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $this->Source);
            if ($this->Configuration && $this->Configuration->Caching() && Gdn::Cache()->Type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::Cache()->ActiveEnabled())
               $CachedConfigData = Gdn::Cache()->Store($FileKey, $Data, array(
                   Gdn_Cache::FEATURE_NOPREFIX => TRUE
               ));

            $TmpFile = tempnam(PATH_CONF, 'config');
            $Result = FALSE;
            if (file_put_contents($TmpFile, $FileContents) !== FALSE) {
               chmod($TmpFile, 0775);
               $Result = rename($TmpFile, $this->Source);
            }

            if ($Result && function_exists('apc_delete_file')) {
               // This fixes a bug with some configurations of apc.
               @apc_delete_file($this->Source);
            }

            $this->Dirty = FALSE;
            return $Result;
            break;
            
         case 'string':
            /**
             * How would these even save? String config data must be handled by 
             * an event hook, if at all.
             */
            $this->Dirty = FALSE;
            return TRUE;
            break;
      }
   }
   
   public function Shutdown() {
      if ($this->Dirty)
         $this->Save();
   }
   
}