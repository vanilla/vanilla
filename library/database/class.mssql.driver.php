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
 * class.mssql.driver.php needs a description.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Database
 */

require_once(dirname(__FILE__).DS.'class.sqldriver.php');

/**
 * The MSSQLDriver class is a Microsoft SQL Server-specific interface for
 * manipulating database information.
 */
class Gdn_MSSQLDriver extends Gdn_SQLDriver {

   /**
    * Because SQL Server does not allow a limit statement, we need to store any
    * specified offset so unnecessary records can be skipped later.
    *
    * @var int
    */
   protected $_Offset = 0;

// =============================================================================
// SECTION 1. STRING SAFETY, PARSING, AND MANIPULATION.
// =============================================================================

   /**
    * Takes a string of SQL and adds backticks if necessary.
    *
    * @param mixed $String The string (or array of strings) of SQL to be escaped.
    * @param boolean $FirstWordOnly Should the function only escape the first word?
    */
   public function EscapeSql($String, $FirstWordOnly = FALSE) {
      if (is_array($String)) {
         $EscapedArray = array();

         foreach ($String as $k => $v) {
            $EscapedArray[$this->EscapeSql($k)] = $this->EscapeSql($v, $FirstWordOnly);
         }

         return $EscapedArray;
      }

      // This function may get "item1 item2" as a string, and so
      // we may need "`item1` `item2`" and not "`item1 item2`"
      if (ctype_alnum($String) === FALSE) {
         if (strpos($String, '.') !== FALSE) {
            $MungedAliases = implode('.', array_keys($this->_AliasMap)).'.';
            $TableName =  substr($String, 0, strpos($String, '.')+1);
            // If the "TableName" isn't found in the alias list, apply the database prefix to it
            $String = (strpos($MungedAliases, $String) !== FALSE) ? $String = $String : $this->DatabasePrefix.$String;
         }

         // This function may get "field >= 1", and need it to return "[field] >= 1"
         $LeftBound = ($FirstWordOnly === TRUE) ? '' : '|\s|\(';

         $String = preg_replace('/(^'.$LeftBound.')([\w\d\-\_]+?)(\s|\)|$)/iS', "$1[$2]$3", $String);
      } else {
         return "[{$String}]";
      }

      $Exceptions = array('as', '/', '-', '%', '+', '*');

      foreach ($Exceptions as $Exception) {
         if (stristr($String, " [{$Exception}] ") !== FALSE)
            $String = preg_replace('/ \[('.preg_quote($Exception).')\] /i', ' $1 ', $String);
      }
      return $String;
   }
   
   public function EscapeIdentifier($RefExpr) {
      $Tokens = $this->_GetIdentifierTokens($RefExpr);
      $Result = '';
      foreach($Token as $Tokens) {
         if(substr($Token, 0, 1) == '`') {
            $Token = substr($Token, 1, strlen($Token) - 2);
            $Token = preg_replace(']', ']]');
            $Token = '[' . $Token . ']';
         }
         $Result .= $Token;
      }
      return $Result;
   }

// =============================================================================
// SECTION 2. DATABASE ENGINE SPECIFIC QUERYING.
// =============================================================================

   /**
    * Returns a platform-specific query to fetch column data from $Table.
    *
    * @param string $Table The name of the table to fetch column data from.
    */
   public function FetchColumnSql($Table) {
      return "select c.*, k.CONSTRAINT_NAME, columnproperty(object_id(c.table_name), c.column_name, 'IsIdentity') as [IDENTITY]
         from information_schema.columns c
         left join information_schema.key_column_usage k
            on c.table_catalog = k.table_catalog
            and c.table_schema = k.table_schema
            and c.table_name = k.table_name
            and c.column_name = k.column_name
         where c.table_catalog = '".$this->DatabaseName()."'
            and c.table_name = '".$Table."'";
   }

