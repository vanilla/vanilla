<?php
/**
 * A database-independent dataset management/manipulation class.
 *
 * This class is HEAVILY inspired by CodeIgniter (http://www.codeigniter.com).
 * My hat is off to them.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

use Vanilla\InjectableInterface;
use Garden\EventManager;

/**
 * Class Gdn_DataSet
 */
class Gdn_DataSet implements IteratorAggregate, Countable, JsonSerializable, InjectableInterface {

    /** Inner join. */
    const JOIN_INNER = 'inner';

    /** Left join. */
    const JOIN_LEFT = 'left';

    /**
     * @var PDO Contains a reference to the open database connection. FALSE by default.
     * This property is passed by reference from the Database object. Do not
     * manipulate or assign anything to this property!
     */
    public $Connection;

    /** @var int The index of the $this->_ResultSet currently being accessed. */
    private $cursor = -1;

    /**
     * @var string Determines what type of result is returned from the various methods by default.
     * Either DATASET_TYPE_OBJECT or DATASET_TYPE_ARRAY.
     */
    protected $_DatasetType = DATASET_TYPE_OBJECT;

    /** @var bool  */
    protected $eof = false;

    /**
     * @var PDOStatement Contains a PDOStatement object returned by a PDO query. FALSE by default.
     * This property is assigned by the database driver object when a query is
     * executed in $Database->query().
     */
    private $pdoStatement;

    /** @var array An array of either objects or associative arrays with the data in this dataset. */
    protected $_Result;

    /** @var EventManager the event manager for plugin hooks */
    protected $eventManager;

    /** @var array of query options from Gdn_Database->query */
    protected $queryOptions;

    /**
     * Gdn_DataSet constructor.
     *
     * @param array|null $result
     * @param string $dataSetType
     * @param array $queryOptions optional query options array that was used to get this DataSet
     */
    public function __construct($result = null, $dataSetType = null, array $queryOptions = []) {
        // Set defaults
        $this->Connection = null;
        $this->cursor = -1;
        $this->pdoStatement = null;
        $this->_Result = $result;
        if ($dataSetType !== null) {
            $this->_DatasetType = $dataSetType;
        } elseif ($result) {
            $firstElement = reset($result);
            if (is_array($firstElement)) {
                $this->_DatasetType = DATASET_TYPE_ARRAY;
            }
        }
        $this->queryOptions = $queryOptions;
        $this->eventManager = Gdn::eventManager();
    }

    /**
     * Sets this class' dependencies for DI
     *
     * @param EventManager $eventManager the event manager
     */
    public function setDependencies(EventManager $eventManager = null) {
        $this->eventManager = $eventManager;
    }

    /**
     * Free up resources.
     */
    public function __destruct() {
        $this->freePDOStatement(true);
    }

    /**
     * Clean sensitive data out of the object.
     */
    public function clean() {
        $this->Connection = null;
        $this->freePDOStatement(true);
    }

    /**
     * Count elements of this object. This method provides support for the countable interface.
     *
     * @return int
     */
    public function count() {
        return $this->numRows();
    }

    /**
     * Moves the dataset's internal cursor pointer to the specified RowIndex.
     *
     * @param int $rowIndex The index to seek in the result resource.
     */
    public function dataSeek($rowIndex = 0) {
        $this->cursor = $rowIndex;
    }

    /**
     * Get or set the dataset type.
     *
     * @param bool $datasetType
     * @return $this|string
     * @psalm-return (
     *     $datasetType is false
     *     ? $this
     *     : string
     * )
     *
     */
    public function datasetType($datasetType = false) {
        if ($datasetType !== false) {
            // Make sure the type isn't changed if the result is already fetched.
            if (!is_null($this->_Result) && $datasetType != $this->_DatasetType) {
                // Loop through the dataset and switch the types.
                $count = count($this->_Result);
                foreach ($this->_Result as $index => &$row) {
                    switch ($datasetType) {
                        case DATASET_TYPE_ARRAY:
                            $row = (array)$row;
                            //$this->_Result[$Index] = (array)$this->_Result[$Index];
                            break;
                        case DATASET_TYPE_OBJECT:
                            $row = (object)$row;
                            //$this->_Result[$Index] = (object)$this->_Result[$Index];
                            break;
                    }
                }
            }

            $this->_DatasetType = $datasetType;
            return $this;
        } else {
            return $this->_DatasetType;
        }
    }

