<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/**
 * The GenericStructure class is used by any given database driver to build,
 * modify, and create tables and views.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Lussumo.Garden.Database
 */
require_once(dirname(__FILE__).DS.'class.database.php');

abstract class Gdn_DatabaseStructure {

   /**
    * The name of the class that has been instantiated. Typically this will be
    * a class that has extended this class.
    *
    * @var string
    */
   public $ClassName = '';

   /**
    * The character encoding to set as default for the table being created.
    *
    * @var string
    */
   protected $_CharacterEncoding;

   /**
    * An associative array of $ColumnName => $ColumnPropertiesObject columns to
    * be added to $this->_TableName;
    *
    * @var array
    */
   protected $_Columns;

   /**
    * The instance of the database singleton.
    *
    * @var Gdn_Database
    */
   public $Database;

   /**
    * The name of the table to create or modify.
    *
    * @var string
    */
   protected $_TableName;

   /**
    * The constructor for this class. Automatically fills $this->ClassName.
    *
    * @param string $Database
    * @todo $Database needs a description.
    */
   public function __construct($Database = NULL) {
      $this->ClassName = get_class($this);
      if(is_null($Database))
         $this->Database = Gdn::Database();
      else
         $this->Database = $Database;
      
      $this->DatabasePrefix($this->Database->DatabasePrefix);
      
      $this->_TableName = '';
      $this->_Columns = array();
   }