   /**
    * Returns a platform-specific query to fetch table names.
    *
    * @param boolean $LimitToPrefix Should the query be limited to tables that have $this->DatabasePrefix ?
    */
   public function FetchTableSql($LimitToPrefix = FALSE) {
      $Sql = "select * from information_schema.tables where table_catalog = '".$this->FormatTableName($this->DatabaseName())."'";

      if ($LimitToPrefix !== FALSE && $this->DatabasePrefix != '')
         $Sql .= " and table_name like ".$this->Connection()->quote($this->DatabasePrefix.'%');

      return $Sql;
   }

   /**
    * Returns an array of schema data objects for each field in the specified
    * table. The returned array of objects contains the following properties:
    * Name, PrimaryKey, Type, AllowNull, Default, Length, Enum.
    *
    *
    * @param string $Table The name of the table to get schema data for.
    */
   public function FetchTableSchema($Table) {
      // Format the table name.
      $Table = $this->DatabasePrefix.$Table;
      $DataSet = $this->Query($this->FetchColumnSql($Table));
      $Schema = array();
      foreach ($DataSet->Result() as $Field) {
         $Object = new Gdn_ShellClass();
         $Object->Name = $Field->COLUMN_NAME;
         $Object->PrimaryKey = (strlen($Field->CONSTRAINT_NAME) > 3 && strtolower(substr($Field->CONSTRAINT_NAME, 0, 3)) == 'pk_') ? TRUE : FALSE;
         $Object->Type = $Field->DATA_TYPE;
         $Object->AllowNull = strtolower($Field->IS_NULLABLE) == 'no' ? FALSE : TRUE;
         $Object->Default = $Field->COLUMN_DEFAULT;
         $Object->Length = $Field->CHARACTER_MAXIMUM_LENGTH;
         $Object->Enum = FALSE;
         $Object->AutoIncrement = $Field->IDENTITY == 1 ? TRUE : FALSE;
         $Schema[$Field->COLUMN_NAME] = $Object;
      }

      return $Schema;
   }

   /**
    * Returns a string of SQL that retrieves the database engine version in the
    * fieldname "version".
    */
   public function FetchVersionSql() {
      return "select serverproperty('productversion')";
   }

   /**
    * Takes a table name and makes sure it is formatted for this database
    * engine.
    *
    * @param string $Table The name of the table name to format.
    */
   public function FormatTableName($Table) {
      if (strpos($Table, '.') !== FALSE)
         $Table = '[' . str_replace('.', '].[', $Table) . ']';

      return $Table;
   }

   /**
    * Returns a delete statement for the specified table and the supplied
    * conditions.
    *
    * @param string $TableName The name of the table to delete from.
    * @param array $Wheres An array of where conditions.
    */
   public function GetDelete($TableName, $Wheres = array()) {
      $Conditions = '';

      if (count($Wheres) > 0) {
         $Conditions = 'where ';
         $Conditions .= implode("\n", $Wheres);

         // Close any where groups that were left open.
         $this->EndQuery();
         
         $this->_OpenWhereGroupCount = 0;

      }

      return "delete from ".$Table.$Conditions;
   }

   /**
    * Returns an insert statement for the specified $Table with the provided $Data.
    *
    * @param string $Table The name of the table to insert data into.
    * @param array $Data An associative array of FieldName => Value pairs that should be inserted
    * $Table.
    */
   public function GetInsert($Table, $Data) {
      if (!is_array($Data))
         trigger_error(ErrorMessage('The data provided is not in a proper format (Array).', 'MySQLDriver', '_GetInsert'), E_USER_ERROR);

      return 'insert into '.$this->FormatTableName($Table).' '
         ."\n(".implode(', ', array_keys($Data)).') '
         ."\nvalues (".implode(', ', array_values($Data)).')';
   }

   /**
    * Adds a limit clause to the provided query for this database engine.
    *
    * @param string $Query The SQL string to which the limit statement should be appended.
    * @param int $Limit The number of records to limit the query to.
    * @param string $Offset The number of records to offset the query from.
    */
   public function GetLimit($Query, $Limit, $Offset) {
      $TotalOffset = $Limit + $Offset;
      $this->_Offset = $Offset;
      return preg_replace('/(^select (distinct)?)/i', '\\1 top '.$TotalOffset.' ', $Query);
   }

