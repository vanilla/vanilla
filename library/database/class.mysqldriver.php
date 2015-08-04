<?php
/**
 * MySQL database driver.
 *
 * The MySQLDriver class can be treated as an interface for all database
 * engines. Any new database engine should have the same public and protected
 * properties and methods as this one so that they can all be treated the same
 * by the application.
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
 * Class Gdn_MySQLDriver
 */
class Gdn_MySQLDriver extends Gdn_SQLDriver {

    /**
     *
     *
     * @param $String
     * @return string
     */
    public function backtick($String) {
        return '`'.trim($String, '`').'`';
    }

    /**
     * Takes a string of SQL and adds backticks if necessary.
     *
     * @param string|array $String The string (or array of strings) of SQL to be escaped.
     * @param boolean $FirstWordOnly Should the function only escape the first word?\
     */
    public function escapeSql($String, $FirstWordOnly = false) {
        if (is_array($String)) {
            $EscapedArray = array();

            foreach ($String as $k => $v) {
                $EscapedArray[$this->escapeSql($k)] = $this->escapeSql($v, $FirstWordOnly);
            }

            return $EscapedArray;
        }
        // echo '<div>STRING: '.$String.'</div>';

        // This function may get "item1 item2" as a string, and so
        // we may need "`item1` `item2`" and not "`item1 item2`"
        if (ctype_alnum($String) === false) {
            if (strpos($String, '.') !== false) {
                $MungedAliases = implode('.', array_keys($this->_AliasMap)).'.';
                $TableName = substr($String, 0, strpos($String, '.') + 1);
                //echo '<div>STRING: '.$String.'</div>';
                //echo '<div>TABLENAME: '.$TableName.'</div>';
                //echo '<div>ALIASES: '.$MungedAliases.'</div>';
                // If the "TableName" isn't found in the alias list and it is a valid table name, apply the database prefix to it
                $String = (strpos($MungedAliases, $TableName) !== false || strpos($TableName, "'") !== false) ? $String : $this->Database->DatabasePrefix.$String;
                //echo '<div>RESULT: '.$String.'</div>';

            }

            // This function may get "field >= 1", and need it to return "`field` >= 1"
            $LeftBound = ($FirstWordOnly === true) ? '' : '|\s|\(';

            $String = preg_replace('/(^'.$LeftBound.')([\w-]+?)(\s|\)|$)/iS', '$1`$2`$3', $String);
            //echo '<div>STRING: '.$String.'</div>';

        } else {
            return "`{$String}`";
        }

        $Exceptions = array('as', '/', '-', '%', '+', '*');

        foreach ($Exceptions as $Exception) {
            if (stristr($String, " `{$Exception}` ") !== false) {
                $String = preg_replace('/ `('.preg_quote($Exception).')` /i', ' $1 ', $String);
            }
        }
        return $String;
    }

    /**
     *
     *
     * @param string $RefExpr
     * @return string
     */
    public function escapeIdentifier($RefExpr) {
        // The MySql back tick syntax is the default escape sequence so nothing needs to be done.
        return $RefExpr;
    }

    /**
     * Returns a platform-specific query to fetch column data from $Table.
     *
     * @param string $Table The name of the table to fetch column data from.
     */
    public function fetchColumnSql($Table) {
        if ($Table[0] != '`' && !StringBeginsWith($Table, $this->Database->DatabasePrefix)) {
            $Table = $this->Database->DatabasePrefix.$Table;
        }

        return "show columns from ".$this->formatTableName($Table);
    }

    /**
     * Returns a platform-specific query to fetch table names.
     * @param mixed $LimitToPrefix Whether or not to limit the search to tables with the database prefix or a specific table name. The following types can be given for this parameter:
     *  - <b>TRUE</b>: The search will be limited to the database prefix.
     *  - <b>FALSE</b>: All tables will be fetched. Default.
     *  - <b>string</b>: The search will be limited to a like clause. The ':_' will be replaced with the database prefix.
     */
    public function fetchTableSql($LimitToPrefix = false) {
        $Sql = "show tables";

        if (is_bool($LimitToPrefix) && $LimitToPrefix && $this->Database->DatabasePrefix != '') {
            $Sql .= " like ".$this->Database->connection()->quote($this->Database->DatabasePrefix.'%');
        } elseif (is_string($LimitToPrefix) && $LimitToPrefix)
            $Sql .= " like ".$this->Database->connection()->quote(str_replace(':_', $this->Database->DatabasePrefix, $LimitToPrefix));

        return $Sql;
        echo "<pre>$Sql</pre>";
    }

