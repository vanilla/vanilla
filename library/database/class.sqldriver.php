<?php
/**
 * Generic SQL database driver.
 *
 * The Gdn_DatabaseDriver class (equivalent to SqlBuilder from Vanilla 1.x) is used
 * by any given database driver to build and execute database queries.
 *
 * This class is HEAVILY inspired by and, in places, flat out copied from
 * CodeIgniter (http://www.codeigniter.com). My hat is off to them.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Class Gdn_SQLDriver
 */
abstract class Gdn_SQLDriver {

    /** @const 2^31 is the max signed int range. */
    const MAX_SIGNED_INT = 2147483648;

    /** @var array An associative array of table alias => table name pairs. */
    protected $_AliasMap;

    /** @var bool Whether or not to capture (not execute) DML statements. */
    public $CaptureModifications = false;

    /** @var string The name of the class that has been instantiated. */
    public $ClassName;

    /** @var Gdn_Database The connection and engine information for the database. */
    public $Database;

    /** @var string The name of the cache key associated with this query. */
    protected $_CacheKey = null;

    /** @var string|null Cache op. */
    protected $_CacheOperation = null;

    /** @var array|null Cache options. */
    protected $_CacheOptions = null;

    /**
     * @var string An associative array of information about the database to which the
     * application is connected. Values include: Engine, Version, DatabaseName.
     */
    protected $_DatabaseInfo = [];

    /** @var boolean A boolean value indicating if this is a distinct query. */
    protected $_Distinct;

    /** @var array A collection of tables from which data is being selected. */
    protected $_Froms;

    /** @var array A collection of group by clauses. */
    protected $_GroupBys;

    /** @var array A collection of having clauses. */
    protected $_Havings;

    /** @var array A collection of tables which have been joined to. */
    protected $_Joins;

    /** @var int The number of records to limit the query to. FALSE by default. */
    protected $_Limit;

    /**
     * @var array An associative array of parameter_name => parameter_value pairs to be
     * inserted into the prepared $this->_PDOStatement.
     */
    protected $_NamedParameters = [];

    /**
     * @var int Whether or not to reset the properties when a query is executed.
     *   0 = The object will reset after query execution.
     *   1 = The object will not reset after the <b>NEXT</b> query execution.
     *   2 = The object will not reset after <b>ALL</b> query executions.
     */
    protected $_NoReset = false;

    /** @var int The offset from which data should be returned. FALSE by default. */
    protected $_Offset;

    /** @var int The number of where groups currently open. */
    protected $_OpenWhereGroupCount;

    /**@var array Extended options for a statement, usable by the driver. */
    protected $_Options = [];

    /** @var array A collection of order by statements. */
    protected $_OrderBys;

    /** @var array A collection of fields that are being selected. */
    protected $_Selects;

    /** @var array An associative array of Field Name => Value pairs to be saved to the database. */
    protected $_Sets;

    /** @var string The logical operator used to concatenate where clauses.*/
    protected $_WhereConcat;

    /** @var string The default $_WhereConcat that will be reverted back to after every where clause is appended. */
    protected $_WhereConcatDefault;

    /** @var string The logical operator used to concatenate where group clauses. */
    protected $_WhereGroupConcat;

    /** @var string The default $_WhereGroupConcat that will be reverted back to after every where or where group clause is appended. */
    protected $_WhereGroupConcatDefault;

    /** @var int The number of where groups to open. */
    protected $_WhereGroupCount;

    /** @var array A collection of where clauses. */
    protected $_Wheres;

    /**
     *
     */
    public function __construct() {
        $this->ClassName = get_class($this);
        $this->reset();
    }

    /**
     * Removes table aliases from an array of JOIN ($this->_Joins) and GROUP BY
     * ($this->_GroupBys) strings. Returns the $Statements array with prefixes
     * removed.
     *
     * @param array $Statements The string specification of the table. ie.
     * "tbl_User as u" or "user u".
     * @return array the array of filtered statements.
     */
    //protected function _FilterTableAliases($Statements) {
    //   foreach ($Statements as $k => $v) {
    //      foreach ($this->_AliasMap as $Alias => $Table) {
    //         $Statement = preg_replace('/(\w+\.\w+)/', $this->escapeIdentifier('$0'), $v); // Makes `table.field`
    //         $Statement = str_replace(array($this->Database->DatabasePrefix.$Table, '.'), array($Table, $this->escapeSql('.')), $Statement);
    //      }
    //      $Statements[$k] = $Statement;
    //   }
    //   return $Statements;
    //}

    /**
     * Concat the next where expression with an 'and' operator.
     * <b>Note</b>: Since 'and' is the default operator to begin with this method doesn't usually have to be called,
     * unless Gdn_DatabaseDriver::or(FALSE) has previously been called.
     *
     * @param boolean $setDefault Whether or not the 'and' is one time or sets the default operator.
     * @return Gdn_SQLDriver $this
     * @see Gdn_DatabaseDriver::orOp()
     */
    public function andOp($setDefault = false) {
        $this->_WhereConcat = 'and';
        if ($setDefault) {
            $this->_WhereConcatDefault = 'and';
            $this->_WhereGroupConcatDefault = 'and';
        }

        return $this;
    }

    /**
     *
     *
     * @param $sql
     * @param null $parameters
     * @return mixed
     */
    public function applyParameters($sql, $parameters = null) {
        if (!is_array($parameters)) {
            $parameters = $this->_NamedParameters;
        }

        // Sort the parameters so that we don't have clashes.
        krsort($parameters);
        foreach ($parameters as $key => $value) {
            if (is_null($value)) {
                $qValue = 'null';
            } else {
                $qValue = $this->Database->connection()->quote($value);
            }
            $sql = str_replace($key, $qValue, $sql);
        }
        return $sql;
    }

    /**
     * A convenience method that calls Gdn_DatabaseDriver::BeginWhereGroup with concatenated with an 'or.'
     * @See Gdn_DatabaseDriver::beginWhereGroup()
     * @return Gdn_SQLDriver $this
     */
    public function orBeginWhereGroup() {
        return $this->orOp()->beginWhereGroup();
    }

    /**
     * Begin bracketed group in the where clause to group logical expressions together.
     *
     * @return Gdn_SQLDriver $this
     */
    public function beginWhereGroup() {
        $this->_WhereGroupConcat = $this->_WhereConcat;
        $this->_WhereGroupCount++;
        $this->_OpenWhereGroupCount++;
        return $this;
    }

