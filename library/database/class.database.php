<?php
/**
 * Database manager
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

use Garden\EventManager;
use Garden\Schema\Schema;
use Vanilla\InjectableInterface;
use Vanilla\Utility\Spans\AbstractSpan;
use Vanilla\Utility\StringUtils;

/**
 * The Database object contains connection and engine information for a single database.
 *
 * It also allows a database to execute string sql statements against that database.
 *
 * @property string[] $CapturedSql
 */
class Gdn_Database implements InjectableInterface
{
    const SQL_MODE_NO_AUTO_VALUE_ZERO = "NO_AUTO_VALUE_ON_ZERO";

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

    /** @var int Number of milliseconds we are willing to wait to make a successful connection to the DB */
    protected $SmoothTimeoutMillis;

    /** @var int Minimum number of milliseconds we want to wait before attempting a new DB connection*/
    protected $SmoothWaitMinMillis;

    /** @var int Maximum number of milliseconds we want to wait before attempting a new DB connection*/
    protected $SmoothWaitMaxMillis;

    /** @var EventManager the event manager for plugin interactions */
    protected $eventManager;

    /**
     * @var \Vanilla\Utility\Timers
     */
    private $timers;

    /**
     * Gdn_Database constructor.
     *
     * @param array|string|null $config The configuration settings for this object.
     * @see Database::init()
     */
    public function __construct($config = null)
    {
        $this->ClassName = get_class($this);
        $this->init($config);
        $this->ConnectRetries = 1;
        $this->timers = Gdn::getContainer()->get(\Vanilla\Utility\Timers::class);
        $this->eventManager = Gdn::eventManager();
    }