    /**
     * Take all of the items encoded in an attributes column and merge them into each row.
     *
     * @param string $name
     */
    public function expandAttributes($name = 'Attributes') {
        $result =& $this->result();

        foreach ($result as &$row) {
            if (is_object($row)) {
                if (is_string($row->$name)) {
                    $attributes = dbdecode($row->$name);

                    if (is_array($attributes)) {
                        foreach ($attributes as $n => $v) {
                            $row->$n = $v;
                        }
                    }
                    unset($row->$name);
                }
            } else {
                if (is_string($row[$name])) {
                    $attributes = dbdecode($row[$name]);

                    if (is_array($attributes)) {
                        $row = array_merge($row, $attributes);
                    }
                    unset($row[$name]);
                }
            }
        }
    }

    /**
     * Fetches all rows from the PDOStatement object into the resultset.
     *
     * @param string|false $datasetType The format in which the result should be returned: object or array.
     * It will fill a different array depending on which type is specified.
     */
    protected function _fetchAllRows($datasetType = false) {
        if (!is_null($this->_Result)) {
            return;
        }

        if ($datasetType) {
            $this->_DatasetType = $datasetType;
        }

        $result = [];
        if (is_null($this->pdoStatement)) {
            $this->_Result = $result;
            return;
        }

        // Calling fetchAll on insert/update/delete queries will raise an error!
        if (preg_match('/^(insert|update|delete)/', trim(strtolower($this->pdoStatement->queryString))) !== 1) {
            $result = $this->pdoStatement->fetchAll($this->_DatasetType == DATASET_TYPE_ARRAY ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
        }

        $this->_Result = $result;

        if ($this->eventManager) {
            $this->_Result = $this->eventManager->fireFilter(
                'database_query_result_after',
                $this->_Result,
                $this->pdoStatement->queryString,
                $this->queryOptions
            );
        }

        $this->freePDOStatement(true);
    }

    /**
     * Returns the first row or FALSE if there are no rows to return.
     *
     * @param string|false $datasetType The format in which the result should be returned: object or array.
     * @return bool|array|stdClass False when empty result set, object or array depending on $datasetType.
     */
    public function &firstRow($datasetType = false) {
        $result = &$this->result($datasetType);
        if (count($result) == 0) {
            return $this->eof;
        }

        return $result[0];
    }

    /**
     * Format the resultset with the given method.
     *
     * @param string $formatMethod The method to use with Gdn_Format::to().
     * @return Gdn_Dataset $this pointer for chaining.
     */
    public function format($formatMethod) {
        $result = &$this->result();
        foreach ($result as $index => $value) {
            $result[$index] = Gdn_Format::to($value, $formatMethod);
        }
        return $this;
    }

    /**
     * Free's the result resource referenced by $this->_PDOStatement.
     *
     * @param bool $destroyPDOStatement
     */
    public function freePDOStatement($destroyPDOStatement = true) {
        try {
            if (is_object($this->pdoStatement)) {
                $this->pdoStatement->closeCursor();
            }

            if ($destroyPDOStatement) {
                $this->pdoStatement = null;
            }
        } catch (Exception $ex) {
            // Go past exceptions in case wait_timeout exceeded.
        }
    }

    /**
     * Interface method for IteratorAggregate.
     */
    public function getIterator() {
        return new ArrayIterator($this->result());
    }

    /**
     * Get a column value from all rows in the result set.
     *
     * @param string $columnName The name of the column to fetch.
     *
     * @return array The result.
     */
    public function column(string $columnName): array {
        $result = $this->resultArray();
        return array_column($result, $columnName);
    }

    /**
     * Index a result array.
     *
     * @param array|Traversable $data The array to index. It is formatted similar to the array returned by Gdn_DataSet::result().
     * @param string|array $columns The name of the column to index on or an array of columns to index on.
     * @param array $options An array of options for the method.
     *  - <b>Sep</b>: The string to seperate index columns by. Default '|'.
     *  - <b>Unique</b>: Whether or not the results are unique.
     *   - <b>true</b> (default): The index is unique.
     *   - <b>false</b>: The index is not unique and each indexed row will be an array or arrays.
     * @return array
     */
    public static function index($data, $columns, $options = []) {
        $columns = (array)$columns;
        $result = [];
        $options = array_change_key_case($options);

        if (is_string($options)) {
            $options = ['sep' => $options];
        }

        $sep = val('sep', $options, '|');
        $unique = val('unique', $options, true);

        foreach ($data as $row) {
            $indexValues = [];
            foreach ($columns as $column) {
                $indexValues[] = val($column, $row);
            }
            $index = implode($sep, $indexValues);

            if ($unique) {
                $result[$index] = $row;
            } else {
                $result[$index][] = $row;
            }
        }
        return $result;
    }

    /**
     * Join a query to this dataset.
     *
     * @param array $data
     * @param array $columns The columns/table information for the join. Depending on the argument's index it will be interpreted differently.
     *  - <b>numeric</b>: This column will come be added to the resulting join. The value can be either a string or a
     *    two element array where the second element specifies an alias.
     *  - <b>alias</b>: The alias of the child table in the query.
     *  - <b>child</b>: The name of the child column.
     *  - <b>column</b>: The name of the column to put the joined data into. Can't be used with <b>prefix</b>.
     *  - <b>parent</b>: The name of the parent column.
     *  - <b>table</b>: The name of the child table in the join.
     *  - <b>prefix</b>: The name of the prefix to give the columns. Can't be used with <b>column</b>.
     * @param array $options An array of extra options.
     *  - <b>sql</b>: A Gdn_SQLDriver with the child query.
     *  - <b>type</b>: The join type, either JOIN_INNER, JOIN_LEFT. This defaults to JOIN_LEFT.
     */
    public static function join(&$data, $columns, $options = []) {
        $options = array_change_key_case($options);

        $sql = Gdn::sql(); //GetValue('sql', $Options, Gdn::sql());
        $resultColumns = [];

        // Grab the columns.
        foreach ($columns as $index => $name) {
            if (is_numeric($index)) {
                // This is a column being selected.
                if (is_array($name)) {
                    $column = $name[0];
                    $columnAlias = $name[1];
                } else {
                    $column = $name;
                    $columnAlias = '';
                }

                if (($pos = strpos($column, '.')) !== false) {
                    $sql->select($column, '', $columnAlias);
                    $column = substr($column, $pos + 1);
                } else {
                    $sql->select(isset($tableAlias) ? $tableAlias.'.'.$column : $column, '', $columnAlias);
                }
                if ($columnAlias) {
                    $resultColumns[] = $columnAlias;
                } else {
                    $resultColumns[] = $column;
                }
            } else {
                switch (strtolower($index)) {
                    case 'alias':
                        $tableAlias = $name;
                        break;
                    case 'child':
                        $childColumn = $name;
                        break;
                    case 'column':
                        $joinColumn = $name;
                        break;
                    case 'parent':
                        $parentColumn = $name;
                        break;
                    case 'prefix':
                        $columnPrefix = $name;
                        break;
                    case 'table':
                        $table = $name;
                        break;
                    case 'type':
                        // The type shouldn't be here, but handle it.
                        $options['Type'] = $name;
                        break;
                    default:
                        throw new Exception("Gdn_DataSet::Join(): Unknown column option '$index'.");
                }
            }
        }

        if (!isset($tableAlias)) {
            if (isset($table)) {
                $tableAlias = 'c';
            } else {
                $tableAlias = 'c';
            }
        }

        if (!isset($parentColumn)) {
            if (isset($childColumn)) {
                $parentColumn = $childColumn;
            } elseif (isset($table)) {
                $parentColumn = $table . 'ID';
            } else {
                throw new Exception("Gdn_DataSet::Join(): Missing 'parent' argument'.");
            }
        }

        // Figure out some options if they weren't specified.
        if (!isset($childColumn)) {
            if (isset($parentColumn)) {
                $childColumn = $parentColumn;
            } elseif (isset($table)) {
                $childColumn = $table . 'ID';
            } else {
                throw new Exception("Gdn_DataSet::Join(): Missing 'child' argument'.");
            }
        }

        if (!isset($columnPrefix) && !isset($joinColumn)) {
            $columnPrefix = stringEndsWith($parentColumn, 'ID', true, true);
        }

        $joinType = strtolower(val('Type', $options, self::JOIN_LEFT));

        // Start augmenting the sql for the join.
        if (isset($table)) {
            $sql->from("$table $tableAlias");
        }
        $sql->select("$tableAlias.$childColumn");

        // Get the IDs to generate an in clause with.
        $iDs = [];
        foreach ($data as $row) {
            $value = val($parentColumn, $row);
            if ($value) {
                $iDs[$value] = true;
            }
        }

        $iDs = array_keys($iDs);
        $sql->whereIn($childColumn, $iDs);

        $childData = $sql->get()->resultArray();
        $childData = self::index($childData, $childColumn, ['unique' => getValue('unique', $options, isset($columnPrefix))]);

        $notFound = [];

        // Join the data in.
        foreach ($data as $index => &$row) {
            $parentID = val($parentColumn, $row);
            if (isset($childData[$parentID])) {
                $childRow = $childData[$parentID];

                if (isset($columnPrefix)) {
                    // Add the data to the columns.
                    foreach ($childRow as $name => $value) {
                        setValue($columnPrefix.$name, $row, $value);
                    }
                } else {
                    // Add the result data.
                    setValue($joinColumn, $row, $childRow);
                }
            } else {
                if ($joinType == self::JOIN_LEFT) {
                    if (isset($columnPrefix)) {
                        foreach ($resultColumns as $name) {
                            setValue($columnPrefix.$name, $row, null);
                        }
                    } else {
                        setValue($joinColumn, $row, []);
                    }
                } else {
                    $notFound[] = $index;
                }
            }
        }

        // Remove inner join rows.
        if ($joinType == self::JOIN_INNER) {
            foreach ($notFound as $index) {
                unset($data[$index]);
            }
        }
    }

    /**
     * Returns the last row in the or FALSE if there are no rows to return.
     *
     * @param string|false $datasetType The format in which the result should be returned: object or array.
     * @return object|array|false Returns the last row however it is formatted.
     */
    public function &lastRow($datasetType = false) {
        $result = &$this->result($datasetType);
        if (count($result) == 0) {
            return $this->eof;
        }

        return $result[count($result) - 1];
    }

    /**
     * Returns the next row or FALSE if there are no more rows.
     *
     * @param string|false $datasetType The format in which the result should be returned: object or array.
     * @return object|array|false Returns the next row or **false** if there isn't one.
     */
    public function &nextRow($datasetType = false) {
        $result = &$this->result($datasetType);
        ++$this->cursor;

        if (isset($result[$this->cursor])) {
            return $result[$this->cursor];
        }
        return $this->eof;
    }

    /**
     * Returns the number of fields in the DataSet.
     */
    public function numFields() {
        $result = is_object($this->pdoStatement) ? $this->pdoStatement->columnCount() : 0;
        return $result;
    }

    /**
     * Returns the number of rows in the DataSet.
     *
     * @param string|false $datasetType The format in which the result should be returned: object or array.
     * @return int Returns the count.
     */
    public function numRows($datasetType = false) {
        $result = count($this->result($datasetType));
        return $result;
    }

    /**
     * Returns the previous row in the requested format.
     *
     * @param string|false $datasetType The format in which the result should be returned: object or array.
     * @return object|array|false Returns a reference to the previous row or **false** if there isn't one.
     */
    public function &previousRow($datasetType = false) {
        $result = &$this->result($datasetType);
        --$this->cursor;
        if (isset($result[$this->cursor])) {
            return $result[$this->cursor];
        }
        return $this->eof;
    }

    /**
     * Returns an array of data as the specified result type: object or array.
     *
     * @param string|false $datasetType The format in which to return a row: object or array. The following values are supported.
     *  - <b>DATASET_TYPE_ARRAY</b>: An array of associative arrays.
     *  - <b>DATASET_TYPE_OBJECT</b>: An array of standard objects.
     *  - <b>FALSE</b>: The current value of the DatasetType property will be used.
     * @return array Returns a reference to all of the result rows.
     */
    public function &result($datasetType = false) {
        $this->datasetType($datasetType);
        if (is_null($this->_Result)) {
            $this->_fetchAllRows();
        }


        return $this->_Result;
    }

    /**
     * Returns an array of associative arrays containing the ResultSet data.
     *
     * @return array Returns an array reference.
     */
    public function &resultArray() {
        return $this->result(DATASET_TYPE_ARRAY);
    }

    /**
     * Returns an array of objects containing the ResultSet data.
     *
     * @param string $formatType Do not use.
     * @return object[]
     */
    public function resultObject($formatType = '') {
        return $this->result(DATASET_TYPE_OBJECT);
    }

    /**
     * Returns the requested row index as the requested row type.
     *
     * @param int $rowIndex The row to return from the result set. It is zero-based.
     * @return mixed The row at the given index or FALSE if there is no row at the index.
     */
    public function &row($rowIndex) {
        $result = &$this->result();
        if (isset($result[$rowIndex])) {
            return $result[$rowIndex];
        }
        return $this->eof;
    }

    /**
     * Allows you to fill this object's result set with a foreign data set in
     * the form of an array of associative arrays (or objects).
     *
     * @param array $resultset The array of arrays or objects that represent the data to be traversed.
     */
    public function importDataset($resultset) {
        if (is_array($resultset)) {
            if (array_key_exists(0, $resultset)) {
                $firstRow = $resultset[0];

                if (is_array($firstRow)) {
                    $this->_DatasetType = DATASET_TYPE_ARRAY;
                } else {
                    $this->_DatasetType = DATASET_TYPE_OBJECT;
                }
            }

            $this->cursor = -1;
            $this->pdoStatement = null;
            $this->_Result = $resultset;
        }
    }

    /**
     * Assigns the PDO statement object to this object.
     *
     * @param PDOStatement|false $pdoStatement The PDO Statement Object being assigned.
     * @return PDOStatement|void
     */
    public function pdoStatement(&$pdoStatement = false) {
        if ($pdoStatement === false) {
            return $this->pdoStatement;
        } else {
            $this->pdoStatement = $pdoStatement;
        }
    }

    /**
     * Unserialize the fields in the dataset.
     *
     * @param array $fields
     * @since 2.1
     */
    public function unserialize($fields = ['Attributes', 'Data']) {
        $result =& $this->result();
        $first = true;

        foreach ($result as &$row) {
            if ($first) {
                // Check which fields are in the dataset.
                foreach ($fields as $index => $field) {
                    if (val($field, $row, false) === false) {
                        unset($fields[$index]);
                    }
                }
                $first = false;
            }

            foreach ($fields as $field) {
                if (is_object($row)) {
                    if (is_string($row->$field)) {
                        $row->$field = dbdecode($row->$field);
                    }
                } else {
                    if (is_string($row[$field])) {
                        $row[$field] = dbdecode($row[$field]);
                    }
                }
            }
        }
    }

    /**
     * Advances to the next row and returns the value from a column.
     *
     * @param string $columnName The name of the column to get the value from.
     * @param string $defaultValue The value to return if there is no data.
     * @return mixed The value from the column or $defaultValue.
     */
    public function value($columnName, $defaultValue = null) {
        if ($row = $this->nextRow()) {
            if (is_array($columnName)) {
                $result = [];
                foreach ($columnName as $name => $default) {
                    if (is_object($row) && property_exists($row, $name)) {
                        return $row->$name;
                    } elseif (is_array($row) && array_key_exists($name, $row)) {
                        return $row[$name];
                    } else {
                        $result[] = $default;
                    }
                }
                return $result;
            } else {
                if (is_object($row) && property_exists($row, $columnName)) {
                    return $row->$columnName;
                } elseif (is_array($row) && array_key_exists($columnName, $row)) {
                    return $row[$columnName];
                }
            }
        }
        if (is_array($columnName)) {
            return array_values($columnName);
        }
        return $defaultValue;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize() {
        $result = $this->resultArray();
        jsonFilter($result);
        return $result;
    }

    /**
     * Returns the query options that created this DataSet
     *
     * @return array query options array or null
     */
    public function getQueryOptions(): array {
        return $this->queryOptions;
    }

    /**
     * Sets the query options that produced this DataSet
     *
     * @param array $options query options
     */
    public function setQueryOptions(array $options) {
        $this->queryOptions = $options;
    }
}
