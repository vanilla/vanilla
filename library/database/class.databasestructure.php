<?php
/**
 * Database Structure tools
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

/**
 * Used by any given database driver to build, modify, and create tables and views.
 */
abstract class Gdn_DatabaseStructure extends Gdn_Pluggable
{
    public const KEY_TYPE_FULLTEXT = "fulltext";

    public const KEY_TYPE_INDEX = "index";

    public const KEY_TYPE_PRIMARY = "primary";

    public const KEY_TYPE_UNIQUE = "unique";

    /**
     * @var int The maximum number of rows allowed for an alter table.
     */
    private $alterTableThreshold;

    /**
     * @var array Issues that occurred during a structure change.
     */
    private $issues;

    /** @var string  */
    protected $_DatabasePrefix = "";

    /**
     * @var bool Whether or not to only capture the sql, rather than execute it. When this property is true
     * then a property called CapturedSql will be added to this class which is an array of all the Sql statements.
     */
    public $CaptureOnly = false;

    /** @var string The character encoding to set as default for the table being created. */
    protected $_CharacterEncoding;

    /** @var array $ColumnName => $ColumnPropertiesObject columns to be added to $this->_TableName. */
    protected $_Columns;

    /** @var Gdn_Database The instance of the database singleton. */
    public $Database;

    /** @var array The existing columns in the database. */
    protected $_ExistingColumns = null;

    /** @var string The name of the table to create or modify. */
    protected $_TableName;

    /** @var bool Whether or not this table exists in the database. */
    protected $_TableExists;

    /** @var string The name of the storage engine for this table. */
    protected $_TableStorageEngine;

    /** @var bool */
    private $fullTextIndexingEnabled = false;

    /**
     * The constructor for this class. Automatically fills $this->ClassName.
     *
     * @param Gdn_Database|null $database
     */
    public function __construct($database = null)
    {
        parent::__construct();

        if (is_null($database)) {
            $this->Database = Gdn::database();
        } else {
            $this->Database = $database;
        }

        $this->databasePrefix($this->Database->DatabasePrefix);

        if (inMaintenanceMode()) {
            $alterTableThreshold = 0;
        } else {
            $alterTableThreshold = c("Database.AlterTableThreshold", 0);
        }
        $this->setAlterTableThreshold($alterTableThreshold);

        $this->reset();
    }

    /**
     * Get the alter table threshold.
     *
     * The alter table threshold is the maximum estimated rows a table can have where alter tables are allowed.
     *
     * @return int Returns the threshold as an integer. A value of zero means no threshold.
     */
    public function getAlterTableThreshold()
    {
        return $this->alterTableThreshold;
    }

    /**
     * Set the alterTableThreshold.
     *
     * @param int $alterTableThreshold
     * @return Gdn_MySQLStructure Returns `$this` for fluent calls.
     */
    public function setAlterTableThreshold($alterTableThreshold)
    {
        $this->alterTableThreshold = $alterTableThreshold;
        return $this;
    }

    /**
     * Create a column and return its object representation.
     *
     * @param string $name
     * @param string $type
     * @param bool $null
     * @param mixed $default
     * @param string|array|false $keyType
     * @return object
     */
    protected function _createColumn($name, $type, $null, $default, $keyType)
    {
        $length = "";
        $precision = "";

        // Check to see if the type starts with a 'u' for unsigned.
        if (is_string($type) && strncasecmp($type, "u", 1) == 0) {
            $type = substr($type, 1);
            $unsigned = true;
        } else {
            $unsigned = false;
        }

        // Check for a length in the type.
        if (is_string($type) && preg_match("/(\w+)\s*\(\s*(\d+)\s*(?:,\s*(\d+)\s*)?\)/", $type, $matches)) {
            $type = $matches[1];
            $length = $matches[2];
            if (count($matches) >= 4) {
                $precision = $matches[3];
            }
        }

        $column = new stdClass();
        $column->Name = $name;
        $column->Type = is_array($type) ? "enum" : $type;
        $column->Length = $length;
        $column->Precision = $precision;
        $column->Enum = is_array($type) ? $type : false;
        $column->AllowNull = $null;
        $column->Default = $default;
        $column->KeyType = $keyType;
        $column->Unsigned = $unsigned;
        $column->AutoIncrement = false;

        if ($column->Type === "datetime") {
            $column->Precision = $column->Length;
            $column->Length = null;
        }

        // Handle enums and sets as types.
        if (is_array($type)) {
            if (count($type) === 2 && is_array(val(1, $type))) {
                // The type is specified as the first element in the array.
                $column->Type = $type[0];
                $column->Enum = $type[1];
            } else {
                // This is an enum.
                $column->Type = "enum";
                $column->Enum = $type;
            }
        } else {
            $column->Type = $type;
            $column->Enum = false;
        }

        return $column;
    }

