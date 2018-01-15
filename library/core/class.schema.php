<?php
/**
 * Schema representation.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Manages defining and examining the schema of a database table.
 */
class Gdn_Schema {

    /**
     * @var array An associative array of TableName => Fields associative arrays that
     * describe the table's field properties. Each field is represented by an
     * object with the following properties: Name, PrimaryKey, Type, AllowNull, Default, Length, Enum
     */
    protected $_Schema;

    /** @var string The name of the table currently being examined. */
    public $CurrentTable;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @param string Explicitly define the name of the table that this model represents. You can also explicitly set this value with $this->TableName.
     * @param Gdn_Database
     */
    public function __construct($table = '', $database = null) {
        if ($table != '') {
            $this->fetch($table, $database);
        }
    }

    /**
     * Fetches the schema for the requested table. If it does not exist yet, it
     * will connect to the database and define it.
     *
     * @param string The name of the table schema to fetch from the database (or cache?).
     * @param Gdn_Database
     * @return array
     */
    public function fetch($table = false, $database = null) {
        if ($table !== false) {
            $this->CurrentTable = $table;
        }

        if (!is_array($this->_Schema)) {
            $this->_Schema = [];
        }

        if (!array_key_exists($this->CurrentTable, $this->_Schema)) {
            if ($database !== null) {
                $sQL = $database->sql();
            } else {
                $sQL = Gdn::sql();
            }
            $this->_Schema[$this->CurrentTable] = $sQL->fetchTableSchema($this->CurrentTable);
        }
        return $this->_Schema[$this->CurrentTable];
    }

    /** Gets the array of fields/properties for the schema.
     *
     * @return array
     */
    public function fields($tablename = false) {
        if (!$tablename) {
            $tablename = $this->CurrentTable;
        }

        return $this->_Schema[$tablename];
    }

    /**
     * Returns a the entire field object.
     *
     * @param string The name of the field to look for in $this->CurrentTable (or $table if it is defined).
     * @param string If this value is specified, $this->CurrentTable will be switched to $table.
     */
    public function getField($field, $table = '') {
        if ($table != '') {
            $this->CurrentTable = $table;
        }

        if (!is_array($this->_Schema)) {
            $this->_Schema = [];
        }

        $result = false;
        if ($this->fieldExists($this->CurrentTable, $field) === true) {
            $result = $this->_Schema[$this->CurrentTable][$field];
        }

        return $result;
    }

    /**
     * Returns the value of $property or $default if not found.
     *
     * @param string The name of the field to look for in $this->CurrentTable (or $table if it is defined).
     * @param string The name of the property to retrieve from $field. Options are: Name, PrimaryKey, Type, AllowNull, Default, Length, and Enum.
     * @param string The default value to return if $property is not found in $field of $table.
     * @param string If this value is specified, $this->CurrentTable will be switched to $table.
     */
    public function getProperty($field, $property, $default = false, $table = '') {
        $return = $default;
        if ($table != '') {
            $this->CurrentTable = $table;
        }

        $properties = ['Name', 'PrimaryKey', 'Type', 'AllowNull', 'Default', 'Length', 'Enum'];
        if (in_array($property, $properties)) {
            $field = $this->getField($field, $this->CurrentTable);
            if ($field !== false) {
                $return = $field->$property;
            }
        }

        return $return;
    }

    /**
     * Returns a boolean value indicating if the specified $field exists in
     * $table. Assumes that $this->fetch() has been called for $table.
     *
     * @param string The name of the table to look for $field in.
     * @param string The name of the field to look for in $table.
     */
    public function fieldExists($table, $field) {
        if (array_key_exists($table, $this->_Schema)
            && is_array($this->_Schema[$table])
            && array_key_exists($field, $this->_Schema[$table])
            && is_object($this->_Schema[$table][$field])
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the name (or array of names) of the field(s) that represents the
     * primary key on $table.
     *
     * @param string The name of the table for which to find the primary key(s).
     */
    public function primaryKey($table, $database = null) {
        $schema = $this->fetch($table, $database);
        $primaryKeys = [];
        foreach ($schema as $fieldName => $properties) {
            if ($properties->PrimaryKey === true) {
                $primaryKeys[] = $fieldName;
            }
        }

        if (count($primaryKeys) == 0) {
            return '';
        } elseif (count($primaryKeys) == 1)
            return $primaryKeys[0];
        else {
            return $primaryKeys;
        }
    }
}
