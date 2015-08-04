<?php
/**
 * Schema representation.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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
    public function __construct($Table = '', $Database = null) {
        if ($Table != '') {
            $this->fetch($Table, $Database);
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
    public function Fetch($Table = false, $Database = null) {
        if ($Table !== false) {
            $this->CurrentTable = $Table;
        }

        if (!is_array($this->_Schema)) {
            $this->_Schema = array();
        }

        if (!array_key_exists($this->CurrentTable, $this->_Schema)) {
            if ($Database !== null) {
                $SQL = $Database->SQL();
            } else {
                $SQL = Gdn::SQL();
            }
            $this->_Schema[$this->CurrentTable] = $SQL->fetchTableSchema($this->CurrentTable);
        }
        return $this->_Schema[$this->CurrentTable];
    }

    /** Gets the array of fields/properties for the schema.
     *
     * @return array
     */
    public function fields($Tablename = false) {
        if (!$Tablename) {
            $Tablename = $this->CurrentTable;
        }

        return $this->_Schema[$Tablename];
    }

    /**
     * Returns a the entire field object.
     *
     * @param string The name of the field to look for in $this->CurrentTable (or $Table if it is defined).
     * @param string If this value is specified, $this->CurrentTable will be switched to $Table.
     */
    public function getField($Field, $Table = '') {
        if ($Table != '') {
            $this->CurrentTable = $Table;
        }

        if (!is_array($this->_Schema)) {
            $this->_Schema = array();
        }

        $Result = false;
        if ($this->fieldExists($this->CurrentTable, $Field) === true) {
            $Result = $this->_Schema[$this->CurrentTable][$Field];
        }

        return $Result;
    }

    /**
     * Returns the value of $Property or $Default if not found.
     *
     * @param string The name of the field to look for in $this->CurrentTable (or $Table if it is defined).
     * @param string The name of the property to retrieve from $Field. Options are: Name, PrimaryKey, Type, AllowNull, Default, Length, and Enum.
     * @param string The default value to return if $Property is not found in $Field of $Table.
     * @param string If this value is specified, $this->CurrentTable will be switched to $Table.
     */
    public function getProperty($Field, $Property, $Default = false, $Table = '') {
        $Return = $Default;
        if ($Table != '') {
            $this->CurrentTable = $Table;
        }

        $Properties = array('Name', 'PrimaryKey', 'Type', 'AllowNull', 'Default', 'Length', 'Enum');
        if (in_array($Property, $Properties)) {
            $Field = $this->getField($Field, $this->CurrentTable);
            if ($Field !== false) {
                $Return = $Field->$Property;
            }
        }

        return $Return;
    }

    /**
     * Returns a boolean value indicating if the specified $Field exists in
     * $Table. Assumes that $this->Fetch() has been called for $Table.
     *
     * @param string The name of the table to look for $Field in.
     * @param string The name of the field to look for in $Table.
     */
    public function fieldExists($Table, $Field) {
        if (array_key_exists($Table, $this->_Schema)
            && is_array($this->_Schema[$Table])
            && array_key_exists($Field, $this->_Schema[$Table])
            && is_object($this->_Schema[$Table][$Field])
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the name (or array of names) of the field(s) that represents the
     * primary key on $Table.
     *
     * @param string The name of the table for which to find the primary key(s).
     */
    public function primaryKey($Table, $Database = null) {
        $Schema = $this->fetch($Table, $Database);
        $PrimaryKeys = array();
        foreach ($Schema as $FieldName => $Properties) {
            if ($Properties->PrimaryKey === true) {
                $PrimaryKeys[] = $FieldName;
            }
        }

        if (count($PrimaryKeys) == 0) {
            return '';
        } elseif (count($PrimaryKeys) == 1)
            return $PrimaryKeys[0];
        else {
            return $PrimaryKeys;
        }
    }
}
