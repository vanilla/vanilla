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
 * @copyright 2009-2015 Vanilla Forums Inc.
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
    protected $_DatabaseInfo = array();

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
    protected $_NamedParameters = array();

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
    protected $_Options = array();

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
    //         $Statement = preg_replace('/(\w+\.\w+)/', $this->EscapeIdentifier('$0'), $v); // Makes `table.field`
    //         $Statement = str_replace(array($this->Database->DatabasePrefix.$Table, '.'), array($Table, $this->EscapeSql('.')), $Statement);
    //      }
    //      $Statements[$k] = $Statement;
    //   }
    //   return $Statements;
    //}

    /**
     * Concat the next where expression with an 'and' operator.
     * <b>Note</b>: Since 'and' is the default operator to begin with this method doesn't usually have to be called,
     * unless Gdn_DatabaseDriver::Or(FALSE) has previously been called.
     *
     * @param boolean $SetDefault Whether or not the 'and' is one time or sets the default operator.
     * @return Gdn_SQLDriver $this
     * @see Gdn_DatabaseDriver::OrOp()
     */
    public function andOp($SetDefault = false) {
        $this->_WhereConcat = 'and';
        if ($SetDefault) {
            $this->_WhereConcatDefault = 'and';
            $this->_WhereGroupConcatDefault = 'and';
        }

        return $this;
    }

    /**
     *
     *
     * @param $Sql
     * @param null $Parameters
     * @return mixed
     */
    public function applyParameters($Sql, $Parameters = null) {
        if (!is_array($Parameters)) {
            $Parameters = $this->_NamedParameters;
        }

        // Sort the parameters so that we don't have clashes.
        krsort($Parameters);
        foreach ($Parameters as $Key => $Value) {
            if (is_null($Value)) {
                $QValue = 'null';
            } else {
                $QValue = $this->Database->connection()->quote($Value);
            }
            $Sql = str_replace($Key, $QValue, $Sql);
        }
        return $Sql;
    }

    /**
     * A convenience method that calls Gdn_DatabaseDriver::BeginWhereGroup with concatenated with an 'or.'
     * @See Gdn_DatabaseDriver::BeginWhereGroup()
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
     * @param string $Field The name of the field on the left hand side of the expression.
     *   If $Field ends with an operator, then it used for the comparison. Otherwise '=' will be used.
     * @param mixed $Value The value on the right side of the expression. If $EscapeValueSql is true then it will end up in a parameter.
     *
     * <b>Syntax</b>
     * The $Field and Value expressions can begin with special characters to do certain things.
     * <ul>
     * <li><b>=</b>: This means that the argument is a function call.
     *   If you want to pass field reference arguments into the function then enclose them in square brackets.
     *   ex. <code>'=LEFT([u.Name], 4)'</code> will call the LEFT database function on the u.Name column.</li>
     * <li><b>@</b>: This means that the argument is a literal.
     *   This is useful for passing in literal numbers.</li>
     * <li><b>no prefix></b>: This will treat the argument differently depending on the argument.
     *   - <b>$Field</b> - The argument is a column reference.
     *   - <b>$Value</b> - The argument will become a named parameter.
     * </li></ul>
     * @return string The single expression.
     */
    public function conditionExpr($Field, $Value, $EscapeFieldSql = true, $EscapeValueSql = true) {
        // Change some variables from the old parameter style to the new one.
        // THIS PART OF THE FUNCTION SHOULD EVENTUALLY BE REMOVED.
        if ($EscapeFieldSql === false) {
            $Field = '@'.$Field;
        }

        if (is_array($Value)) {
            throw new Exception('Gdn_SQL->ConditionExpr(VALUE, ARRAY) is not supported.', 500);
        } elseif (!$EscapeValueSql && !is_null($Value)) {
            $Value = '@'.$Value;
        }

        // Check for a straight literal field expression.
        if (!$EscapeFieldSql && !$EscapeValueSql && is_null($Value)) {
            return substr($Field, 1); // warning: might not be portable across different drivers
        }
        $Expr = ''; // final expression which is built up
        $Op = ''; // logical operator

        // Try and split an operator out of $Field.
        $FieldOpRegex = "/(?:\s*(=|<>|>|<|>=|<=)\s*$)|\s+(like|not\s+like)\s*$|\s+(?:(is)\s+(null)|(is\s+not)\s+(null))\s*$/i";
        $Split = preg_split($FieldOpRegex, $Field, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        if (count($Split) > 1) {
            $Field = $Split[0];
            $Op = strtolower($Split[1]);
            if (count($Split) > 2) {
                $Value = null;
            }
        } else {
            $Op = '=';
        }

        if ($Op == '=' && is_null($Value)) {
            // This is a special case where the value SQL is checking for an is null operation.
            $Op = 'is';
            $Value = '@null';
            $EscapeValueSql = false;
        }

        // Add the left hand side of the expression.
        $Expr .= $this->_parseExpr($Field, null, $EscapeFieldSql);

        // Add the expression operator.
        $Expr .= ' '.$Op.' ';

        if ($Op == 'is' || $Op == 'is not' && is_null($Value)) {
            $Expr .= 'null';
        } else {
            // Add the right side of the expression.
            $Expr .= $this->_parseExpr($Value, $Field, $EscapeValueSql);
        }

        return $Expr;
    }

    /**
     * Set the cache key for this transaction
     *
     * @param string|array $Key The cache key (or array of keys) that this query will save into.
     * @param string $Operation The cache operation as a hint to the db.
     * @param array $Options The cache options as passed into Gdn_Cache::Store().
     * @return Gdn_SQLDriver $this
     */
    public function cache($Key, $Operation = null, $Options = null) {
        if (!$Key) {
            $this->_CacheKey = null;
            $this->_CacheOperation = null;
            $this->_CacheOptions = null;

            return $this;
        }

        $this->_CacheKey = $Key;

        if (!is_null($Operation)) {
            $this->_CacheOperation = $Operation;
        }

        if (!is_null($Options)) {
            $this->_CacheOptions = $Options;
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
     * @param mixed $Table The table (or array of table names) to delete from.
     * @param mixed $Where The string on the left side of the where comparison, or an associative
     * array of Field => Value items to compare.
     * @param int $Limit The number of records to limit the query to.
     */
    public function delete($Table = '', $Where = '', $Limit = false) {
        if ($Table == '') {
            if (!isset($this->_Froms[0])) {
                return false;
            }

            $Table = $this->_Froms[0];
        } elseif (is_array($Table)) {
            foreach ($Table as $t) {
                $this->delete($t, $Where, $Limit, false);
            }
            return;
        } else {
            $Table = $this->escapeIdentifier($this->Database->DatabasePrefix.$Table);
        }

        if ($Where != '') {
            $this->where($Where);
        }

        if ($Limit !== false) {
            $this->limit($Limit);
        }

        if (count($this->_Wheres) == 0) {
            return false;
        }

        $Sql = $this->getDelete($Table, $this->_Wheres, $this->_Limit);

        return $this->query($Sql, 'delete');
    }

    /**
     * Specifies that the query should be run as a distinct so that duplicate
     * columns are grouped together. Returns this object for chaining purposes.
     *
     * @param boolean $Bool A boolean value indicating if the query should be distinct or not.
     * @return Gdn_SQLDriver $this
     */
    public function distinct($Bool = true) {
        $this->_Distinct = (is_bool($Bool)) ? $Bool : true;
        return $this;
    }

    /**
     * Removes all data from a table.
     *
     * @param string $Table The table to empty.
     */
    public function emptyTable($Table = '') {
        if ($Table == '') {
            if (!isset($this->_Froms[0])) {
                return false;
            }

            $Table = $this->_Froms[0];
        } else {
            $Table = $this->escapeIdentifier($this->Database->DatabasePrefix.$Table);
        }


        $Sql = $this->getDelete($Table);

        return $this->query($Sql, 'delete');
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
            $WhereCount = count($this->_Wheres);

            if ($this->_OpenWhereGroupCount >= $this->_WhereGroupCount) {
                $this->_OpenWhereGroupCount--;
            } elseif ($WhereCount > 0) {
                $this->_Wheres[$WhereCount - 1] .= ')';
            }

            $this->_WhereGroupCount--;
        }

        return $this;
    }

    /**
     * Takes a string formatted as an SQL field reference and escapes it for the defined database engine.
     *
     * @param string $RefExpr The reference expression to be escaped.
     *   The reference should be in the form of alias.column.
     */
    protected function escapeIdentifier($RefExpr) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'EscapeSql'), E_USER_ERROR);
    }

    /**
     * Takes a string of SQL and escapes it for the defined database engine.
     * ie. adds backticks or any other database-specific formatting.
     *
     * @param mixed $String The string (or array of strings) of SQL to be escaped.
     * @param boolean $FirstWordOnly A boolean value indicating if the first word should be escaped only.
     */
    protected function escapeSql($String, $FirstWordOnly = false) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'EscapeSql'), E_USER_ERROR);
    }

    /**
     * Returns a platform-specific query to fetch column data from $Table.
     *
     * @param string $Table The name of the table to fetch column data from.
     */
    public function fetchColumnSql($Table) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'FetchColumnSql'), E_USER_ERROR);
    }

    /**
     * Returns a platform-specific query to fetch table names.
     * @param mixed $LimitToPrefix Whether or not to limit the search to tables with the database prefix or a specific table name. The following types can be given for this parameter:
     *  - <b>TRUE</b>: The search will be limited to the database prefix.
     *  - <b>FALSE</b>: All tables will be fetched. Default.
     *  - <b>string</b>: The search will be limited to a like clause. The ':_' will be replaced with the database prefix.
     */
    public function fetchTableSql($LimitToPrefix = false) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'FetchTableSql'), E_USER_ERROR);
    }

    /**
     * Returns an array containing table names in the database.
     * @param mixed $LimitToPrefix Whether or not to limit the search to tables with the database prefix or a specific table name. The following types can be given for this parameter:
     *  - <b>TRUE</b>: The search will be limited to the database prefix.
     *  - <b>FALSE</b>: All tables will be fetched. Default.
     *  - <b>string</b>: The search will be limited to a like clause. The ':_' will be replaced with the database prefix.
     * @return array
     */
    public function fetchTables($LimitToPrefix = false) {
        $Sql = $this->fetchTableSql($LimitToPrefix);
        $Data = $this->query($Sql);
        $Return = array();
        foreach ($Data->resultArray() as $Row) {
            if (isset($Row['TABLE_NAME'])) {
                $Return[] = $Row['TABLE_NAME'];
            } else {
                $Return[] = array_shift($Row);
            }
        }

        return $Return;
    }

    /**
     * Returns an array of schema data objects for each field in the specified
     * table. The returned array of objects contains the following properties:
     * Name, PrimaryKey, Type, AllowNull, Default, Length, Enum.
     *
     * @param string $Table The name of the table to get schema data for.
     */
    public function fetchTableSchema($Table) {
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
     * Returns an array containing column names from $Table.
     *
     * @param string $Table The name of the table to fetch column data from.
     */
    public function fetchColumns($Table) {
        $Sql = $this->fetchColumnSql($Table);
        $Data = $this->query($Sql);
        $Return = array();
        foreach ($Data->resultArray() as $Row) {
            if (isset($Row['COLUMN_NAME'])) {
                $Return[] = $Row['COLUMN_NAME'];
            } else {
                $Return[] = current($Row);
            }
        }

        return $Return;
    }

    /**
     * Takes a table name and makes sure it is formatted for this database
     * engine.
     *
     * @param string $Table The name of the table name to format.
     */
    public function formatTableName($Table) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'FormatTableName'), E_USER_ERROR);
    }

    /**
     * The table(s) from which to select values. Returns this object for
     * chaining purposes.
     *
     * @param mixed $From A string or array of table names/aliases from which to select data.
     * Accepted formats include:
     *    user
     *    user, user u2, role
     *    array("user u", "user u2", "role")
     *
     * @return Gdn_SQLDriver $this
     **/
    public function from($From) {
        if (!is_array($From)) {
            $From = array($From);
        }

        $Count = count($From);
        $i = 0;
        for ($i = 0; $i < $Count; ++$i) {
            $this->_Froms[] = $this->escapeIdentifier($this->mapAliases($From[$i]));
        }

        return $this;
    }

    /**
     * Returns a string of comma delimited table names to select from.
     *
     * @param mixed $Tables The name of a table (or an array of table names) to be added in the from
     * clause of a query.
     */
    protected function _fromTables($Tables) {
        return is_array($Tables) ? implode(', ', $Tables) : $Tables;
    }

    /**
     * Builds the select statement and runs the query, returning a result object.
     *
     * @param string $Table The table from which to select data. Adds to the $this->_Froms collection.
     * @param string $OrderFields A string of fields to be ordered.
     * @param string $OrderDirection The direction of the sort.
     * @param int $Limit Adds a limit to the query.
     * @param int $PageNumber The page of data to retrieve.
     * @return Gdn_DataSet
     */
    public function get($Table = '', $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $PageNumber = false) {
        if ($Table != '') {
            //$this->MapAliases($Table);
            $this->from($Table);
        }

        if ($OrderFields != '') {
            $this->orderBy($OrderFields, $OrderDirection);
        }

        if ($Limit !== false) {
            if ($PageNumber == false || $PageNumber < 1) {
                $PageNumber = 1;
            }

            $Offset = ($PageNumber - 1) * $Limit;
            $this->limit($Limit, $Offset);
        }

        $Result = $this->query($this->getSelect());
        return $Result;
    }

    /**
     * A helper function for escaping sql identifiers.
     *
     * @param string The sql containing identifiers to escape in a different language.
     *   All identifiers requiring escaping should be enclosed in back ticks (`).
     * @return array All of the tokens in the sql. The tokens that require escaping will still have back ticks.
     */
    protected function _getIdentifierTokens($Sql) {
        $Tokens = preg_split('/`/', $Sql, -1, PREG_SPLIT_DELIM_CAPTURE);
        $Result = array();

        $InIdent = false;
        $CurrentToken = '';
        for ($i = 0; $i < count($Tokens); $i++) {
            $Token = $Tokens[i];
            $Result .= $Token;
            if ($Token == '`') {
                if ($InIdent && $i < count($Tokens) - 1 && $Tokens[$i + 1] == '`') {
                    // This is an escaped back tick.
                    $i++; // skip next token
                } elseif ($InIdent) {
                    $Result[] = $CurrentToken;
                    $CurrentToken = $CurrentToken;
                    $InIdent = false;
                } else {
                    $InIdent = true;
                }
            } elseif (!$InIdent) {
                $Result[] = $CurrentToken;
                $CurrentToken = '';
            }
        }

        return $Result;
    }

    /**
     * Returns the total number of records in the specified table.
     *
     * @param string $Table The table from which to count rows of data.
     * @param mixed $Where Adds to the $this->_Wheres collection using $this->Where();
     */
    public function getCount($Table = '', $Where = false) {
        if ($Table != '') {
            //$this->MapAliases($Table);
            $this->from($Table);
        }

        if ($Where !== false) {
            $this->where($Where);
        }

        $this->select('*', 'count', 'RowCount'); // count * slow on innodb
        $Sql = $this->getSelect();
        $Result = $this->query($Sql);

        $CountData = $Result->firstRow();
        return $CountData->RowCount;
    }

    /**
     * Returns the total number of records in the specified table.
     *
     * @param string $Table The table from which to count rows of data.
     * @param mixed $Like Adds to the $this->_Wheres collection using $this->Like();
     */
    public function getCountLike($Table = '', $Like = false) {
        if ($Table != '') {
            $this->mapAliases($Table);
            $this->from($Table);
        }

        if ($Like !== false) {
            $this->like($Like);
        }

        $this->select('*', 'count', 'RowCount');
        $Result = $this->query($this->getSelect());

        $CountData = $Result->firstRow();
        return $CountData->RowCount;
    }

    /**
     * Returns a delete statement for the specified table and the supplied
     * conditions.
     *
     * @param string $TableName The name of the table to delete from.
     * @param array $Wheres An array of where conditions.
     */
    public function getDelete($TableName, $Wheres = array()) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetDelete'), E_USER_ERROR);
    }

    /**
     * Returns an insert statement for the specified $Table with the provided $Data.
     *
     * @param string $Table The name of the table to insert data into.
     * @param string $Data An associative array of FieldName => Value pairs that should be inserted
     * $Table.
     */
    public function getInsert($Table, $Data) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetInsert'), E_USER_ERROR);
    }

    /**
     * Adds a limit clause to the provided query for this database engine.
     *
     * @param string $Query The SQL string to which the limit statement should be appended.
     * @param int $Limit The number of records to limit the query to.
     * @param int $Offset The number of records to offset the query from.
     */
    public function GetLimit($Query, $Limit, $Offset) {
        trigger_error(ErrorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetLimit'), E_USER_ERROR);
    }

    /**
     * Builds the select statement based on the various collections in this
     * object. This method should not be called directly; it is called by
     * $this->Get() and $this->GetWhere().
     */
    public function getSelect() {
        // Close off any open query elements.
        $this->_endQuery();

        $Sql = (!$this->_Distinct) ? 'select ' : 'select distinct ';

        // Don't escape the field if it is numeric or an asterisk (all columns)
        $Selects = array();
        foreach ($this->_Selects as $Key => $Expr) {
            $Field = $Expr['Field'];
            $Function = $Expr['Function'];
            $Alias = $Expr['Alias'];
            $CaseOptions = ArrayValue('CaseOptions', $Expr);
            if ($Field != '*' && !is_numeric($Field)) {
                $Field = $this->escapeIdentifier($Field);
            }

            if ($Alias == '' && $Function != '') {
                $Alias = $Field;
            }

            // if (in_array(strtolower($Function), array('max', 'min', 'avg', 'sum', 'count')))
            if ($Function != '') {
                if (strpos($Function, '%s') !== false) {
                    $Field = sprintf($Function, $Field);
                } else {
                    $Field = $Function.'('.$Field.')';
                }
            }

            if ($CaseOptions !== false) {
                $Field = 'case '.$Field.$CaseOptions.' end';
            }

            if ($Alias != '') {
                $Field .= ' as '.$this->quoteIdentifier($Alias);
            }

            if ($Field != '') {
                $Selects[] = $Field;
            }
        }
        $Sql .= (count($Selects) == 0) ? '*' : implode(', ', $Selects);

        if (count($this->_Froms) > 0) {
            $Sql .= "\nfrom ".$this->_fromTables($this->_Froms);
        }

        if (count($this->_Joins) > 0) {
            $Sql .= "\n";
            $Sql .= implode("\n", $this->_Joins);
        }

        if (count($this->_Wheres) > 0) {
            $Sql .= "\nwhere ";
        }

        $Sql .= implode("\n", $this->_Wheres);

        // Close any where groups that were left open.
        for ($i = 0; $i < $this->_OpenWhereGroupCount; ++$i) {
            $Sql .= ')';
        }
        $this->_OpenWhereGroupCount = 0;

        if (count($this->_GroupBys) > 0) {
            $Sql .= "\ngroup by ";

            // special consideration for table aliases
            if (count($this->_AliasMap) > 0 && $this->Database->DatabasePrefix) {
                $Sql .= implode(', ', $this->_filterTableAliases($this->_GroupBys));
            } else {
                $Sql .= implode(', ', $this->_GroupBys);
            }
        }

        if (count($this->_Havings) > 0) {
            $Sql .= "\nhaving ".implode("\n", $this->_Havings);
        }

        if (count($this->_OrderBys) > 0) {
            $Sql .= "\norder by ".implode(', ', $this->_OrderBys);
        }

        if (is_numeric($this->_Limit)) {
            $Sql .= "\n";
            $Sql = $this->getLimit($Sql, $this->_Limit, $this->_Offset);
        }

        return $Sql;
    }

    /**
     * Returns a truncate statement for this database engine.
     *
     * @param string $Table The name of the table to updated data in.
     */
    public function getTruncate($Table) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetTruncate'), E_USER_ERROR);
    }

    /**
     * Returns an update statement for the specified table with the provided
     * $Data.
     *
     * @param array $Tables The names of the tables to updated data in.
     * @param array $Data An associative array of FieldName => Value pairs that should be inserted
     * $Table.
     * @param mixed $Where A where clause (or array containing multiple where clauses) to be applied
     * to the where portion of the update statement.
     */
    public function getUpdate($Tables, $Data, $Where) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'GetUpdate'), E_USER_ERROR);
    }

    /**
     * Builds the select statement and runs the query, returning a result
     * object. Allows a where clause, limit, and offset to be added directly.
     *
     * @param string $Table The table from which to select data. Adds to the $this->_Froms collection.
     * @param mixed $Where Adds to the $this->_Wheres collection using $this->Where();
     * @param string $OrderFields A string of fields to be ordered.
     * @param string $OrderDirection The direction of the sort.
     * @param int $Limit The number of records to limit the query to.
     * @param int $Offset The offset where the query results should begin.
     * @return Gdn_DataSet The data returned by the query.
     */
    public function getWhere($Table = '', $Where = false, $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = 0) {
        if ($Table != '') {
            //$this->MapAliases($Table);
            $this->from($Table);
        }

        if ($Where !== false) {
            $this->where($Where);
        }

        if ($OrderFields != '') {
            $this->orderBy($OrderFields, $OrderDirection);
        }

        if ($Limit !== false) {
            $this->limit($Limit, $Offset);
        }

        $Result = $this->query($this->getSelect());

        return $Result;
    }

    /**
     * Builds the select statement and runs the query, returning a result
     * object. Allows a like clause, limit, and offset to be added directly.
     *
     * @param string $Table The table from which to select data. Adds to the $this->_Froms collection.
     * @param mixed $Like Adds to the $this->_Wheres collection using $this->Like();
     * @param string $OrderFields A string of fields to be ordered.
     * @param string $OrderDirection The direction of the sort.
     * @param int $Limit The number of records to limit the query to.
     * @param int $PageNumber The offset where the query results should begin.
     */
    public function getWhereLike($Table = '', $Like = false, $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $PageNumber = false) {
        if ($Table != '') {
            $this->mapAliases($Table);
            $this->from($Table);
        }

        if ($Like !== false) {
            $this->like($Like);
        }

        if ($OrderFields != '') {
            $this->orderBy($OrderFields, $OrderDirection);
        }

        if ($Limit !== false) {
            if ($PageNumber == false || $PageNumber < 1) {
                $PageNumber = 1;
            }

            $Offset = ($PageNumber - 1) * $Limit;
            $this->limit($Limit, $Offset);
        }

        $Result = $this->query($this->getSelect());

        return $Result;
    }

    /**
     * Adds to the $this->_GroupBys collection.
     *
     * @param mixed $Fields An array of field names (or a comma-delimited list of field names) to be
     * grouped by.
     * @return Gdn_SQLDriver $this
     */
    public function groupBy($Fields = null) {
        if (is_null($Fields)) {
            // Group by every item in the select that isn't a function.
            foreach ($this->_Selects as $Alias => $Select) {
                if (ArrayValue('Function', $Select) == '') {
                    $this->_GroupBys[] = $Select['Field'];
                }
            }
            return $this;
        }

        if (is_string($Fields)) {
            $Fields = explode(',', $Fields);
        }

        foreach ($Fields as $Field) {
            $Field = trim($Field);

            if ($Field != '') {
                $this->_GroupBys[] = $this->escapeIdentifier($Field);
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
     * @param string $Sql The condition to add.
     * @return Gdn_SQLDriver $this
     */
    protected function _having($Sql) {
        // Figure out the concatenation operator.
        $Concat = '';

        if (count($this->_Havings) > 0) {
            $Concat = ' '.$this->_WhereConcat.' ';
        }

        // Revert the concat back to 'and'.
        $this->_WhereConcat = $this->_WhereConcatDefault;

        $this->_Havings[] = $Concat.$Sql;

        return $this;
    }

    /**
     * Adds to the $this->_Havings collection. Called by $this->Having() and
     * $this->OrHaving().
     *
     * @param mixed $Field The name of the field (or array of field names) in the having clause.
     * @param string $Value The string on the right side of the having comparison.
     * @param boolean $EscapeSql A boolean value indicating if $this->EscapeSql method should be called
     * on $Field.
     * @param boolean $EscapeString A boolean value indicating if $this->EscapeString method should be called
     * on $Value.
     * @return Gdn_SQLDriver $this
     */
    function having($Field, $Value = '', $EscapeField = true, $EscapeValue = true) {
        if (!is_array($Field)) {
            $Field = array($Field => $Value);
        }

        foreach ($Field as $ChildField => $ChildValue) {
            $Expr = $this->conditionExpr($ChildField, $ChildValue, $EscapeField, $EscapeValue);
            $this->_having($Expr);
        }

        return $this;
    }

    /**
     * @return Gdn_SQLDriver $this
     */
    public function history($UpdateFields = true, $InsertFields = false) {
        $UserID = valr('User.UserID', Gdn::session(), Gdn::session()->UserID);

        if ($InsertFields) {
            $this->set('DateInserted', Gdn_Format::toDateTime())->set('InsertUserID', $UserID);
        }
        if ($UpdateFields) {
            $this->set('DateUpdated', Gdn_Format::toDateTime())->set('UpdateUserID', $UserID);
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
     * @param string $Request The piece of information being requested. Accepted values are: Engine,
     * Version, and DatabaseName.
     */
    public function information($Request) {
        if (array_key_exists($Request, $this->_DatabaseInfo) === false) {
            if ($Request == 'Version') {
                $this->_DatabaseInfo['Version'] = $this->version();
            } else {
                $this->_DatabaseInfo['HostName'] = Gdn::config('Database.Host', '');
                $this->_DatabaseInfo['DatabaseName'] = Gdn::config('Database.Name', '');
            }
        }
        if (array_key_exists($Request, $this->_DatabaseInfo) === true) {
            return $this->_DatabaseInfo[$Request];
        } else {
            return '';
        }
    }

    /**
     * Builds the insert statement and runs the query, returning a result
     * object.
     *
     * @param string $Table The table to which data should be inserted.
     * @param mixed $Set An associative array (or object) of FieldName => Value pairs that should
     * be inserted, or an array of FieldName values that should have values
     * inserted from $Select.
     * @param string $Select A select query that will fill the FieldNames specified in $Set.
     */
    public function insert($Table = '', $Set = null, $Select = '') {
        if (count($Set) == 0 && count($this->_Sets) == 0) {
            return false;
        }

        if (!is_null($Set) && $Select == '' && !array_key_exists(0, $Set)) {
            $this->set($Set);
            $Set = $this->_Sets;
        }

        if ($Table == '') {
            if (!isset($this->_Froms[0])) {
                return false;
            }

            $Table = $this->_Froms[0];
        }

        $Sql = $this->getInsert($this->escapeIdentifier($this->Database->DatabasePrefix.$Table), $Set, $Select);
        $Result = $this->query($Sql, 'insert');

        return $Result;
    }

    /**
     * Inserts or updates values in the table depending on whether they are already there.
     *
     * @param string $Table The name of the table to insert/update.
     * @param array $Set The columns to update.
     * @param array $Where The columns to find the row to update.
     * If a row is not found then one is inserted and the items in this array are merged with $Set.
     */
    public function replace($Table = '', $Set = null, $Where, $CheckExisting = false) {
        if (count($this->_Sets) > 0) {
            foreach ($this->_Sets as $Key => $Value) {
                if (array_key_exists($Value, $this->_NamedParameters)) {
                    $Set[$Key] = $this->_NamedParameters[$Value];
                    unset($this->_NamedParameters[$Value]);
                } else {
                    $Set[$Key] = $Value;
                }
            }
            $this->_Sets = array();
        }

        // Check to see if there is a row in the table like this.
        if ($CheckExisting) {
            $Row = $this->getWhere($Table, $Where)->firstRow(DATASET_TYPE_ARRAY);

            $Update = false;
            if ($Row) {
                $Update = true;
                foreach ($Set as $Key => $Value) {
                    unset($Set[$Key]);
                    $Key = trim($Key, '`');

                    if (!$this->CaptureModifications && !array_key_exists($Key, $Row)) {
                        continue;
                    }

                    if (in_array($Key, array('DateInserted', 'InsertUserID', 'DateUpdated', 'UpdateUserID'))) {
                        continue;
                    }


                    // We are assuming here that if the existing record doesn't contain the column then it's just been added.
                    if (preg_match('/^`(.+)`$/', $Value, $Matches)) {
                        if (!array_key_exists($Key, $Row) || $Row[$Key] != $Row[$Matches[1]]) {
                            $this->set('`'.$Key.'`', $Value, false);
                        }
                    } elseif (!array_key_exists($Key, $Row) || $Row[$Key] != $Value) {
                        $this->set('`'.$Key.'`', $Value);
                    }

                }
                if (count($this->_Sets) == 0) {
                    $this->reset();
                    return;
                }
            }
        } else {
            $Count = $this->getCount($Table, $Where);
            $Update = $Count > 0;
        }

        if ($Update) {
            // Update the table.
            $this->put($Table, $Set, $Where);
        } else {
            // Insert the table.
            $Set = array_merge($Set, $Where);
            $this->insert($Table, $Set);
        }
    }

    /**
     * The table(s) to which this query should join. Returns this object for
     * chaining purposes.
     *
     * @param string $TableName The name of a single table to join to.
     * @param string $On The conditions on which the join should occur.
     * ie. "user.role_id = role.id"
     * @param string $Join The type of join to be made. Accepted values are:
     * 'inner', 'outer', 'left', 'right', 'left outer', and 'right outer'.
     * @return Gdn_SQLDriver $this
     */
    public function join($TableName, $On, $Join = '') {
        $Join = strtolower(trim($Join));
        if ($Join != '' && !in_array($Join, array('inner', 'outer', 'left', 'right', 'left outer', 'right outer'), true)) {
            $Join = '';
        }

        // Add the table prefix to any table specifications in the clause
        // echo '<div>'.$TableName.' ---> '.$this->EscapeSql($this->Database->DatabasePrefix.$TableName, TRUE).'</div>';
        if ($this->Database->DatabasePrefix) {
            $TableName = $this->mapAliases($TableName);

            //$Aliases = array_keys($this->_AliasMap);
            //$Regex = '';
            //foreach ($Aliases as $Alias) {
            //   $Regex .= '(?<! '.$Alias.')';
            //}
            //$Regex = '/(\w+'.$Regex.'\.)/';
            //$On = preg_replace($Regex, $this->Database->DatabasePrefix.'$1', ' '.$On);
        }
        $JoinClause = ltrim($Join.' join ').$this->escapeIdentifier($TableName, true).' on '.$On;
        $this->_Joins[] = $JoinClause;

        return $this;
    }

    /**
     * A convenience method for Gdn_DatabaseDriver::Join that makes the join type 'left.'
     * @see Gdn_DatabaseDriver::Join()
     */
    public function leftJoin($TableName, $On) {
        return $this->join($TableName, $On, 'left');
    }

    /**
     * Adds to the $this->_Wheres collection. Used to generate the LIKE portion
     * of a query. Called by $this->Like(), $this->NotLike()
     *
     * @param mixed $Field The field name (or array of field name => match values) to search in for
     * a like $Match.
     * @param string $Match The value to try to match using a like statement in $Field.
     * @param string $Concat The concatenation operator for the items being added to the like in
     * clause.
     * @param string $Side A string indicating which side of the match to place asterisk operators.
     * Accepted values are left, right, both, none. Default is both.
     * @param string $Op Either 'like' or 'not like' clause.
     * @return Gdn_SQLDriver $this
     */
    public function like($Field, $Match = '', $Side = 'both', $Op = 'like') {
        if (!is_array($Field)) {
            $Field = array($Field => $Match);
        }

        foreach ($Field as $SubField => $SubValue) {
            $SubField .= ' '.$Op.' ';
            switch ($Side) {
                case 'left':
                    $SubValue = '%'.$SubValue;
                    break;
                case 'right':
                    $SubValue .= '%';
                    break;
                case 'both':
                    if (strlen($Match) == 0) {
                        $SubValue = '%';
                    } else {
                        $SubValue = '%'.$SubValue.'%';
                    }
                    break;
            }
            $Expr = $this->conditionExpr($SubField, $SubValue);
            $this->_where($Expr);
        }
        return $this;
    }

    /**
     * Sets the limit (and offset optionally) for the query.
     *
     * @param int $Limit The number of records to limit the query to.
     * @param int $Offset The offset where the query results should begin.
     * @return Gdn_SQLDriver $this
     */
    public function limit($Limit, $Offset = false) {
        // SQL chokes on ints over 2^31
        if ($Limit > self::MAX_SIGNED_INT) {
            throw new Exception(t('Invalid limit.'), 400);
        }

        $this->_Limit = $Limit;

        if ($Offset !== false) {
            $this->offset($Offset);
        }

        return $this;
    }

    /**
     * Takes a provided table specification and parses out any table aliases
     * provided, placing them in an alias mapping array. Returns the table
     * specification with any table prefix prepended.
     *
     * @param string $TableString The string specification of the table. ie.
     * "tbl_User as u" or "user u".
     * @return string
     */
    public function mapAliases($TableString) {
        // Make sure all tables have an alias.
        if (strpos($TableString, ' ') === false) {
            $TableString .= " `$TableString`";
        }

        // Map the alias to the alias mapping array
        $TableString = trim(preg_replace('/\s+as\s+/i', ' ', $TableString));
        $Alias = strrchr($TableString, " ");
        $TableName = substr($TableString, 0, strlen($TableString) - strlen($Alias));

        // If no alias was specified then it will be set to the tablename.
        $Alias = trim($Alias);
        if (strlen($Alias) == 0) {
            $Alias = $TableName;
            $TableString .= " `$Alias`";
        }

        //$this->_AliasMap[$Alias] = $TableName;

        // Return the string with the database table prefix prepended
        return $this->Database->DatabasePrefix.$TableString;
    }

    /**
     * A convenience method for Gdn_DatabaseDriver::Like that changes the operator to 'not like.'
     * @see Gdn_DatabaseDriver::Like()
     */
    public function notLike($Field, $Match = '', $Side = 'both') {
        return $this->like($Field, $Match, $Side, 'not like');
    }

    /**
     * Takes a parameter name and makes sure it is cleaned up to be used as a
     * named parameter in a pdo prepared statement.
     * @param string $Name The name of the parameter to cleanup
     * @param boolean $CreateNew Wether or not this is a new or existing parameter.
     * @return string The cleaned up named parameter name.
     */
    public function namedParameter($Name, $CreateNew = false, $Value = null) {
        // Format the parameter name so it is safe for sql
        $NiceName = ':'.preg_replace('/([^\w])/', '', $Name); // Removes everything from the string except letters, numbers and underscores

        if ($CreateNew) {
            // Make sure that the new name doesn't already exist.
            $NumberedName = $NiceName;
            $i = 0;
            while (array_key_exists($NumberedName, $this->_NamedParameters)) {
                $NumberedName = $NiceName.$i;
                ++$i;
            }
            $NiceName = $NumberedName;
        }

        if (!is_null($Value)) {
            $this->_NamedParameters[$NiceName] = $Value;
        }

        return $NiceName;
    }

    public function &namedParameters($NewValue = null) {
        if ($NewValue !== null) {
            $this->_NamedParameters = $NewValue;
        }
        $Result =& $this->_NamedParameters;
        return $Result;
    }

    /**
     * Allows a query to be called without resetting the object.
     * @param boolean $Reset Whether or not to reset this object when the next query executes.
     * @param boolean $OneTime Whether or not this will apply for only the next query or for all subsequent queries.
     * @return Gdn_SQLDriver $this
     */
    public function noReset($NoReset = true, $OneTime = true) {
        $_NoReset = $NoReset ? ($OneTime ? 1 : 2) : 0;
        return $this;
    }

    /**
     * Sets the offset for the query.
     *
     * @param int $Offset The offset where the query results should begin.
     * @return Gdn_SQLDriver $this
     */
    public function offset($Offset) {
        // SQL chokes on ints over 2^31
        if ($Offset > self::MAX_SIGNED_INT) {
            throw new Exception(T('Invalid offset.'), 400);
        }

        $this->_Offset = $Offset;
        return $this;
    }

    /**
     * Gets/sets an option on the object.
     *
     * @param string $Key The key of the option.
     * @param mixed $Value The value of the option or not specified just to get the current value.
     * @return mixed The value of the option or $this if $Value is specified.
     */
    public function options($Key, $Value = null) {
        if (is_array($Key)) {
            foreach ($Key as $K => $V) {
                $this->Options[$K] = $V;
                return $this;
            }
        } elseif ($Value !== null) {
            $this->_Options[$Key] = $Value;
            return $this;
        } elseif (isset($this->_Options[$Key]))
            return $this->_Options[$Key];
        else {
            return null;
        }
    }

    /**
     * Adds to the $this->_OrderBys collection.
     *
     * @param string $Fields A string of fields to be ordered.
     * @param string $Direction The direction of the sort.
     * @return Gdn_SQLDriver $this
     */
    public function orderBy($Fields, $Direction = 'asc') {
        if (!$Fields) {
            return $this;
        }

        if ($Direction && $Direction != 'asc') {
            $Direction = 'desc';
        } else {
            $Direction = 'asc';
        }

        $this->_OrderBys[] = $this->escapeIdentifier($Fields, true).' '.$Direction;
        return $this;
    }

    /**
     * Adds to the $this->_Havings collection. Concatenates multiple calls with OR.
     *
     * @param mixed $Field The name of the field (or array of field names) in the having clause.
     * @param string $Value The string on the right side of the having comparison.
     * @param boolean $EscapeField A boolean value indicating if $this->EscapeSql method should be called
     * on $Field.
     * @param boolean $EscapeValue A boolean value indicating if $this->EscapeString method should be called
     * on $Value.
     * @return Gdn_SQLDriver $this
     * @see Gdn_DatabaseDriver::Having()
     */
    function orHaving($Field, $Value = '', $EscapeField = true, $EscapeValue = true) {
        return $this->orOp()->having($Field, $Value, $EscapeField, $EscapeValue);
    }

    /**
     * A convenience method that calls Gdn_DatabaseDriver::Like with concatenated with an 'or.'
     * @See Gdn_DatabaseDriver::Like()
     * @return Gdn_SQLDriver $this
     */
    public function orLike($Field, $Match = '', $Side = 'both', $Op = 'like') {
        if (!is_array($Field)) {
            $Field = array($Field => $Match);
        }

        foreach ($Field as $f => $v) {
            $this->orOp()->like($f, $v, $Side, $Op);
        }
        return $this;

//       return $this->OrOp()->Like($Field, $Match, $Side, $Op);
    }

    /** A convenience method for Gdn_DatabaseDriver::Like that changes the operator to 'not like,'
     *    and is concatenated with an 'or.'
     * @see Gdn_DatabaseDriver::NotLike()
     * @see GenricDriver::Like()
     */
    public function orNotLike($Field, $Match = '', $Side = 'both') {
        return $this->orLike($Field, $Match, $Side, 'not like');
    }

    /**
     * Concat the next where expression with an 'or' operator.
     *
     * @param boolean $SetDefault Whether or not the 'or' is one time, or will revert.
     * @return Gdn_SQLDriver $this
     * @see Gdn_DatabaseDriver::AndOp()
     */
    public function orOp($SetDefault = false) {
        $this->_WhereConcat = 'or';
        if ($SetDefault) {
            $this->_WhereConcatDefault = 'or';
            $this->_WhereGroupConcatDefault = 'or';
        }

        return $this;
    }

    /**
     * @link Gdn_DatabaseDriver::Where()
     */
    public function orWhere($Field, $Value = null, $EscapeFieldSql = true, $EscapeValueSql = true) {
        return $this->orOp()->where($Field, $Value, $EscapeFieldSql, $EscapeValueSql);
    }

    /**
     * A convienience method for Gdn_DatabaseDriver::WhereExists() concatenates with an 'or.'
     * @see Gdn_DatabaseDriver::WhereExists()
     */
    public function orWhereExists($SqlDriver, $Op = 'exists') {
        return $this->orOp()->whereExists($SqlDriver, $Op);
    }

    /**
     * @ling Gdn_DatabaseDriver::WhereIn()
     */
    public function orWhereIn($Field, $Values) {
        return $this->orOp()->whereIn($Field, $Values);
    }

    /**
     * A convienience method for Gdn_DatabaseDriver::WhereExists() that changes the operator to 'not exists,'
     *   and concatenates with an 'or.'
     * @see Gdn_DatabaseDriver::WhereExists()
     * @see Gdn_DatabaseDriver::WhereNotExists()
     */
    public function orWhereNotExists($SqlDriver) {
        return $this->orWhereExists($SqlDriver, 'not exists');
    }

    /**
     * A convenience method for Gdn_DatabaseDriver::WhereIn() that changes the operator to 'not in,'
     *   and concatenates with an 'or.'
     * @see Gdn_DatabaseDriver::WhereIn()
     * @see Gdn_DatabaseDriver::WhereNotIn()
     */
    public function orWhereNotIn($Field, $Values) {
        return $this->orOp()->whereNotIn($Field, $Values);
    }

    /**
     * Parses an expression for use in where clauses.
     *
     * @param string $Expr The expression to parse.
     * @param string $Name A name to give the parameter if $Expr becomes a named parameter.
     * @return string The parsed expression.
     */
    protected function _parseExpr($Expr, $Name = null, $EscapeExpr = false) {
        $Result = '';

        $C = substr($Expr, 0, 1);

        if ($C === '=' && $EscapeExpr === false) {
            // This is a function call. Each parameter has to be parsed.
            $FunctionArray = preg_split('/(\[[^\]]+\])/', substr($Expr, 1), -1, PREG_SPLIT_DELIM_CAPTURE);
            for ($i = 0; $i < count($FunctionArray); $i++) {
                $Part = $FunctionArray[$i];
                if (substr($Part, 1) == '[') {
                    // Translate the part of the function call.
                    $Part = $this->_fieldExpr(substr($Part, 1, strlen($Part) - 2), $Name);
                    $FunctionArray[$i] = $Part;
                }
            }
            // Combine the array back to the original function call.
            $Result = join('', $FunctionArray);
        } elseif ($C === '@' && $EscapeExpr === false) {
            // This is a literal. Don't do anything.
            $Result = substr($Expr, 1);
        } else {
            // This is a column reference.
            if (is_null($Name)) {
                $Result = $this->escapeIdentifier($Expr);
            } else {
                // This is a named parameter.

                // Check to see if the named parameter is valid.
                if (in_array(substr($Expr, 0, 1), array('=', '@'))) {
                    // The parameter has to be a default name.
                    $Result = $this->namedParameter('Param', true);
                } else {
                    $Result = $this->namedParameter($Name, true);
                }
                $this->_NamedParameters[$Result] = $Expr;
            }
        }

        return $Result;
    }

    /**
     * Joins the query to a permission junction table and limits the results accordingly.
     *
     * @param mixed $Permission The permission name (or array of names) to use when limiting the query.
     * @param string $ForeignAlias The alias of the table to join to (ie. Category).
     * @param string $ForeignColumn The primary key column name of $JunctionTable (ie. CategoryID).
     * @param string $JunctionTable
     * @param string $JunctionColumn
     * @return Gdn_SQLDriver $this
     */
    public function permission($Permission, $ForeignAlias, $ForeignColumn, $JunctionTable = '', $JunctionColumn = '') {
        $PermissionModel = Gdn::permissionModel();
        $PermissionModel->sqlPermission($this, $Permission, $ForeignAlias, $ForeignColumn, $JunctionTable, $JunctionColumn);

        return $this;
    }

    /**
     * Prefixes a table with the database prefix if it is not already there.
     *
     * @param string $Table The table name to prefix.
     */
    public function prefixTable($Table) {
        $Prefix = $this->Database->DatabasePrefix;

        if ($Prefix != '' && substr($Table, 0, strlen($Prefix)) != $Prefix) {
            $Table = $Prefix.$Table;
        }

        return $Table;
    }

    /**
     * Builds the update statement and runs the query, returning a result object.
     *
     * @param string $Table The table to which data should be updated.
     * @param mixed $Set An array of $FieldName => $Value pairs, or an object of $DataSet->Field
     * properties containing one rowset.
     * @param string $Where Adds to the $this->_Wheres collection using $this->Where();
     * @param int $Limit Adds a limit to the query.
     */
    public function put($Table = '', $Set = null, $Where = false, $Limit = false) {
        $this->update($Table, $Set, $Where, $Limit);

        if (count($this->_Sets) == 0 || !isset($this->_Froms[0])) {
            $this->reset();
            return false;
        }

        $Sql = $this->getUpdate($this->_Froms, $this->_Sets, $this->_Wheres, $this->_OrderBys, $this->_Limit);
        $Result = $this->query($Sql, 'update');

        return $Result;
    }

    public function query($Sql, $Type = 'select') {
        $QueryOptions = array('Type' => $Type, 'Slave' => GetValue('Slave', $this->_Options, null));

        switch ($Type) {
            case 'insert':
                $ReturnType = 'ID';
                break;
            case 'update':
                $ReturnType = null;
                break;
            default:
                $ReturnType = 'DataSet';
                break;
        }

        $QueryOptions['ReturnType'] = $ReturnType;
        if (!is_null($this->_CacheKey)) {
            $QueryOptions['Cache'] = $this->_CacheKey;
        }

        if (!is_null($this->_CacheKey)) {
            $QueryOptions['CacheOperation'] = $this->_CacheOperation;
        }

        if (!is_null($this->_CacheOptions)) {
            $QueryOptions['CacheOptions'] = $this->_CacheOptions;
        }

        try {
            if ($this->CaptureModifications && strtolower($Type) != 'select') {
                if (!property_exists($this->Database, 'CapturedSql')) {
                    $this->Database->CapturedSql = array();
                }
                $Sql2 = $this->applyParameters($Sql, $this->_NamedParameters);

                $this->Database->CapturedSql[] = $Sql2;
                $this->reset();
                return true;
            }

            $Result = $this->Database->query($Sql, $this->_NamedParameters, $QueryOptions);
        } catch (Exception $Ex) {
            $this->reset();
            throw $Ex;
        }
        $this->reset();

        return $Result;
    }

    public function quoteIdentifier($String) {
        return '`'.$String.'`';
    }

    /**
     * Resets properties of this object that relate to building a select
     * statement back to their default values. Called by $this->Get() and
     * $this->GetWhere().
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
        $this->_Selects = array();
        $this->_Froms = array();
        $this->_Joins = array();
        $this->_Wheres = array();
        $this->_WhereConcat = 'and';
        $this->_WhereConcatDefault = 'and';
        $this->_WhereGroupConcat = 'and';
        $this->_WhereGroupConcatDefault = 'and';
        $this->_WhereGroupCount = 0;
        $this->_OpenWhereGroupCount = 0;
        $this->_GroupBys = array();
        $this->_Havings = array();
        $this->_OrderBys = array();
        $this->_AliasMap = array();

        $this->_CacheKey = null;
        $this->_CacheOperation = null;
        $this->_CacheOptions = null;
        $this->_Distinct = false;
        $this->_Limit = false;
        $this->_Offset = false;
        $this->_Order = false;

        $this->_Sets = array();
        $this->_NamedParameters = array();
        $this->_Options = array();

        return $this;
    }

    /**
     * Allows the specification of columns to be selected in a database query.
     * Returns this object for chaining purposes. ie. $db->Select()->From();
     *
     * @param mixed $Select NotRequired "*" The field(s) being selected. It
     * can be a comma delimited string, the name of a single field, or an array
     * of field names.
     * @param string $Function NotRequired "" The aggregate function to be used on
     * the select column. Only valid if a single column name is provided.
     * Accepted values are MAX, MIN, AVG, SUM.
     * @param string $Alias NotRequired "" The alias to give a column name.
     * @return Gdn_SQLDriver $this
     */
    public function select($Select = '*', $Function = '', $Alias = '') {
        if (is_string($Select)) {
            if ($Function == '') {
                $Select = explode(',', $Select);
            } else {
                $Select = array($Select);
            }
        }
        $Count = count($Select);

        $i = 0;
        for ($i = 0; $i < $Count; $i++) {
            $Field = trim($Select[$i]);

            // Try and figure out an alias for the field.
            if ($Alias == '' || ($Count > 1 && $i > 0)) {
                if (preg_match('/^([^\s]+)\s+(?:as\s+)?`?([^`]+)`?$/', $Field, $Matches) > 0) {
                    // This is an explicit alias in the select clause.
                    $Field = $Matches[1];
                    $Alias = $Matches[2];
                } elseif (preg_match('/^[^\.]+\.`?([^`]+)`?$/', $Field, $Matches) > 0) {
                    // This is an alias from the field name.
                    $Alias = $Matches[1];
                } else {
                    $Alias = '';
                }
                // Make sure we aren't selecting * as an alias.
                if ($Alias == '*') {
                    $Alias = '';
                }
            }

            $Expr = array('Field' => $Field, 'Function' => $Function, 'Alias' => $Alias);

            if ($Alias == '') {
                $this->_Selects[] = $Expr;
            } else {
                $this->_Selects[$Alias] = $Expr;
            }
        }
        return $this;
    }

    /**
     * Allows the specification of a case statement in the select list.
     *
     * @param string $Field The field being examined in the case statement.
     * @param array $Options The options and results in an associative array. A
     * blank key will be the final "else" option of the case statement. eg.
     * array('null' => 1, '' => 0) results in "when null then 1 else 0".
     * @param string $Alias The alias to give a column name.
     * @return Gdn_SQLDriver $this
     */
    public function selectCase($Field, $Options, $Alias) {
        $CaseOptions = '';
        foreach ($Options as $Key => $Val) {
            if ($Key == '') {
                $CaseOptions .= ' else '.$Val;
            } else {
                $CaseOptions .= ' when '.$Key.' then '.$Val;
            }
        }

        $Expr = array('Field' => $Field, 'Function' => '', 'Alias' => $Alias, 'CaseOptions' => $CaseOptions);

        if ($Alias == '') {
            $this->_Selects[] = $Expr;
        } else {
            $this->_Selects[$Alias] = $Expr;
        }

        return $this;
    }

    /**
     * Adds values to the $this->_Sets collection. Allows for the inserting
     * and updating of values to the db.
     *
     * @param mixed $Field The name of the field to save value as. Alternately this can be an array
     * of $FieldName => $Value pairs, or even an object of $DataSet->Field properties containing one rowset.
     * @param string $Value The value to be set in $Field. Ignored if $Field was an array or object.
     * @param boolean $EscapeString A boolean value indicating if the $Value(s) should be escaped or not.
     * @param boolean $CreateNewNamedParameter A boolean value indicating that if (a) a named parameter is being
     * created, and (b) that name already exists in $this->_NamedParameters
     * collection, then a new one should be created rather than overwriting the
     * existing one.
     * @return Gdn_SQLDriver $this Returns this for fluent calls
     * @throws \Exception Throws an exception if an invalid type is passed for {@link $Value}.
     */
    public function set($Field, $Value = '', $EscapeString = true, $CreateNewNamedParameter = true) {
        $Field = Gdn_Format::objectAsArray($Field);

        if (!is_array($Field)) {
            $Field = array($Field => $Value);
        }

        foreach ($Field as $f => $v) {
            if (is_array($v) || is_object($v)) {
                throw new Exception('Invalid value type ('.gettype($v).') in INSERT/UPDATE statement.', 500);
            } else {
                if ($EscapeString) {
                    $NamedParameter = $this->namedParameter($f, $CreateNewNamedParameter);
                    $this->_NamedParameters[$NamedParameter] = $v;
                    $this->_Sets[$this->escapeIdentifier($f)] = $NamedParameter;
                } else {
                    $this->_Sets[$this->escapeIdentifier($f)] = $v;
                }
            }
        }

        return $this;
    }

    /**
     * Sets the character encoding for this database engine.
     */
    public function setEncoding($Encoding) {
        trigger_error(errorMessage('The selected database engine does not perform the requested task.', $this->ClassName, 'SetEncoding'), E_USER_ERROR);
    }

    /**
     * Similar to $this->Set() in every way except that if a named parameter is
     * used in place of $Value, it will overwrite any existing value associated
     * with that name as opposed to adding a new name/value (which is the
     * default way that $this->Set() works).
     *
     * @param mixed $Field The name of the field to save value as. Alternately this can be an array
     * of $FieldName => $Value pairs, or even an object of $DataSet->Field
     * properties containing one rowset.
     * @param string $Value The value to be set in $Field. Ignored if $Field was an array or object.
     * @param boolean $EscapeString A boolean value indicating if the $Value(s) should be escaped or not.
     */
    public function setOverwrite($Field, $Value = '', $EscapeString = true) {
        return $this->set($Field, $Value, $EscapeString, false);
    }

    /**
     * Truncates all data from a table (will delete from the table if database
     * does not support truncate).
     *
     * @param string $Table The table to truncate.
     */
    public function truncate($Table = '') {
        if ($Table == '') {
            if (!isset($this->_Froms[0])) {
                return false;
            }

            $Table = $this->_Froms[0];
        } else {
            $Table = $this->escapeIdentifier($this->Database->DatabasePrefix.$Table);
        }

        $Sql = $this->getTruncate($Table);
        $Result = $this->query($Sql, 'truncate');
        return $Result;
    }

    /**
     * Allows the specification of a table to be updated in a database query.
     * Returns this object for chaining purposes. ie. $db->Update()->Join()->Set()->Where();
     *
     * @param string $Table The table to which data should be updated.
     * @param mixed $Set An array of $FieldName => $Value pairs, or an object of $DataSet->Field
     * properties containing one rowset.
     * @param string $Where Adds to the $this->_Wheres collection using $this->Where();
     * @param int $Limit Adds a limit to the query.
     * @return Gdn_SQLDriver $this
     */
    public function update($Table, $Set = null, $Where = false, $Limit = false) {
        if ($Table != '') {
            $this->from($Table);
        }

        if (!is_null($Set)) {
            $this->set($Set);
        }

        if ($Where !== false) {
            $this->where($Where);
        }

        if ($Limit !== false) {
            $this->limit($Limit);
        }

        return $this;
    }

    /**
     * Returns a plain-english string containing the version of the database engine.
     */
    public function version() {
        $Query = $this->query($this->fetchVersionSql());
        return $Query->value('version');
    }

    /**
     * Adds to the $this->_Wheres collection. This is the most basic where that adds a freeform string of text.
     *   It should be used only in conjunction with methods that properly escape the sql.
     * @param string $Sql The condition to add.
     * @return Gdn_SQLDriver $this
     */
    protected function _where($Sql) {
        // Figure out the concatenation operator.
        $Concat = '';

        if ($this->_OpenWhereGroupCount > 0) {
            $this->_WhereConcat = $this->_WhereGroupConcat;
        }

        if (count($this->_Wheres) > 0) {
            $Concat = str_repeat(' ', $this->_WhereGroupCount + 1).$this->_WhereConcat.' ';
        }

        // Open the group(s) if necessary.
        while ($this->_OpenWhereGroupCount > 0) {
            $Concat .= '(';
            $this->_OpenWhereGroupCount--;
        }

        // Revert the concat back to 'and'.
        $this->_WhereConcat = $this->_WhereConcatDefault;
        $this->_WhereGroupConcat = $this->_WhereGroupConcatDefault;

        $this->_Wheres[] = $Concat.$Sql;

        return $this;
    }

    /**
     * Adds to the $this->_Wheres collection. Called by $this->Where() and $this->OrWhere();
     *
     * @param mixed $Field The string on the left side of the comparison, or an associative array of
     * Field => Value items to compare.
     * @param mixed $Value The string on the right side of the comparison. You can optionally
     * provide an array of DatabaseFunction => Value, which will be converted to
     * DatabaseFunction('Value'). If DatabaseFunction contains a '%s' then sprintf will be used for to place DatabaseFunction into the value.
     * @param boolean $EscapeFieldSql A boolean value indicating if $this->EscapeSql method should be called
     * on $Field.
     * @param boolean $EscapeValueString A boolean value indicating if $this->EscapeString method should be called
     * on $Value.
     * @return Gdn_SQLDriver $this
     */
    public function where($Field, $Value = null, $EscapeFieldSql = true, $EscapeValueSql = true) {
        if (!is_array($Field)) {
            $Field = array($Field => $Value);
        }

        foreach ($Field as $SubField => $SubValue) {
            if (is_array($SubValue) && (isset($SubValue[0]) || count($SubValue) == 0)) {
                if (count($SubValue) == 1) {
                    $this->where($SubField, $SubValue[0]);
                } else {
                    $this->whereIn($SubField, $SubValue);
                }
            } else {
                $WhereExpr = $this->conditionExpr($SubField, $SubValue, $EscapeFieldSql, $EscapeValueSql);
                if (strlen($WhereExpr) > 0) {
                    $this->_where($WhereExpr);
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
     * in (1,2,3)" query. Called by $this->WhereIn(), $this->OrWhereIn(),
     * $this->WhereNotIn(), and $this->OrWhereNotIn().
     *
     * @param string $Field The field to search in for $Values.
     * @param array $Values An array of values to look for in $Field.
     * @param string $Op Either 'in' or 'not in' for the respective operation.
     * @param string $Escape Whether or not to escape the items in $Values.
     * clause.
     * @return Gdn_SQLDriver $this
     */
    public function _whereIn($Field, $Values, $Op = 'in', $Escape = true) {
        if (is_null($Field) || !is_array($Values)) {
            return;
        }

        $FieldExpr = $this->_parseExpr($Field);

        // Build up the in clause.
        $In = array();
        foreach ($Values as $Value) {
            if ($Escape) {
                $ValueExpr = $this->Database->connection()->quote($Value);
            } else {
                $ValueExpr = (string)$Value;
            }

            if (strlen($ValueExpr) > 0) {
                $In[] = $ValueExpr;
            }
        }
        if (count($In) > 0) {
            $InExpr = '('.implode(', ', $In).')';
        } else {
            $InExpr = '(null)';
        }

        // Set the final expression.
        $Expr = $FieldExpr.' '.$Op.' '.$InExpr;
        $this->_where($Expr);

        return $this;
    }

    /**
     * Adds to the $this->_WhereIns collection. Used to generate a "where field
     * in (1,2,3)" query. Concatenated with AND.
     *
     * @param string $Field The field to search in for $Values.
     * @param array $Values An array of values to look for in $Field.
     * @return Gdn_SQLDriver $this
     */
    public function whereIn($Field, $Values, $Escape = true) {
        return $this->_whereIn($Field, $Values, 'in', $Escape);
    }

    /**
     * A convenience method for Gdn_DatabaseDriver::WhereIn() that changes the operator to 'not in.'
     * @see Gdn_DatabaseDriver::WhereIn()
     * @return Gdn_SQLDriver $this
     */
    public function whereNotIn($Field, $Values, $Escape = true) {
        return $this->_whereIn($Field, $Values, 'not in', $Escape);
    }

    /**
     * Adds an Sql exists expression to the $this->_Wheres collection.
     * @param Gdn_DatabaseDriver $SqlDriver The sql to add.
     * @param string $Op Either 'exists' or 'not exists'
     * @return Gdn_DatabaseDriver $this
     */
    public function whereExists($SqlDriver, $Op = 'exists') {
        $Sql = $Op." (\r\n".$SqlDriver->getSelect()."\n)";

        // Add the inner select.
        $this->_where($Sql);

        // Add the named parameters from the inner select to this statement.
        foreach ($SqlDriver->_NamedParameters as $Name => $Value) {
            $this->_NamedParameters[$Name] = $Value;
        }

        return $this;
    }

    /**
     * A convienience method for Gdn_DatabaseDriver::WhereExists() that changes the operator to 'not exists'.
     * @see Gdn_DatabaseDriver::WhereExists()
     */
    public function whereNotExists($SqlDriver) {
        return $this->whereExists(@SqlDriver, 'not exists');
    }
}
