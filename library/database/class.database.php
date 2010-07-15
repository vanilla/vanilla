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
 * The Database object contains connection and engine information for a single database.
 * It also allows a database to execute string sql statements against that database.
 *
 * @author Todd Burry
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Database
 */

require_once(dirname(__FILE__).DS.'class.dataset.php');

class Gdn_Database {
   /// CONSTRUCTOR ///

   /** @param mixed $Config The configuration settings for this object.
    *  @see Database::Init()
    */
   public function __construct($Config = NULL) {
      $this->ClassName = get_class($this);
      $this->Init($Config);
   }
   
   /// PROPERTIES ///
   
   /** @var string The instance name of this class or the class that inherits from this class. */
   public $ClassName;
   
   private $_CurrentResultSet;
   
   /** @var PDO The connectio to the database. */
   protected $_Connection = NULL;
   
   
   protected $_SQL = NULL;
   
   protected $_Structure = NULL;
   
   /** Get the PDO connection to the database.
    * @return PDO The connection to the database.
    */
   public function Connection() {
      if(!is_object($this->_Connection)) {
         try {
            $this->_Connection = new PDO(strtolower($this->Engine) . ':' . $this->Dsn, $this->User, $this->Password, $this->ConnectionOptions);
            if($this->ConnectionOptions[1002])
               $this->Query($this->ConnectionOptions[1002]);
         } catch (Exception $ex) {
            trigger_error(ErrorMessage('An error occurred while attempting to connect to the database', $this->ClassName, 'Connection', $ex->getMessage()), E_USER_ERROR);
         }
      }
      
      return $this->_Connection;
   }
   
   /** @var array The connection options passed to the PDO constructor **/
   public $ConnectionOptions;
   
   /** @var string The prefix to all database tables. */
   public $DatabasePrefix;
   
   /** @var array Extented properties that a specific driver can use. **/
   public $ExtendedProperties;
   
   /** $var bool Whether or not the connection is in a transaction. **/
   protected $_InTransaction = FALSE;
   
   /** @var string The PDO dsn for the database connection.
    *  Note: This does NOT include the engine before the dsn.
    */
   public $Dsn;
   
   /** @var string The name of the database engine for this class. */
   public $Engine;
   
   /** @var string The password to the database. */
   public $Password;
   
   /** @var string The username connecting to the database. */
   public $User;
   
   /// METHODS ///
   
   /**
    * Begin a transaction on the database.
    */
   public function BeginTransaction() {
      if(!$this->_InTransaction)
         $this->_InTransaction = $this->Connection()->beginTransaction();
   }
   
   public function CloseConnection() {
      if (!Gdn::Config('Database.PersistentConnection')) {
         $this->CommitTransaction();
         $this->_Connection = NULL;
      }
   }
   
   /**
    * Commit a transaction on the database.
    */
   public function CommitTransaction() {
      if($this->_InTransaction) {
         $this->_InTransaction = !$this->Connection()->commit();
      }
   }
	
	/**
	 * Properly quotes and escapes a expression for an sql string.
	 * @param mixed $Expr The expression to quote.
	 * @return string The quoted expression.
	 */
	public function QuoteExpression($Expr) {
		if(is_null($Expr)) {
			return 'NULL';
		} elseif(is_string($Expr)) {
			return '\''.str_replace('\'', '\\\'', $Expr).'\'';
		} elseif(is_object($Expr)) {
			return '?OBJECT?';
		} else {
			return $Expr;
		}
	}
   
   /**
    * Initialize the properties of this object.
    * @param mixed $Config The database is instantiated differently depending on the type of $Config:
    * - <b>null</b>: The database stored in the factory location Gdn:AliasDatabase will be used.
    * - <b>string</b>: The name of the configuration section to get the connection information from.
    * - <b>array</b>: The database properties will be set from the array. The following items can be in the array:
    *   - <b>Engine</b>: Required. The name of the database engine (MySQL, pgsql, sqlite, odbc, etc.
    *   - <b>Dsn</b>: Optional. The dsn for the connection. If the dsn is not supplied then the connectio information below must be supplied.
    *   - <b>Host, Dbname</b>: Optional. The individual database connection options that will be build into a dsn.
    *   - <b>User</b>: The username to connect to the datbase.
    *   - <b>Password</b>: The password to connect to the database.
    *   - <b>ConnectionOptions</b>: Other PDO connection attributes.
    */
   public function Init($Config = NULL) {
      if(is_null($Config))
         $Config = Gdn::Config('Database');
      elseif(is_string($Config))
         $Config = Gdn::Config($Config);
         
      $DefaultConfig = Gdn::Config('Database');
         
      $this->Engine = ArrayValue('Engine', $Config, $DefaultConfig['Engine']);
      $this->User = ArrayValue('User', $Config, $DefaultConfig['User']);
      $this->Password = ArrayValue('Password', $Config, $DefaultConfig['Password']);
      $this->ConnectionOptions = ArrayValue('ConnectionOptions', $Config, $DefaultConfig['ConnectionOptions']);
      $this->DatabasePrefix = ArrayValue('DatabasePrefix', $Config, ArrayValue('Prefix', $Config, $DefaultConfig['DatabasePrefix']));
      $this->ExtendedProperties = ArrayValue('ExtendedProperties', $Config, array());
      
      if(array_key_exists('Dsn', $Config)) {
         // Get the dsn from the property.
         $Dsn = $Config['Dsn'];
      } else {   
         $Host = ArrayValue('Host', $Config, ArrayValue('Host', $DefaultConfig, ''));
         if(array_key_exists('Dbname', $Config))
            $Dbname = $Config['Dbname'];
         elseif(array_key_exists('Name', $Config))
            $Dbname = $Config['Name'];
         elseif(array_key_exists('Dbname', $DefaultConfig))
            $Dbname = $DefaultConfig['Dbname'];
         elseif(array_key_exists('Name', $DefaultConfig))
            $Dbname = $DefaultConfig['Name'];
         // Was the port explicitly defined in the config?
         $Port = ArrayValue('Port', $Config, ArrayValue('Port', $DefaultConfig, ''));
         
         if(!isset($Dbname)) {
            $Dsn = $DefaultConfig['Dsn'];
         } else {
            if(empty($Port)) {
               // Was the port explicitly defined with the host name? (ie. 127.0.0.1:3306)
               $Host = explode(':', $Host);
               $Port = count($Host) == 2 ? $Host[1] : '';
               $Host = $Host[0];
            }
            
            if(empty($Port)) {
               $Dsn = sprintf('host=%s;dbname=%s;', $Host, $Dbname);
            } else {
               $Dsn = sprintf('host=%s;port=%s;dbname=%s;', $Host, $Port, $Dbname);
            }
         }
      }
      
      $this->Dsn = $Dsn;
   }
   