    /**
     * Returns a single Condition Expression for use in a 'where' or an 'on' clause.
     *
     * @param string $field The name of the field on the left hand side of the expression.
     *   If $field ends with an operator, then it used for the comparison. Otherwise '=' will be used.
     * @param mixed $value The value on the right side of the expression. If $escapeValueSql is true then it will end up in a parameter.
     *
     * <b>Syntax</b>
     * The $field and Value expressions can begin with special characters to do certain things.
     * <ul>
     * <li><b>=</b>: This means that the argument is a function call.
     *   If you want to pass field reference arguments into the function then enclose them in square brackets.
     *   ex. <code>'=lEFT([u.Name], 4)'</code> will call the LEFT database function on the u.Name column.</li>
     * <li><b>@</b>: This means that the argument is a literal.
     *   This is useful for passing in literal numbers.</li>
     * <li><b>no prefix></b>: This will treat the argument differently depending on the argument.
     *   - <b>$field</b> - The argument is a column reference.
     *   - <b>$value</b> - The argument will become a named parameter.
     * </li></ul>
     * @return string The single expression.
     */
    public function conditionExpr($field, $value, $escapeFieldSql = true, $escapeValueSql = true) {
        // Change some variables from the old parameter style to the new one.
        // THIS PART OF THE FUNCTION SHOULD EVENTUALLY BE REMOVED.
        if ($escapeFieldSql === false) {
            $field = '@'.$field;
        }

        if (is_array($value)) {
            throw new Exception('Gdn_SQL->ConditionExpr(VALUE, ARRAY) is not supported.', 500);
        } elseif (!$escapeValueSql && !is_null($value)) {
            $value = '@'.$value;
        }

        // Check for a straight literal field expression.
        if (!$escapeFieldSql && !$escapeValueSql && is_null($value)) {
            return substr($field, 1); // warning: might not be portable across different drivers
        }
        $expr = ''; // final expression which is built up
        $op = ''; // logical operator

        // Try and split an operator out of $Field.
        $fieldOpRegex = "/(?:\s*(=|<>|>|<|>=|<=)\s*$)|\s+(like|not\s+like)\s*$|\s+(?:(is)\s+(null)|(is\s+not)\s+(null))\s*$/i";
        $split = preg_split($fieldOpRegex, $field, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        if (count($split) > 1) {
            $field = $split[0];
            $op = strtolower($split[1]);
            if (count($split) > 2) {
                $value = null;
            }
        } else {
            $op = '=';
        }

        if ($op == '=' && is_null($value)) {
            // This is a special case where the value SQL is checking for an is null operation.
            $op = 'is';
            $value = '@null';
            $escapeValueSql = false;
        }

        // Add the left hand side of the expression.
        $expr .= $this->_parseExpr($field, null, $escapeFieldSql);

        // Add the expression operator.
        $expr .= ' '.$op.' ';

        if ($op == 'is' || $op == 'is not' && is_null($value)) {
            $expr .= 'null';
        } else {
            // Add the right side of the expression.
            $expr .= $this->_parseExpr($value, $field, $escapeValueSql);
        }

        return $expr;
    }

    /**
     * Set the cache key for this transaction
     *
     * @param string|array $key The cache key (or array of keys) that this query will save into.
     * @param string $operation The cache operation as a hint to the db.
     * @param array $options The cache options as passed into Gdn_Cache::store().
     * @return Gdn_SQLDriver $this
     */
    public function cache($key, $operation = null, $options = null) {
        if (!$key) {
            $this->_CacheKey = null;
            $this->_CacheOperation = null;
            $this->_CacheOptions = null;

            return $this;
        }

        $this->_CacheKey = $key;

        if (!is_null($operation)) {
            $this->_CacheOperation = $operation;
        }

        if (!is_null($options)) {
            $this->_CacheOptions = $options;
        }

        return $this;
    }

    /**
     * Returns the name of the database currently connected to.
     */
    public function databaseName() {
        return $this->information('DatabaseName');
    }

    /**
     * Builds and executes a delete from query.
     *
     * @param mixed $table The table (or array of table names) to delete from.
     * @param mixed $where The string on the left side of the where comparison, or an associative
     * array of Field => Value items to compare.
     * @param int $limit The number of records to limit the query to.
     */
    public function delete($table = '', $where = '', $limit = false) {
        if ($table == '') {
            if (!isset($this->_Froms[0])) {
                return false;
            }

            $table = $this->_Froms[0];
        } elseif (is_array($table)) {
            foreach ($table as $t) {
                $this->delete($t, $where, $limit, false);
            }
            return;
        } else {
            $table = $this->escapeIdentifier($this->Database->DatabasePrefix.$table);
        }

        if ($where != '') {
            $this->where($where);
        }

        if ($limit !== false) {
            $this->limit($limit);
        }

        if (count($this->_Wheres) == 0) {
            return false;
        }

        $sql = $this->getDelete($table, $this->_Wheres, $this->_Limit);

        return $this->query($sql, 'delete');
    }

    /**
     * Specifies that the query should be run as a distinct so that duplicate
     * columns are grouped together. Returns this object for chaining purposes.
     *
     * @param boolean $bool A boolean value indicating if the query should be distinct or not.
     * @return Gdn_SQLDriver $this
     */
    public function distinct($bool = true) {
        $this->_Distinct = (is_bool($bool)) ? $bool : true;
        return $this;
    }

    /**
     * Removes all data from a table.
     *
     * @param string $table The table to empty.
     */
    public function emptyTable($table = '') {
        if ($table == '') {
            if (!isset($this->_Froms[0])) {
                return false;
            }

            $table = $this->_Froms[0];
        } else {
            $table = $this->escapeIdentifier($this->Database->DatabasePrefix.$table);
        }


        $sql = $this->getDelete($table);

        return $this->query($sql, 'delete');
    }

    /**
     * Closes off any open elements in the query before execution.
     * Ideally, the programmer should have everything closed off so this method will do nothing.
     */
    protected function _endQuery() {
        // Close the where groups.
        while ($this->_WhereGroupCount > 0) {
            $this->endWhereGroup();
        }
    }

    /**
     * End a bracketed group in the where clause.
     * <b>Note</b>: If no items where added to the group then no barackets will appear in the final statement.
     *
     * @return Gdn_SQLDriver $this
     */
    public function endWhereGroup() {
        if ($this->_WhereGroupCount > 0) {
            $whereCount = count($this->_Wheres);

            if ($this->_OpenWhereGroupCount >= $this->_WhereGroupCount) {
                $this->_OpenWhereGroupCount--;
            } elseif ($whereCount > 0) {
                $this->_Wheres[$whereCount - 1] .= ')';
            }

            $this->_WhereGroupCount--;
        }

        return $this;
    }

    /**
     * Takes a string formatted as an SQL field reference and escapes it for the defined database engine.
     *
     * @param string $refExpr The reference expression to be escaped.
     *   The reference should be in the form of alias.column.
     * @return string Returns the escaped string.
     */
    public function escapeIdentifier($refExpr) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'EscapeSql'), E_USER_ERROR);
    }

    /**
     * Escape the identifiers in a field reference expression.
     *
     * @param string $refExpr The field reference expression.
     * @paran bool $check Check to see if the field is already quoted.
     * @return string Returns an escaped expression.
     */
    protected function escapeFieldReference(string $refExpr, bool $check = true): string {
        if ($check && preg_match('/^`[^`]+`$/', $refExpr)) {
            return $refExpr;
        }

        return implode('.', array_map(function ($part) {
            if ($part === '*') {
                return $part;
            }
            return $this->escapeIdentifier($part);
        }, explode('.', $refExpr, 2)));
    }

    /**
     * Escape the keys of an array.
     *
     * @param callable $callback The escape function.
     * @param array $arr The array to escape.
     * @return array
     */
    protected function escapeKeys(callable $callback, array $arr) {
        $result = [];
        foreach ($arr as $key => $value) {
            $result[$callback($key)] = $value;
        }
        return $result;
    }

    /**
     * Takes a string of SQL and escapes it for the defined database engine.
     * ie. adds backticks or any other database-specific formatting.
     *
     * @param mixed $string The string (or array of strings) of SQL to be escaped.
     * @param boolean $firstWordOnly A boolean value indicating if the first word should be escaped only.
     * @deprecated
     */
    protected function escapeSql($string, $firstWordOnly = false) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'EscapeSql'), E_USER_ERROR);
    }

    /**
     * Returns a platform-specific query to fetch column data from $table.
     *
     * @param string $table The name of the table to fetch column data from.
     */
    public function fetchColumnSql($table) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'FetchColumnSql'), E_USER_ERROR);
    }

    /**
     * Returns a platform-specific query to fetch table names.
     * @param mixed $limitToPrefix Whether or not to limit the search to tables with the database prefix or a specific table name. The following types can be given for this parameter:
     *  - <b>TRUE</b>: The search will be limited to the database prefix.
     *  - <b>FALSE</b>: All tables will be fetched. Default.
     *  - <b>string</b>: The search will be limited to a like clause. The ':_' will be replaced with the database prefix.
     */
    public function fetchTableSql($limitToPrefix = false) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'FetchTableSql'), E_USER_ERROR);
    }

    /**
     * Returns an array containing table names in the database.
     * @param mixed $limitToPrefix Whether or not to limit the search to tables with the database prefix or a specific table name. The following types can be given for this parameter:
     *  - <b>TRUE</b>: The search will be limited to the database prefix.
     *  - <b>FALSE</b>: All tables will be fetched. Default.
     *  - <b>string</b>: The search will be limited to a like clause. The ':_' will be replaced with the database prefix.
     * @return array
     */
    public function fetchTables($limitToPrefix = false) {
        $sql = $this->fetchTableSql($limitToPrefix);
        $data = $this->query($sql);
        $return = [];
        foreach ($data->resultArray() as $row) {
            if (isset($row['TABLE_NAME'])) {
                $return[] = $row['TABLE_NAME'];
            } else {
                $return[] = array_shift($row);
            }
        }

        return $return;
    }

    /**
     * Returns an array of schema data objects for each field in the specified
     * table. The returned array of objects contains the following properties:
     * Name, PrimaryKey, Type, AllowNull, Default, Length, Enum.
     *
     * @param string $table The name of the table to get schema data for.
     */
    public function fetchTableSchema($table) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'FetchTableSchema'), E_USER_ERROR);
    }

    /**
     * Returns a string of SQL that retrieves the database engine version in the
     * fieldname "version".
     */
    public function fetchVersionSql() {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'FetchVersionSql'), E_USER_ERROR);
    }

    /**
     * Returns an array containing column names from $table.
     *
     * @param string $table The name of the table to fetch column data from.
     */
    public function fetchColumns($table) {
        $sql = $this->fetchColumnSql($table);
        $data = $this->query($sql);
        $return = [];
        foreach ($data->resultArray() as $row) {
            if (isset($row['COLUMN_NAME'])) {
                $return[] = $row['COLUMN_NAME'];
            } else {
                $return[] = current($row);
            }
        }

        return $return;
    }

    /**
     * Takes a table name and makes sure it is formatted for this database
     * engine.
     *
     * @param string $table The name of the table name to format.
     */
    public function formatTableName($table) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'FormatTableName'), E_USER_ERROR);
    }

    /**
     * The table(s) from which to select values. Returns this object for
     * chaining purposes.
     *
     * @param mixed $from A string or array of table names/aliases from which to select data.
     * Accepted formats include:
     *    user
     *    user, user u2, role
     *    array("user u", "user u2", "role")
     * @param boolean $escape Whether or not the from query should be escaped.
     *
     * @return Gdn_SQLDriver $this
     **/
    public function from($from, $escape = true) {
        if (!is_array($from)) {
            $from = [$from];
        }

        foreach ($from as $part) {
            $this->_Froms[] = $this->mapAliases($part, $escape);
        }

        return $this;
    }

    /**
     * Merge the named parameters from another SQL object with this one.
     *
     * This method is here to support some inner select optimizations. We intentionally try and leave parameters protected
     * as much as possible to support future changes.
     *
     * @param Gdn_SQLDriver $sql The query to merge the parameters from.
     * @return $this
     */
    public function mergeParameters(Gdn_SQLDriver $sql) {
        $this->_NamedParameters = array_replace($this->_NamedParameters, $sql->_NamedParameters);
        return $this;
    }

    /**
     * Returns a string of comma delimited table names to select from.
     *
     * @param mixed $tables The name of a table (or an array of table names) to be added in the from
     * clause of a query.
     */
    protected function _fromTables($tables) {
        return is_array($tables) ? implode(', ', $tables) : $tables;
    }

    /**
     * Builds the select statement and runs the query, returning a result object.
     *
     * @param string $table The table from which to select data. Adds to the $this->_Froms collection.
     * @param string $orderFields A string of fields to be ordered.
     * @param string $orderDirection The direction of the sort.
     * @param int $limit Adds a limit to the query.
     * @param int $pageNumber The page of data to retrieve.
     * @return Gdn_DataSet
     */
    public function get($table = '', $orderFields = '', $orderDirection = 'asc', $limit = false, $pageNumber = false) {
        if ($table != '') {
            //$this->mapAliases($Table);
            $this->from($table);
        }

        if ($orderFields != '') {
            $this->orderBy($orderFields, $orderDirection);
        }

        if ($limit !== false) {
            if ($pageNumber == false || $pageNumber < 1) {
                $pageNumber = 1;
            }

            $offset = ($pageNumber - 1) * $limit;
            $this->limit($limit, $offset);
        }

        $result = $this->query($this->getSelect());
        return $result;
    }

    /**
     * A helper function for escaping sql identifiers.
     *
     * @param string The sql containing identifiers to escape in a different language.
     *   All identifiers requiring escaping should be enclosed in back ticks (`).
     * @return array All of the tokens in the sql. The tokens that require escaping will still have back ticks.
     */
    protected function _getIdentifierTokens($sql) {
        $tokens = preg_split('/`/', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = [];

        $inIdent = false;
        $currentToken = '';
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[i];
            $result .= $token;
            if ($token == '`') {
                if ($inIdent && $i < count($tokens) - 1 && $tokens[$i + 1] == '`') {
                    // This is an escaped back tick.
                    $i++; // skip next token
                } elseif ($inIdent) {
                    $result[] = $currentToken;
                    $currentToken = $currentToken;
                    $inIdent = false;
                } else {
                    $inIdent = true;
                }
            } elseif (!$inIdent) {
                $result[] = $currentToken;
                $currentToken = '';
            }
        }

        return $result;
    }

    /**
     * Returns the total number of records in the specified table.
     *
     * @param string $table The table from which to count rows of data.
     * @param mixed $where Adds to the $this->_Wheres collection using $this->where();
     */
    public function getCount($table = '', $where = false) {
        if ($table != '') {
            //$this->mapAliases($Table);
            $this->from($table);
        }

        if ($where !== false) {
            $this->where($where);
        }

        $this->select('*', 'count', 'RowCount'); // count * slow on innodb
        $sql = $this->getSelect();
        $result = $this->query($sql);

        $countData = $result->firstRow();
        return $countData->RowCount;
    }

    /**
     * Returns the total number of records in the specified table.
     *
     * @param string $table The table from which to count rows of data.
     * @param mixed $like Adds to the $this->_Wheres collection using $this->like();
     */
    public function getCountLike($table = '', $like = false) {
        if ($table != '') {
            $this->mapAliases($table);
            $this->from($table);
        }

        if ($like !== false) {
            $this->like($like);
        }

        $this->select('*', 'count', 'RowCount');
        $result = $this->query($this->getSelect());

        $countData = $result->firstRow();
        return $countData->RowCount;
    }

    /**
     * Returns a delete statement for the specified table and the supplied
     * conditions.
     *
     * @param string $tableName The name of the table to delete from.
     * @param array $wheres An array of where conditions.
     */
    public function getDelete($tableName, $wheres = []) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetDelete'), E_USER_ERROR);
    }

    /**
     * Returns an insert statement for the specified $table with the provided $data.
     *
     * @param string $table The name of the table to insert data into.
     * @param string $data An associative array of FieldName => Value pairs that should be inserted
     * $table.
     */
    public function getInsert($table, $data) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetInsert'), E_USER_ERROR);
    }

    /**
     * Adds a limit clause to the provided query for this database engine.
     *
     * @param string $query The SQL string to which the limit statement should be appended.
     * @param int $limit The number of records to limit the query to.
     * @param int $offset The number of records to offset the query from.
     */
    public function getLimit($query, $limit, $offset) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetLimit'), E_USER_ERROR);
    }

    /**
     * Builds the select statement based on the various collections in this
     * object. This method should not be called directly; it is called by
     * $this->get() and $this->getWhere().
     */
    public function getSelect() {
        // Close off any open query elements.
        $this->_endQuery();

        $sql = (!$this->_Distinct) ? 'select ' : 'select distinct ';

        // Don't escape the field if it is numeric or an asterisk (all columns)
        $selects = [];
        foreach ($this->_Selects as $key => $expr) {
            $field = $expr['Field'];
            $function = $expr['Function'];
            $alias = $expr['Alias'];
            $caseOptions = val('CaseOptions', $expr);

            if ($alias == '' && $function != '') {
                $alias = $field;
            }

            // if (in_array(strtolower($Function), array('max', 'min', 'avg', 'sum', 'count')))
            if ($function != '') {
                if (strpos($function, '%s') !== false) {
                    $field = sprintf($function, $field);
                } else {
                    $field = $function.'('.$field.')';
                }
            }

            if ($caseOptions !== false) {
                $field = 'case '.$field.$caseOptions.' end';
            }

            if ($alias != '') {
                $field .= ' as '.$this->escapeIdentifier($alias);
            }

            if ($field != '') {
                $selects[] = $field;
            }
        }
        $sql .= (count($selects) == 0) ? '*' : implode(', ', $selects);

        if (count($this->_Froms) > 0) {
            $sql .= "\nfrom ".$this->_fromTables($this->_Froms);
        }

        if (count($this->_Joins) > 0) {
            $sql .= "\n";
            $sql .= implode("\n", $this->_Joins);
        }

        if (count($this->_Wheres) > 0) {
            $sql .= "\nwhere ";
        }

        $sql .= implode("\n", $this->_Wheres);

        // Close any where groups that were left open.
        for ($i = 0; $i < $this->_OpenWhereGroupCount; ++$i) {
            $sql .= ')';
        }
        $this->_OpenWhereGroupCount = 0;

        if (count($this->_GroupBys) > 0) {
            $sql .= "\ngroup by ";

            // special consideration for table aliases
            if (count($this->_AliasMap) > 0 && $this->Database->DatabasePrefix) {
                $sql .= implode(', ', $this->_filterTableAliases($this->_GroupBys));
            } else {
                $sql .= implode(', ', $this->_GroupBys);
            }
        }

        if (count($this->_Havings) > 0) {
            $sql .= "\nhaving ".implode("\n", $this->_Havings);
        }

        if (count($this->_OrderBys) > 0) {
            $sql .= "\norder by ".implode(', ', $this->_OrderBys);
        }

        if (is_numeric($this->_Limit)) {
            $sql .= "\n";
            $sql = $this->getLimit($sql, $this->_Limit, $this->_Offset);
        }

        return $sql;
    }

    /**
     * Returns a truncate statement for this database engine.
     *
     * @param string $table The name of the table to updated data in.
     */
    public function getTruncate($table) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetTruncate'), E_USER_ERROR);
    }

    /**
     * Returns an update statement for the specified table with the provided
     * $Data.
     *
     * @param array $tables The names of the tables to updated data in.
     * @param array $data An associative array of FieldName => Value pairs that should be inserted
     * $Table.
     * @param mixed $where A where clause (or array containing multiple where clauses) to be applied
     * @param mixed $orderBy A collection of order by statements.
     * @param mixed $limit The number of records to limit the query to.
     * to the where portion of the update statement.
     * @return string
     */
    public function getUpdate($tables, $data, $where, $orderBy = null, $limit = null) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetUpdate'), E_USER_ERROR);
    }

    /**
     * Builds the select statement and runs the query, returning a result
     * object. Allows a where clause, limit, and offset to be added directly.
     *
     * @param string $table The table from which to select data. Adds to the $this->_Froms collection.
     * @param mixed $where Adds to the $this->_Wheres collection using $this->where();
     * @param string $orderFields A string of fields to be ordered.
     * @param string $orderDirection The direction of the sort.
     * @param int $limit The number of records to limit the query to.
     * @param int $offset The offset where the query results should begin.
     * @return Gdn_DataSet The data returned by the query.
     */
    public function getWhere($table = '', $where = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = 0) {
        if ($table != '') {
            //$this->mapAliases($Table);
            $this->from($table);
        }

        if ($where !== false) {
            $this->where($where);
        }

        if ($orderFields != '') {
            $this->orderBy($orderFields, $orderDirection);
        }

        if ($limit !== false) {
            $this->limit($limit, $offset);
        }

        $result = $this->query($this->getSelect());

        return $result;
    }

    /**
     * Builds the select statement and runs the query, returning a result
     * object. Allows a like clause, limit, and offset to be added directly.
     *
     * @param string $table The table from which to select data. Adds to the $this->_Froms collection.
     * @param mixed $like Adds to the $this->_Wheres collection using $this->like();
     * @param string $orderFields A string of fields to be ordered.
     * @param string $orderDirection The direction of the sort.
     * @param int $limit The number of records to limit the query to.
     * @param int $pageNumber The offset where the query results should begin.
     */
    public function getWhereLike($table = '', $like = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $pageNumber = false) {
        if ($table != '') {
            $this->mapAliases($table);
            $this->from($table);
        }

        if ($like !== false) {
            $this->like($like);
        }

        if ($orderFields != '') {
            $this->orderBy($orderFields, $orderDirection);
        }

        if ($limit !== false) {
            if ($pageNumber == false || $pageNumber < 1) {
                $pageNumber = 1;
            }

            $offset = ($pageNumber - 1) * $limit;
            $this->limit($limit, $offset);
        }

        $result = $this->query($this->getSelect());

        return $result;
    }

    /**
     * Adds to the $this->_GroupBys collection.
     *
     * @param mixed $fields An array of field names (or a comma-delimited list of field names) to be
     * grouped by.
     * @return Gdn_SQLDriver $this
     */
    public function groupBy($fields = null) {
        if (is_null($fields)) {
            // Group by every item in the select that isn't a function.
            foreach ($this->_Selects as $alias => $select) {
                if (val('Function', $select) == '') {
                    $this->_GroupBys[] = $select['Field'];
                }
            }
            return $this;
        }

        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        foreach ($fields as $field) {
            $field = trim($field);

            if ($field != '') {
                $this->_GroupBys[] = $this->escapeFieldReference($field);
            }
        }
        return $this;
    }

    /**
     * Adds to the $this->_Havings collection.
     *
     * This is the most basic having that adds a freeform string of text.
     * It should be used only in conjunction with methods that properly escape the sql.
     *
     * @param string $sql The condition to add.
     * @return Gdn_SQLDriver $this
     */
    protected function _having($sql) {
        // Figure out the concatenation operator.
        $concat = '';

        if (count($this->_Havings) > 0) {
            $concat = ' '.$this->_WhereConcat.' ';
        }

        // Revert the concat back to 'and'.
        $this->_WhereConcat = $this->_WhereConcatDefault;

        $this->_Havings[] = $concat.$sql;

        return $this;
    }

    /**
     * Adds to the $this->_Havings collection. Called by $this->having() and
     * $this->orHaving().
     *
     * @param mixed $field The name of the field (or array of field names) in the having clause.
     * @param string $value The string on the right side of the having comparison.
     * @param boolean $EscapeSql A boolean value indicating if $this->EscapeSql method should be called
     * on $field.
     * @param boolean $EscapeString A boolean value indicating if $this->EscapeString method should be called
     * on $value.
     * @return Gdn_SQLDriver $this
     */
    function having($field, $value = '', $escapeField = true, $escapeValue = true) {
        if (!is_array($field)) {
            $field = [$field => $value];
        }

        foreach ($field as $childField => $childValue) {
            $expr = $this->conditionExpr($childField, $childValue, $escapeField, $escapeValue);
            $this->_having($expr);
        }

        return $this;
    }

    /**
     * @return Gdn_SQLDriver $this
     */
    public function history($updateFields = true, $insertFields = false) {
        $userID = valr('User.UserID', Gdn::session(), Gdn::session()->UserID);

        if ($insertFields) {
            $this->set('DateInserted', Gdn_Format::toDateTime())->set('InsertUserID', $userID);
        }
        if ($updateFields) {
            $this->set('DateUpdated', Gdn_Format::toDateTime())->set('UpdateUserID', $userID);
        }
        return $this;
    }

    /**
     * Returns the last identity to be inserted into the database at $this->_Connection.
     */
    public function identity() {
        return $this->connection()->lastInsertId();
    }

    /**
     * Returns information about the database. Values include: Engine, Version, DatabaseName.
     *
     * @param string $request The piece of information being requested. Accepted values are: Engine,
     * Version, and DatabaseName.
     */
    public function information($request) {
        if (array_key_exists($request, $this->_DatabaseInfo) === false) {
            if ($request == 'Version') {
                $this->_DatabaseInfo['Version'] = $this->version();
            } else {
                $this->_DatabaseInfo['HostName'] = Gdn::config('Database.Host', '');
                $this->_DatabaseInfo['DatabaseName'] = Gdn::config('Database.Name', '');
            }
        }
        if (array_key_exists($request, $this->_DatabaseInfo) === true) {
            return $this->_DatabaseInfo[$request];
        } else {
            return '';
        }
    }

    /**
     * Builds the insert statement and runs the query, returning a result
     * object.
     *
     * @param string $table The table to which data should be inserted.
     * @param mixed $set An associative array (or object) of FieldName => Value pairs that should
     * be inserted, or an array of FieldName values that should have values
     * inserted from $select.
     * @param string $select A select query that will fill the FieldNames specified in $set.
     */
    public function insert($table = '', $set = null, $select = '') {
        if (count($set) == 0 && count($this->_Sets) == 0) {
            return false;
        }

        if (!is_null($set) && $select == '' && !array_key_exists(0, $set)) {
            $this->set($set);
            $set = $this->_Sets;
        }

        if ($table == '') {
            if (!isset($this->_Froms[0])) {
                return false;
            }

            $table = $this->_Froms[0];
        }

        $sql = $this->getInsert($this->escapeIdentifier($this->Database->DatabasePrefix.$table), $set, $select);
        $result = $this->query($sql, 'insert');

        return $result;
    }

    /**
     * Inserts or updates values in the table depending on whether they are already there.
     *
     * @param string $table The name of the table to insert/update.
     * @param array $set The columns to update.
     * @param array $where The columns to find the row to update.
     * If a row is not found then one is inserted and the items in this array are merged with $set.
     */
    public function replace($table = '', $set = null, $where, $checkExisting = false) {
        $set = $this->escapeKeys([$this, 'escapeFieldReference'], $set);

        if (count($this->_Sets) > 0) {
            foreach ($this->_Sets as $key => $value) {
                if (array_key_exists($value, $this->_NamedParameters)) {
                    $set[$key] = $this->_NamedParameters[$value];
                    unset($this->_NamedParameters[$value]);
                } else {
                    $set[$key] = $value;
                }
            }
            $this->_Sets = [];
        }

        // Check to see if there is a row in the table like this.
        if ($checkExisting) {
            $row = $this->getWhere($table, $where)->firstRow(DATASET_TYPE_ARRAY);

            $update = false;
            if ($row) {
                $update = true;
                foreach ($set as $key => $value) {
                    unset($set[$key]);
                    $key = $this->unescapeIdentifier($key);

                    if (!$this->CaptureModifications && !array_key_exists($key, $row)) {
                        continue;
                    }

                    if (in_array($key, ['DateInserted', 'InsertUserID', 'DateUpdated', 'UpdateUserID'])) {
                        continue;
                    }


                    // We are assuming here that if the existing record doesn't contain the column then it's just been added.
                    if (preg_match('/^`(.+)`$/', $value, $matches)) {
                        if (!array_key_exists($key, $row) || $row[$key] != $row[$matches[1]]) {
                            $this->set($this->escapeIdentifier($key), $value, false);
                        }
                    } elseif (!array_key_exists($key, $row) || $row[$key] != $value) {
                        $this->set($this->escapeIdentifier($key), $value);
                    }

                }
                if (count($this->_Sets) === 0) {
                    $this->reset();
                    return;
                }
            }
        } else {
            $count = $this->getCount($table, $where);
            $update = $count > 0;
        }

        if ($update) {
            // Update the table.
            $this->put($table, $set, $where);
        } else {
            // Insert the table.
            $set = array_merge($set, $this->escapeKeys([$this, 'escapeFieldReference'], $where));
            $this->insert($table, $set);
        }
    }

    /**
     * The table(s) to which this query should join. Returns this object for
     * chaining purposes.
     *
     * @param string $tableName The name of a single table to join to.
     * @param string $on The conditions on which the join should occur.
     * ie. "user.role_id = role.id"
     * @param string $join The type of join to be made. Accepted values are:
     * 'inner', 'outer', 'left', 'right', 'left outer', and 'right outer'.
     * @return Gdn_SQLDriver $this
     */
    public function join($tableName, $on, $join = '') {
        $join = strtolower(trim($join));
        if ($join != '' && !in_array($join, ['inner', 'outer', 'left', 'right', 'left outer', 'right outer'], true)) {
            $join = '';
        }

        // Add the table prefix to any table specifications in the clause
        // echo '<div>'.$TableName.' ---> '.$this->escapeSql($this->Database->DatabasePrefix.$TableName, TRUE).'</div>';
        if ($this->Database->DatabasePrefix && $tableName[0] !== '(') {
            $tableName = $this->mapAliases($tableName);
        }
        $joinClause = ltrim($join.' join ')."$tableName on $on";
        $this->_Joins[] = $joinClause;

        return $this;
    }

    /**
     * A convenience method for Gdn_DatabaseDriver::Join that makes the join type 'left.'
     * @see Gdn_DatabaseDriver::join()
     */
    public function leftJoin($tableName, $on) {
        return $this->join($tableName, $on, 'left');
    }

    /**
     * Adds to the $this->_Wheres collection. Used to generate the LIKE portion
     * of a query. Called by $this->like(), $this->notLike()
     *
     * @param mixed $field The field name (or array of field name => match values) to search in for
     * a like $match.
     * @param string $match The value to try to match using a like statement in $field.
     * @param string $Concat The concatenation operator for the items being added to the like in
     * clause.
     * @param string $side A string indicating which side of the match to place asterisk operators.
     * Accepted values are left, right, both, none. Default is both.
     * @param string $op Either 'like' or 'not like' clause.
     * @return Gdn_SQLDriver $this
     */
    public function like($field, $match = '', $side = 'both', $op = 'like') {
        if (!is_array($field)) {
            $field = [$field => $match];
        }

        foreach ($field as $subField => $subValue) {
            $subField .= ' '.$op.' ';
            switch ($side) {
                case 'left':
                    $subValue = '%'.$subValue;
                    break;
                case 'right':
                    $subValue .= '%';
                    break;
                case 'both':
                    if (strlen($match) == 0) {
                        $subValue = '%';
                    } else {
                        $subValue = '%'.$subValue.'%';
                    }
                    break;
            }
            $expr = $this->conditionExpr($subField, $subValue);
            $this->_where($expr);
        }
        return $this;
    }

    /**
     * Sets the limit (and offset optionally) for the query.
     *
     * @param int $limit The number of records to limit the query to.
     * @param int $offset The offset where the query results should begin.
     * @return Gdn_SQLDriver $this
     */
    public function limit($limit, $offset = false) {
        // SQL chokes on ints over 2^31
        if ($limit > self::MAX_SIGNED_INT) {
            throw new Exception(t('Invalid limit.'), 400);
        }

        $this->_Limit = $limit;

        if ($offset !== false) {
            $this->offset($offset);
        }

        return $this;
    }

    /**
     * Takes a provided table specification and parses out any table aliases
     * provided, placing them in an alias mapping array. Returns the table
     * specification with any table prefix prepended.
     *
     * @param string $tableString The string specification of the table. ie. "tbl_User as u" or "user u".
     * @param boolean $escape Whether or not to escape the tables and aliases.
     * @return string
     */
    public function mapAliases($tableString, $escape = true) {
        if (preg_match('`^([^\s]+?)(?:\s+(?:as\s+)?([a-z_][a-z0-9_]*))?$`i', trim($tableString), $m)) {
            $tableName = $m[1];
            $alias = $m[2] ?? $tableName;

            $fullTableName = $this->Database->DatabasePrefix.$tableName;
            $escapedTableName = $escape ? $this->escapeIdentifier($fullTableName) : $fullTableName;
            $escapedAlias = $escape ? $this->escapeIdentifier($alias) : $alias;

            return $escapedTableName.' '.$escapedAlias;
        } else {
            throw new \InvalidArgumentException("Unknown table expression: $tableString", 500);
        }
    }

    /**
     * A convenience method for Gdn_DatabaseDriver::Like that changes the operator to 'not like.'
     * @see Gdn_DatabaseDriver::like()
     */
    public function notLike($field, $match = '', $side = 'both') {
        return $this->like($field, $match, $side, 'not like');
    }

    /**
     * Takes a parameter name and makes sure it is cleaned up to be used as a
     * named parameter in a pdo prepared statement.
     * @param string $name The name of the parameter to cleanup
     * @param boolean $createNew Wether or not this is a new or existing parameter.
     * @return string The cleaned up named parameter name.
     */
    public function namedParameter($name, $createNew = false, $value = null) {
        // Format the parameter name so it is safe for sql
        $niceName = ':'.preg_replace('/([^\w])/', '', $name); // Removes everything from the string except letters, numbers and underscores

        if ($createNew) {
            // Make sure that the new name doesn't already exist.
            $numberedName = $niceName;
            $i = 0;
            while (array_key_exists($numberedName, $this->_NamedParameters)) {
                $numberedName = $niceName.$i;
                ++$i;
            }
            $niceName = $numberedName;
        }

        if (!is_null($value)) {
            $this->_NamedParameters[$niceName] = $value;
        }

        return $niceName;
    }

    public function &namedParameters($newValue = null) {
        if ($newValue !== null) {
            $this->_NamedParameters = $newValue;
        }
        $result =& $this->_NamedParameters;
        return $result;
    }

    /**
     * Allows a query to be called without resetting the object.
     * @param boolean $Reset Whether or not to reset this object when the next query executes.
     * @param boolean $oneTime Whether or not this will apply for only the next query or for all subsequent queries.
     * @return Gdn_SQLDriver $this
     */
    public function noReset($noReset = true, $oneTime = true) {
        $_NoReset = $noReset ? ($oneTime ? 1 : 2) : 0;
        return $this;
    }

    /**
     * Sets the offset for the query.
     *
     * @param int $offset The offset where the query results should begin.
     * @return Gdn_SQLDriver $this
     */
    public function offset($offset) {
        // SQL chokes on ints over 2^31
        if ($offset > self::MAX_SIGNED_INT) {
            throw new Exception(t('Invalid offset.'), 400);
        }

        $this->_Offset = $offset;
        return $this;
    }

    /**
     * Gets/sets an option on the object.
     *
     * @param string $key The key of the option.
     * @param mixed $value The value of the option or not specified just to get the current value.
     * @return mixed The value of the option or $this if $value is specified.
     */
    public function options($key, $value = null) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->_Options[$k] = $v;
                return $this;
            }
        } elseif ($value !== null) {
            $this->_Options[$key] = $value;
            return $this;
        } elseif (isset($this->_Options[$key]))
            return $this->_Options[$key];
        else {
            return null;
        }
    }

    /**
     * Adds to the $this->_OrderBys collection.
     *
     * @param string $fields A string of fields to be ordered.
     * @param string $direction The direction of the sort.
     * @return Gdn_SQLDriver $this
     */
    public function orderBy($fields, $direction = 'asc') {
        if (!$fields) {
            return $this;
        }

        $fields = explode(',', "$fields $direction");

        foreach ($fields as $parts) {
            if (preg_match('`^([^\s]+?)(?:\s+?(asc|desc))?$`i', trim($parts), $m)) {
                $field = $m[1];
                $direction = $m[2] ?? 'asc';

                $this->_OrderBys[] = $this->escapeFieldReference($field).' '.$direction;
            } else {
                trigger_error("Invalid order by expression: $parts");
            }
        }

        return $this;
    }

    /**
     * Adds to the $this->_Havings collection. Concatenates multiple calls with OR.
     *
     * @param mixed $field The name of the field (or array of field names) in the having clause.
     * @param string $value The string on the right side of the having comparison.
     * @param boolean $escapeField A boolean value indicating if $this->EscapeSql method should be called
     * on $field.
     * @param boolean $escapeValue A boolean value indicating if $this->EscapeString method should be called
     * on $value.
     * @return Gdn_SQLDriver $this
     * @see Gdn_DatabaseDriver::having()
     */
    function orHaving($field, $value = '', $escapeField = true, $escapeValue = true) {
        return $this->orOp()->having($field, $value, $escapeField, $escapeValue);
    }

    /**
     * A convenience method that calls Gdn_DatabaseDriver::Like with concatenated with an 'or.'
     * @See Gdn_DatabaseDriver::like()
     * @return Gdn_SQLDriver $this
     */
    public function orLike($field, $match = '', $side = 'both', $op = 'like') {
        if (!is_array($field)) {
            $field = [$field => $match];
        }

        foreach ($field as $f => $v) {
            $this->orOp()->like($f, $v, $side, $op);
        }
        return $this;

//       return $this->orOp()->like($Field, $Match, $Side, $Op);
    }

    /** A convenience method for Gdn_DatabaseDriver::Like that changes the operator to 'not like,'
     *    and is concatenated with an 'or.'
     * @see Gdn_DatabaseDriver::notLike()
     * @see GenricDriver::like()
     */
    public function orNotLike($field, $match = '', $side = 'both') {
        return $this->orLike($field, $match, $side, 'not like');
    }

    /**
     * Concat the next where expression with an 'or' operator.
     *
     * @param boolean $setDefault Whether or not the 'or' is one time, or will revert.
     * @return Gdn_SQLDriver $this
     * @see Gdn_DatabaseDriver::andOp()
     */
    public function orOp($setDefault = false) {
        $this->_WhereConcat = 'or';
        if ($setDefault) {
            $this->_WhereConcatDefault = 'or';
            $this->_WhereGroupConcatDefault = 'or';
        }

        return $this;
    }

    /**
     * @link Gdn_DatabaseDriver::where()
     */
    public function orWhere($field, $value = null, $escapeFieldSql = true, $escapeValueSql = true) {
        return $this->orOp()->where($field, $value, $escapeFieldSql, $escapeValueSql);
    }

    /**
     * A convienience method for Gdn_DatabaseDriver::whereExists() concatenates with an 'or.'
     * @see Gdn_DatabaseDriver::whereExists()
     */
    public function orWhereExists($sqlDriver, $op = 'exists') {
        return $this->orOp()->whereExists($sqlDriver, $op);
    }

    /**
     * @ling Gdn_DatabaseDriver::whereIn()
     */
    public function orWhereIn($field, $values) {
        return $this->orOp()->whereIn($field, $values);
    }

    /**
     * A convienience method for Gdn_DatabaseDriver::whereExists() that changes the operator to 'not exists,'
     *   and concatenates with an 'or.'
     * @see Gdn_DatabaseDriver::whereExists()
     * @see Gdn_DatabaseDriver::whereNotExists()
     */
    public function orWhereNotExists($sqlDriver) {
        return $this->orWhereExists($sqlDriver, 'not exists');
    }

    /**
     * A convenience method for Gdn_DatabaseDriver::whereIn() that changes the operator to 'not in,'
     *   and concatenates with an 'or.'
     * @see Gdn_DatabaseDriver::whereIn()
     * @see Gdn_DatabaseDriver::whereNotIn()
     */
    public function orWhereNotIn($field, $values) {
        return $this->orOp()->whereNotIn($field, $values);
    }

    /**
     * Parses an expression for use in where clauses.
     *
     * @param string $expr The expression to parse.
     * @param string $name A name to give the parameter if $expr becomes a named parameter.
     * @return string The parsed expression.
     */
    protected function _parseExpr($expr, $name = null, $escapeExpr = false) {
        $result = '';

        $c = substr($expr, 0, 1);

        if ($c === '=' && $escapeExpr === false) {
            // This is a function call. Each parameter has to be parsed.
            $functionArray = preg_split('/(\[[^\]]+\])/', substr($expr, 1), -1, PREG_SPLIT_DELIM_CAPTURE);
            for ($i = 0; $i < count($functionArray); $i++) {
                $part = $functionArray[$i];
                if (substr($part, 1) == '[') {
                    // Translate the part of the function call.
                    $part = $this->_fieldExpr(substr($part, 1, strlen($part) - 2), $name);
                    $functionArray[$i] = $part;
                }
            }
            // Combine the array back to the original function call.
            $result = join('', $functionArray);
        } elseif ($c === '@' && $escapeExpr === false) {
            // This is a literal. Don't do anything.
            $result = substr($expr, 1);
        } else {
            // This is a column reference.
            if (is_null($name)) {
                $result = $this->escapeFieldReference($expr);
            } else {
                // This is a named parameter.

                // Check to see if the named parameter is valid.
                if (in_array(substr($expr, 0, 1), ['=', '@'])) {
                    // The parameter has to be a default name.
                    $result = $this->namedParameter('Param', true);
                } else {
                    $result = $this->namedParameter($name, true);
                }
                $this->_NamedParameters[$result] = $expr;
            }
        }

        return $result;
    }

    /**
     * Joins the query to a permission junction table and limits the results accordingly.
     *
     * @param mixed $permission The permission name (or array of names) to use when limiting the query.
     * @param string $foreignAlias The alias of the table to join to (ie. Category).
     * @param string $foreignColumn The primary key column name of $junctionTable (ie. CategoryID).
     * @param string $junctionTable
     * @param string $junctionColumn
     * @return Gdn_SQLDriver $this
     */
    public function permission($permission, $foreignAlias, $foreignColumn, $junctionTable = '', $junctionColumn = '') {
        $permissionModel = Gdn::permissionModel();
        $permissionModel->sqlPermission($this, $permission, $foreignAlias, $foreignColumn, $junctionTable, $junctionColumn);

        return $this;
    }

    /**
     * Prefixes a table with the database prefix if it is not already there.
     *
     * @param string $table The table name to prefix.
     */
    public function prefixTable($table) {
        $prefix = $this->Database->DatabasePrefix;

        if ($prefix != '' && substr($table, 0, strlen($prefix)) != $prefix) {
            $table = $prefix.$table;
        }

        return $table;
    }

    /**
     * Builds the update statement and runs the query, returning a result object.
     *
     * @param string $table The table to which data should be updated.
     * @param mixed $set An array of $FieldName => $Value pairs, or an object of $DataSet->Field
     * properties containing one rowset.
     * @param string $where Adds to the $this->_Wheres collection using $this->where();
     * @param int $limit Adds a limit to the query.
     */
    public function put($table = '', $set = null, $where = false, $limit = false) {
        $this->update($table, $set, $where, $limit);

        if (count($this->_Sets) == 0 || !isset($this->_Froms[0])) {
            $this->reset();
            return false;
        }

        $sql = $this->getUpdate($this->_Froms, $this->_Sets, $this->_Wheres, $this->_OrderBys, $this->_Limit);
        $result = $this->query($sql, 'update');

        return $result;
    }

    public function query($sql, $type = 'select') {
        $queryOptions = ['Type' => $type, 'Slave' => val('Slave', $this->_Options, null)];

        switch ($type) {
            case 'insert':
                $returnType = 'ID';
                break;
            case 'update':
            case 'delete':
                $returnType = '';
                break;
            default:
                $returnType = 'DataSet';
                break;
        }

        $queryOptions['ReturnType'] = $returnType;
        if (!is_null($this->_CacheKey)) {
            $queryOptions['Cache'] = $this->_CacheKey;
        }

        if (!is_null($this->_CacheKey)) {
            $queryOptions['CacheOperation'] = $this->_CacheOperation;
        }

        if (!is_null($this->_CacheOptions)) {
            $queryOptions['CacheOptions'] = $this->_CacheOptions;
        }

        $parameters = $this->calculateParameters($this->_NamedParameters);

        try {
            if ($this->CaptureModifications && strtolower($type) != 'select') {
                if (!property_exists($this->Database, 'CapturedSql')) {
                    $this->Database->CapturedSql = [];
                }
                $sql2 = $this->applyParameters($sql, $parameters);

                $this->Database->CapturedSql[] = $sql2;
                $this->reset();
                return true;
            }

            $result = $this->Database->query($sql, $parameters, $queryOptions);
        } catch (Exception $ex) {
            $this->reset();
            throw $ex;
        }
        $this->reset();

        return $result;
    }

    /**
     * Do anything necessary to coerce parameter values into something appropriate for the database.
     *
     * @param array $parameters The parameters to calculate.
     * @return array New parameters
     */
    protected function calculateParameters($parameters) {
        $dtZone = new DateTimeZone('UTC');

        $result = [];
        foreach ($parameters as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $dt = new DateTime('@'.$value->getTimestamp());
                $dt->setTimezone($dtZone);
                $value = $dt->format(MYSQL_DATE_FORMAT);
            } elseif (is_bool($value)) {
                $value = (int)$value;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Quote an identifier such as a table or column name.
     *
     * @param string $string The identifier to quote.
     * @return string Returns a quoted string.
     * @deprecated
     */
    public function quoteIdentifier($string) {
        deprecated('Gdn_SQLDriver::quoteIdentifier()', 'Gdn_SQLDriver::escapeIdentifier()');
        return $this->escapeIdentifier($string);
    }

    /**
     * Unquote an already quoted identifier.
     *
     * @param string $string The quoted identifier.
     * @return string Returns the unquoted identifer.
     */
    public function unescapeIdentifier(string $string): string {
        return preg_replace_callback('/(`+)/', function ($m) {
            return str_repeat('`', intdiv(strlen($m[1]), 2));
        }, $string);
    }

    /**
     * Resets properties of this object that relate to building a select
     * statement back to their default values. Called by $this->get() and
     * $this->getWhere().
     * @return Gdn_SQLDriver $this
     */
    public function reset() {
        // Check the _NoReset flag.
        switch ($this->_NoReset) {
            case 1:
                $this->_NoReset = 0;
                return;
            case 2:
                return;
        }
        $this->_Selects = [];
        $this->_Froms = [];
        $this->_Joins = [];
        $this->_Wheres = [];
        $this->_WhereConcat = 'and';
        $this->_WhereConcatDefault = 'and';
        $this->_WhereGroupConcat = 'and';
        $this->_WhereGroupConcatDefault = 'and';
        $this->_WhereGroupCount = 0;
        $this->_OpenWhereGroupCount = 0;
        $this->_GroupBys = [];
        $this->_Havings = [];
        $this->_OrderBys = [];
        $this->_AliasMap = [];

        $this->_CacheKey = null;
        $this->_CacheOperation = null;
        $this->_CacheOptions = null;
        $this->_Distinct = false;
        $this->_Limit = false;
        $this->_Offset = false;
        $this->_Order = false;

        $this->_Sets = [];
        $this->_NamedParameters = [];
        $this->_Options = [];

        return $this;
    }

    /**
     * Allows the specification of columns to be selected in a database query.
     * Returns this object for chaining purposes. ie. $db->select()->from();
     *
     * @param mixed $select NotRequired "*" The field(s) being selected. It
     * can be a comma delimited string, the name of a single field, or an array
     * of field names.
     * @param string $function NotRequired "" The aggregate function to be used on
     * the select column. Only valid if a single column name is provided.
     * Accepted values are MAX, MIN, AVG, SUM.
     * @param string $alias NotRequired "" The alias to give a column name.
     * @return Gdn_SQLDriver $this
     */
    public function select($select = '*', $function = '', $alias = '') {
        if (is_string($select)) {
            if ($function == '') {
                $select = explode(',', $select);
            } else {
                $select = [$select];
            }
        }
        $count = count($select);

        for ($i = 0; $i < $count; $i++) {
            $field = trim($select[$i]);

            // Try and figure out an alias for the field.
            if ($alias == '' || ($count > 1 && $i > 0)) {
                if (preg_match('/^([^\s]+)\s+(?:as\s+)?`?([^`]+)`?$/i', $field, $matches) > 0) {
                    // This is an explicit alias in the select clause.
                    $field = $matches[1];
                    $alias = $matches[2];
                } else {
                    // This is an alias from the field name.
                    $alias = trim(strstr($field, '.'), ' .`');
                }
                // Make sure we aren't selecting * as an alias.
                if ($alias === '*') {
                    $alias = '';
                }
            }

            $expr = ['Field' => $field, 'Function' => $function, 'Alias' => $alias];

            if ($alias == '') {
                $this->_Selects[] = $expr;
            } else {
                $this->_Selects[$alias] = $expr;
            }
        }
        return $this;
    }

    /**
     * Allows the specification of a case statement in the select list.
     *
     * @param string $field The field being examined in the case statement.
     * @param array $options The options and results in an associative array. A
     * blank key will be the final "else" option of the case statement. eg.
     * array('null' => 1, '' => 0) results in "when null then 1 else 0".
     * @param string $alias The alias to give a column name.
     * @return Gdn_SQLDriver $this
     */
    public function selectCase($field, $options, $alias) {
        $caseOptions = '';
        foreach ($options as $key => $val) {
            if ($key == '') {
                $caseOptions .= ' else '.$val;
            } else {
                $caseOptions .= ' when '.$key.' then '.$val;
            }
        }

        $expr = ['Field' => $field, 'Function' => '', 'Alias' => $alias, 'CaseOptions' => $caseOptions];

        if ($alias == '') {
            $this->_Selects[] = $expr;
        } else {
            $this->_Selects[$alias] = $expr;
        }

        return $this;
    }

    /**
     * Adds values to the $this->_Sets collection. Allows for the inserting
     * and updating of values to the db.
     *
     * @param mixed $field The name of the field to save value as. Alternately this can be an array
     * of $fieldName => $value pairs, or even an object of $DataSet->Field properties containing one rowset.
     * @param string $value The value to be set in $field. Ignored if $field was an array or object.
     * @param boolean $escapeString A boolean value indicating if the $value(s) should be escaped or not.
     * @param boolean $createNewNamedParameter A boolean value indicating that if (a) a named parameter is being
     * created, and (b) that name already exists in $this->_NamedParameters
     * collection, then a new one should be created rather than overwriting the
     * existing one.
     * @return Gdn_SQLDriver $this Returns this for fluent calls
     * @throws \Exception Throws an exception if an invalid type is passed for {@link $value}.
     */
    public function set($field, $value = '', $escapeString = true, $createNewNamedParameter = true) {
        $field = Gdn_Format::objectAsArray($field);

        if (!is_array($field)) {
            $field = [$field => $value];
        }

        foreach ($field as $f => $v) {
            if (is_array($v) || is_object($v)) {
                throw new Exception('Invalid value type ('.gettype($v).') in INSERT/UPDATE statement.', 500);
            } else {
                $escapedName = $this->escapeFieldReference($f, true);
                if (in_array(substr($f, -1),  ['+', '-'], true)) {
                    // This is an increment/decrement.
                    $op = substr($f, -1);
                    $f = substr($f, 0, -1);
                    $escapedName = $this->escapeFieldReference($f, true);

                    $parameter = $this->namedParameter($f, $createNewNamedParameter);
                    $this->_NamedParameters[$parameter] = $v;
                    $this->_Sets[$escapedName] = "$escapedName $op $parameter";
                } elseif ($escapeString) {
                    $namedParameter = $this->namedParameter($f, $createNewNamedParameter);
                    $this->_NamedParameters[$namedParameter] = $v;
                    $this->_Sets[$escapedName] = $namedParameter;
                } else {
                    $this->_Sets[$escapedName] = $v;
                }
            }
        }

        return $this;
    }

    /**
     * Sets the character encoding for this database engine.
     */
    public function setEncoding($encoding) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'SetEncoding'), E_USER_ERROR);
    }

    /**
     * Similar to $this->set() in every way except that if a named parameter is
     * used in place of $value, it will overwrite any existing value associated
     * with that name as opposed to adding a new name/value (which is the
     * default way that $this->set() works).
     *
     * @param mixed $field The name of the field to save value as. Alternately this can be an array
     * of $fieldName => $value pairs, or even an object of $DataSet->Field
     * properties containing one rowset.
     * @param string $value The value to be set in $field. Ignored if $field was an array or object.
     * @param boolean $escapeString A boolean value indicating if the $value(s) should be escaped or not.
     */
    public function setOverwrite($field, $value = '', $escapeString = true) {
        return $this->set($field, $value, $escapeString, false);
    }

    /**
     * Truncates all data from a table (will delete from the table if database
     * does not support truncate).
     *
     * @param string $table The table to truncate.
     */
    public function truncate($table = '') {
        if ($table == '') {
            if (!isset($this->_Froms[0])) {
                return false;
            }

            $table = $this->_Froms[0];
        } else {
            $table = $this->escapeIdentifier($this->Database->DatabasePrefix.$table);
        }

        $sql = $this->getTruncate($table);
        $result = $this->query($sql, 'truncate');
        return $result;
    }

    /**
     * Allows the specification of a table to be updated in a database query.
     * Returns this object for chaining purposes. ie. $db->update()->join()->set()->where();
     *
     * @param string $table The table to which data should be updated.
     * @param mixed $set An array of $FieldName => $Value pairs, or an object of $DataSet->Field
     * properties containing one rowset.
     * @param string $where Adds to the $this->_Wheres collection using $this->where();
     * @param int $limit Adds a limit to the query.
     * @return Gdn_SQLDriver $this
     */
    public function update($table, $set = null, $where = false, $limit = false) {
        if ($table != '') {
            $this->from($table);
        }

        if (!is_null($set)) {
            $this->set($set);
        }

        if ($where !== false) {
            $this->where($where);
        }

        if ($limit !== false) {
            $this->limit($limit);
        }

        return $this;
    }

    /**
     * Returns a plain-english string containing the version of the database engine.
     */
    public function version() {
        $query = $this->query($this->fetchVersionSql());
        return $query->value('Version');
    }

    /**
     * Adds to the $this->_Wheres collection. This is the most basic where that adds a freeform string of text.
     *   It should be used only in conjunction with methods that properly escape the sql.
     * @param string $sql The condition to add.
     * @return Gdn_SQLDriver $this
     */
    protected function _where($sql) {
        // Figure out the concatenation operator.
        $concat = '';

        if ($this->_OpenWhereGroupCount > 0) {
            $this->_WhereConcat = $this->_WhereGroupConcat;
        }

        if (count($this->_Wheres) > 0) {
            $concat = str_repeat(' ', $this->_WhereGroupCount + 1).$this->_WhereConcat.' ';
        }

        // Open the group(s) if necessary.
        while ($this->_OpenWhereGroupCount > 0) {
            $concat .= '(';
            $this->_OpenWhereGroupCount--;
        }

        // Revert the concat back to 'and'.
        $this->_WhereConcat = $this->_WhereConcatDefault;
        $this->_WhereGroupConcat = $this->_WhereGroupConcatDefault;

        $this->_Wheres[] = $concat.$sql;

        return $this;
    }

    /**
     * Adds to the $this->_Wheres collection. Called by $this->where() and $this->orWhere();
     *
     * @param mixed $field The string on the left side of the comparison, or an associative array of
     * Field => Value items to compare.
     * @param mixed $value The string on the right side of the comparison. You can optionally
     * provide an array of DatabaseFunction => Value, which will be converted to
     * databaseFunction('Value'). If DatabaseFunction contains a '%s' then sprintf will be used for to place DatabaseFunction into the value.
     * @param boolean $escapeFieldSql A boolean value indicating if $this->EscapeSql method should be called
     * on $field.
     * @param boolean $EscapeValueString A boolean value indicating if $this->EscapeString method should be called
     * on $value.
     * @return Gdn_SQLDriver $this
     */
    public function where($field, $value = null, $escapeFieldSql = true, $escapeValueSql = true) {
        if (!is_array($field)) {
            $field = [$field => $value];
        }

        foreach ($field as $subField => $subValue) {
            if (is_array($subValue)) {
                if (count($subValue) == 1) {
                    $firstVal = reset($subValue);
                    $this->where($subField, $firstVal);
                } else {
                    $this->whereIn($subField, $subValue);
                }
            } else {
                $whereExpr = $this->conditionExpr($subField, $subValue, $escapeFieldSql, $escapeValueSql);
                if (strlen($whereExpr) > 0) {
                    $this->_where($whereExpr);
                }
            }
        }
        return $this;
    }

    /**
     * Get the number of items in the where array.
     *
     * @return int Returns the number of items in the where array.
     */
    public function whereCount() {
        return count($this->_Wheres);
    }

    /**
     * Adds to the $this->_WhereIns collection. Used to generate a "where field
     * in (1,2,3)" query. Called by $this->whereIn(), $this->orWhereIn(),
     * $this->whereNotIn(), and $this->orWhereNotIn().
     *
     * @param string $field The field to search in for $values.
     * @param array $values An array of values to look for in $field.
     * @param string $op Either 'in' or 'not in' for the respective operation.
     * @param string $escape Whether or not to escape the items in $values.
     * clause.
     * @return Gdn_SQLDriver $this
     */
    public function _whereIn($field, $values, $op = 'in', $escape = true) {
        if (is_null($field) || !is_array($values)) {
            return;
        }

        $fieldExpr = $this->_parseExpr($field);

        // Build up the in clause.
        $in = [];
        foreach ($values as $value) {
            if ($escape) {
                $valueExpr = $this->Database->connection()->quote($value);
            } else {
                $valueExpr = (string)$value;
            }

            if (strlen($valueExpr) > 0) {
                $in[] = $valueExpr;
            }
        }
        if (count($in) > 0) {
            $inExpr = '('.implode(', ', $in).')';
        } else {
            $inExpr = '(null)';
        }

        // Set the final expression.
        $expr = $fieldExpr.' '.$op.' '.$inExpr;
        $this->_where($expr);

        return $this;
    }

    /**
     * Adds to the $this->_WhereIns collection. Used to generate a "where field
     * in (1,2,3)" query. Concatenated with AND.
     *
     * @param string $field The field to search in for $values.
     * @param array $values An array of values to look for in $field.
     * @return Gdn_SQLDriver $this
     */
    public function whereIn($field, $values, $escape = true) {
        return $this->_whereIn($field, $values, 'in', $escape);
    }

    /**
     * A convenience method for Gdn_DatabaseDriver::whereIn() that changes the operator to 'not in.'
     * @see Gdn_DatabaseDriver::whereIn()
     * @return Gdn_SQLDriver $this
     */
    public function whereNotIn($field, $values, $escape = true) {
        return $this->_whereIn($field, $values, 'not in', $escape);
    }

    /**
     * Adds an Sql exists expression to the $this->_Wheres collection.
     * @param Gdn_DatabaseDriver $sqlDriver The sql to add.
     * @param string $op Either 'exists' or 'not exists'
     * @return Gdn_DatabaseDriver $this
     */
    public function whereExists($sqlDriver, $op = 'exists') {
        $sql = $op." (\r\n".$sqlDriver->getSelect()."\n)";

        // Add the inner select.
        $this->_where($sql);

        // Add the named parameters from the inner select to this statement.
        foreach ($sqlDriver->_NamedParameters as $name => $value) {
            $this->_NamedParameters[$name] = $value;
        }

        return $this;
    }

    /**
     * A convienience method for Gdn_DatabaseDriver::whereExists() that changes the operator to 'not exists'.
     * @see Gdn_DatabaseDriver::whereExists()
     */
    public function whereNotExists($sqlDriver) {
        return $this->whereExists(@SqlDriver, 'not exists');
    }
}
