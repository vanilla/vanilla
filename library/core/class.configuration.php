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

class Gdn_Configuration {

   /**
    * This is the name of the associative array defined in $Configuration->File
    * that contains the settings to be manipulated. This should be a string
    * that can safely be used as a variable (no spaces, numbers, special chars,
    * etc). Since the settings array can contain multiple configuration arrays,
    * this property holds the name of the one currently being manipulated.
    *
    * @var string
    */
   public $CurrentGroup = '';

   /**
    * Contains the full path to the file that contains the settings array. This
    * is automatically defined when $Configuration->LoadFromFile() is called so
    * it does not need to be redefined when $Configuration->SaveToFile() is
    * called (unless you want to save the settings to a different file).
    *
    * @var string
    */
   private $_File = '';

   /**
    * Holds the associative array of configuration data.
    * ie. <code>$this->_Data['Group0']['Group1']['ConfigurationName'] = 'Value';</code>
    *
    * @var array
    */
   protected $_Data = array();
   
   public $NotFound = 'NOT_FOUND';
   
   /**
    * Holds the data that has been set and should be saved.
    *
    * @var array.
    */
   protected $_SaveData;
   
   
   public function ClearSaveData() {
      $this->_SaveData = array();
   }
   
   /**
    * Finds the data at a given position and returns a reference to it.
    *
    * @param string $Name The name of the configuration using dot notation.
    * @param boolean $Create Whether or not to create the data if it isn't there already.
    * @return mixed A reference to the configuration data node.
    */
   public function &Find($Name, $Create = TRUE) {
      $Array = &$this->_Data;
      
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
      
      $Value = $this->_Data;
      $Count = count($Path);
      for($i = 0; $i < $Count; ++$i) {
         if(is_array($Value) && array_key_exists($Path[$i], $Value)) {
            $Value = $Value[$Path[$i]];
         } else {
            return $DefaultValue;
         }
      }
      
      if(is_string($Value))
         $Result = Gdn_Format::Unserialize($Value);
      else
         $Result = $Value;
         
      return $Result;
   }

