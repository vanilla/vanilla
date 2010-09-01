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
 * The MySQLStructure class is a MySQL-specific class for manipulating
 * database structure.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Database
 */
require_once(dirname(__FILE__).DS.'class.databasestructure.php');

class Gdn_MySQLStructure extends Gdn_DatabaseStructure {
   /// Constructor ///
   
   public function __construct($Database = NULL) {
      parent::__construct($Database);
   }

   /**
    * Drops $this->Table() from the database.
    */
   public function Drop() {
      if($this->TableExists())
         return $this->Query('drop table `'.$this->_DatabasePrefix.$this->_TableName.'`');
   }

   /**
    * Drops $Name column from $this->Table().
    *
    * @param string $Name The name of the column to drop from $this->Table().
    * @return boolean
    */
   public function DropColumn($Name) {
      if (!$this->Query('alter table `'.$this->_DatabasePrefix.$this->_TableName.'` drop column `'.$Name.'`'))
         throw new Exception(sprintf(T('Failed to remove the `%1$s` column from the `%2$s` table.'), $Name, $this->_DatabasePrefix.$this->_TableName));

      return TRUE;
   }
   
   public function Engine($Engine, $CheckAvailability=TRUE) {
      $Engine = strtolower($Engine);
      
      if ($CheckAvailability) {
         $EngineList = $this->Database->Query("SHOW ENGINES;");
         $ViableEngines = array();
         while ($EngineList && $StorageEngine = $EngineList->Value('Engine', FALSE)) {
            $EngineName = strtolower($StorageEngine);
            $ViableEngines[$EngineName] = TRUE;
         }
         
         if (!array_key_exists($Engine, $ViableEngines))
            return $this;
      }
      
      $this->_TableStorageEngine = $Engine;
      return $this;
   }
   
   /**
    * Renames a column in $this->Table().
    *
    * @param string $OldName The name of the column to be renamed.
    * @param string $NewName The new name for the column being renamed.
    * @param string $TableName
    * @return boolean
    * @todo $TableName needs a description.
    */
   public function RenameColumn($OldName, $NewName, $TableName = '') {
      if ($TableName != '')
         $this->_TableName = $TableName;

      // Get the schema for this table
      $OldPrefix = $this->Database->DatabasePrefix;
      $this->Database->DatabasePrefix = $this->_DatabasePrefix;
      $Schema = $this->Database->SQL()->FetchTableSchema($this->_TableName);
      $this->Database->DatabasePrefix = $OldPrefix;

      // Get the definition for this column
      $OldColumn = ArrayValue($OldName, $Schema);
      $NewColumn = ArrayValue($NewName, $Schema);

      // Make sure that one column, or the other exists
      if (!$OldColumn && !$NewColumn)
         throw new Exception(sprintf(T('The `%1$s` column does not exist.'),$OldName));

      // Make sure the new column name isn't already taken
      if ($OldColumn && $NewColumn)
         throw new Exception(sprintf(T('You cannot rename the `%1$s` column to `%2$s` because that column already exists.'), $OldName, $NewName));

      // Rename the column
      // The syntax for renaming a column is:
      // ALTER TABLE tablename CHANGE COLUMN oldname newname originaldefinition;
      if (!$this->Query('alter table `'.$this->_TableName.'` change column `'.$OldName.'` `'.$NewName.'` '.$this->_DefineColumn($OldColumn)))
         throw new Exception(sprintf(T('Failed to rename table `%1$s` to `%2$s`.'), $OldName, $NewName));

      return TRUE;
   }

   /**
    * Renames a table in the database.
    *
    * @param string $OldName The name of the table to be renamed.
    * @param string $NewName The new name for the table being renamed.
    * @param boolean $UsePrefix A boolean value indicating if $this->_DatabasePrefix should be prefixed
    * before $OldName and $NewName.
    * @return boolean
    */
   public function RenameTable($OldName, $NewName, $UsePrefix = FALSE) {
      if (!$this->Query('rename table `'.$OldName.'` to `'.$NewName.'`'))
         throw new Exception(sprintf(T('Failed to rename table `%1$s` to `%2$s`.'), $OldName, $NewName));

      return TRUE;
   }

   /**
    * Specifies the name of the view to create or modify.
    *
    * @param string $Name The name of the view.
    * @param string $Query The actual query to create as the view. Typically
    * this can be generated with the $Database object.
    */
   public function View($Name, $SQL) {
      if(is_string($SQL)) {
         $SQLString = $SQL;
         $SQL = NULL;
      } else {
         $SQLString = $SQL->GetSelect();
      }
      
      $Result = $this->Query('create or replace view '.$this->_DatabasePrefix.$Name." as \n".$SQLString);
      if(!is_null($SQL)) {
         $SQL->Reset();
      }
   }

