<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/// <namespace>
/// Lussumo.Garden.Core
/// </namespace>

/// <summary>
/// Manages defining and examining the schema of a database table.
/// </summary>
class Schema {
   
   /// <prop type="array">
   /// An associative array of TableName => Fields associative arrays that
   /// describe the table's field properties. Each field is represented by an
   /// object with the following properties: 
   ///  Name, PrimaryKey, Type, AllowNull, Default, Length, Enum
   /// </prop>
   protected $_Schema;
   
   /// <prop type="string">
   /// The name of the table currently being examined.
   /// </prop>
   public $CurrentTable;
   
   /// <summary>
   /// Class constructor. Defines the related database table name.
   /// </summary>
   /// <param name="TableName" type="string" required="false" default="get_class($this)">
   /// An optional parameter that allows you to explicitly define the name of
   /// the table that this model represents. You can also explicitly set this
   /// value with $this->TableName.
   /// </param>
   public function __construct($Table = '') {
      if ($Table != '')
         $this->Fetch($Table);
   }
   
   /// <summary>
   /// Fetches the schema for the requested table. If it does not exist yet, it
   /// will connect to the database and define it.
   /// </summary>
   /// <param name="Table" type="string" required="false" default="FALSE">
   /// The name of the table schema to fetch from the database (or cache?).
   /// </param>   
   public function Fetch($Table = FALSE) {
      if ($Table !== FALSE)
         $this->CurrentTable = $Table;
      
      if (!is_array($this->_Schema))
         $this->_Schema = array();
         
      if (!array_key_exists($this->CurrentTable, $this->_Schema)) {
         $SQL = Gdn::SQL();
         $this->_Schema[$this->CurrentTable] = $SQL->FetchTableSchema($this->CurrentTable);
      }
      return $this->_Schema[$this->CurrentTable];
   }
   
   /// <summary>
   /// Returns a the entire field object.
   /// </summary>
   /// <param name="Field" type="string">
   /// The name of the field to look for in $this->CurrentTable (or $Table if it
   /// is defined).
   /// </param>
   /// <param name="Table" type="string" required="false" default="$this->CurrentTable">
   /// If this value is specified, $this->CurrentTable will be switched to
   /// $Table.
   /// </param>
   public function GetField($Field, $Table = '') {
      if ($Table != '')
         $this->CurrentTable = $Table;
      
      if (!is_array($this->_Schema))
         $this->_Schema = array();
         
      $Field = FALSE;
      if ($this->FieldExists($this->CurrentTable, $Field) === TRUE)
         $Field = $this->_Schema[$this->CurrentTable][$FieldName];
         
      return $Field;
   }
   
   /// <summary>
   /// Returns a the value of $Property or $Default if not found.
   /// </summary>
   /// <param name="Field" type="string">
   /// The name of the field to look for in $this->CurrentTable (or $Table if it
   /// is defined).
   /// </param>
   /// <param name="Property" type="string">
   /// The name of the property to retrieve from $Field. Options are: Name,
   /// PrimaryKey, Type, AllowNull, Default, Length, and Enum.
   /// </param>
   /// <param name="Default" type="string" required="false" default="FALSE">
   /// The default value to return if $Property is not found in $Field of
   /// $Table.
   /// </param>
   /// <param name="Table" type="string" required="false" default="$this->CurrentTable">
   /// If this value is specified, $this->CurrentTable will be switched to
   /// $Table.
   /// </param>
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
   
   /// <summary>
   /// Returns a boolean value indicating if the specified $Field exists in
   /// $Table. Assumes that $this->Fetch() has been called for $Table.
   /// </summary>
   /// <param name="Table" type="string">
   /// The name of the table to look for $Field in.
   /// </param>
   /// <param name="Field" type="string">
   /// The name of the field to look for in $Table.
   /// </param>
   public function FieldExists($Table, $Field) {
      if (array_key_exists($Table, $this->_Schema)
         && is_array($this->_Schema[$Table])
         && array_key_exists($Field, $this->_Schema[$Table])
         && is_object($this->_Schema[$Table][$Field]))
         return TRUE;
      else
         return FALSE;
   }
   
   /// <summary>
   /// Returns the name (or array of names) of the field(s) that represents the
   /// primary key on $Table.
   /// </summary>
   /// <param name="Table" type="string">
   /// The name of the table for which to find the primary key(s).
   /// </param>
   public function PrimaryKey($Table) {
      $Schema = $this->Fetch($Table);
      $PrimaryKeys = array();
      
      foreach ($Schema as $FieldName => $Properties) {
         if ($Properties->PrimaryKey === TRUE)
            $PrimaryKeys[] = $FieldName;
      }
      
      return count($PrimaryKeys) == 1 ? $PrimaryKeys[0] : $PrimaryKeys;
   }
}