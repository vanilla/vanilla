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
 * Manages defining and examining the schema of a database table.
 */
class Gdn_Schema {

   /**
    * An associative array of TableName => Fields associative arrays that
    * describe the table's field properties. Each field is represented by an
    * object with the following properties:
    *  Name, PrimaryKey, Type, AllowNull, Default, Length, Enum
    */
   protected $_Schema;
   
   /**
    * The name of the table currently being examined.
    */
   public $CurrentTable;
   
   /**
    * Class constructor. Defines the related database table name.
    *
    * @param string Explicitly define the name of the table that this model represents. You can also explicitly set this value with $this->TableName.
    * @param Gdn_Database
    */
   public function __construct($Table = '', $Database = NULL) {
      if ($Table != '')
         $this->Fetch($Table, $Database);
   }
   
   /**
    * Fetches the schema for the requested table. If it does not exist yet, it
    * will connect to the database and define it.
    *
    * @param string The name of the table schema to fetch from the database (or cache?).
    * @param Gdn_Database
    * @return array
    */
   public function Fetch($Table = FALSE, $Database = NULL) {
      if ($Table !== FALSE)
         $this->CurrentTable = $Table;
      
      if (!is_array($this->_Schema))
         $this->_Schema = array();
         
      if (!array_key_exists($this->CurrentTable, $this->_Schema)) {
         if($Database !== NULL) {
            $SQL = $Database->SQL();
         }
         else {
            $SQL = Gdn::SQL();
         }
         $this->_Schema[$this->CurrentTable] = $SQL->FetchTableSchema($this->CurrentTable);
      }
      return $this->_Schema[$this->CurrentTable];
   }

   /** Gets the array of fields/properties for the schema.
    *
    * @return array
    */
   public function Fields($Tablename = FALSE) {
      if (!$Tablename)
         $Tablename = $this->CurrentTable;

      return $this->_Schema[$Tablename];
   }
   
   /**
    * Returns a the entire field object.
    *
    * @param string The name of the field to look for in $this->CurrentTable (or $Table if it is defined).
    * @param string If this value is specified, $this->CurrentTable will be switched to $Table.
    */
   public function GetField($Field, $Table = '') {
      if ($Table != '')
         $this->CurrentTable = $Table;
      
      if (!is_array($this->_Schema))
         $this->_Schema = array();
         
      $Result = FALSE;
      if ($this->FieldExists($this->CurrentTable, $Field) === TRUE)
         $Result = $this->_Schema[$this->CurrentTable][$Field];
         
      return $Result;
   }
   
   /**
    * Returns the value of $Property or $Default if not found.
    *
    * @param string The name of the field to look for in $this->CurrentTable (or $Table if it is defined).
    * @param string The name of the property to retrieve from $Field. Options are: Name, PrimaryKey, Type, AllowNull, Default, Length, and Enum.
    * @param string The default value to return if $Property is not found in $Field of $Table.
    * @param string If this value is specified, $this->CurrentTable will be switched to $Table.
    */
   public function GetProperty($Field, $Property, $Default = FALSE, $Table = '') {
      $Return = $Default;
      if ($Table != '')
         $this->CurrentTable = $Table;
         
      $Properties = array('Name', 'PrimaryKey', 'Type', 'AllowNull', 'Default', 'Length', 'Enum');
      if (in_array($Property, $Properties)) {
         $Field = $this->GetField($Field, $this->CurrentTable);
         if ($Field !== FALSE)
            $Return = $Field->$Property;
      }
         
      return $Return;
   }
   
   /**
    * Returns a boolean value indicating if the specified $Field exists in
    * $Table. Assumes that $this->Fetch() has been called for $Table.
    *
    * @param string The name of the table to look for $Field in.
    * @param string The name of the field to look for in $Table.
    */
   public function FieldExists($Table, $Field) {
      if (array_key_exists($Table, $this->_Schema)
         && is_array($this->_Schema[$Table])
         && array_key_exists($Field, $this->_Schema[$Table])
         && is_object($this->_Schema[$Table][$Field]))
         return TRUE;
      else
         return FALSE;
   }
   
   /**
    * Returns the name (or array of names) of the field(s) that represents the
    * primary key on $Table.
    *
    * @param string The name of the table for which to find the primary key(s).
    */
   public function PrimaryKey($Table, $Database = NULL) {
      $Schema = $this->Fetch($Table, $Database);
      $PrimaryKeys = array();
      foreach ($Schema as $FieldName => $Properties) {
         if ($Properties->PrimaryKey === TRUE)
            $PrimaryKeys[] = $FieldName;
      }
      
      return count($PrimaryKeys) == 1 ? $PrimaryKeys[0] : $PrimaryKeys;
   }
}