    /**
     * Add dateInserted / dateUpdated and insertUserID / updateUserID columns.
     *
     * @param bool $includeUpdate Add update columns.
     * @param bool $includeIPAddress Add insertIPAddress & updateIPAddress columns.
     *
     * @return $this
     */
    public function insertUpdateColumns(
        bool $includeUpdate = true,
        bool $includeIPAddress = false,
        int $datePrecision = 0
    ): Gdn_DatabaseStructure {
        $this->column("dateInserted", $datePrecision > 0 ? "datetime($datePrecision)" : "datetime", false);

        $this->column("insertUserID", "int", false);

        if ($includeUpdate) {
            $this->column("dateUpdated", "datetime[$datePrecision]", null)->column("updateUserID", "int", null);
        }

        if ($includeIPAddress) {
            $this->column("insertIPAddress", "varbinary(16)", false);
            if ($includeUpdate) {
                $this->column("updateIPAddress", "varbinary(16)", null);
            }
        }

        return $this;
    }

    /**
     * Defines a column to be added to $this->table().
     *
     * @param string $name The name of the column to create.
     * @param mixed $type The data type of the column to be created. Types with a length specify the length in brackets.
     * * If an array of values is provided, the type will be set as "enum" and the array will be assigned as the column's Enum property.
     * * If an array of two values is specified then a "set" or "enum" can be specified (ex. array('set', array('Short', 'Tall', 'Fat', 'Skinny')))
     * @param boolean $nullDefault Whether or not nulls are allowed, if not a default can be specified.
     * * TRUE: Nulls are allowed.
     * * FALSE: Nulls are not allowed.
     * * Any other value: Nulls are not allowed, and the specified value will be used as the default.
     * @param string|false $keyType What type of key is this column on the table? Options
     * are primary, key, and FALSE (not a key).
     * @return $this
     */
    public function column($name, $type, $nullDefault = false, $keyType = false)
    {
        if (is_null($nullDefault) || $nullDefault === true) {
            $null = true;
            $default = null;
        } elseif ($nullDefault === false) {
            $null = false;
            $default = null;
        } elseif (is_array($nullDefault)) {
            $null = val("Null", $nullDefault);
            $default = val("Default", $nullDefault, null);
        } else {
            $null = false;
            $default = $nullDefault;
        }

        // Check the key type for validity. A column can be in many keys by specifying an array as key type.
        $keyTypes = (array) $keyType;
        $keyTypes1 = [];
        foreach ($keyTypes as $keyType1) {
            $parts = explode(".", $keyType1, 2);

            if (in_array($parts[0], $this->validKeyTypes())) {
                $keyTypes1[] = $keyType1;
            }
        }
        if (count($keyTypes1) == 0) {
            $keyType = false;
        } elseif (count($keyTypes1) == 1) {
            $keyType = $keyTypes1[0];
        } else {
            $keyType = $keyTypes1;
        }

        $column = $this->_createColumn($name, $type, $null, $default, $keyType);
        $this->_Columns[$name] = $column;
        return $this;
    }

    /**
     * Returns whether or not a column exists in the database.
     *
     * @param string $columnName The name of the column to check.
     * @return bool
     */
    public function columnExists($columnName)
    {
        $result = array_key_exists($columnName, $this->existingColumns());
        if (!$result) {
            foreach ($this->existingColumns() as $colName => $def) {
                if (strcasecmp($columnName, $colName) == 0) {
                    return true;
                }
            }
            return false;
        }
        return $result;
    }

