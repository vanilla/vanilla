<?php if (!defined('APPLICATION')) exit();

/**
 * Database manager
 *
 * The Database object contains connection and engine information for a single database.
 * It also allows a database to execute string sql statements against that database.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Database {
   /// CONSTRUCTOR ///

   /** @param mixed $Config The configuration settings for this object.
    *  @see Database::Init()
    */
   public function __construct($Config = NULL) {
      $this->ClassName = get_class($this);
      $this->Init($Config);
      $this->ConnectRetries = 1;
   }

   /// PROPERTIES ///

   /** @var string The instance name of this class or the class that inherits from this class. */
   public $ClassName;

   private $_CurrentResultSet;

   /** @var PDO The connectio to the database. */
   protected $_Connection = NULL;

   protected $_IsPersistent = FALSE;

   /** @var PDO The connection to the slave database. */
   protected $_Slave = NULL;

   /** @var array The slave connection settings. */
   protected $_SlaveConfig = NULL;

   protected $_SQL = NULL;

   protected $_Structure = NULL;

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

   /**
    * @var array Information about the last query.
    */
   public $LastInfo = array();

   /** @var string The password to the database. */
   public $Password;

   /** @var string The username connecting to the database. */
   public $User;

   /** @var int Number of retries when the db has gone away. */
   public $ConnectRetries;

   /// METHODS ///

   /**
    * Begin a transaction on the database.
    */
   public function BeginTransaction() {
      if (!$this->_InTransaction)
         $this->_InTransaction = $this->Connection()->beginTransaction();
   }

   /** Get the PDO connection to the database.
    * @return PDO The connection to the database.
    */
   public function Connection() {
      $this->_IsPersistent = val(PDO::ATTR_PERSISTENT, $this->ConnectionOptions, FALSE);
      if($this->_Connection === NULL) {
         $this->_Connection = $this->NewPDO($this->Dsn, $this->User, $this->Password);
      }

      return $this->_Connection;
   }

   public function CloseConnection() {
      if (!$this->_IsPersistent) {
         $this->CommitTransaction();
         $this->_Connection = NULL;
         $this->_Slave = NULL;
      }
   }

   /**
    * Close connection regardless of persistence
    *
    */
   public function ForceCloseConnection() {
      $this->_Connection = NULL;
   }

   /**
    * Hook for cleanup via Gdn_Factory
    *
    */
   public function Cleanup() {
      $this->CloseConnection();
   }

   /**
    * Commit a transaction on the database.
    */
   public function CommitTransaction() {
      if ($this->_InTransaction)
         $this->_InTransaction = !$this->Connection()->commit();
   }

   protected function NewPDO($Dsn, $User, $Password) {
      try {
         $PDO = new PDO(strtolower($this->Engine).':'.$Dsn, $User, $Password, $this->ConnectionOptions);
         $PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);

         if ($this->ConnectionOptions[1002])
            $PDO->query($this->ConnectionOptions[1002]);

         // We only throw exceptions during connect
         $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
      } catch (Exception $ex) {
         $Timeout = FALSE;
         if ($ex->getCode() == '2002' && preg_match('/Operation timed out/i', $ex->getMessage()))
            $Timeout = TRUE;
         if ($ex->getCode() == '2003' && preg_match("/Can't connect to MySQL/i", $ex->getMessage()))
            $Timeout = TRUE;

         if ($Timeout)
            throw new Exception(ErrorMessage('Timeout while connecting to the database', $this->ClassName, 'Connection', $ex->getMessage()), 504);

         throw new Exception(ErrorMessage('An error occurred while attempting to connect to the database', $this->ClassName, 'Connection', $ex->getMessage()), 500);
      }

      return $PDO;
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
      if (is_null($Config))
         $Config = array();
      if (is_null($DefaultConfig))
         $DefaultConfig = array();

      // Make sure DefaultConfig has all the keys we need
      $DefaultConfig = array_merge(array(
         'Engine'             => null,
         'Host'               => '',
         'User'               => null,
         'Password'           => null,
         'ConnectionOptions'  => null,
         'DatabasePrefix'     => null,
         'Prefix'             => null
      ), $DefaultConfig);

      $Config = array_merge($DefaultConfig, $Config);

      $this->Engine = val('Engine', $Config);
      $this->User = val('User', $Config);
      $this->Password = val('Password', $Config);
      $this->ConnectionOptions = val('ConnectionOptions', $Config);
      $this->DatabasePrefix = val('DatabasePrefix', $Config, val('Prefix', $Config));
      $this->ExtendedProperties = val('ExtendedProperties', $Config, array());

      if (array_key_exists('Dsn', $Config)) {
         // Get the dsn from the property.
         $Dsn = $Config['Dsn'];
      } else {
         $Host = val('Host', $Config);
         if(array_key_exists('Dbname', $Config))
            $Dbname = $Config['Dbname'];
         elseif(array_key_exists('Name', $Config))
            $Dbname = $Config['Name'];
         elseif(array_key_exists('Dbname', $DefaultConfig))
            $Dbname = $DefaultConfig['Dbname'];
         elseif(array_key_exists('Name', $DefaultConfig))
            $Dbname = $DefaultConfig['Name'];

         // Was the port explicitly defined in the config?
         $Port = val('Port', $Config, val('Port', $DefaultConfig, ''));

         if(!isset($Dbname)) {
            $Dsn = val('Dsn', $DefaultConfig);
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

      if (array_key_exists('Slave', $Config)) {
         $this->_SlaveConfig = $Config['Slave'];
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
      $this->LastInfo = array();

      if ($Sql == '')
         trigger_error(ErrorMessage('Database was queried with an empty string.', $this->ClassName, 'Query'), E_USER_ERROR);

      // Get the return type.
      if (isset($Options['ReturnType']))
         $ReturnType = $Options['ReturnType'];
      elseif (preg_match('/^\s*"?(insert)\s+/i', $Sql))
         $ReturnType = 'ID';
      elseif (!preg_match('/^\s*"?(update|delete|replace|create|drop|load data|copy|alter|grant|revoke|lock|unlock)\s+/i', $Sql))
         $ReturnType = 'DataSet';
      else
         $ReturnType = NULL;

      if (isset($Options['Cache'])) {
         // Check to see if the query is cached.
         $CacheKeys = (array)val('Cache',$Options,NULL);
         $CacheOperation = val('CacheOperation',$Options,NULL);
         if (is_null($CacheOperation)) {
            switch ($ReturnType) {
               case 'DataSet':
                  $CacheOperation = 'get';
                  break;
               case 'ID':
               case NULL:
                  $CacheOperation = 'remove';
                  break;
            }
         }

         switch ($CacheOperation) {
            case 'get':
               foreach ($CacheKeys as $CacheKey) {
                  $Data = Gdn::Cache()->Get($CacheKey);
               }

               // Cache hit. Return.
               if ($Data !== Gdn_Cache::CACHEOP_FAILURE)
                  return new Gdn_DataSet($Data);

               // Cache miss. Save later.
               $StoreCacheKey = $CacheKey;
               break;

            case 'increment':
            case 'decrement':
               $CacheMethod = ucfirst($CacheOperation);
               foreach ($CacheKeys as $CacheKey) {
                  $CacheResult = Gdn::Cache()->$CacheMethod($CacheKey);
               }
               break;

            case 'remove':
               foreach ($CacheKeys as $CacheKey) {
                  $Res = Gdn::Cache()->Remove($CacheKey);
               }
               break;
         }
      }

      // We will retry this query a few times if it fails.
      $tries = $this->ConnectRetries + 1;
      if ($tries < 1) {
         $tries = 1;
      }

      for ($try = 0; $try < $tries; $try++) {
         if (val('Type', $Options) == 'select' && val('Slave', $Options, NULL) !== FALSE) {
            $PDO = $this->Slave();
            $this->LastInfo['connection'] = 'slave';
         } else {
            $PDO = $this->Connection();
            $this->LastInfo['connection'] = 'master';
         }

         // Make sure other unbufferred queries are not open
         if (is_object($this->_CurrentResultSet)) {
            $this->_CurrentResultSet->Result();
            $this->_CurrentResultSet->FreePDOStatement(FALSE);
         }

         $PDOStatement = null;
         try {

            // Prepare / Execute
            if (!is_null($InputParameters) && count($InputParameters) > 0) {
               $PDOStatement = $PDO->prepare($Sql);

               if (!is_object($PDOStatement)) {
                  trigger_error(ErrorMessage('PDO Statement failed to prepare', $this->ClassName, 'Query', $this->GetPDOErrorMessage($PDO->errorInfo())), E_USER_ERROR);
               } else if ($PDOStatement->execute($InputParameters) === FALSE) {
                  trigger_error(ErrorMessage($this->GetPDOErrorMessage($PDOStatement->errorInfo()), $this->ClassName, 'Query', $Sql), E_USER_ERROR);
               }
            } else {
               $PDOStatement = $PDO->query($Sql);
            }

            if ($PDOStatement === FALSE) {
               list($state, $code, $message) = $PDO->errorInfo();

               // Detect mysql "server has gone away" and try to reconnect.
               if ($code == 2006 && $try < $tries) {
                  $this->closeConnection();
                  continue;
               } else {
                  throw new Gdn_UserException($message, $code);
               }
            }

            // If we get here then the pdo statement prepared properly.
            break;

         } catch (Gdn_UserException $uex) {
            trigger_error($uex->getMessage(), E_USER_ERROR);
         } catch (Exception $ex) {
            list($state, $code, $message) = $PDO->errorInfo();

            // If the error code is consistent with a disconnect, attempt to retry
            if ($code == 2006 && $try < $tries) {
               $this->closeConnection();
               continue;
            }

            trigger_error($message, E_USER_ERROR);
         }

      }

      // Did this query modify data in any way?
      if ($ReturnType == 'ID') {
         $this->_CurrentResultSet = $PDO->lastInsertId();
         if (is_a($PDOStatement, 'PDOStatement')) {
            $PDOStatement->closeCursor();
         }
      } else {
         if ($ReturnType == 'DataSet') {
            // Create a DataSet to manage the resultset
            $this->_CurrentResultSet = new Gdn_DataSet();
            $this->_CurrentResultSet->Connection = $PDO;
            $this->_CurrentResultSet->PDOStatement($PDOStatement);
         } elseif (is_a($PDOStatement, 'PDOStatement')) {
            $PDOStatement->closeCursor();
         }
      }

      if (isset($StoreCacheKey)) {
         if ($CacheOperation == 'get') {
            Gdn::Cache()->Store(
               $StoreCacheKey,
               (($this->_CurrentResultSet instanceof Gdn_DataSet) ? $this->_CurrentResultSet->ResultArray() : $this->_CurrentResultSet),
               val('CacheOptions', $Options, array())
            );
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
    * The slave connection to the database.
    * @return PDO
    */
   public function Slave() {
      if ($this->_Slave === NULL) {
         if (empty($this->_SlaveConfig)) {
            $this->_Slave = $this->Connection();
         } else {
            $this->_Slave = $this->NewPDO($this->_SlaveConfig['Dsn'], $this->_SlaveConfig['User'], $this->_SlaveConfig['Password']);
         }
      }

      return $this->_Slave;
   }

   /**
    * Get the database driver class for the database.
    * @return Gdn_SQLDriver The database driver class associated with this database.
    */
   public function SQL() {
      if(is_null($this->_SQL)) {
         $Name = $this->Engine . 'Driver';
         $this->_SQL = Gdn::Factory($Name);
         if (!$this->_SQL)
            $this->_SQL = new stdClass();
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
      if (is_null($this->_Structure)) {
         $Name = $this->Engine . 'Structure';
         $this->_Structure = Gdn::Factory($Name);
         $this->_Structure->Database = $this;
      }

      return $this->_Structure;
   }
}
