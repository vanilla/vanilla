<?php
/**
 * Database Structure tools
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Used by any given database driver to build, modify, and create tables and views.
 */
abstract class Gdn_DatabaseStructure extends Gdn_Pluggable {
    /**
     * @var int The maximum number of rows allowed for an alter table.
     */
    private $alterTableThreshold;

    /**
     * @var array Issues that occurred during a structure change.
     */
    private $issues;

    /** @var string  */
    protected $_DatabasePrefix = '';

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

    /**
     * The constructor for this class. Automatically fills $this->ClassName.
     *
     * @param string $database
     * @todo $database needs a description.
     */
    public function __construct($database = null) {
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
            $alterTableThreshold = c('Database.AlterTableThreshold', 0);
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
    public function getAlterTableThreshold() {
        return $this->alterTableThreshold;
    }

    /**
     * Set the alterTableThreshold.
     *
     * @param int $alterTableThreshold
     * @return Gdn_MySQLStructure Returns `$this` for fluent calls.
     */
    public function setAlterTableThreshold($alterTableThreshold) {
        $this->alterTableThreshold = $alterTableThreshold;
        return $this;
    }

    /**
     *
     *
     * @param $name
     * @param $type
     * @param $null
     * @param $default
     * @param $keyType
     * @return stdClass
     */
    protected function _createColumn($name, $type, $null, $default, $keyType) {
        $length = '';
        $precision = '';

        // Check to see if the type starts with a 'u' for unsigned.
        if (is_string($type) && strncasecmp($type, 'u', 1) == 0) {
            $type = substr($type, 1);
            $unsigned = true;
        } else {
            $unsigned = false;
        }

        // Check for a length in the type.
        if (is_string($type) && preg_match('/(\w+)\s*\(\s*(\d+)\s*(?:,\s*(\d+)\s*)?\)/', $type, $matches)) {
            $type = $matches[1];
            $length = $matches[2];
            if (count($matches) >= 4) {
                $precision = $matches[3];
            }
        }

        $column = new stdClass();
        $column->Name = $name;
        $column->Type = is_array($type) ? 'enum' : $type;
        $column->Length = $length;
        $column->Precision = $precision;
        $column->Enum = is_array($type) ? $type : false;
        $column->AllowNull = $null;
        $column->Default = $default;
        $column->KeyType = $keyType;
        $column->Unsigned = $unsigned;
        $column->AutoIncrement = false;

        // Handle enums and sets as types.
        if (is_array($type)) {
            if (count($type) === 2 && is_array(val(1, $type))) {
                // The type is specified as the first element in the array.
                $column->Type = $type[0];
                $column->Enum = $type[1];
            } else {
                // This is an enum.
                $column->Type = 'enum';
                $column->Enum = $type;
            }
        } else {
            $column->Type = $type;
            $column->Enum = false;
        }

        return $column;
    }

    /**
     * Defines a column to be added to $this->table().
     *
     * @param string $name The name of the column to create.
     * @param mixed $type The data type of the column to be created. Types with a length speecifty the length in barackets.
     * * If an array of values is provided, the type will be set as "enum" and the array will be assigned as the column's Enum property.
     * * If an array of two values is specified then a "set" or "enum" can be specified (ex. array('set', array('Short', 'Tall', 'Fat', 'Skinny')))
     * @param boolean $nullDefault Whether or not nulls are allowed, if not a default can be specified.
     * * TRUE: Nulls are allowed.
     * * FALSE: Nulls are not allowed.
     * * Any other value: Nulls are not allowed, and the specified value will be used as the default.
     * @param string $keyType What type of key is this column on the table? Options
     * are primary, key, and FALSE (not a key).
     * @return $this
     */
    public function column($name, $type, $nullDefault = false, $keyType = false) {
        if (is_null($nullDefault) || $nullDefault === true) {
            $null = true;
            $default = null;
        } elseif ($nullDefault === false) {
            $null = false;
            $default = null;
        } elseif (is_array($nullDefault)) {
            $null = val('Null', $nullDefault);
            $default = val('Default', $nullDefault, null);
        } else {
            $null = false;
            $default = $nullDefault;
        }

        // Check the key type for validity. A column can be in many keys by specifying an array as key type.
        $keyTypes = (array)$keyType;
        $keyTypes1 = [];
        foreach ($keyTypes as $keyType1) {
            $parts = explode('.', $keyType1, 2);

            if (in_array($parts[0], ['primary', 'key', 'index', 'unique', 'fulltext', false])) {
                $keyTypes1[] = $keyType1;
            }
        }
        if (count($keyTypes1) == 0) {
            $keyType = false;
        } elseif (count($keyTypes1) == 1)
            $keyType = $keyTypes1[0];
        else {
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
    public function columnExists($columnName) {
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
     * @return array
     */
    public function columns($name = '') {
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
    public function columnTypeString($column) {
        if (is_string($column)) {
            $column = $this->_Columns[$column];
        }

        $type = val('Type', $column);
        $length = val('Length', $column);
        $precision = val('Precision', $column);

        if (in_array(strtolower($type), ['tinyint', 'smallint', 'mediumint', 'int', 'float', 'double'])) {
            $length = null;
        }

        if ($type && $length && $precision) {
            $result = "$type($length, $precision)";
        } elseif ($type && $length)
            $result = "$type($length)";
        elseif (strtolower($type) == 'enum') {
            $result = val('Enum', $column, []);
        } elseif ($type)
            $result = $type;
        else {
            $result = 'int';
        }

        return $result;
    }

    /**
     * Gets and/or sets the database prefix.
     *
     * @param string $databasePrefix
     * @todo $databasePrefix needs a description.
     */
    public function databasePrefix($databasePrefix = '') {
        if ($databasePrefix != '') {
            $this->_DatabasePrefix = $databasePrefix;
        }

        return $this->_DatabasePrefix;
    }

    /**
     * Drops $this->table() from the database.
     */
    public function drop() {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'Drop'), E_USER_ERROR);
    }

    /**
     * Drops $name column from $this->table().
     *
     * @param string $name The name of the column to drop from $this->table().
     */
    public function dropColumn($name) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'DropColumn'), E_USER_ERROR);
    }

