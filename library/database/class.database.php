<?php
/**
 * Database manager
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
    public $LastInfo = [];

    /** @var string The password to the database. */
    public $Password;

    /** @var string The username connecting to the database. */
    public $User;

    /** @var int Number of retries when the db has gone away. */
    public $ConnectRetries;

    /**
     *
     *
     * @param mixed $config The configuration settings for this object.
     * @see Database::init()
     */
    public function __construct($config = null) {
        $this->ClassName = get_class($this);
        $this->init($config);
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
     * @param $dsn
     * @param $user
     * @param $password
     * @return PDO
     * @throws Exception
     */
    protected function newPDO($dsn, $user, $password) {
        try {
            $pDO = new PDO(strtolower($this->Engine).':'.$dsn, $user, $password, $this->ConnectionOptions);
            $pDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
            $pDO->query("set time_zone = '+0:0'");

            // We only throw exceptions during connect
            $pDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        } catch (Exception $ex) {
            $timeout = false;
            if ($ex->getCode() == '2002' && preg_match('/Operation timed out/i', $ex->getMessage())) {
                $timeout = true;
            }
            if ($ex->getCode() == '2003' && preg_match("/Can't connect to MySQL/i", $ex->getMessage())) {
                $timeout = true;
            }

            if ($timeout) {
                throw new Exception(errorMessage('Timeout while connecting to the database', $this->ClassName, 'Connection', $ex->getMessage()), 504);
            }

            throw new Exception(errorMessage('An error occurred while attempting to connect to the database', $this->ClassName, 'Connection', $ex->getMessage()), 500);
        }

        return $pDO;
    }

    /**
     * Properly quotes and escapes a expression for a SQL string.
     *
     * @param mixed $expr The expression to quote.
     * @return string The quoted expression.
     */
    public function quoteExpression($expr) {
        if (is_null($expr)) {
            return 'NULL';
        } elseif (is_string($expr)) {
            return '\''.str_replace('\'', '\\\'', $expr).'\'';
        } elseif (is_object($expr)) {
            return '?OBJECT?';
        } else {
            return $expr;
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
     * @param string $sql A string of SQL to be executed.
     * @param array $inputParameters An array of values with as many elements as there are bound parameters in the SQL statement being executed.
     */
    public function query($sql, $inputParameters = null, $options = []) {
        $this->LastInfo = [];

        if ($sql == '') {
            trigger_error(errorMessage('Database was queried with an empty string.', $this->ClassName, 'Query'), E_USER_ERROR);
        }

        // Get the return type.
        if (isset($options['ReturnType'])) {
            $returnType = $options['ReturnType'];
        } elseif (preg_match('/^\s*"?(insert)\s+/i', $sql)) {
            $returnType = 'ID';
        } elseif (!preg_match('/^\s*"?(update|delete|replace|create|drop|load data|copy|alter|grant|revoke|lock|unlock)\s+/i', $sql)) {
            $returnType = 'DataSet';
        } else {
            $returnType = null;
        }

        if (isset($options['Cache'])) {
            // Check to see if the query is cached.
            $cacheKeys = (array)val('Cache', $options, null);
            $cacheOperation = val('CacheOperation', $options, null);
            if (is_null($cacheOperation)) {
                switch ($returnType) {
                    case 'DataSet':
                        $cacheOperation = 'get';
                        break;
                    case 'ID':
                    case null:
                        $cacheOperation = 'remove';
                        break;
                }
            }

            switch ($cacheOperation) {
                case 'get':
                    foreach ($cacheKeys as $cacheKey) {
                        $data = Gdn::cache()->get($cacheKey);
                    }

                    // Cache hit. Return.
                    if ($data !== Gdn_Cache::CACHEOP_FAILURE) {
                        return new Gdn_DataSet($data);
                    }

                    // Cache miss. Save later.
                    $storeCacheKey = $cacheKey;
                    break;

                case 'increment':
                case 'decrement':
                    $cacheMethod = ucfirst($cacheOperation);
                    foreach ($cacheKeys as $cacheKey) {
                        $cacheResult = Gdn::cache()->$cacheMethod($cacheKey);
                    }
                    break;

                case 'remove':
                    foreach ($cacheKeys as $cacheKey) {
                        $res = Gdn::cache()->remove($cacheKey);
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
            if (val('Type', $options) == 'select' && val('Slave', $options, null) !== false) {
                $pDO = $this->slave();
                $this->LastInfo['connection'] = 'slave';
            } else {
                $pDO = $this->connection();
                $this->LastInfo['connection'] = 'master';
            }

            // Make sure other unbufferred queries are not open
            if (is_object($this->_CurrentResultSet)) {
                $this->_CurrentResultSet->result();
                $this->_CurrentResultSet->freePDOStatement(false);
            }

            $pDOStatement = null;
            try {
                // Prepare / Execute
                if (!is_null($inputParameters) && count($inputParameters) > 0) {
                    $pDOStatement = $pDO->prepare($sql);

                    if (!is_object($pDOStatement)) {
                        trigger_error(errorMessage('PDO Statement failed to prepare', $this->ClassName, 'Query', $this->getPDOErrorMessage($pDO->errorInfo())), E_USER_ERROR);
                    } elseif ($pDOStatement->execute($inputParameters) === false) {
                        trigger_error(errorMessage($this->getPDOErrorMessage($pDOStatement->errorInfo()), $this->ClassName, 'Query', $sql), E_USER_ERROR);
                    }
                } else {
                    $pDOStatement = $pDO->query($sql);
                }

                if ($pDOStatement === false) {
                    list($state, $code, $message) = $pDO->errorInfo();

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
                list($state, $code, $message) = $pDO->errorInfo();

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

        if ($pDOStatement instanceof PDOStatement) {
            $this->LastInfo['RowCount'] = $pDOStatement->rowCount();
        }

        // Did this query modify data in any way?
        if ($returnType === 'ID') {
            $this->_CurrentResultSet = $pDO->lastInsertId();
            if (is_a($pDOStatement, 'PDOStatement')) {
                $pDOStatement->closeCursor();
            }
        } else {
            if ($returnType === 'DataSet') {
                // Create a DataSet to manage the resultset
                $this->_CurrentResultSet = new Gdn_DataSet();
                $this->_CurrentResultSet->Connection = $pDO;
                $this->_CurrentResultSet->pdoStatement($pDOStatement);
            } elseif (is_a($pDOStatement, 'PDOStatement')) {
                $pDOStatement->closeCursor();
            }

        }

        if (isset($storeCacheKey)) {
            if ($cacheOperation == 'get') {
                Gdn::cache()->store(
                    $storeCacheKey,
                    (($this->_CurrentResultSet instanceof Gdn_DataSet) ? $this->_CurrentResultSet->resultArray() : $this->_CurrentResultSet),
                    val('CacheOptions', $options, [])
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
     * @param $errorInfo
     * @return string
     */
    public function getPDOErrorMessage($errorInfo) {
        $errorMessage = '';
        if (is_array($errorInfo)) {
            if (count($errorInfo) >= 2) {
                $errorMessage = $errorInfo[2];
            } elseif (count($errorInfo) >= 1)
                $errorMessage = $errorInfo[0];
        } elseif (is_string($errorInfo)) {
            $errorMessage = $errorInfo;
        }

        return $errorMessage;
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
            $name = $this->Engine.'Driver';
            $this->_SQL = Gdn::factory($name);
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
            $name = $this->Engine.'Structure';
            $this->_Structure = Gdn::factory($name, $this);
        }

        return $this->_Structure;
    }
}