   /**
    * Creates the table defined with $this->Table() and $this->Column().
    */
   protected function _Create() {
      $PrimaryKey = array();
      $UniqueKey = array();
      $FullTextKey = array();
      $Keys = '';
      $Sql = '';

      foreach ($this->_Columns as $ColumnName => $Column) {
         if ($Sql != '')
            $Sql .= ',';

         $Sql .= "\n".$this->_DefineColumn($Column);

         $ColumnKeyTypes = (array)$Column->KeyType;

         foreach ($ColumnKeyTypes as $ColumnKeyType) {
            if ($ColumnKeyType == 'primary')
               $PrimaryKey[] = $ColumnName;
            elseif ($ColumnKeyType == 'key')
               $Keys .= ",\nkey `".Gdn_Format::AlphaNumeric('`FK_'.$this->_TableName.'_'.$ColumnName).'` (`'.$ColumnName.'`)';
            elseif ($ColumnKeyType == 'index')
               $Keys .= ",\nindex `".Gdn_Format::AlphaNumeric('`IX_'.$this->_TableName.'_'.$ColumnName).'` (`'.$ColumnName.'`)';
            elseif ($ColumnKeyType == 'unique')
               $UniqueKey[] = $ColumnName;
            elseif ($ColumnKeyType == 'fulltext')
               $FullTextKey[] = $ColumnName;
         }
      }
      // Build primary keys
      if (count($PrimaryKey) > 0)
         $Keys .= ",\nprimary key (`".implode('`, `', $PrimaryKey)."`)";
      // Build unique keys.
      if (count($UniqueKey) > 0)
         $Keys .= ",\nunique index `".Gdn_Format::AlphaNumeric('UX_'.$this->_TableName).'` (`'.implode('`, `', $UniqueKey)."`)";
      // Build full text index.
      if (count($FullTextKey) > 0)
         $Keys .= ",\nfulltext index `".Gdn_Format::AlphaNumeric('TX_'.$this->_TableName).'` (`'.implode('`, `', $FullTextKey)."`)";

      $Sql = 'create table `'.$this->_DatabasePrefix.$this->_TableName.'` ('
         .$Sql
         .$Keys
      ."\n)";
      
      if (!is_null($this->_TableStorageEngine))
         $Sql .= ' ENGINE='.$this->_TableStorageEngine;

      if ($this->_CharacterEncoding !== FALSE && $this->_CharacterEncoding != '')
         $Sql .= ' default character set '.$this->_CharacterEncoding;
         
      if (array_key_exists('Collate', $this->Database->ExtendedProperties)) {
         $Sql .= ' collate ' . $this->Database->ExtendedProperties['Collate'];
      }
      
      $Sql .= ';';

      $Result = $this->Query($Sql);
      $this->Reset();
      
      return $Result;
   }
   
   protected function _IndexSql($Columns, $KeyType = FALSE) {
      $Result = array();
      $Keys = array();
      $Prefixes = array('key' => 'FK_', 'index' => 'IX_', 'unique' => 'UX_', 'fulltext' => 'TX_');
      
      // Gather the names of the columns.
      foreach ($Columns as $ColumnName => $Column) {
         $ColumnKeyTypes = (array)$Column->KeyType;

         foreach ($ColumnKeyTypes as $ColumnKeyType) {
            if(!$ColumnKeyType || ($KeyType && $KeyType != $ColumnKeyType))
               continue;

            if($ColumnKeyType == 'key' || $ColumnKeyType == 'index') {
               $Name = $Prefixes[$ColumnKeyType].$this->_TableName.'_'.$ColumnName;
               $Result[$Name] = $ColumnKeyType." $Name (`$ColumnName`)";
            } else {
               // This is a multi-column key type so just collect the column name.
               $Keys[$ColumnKeyType][] = $ColumnName;
            }
         }
      }
      
      // Make the multi-column keys into sql statements.
      foreach($Keys as $KeyType2 => $Columns) {
         if($KeyType2 == 'primary') {
            $Result['PRIMARY'] = 'primary key (`'.implode('`, `', $Columns).'`)';
         } else {
            $Name = $Prefixes[$KeyType2].$this->_TableName;
            $Result[$Name] = "$KeyType2 index $Name (`".implode('`, `', $Columns).'`)';
         }
      }
      
      return $Result;
   }
   