   /**
    * Executes a string of SQL. Returns a @@DataSet object.
    *
    * @param string $Sql A string of SQL to be executed.
    * @param array $InputParameters An array of values with as many elements as there are bound parameters in the SQL statement being executed.
    */
   public function Query($Sql, $InputParameters = NULL, $Options = array()) {
		if (isset($Options['Event'])) {
			// TODO: Raise an event so the query can be overridden.
         
		}
		
      if ($Sql == '')
         trigger_error(ErrorMessage('Database was queried with an empty string.', $this->ClassName, 'Query'), E_USER_ERROR);

      // Run the Query
      if (!is_null($InputParameters) && count($InputParameters) > 0) {
         // Make sure other unbufferred queries are not open
         if (is_object($this->_CurrentResultSet)) {
            $this->_CurrentResultSet->Result();
            $this->_CurrentResultSet->FreePDOStatement(FALSE);
         }

         $PDOStatement = $this->Connection()->prepare($Sql);

         if (!is_object($PDOStatement)) {
            trigger_error(ErrorMessage('PDO Statement failed to prepare', $this->ClassName, 'Query', $this->GetPDOErrorMessage($this->Connection()->errorInfo())), E_USER_ERROR);
         } else if ($PDOStatement->execute($InputParameters) === FALSE) {
            trigger_error(ErrorMessage($this->GetPDOErrorMessage($PDOStatement->errorInfo()), $this->ClassName, 'Query', $Sql), E_USER_ERROR);
         }
      } else {
         $PDOStatement = $this->Connection()->query($Sql);
      }

      if ($PDOStatement === FALSE) {
         trigger_error(ErrorMessage($this->GetPDOErrorMessage($this->Connection()->errorInfo()), $this->ClassName, 'Query', $Sql), E_USER_ERROR);
      }

      $ReturnType = GetValue('ReturnType', $Options);
      
      // Did this query modify data in any way?
      if ($ReturnType == 'ID' || preg_match('/^\s*"?(insert)\s+/i', $Sql)) {
         $this->_CurrentResultSet = $this->Connection()->lastInsertId();
      } else {
         if ($ReturnType == 'DataSet' || !preg_match('/^\s*"?(update|delete|replace|create|drop|load data|copy|alter|grant|revoke|lock|unlock)\s+/i', $Sql)) {
            // Create a DataSet to manage the resultset
            $this->_CurrentResultSet = new Gdn_DataSet();
            $this->_CurrentResultSet->Connection = $this->Connection();
            $this->_CurrentResultSet->PDOStatement($PDOStatement);
         }
      }
      
      return $this->_CurrentResultSet;
   }
   
   public function RollbackTransaction() {
      if($this->_InTransaction) {
         $this->_InTransaction = !$this->Connection()->rollBack();
      }
   }
   public function GetPDOErrorMessage($ErrorInfo) {
      $ErrorMessage = '';
      if (is_array($ErrorInfo)) {
         if (count($ErrorInfo) >= 2)
            $ErrorMessage = $ErrorInfo[2];
         elseif (count($ErrorInfo) >= 1)
            $ErrorMessage = $ErrorInfo[0];
      } elseif (is_string($ErrorInfo)) {
         $ErrorMessage = $ErrorInfo;
      }

      return $ErrorMessage;
   }
   
   /**
    * Get the database driver class for the database.
    * @return Gdn_SQLDriver The database driver class associated with this database.
    */
   public function SQL() {
      if(is_null($this->_SQL)) {
         $Name = $this->Engine . 'Driver';
         $this->_SQL = Gdn::Factory($Name);
         $this->_SQL->Database = $this;
      }
      
      return $this->_SQL;
   }
   
   /**
    * Get the database structure class for this database.
    * 
    * @return Gdn_DatabaseStructure The database structure class for this database.
    */
   public function Structure() {
      if(is_null($this->_Structure)) {
         $Name = $this->Engine . 'Structure';
         $this->_Structure = Gdn::Factory($Name);
         $this->_Structure->Database = $this;
      }
      
      return $this->_Structure;
   }
}