    /**
     * Returns an array of schema data objects for each field in the specified
     * table. The returned array of objects contains the following properties:
     * Name, PrimaryKey, Type, AllowNull, Default, Length, Enum.
     *
     * @param string $Table The name of the table to get schema data for.
     */
    public function fetchTableSchema($Table) {
        // Format the table name.
        $Table = $this->escapeSql($this->Database->DatabasePrefix.$Table);
        $DataSet = $this->query($this->fetchColumnSql($Table));
        $Schema = array();

        foreach ($DataSet->result() as $Field) {
            $Type = $Field->Type;
            $Unsigned = stripos($Type, 'unsigned') !== false;
            $Length = '';
            $Precision = '';
            $Parentheses = strpos($Type, '(');
            $Enum = '';

            if ($Parentheses !== false) {
                $LengthParts = explode(',', substr($Type, $Parentheses + 1, -1));
                $Type = substr($Type, 0, $Parentheses);

                if (strcasecmp($Type, 'enum') == 0) {
                    $Enum = array();
                    foreach ($LengthParts as $Value) {
                        $Enum[] = trim($Value, "'");
                    }
                } else {
                    $Length = trim($LengthParts[0]);
                    if (count($LengthParts) > 1) {
                        $Precision = trim($LengthParts[1]);
                    }
                }
            }

            $Object = new stdClass();
            $Object->Name = $Field->Field;
            $Object->PrimaryKey = ($Field->Key == 'PRI' ? true : false);
            $Object->Type = $Type;
            //$Object->Type2 = $Field->Type;
            $Object->Unsigned = $Unsigned;
            $Object->AllowNull = ($Field->Null == 'YES');
            $Object->Default = $Field->Default;
            $Object->Length = $Length;
            $Object->Precision = $Precision;
            $Object->Enum = $Enum;
            $Object->KeyType = null; // give placeholder so it can be defined again.
            $Object->AutoIncrement = strpos($Field->Extra, 'auto_increment') === false ? false : true;
            $Schema[$Field->Field] = $Object;
        }

        return $Schema;
    }

    /**
     * Returns a string of SQL that retrieves the database engine version in the fieldname "version".
     */
    public function fetchVersionSql() {
        return "select version() as Version";
    }

    /**
     * Takes a table name and makes sure it is formatted for this database
     * engine.
     *
     * @param string $Table The name of the table name to format.
     */
    public function formatTableName($Table) {

        if (strpos($Table, '.') !== false) {
            if (preg_match('/^([^\s]+)\s+(?:as\s+)?`?([^`]+)`?$/', $Table, $Matches)) {
                $DatabaseTable = '`'.str_replace('.', '`.`', $Matches[1]).'`';
                $Table = str_replace($Matches[1], $DatabaseTable, $Table);
            } else {
                $Table = '`'.str_replace('.', '`.`', $Table).'`';
            }
        }
        return $Table;
    }

    /**
     * Returns a delete statement for the specified table and the supplied
     * conditions.
     *
     * @param string $TableName The name of the table to delete from.
     * @param array $Wheres An array of where conditions.
     */
    public function getDelete($TableName, $Wheres = array()) {
        $Conditions = '';
        $Joins = '';
        $DeleteFrom = '';

        if (count($this->_Joins) > 0) {
            $Joins .= "\n";
            $Joins .= implode("\n", $this->_Joins);


            $DeleteFroms = array();
            foreach ($this->_Froms as $From) {
                $Parts = preg_split('`\s`', trim($From));
                if (count($Parts) > 1) {
                    $DeleteFroms[] = $Parts[1].'.*';
                } else {
                    $DeleteFroms[] = $Parts[0].'.*';
                }
            }
            $DeleteFrom = implode(', ', $DeleteFroms);
        }

        if (count($Wheres) > 0) {
            $Conditions = "\nwhere ";
            $Conditions .= implode("\n", $Wheres);

            // Close any where groups that were left open.
            $this->_endQuery();

        }

        return "delete $DeleteFrom from ".$TableName.$Joins.$Conditions;
    }

