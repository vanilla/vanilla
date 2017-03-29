<?php
/**
 * Database manager
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * The Database object contains connection and engine information for a single database.
 *
 * It also allows a database to execute string sql statements against that database.
 */
class Gdn_Database {

    /** @var string The instance name of this class or the class that inherits from this class. */
    public $ClassName;

    /** @var object */
    private $_CurrentResultSet;

    /** @var PDO The connection to the database. */
    protected $_Connection = null;

    /** @var bool */
    protected $_IsPersistent = false;

    /** @var PDO The connection to the slave database. */
    protected $_Slave = null;

    /** @var array The slave connection settings. */
    protected $_SlaveConfig = null;

    /** @var string|null */
    protected $_SQL = null;

    /** @var array|null */
    protected $_Structure = null;

    /** @var array The connection options passed to the PDO constructor. */
    public $ConnectionOptions;

    /** @var string The prefix to all database tables. */
    public $DatabasePrefix;

    /** @var array Extented properties that a specific driver can use. */
    public $ExtendedProperties;

    /** $var bool Whether or not the connection is in a transaction. **/
    protected $_InTransaction = false;

    /** @var string The PDO dsn for the database connection. Note: This does NOT include the engine before the DSN. */
    public $Dsn;

    /** @var string The name of the database engine for this class. */
    public $Engine;

    /** @var array Information about the last query. */
    public $LastInfo = array();

    /** @var string The password to the database. */
    public $Password;

    /** @var string The username connecting to the database. */
    public $User;

    /** @var int Number of retries when the db has gone away. */
    public $ConnectRetries;

    /**
     *
     *
     * @param mixed $Config The configuration settings for this object.
     * @see Database::Init()
     */
    public function __construct($Config = null) {
        $this->ClassName = get_class($this);
        $this->init($Config);
        $this->ConnectRetries = 1;
    }

    /**
     * Begin a transaction on the database.
     */
    public function beginTransaction() {
        if (!$this->_InTransaction) {
            $this->_InTransaction = $this->connection()->beginTransaction();
        }
    }

    /**
     * Get the PDO connection to the database.
     *
     * @return PDO The connection to the database.
     */
    public function connection() {
        $this->_IsPersistent = val(PDO::ATTR_PERSISTENT, $this->ConnectionOptions, false);
        if ($this->_Connection === null) {
            $this->_Connection = $this->newPDO($this->Dsn, $this->User, $this->Password);
        }

        return $this->_Connection;
    }

    /**
     *
     */
    public function closeConnection() {
        if (!$this->_IsPersistent) {
            $this->commitTransaction();
            $this->_Connection = null;
            $this->_Slave = null;
        }
    }

    /**
     * Close connection regardless of persistence.
     */
    public function forceCloseConnection() {
        $this->_Connection = null;
    }

    /**
     * Hook for cleanup via Gdn_Factory.
     */
    public function cleanup() {
        $this->closeConnection();
    }

    /**
     * Commit a transaction on the database.
     */
    public function commitTransaction() {
        if ($this->_InTransaction) {
            $this->_InTransaction = !$this->connection()->commit();
        }
    }

    /**
     *
     *
     * @param $Dsn
     * @param $User
     * @param $Password
     * @return PDO
     * @throws Exception
     */
    protected function newPDO($Dsn, $User, $Password) {
        try {
            $PDO = new PDO(strtolower($this->Engine).':'.$Dsn, $User, $Password, $this->ConnectionOptions);
            $PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
            $PDO->query("set time_zone = '+0:0'");

            // We only throw exceptions during connect
            $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        } catch (Exception $ex) {
            $Timeout = false;
            if ($ex->getCode() == '2002' && preg_match('/Operation timed out/i', $ex->getMessage())) {
                $Timeout = true;
            }
            if ($ex->getCode() == '2003' && preg_match("/Can't connect to MySQL/i", $ex->getMessage())) {
                $Timeout = true;
            }

            if ($Timeout) {
                throw new Exception(errorMessage('Timeout while connecting to the database', $this->ClassName, 'Connection', $ex->getMessage()), 504);
            }

            throw new Exception(errorMessage('An error occurred while attempting to connect to the database', $this->ClassName, 'Connection', $ex->getMessage()), 500);
        }

        return $PDO;
    }

    /**
     * Properly quotes and escapes a expression for a SQL string.
     *
     * @param mixed $Expr The expression to quote.
     * @return string The quoted expression.
     */
    public function quoteExpression($Expr) {
        if (is_null($Expr)) {
            return 'NULL';
        } elseif (is_string($Expr)) {
            return '\''.str_replace('\'', '\\\'', $Expr).'\'';
        } elseif (is_object($Expr)) {
            return '?OBJECT?';
        } else {
            return $Expr;
        }
    }