    /**
     * An associative array of $ColumnName => $ColumnProperties columns for the table.
     *
     * @param string $name The name of the column to fetch. Specify an empty string to get them all.
     * @return array|object
     */
    public function columns($name = "")
    {
        if (strlen($name) > 0) {
            if (array_key_exists($name, $this->_Columns)) {
                return $this->_Columns[$name];
            } else {
                foreach ($this->_Columns as $colName => $def) {
                    if (strcasecmp($name, $colName) == 0) {
                        return $def;
                    }
                }
                return null;
            }
        }
        return $this->_Columns;
    }

    /**
     * Return the definition string for a column.
     *
     * @param mixed $column The column to get the type string from.
     *  - <b>object</b>: The column as returned by the database schema. The properties looked at are Type, Length, and Precision.
     *  - <b>string</b<: The name of the column currently in this structure.
     * @return string The type definition string.
     */
    public function columnTypeString($column)
    {
        if (is_string($column)) {
            $column = $this->_Columns[$column];
        }

        $type = val("Type", $column);
        $length = val("Length", $column);
        $precision = val("Precision", $column);

        if (in_array(strtolower($type), ["tinyint", "smallint", "mediumint", "int", "float", "double"])) {
            $length = null;
        }

        if ($type && $length && $precision) {
            $result = "$type($length, $precision)";
        } elseif ($type && $length) {
            $result = "$type($length)";
        } elseif (strtolower($type) == "enum") {
            $result = val("Enum", $column, []);
        } elseif ($type) {
            $result = $type;
        } else {
            $result = "int";
        }

        return $result;
    }

    /**
     * Gets and/or sets the database prefix.
     *
     * @param string $databasePrefix
     * @return string
     */
    public function databasePrefix($databasePrefix = "")
    {
        if ($databasePrefix != "") {
            $this->_DatabasePrefix = $databasePrefix;
        }

        return $this->_DatabasePrefix;
    }

    /**
     * Drops $this->table() from the database.
     */
    public function drop()
    {
        trigger_error(
            errorMessage("The selected database engine does not perform the requested task.", $this->ClassName, "Drop"),
            E_USER_ERROR
        );
    }

    /**
     * Drops $name column from $this->table().
     *
     * @param string $name The name of the column to drop from $this->table().
     */
    public function dropColumn($name)
    {
        trigger_error(
            errorMessage(
                "The selected database engine does not perform the requested task.",
                $this->ClassName,
                "DropColumn"
            ),
            E_USER_ERROR
        );
    }

    /**
     * Set the storage engine for the table.
     *
     * @param string $engine
     * @param bool $checkAvailability
     * @return $this
     */
    public function engine($engine, $checkAvailability = true)
    {
        trigger_error(
            errorMessage(
                "The selected database engine does not perform the requested task.",
                $this->ClassName,
                "Engine"
            ),
            E_USER_ERROR
        );
    }

    /**
     * Is full-text indexing of columns allowed?
     *
     * @return bool
     */
    public function isFullTextIndexingEnabled(): bool
    {
        return $this->fullTextIndexingEnabled;
    }

    /**
     * Should full-text indexing of columns be allowed?
     *
     * @param bool $fullTextIndexing
     */
    public function setFullTextIndexingEnabled(bool $fullTextIndexing): void
    {
        $this->fullTextIndexingEnabled = $fullTextIndexing;
    }

    /**
     * Load the schema for this table from the database.
     *
     * @param string $tableName The name of the table to get or blank to get the schema for the current table.
     * @return $this
     */
    public function get($tableName = "")
    {
        if ($tableName) {
            $this->table($tableName);
        }

        $columns = $this->Database->sql()->fetchTableSchema($this->_TableName, true);
        $this->_Columns = $columns;

        return $this;
    }

    /**
     * Get the estimated number of rows in a table.
     *
     * @param string $tableName The name of the table to look up, without its prefix.
     * @return int|null Returns the estimated number of rows or **null** if the information doesn't exist.
     */
    public function getRowCountEstimate($tableName)
    {
        // This method is basically abstract.
        return null;
    }

    /**
     * Defines a primary key column on a table.
     *
     * @param string $name The name of the column.
     * @param string $type The data type of the column.
     * @param bool $autoIncrement
     * @return $this
     */
    public function primaryKey($name, $type = "int", bool $autoIncrement = true)
    {
        $column = $this->_createColumn($name, $type, false, null, "primary");
        $column->AutoIncrement = $autoIncrement;
        $this->_Columns[$name] = $column;

        return $this;
    }