   protected function _IndexSqlDb() {
      // We don't want this to be captured so send it directly.
      $Data = $this->Database->Query('show indexes from '.$this->_DatabasePrefix.$this->_TableName);
      
      $Result = array();   
      foreach($Data as $Row) {
         if(array_key_exists($Row->Key_name, $Result)) {
            $Result[$Row->Key_name] .= ', `'.$Row->Column_name.'`';
         } else {
            switch(strtoupper(substr($Row->Key_name, 0, 2))) {
               case 'PR':
                  $Type = 'primary key';
                  break;
               case 'FK':
                  $Type = 'key '.$Row->Key_name;
                  break;
               case 'IX':
                  $Type = 'index '.$Row->Key_name;
                  break;
               case 'UX':
                  $Type = 'unique index '.$Row->Key_name;
                  break;
               case 'TX':
                  $Type = 'fulltext index '.$Row->Key_name;
                  break;
               default:
                  // Try and guess the index type.
                  if(strcasecmp($Row->Index_type, 'fulltext') == 0)
                     $Type = 'fulltext index '.$Row->Key_name;
                  elseif($Row->Non_unique)
                     $Type = 'index '.$Row->Key_name;
                  else
                     $Type = 'unique index '.$Row->Key_name;

                  break;
            }
            $Result[$Row->Key_name] = $Type.' (`'.$Row->Column_name.'`';
         }
      }
      
      // Cap off the sql.
      foreach($Result as $Name => $Sql) {
         $Result[$Name] .= ')';
      }
      
      return $Result;
   }

   /**
    * Modifies $this->Table() with the columns specified with $this->Column().
    *
    * @param boolean $Explicit If TRUE, this method will remove any columns from the table that were not
    * defined with $this->Column().
    */
   protected function _Modify($Explicit = FALSE) {
      $Px = $this->_DatabasePrefix;
      $AdditionalSql = array(); // statements executed at the end

      // Returns an array of schema data objects for each field in the specified
      // table. The returned array of objects contains the following properties:
      // Name, PrimaryKey, Type, AllowNull, Default, Length, Enum.
      $ExistingColumns = $this->ExistingColumns();

      // 1. Remove any unnecessary columns if this is an explicit modification
      if ($Explicit) {
         // array_diff returns values from the first array that aren't present
         // in the second array. In this example, all columns currently in the
         // table that are NOT in $this->_Columns.
         $RemoveColumns = array_diff(array_keys($ExistingColumns), array_keys($this->_Columns));
         foreach ($RemoveColumns as $Column) {
            $this->DropColumn($Column);
         }
      }

      // Prepare the alter query
      $AlterSqlPrefix = 'alter table `'.$this->_DatabasePrefix.$this->_TableName.'` ';
      
      // 2. Alter the table storage engine
      if(!is_null($this->_TableStorageEngine)) {
			$CurrentEngine = $this->Database->Query("show table status where name = '".$this->_DatabasePrefix.$this->_TableName."'")->Value('Engine');

			if(strcasecmp($CurrentEngine, $this->_TableStorageEngine)) {
				$EngineQuery = $AlterSqlPrefix.' engine = '.$this->_TableStorageEngine;
				if (!$this->Query($EngineQuery))
					throw new Exception(sprintf(T('Failed to alter the storage engine of table `%1$s` to `%2$s`.'), $this->_DatabasePrefix.$this->_TableName, $this->_TableStorageEngine));
			}
      }
      
      // 3. Add new columns & modify existing ones

      // array_diff returns values from the first array that aren't present in
      // the second array. In this example, all columns in $this->_Columns that
      // are NOT in the table.
      $PrevColumnName = FALSE;
      foreach ($this->_Columns as $ColumnName => $Column) {
         if (!array_key_exists($ColumnName, $ExistingColumns)) {

            // This column name is not in the existing column collection, so add the column
            $AddColumnSql = $AlterSqlPrefix.' add '.$this->_DefineColumn(GetValue($ColumnName, $this->_Columns));
            if($PrevColumnName !== FALSE)
               $AddColumnSql .= " after `$PrevColumnName`";

            if (!$this->Query($AddColumnSql))
               throw new Exception(sprintf(T('Failed to add the `%1$s` column to the `%1$s` table.'), $Column, $this->_DatabasePrefix.$this->_TableName));
         } else {
				$ExistingColumn = $ExistingColumns[$ColumnName];

            $ExistingColumnDef = $this->_DefineColumn($ExistingColumn);
            $ColumnDef = $this->_DefineColumn($Column);
            $Comment = "/* Existing: $ExistingColumnDef, New: $ColumnDef */\n";
            
				if ($ExistingColumnDef != $ColumnDef) {  //$Column->Type != $ExistingColumn->Type || $Column->AllowNull != $ExistingColumn->AllowNull || ($Column->Length != $ExistingColumn->Length && !in_array($Column->Type, array('tinyint', 'smallint', 'int', 'bigint', 'float', 'double')))) {
               // The existing & new column types do not match, so modify the column
					if (!$this->Query($Comment.$AlterSqlPrefix.' change `'.$ColumnName.'` '.$this->_DefineColumn(GetValue($ColumnName, $this->_Columns))))
						throw new Exception(sprintf(T('Failed to modify the data type of the `%1$s` column on the `%2$s` table.'),
                     $ColumnName,
                     $this->_DatabasePrefix.$this->_TableName));

               // Check for a modification from an enum to an int.
               if(strcasecmp($ExistingColumn->Type, 'enum') == 0 && in_array(strtolower($Column->Type), $this->Types('int'))) {
                  $Sql = "update `$Px{$this->_TableName}` set `$ColumnName` = case `$ColumnName`";
                  foreach($ExistingColumn->Enum as $Index => $NewValue) {
                     $OldValue = $Index + 1;
                     
                     if(!is_numeric($NewValue))
                        continue;
                     $NewValue = (int)$NewValue;

                     $Sql .= " when $OldValue then $NewValue";
                  }
                  $Sql .= " else `$ColumnName` end";
                  $Description = "Update {$this->_TableName}.$ColumnName enum values to {$Column->Type}";
                  $AdditionalSql[$Description] = $Sql;

               }
				}
         }
         $PrevColumnName = $ColumnName;
      }
      
      // 4. Update Indexes
      $Indexes = $this->_IndexSql($this->_Columns);
      $IndexesDb = $this->_IndexSqlDb();

      $IndexSql = array();
      // Go through the indexes to add or modify.
      foreach($Indexes as $Name => $Sql) {
         if(array_key_exists($Name, $IndexesDb)) {
            if($Indexes[$Name] != $IndexesDb[$Name]) {
               if($Name == 'PRIMARY')
                  $IndexSql[$Name] = $AlterSqlPrefix."drop primary key;\n";
               else
                  $IndexSql[$Name] = $AlterSqlPrefix.'drop index '.$Name.";\n";
               $IndexSql[$Name] .= $AlterSqlPrefix."add $Sql;\n";
            }
            unset($IndexesDb[$Name]);
         } else {
            $IndexSql[$Name] = $AlterSqlPrefix."add $Sql;\n";   
         }
      }
      // Go through the indexes to drop.
      if($Explicit) {
         foreach($IndexesDb as $Name => $Sql) {
            if($Name == 'PRIMARY')
               $IndexSql[$Name] = $AlterSqlPrefix."drop primary key;\n";
            else
               $IndexSql[$Name] = $AlterSqlPrefix.'drop index '.$Name.";\n";
         }
      }
      
      // Modify all of the indexes.
      foreach($IndexSql as $Name => $Sql) {
         if(!$this->Query($Sql))
            throw new Exception(sprintf(T('Error.ModifyIndex', 'Failed to add or modify the `%1$s` index in the `%2$s` table.'), $Name, $this->_TableName));
      }

      // Run any additional Sql.
      foreach($AdditionalSql as $Description => $Sql) {
         if(!$this->Query($Sql))
            throw new Exception("Error modifying table: {$Description}.");
      }

      $this->Reset();
      return TRUE;
   }