    /**
     * Initialize the properties of this object.
     *
     * @param mixed $config The database is instantiated differently depending on the type of $Config:
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
    public function init($config = null) {
        if (is_null($config)) {
            $config = Gdn::config('Database');
        } elseif (is_string($config)) {
            $config = Gdn::config($config);
        }

        $defaultConfig = (array)Gdn::config('Database', []);
        if (is_null($config)) {
            $config = [];
        }

        if (is_null($defaultConfig)) {
            $defaultConfig = [];
        }

        // Make sure the config has all the keys we need
        $config += $defaultConfig + [
            'Dsn' => null,
            'Engine' => null,
            'Host' => '',
            'Dbname' => '',
            'Name' => '',
            'Port' => null,
            'User' => null,
            'Password' => null,
            'ConnectionOptions' => null,
            'ExtendedProperties' => [],
            'DatabasePrefix' => null,
            'Prefix' => null,
        ];

        $this->Engine = $config['Engine'];
        $this->User = $config['User'];
        $this->Password = $config['Password'];
        $this->ConnectionOptions = $config['ConnectionOptions'];
        $this->DatabasePrefix = $config['DatabasePrefix'] ?: $config['Prefix'];
        $this->ExtendedProperties = $config['ExtendedProperties'];

        if (!empty($config['Dsn'])) {
            // Get the dsn from the property.
            $dsn = $config['Dsn'];
        } else {
            $host = $config['Host'];
            $dbname = $config['Dbname'] ?: $config['Name'];

            if (empty($dbname)) {
                $dsn = '';
            } else {
                // Was the port explicitly defined in the config?
                $port = $config['Port'];
                if (empty($port) && strpos($host, ':') !== false) {
                    // Was the port explicitly defined with the host name? (ie. 127.0.0.1:3306)
                    list($host, $port) = explode(':', $host);
                }

                if (empty($port)) {
                    $dsn = sprintf('host=%s;dbname=%s;', $host, $dbname);
                } else {
                    $dsn = sprintf('host=%s;port=%s;dbname=%s;', $host, $port, $dbname);
                }
            }
        }
        if (strpos($dsn, 'charset=') === false) {
            $dsn .= rtrim($dsn, ';').';charset='.c('Database.CharacterEncoding', 'utf8mb4');
        }

        if (array_key_exists('Slave', $config)) {
            $this->_SlaveConfig = $config['Slave'];
        }

        $this->Dsn = $dsn;
    }

    /**
     * Executes a string of SQL. Returns a @@DataSet object.
     *
     * @param string $Sql A string of SQL to be executed.
     * @param array $InputParameters An array of values with as many elements as there are bound parameters in the SQL statement being executed.
     */
    public function query($Sql, $InputParameters = null, $Options = array()) {
        $this->LastInfo = array();

        if ($Sql == '') {
            trigger_error(errorMessage('Database was queried with an empty string.', $this->ClassName, 'Query'), E_USER_ERROR);
        }

        // Get the return type.
        if (isset($Options['ReturnType'])) {
            $ReturnType = $Options['ReturnType'];
        } elseif (preg_match('/^\s*"?(insert)\s+/i', $Sql)) {
            $ReturnType = 'ID';
        } elseif (!preg_match('/^\s*"?(update|delete|replace|create|drop|load data|copy|alter|grant|revoke|lock|unlock)\s+/i', $Sql)) {
            $ReturnType = 'DataSet';
        } else {
            $ReturnType = null;
        }

        if (isset($Options['Cache'])) {
            // Check to see if the query is cached.
            $CacheKeys = (array)val('Cache', $Options, null);
            $CacheOperation = val('CacheOperation', $Options, null);
            if (is_null($CacheOperation)) {
                switch ($ReturnType) {
                    case 'DataSet':
                        $CacheOperation = 'get';
                        break;
                    case 'ID':
                    case null:
                        $CacheOperation = 'remove';
                        break;
                }
            }

            switch ($CacheOperation) {
                case 'get':
                    foreach ($CacheKeys as $CacheKey) {
                        $Data = Gdn::cache()->get($CacheKey);
                    }

                    // Cache hit. Return.
                    if ($Data !== Gdn_Cache::CACHEOP_FAILURE) {
                        return new Gdn_DataSet($Data);
                    }

                    // Cache miss. Save later.
                    $StoreCacheKey = $CacheKey;
                    break;

                case 'increment':
                case 'decrement':
                    $CacheMethod = ucfirst($CacheOperation);
                    foreach ($CacheKeys as $CacheKey) {
                        $CacheResult = Gdn::cache()->$CacheMethod($CacheKey);
                    }
                    break;

                case 'remove':
                    foreach ($CacheKeys as $CacheKey) {
                        $Res = Gdn::cache()->remove($CacheKey);
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
            if (val('Type', $Options) == 'select' && val('Slave', $Options, null) !== false) {
                $PDO = $this->slave();
                $this->LastInfo['connection'] = 'slave';
            } else {
                $PDO = $this->connection();
                $this->LastInfo['connection'] = 'master';
            }

            // Make sure other unbufferred queries are not open
            if (is_object($this->_CurrentResultSet)) {
                $this->_CurrentResultSet->result();
                $this->_CurrentResultSet->freePDOStatement(false);
            }

            $PDOStatement = null;
            try {
                // Prepare / Execute
                if (!is_null($InputParameters) && count($InputParameters) > 0) {
                    $PDOStatement = $PDO->prepare($Sql);

                    if (!is_object($PDOStatement)) {
                        trigger_error(errorMessage('PDO Statement failed to prepare', $this->ClassName, 'Query', $this->getPDOErrorMessage($PDO->errorInfo())), E_USER_ERROR);
                    } elseif ($PDOStatement->execute($InputParameters) === false) {
                        trigger_error(errorMessage($this->getPDOErrorMessage($PDOStatement->errorInfo()), $this->ClassName, 'Query', $Sql), E_USER_ERROR);
                    }
                } else {
                    $PDOStatement = $PDO->query($Sql);
                }

                if ($PDOStatement === false) {
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

                if (!$message) {
                    $message = $ex->getMessage();
                }

                trigger_error($message, E_USER_ERROR);
            }

        }

        if ($PDOStatement instanceof PDOStatement) {
            $this->LastInfo['RowCount'] = $PDOStatement->rowCount();
        }

        // Did this query modify data in any way?
        if ($ReturnType === 'ID') {
            $this->_CurrentResultSet = $PDO->lastInsertId();
            if (is_a($PDOStatement, 'PDOStatement')) {
                $PDOStatement->closeCursor();
            }
        } else {
            if ($ReturnType === 'DataSet') {
                // Create a DataSet to manage the resultset
                $this->_CurrentResultSet = new Gdn_DataSet();
                $this->_CurrentResultSet->Connection = $PDO;
                $this->_CurrentResultSet->pdoStatement($PDOStatement);
            } elseif (is_a($PDOStatement, 'PDOStatement')) {
                $PDOStatement->closeCursor();
            }

        }

        if (isset($StoreCacheKey)) {
            if ($CacheOperation == 'get') {
                Gdn::cache()->store(
                    $StoreCacheKey,
                    (($this->_CurrentResultSet instanceof Gdn_DataSet) ? $this->_CurrentResultSet->resultArray() : $this->_CurrentResultSet),
                    val('CacheOptions', $Options, array())
                );
            }
        }

        return $this->_CurrentResultSet;
    }

    /**
     *
     */
    public function rollbackTransaction() {
        if ($this->_InTransaction) {
            $this->_InTransaction = !$this->connection()->rollBack();
        }
    }

    /**
     *
     *
     * @param $ErrorInfo
     * @return string
     */
    public function getPDOErrorMessage($ErrorInfo) {
        $ErrorMessage = '';
        if (is_array($ErrorInfo)) {
            if (count($ErrorInfo) >= 2) {
                $ErrorMessage = $ErrorInfo[2];
            } elseif (count($ErrorInfo) >= 1)
                $ErrorMessage = $ErrorInfo[0];
        } elseif (is_string($ErrorInfo)) {
            $ErrorMessage = $ErrorInfo;
        }

        return $ErrorMessage;
    }

    /**
     * The slave connection to the database.
     *
     * @return PDO
     */
    public function slave() {
        if ($this->_Slave === null) {
            if (empty($this->_SlaveConfig) || empty($this->_SlaveConfig['Dsn'])) {
                $this->_Slave = $this->connection();
            } else {
                $this->_Slave = $this->newPDO($this->_SlaveConfig['Dsn'], val('User', $this->_SlaveConfig), val('Password', $this->_SlaveConfig));
            }
        }

        return $this->_Slave;
    }

    /**
     * Get the database driver class for the database.
     *
     * @return Gdn_SQLDriver The database driver class associated with this database.
     */
    public function sql() {
        if (is_null($this->_SQL)) {
            $Name = $this->Engine.'Driver';
            $this->_SQL = Gdn::factory($Name);
            if (!$this->_SQL) {
                $this->_SQL = new stdClass();
            }
            $this->_SQL->Database = $this;
        }

        return $this->_SQL;
    }

    /**
     * Get the database structure class for this database.
     *
     * @return Gdn_DatabaseStructure The database structure class for this database.
     */
    public function structure() {
        if (is_null($this->_Structure)) {
            $Name = $this->Engine.'Structure';
            $this->_Structure = Gdn::factory($Name, $this);
        }

        return $this->_Structure;
    }
}