    /**
     * Send a query to the database and return the result.
     *
     * @param string $sql The sql to execute.
     * @param bool $checkThreshold Whether or not to check the alter table threshold before altering the table.
     * @return bool Whether or not the query succeeded.
     */
    public function executeQuery($sql, $checkThreshold = false)
    {
        if ($this->CaptureOnly) {
            if (!property_exists($this->Database, "CapturedSql")) {
                $this->Database->CapturedSql = [];
            }
            $this->Database->CapturedSql[] = $sql;
            return true;
        } elseif (
            $checkThreshold &&
            $this->getAlterTableThreshold() &&
            $this->getRowCountEstimate($this->tableName()) >= $this->getAlterTableThreshold()
        ) {
            $this->addIssue("The table was past its threshold. Run the alter manually.", $sql);

            // Log an event to be captured and analysed later.
            Logger::event(
                "structure_threshold",
                Logger::ALERT,
                "Cannot alter table {tableName}. Its count of {rowCount,number} is past the {rowThreshold,number} threshold.",
                [
                    "tableName" => $this->tableName(),
                    "rowCount" => $this->getRowCountEstimate($this->tableName()),
                    "rowThreshold" => $this->getAlterTableThreshold(),
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
                ]
            );
            return true;
        } else {
            $result = $this->Database->query($sql);
            return $result;
        }
    }

    /**
     * Renames a column in $this->table().
     *
     * @param string $oldName The name of the column to be renamed.
     * @param string $newName The new name for the column being renamed.
     */
    public function renameColumn($oldName, $newName)
    {
        trigger_error(
            errorMessage(
                "The selected database engine does not perform the requested task.",
                $this->ClassName,
                "RenameColumn"
            ),
            E_USER_ERROR
        );
    }

    /**
     * Renames a table in the database.
     *
     * @param string $oldName The name of the table to be renamed.
     * @param string $newName The new name for the table being renamed.
     * @param boolean $usePrefix A boolean value indicating if $this->_DatabasePrefix should be prefixed
     * before $oldName and $newName.
     */
    public function renameTable($oldName, $newName, $usePrefix = false)
    {
        trigger_error(
            errorMessage(
                "The selected database engine does not perform the requested task.",
                $this->ClassName,
                "RenameTable"
            ),
            E_USER_ERROR
        );
    }

    /**
     * Creates the table and columns specified with $this->table() and
     * $this->column(). If no table or columns have been specified, this method
     * will throw a fatal error.
     *
     * @param boolean $explicit If TRUE, and the table specified with $this->table() already exists, this
     * method will remove any columns from the table that were not defined with
     * $this->column().
     * @param boolean $drop If TRUE, and the table specified with $this->table() already exists, this
     * method will drop the table before attempting to re-create it.
     */
    public function set($explicit = false, $drop = false)
    {
        /// Throw an event so that the structure can be overridden.
        $this->EventArguments["Explicit"] = $explicit;
        $this->EventArguments["Drop"] = $drop;
        $this->fireEvent("BeforeSet");

        try {
            // Make sure that table and columns have been defined
            if ($this->_TableName == "") {
                throw new Exception(t("You must specify a table before calling DatabaseStructure::Set()"));
            }

            if (count($this->_Columns) == 0) {
                throw new Exception(t("You must provide at least one column before calling DatabaseStructure::Set()"));
            }

            if ($this->tableExists()) {
                if ($drop) {
                    // Drop the table.
                    $this->drop();

                    // And re-create it.
                    return $this->_create();
                }

                // If the table already exists, go into modify mode.
                return $this->_modify($explicit, $drop);
            } else {
                // If it doesn't already exist, go into create mode.
                return $this->_create();
            }
        } catch (Exception $ex) {
            $this->reset();
            throw $ex;
        }
    }

    /**
     * Specifies the name of the table to create or modify.
     *
     * @param string $name The name of the table.
     * @param string $characterEncoding The default character encoding to specify for this table.
     * @return $this
     */
    public function table($name = "", $characterEncoding = "")
    {
        if (!$name) {
            return $this->_TableName;
        }

        $this->reset();
        $this->_TableName = $name;
        if ($characterEncoding == "") {
            $characterEncoding = Gdn::config("Database.CharacterEncoding", "");
        }

        $this->_CharacterEncoding = $characterEncoding;
        return $this;
    }