    /**
     * Returns an insert statement for the specified $Table with the provided $Data.
     *
     * @param string $Table The name of the table to insert data into.
     * @param array $Data An associative array of FieldName => Value pairs that should be inserted,
     * or an array of FieldName values that should have values inserted from
     * $Select.
     * @param string $Select A select query that will fill the FieldNames specified in $Data.
     */
    public function getInsert($Table, $Data, $Select = '') {
        if (!is_array($Data)) {
            trigger_error(ErrorMessage('The data provided is not in a proper format (Array).', 'MySQLDriver', 'GetInsert'), E_USER_ERROR);
        }

        if ($this->options('Replace')) {
            $Sql = 'replace ';
        } else {
            $Sql = 'insert '.($this->options('Ignore') ? 'ignore ' : '');
        }

        $Sql .= $this->formatTableName($Table).' ';
        if ($Select != '') {
            $Sql .= "\n(".implode(', ', $Data).') '
                ."\n".$Select;
        } else {
            if (array_key_exists(0, $Data)) {
                // This is a big insert with a bunch of rows.
                $Keys = array_keys($Data[0]);
                $Keys = array_map(array($this, 'Backtick'), $Keys);
                $Sql .= "\n(".implode(', ', $Keys).') '
                    ."\nvalues ";

                // Append each insert statement.
                for ($i = 0; $i < count($Data); $i++) {
                    if ($i > 0) {
                        $Sql .= ', ';
                    }
                    $Sql .= "\n('".implode('\', \'', array_values($Data[$i])).'\')';
                }
            } else {
                $Keys = array_keys($Data);
                $Keys = array_map(array($this, 'Backtick'), $Keys);
                $Sql .= "\n(".implode(', ', $Keys).') '
                    ."\nvalues (".implode(', ', array_values($Data)).')';
            }
        }
        return $Sql;
    }

    /**
     * Adds a limit clause to the provided query for this database engine.
     *
     * @param string $Query The SQL string to which the limit statement should be appended.
     * @param int $Limit The number of records to limit the query to.
     * @param int $Offset The number of records to offset the query from.
     */
    public function getLimit($Query, $Limit, $Offset) {
        $Offset = $Offset == 0 ? '' : $Offset.', ';
        return $Query."limit ".$Offset.$Limit;
    }

    /**
     * Returns an update statement for the specified table with the provided $Data.
     *
     * @param array $Tables The name of the table to updated data in.
     * @param array $Data An associative array of FieldName => Value pairs that should be inserted $Table.
     * @param mixed $Where A where clause (or array containing multiple where clauses) to be applied
     * to the where portion of the update statement.
     */
    public function getUpdate($Tables, $Data, $Where) {
        if (!is_array($Data)) {
            trigger_error(errorMessage('The data provided is not in a proper format (Array).', 'MySQLDriver', '_GetUpdate'), E_USER_ERROR);
        }

        $Sets = array();
        foreach ($Data as $Field => $Value) {
            $Sets[] = $Field." = ".$Value;
        }

        $sql = 'update '.($this->options('Ignore') ? 'ignore ' : '').$this->_fromTables($Tables);

        if (count($this->_Joins) > 0) {
            $sql .= "\n";

            $Join = $this->_Joins[count($this->_Joins) - 1];

            $sql .= implode("\n", $this->_Joins);
        }

        $sql .= "\nset ".implode(",\n ", $Sets);
        if (is_array($Where) && count($Where) > 0) {
            $sql .= "\nwhere ".implode("\n ", $Where);

            // Close any where groups that were left open.
            for ($i = 0; $i < $this->_OpenWhereGroupCount; ++$i) {
                $sql .= ')';
            }
            $this->_OpenWhereGroupCount = 0;
        } elseif (is_string($Where) && !stringIsNullOrEmpty($Where)) {
            $sql .= ' where '.$Where;
        }
        return $sql;
    }

    /**
     * Returns a truncate statement for this database engine.
     *
     * @param string The name of the table to updated data in.
     */
    public function getTruncate($Table) {
        return 'truncate '.$this->formatTableName($Table);
    }

    /**
     * Allows the specification of a case statement in the select list.
     *
     * @param string $Field The field being examined in the case statement.
     * @param array $Options The options and results in an associative array. A blank key will be the
     * final "else" option of the case statement. eg.
     * array('null' => 1, '' => 0) results in "when null then 1 else 0".
     * @param string $Alias The alias to give a column name.
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
        $this->_Selects[] = array('Field' => $Field, 'Function' => '', 'Alias' => $Alias, 'CaseOptions' => $CaseOptions);
        return $this;
    }

    /**
     * Sets the character encoding for this database engine.
     *
     * @param string $Encoding
     * @todo $Encoding needs a description.
     */
    public function setEncoding($Encoding) {
        if ($Encoding != '' && $Encoding !== false) {
            // Make sure to pass through any named parameters from queries defined before the connection was opened.
            $SavedNamedParameters = $this->_NamedParameters;
            $this->_NamedParameters = array();
            $this->_NamedParameters[':encoding'] = $Encoding;
            $this->query('set names :encoding');
            $this->_NamedParameters = $SavedNamedParameters;
        }
    }
}
