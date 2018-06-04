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
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param $string
     * @return string
     * @deprecated
     */
    public function backtick($string) {
        deprecated('backtick', 'escapeIdentifier');
        return '`'.trim($string, '`').'`';
    }

    /**
     * Takes a string of SQL and adds backticks if necessary.
     *
     * @param string|array $string The string (or array of strings) of SQL to be escaped.
     * @param boolean $firstWordOnly Should the function only escape the first word?\
     */
    public function escapeSql($string, $firstWordOnly = false) {
        if (is_array($string)) {
            $escapedArray = [];

            foreach ($string as $k => $v) {
                $escapedArray[$this->escapeSql($k)] = $this->escapeSql($v, $firstWordOnly);
            }

            return $escapedArray;
        }
        // echo '<div>STRING: '.$String.'</div>';

        // This function may get "item1 item2" as a string, and so
        // we may need "`item1` `item2`" and not "`item1 item2`"
        if (ctype_alnum($string) === false) {
            if (strpos($string, '.') !== false) {
                $mungedAliases = implode('.', array_keys($this->_AliasMap)).'.';
                $tableName = substr($string, 0, strpos($string, '.') + 1);
                //echo '<div>STRING: '.$String.'</div>';
                //echo '<div>TABLENAME: '.$TableName.'</div>';
                //echo '<div>ALIASES: '.$MungedAliases.'</div>';
                // If the "TableName" isn't found in the alias list and it is a valid table name, apply the database prefix to it
                $string = (strpos($mungedAliases, $tableName) !== false || strpos($tableName, "'") !== false) ? $string : $this->Database->DatabasePrefix.$string;
                //echo '<div>RESULT: '.$String.'</div>';

            }

            // This function may get "field >= 1", and need it to return "`field` >= 1"
            $leftBound = ($firstWordOnly === true) ? '' : '|\s|\(';

            $string = preg_replace('/(^'.$leftBound.')([\w-]+?)(\s|\)|$)/iS', '$1`$2`$3', $string);
            //echo '<div>STRING: '.$String.'</div>';

        } else {
            return "`{$string}`";
        }

        $exceptions = ['as', '/', '-', '%', '+', '*'];

        foreach ($exceptions as $exception) {
            if (stristr($string, " `{$exception}` ") !== false) {
                $string = preg_replace('/ `('.preg_quote($exception).')` /i', ' $1 ', $string);
            }
        }
        return $string;
    }

    /**
     *
     *
     * @param string $refExpr
     * @return string
     */
    public function escapeIdentifier($refExpr) {
        return '`'.str_replace('`', '``', $refExpr).'`';
    }

    /**
     * Returns a platform-specific query to fetch column data from $table.
     *
     * @param string $table The name of the table to fetch column data from.
     */
    public function fetchColumnSql($table) {
        if ($table[0] != '`' && !stringBeginsWith($table, $this->Database->DatabasePrefix)) {
            $table = $this->Database->DatabasePrefix.$table;
        }

        return "show columns from ".$this->formatTableName($table);
    }

    /**
     * Returns a platform-specific query to fetch table names.
     * @param mixed $limitToPrefix Whether or not to limit the search to tables with the database prefix or a specific table name. The following types can be given for this parameter:
     *  - <b>TRUE</b>: The search will be limited to the database prefix.
     *  - <b>FALSE</b>: All tables will be fetched. Default.
     *  - <b>string</b>: The search will be limited to a like clause. The ':_' will be replaced with the database prefix.
     */
    public function fetchTableSql($limitToPrefix = false) {
        $sql = "show tables";

        if (is_bool($limitToPrefix) && $limitToPrefix && $this->Database->DatabasePrefix != '') {
            $sql .= " like ".$this->Database->connection()->quote($this->Database->DatabasePrefix.'%');
        } elseif (is_string($limitToPrefix) && $limitToPrefix)
            $sql .= " like ".$this->Database->connection()->quote(str_replace(':_', $this->Database->DatabasePrefix, $limitToPrefix));

        return $sql;
        echo "<pre>$sql</pre>";
    }

    /**
     * Returns an array of schema data objects for each field in the specified
     * table. The returned array of objects contains the following properties:
     * Name, PrimaryKey, Type, AllowNull, Default, Length, Enum.
     *
     * @param string $table The name of the table to get schema data for.
     */
    public function fetchTableSchema($table) {
        // Format the table name.
        $table = $this->escapeIdentifier($this->Database->DatabasePrefix.$table);
        $dataSet = $this->query($this->fetchColumnSql($table));
        $schema = [];

        foreach ($dataSet->result() as $field) {
            $type = $field->Type;
            $unsigned = stripos($type, 'unsigned') !== false;
            $length = '';
            $precision = '';
            $parentheses = strpos($type, '(');
            $enum = '';

            if ($parentheses !== false) {
                $lengthParts = explode(',', substr($type, $parentheses + 1, -1));
                $type = substr($type, 0, $parentheses);

                if (strcasecmp($type, 'enum') == 0) {
                    $enum = [];
                    foreach ($lengthParts as $value) {
                        $enum[] = trim($value, "'");
                    }
                } else {
                    $length = trim($lengthParts[0]);
                    if (count($lengthParts) > 1) {
                        $precision = trim($lengthParts[1]);
                    }
                }
            }

            $object = new stdClass();
            $object->Name = $field->Field;
            $object->PrimaryKey = ($field->Key == 'PRI' ? true : false);
            $object->Type = $type;
            //$Object->Type2 = $Field->Type;
            $object->Unsigned = $unsigned;
            $object->AllowNull = ($field->Null == 'YES');
            $object->Default = $field->Default;
            $object->Length = $length;
            $object->Precision = $precision;
            $object->Enum = $enum;
            $object->KeyType = null; // give placeholder so it can be defined again.
            $object->AutoIncrement = strpos($field->Extra, 'auto_increment') === false ? false : true;
            $schema[$field->Field] = $object;
        }

        return $schema;
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
     * @param string $table The name of the table name to format.
     */
    public function formatTableName($table) {

        if (strpos($table, '.') !== false) {
            if (preg_match('/^([^\s]+)\s+(?:as\s+)?`?([^`]+)`?$/', $table, $matches)) {
                $databaseTable = '`'.str_replace('.', '`.`', $matches[1]).'`';
                $table = str_replace($matches[1], $databaseTable, $table);
            } else {
                $table = '`'.str_replace('.', '`.`', $table).'`';
            }
        }
        return $table;
    }

    /**
     * Returns a delete statement for the specified table and the supplied
     * conditions.
     *
     * @param string $tableName The name of the table to delete from.
     * @param array $wheres An array of where conditions.
     */
    public function getDelete($tableName, $wheres = [], $limit = 0) {
        $conditions = '';
        $joins = '';
        $deleteFrom = '';
        $limitSql = '';

        if (count($this->_Joins) > 0) {
            $joins .= "\n";
            $joins .= implode("\n", $this->_Joins);


            $deleteFroms = [];
            foreach ($this->_Froms as $from) {
                $parts = preg_split('`\s`', trim($from));
                if (count($parts) > 1) {
                    $deleteFroms[] = $parts[1].'.*';
                } else {
                    $deleteFroms[] = $parts[0].'.*';
                }
            }
            $deleteFrom = implode(', ', $deleteFroms);
        } elseif ($limit > 0) {
            $limitSql = "\nlimit ".((int)$limit);
        }

        if (count($wheres) > 0) {
            $conditions = "\nwhere ";
            $conditions .= implode("\n", $wheres);

            // Close any where groups that were left open.
            $this->_endQuery();
        }

        return "delete $deleteFrom from ".$tableName.$joins.$conditions.$limitSql;
    }

    /**
     * Returns an insert statement for the specified $table with the provided $data.
     *
     * @param string $table The name of the table to insert data into.
     * @param array $data An associative array of FieldName => Value pairs that should be inserted,
     * or an array of FieldName values that should have values inserted from
     * $select.
     * @param string $select A select query that will fill the FieldNames specified in $data.
     */
    public function getInsert($table, $data, $select = '') {
        if (!is_array($data)) {
            trigger_error(errorMessage('The data provided is not in a proper format (Array).', 'MySQLDriver', 'GetInsert'), E_USER_ERROR);
        }

        if ($this->options('Replace')) {
            $sql = 'replace ';
        } else {
            $sql = 'insert '.($this->options('Ignore') ? 'ignore ' : '');
        }

        $sql .= $this->formatTableName($table).' ';
        if ($select != '') {
            $sql .= "\n(".implode(', ', $data).') '
                ."\n".$select;
        } else {
            if (array_key_exists(0, $data)) {
                // This is a big insert with a bunch of rows.
                $keys = array_keys($data[0]);
                $keys = array_map([$this, 'escapeIdentifier'], $keys);
                $sql .= "\n(".implode(', ', $keys).') '
                    ."\nvalues ";

                // Append each insert statement.
                for ($i = 0; $i < count($data); $i++) {
                    if ($i > 0) {
                        $sql .= ', ';
                    }
                    $sql .= "\n('".implode('\', \'', array_values($data[$i])).'\')';
                }
            } else {
                $keys = array_keys($data);
                $sql .= "\n(".implode(', ', $keys).') '
                    ."\nvalues (".implode(', ', array_values($data)).')';
            }
        }
        return $sql;
    }

    /**
     * Adds a limit clause to the provided query for this database engine.
     *
     * @param string $query The SQL string to which the limit statement should be appended.
     * @param int $limit The number of records to limit the query to.
     * @param int $offset The number of records to offset the query from.
     */
    public function getLimit($query, $limit, $offset) {
        $offset = $offset == 0 ? '' : $offset.', ';
        return $query."limit ".$offset.$limit;
    }

    /**
     * Returns an update statement for the specified table with the provided $data.
     *
     * @param array $tables The name of the table to updated data in.
     * @param array $data An associative array of FieldName => Value pairs that should be inserted $Table.
     * @param mixed $where A where clause (or array containing multiple where clauses) to be applied
     * to the where portion of the update statement.
     * @param array $orderBy A collection of order by statements.
     * @param int $limit The number of records to limit the query to.
     * @return string
     */
    public function getUpdate($tables, $data, $where, $orderBy = null, $limit = null) {
        if (!is_array($data)) {
            trigger_error(errorMessage('The data provided is not in a proper format (Array).', 'MySQLDriver', '_GetUpdate'), E_USER_ERROR);
        }

        $sets = [];
        foreach ($data as $field => $value) {
            $sets[] = $field." = ".$value;
        }

        $sql = 'update '.($this->options('Ignore') ? 'ignore ' : '').$this->_fromTables($tables);

        if (count($this->_Joins) > 0) {
            $sql .= "\n";

            $sql .= implode("\n", $this->_Joins);
        }

        $sql .= "\nset ".implode(",\n ", $sets);
        if (is_array($where) && count($where) > 0) {
            $sql .= "\nwhere ".implode("\n ", $where);

            // Close any where groups that were left open.
            for ($i = 0; $i < $this->_OpenWhereGroupCount; ++$i) {
                $sql .= ')';
            }
            $this->_OpenWhereGroupCount = 0;
        } elseif (is_string($where) && !stringIsNullOrEmpty($where)) {
            $sql .= ' where '.$where;
        }

        if (is_array($orderBy) && count($orderBy) > 0) {
            $sql .= "\norder by ".implode(', ', $orderBy);
        }

        if (is_numeric($limit)) {
            $sql .= "\n";
            $sql = $this->getLimit($sql, $limit, 0);
        }

        return $sql;
    }

    /**
     * Returns a truncate statement for this database engine.
     *
     * @param string The name of the table to updated data in.
     */
    public function getTruncate($table) {
        return 'truncate '.$this->formatTableName($table);
    }

    /**
     * Allows the specification of a case statement in the select list.
     *
     * @param string $field The field being examined in the case statement.
     * @param array $options The options and results in an associative array. A blank key will be the
     * final "else" option of the case statement. eg.
     * array('null' => 1, '' => 0) results in "when null then 1 else 0".
     * @param string $alias The alias to give a column name.
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
        $this->_Selects[] = ['Field' => $field, 'Function' => '', 'Alias' => $alias, 'CaseOptions' => $caseOptions];
        return $this;
    }

    /**
     * Sets the character encoding for this database engine.
     *
     * @param string $encoding
     * @todo $encoding needs a description.
     */
    public function setEncoding($encoding) {
        if ($encoding != '' && $encoding !== false) {
            // Make sure to pass through any named parameters from queries defined before the connection was opened.
            $savedNamedParameters = $this->_NamedParameters;
            $this->_NamedParameters = [];
            $this->_NamedParameters[':encoding'] = $encoding;
            $this->query('set names :encoding');
            $this->_NamedParameters = $savedNamedParameters;
        }
    }
}