   /**
    * Returns an update statement for the specified table with the provided
    * $Data.
    *
    * @param string $Table The name of the table to updated data in.
    * @param array $Data An associative array of FieldName => Value pairs that should be inserted
    * $Table.
    * @param mixed $Where A where clause (or array containing multiple where clauses) to be applied
    * to the where portion of the update statement.
    */
   public function GetUpdate($Table, $Data, $Where) {
      if (!is_array($Data))
         trigger_error(ErrorMessage('The data provided is not in a proper format (Array).', 'MySQLDriver', '_GetUpdate'), E_USER_ERROR);

      $Sets = array();
      foreach($Data as $Field => $Value) {
         $Sets[] = $Field." = ".$Value;
      }

      $sql = 'update '.$this->FormatTableName($Table).' set '.implode(', ', $Sets);
      if (is_array($Where)) {
         $sql .= ' where '.implode(' ', $Where);

         // Close any where groups that were left open.
         for ($i = 0; $i < $this->_OpenWhereGroupCount; ++$i) {
            $sql .= ')';
         }
         $this->_OpenWhereGroupCount = 0;
      } else {
         $sql .= ' where '.$Where;
      }
      return $sql;
   }

   /**
    * Returns a truncate statement for this database engine.
    *
    * @param string $Table The name of the table to updated data in.
    */
   public function GetTruncate($Table) {
      return 'truncate table '.$this->FormatTableName($Table);
   }

   /**
    * Sets the character encoding for this database engine.
    *
    * @param string $Encoding Description needed.
    * @todo $encoding needs a description.
    */
   public function SetEncoding($Encoding) {
      // TODO: Don't know how to do this!
   }

   /**
    * Executes a string of SQL. Returns a @@DataSet object. This is an override
    * method because of SQL Server's lack of a LIMIT clause.
    *
    * @param string $Sql A string of SQL to be executed.
    */
   public function Query($Sql) {
      if ($Sql == '')
         trigger_error(ErrorMessage('Database was queried with an empty string.', $this->ClassName, 'Query'), E_USER_ERROR);

      // Make sure that we don't need to wipe out the pdo statement (and related named parameters)
      if ($this->_PDOStatement !== FALSE && $Sql != $this->_PDOStatement->queryString) {
         // echo '<div>Existing: '.$this->_PDOStatement->queryString.'</div>';
         // echo '<div>New: '.$Sql.'</div>';
         $this->_ResetPDOStatement();
      }

      // Save the query for debugging
      $this->Queries[] = $Sql;

      // Start the Query Timer
      $TimeStart = list($sm, $ss) = explode(' ', microtime());

      // Run the Query
      if (count($this->_NamedParameters) > 0) {
         if ($this->_PDOStatement === FALSE)
            $this->_PDOStatement = $this->Connection()->prepare($Sql);
         $this->_PDOStatement->execute($this->_NamedParameters);
      } else {
         $this->_PDOStatement = $this->Connection()->query($Sql);
      }

      if ($this->_PDOStatement === FALSE) {
         $Error = $this->Connection()->errorInfo();
         trigger_error(ErrorMessage($Error[2], $this->ClassName, 'Query', $Sql), E_USER_ERROR);
      }

      // Aggregate the query times
      $TimeEnd = list($em, $es) = explode(' ', microtime());
      $this->_ExecutionTime += ($em + $es) - ($sm + $ss);
      $this->QueryTimes[] = ($em + $es) - ($sm + $ss);

      // Did this query modify data in any way?
      if (preg_match('/^\s*"?(insert|update|delete|replace|create|drop|load data|copy|alter|grant|revoke|lock|unlock)\s+/i', $Sql))
         return TRUE;

      // Dispose of unneeded rows
      $i = 0;
      while ($i < $this->_Offset && $this->_PDOStatement->fetch()) {
         ++$i;
      }
      // Set the limit back to 0
      $this->_Offset = 0;

      // Create a DataSet to manage the resultset
      $ResultSet = new Gdn_DataSet();
      $ResultSet->Connection =& $this->_Connection; // Not using $this->Connection() because we know for a fact that the connection was created a few lines above.
      $ResultSet->PDOStatement($this->_PDOStatement);

      return $ResultSet;
   }
}
