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
 * The GenericStructure class is used by any given database driver to build,
 * modify, and create tables and views.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Database
 */
require_once(dirname(__FILE__).DS.'class.database.php');

abstract class Gdn_DatabaseStructure {

	protected $_DatabasePrefix = '';

	/**
	 * Whether or not to only capture the sql, rather than execute it.
	 * When this property is true then a property called CapturedSql will be added to this class which is an array of all the Sql statements.
	 * @var bool
	 */
	public $CaptureOnly = FALSE;

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

   /** The existing columns in the database.
    * @var array
    */
   protected $_ExistingColumns = NULL;

   /**
    * The name of the table to create or modify.
    *
    * @var string
    */
   protected $_TableName;

   /** @var bool Whether or not this table exists in the database.
    */
   protected $_TableExixts;

   /** @var string The name of the storage engine for this table.
    */
   protected $_TableStorageEngine;

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
      
      $this->Reset();
   }
   
   protected function _CreateColumn($Name, $Type, $Null, $Default, $KeyType) {
      $Length = '';
      $Precision = '';
      
      // Check to see if the type starts with a 'u' for unsigned.
      if(is_string($Type) && strncasecmp($Type, 'u', 1) == 0) {
         $Type = substr($Type, 1);
         $Unsigned = TRUE;
      } else {
         $Unsigned = FALSE;
      }
      
      // Check for a length in the type.
      if(is_string($Type) && preg_match('/(\w+)\s*\(\s*(\d+)\s*(?:,\s*(\d+)\s*)?\)/', $Type, $Matches)) {
         $Type = $Matches[1];
         $Length = $Matches[2];
         if(count($Matches) >= 4)
            $Precision = $Matches[3];
      }
      
      $Column = new stdClass();
      $Column->Name = $Name;
      $Column->Type = is_array($Type) ? 'enum' : $Type;
      $Column->Length = $Length;
      $Column->Precision = $Precision;
      $Column->Enum = is_array($Type) ? $Type : FALSE;
      $Column->AllowNull = $Null;
      $Column->Default = $Default;
      $Column->KeyType = $KeyType;
      $Column->Unsigned = $Unsigned;
      $Column->AutoIncrement = FALSE;
      
      // Handle enums and sets as types.
      if(is_array($Type)) {
         if(count($Type) === 2 && is_array(ArrayValue(1, $Type))) {
            // The type is specified as the first element in the array.
            $Column->Type = $Type[0];
            $Column->Enum = $Type[1];
         } else {
            // This is an enum.
            $Column->Type = 'enum';
            $Column->Enum = $Type;
         }
      } else {
         $Column->Type = $Type;
         $Column->Enum = FALSE;
      }
      
      return $Column;
   }
   
   /**
    * Defines a column to be added to $this->Table().
    *
    * @param string $Name The name of the column to create.
    * @param mixed $Type The data type of the column to be created. Types with a length speecifty the length in barackets.
    * * If an array of values is provided, the type will be set as "enum" and the array will be assigned as the column's Enum property.
    * * If an array of two values is specified then a "set" or "enum" can be specified (ex. array('set', array('Short', 'Tall', 'Fat', 'Skinny')))
    * @param boolean $NullDefault Whether or not nulls are allowed, if not a default can be specified.
    * * TRUE: Nulls are allowed.
    * * FALSE: Nulls are not allowed.
    * * Any other value: Nulls are not allowed, and the specified value will be used as the default.
    * @param string $KeyType What type of key is this column on the table? Options
    * are primary, key, and FALSE (not a key).
    */
   public function Column($Name, $Type, $NullDefault = FALSE, $KeyType = FALSE) {
      if(is_null($NullDefault) || $NullDefault === TRUE) {
         $Null = TRUE;
         $Default = NULL;
      } elseif($NullDefault === FALSE) {
         $Null = FALSE;
         $Default = NULL;
      } elseif(is_array($NullDefault)) {
         $Null = ArrayValue('Null', $NullDefault);
         $Default = ArrayValue('Default', $NullDefault, NULL);
      } else {
         $Null = FALSE;
         $Default = $NullDefault;
      }

      // Check the key type for validity. A column can be in many keys by specifying an array as key type.
      $KeyTypes = (array)$KeyType;
      $KeyTypes1 = array();
      foreach ($KeyTypes as $KeyType1) {
         if (in_array($KeyType1, array('primary', 'key', 'index', 'unique', 'fulltext', FALSE)))
            $KeyTypes1[] = $KeyType1;
      }
      if (count($KeyTypes1) == 0)
         $KeyType = FALSE;
      elseif (count($KeyTypes1) == 1)
         $KeyType = $KeyTypes1[0];
      else
         $KeyType = $KeyTypes1;

      $Column = $this->_CreateColumn($Name, $Type, $Null, $Default, $KeyType);
      $this->_Columns[$Name] = $Column;
      return $this;
   }

   /** Returns whether or not a column exists in the database.
    *
    * @param string $ColumnName The name of the column to check.
    * @return bool
    */
   public function ColumnExists($ColumnName) {
      $Result = array_key_exists($ColumnName, $this->ExistingColumns());
      if (!$Result) {
         foreach ($this->_Columns as $ColName => $Def) {
            if (strcasecmp($ColumnName, $ColName) == 0)
               return TRUE;
         }
         return FALSE;
      }
      return $Result;
   }
   
   /**
	 * And associative array of $ColumnName => $ColumnProperties columns for the table.
	 * @return array
	 */
	public function Columns($Name = '') {
      if (strlen($Name) > 0) {
         if (array_key_exists($Name, $this->_Columns))
            return $this->_Columns[$Name];
         else {
            foreach($this->_Columns as $ColName => $Def) {
               if (strcasecmp($Name, $ColName) == 0)
                  return $Def;
            }
            return NULL;
         }
      }
		return $this->_Columns;
	}

	/** Return the definition string for a column.
	 * @param mixed $Column The column to get the type string from.
	 *  - <b>object</b>: The column as returned by the database schema. The properties looked at are Type, Length, and Precision.
	 *  - <b>string</b<: The name of the column currently in this structure.
	 * * @return string The type definition string.
	 */
	public function ColumnTypeString($Column) {
		if(is_string($Column))
			$Column = $this->_Columns[$Column];
		
		$Type = GetValue('Type', $Column);
		$Length = GetValue('Length', $Column);
		$Precision = GetValue('Precision', $Column);

		if(in_array(strtolower($Type), array('tinyint', 'smallint', 'mediumint', 'int', 'float', 'double')))
			$Length = NULL;

		if($Type && $Length && $Precision)
			$Result = "$Type($Length, $Precision)";
		elseif($Type && $Length)
			$Result = "$Type($Length)";
		elseif($Type)
			$Result = $Type;
		else
			$Result = 'int';

		return $Result;
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

   public function Engine($Engine, $CheckAvailability=TRUE) {
      trigger_error(ErrorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'Engine'), E_USER_ERROR);
   }


	/** Load the schema for this table from the database.
	 * @param string $TableName The name of the table to get or blank to get the schema for the current table.
	 * @return Gdn_DatabaseStructure $this
	 */
	public function Get($TableName = '') {
		if($TableName)
			$this->Table($TableName);

		$Columns = $this->Database->SQL()->FetchTableSchema($this->_TableName);
		$this->_Columns = $Columns;

		return $this;
	}

   /**
    * Defines a primary key column on a table.
    *
    * @param string $Name The name of the column.
    * @param string $Type The data type of the column.
    * @return Gdn_DatabaseStructure $this.
    */
   public function PrimaryKey($Name, $Type = 'int') {
      $Column = $this->_CreateColumn($Name, $Type, FALSE, NULL, 'primary');
      $Column->AutoIncrement = TRUE;
      $this->_Columns[$Name] = $Column;
      
      return $this;
   }
	
	/**
	 * Send a query to the database and return the result.
	 * @param string $Sql The sql to execute.
	 * @return bool Whethor or not the query succeeded.
	 */
	public function Query($Sql) {
		if($this->CaptureOnly) {
			if(!property_exists($this->Database, 'CapturedSql'))
				$this->Database->CapturedSql = array();
			$this->Database->CapturedSql[] = $Sql;
			return TRUE;
		} else {
			$Result = $this->Database->Query($Sql);
			return $Result;
		}
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
      try {
         // Make sure that table and columns have been defined
         if ($this->_TableName == '')
            throw new Exception(T('You must specify a table before calling DatabaseStructure::Set()'));

         if (count($this->_Columns) == 0)
            throw new Exception(T('You must provide at least one column before calling DatabaseStructure::Set()'));

         if ($this->TableExists()) {
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
      } catch (Exception $Ex) {
         $this->Reset();
         throw $Ex;
      }
   }

   /**
    * Specifies the name of the table to create or modify.
    *
    * @param string $Name The name of the table.
    * @param string $CharacterEncoding The default character encoding to specify for this table.
    */
   public function Table($Name = '', $CharacterEncoding = '') {
		if(!$Name)
			return $this->_TableName;
		
      $this->_TableName = $Name;
      if ($CharacterEncoding == '')
         $CharacterEncoding = Gdn::Config('Database.CharacterEncoding', '');

      $this->_CharacterEncoding = $CharacterEncoding;
      return $this;
   }

   /** Whether or not the table exists in the database.
    * @return bool
    */
   public function TableExists($TableName = NULL) {
      if($this->_TableExists === NULL || $TableName !== NULL) {
         if ($TableName === NULL)
            $TableName = $this->TableName();

         if(strlen($TableName) > 0) {
            $Tables = $this->Database->SQL()->FetchTables(':_'.$TableName);
            $Result = count($Tables) > 0;
         } else {
            $Result = FALSE;
         }
         if ($TableName == $this->TableName())
            $this->_TableExists = $Result;
         return $Result;
      }
      return $this->_TableExists;
   }

   /** Returns the name of the table being defined in this object.
    *
    * @return string
    */
   public function TableName() {
      return $this->_TableName;
   }

   /** Gets an arrya of type names allowed in the structure.
    * @param string $Class The class of types to get. Valid values are:
    *  - <b>int</b>: Integer types.
    *  - <b>float</b>: Floating point types.
    *  - <b>decimal</b>: Precise decimal types.
    *  - <b>numeric</b>: float, int and decimal.
    *  - <b>string</b>: String types.
    *  - <b>date</b>: Date types.
    *  - <b>length</b>: Types that have a length.
    *  - <b>precision</b>: Types that have a precision.
    *  - <b>other</b>: Types that don't fit into any other category on their own.
    *  - <b>all</b>: All recognized types.
    */
   public function Types($Class = 'all') {
      $Date = array('datetime', 'date');
      $Decimal = array('decimal');
      $Float = array('float', 'double');
      $Int = array('int', 'tinyint', 'smallint', 'mediumint', 'bigint');
      $String = array('varchar', 'char', 'mediumtext', 'text');
      $Length = array('varbinary');
      $Other = array('enum');

      switch(strtolower($Class)) {
         case 'date': return $Date;
         case 'decimal': return $Decimal;
         case 'float': return $Float;
         case 'int': return $Int;
         case 'string': return $String;
         case 'other': return array_merge($Length, $Other);

         case 'numeric': return array_merge($Foat, $Int, $Decimal);
         case 'length': return array_merge($String, $Length, $Decimal);
         case 'precision': return $Decimal;
         default: return array();
      }
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

   /** Gets the column definitions for the columns in the database.
    * @return array
    */
   public function ExistingColumns() {
      if($this->_ExistingColumns === NULL) {
         if($this->TableExists())
            $this->_ExistingColumns = $this->Database->SQL()->FetchTableSchema($this->_TableName);
         else
            $this->_ExistingColumns = array();
      }
      return $this->_ExistingColumns;
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

   /** Reset the internal state of this object so that it can be reused.
    * @return Gdn_DatabaseStructure $this
    */
   public function Reset() {
      $this->_CharacterEncoding = '';
      $this->_Columns = array();
      $this->_ExistingColumns = NULL;
      $this->_TableExists = NULL;
      $this->_TableName = '';
      $this->_TableStorageEngine = NULL;

		return $this;
   }
}
