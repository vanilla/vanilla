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
 * Represents, enforces integrity, and aids in the management of: configuration
 * data. This generic model can be instantiated (with the configuration array
 * name it is intended to represent) and used directly, or it can be extended
 * and overridden for more complicated procedures related to different
 * configuration arrays.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_ConfigurationModel {

   /**
    * The name of the configuration array that this model is intended to
    * represent. The default value assigned to $this->Name will be the name
    * that the model was instantiated with (defined in $this->__construct()).
    *
    * @var string
    */
   public $Name;

   /**
    * An object that is used to manage and execute data integrity rules on this
    * object.
    *
    * @var object
    */
   public $Validation;

   /**
    * The actual array of data being worked on.
    *
    * @var object
    */
   public $Data;

   /**
    * A collection of Field => Values that will NOT be validated and WILL be
    * saved as long as validation succeeds. You can add to this collection with
    * $this->ForceSetting();
    *
    * @var string
    */
   private $_ForceSettings = array();

   /**
    * Class constructor. Defines the related database table name.
    *
    * @param string $ConfigurationArrayName The name of the configuration array that is being manipulated.
    * @param object $Validation
    */
   public function __construct(&$Validation) {
      $this->Name = 'Configuration';
      $this->Data = array();
      $this->Validation = &$Validation;
   }

   /**
    * Allows the user to declare which values are being manipulated in the
    * $this->Name configuration array.
    *
    * @param mixed $FieldName The name of the field (or array of field names) to ensure.
    */
   public function SetField($FieldName) {
      $Config = Gdn::Factory(Gdn::AliasConfig);
      if (is_array($FieldName) === FALSE)
         $FieldName = array($FieldName);

      foreach($FieldName as $Index => $Value) {
         if(is_numeric($Index)) {
            $NameKey = $Value;
            $Default = '';
         } else {
            $NameKey = $Index;
            $Default = $Value;
         }
         /*
         if ($this->Name != 'Configuration')
            $Name = $NameKey;
         else
            $Name = $this->Name.'.'.$NameKey;
         */

         $this->Data[$NameKey] = $Config->Get($NameKey, $Default);
      }
   }

   /**
    * Adds a new Setting => Value pair that will NOT be validated and WILL be
    * saved to the configuration array.
    *
    * @param mixed $FieldName The name of the field (or array of field names) to save.
    * @param mixed $FieldValue The value of FieldName to be saved.
    */
   public function ForceSetting($FieldName, $FieldValue) {
      $this->_ForceSettings[$FieldName] = $FieldValue;
   }

   /**
    * Takes an associative array and munges it's keys together with a dot
    * delimiter. For example:
    *  $Array['Database']['Host'] = 'dbhost';
    *  ... becomes ...
    *  $Array['Database.Host'] = 'dbhost';
    *
    * @param array $Array The array to be normalized.
    */
   private function NormalizeArray($Array) {
      $Return = array();
      foreach ($Array as $Key => $Value) {
         if (is_array($Value) === TRUE && array_key_exists(0, $Value) === FALSE) {
            foreach($Value as $k => $v) {
               $Return[$Key.'.'.$k] = $v;
            }
         } else {
            $Return[$Key] = $Value;
         }
      }
      return $Return;
   }

   /**
    * Takes a set of form data ($Form->_PostValues), validates them, and
    * inserts or updates them to the configuration file.
    *
    * @param array $FormPostValues An associative array of $Field => $Value pairs that represent data posted
    * from the form in the $_POST or $_GET collection.
    */
   public function Save($FormPostValues, $Live = FALSE) {
      // Fudge your way through the schema application. This will allow me to
      // force the validation object to expect the fieldnames contained in
      // $this->Data.
      $this->Validation->ApplySchema($this->Data);
      // Validate the form posted values
      if ($this->Validation->Validate($FormPostValues)) {
         // Merge the validation fields and the forced settings into a single array
         $Settings = $this->Validation->ValidationFields();
         if (is_array($this->_ForceSettings))
            $Settings = MergeArrays($Settings, $this->_ForceSettings);
            
         $SaveResults = SaveToConfig($Settings);
         
         // If the Live flag is true, set these in memory too
         if ($SaveResults && $Live)
            Gdn::Config()->Set($Settings, TRUE);
         
         return $SaveResults;
      } else {
         return FALSE;
      }
   }

   /**
    * A convenience method to check that the form-posted data is valid; just
    * in case you don't want to jump directly to the save if the data *is*
    * valid.
    *
    * @param string $FormPostValues
    * @todo $FormPostValues needs a description and correct variable type.
    */
   public function Validate($FormPostValues) {
      $this->Validation->ApplySchema($this->Data);
      // Validate the form posted values
      return $this->Validation->Validate($FormPostValues);
   }

   /**
    * Returns the $this->Validation->ValidationResults() array.
    */
   public function ValidationResults() {
      return $this->Validation->Results();
   }
}