   /**
    * Undocumented method.
    *
    * @param string $Column
    * @todo This method and $Column need descriptions.
    */
   protected function _DefineColumn($Column) {
      if (!is_array($Column->Type) && !in_array($Column->Type, array('tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'char', 'varchar', 'varbinary', 'date', 'datetime', 'mediumtext', 'text', 'decimal', 'float', 'double', 'enum', 'timestamp')))
         throw new Exception(sprintf(T('The specified data type (%1$s) is not accepted for the MySQL database.'), $Column->Type));
      
      $Return = '`'.$Column->Name.'` '.$Column->Type;
      
      $LengthTypes = $this->Types('length');
      if ($Column->Length != '' && in_array(strtolower($Column->Type), $LengthTypes)) {
         if($Column->Precision != '')
            $Return .= '('.$Column->Length.', '.$Column->Precision.')';
         else
            $Return .= '('.$Column->Length.')';
      }
      if (property_exists($Column, 'Unsigned') && $Column->Unsigned) {
         $Return .= ' unsigned';
      }

      if (is_array($Column->Enum))
         $Return .= "('".implode("','", $Column->Enum)."')";

      if (!$Column->AllowNull)
         $Return .= ' not null';

      if (!is_null($Column->Default) && strcasecmp($Column->Type, 'timestamp') != 0)
         $Return .= " default ".self::_QuoteValue($Column->Default);

      if ($Column->AutoIncrement)
         $Return .= ' auto_increment';

      return $Return;
   }
   
   protected static function _QuoteValue($Value) {
      if(is_numeric($Value)) {
         return $Value;
      } else if(is_bool($Value)) {
         return $Value ? '1' : '0';
      } else {
         return "'".str_replace("'", "''", $Value)."'";
      }
   }
}