    /**
     * Sets this class' dependencies for DI
     *
     * @param EventManager $eventManager the event manager
     */
    public function setDependencies(EventManager $eventManager = null)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Run a some callback within a database transaction.
     *
     * If a transaction is already started the callback will be executed directly.
     *
     * @param callable $callback
     *
     * @return mixed
     */
    public function runWithTransaction(callable $callback): mixed
    {
        if ($this->isInTransaction()) {
            return call_user_func($callback);
        }
        try {
            $this->beginTransaction();
            $result = call_user_func($callback);
            $this->commitTransaction();
            return $result;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Begin a transaction on the database.
     */
    public function beginTransaction()
    {
        if (!$this->_InTransaction) {
            $this->_InTransaction = $this->connection()->beginTransaction();
        }
    }

    /**
     * Get the PDO connection to the database.
     *
     * @return PDO The connection to the database.
     */
    public function connection()
    {
        $this->_IsPersistent = val(PDO::ATTR_PERSISTENT, $this->ConnectionOptions, false);
        if ($this->_Connection === null) {
            $this->_Connection = $this->newPDO($this->Dsn, $this->User, $this->Password);
        }

        return $this->_Connection;
    }

    /**
     * Close the connection to the database.
     */
    public function closeConnection()
    {
        if (!$this->_IsPersistent) {
            $this->commitTransaction();
            $this->_Connection = null;
            $this->_Slave = null;
        }
    }

    /**
     * Close connection regardless of persistence.
     */
    public function forceCloseConnection()
    {
        $this->_Connection = null;
    }

    /**
     * Hook for cleanup via Gdn_Factory.
     */
    public function cleanup()
    {
        $this->closeConnection();
    }

    /**
     * Commit a transaction on the database.
     */
    public function commitTransaction()
    {
        if ($this->_InTransaction) {
            $this->_InTransaction = !$this->connection()->commit();
        }
    }

    /**
     * Creates a new PDO object.
     *
     * Supports connection smoothness. In the case that the connection cannot be done because a resources limit is reached
     * (max connections, max user connections, user limits) we loop up to `SmoothTimeoutMillis` milliseconds and we retry
     * the connection.
     * Every try sleeps for a random amount of time between SmoothWaitMinMillis & SmoothWaitMaxMillis and then attempts a
     * new connection.
     * `$timeoutAt` holds the timestamps at which no more retries are done. It's static as it's a fixed amount of time
     * we are willing to wait during the whole request processing time (Otherwise, waiting time could be accumulative in cases)
     *
     * Beware that in the theoretical "worst" case scenario we wait for up to (SmoothTimeoutMillis +  SmoothWaitMaxMillis - 1),
     * 5749ms for the defaults values.
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @return PDO
     * @throws Exception Throws an exception if there is an error connecting to the database.
     */
    protected function newPDO($dsn, $user, $password)
    {
        static $timeoutAt = null;
        $timeoutAt = $timeoutAt ?? microtime(true) + $this->SmoothTimeoutMillis / 1000;
        do {
            try {
                $pDO = new PDO(strtolower($this->Engine) . ":" . $dsn, $user, $password, $this->ConnectionOptions);
                $pDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
                $pDO->query("set time_zone = '+0:0'");
                $pDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                return $pDO;
            } catch (Exception $ex) {
                if (
                    $ex instanceof PDOException &&
                    microtime(true) < $timeoutAt &&
                    in_array($ex->getCode(), [1203, 1040, 1226])
                ) {
                    /*
                     * https://dev.mysql.com/doc/refman/5.7/en/server-error-reference.html
                     * ---
                     * Error number: 1203; Symbol: ER_TOO_MANY_USER_CONNECTIONS; SQLSTATE: 42000
                     * Message: User %s already has more than 'max_user_connections' active connections
                     * ---
                     * Error number: 1040; Symbol: ER_CON_COUNT_ERROR; SQLSTATE: 08004
                     * Message: Too many connections
                     * ---
                     * Error number: 1226; Symbol: ER_USER_LIMIT_REACHED; SQLSTATE: 42000
                     * Message: User '%s' has exceeded the '%s' resource (current value: %ld)
                     * ---
                     * In case connection limit is reached, we wait a random amount of time hoping the connection is released
                     */
                    usleep(rand($this->SmoothWaitMinMillis, $this->SmoothWaitMaxMillis) * 1000);
                } else {
                    $timeout = false;
                    if ($ex->getCode() == "2002" && preg_match("/Operation timed out/i", $ex->getMessage())) {
                        $timeout = true;
                    }
                    if ($ex->getCode() == "2003" && preg_match("/Can't connect to MySQL/i", $ex->getMessage())) {
                        $timeout = true;
                    }

                    if ($timeout) {
                        throw new Exception("Timeout while connecting to the database.", 504);
                    }

                    throw new Exception(
                        "An error occurred while attempting to connect to the database. dsn: $dsn, error: " .
                            $dsn .
                            $ex->getMessage(),
                        500
                    );
                }
            }
        } while (true);
    }

    /**
     * Properly quotes and escapes a expression for a SQL string.
     *
     * @param mixed $expr The expression to quote.
     * @return string The quoted expression.
     */
    public function quoteExpression($expr)
    {
        if (is_null($expr)) {
            return "NULL";
        } elseif (is_string($expr)) {
            return '\'' . str_replace('\'', '\\\'', $expr) . '\'';
        } elseif (is_object($expr)) {
            return "?OBJECT?";
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
     *   - <b>User</b>: The username to connect to the database.
     *   - <b>Password</b>: The password to connect to the database.
     *   - <b>ConnectionOptions</b>: Other PDO connection attributes.
     */
    public function init($config = null)
    {
        if (is_null($config)) {
            $config = Gdn::config("Database");
        } elseif (is_string($config)) {
            $config = Gdn::config($config);
        }

        $defaultConfig = (array) Gdn::config("Database", []);
        if (is_null($config)) {
            $config = [];
        }

        if (is_null($defaultConfig)) {
            $defaultConfig = [];
        }

        // Make sure the config has all the keys we need
        $config += $defaultConfig + [
            "Dsn" => null,
            "Engine" => null,
            "Host" => "",
            "Dbname" => "",
            "Name" => "",
            "Port" => null,
            "User" => null,
            "Password" => null,
            "ConnectionOptions" => null,
            "ExtendedProperties" => [],
            "DatabasePrefix" => null,
            "Prefix" => null,
            "SmoothTimeoutMillis" => 5000,
            "SmoothWaitMinMillis" => 250,
            "SmoothWaitMaxMillis" => 750,
        ];

        $this->Engine = $config["Engine"];
        $this->User = $config["User"];
        $this->Password = $config["Password"];
        $this->ConnectionOptions = $config["ConnectionOptions"];
        $this->DatabasePrefix = $config["DatabasePrefix"] ?: $config["Prefix"];
        $this->ExtendedProperties = $config["ExtendedProperties"];
        $this->SmoothTimeoutMillis = $config["SmoothTimeoutMillis"];
        $this->SmoothWaitMinMillis = $config["SmoothWaitMinMillis"];
        $this->SmoothWaitMaxMillis = $config["SmoothWaitMaxMillis"];

        if (!empty($config["Dsn"])) {
            // Get the dsn from the property.
            $dsn = $config["Dsn"];
        } else {
            $host = $config["Host"];
            $dbname = $config["Dbname"] ?: $config["Name"];

            if (empty($dbname)) {
                $dsn = "";
            } else {
                // Was the port explicitly defined in the config?
                $port = $config["Port"];
                if (empty($port) && strpos($host, ":") !== false) {
                    // Was the port explicitly defined with the host name? (ie. 127.0.0.1:3306)
                    [$host, $port] = explode(":", $host);
                }

                if (empty($port)) {
                    $dsn = sprintf("host=%s;dbname=%s;", $host, $dbname);
                } else {
                    $dsn = sprintf("host=%s;port=%s;dbname=%s;", $host, $port, $dbname);
                }
            }
        }
        if (strpos($dsn, "charset=") === false) {
            $dsn .= rtrim($dsn, ";") . ";charset=" . c("Database.CharacterEncoding", "utf8mb4");
        }

        if (array_key_exists("Slave", $config)) {
            $this->_SlaveConfig = $config["Slave"];
        }

        $this->Dsn = $dsn;
    }

    /**
     * Executes a string of SQL. Returns a @@DataSet object.
     *
     * @param string $sql A string of SQL to be executed.
     * @param array $inputParameters An array of values with as many elements as there are bound parameters in the SQL statement being executed.
     * @param array $options
     * @return mixed
     */
    public function query($sql, $inputParameters = null, $options = [])
    {
        $this->LastInfo = [];

        if ($sql == "") {
            trigger_error(
                errorMessage("Database was queried with an empty string.", $this->ClassName, "Query"),
                E_USER_ERROR
            );
        }

        // Get the return type.
        if (isset($options["ReturnType"])) {
            $returnType = $options["ReturnType"];
        } elseif (preg_match('/^\s*"?(insert)\s+/i', $sql)) {
            $returnType = "ID";
        } elseif (
            !preg_match(
                '/^\s*"?(update|delete|replace|create|drop|load data|copy|alter|grant|revoke|lock|unlock)\s+/i',
                $sql
            )
        ) {
            $returnType = "DataSet";
        } else {
            $returnType = null;
        }

        $span = $returnType === "DataSet" ? $this->timers->startDbRead() : $this->timers->startDbWrite();

        try {
            if (isset($options["Cache"])) {
                // Check to see if the query is cached.
                $cacheKeys = (array) val("Cache", $options, null);
                $cacheOperation = val("CacheOperation", $options, null);
                if (is_null($cacheOperation)) {
                    switch ($returnType) {
                        case "DataSet":
                            $cacheOperation = "get";
                            break;
                        case "ID":
                        case null:
                            $cacheOperation = "remove";
                            break;
                    }
                }

                switch ($cacheOperation) {
                    case "get":
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

                    case "increment":
                    case "decrement":
                        $cacheMethod = ucfirst($cacheOperation);
                        foreach ($cacheKeys as $cacheKey) {
                            $cacheResult = Gdn::cache()->$cacheMethod($cacheKey);
                        }
                        break;

                    case "remove":
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

            if (val("Type", $options) == "select" && val("Slave", $options, null) !== false) {
                $pDO = $this->slave();
                $this->LastInfo["connection"] = "slave";
            } else {
                $pDO = $this->connection();
                $this->LastInfo["connection"] = "master";
            }

            if ($this->eventManager) {
                $inputParameters = $this->eventManager->fireFilter(
                    "database_query_before",
                    $inputParameters,
                    $sql,
                    $options
                );
            }

            for ($try = 0; $try < $tries; $try++) {
                // Make sure other unbuffered queries are not open
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
                            trigger_error(
                                errorMessage(
                                    "PDO Statement failed to prepare",
                                    $this->ClassName,
                                    "Query",
                                    $this->getPDOErrorMessage($pDO->errorInfo())
                                ),
                                E_USER_ERROR
                            );
                        } elseif ($pDOStatement->execute($inputParameters) === false) {
                            trigger_error(
                                errorMessage(
                                    $this->getPDOErrorMessage($pDOStatement->errorInfo()),
                                    $this->ClassName,
                                    "Query",
                                    $sql
                                ),
                                E_USER_ERROR
                            );
                        }
                    } else {
                        $pDOStatement = $pDO->query($sql);
                    }

                    if ($pDOStatement === false) {
                        [$state, $code, $message] = $pDO->errorInfo();

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
                    // Rethrow.
                    throw $uex;
                } catch (PDOException $pex) {
                    $code = $pex->errorInfo[1] ?? $pex->getCode();
                    $message = $pex->errorInfo[2] ?? $pex->getMessage();

                    // Detect mysql "server has gone away" and try to reconnect.
                    if ($code == 2006 && $try < $tries) {
                        $this->closeConnection();
                        continue;
                    } else {
                        throw new Gdn_UserException($message, $code, $pex);
                    }
                } catch (Exception $ex) {
                    $errorInfo = $pDO->errorInfo();
                    [$state, $code, $message] = $errorInfo;

                    // If the error code is consistent with a disconnect, attempt to retry
                    if ($code == 2006 && $try < $tries) {
                        $this->closeConnection();
                        continue;
                    }

                    // Otherwise re-throw;
                    throw $ex;
                }
            }

            if ($pDOStatement instanceof PDOStatement) {
                $this->LastInfo["RowCount"] = $pDOStatement->rowCount();
            }

            // Did this query modify data in any way?
            if ($returnType === "ID") {
                $this->_CurrentResultSet = $pDO->lastInsertId();
                if (is_a($pDOStatement, "PDOStatement")) {
                    $pDOStatement->closeCursor();
                }
            } else {
                if ($returnType === "DataSet") {
                    // Create a DataSet to manage the resultset
                    $this->_CurrentResultSet = Gdn::getContainer()->get(Gdn_DataSet::class);
                    $this->_CurrentResultSet->Connection = $pDO;
                    $this->_CurrentResultSet->pdoStatement($pDOStatement);
                    $this->_CurrentResultSet->setQueryOptions($options);
                } elseif (is_a($pDOStatement, "PDOStatement")) {
                    $pDOStatement->closeCursor();
                }
            }

            if (isset($storeCacheKey)) {
                if ($cacheOperation == "get") {
                    Gdn::cache()->store(
                        $storeCacheKey,
                        $this->_CurrentResultSet instanceof Gdn_DataSet
                            ? $this->_CurrentResultSet->resultArray()
                            : $this->_CurrentResultSet,
                        val("CacheOptions", $options, [])
                    );
                }
            }

            return $this->_CurrentResultSet;
        } finally {
            $span->finish($sql, $inputParameters ?? []);
        }
    }

    /**
     * Rollback the active transaction.
     */
    public function rollbackTransaction()
    {
        if ($this->_InTransaction) {
            $this->_InTransaction = !$this->connection()->rollBack();
        }
    }

    /**
     * @return bool
     */
    public function isInTransaction(): bool
    {
        return $this->_InTransaction;
    }

    /**
     * Get the specific PDO error message.
     *
     * @param string|array $errorInfo
     * @return string
     */
    public function getPDOErrorMessage($errorInfo)
    {
        $errorMessage = "";
        if (is_array($errorInfo)) {
            if (count($errorInfo) >= 2) {
                $errorMessage = $errorInfo[2];
            } elseif (count($errorInfo) >= 1) {
                $errorMessage = $errorInfo[0];
            }
        } elseif (is_string($errorInfo)) {
            $errorMessage = $errorInfo;
        }

        return $errorMessage;
    }

    /**
     * Translate a database data type into a type compatible with Garden Schema.
     *
     * @param string $fieldType
     * @return string
     */
    private function simpleDataType(string $fieldType): string
    {
        $fieldType = strtolower($fieldType);

        switch ($fieldType) {
            case "bool":
            case "boolean":
                $result = "boolean";
                break;
            case "bit":
            case "tinyint":
            case "smallint":
            case "mediumint":
            case "int":
            case "integer":
            case "bigint":
                $result = "integer";
                break;
            case "decimal":
            case "dec":
            case "float":
            case "double":
                $result = "number";
                break;
            case "timestamp":
                $result = "timestamp";
                break;
            case "date":
            case "datetime":
                $result = "datetime";
                break;
            default:
                $result = "string";
        }

        return $result;
    }

    /**
     * Generate a Garden Schema instance, based on the database table schema.
     *
     * @param string $table Target table for building the Schema.
     * @return \Garden\Schema\Schema
     */
    public function simpleSchema(string $table): \Garden\Schema\Schema
    {
        $properties = [];
        $required = [];
        $databaseSchema = $this->sql()->fetchTableSchema($table);

        /** @var object $databaseField */
        foreach ($databaseSchema as $databaseField) {
            $type = $this->simpleDataType($databaseField->Type);
            $allowNull = (bool) $databaseField->AllowNull;
            $isAutoIncrement = (bool) $databaseField->AutoIncrement;
            $hasDefault = !($databaseField->Default === null);
            $isRequired = !$allowNull && !$isAutoIncrement && !$hasDefault;
            if ($isRequired) {
                $required[] = $databaseField->Name;
            }

            $field = [
                "allowNull" => $allowNull,
                "required" => $isRequired,
                "type" => $type,
            ];
            if ($type === "string") {
                $maxLength = $databaseField->Length ?? null;
                if ($maxLength !== null) {
                    $field["maxLength"] = (int) $maxLength;
                }

                $maxByteLength = $databaseField->ByteLength ?? null;
                if ($maxByteLength !== null) {
                    $field["maxByteLength"] = (int) $maxByteLength;
                }
            }
            if (is_array($databaseField->Enum) && !empty($databaseField->Enum)) {
                $field["enum"] = $databaseField->Enum;
            }

            // Garden Schema requires appending a question mark to the field name if it's not required.
            $key = $databaseField->Name;
            $properties[$key] = $field;
        }

        $schema = Schema::parse(["type" => "object", "properties" => $properties, "required" => $required]);
        return $schema;
    }

    /**
     * Get the estimated row count for a table.
     * Notably InnoDB doesn't keep very accurate counts here so this is just a rough estimate can vary by up to 50%.
     *
     * @param string $tableName
     *
     * @return int
     */
    public function getEstimatedRowCount(string $tableName): int
    {
        $data = $this->query(
            "show table status like " . $this->connection()->quote($this->DatabasePrefix . $tableName),
            [],
            ["ReturnType" => "DataSet"]
        );

        return $data->value("Rows", 0);
    }

    /**
     * The slave connection to the database.
     *
     * @return PDO
     */
    public function slave()
    {
        if ($this->_Slave === null) {
            if (empty($this->_SlaveConfig) || empty($this->_SlaveConfig["Dsn"])) {
                $this->_Slave = $this->connection();
            } else {
                $this->_Slave = $this->newPDO(
                    $this->_SlaveConfig["Dsn"],
                    val("User", $this->_SlaveConfig),
                    val("Password", $this->_SlaveConfig)
                );
            }
        }

        return $this->_Slave;
    }

    /**
     * Get the database driver class for the database.
     *
     * @return Gdn_SQLDriver The database driver class associated with this database.
     */
    public function sql()
    {
        if (is_null($this->_SQL)) {
            $name = $this->Engine . "Driver";
            $this->_SQL = Gdn::factory($name);
            if (!$this->_SQL) {
                throw new Exception("Could not instantiate database driver '$name'.");
            }
            $this->_SQL->Database = $this;
        }

        return $this->_SQL;
    }

    /**
     * Get a clean SQL driver instance.
     *
     * @return Gdn_SQLDriver
     */
    public function createSql(): Gdn_SQLDriver
    {
        $sql = clone $this->sql();
        $sql->reset();
        return $sql;
    }

    /**
     * Get the database structure class for this database.
     *
     * @return Gdn_DatabaseStructure The database structure class for this database.
     */
    public function structure()
    {
        $name = $this->Engine . "Structure";
        $this->_Structure = Gdn::getContainer()->get($name);

        return $this->_Structure;
    }

    /**
     * Get an array of the current sql modes.
     *
     * @return array
     */
    public function getSqlModes(): array
    {
        $currentModes = $this->query("SELECT @@sql_mode", [])->column("@@sql_mode");
        $currentModes = reset($currentModes);
        $currentModes = explode(",", $currentModes);
        return $currentModes;
    }

    /**
     * Get an array of the current sql modes.
     *
     * @param string[] $modes MySQL modes to set.
     */
    public function setSqlModes(array $modes): void
    {
        $modes = implode(",", $modes);
        $this->query("SET SESSION sql_mode=:mode", [":mode" => $modes], []);
    }

    /**
     * Run a callback with particular sql modes.
     *
     * @param array $modes
     * @param callable $callback
     *
     * @return mixed The result of the callback.
     */
    public function runWithSqlMode(array $modes, callable $callback)
    {
        $originalModes = $this->getSqlModes();
        $newModes = array_values(array_unique(array_merge($originalModes, $modes)));

        try {
            $this->setSqlModes($newModes);
            $result = call_user_func($callback);
            return $result;
        } finally {
            $this->setSqlModes($originalModes);
        }
    }
}