    /**
     *
     *
     * @param $engine
     * @param bool $checkAvailability
     */
    public function engine($engine, $checkAvailability = true) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'Engine'), E_USER_ERROR);
    }


    /**
     * Load the schema for this table from the database.
     *
     * @param string $tableName The name of the table to get or blank to get the schema for the current table.
     * @return Gdn_DatabaseStructure $this
     */
    public function get($tableName = '') {
        if ($tableName) {
            $this->table($tableName);
        }

        $columns = $this->Database->sql()->fetchTableSchema($this->_TableName);
        $this->_Columns = $columns;

        return $this;
    }

    /**
     * Get the estimated number of rows in a table.
     *
     * @param string $tableName The name of the table to look up, without its prefix.
     * @return int|null Returns the estimated number of rows or **null** if the information doesn't exist.
     */
    public function getRowCountEstimate($tableName) {
        // This method is basically abstract.
        return null;
    }

    /**
     * Defines a primary key column on a table.
     *
     * @param string $name The name of the column.
     * @param string $type The data type of the column.
     * @return Gdn_DatabaseStructure $this.
     */
    public function primaryKey($name, $type = 'int') {
        $column = $this->_createColumn($name, $type, false, null, 'primary');
        $column->AutoIncrement = true;
        $this->_Columns[$name] = $column;

        return $this;
    }

    /**
     * Send a query to the database and return the result.
     *
     * @deprecated since 2.3. Was incorrectly public. Replaced by executeQuery().
     * @param string $sql The sql to execute.
     * @param bool $checkTreshold Should not be used
     * @return Gdn_Dataset
     */
    public function query($sql, $checkTreshold = false) {

        $class = null;
        $internalCall = false;

        // Detect the origin of this call.
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (count($backtrace) > 1) {
            $class = val('class', $backtrace[1]);
            if ($class) {
                $internalCall = is_a($class, __CLASS__, true);
            }
        }

        // Give appropriate message based on whether we're using it internally.
        if ($internalCall) {
            deprecated("$class::query()", "$class::executeQuery()");
            return $this->executeQuery($sql, $checkTreshold);
        } else {
            deprecated(__CLASS__.'::query()', 'Gdn_SQLDriver::query()');
            return $this->Database->query($sql);
        }
    }

    /**
     * Send a query to the database and return the result.
     *
     * @param string $sql The sql to execute.
     * @param bool $checkThreshold Whether or not to check the alter table threshold before altering the table.
     * @return bool Whether or not the query succeeded.
     */
    protected function executeQuery($sql, $checkThreshold = false) {
        if ($this->CaptureOnly) {
            if (!property_exists($this->Database, 'CapturedSql')) {
                $this->Database->CapturedSql = [];
            }
            $this->Database->CapturedSql[] = $sql;
            return true;
        } elseif ($checkThreshold && $this->getAlterTableThreshold() && $this->getRowCountEstimate($this->tableName()) >= $this->getAlterTableThreshold()) {
            $this->addIssue("The table was past its threshold. Run the alter manually.", $sql);

            // Log an event to be captured and analysed later.
            Logger::event(
                'structure_threshold',
                Logger::ALERT,
                "Cannot alter table {tableName}. Its count of {rowCount,number} is past the {rowThreshold,number} threshold.",
                [
                    'tableName' => $this->tableName(),
                    'rowCount' => $this->getRowCountEstimate($this->tableName()),
                    'rowThreshold' => $this->getAlterTableThreshold()
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
    public function renameColumn($oldName, $newName) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'RenameColumn'), E_USER_ERROR);
    }

    /**
     * Renames a table in the database.
     *
     * @param string $oldName The name of the table to be renamed.
     * @param string $newName The new name for the table being renamed.
     * @param boolean $usePrefix A boolean value indicating if $this->_DatabasePrefix should be prefixed
     * before $oldName and $newName.
     */
    public function renameTable($oldName, $newName, $usePrefix = false) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'RenameTable'), E_USER_ERROR);
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
    public function set($explicit = false, $drop = false) {
        /// Throw an event so that the structure can be overridden.
        $this->EventArguments['Explicit'] = $explicit;
        $this->EventArguments['Drop'] = $drop;
        $this->fireEvent('BeforeSet');

        try {
            // Make sure that table and columns have been defined
            if ($this->_TableName == '') {
                throw new Exception(t('You must specify a table before calling DatabaseStructure::Set()'));
            }

            if (count($this->_Columns) == 0) {
                throw new Exception(t('You must provide at least one column before calling DatabaseStructure::Set()'));
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
     */
    public function table($name = '', $characterEncoding = '') {
        if (!$name) {
            return $this->_TableName;
        }

        $this->reset();
        $this->_TableName = $name;
        if ($characterEncoding == '') {
            $characterEncoding = Gdn::config('Database.CharacterEncoding', '');
        }

        $this->_CharacterEncoding = $characterEncoding;
        return $this;
    }

    /**
     * Whether or not the table exists in the database.
     *
     * @return bool
     */
    public function tableExists($tableName = null) {
        if ($this->_TableExists === null || $tableName !== null) {
            if ($tableName === null) {
                $tableName = $this->tableName();
            }

            if (strlen($tableName) > 0) {
                $tables = $this->Database->sql()->fetchTables(':_'.$tableName);
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
    public function tableName() {
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
     *  - <b>precision</b>: Types that have a precision.
     *  - <b>other</b>: Types that don't fit into any other category on their own.
     *  - <b>all</b>: All recognized types.
     */
    public function types($class = 'all') {
        $date = ['datetime', 'date', 'timestamp'];
        $decimal = ['decimal', 'numeric'];
        $float = ['float', 'double'];
        $int = ['int', 'tinyint', 'smallint', 'mediumint', 'bigint'];
        $string = ['varchar', 'char', 'mediumtext', 'text'];
        $length = ['varbinary'];
        $other = ['enum', 'tinyblob', 'blob', 'mediumblob', 'longblob', 'ipaddress'];

        switch (strtolower($class)) {
            case 'date':
                return $date;
            case 'decimal':
                return $decimal;
            case 'float':
                return $float;
            case 'int':
                return $int;
            case 'string':
                return $string;
            case 'other':
                return array_merge($length, $other);

            case 'numeric':
                return array_merge($float, $int, $decimal);
            case 'length':
                return array_merge($string, $length, $decimal);
            case 'precision':
                return $decimal;
            default:
                return [];
        }
    }

    /**
     * Specifies the name of the view to create or modify.
     *
     * @param string $name The name of the view.
     * @param string $query Query to create as the view. Typically this can be generated with the $Database object.
     */
    public function view($name, $query) {
        trigger_error(errorMessage('The selected database engine can not create or modify views.', $this->ClassName, 'View'), E_USER_ERROR);
    }

    /**
     * Creates the table defined with $this->table() and $this->column().
     */
    protected function _create() {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, '_Create'), E_USER_ERROR);
    }

    /**
     * Gets the column definitions for the columns in the database.
     *
     * @return array
     */
    public function existingColumns() {
        if ($this->_ExistingColumns === null) {
            if ($this->tableExists()) {
                $this->_ExistingColumns = $this->Database->sql()->fetchTableSchema($this->_TableName);
            } else {
                $this->_ExistingColumns = [];
            }
        }
        return $this->_ExistingColumns;
    }

    /**
     * Modifies $this->table() with the columns specified with $this->column().
     *
     * @param boolean $explicit If TRUE, this method will remove any columns from the table that were not
     * defined with $this->column().
     */
    protected function _modify($explicit = false) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, '_Modify'), E_USER_ERROR);
    }

    /**
     * Reset the internal state of this object so that it can be reused.
     *
     * @return Gdn_DatabaseStructure $this
     */
    public function reset() {
        $this->_CharacterEncoding = '';
        $this->_Columns = [];
        $this->_ExistingColumns = null;
        $this->_TableExists = null;
        $this->_TableName = '';
        $this->_TableStorageEngine = null;

        return $this;
    }

    /**
     * Add an issue to the issues list.
     *
     * @param string $message A human readable string for the issue.
     * @param string $sql The SQL that didn't happen.
     * @return Gdn_DatabaseStructure Returns **this** for chaining.
     */
    protected function addIssue($message, $sql) {
        $this->issues[] = ['table' => $this->tableName(), 'message' => $message, 'sql' => $sql];
        return $this;
    }

    /**
     * Get a list of issues that occurred during the last call to {@link Gdn_DatabaseStructure::set()}.
     *
     * @return array Returns an array of issues.
     */
    public function getIssues() {
        return $this->issues;
    }
}