    /**
     * Whether or not the table exists in the database.
     *
     * @param string|null $tableName
     * @return bool
     */
    public function tableExists($tableName = null)
    {
        if ($this->_TableExists === null || $tableName !== null) {
            if ($tableName === null) {
                $tableName = $this->tableName();
            }

            if (strlen($tableName) > 0) {
                if (str_starts_with($tableName, $this->databasePrefix())) {
                    $tableName = str_replace($this->databasePrefix(), "", $tableName);
                }
                $tables = $this->Database->sql()->fetchTables(":_" . $tableName);
                $result = count($tables) > 0;
            } else {
                $result = false;
            }
            if ($tableName == $this->tableName()) {
                $this->_TableExists = $result;
            }
            return $result;
        }
        return $this->_TableExists;
    }

    /**
     * Returns the name of the table being defined in this object.
     *
     * @return string
     */
    public function tableName()
    {
        return $this->_TableName;
    }

    /**
     * Gets an array of type names allowed in the structure.
     *
     * @param string $class The class of types to get. Valid values are:
     *  - <b>int</b>: Integer types.
     *  - <b>float</b>: Floating point types.
     *  - <b>decimal</b>: Precise decimal types.
     *  - <b>numeric</b>: float, int and decimal.
     *  - <b>string</b>: String types.
     *  - <b>date</b>: Date types.
     *  - <b>length</b>: Types that have a length.
     *  - <b>defineLength</b>: Types that MUST be defined with a length.
     *  - <b>precision</b>: Types that have a precision.
     *  - <b>other</b>: Types that don't fit into any other category on their own.
     *  - <b>all</b>: All recognized types.
     */
    public function types($class = "all")
    {
        $date = ["datetime", "date", "timestamp"];
        $decimal = ["decimal", "numeric"];
        $float = ["float", "double"];
        $int = ["int", "tinyint", "smallint", "mediumint", "bigint"];
        $char = ["varchar", "char"];
        $text = ["tinytext", "text", "mediumtext", "longtext"];
        $length = ["varbinary"];
        $other = ["enum", "tinyblob", "blob", "mediumblob", "longblob", "ipaddress"];

        switch (strtolower($class)) {
            case "date":
                return $date;
            case "decimal":
            case "precision":
                return $decimal;
            case "float":
                return $float;
            case "int":
                return $int;
            case "string":
                return array_merge($char, $text);
            case "other":
                return array_merge($length, $other);
            case "numeric":
                return array_merge($float, $int, $decimal);
            case "length":
                return array_merge($char, $text, $length, $decimal);
            case "definelength":
                return array_merge($char, $length, $decimal, ["datetime"]);
            default:
                return [];
        }
    }

    /**
     * Specifies the name of the view to create or modify.
     *
     * @param string $name The name of the view.
     * @param string $sql Query to create as the view. Typically this can be generated with the $Database object.
     */
    public function view($name, $sql)
    {
        trigger_error(
            errorMessage("The selected database engine can not create or modify views.", $this->ClassName, "View"),
            E_USER_ERROR
        );
    }

    /**
     * Creates the table defined with $this->table() and $this->column().
     */
    protected function _create()
    {
        trigger_error(
            errorMessage(
                "The selected database engine does not perform the requested task.",
                $this->ClassName,
                "_Create"
            ),
            E_USER_ERROR
        );
    }

    /**
     * Gets the column definitions for the columns in the database.
     *
     * @return array
     */
    public function existingColumns()
    {
        if ($this->_ExistingColumns === null) {
            if ($this->tableExists()) {
                $this->_ExistingColumns = $this->Database->sql()->fetchTableSchema($this->_TableName, true);
            } else {
                $this->_ExistingColumns = [];
            }
        }
        return $this->_ExistingColumns;
    }

    /**
     * @param string $indexName
     *
     * @return $this
     */
    public function dropIndexIfExists(string $indexName)
    {
        $tableName = $this->tableName();
        if ($this->indexExists($tableName, $indexName)) {
            $px = $this->Database->DatabasePrefix;
            $this->executeQuery("DROP INDEX `$indexName` ON " . $px . $tableName . " ALGORITHM = INPLACE LOCK = NONE");
        }
        return $this;
    }