   /**
    * Assigns a setting to the configuration array.
    *
    * @param string $Name The name of the configuration setting to assign. If the setting is
    * contained within an associative array, use dot denomination to get the
    * setting. ie. <code>$this->Set('Database.Host', $Value)</code> would set
    * <code>$Configuration[$Group]['Database']['Host'] = $Value</code>.
    * @param mixed $Value The value of the configuration setting.
    * @param boolean $Overwrite If the setting already exists, should it's value be overwritten? Defaults to true.
    * @param boolean $AddToSave Whether or not to queue the value up for the next call to Gdn_Config::Save().
    */
   public function Set($Name, $Value, $Overwrite = TRUE, $AddToSave = TRUE) {
      if(!is_array($this->_Data))
         $this->_Data = array();

      if(!is_array($this->_SaveData))
         $this->_SaveData = array();
         
      if (!is_array($Name)) {
         $Name = array(
            $Name => $Value
         );
      } else {
         $Overwrite = $Value;
      }
      
      $Data = $Name;
      foreach ($Data as $Name => $Value) {

         $Keys = explode('.', $Name);
         $KeyCount = count($Keys);
   
         $Array =& $this->_Data;
         
         $SaveArray =& $this->_SaveData;
         for ($i = 0; $i < $KeyCount; ++$i) {
            $Key = $Keys[$i];
            if (!is_array($Array)) $Array = array();
            $KeyExists = array_key_exists($Key, $Array);
   
            if($i == $KeyCount - 1) {   
               // If we are on the last iteration of the key, then set the value.
               if($KeyExists === FALSE || $Overwrite === TRUE) {
                  $Array[$Key] = Gdn_Format::Serialize($Value);
                  if ($AddToSave)
                     $SaveArray[$Key] = Gdn_Format::Serialize($Value);
               }
            } else {
               // Otherwise, traverse the array
               if($KeyExists === FALSE) {
                  $Array[$Key] = array();
                  if ($AddToSave)
                     $SaveArray[$Key] = array();
               }
               $Array =& $Array[$Key];
               if ($AddToSave)
                  $SaveArray =& $SaveArray[$Key];
            }
         }
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
   public function Remove($Name) {
   
      // The full merged config
      if(!is_array($this->_Data))
         return FALSE;

      // The local override config
      if(!is_array($this->_SaveData))
         $this->_SaveData = array();
      
      $Found = FALSE;
      $KeyParts = explode('.', $Name);
      $KeyPartsCount = count($KeyParts);

      $DataMergedConfig =& $this->_Data;
      $DataLocalConfig =& $this->_SaveData;

      for ($i = 0; $i < $KeyPartsCount; ++$i) {
         
         $Key = $KeyParts[$i];
         
         // Key will always be in here if it is anywhere at all
         if (array_key_exists($Key, $DataMergedConfig)) {

            // Does the key exist in the override file?
            $LocalKeyExists = is_array($DataLocalConfig) && array_key_exists($Key, $DataLocalConfig);
            if ($i == ($KeyPartsCount - 1)) {
               // We are at the setting, so unset it.
               $Found = TRUE;
               unset($DataMergedConfig[$Key]);
               
               // Only try to unset the local key if it exists
               if ($LocalKeyExists)
                  unset($DataLocalConfig[$Key]);
            } else {
               // Traverse the arrays.
               $DataMergedConfig =& $DataMergedConfig[$Key];
               
               // Only try to traverse the local array if the key exists...
               if ($LocalKeyExists)
                  $DataLocalConfig =& $DataLocalConfig[$Key];
               // ..otherwise, if its empty, unset it. Else just leave it alone.
               else
                  if (!sizeof($DataLocalConfig))
                     $DataLocalConfig = null;
            }
         } else {
            $Found = FALSE;
            break;
         }
      }
      
      return $Found;
   }

   /**
    * Loads an array of settings from a file into the object with the specified name;
    *
    * @param string $File A string containing the path to a file that contains the settings array.
    * @param string $LoadFor An enumerator ('Save' or 'Use') indicating what the settings are being
    * loaded for. If 'Save', the settings will be loaded into $this->_SaveData group
    * and kept there until <code>$this->Save()</code> is called. If 'Use', the settings will
    * be loaded into the $Group array.
    * @param string $Name The name of the variable and initial group settings.
    * <b>Note</b>: When $Name is 'Configuration' then the data will be set to the root of the config.
    * @return boolean
    */
   public function Load($File, $LoadFor = 'Use', $Name = 'Configuration') {
      // Prevent someone from calling Save and wiping out a config file accidentally.
      if($LoadFor == 'Save')
         $this->_File = $File;
      else
         $this->_File = '';
      
      if(!file_exists($File)) {
         return FALSE;
      }
      
      switch($LoadFor) {
         case 'Save':
            $Array = &$this->_SaveData; break;
         case 'Use':
            $Array = &$this->_Data; break;
      }
      
      if(!is_array($Array))
         $Array = array();
         
      // Define the variable properly.
      $$Name = NULL;
      
      // Include the file.
      include($File);
      
      // Make sure the config variable is here and is an array.
      if(is_null($$Name) || !is_array($$Name)) {
         return TRUE;
      }
      
      if($Name != 'Configuration') {
         $Configuration[$Name] = $$Name;
      }
      
      $this->_MergeConfig($Array, $Configuration);
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
      if (!is_array($this->_Data))
         $this->_Data = array();
      if($Name == 'Configuration')
         $Name == '';
         
      // Find the spot to insert the settings.
      $Loc = &$this->Find($Name, TRUE);
      
      if(is_null($Loc) || $Overwrite === TRUE) {
         $Loc = $Settings;
         return TRUE;
      } else {
         return FALSE;
      }
   }
   
   protected function _MergeConfig(&$Data, &$Loaded) {
      foreach($Loaded as $Key => $Value) {
         if(!array_key_exists($Key, $Data)) {
            $Data[$Key] = $Value;
         } elseif(is_array($Data[$Key]) && is_array($Value)) {
            $this->_MergeConfig($Data[$Key], $Value);
         } else {
            $Data[$Key] = $Value;
         }
      }
   }

   /**
    * Saves all settings in $Group to $File.
    *
    * @param string $File The full path to the file where the Settings should be saved.
    * @param string $Group The name of the settings group to be saved to the $File.
    * @param boolean $RequireSourceFile Should $File be required to exist in order to save? If true, then values
    * from this file will be merged into the settings array before it is saved.
    * If false, the values in the settings array will overwrite any values
    * existing in the file (if it exists).
    * @return boolean
    */
   public function Save($File = '', $Group = '', $RequireSourceFile = TRUE) {
      if ($File == '')
         $File = $this->_File;

      if ($File == '')
         trigger_error(ErrorMessage('You must specify a file path to be saved.', 'Configuration', 'Save'), E_USER_ERROR);

      if (!is_writable($File))
         throw new Exception(sprintf(T("Unable to write to config file '%s' when saving."),$File));

      if($Group == '')
         $Group = $this->CurrentGroup;

      if($Group == '')
         $Group = 'Configuration';
         
      $Data = &$this->_SaveData;
      $this->_Sort($Data);
      
      // Check for the case when the configuration is the group.
      if(is_array($Data) && count($Data) == 1 && array_key_exists($Group, $Data)) {
         $Data = $Data[$Group];
      }

      $NewLines = array();
      $NewLines[] = "<?php if (!defined('APPLICATION')) exit();";
      $LastName = '';
      foreach($Data as $Name => $Value) {
         // Write a newline to seperate sections.
         if($LastName != $Name && is_array($Value)) {
            $NewLines[] = '';
            $NewLines[] = '// '.$Name;
         }
         
         $Line = "\$".$Group."['".$Name."']";
         FormatArrayAssignment($NewLines, $Line, $Value);
      }
      
      // Record who made the change and when
      if (is_array($NewLines)) {
         $Session = Gdn::Session();
         $User = $Session->UserID > 0 && is_object($Session->User) ? $Session->User->Name : 'Unknown';
         $NewLines[] = '';
         $NewLines[] = '// Last edited by '.$User.' (' . RemoteIp() . ')' . Gdn_Format::ToDateTime();
      }

      $FileContents = FALSE;
      if ($NewLines !== FALSE)
         $FileContents = implode("\n", $NewLines);

      if ($FileContents === FALSE)
         trigger_error(ErrorMessage('Failed to define configuration file contents.', 'Configuration', 'Save'), E_USER_ERROR);

      // echo 'saving '.$File;
      Gdn_FileSystem::SaveFile($File, $FileContents, LOCK_EX);
      
      // Call the built in method to remove the dependancy to an external object.
      //file_put_contents($File, $FileContents);

      // Clear out the save data array
      $this->_SaveData = array();
      $this->_File = '';
      return TRUE;
   }
   
   protected function _Sort(&$Data) {
      ksort($Data);
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