   /**
    * Defines a column to be added to $this->Table().
    *
    * @param string $Name The name of the column to create.
    * @param mixed $Type The data type of the column to be created. If an array of values is
    * provided, the type will be set as "enum" and the array will be assigned
    * as the column's Enum property.
    * @param mixed $Length The length of the column.
    * @param boolean $Null Does the column allow null values?
    * @param mixed $Default The default value of the column. If NULL is provided (default), there
    * will be no default value.
    * @param string $KeyType What type of key is this column on the table? Options
    * are primary, key, and FALSE (not a key).
    * @param boolean $AutoIncrement A boolean value indicating if this column auto-increments.
    */
   public function Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE) {
      if (!in_array($KeyType, array('primary', 'key', 'index', 'unique', FALSE)))
         $KeyType = FALSE;
         
      // Check for a length in the type.
      if(is_string($Type) && preg_match('/([_a-zA-Z]\w*)\s*(?:\((\d+)\))?/', $Type, $Matches)) {
         $Type = $Matches[1];
         if(count($Matches) >= 3)
            $Length = $Matches[2];
      }

      $Column = new Gdn_ShellClass();
      $Column->Name = $Name;
      $Column->Type = is_array($Type) ? 'enum' : $Type;
      $Column->Length = $Length;
      $Column->Enum = is_array($Type) ? $Type : FALSE;
      $Column->AllowNull = $Null;
      $Column->Default = $Default;
      $Column->KeyType = $KeyType;
      $Column->AutoIncrement = $AutoIncrement;
      $this->_Columns[$Name] = $Column;
      return $this;
   }

   /**
    * Gets and/or sets the database prefix.
    *
    * @param string $DatabasePrefix
    * @todo $DatabasePrefix needs a description.
    */
   public function DatabasePrefix($DatabasePrefix = '') {
      if ($DatabasePrefix != '')
         $this->_DatabasePrefix = $DatabasePrefix;

      return $this->_DatabasePrefix;
   }

   /**
    * Drops $this->Table() from the database.
    */
   public function Drop() {
      trigger_error(ErrorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'Drop'), E_USER_ERROR);
   }

   /**
    * Drops $Name column from $this->Table().
    *
    * @param string $Name The name of the column to drop from $this->Table().
    */
   public function DropColumn($Name) {
      trigger_error(ErrorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'DropColumn'), E_USER_ERROR);
   }

   /**
    * Defines a primary key column on a table.
    *
    * @param string $Name The name of the column.
    * @param string $Type The data type of the column.
    * @return Gdn_DatabaseStructure $this.
    */
   public function PrimaryKey($Name, $Type = 'int') {
      return $this->Column($Name, $Type, '', FALSE, NULL, 'primary', TRUE);
   }
   
   /**
    * Renames a column in $this->Table().
    *
    * @param string $OldName The name of the column to be renamed.
    * @param string $NewName The new name for the column being renamed.
    */
   public function RenameColumn($OldName, $NewName) {
      trigger_error(ErrorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'RenameColumn'), E_USER_ERROR);
   }

   /**
    * Renames a table in the database.
    *
    * @param string $OldName The name of the table to be renamed.
    * @param string $NewName The new name for the table being renamed.
    * @param boolean $UsePrefix A boolean value indicating if $this->_DatabasePrefix should be prefixed
    * before $OldName and $NewName.
    */
   public function RenameTable($OldName, $NewName, $UsePrefix = FALSE) {
      trigger_error(ErrorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'RenameTable'), E_USER_ERROR);
   }

   /**
    * Creates the table and columns specified with $this->Table() and
    * $this->Column(). If no table or columns have been specified, this method
    * will throw a fatal error.
    *
    * @param boolean $Explicit If TRUE, and the table specified with $this->Table() already exists, this
    * method will remove any columns from the table that were not defined with
    * $this->Column().
    * @param boolean $Drop If TRUE, and the table specified with $this->Table() already exists, this
    * method will drop the table before attempting to re-create it.
    */
   public function Set($Explicit = FALSE, $Drop = FALSE) {
      // Make sure that table and columns have been defined
      if ($this->_TableName == '')
         throw new Exception(Gdn::Translate('You must specify a table before calling DatabaseStructure::Set()'));

      if (count($this->_Columns) == 0)
         throw new Exception(Gdn::Translate('You must provide at least one column before calling DatabaseStructure::Set()'));

      // Be sure to convert names to lowercase before comparing because
      // different operating systems/databases vary on how case-sensitivity is
      // handled in table names.
      $SQL = $this->Database->SQL();
      $Tables = $SQL->FetchTables();
      if (in_array(strtolower($this->_DatabasePrefix.$this->_TableName), array_map('strtolower', $Tables))) {
         if ($Drop) {
            // Drop the table.
            $this->Drop();

            // And re-create it.
            return $this->_Create();
         }

         // If the table already exists, go into modify mode.
         return $this->_Modify($Explicit, $Drop);
      } else {
         // If it doesn't already exist, go into create mode.
         return $this->_Create();
      }
   }

   /**
    * Specifies the name of the table to create or modify.
    *
    * @param string $Name The name of the table.
    * @param string $CharacterEncoding The default character encoding to specify for this table.
    */
   public function Table($Name, $CharacterEncoding = '') {
      $this->_TableName = $Name;
      if ($CharacterEncoding == '')
         $CharacterEncoding = Gdn::Config('Garden.Database.CharacterEncoding', '');

      $this->_CharacterEncoding = $CharacterEncoding;
      return $this;
   }

   /**
    * Specifies the name of the view to create or modify.
    *
    * @param string $Name The name of the view.
    * @param string $Query The actual query to create as the view. Typically this
    * can be generated with the $Database object.
    */
   public function View($Name, $Query) {
      trigger_error(ErrorMessage('The selected database engine can not create or modify views.', $this->ClassName, 'View'), E_USER_ERROR);
   }

   /**
    * Creates the table defined with $this->Table() and $this->Column().
    */
   protected function _Create() {
      trigger_error(ErrorMessage('The selected database engine does not perform the requested task.', $this->ClassName, '_Create'), E_USER_ERROR);
   }

   /**
    * Modifies $this->Table() with the columns specified with $this->Column().
    *
    * @param boolean $Explicit If TRUE, this method will remove any columns from the table that were not
    * defined with $this->Column().
    */
   protected function _Modify($Explicit = FALSE) {
      trigger_error(ErrorMessage('The selected database engine does not perform the requested task.', $this->ClassName, '_Modify'), E_USER_ERROR);
   }

   /**
    * @todo Undocumented method.
    */
   protected function _Reset() {
      $this->_CharacterEncoding = '';
      $this->_Columns = array();
      $this->_TableName = '';
   }
}