    /**
     * Check if an index exists on a table.
     *
     * @param string $tableName
     * @param string $indexName
     *
     * @return bool
     */
    public function indexExists(string $tableName, string $indexName): bool
    {
        $db = $this->Database;
        $px = $this->Database->DatabasePrefix;

        if ($this->tableExists($tableName)) {
            $tableName = stringBeginsWith($tableName, $px) ? $tableName : $px . $tableName;

            $count = $db->query("SHOW INDEX FROM `$tableName` WHERE KEY_NAME = '$indexName'")->count();
            if ($count > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create an index if it doesn't exist.
     *
     * @param string $indexName Name of the index.
     * @param string[] $columns The columns making up the index in order.
     *
     * @return $this
     */
    public function createIndexIfNotExists(string $indexName, array $columns)
    {
        $px = $this->Database->DatabasePrefix;
        $tableName = $this->tableName();
        $tableName = $px . $tableName;

        $indexExists = $this->indexExists($tableName, $indexName);

        if (!$indexExists) {
            $quotedColumns = array_map(function ($col) {
                return "`$col`";
            }, $columns);
            $indexType = str_starts_with("UX", $indexName) ? " UNIQUE " : "";
            $columnString = implode(",", $quotedColumns);
            $sql = <<<SQL
ALTER TABLE `{$tableName}`
ADD $indexType INDEX {$indexName} ({$columnString}), ALGORITHM=INPLACE, LOCK=NONE
SQL;

            $this->executeQuery($sql, true);
        }
        return $this;
    }

    /**
     * Rename an old index to a new one if possible.
     * Deletes the old index if both exist.
     *
     * @param string $oldName
     * @param string $newName
     *
     * @return $this
     */
    public function tryRenameIndex(string $oldName, string $newName)
    {
        $px = $this->Database->DatabasePrefix;
        $tableName = $this->tableName();
        $tableName = $px . $tableName;

        $oldIndexExists = $this->indexExists($tableName, $oldName);

        if (!$oldIndexExists) {
            // Nothing to do.
            return $this;
        }

        $newIndexExists = $this->indexExists($tableName, $newName);
        if ($newIndexExists) {
            // We can drop the old index
            $this->dropIndexIfExists($oldName);
            return $this;
        }

        // At this point the old one exists, but the new one does not.
        $sql = <<<SQL
ALTER TABLE `{$tableName}`
RENAME INDEX {$oldName} TO {$newName}
SQL;
        $this->executeQuery($sql);
        return $this;
    }

    /**
     * Modifies $this->table() with the columns specified with $this->column().
     *
     * @param boolean $explicit If TRUE, this method will remove any columns from the table that were not
     * defined with $this->column().
     */
    protected function _modify($explicit = false)
    {
        trigger_error(
            errorMessage(
                "The selected database engine does not perform the requested task.",
                $this->ClassName,
                "_Modify"
            ),
            E_USER_ERROR
        );
    }

    /**
     * Reset the internal state of this object so that it can be reused.
     *
     * @return $this
     */
    public function reset()
    {
        $this->_CharacterEncoding = "";
        $this->_Columns = [];
        $this->_ExistingColumns = null;
        $this->_TableExists = null;
        $this->_TableName = "";
        $this->_TableStorageEngine = null;

        return $this;
    }

    /**
     * Add an issue to the issues list.
     *
     * @param string $message A human readable string for the issue.
     * @param string $sql The SQL that didn't happen.
     * @return $this
     */
    protected function addIssue($message, $sql)
    {
        $this->issues[] = ["table" => $this->tableName(), "message" => $message, "sql" => $sql];
        return $this;
    }

    /**
     * Get a list of issues that occurred during the last call to {@link Gdn_DatabaseStructure::set()}.
     *
     * @return array Returns an array of issues.
     */
    public function getIssues()
    {
        return $this->issues;
    }

    /**
     * Get all valid key types.
     *
     * @return array
     */
    private function validKeyTypes(): array
    {
        $result = [self::KEY_TYPE_PRIMARY, self::KEY_TYPE_INDEX, self::KEY_TYPE_UNIQUE, "key", false];
        if ($this->isFullTextIndexingEnabled()) {
            $result[] = self::KEY_TYPE_FULLTEXT;
        }
        return $result;
    }
}
