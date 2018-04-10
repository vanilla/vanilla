<?php
/**
 * MySQL structure driver.
 *
 * MySQL-specific structure tools for performing structural changes on MySQL
 * database servers.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Class Gdn_MySQLStructure
 */
class Gdn_MySQLStructure extends Gdn_DatabaseStructure {
    /**
     * @var array[int] An array of table names to row count estimates.
     */
    private $rowCountEstimates;

    /**
     *
     *
     * @param null $database
     */
    public function __construct($database = null) {
        parent::__construct($database);
    }

    /**
     * Drops $this->table() from the database.
     */
    public function drop() {
        if ($this->tableExists()) {
            return $this->executeQuery('drop table `'.$this->_DatabasePrefix.$this->_TableName.'`');
        }
    }

    /**
     * Drops $name column from $this->table().
     *
     * @param string $name The name of the column to drop from $this->table().
     * @return boolean
     */
    public function dropColumn($name) {
        if (!$this->executeQuery('alter table `'.$this->_DatabasePrefix.$this->_TableName.'` drop column `'.$name.'`')) {
            throw new Exception(sprintf(t('Failed to remove the `%1$s` column from the `%2$s` table.'), $name, $this->_DatabasePrefix.$this->_TableName));
        }

        return true;
    }

    /**
     *
     *
     * @param $engine
     * @return bool
     */
    public function hasEngine($engine) {
        static $viableEngines = null;

        if ($viableEngines === null) {
            $engineList = $this->Database->query("SHOW ENGINES;");
            $viableEngines = [];
            while ($engineList && $storageEngine = $engineList->value('Engine', false)) {
                $engineName = strtolower($storageEngine);
                $viableEngines[$engineName] = true;
            }
        }

        if (array_key_exists($engine, $viableEngines)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     *
     * @param $engine
     * @param bool $checkAvailability
     * @return $this
     */
    public function engine($engine, $checkAvailability = true) {
        $engine = strtolower($engine);

        if ($checkAvailability) {
            if (!$this->hasEngine($engine)) {
                return $this;
            }
        }

        $this->_TableStorageEngine = $engine;
        return $this;
    }

    /**
     * Get the estimated number of rows in a table.
     *
     * @param string $tableName The name of the table to look up, without its prefix.
     * @return int|null Returns the estimated number of rows or **null** if the information doesn't exist.
     */
    public function getRowCountEstimate($tableName) {
        if (!isset($this->rowCountEstimates)) {
            $data = $this->Database->query("show table status")->resultArray();
            $this->rowCountEstimates = [];
            foreach ($data as $row) {
                $name = stringBeginsWith($row['Name'], $this->Database->DatabasePrefix, false, true);
                $this->rowCountEstimates[$name] = $row['Rows'];
            }
        }

        return val($tableName, $this->rowCountEstimates, null);
    }

    /**
     * Renames a column in $this->table().
     *
     * @param string $oldName The name of the column to be renamed.
     * @param string $newName The new name for the column being renamed.
     * @param string $tableName
     * @return boolean
     */
    public function renameColumn($oldName, $newName, $tableName = '') {
        if ($tableName != '') {
            $this->_TableName = $tableName;
        }

        // Get the schema for this table
        $oldPrefix = $this->Database->DatabasePrefix;
        $this->Database->DatabasePrefix = $this->_DatabasePrefix;
        $schema = $this->Database->sql()->fetchTableSchema($this->_TableName);
        $this->Database->DatabasePrefix = $oldPrefix;

        // Get the definition for this column
        $oldColumn = val($oldName, $schema);
        $newColumn = val($newName, $schema);

        // Make sure that one column, or the other exists
        if (!$oldColumn && !$newColumn) {
            throw new Exception(sprintf(t('The `%1$s` column does not exist.'), $oldName));
        }

        // Make sure the new column name isn't already taken
        if ($oldColumn && $newColumn) {
            throw new Exception(sprintf(t('You cannot rename the `%1$s` column to `%2$s` because that column already exists.'), $oldName, $newName));
        }

        // Rename the column
        // The syntax for renaming a column is:
        // ALTER TABLE tablename CHANGE COLUMN oldname newname originaldefinition;
        if (!$this->executeQuery('alter table `'.$oldPrefix.$this->_TableName.'` change column '.$this->_defineColumn($oldColumn, $newName))) {
            throw new Exception(sprintf(t('Failed to rename table `%1$s` to `%2$s`.'), $oldName, $newName));
        }

        return true;
    }

    /**
     * Renames a table in the database.
     *
     * @param string $oldName The name of the table to be renamed.
     * @param string $newName The new name for the table being renamed.
     * @param boolean $usePrefix A boolean value indicating if $this->_DatabasePrefix should be prefixed
     * before $oldName and $newName.
     * @return boolean
     */
    public function renameTable($oldName, $newName, $usePrefix = false) {
        if (!$this->executeQuery('rename table `'.$oldName.'` to `'.$newName.'`')) {
            throw new Exception(sprintf(t('Failed to rename table `%1$s` to `%2$s`.'), $oldName, $newName));
        }

        return true;
    }

    /**
     * Specifies the name of the view to create or modify.
     *
     * @param string $name The name of the view.
     * @param string $Query The actual query to create as the view. Typically
     * this can be generated with the $Database object.
     */
    public function view($name, $sQL) {
        if (is_string($sQL)) {
            $sQLString = $sQL;
            $sQL = null;
        } else {
            $sQLString = $sQL->getSelect();
        }

        $result = $this->executeQuery('create or replace view '.$this->_DatabasePrefix.$name." as \n".$sQLString);
        if (!is_null($sQL)) {
            $sQL->reset();
        }
    }

    /**
     * Creates the table defined with $this->table() and $this->column().
     */
    protected function _create() {
        $primaryKey = [];
        $uniqueKey = [];
        $fullTextKey = [];
        $allowFullText = true;
        $indexes = [];
        $keys = '';
        $sql = '';
        $tableName = Gdn_Format::alphaNumeric($this->_TableName);

        $forceDatabaseEngine = c('Database.ForceStorageEngine');
        if ($forceDatabaseEngine && !$this->_TableStorageEngine) {
            $this->_TableStorageEngine = $forceDatabaseEngine;
            $allowFullText = $this->_supportsFulltext();
        }

        foreach ($this->_Columns as $columnName => $column) {
            if ($sql != '') {
                $sql .= ',';
            }

            $sql .= "\n".$this->_defineColumn($column);

            $columnKeyTypes = (array)$column->KeyType;

            foreach ($columnKeyTypes as $columnKeyType) {
                $keyTypeParts = explode('.', $columnKeyType, 2);
                $columnKeyType = $keyTypeParts[0];
                $indexGroup = val(1, $keyTypeParts, '');

                if ($columnKeyType == 'primary') {
                    $primaryKey[] = $columnName;
                } elseif ($columnKeyType == 'key')
                    $indexes['FK'][$indexGroup][] = $columnName;
                elseif ($columnKeyType == 'index')
                    $indexes['IX'][$indexGroup][] = $columnName;
                elseif ($columnKeyType == 'unique')
                    $uniqueKey[] = $columnName;
                elseif ($columnKeyType == 'fulltext' && $allowFullText)
                    $fullTextKey[] = $columnName;
            }
        }
        // Build primary keys
        if (count($primaryKey) > 0) {
            $keys .= ",\nprimary key (`".implode('`, `', $primaryKey)."`)";
        }
        // Build unique keys.
        if (count($uniqueKey) > 0) {
            $keys .= ",\nunique index `UX_{$tableName}` (`".implode('`, `', $uniqueKey)."`)";
        }
        // Build full text index.
        if (count($fullTextKey) > 0) {
            $keys .= ",\nfulltext index `TX_{$tableName}` (`".implode('`, `', $fullTextKey)."`)";
        }
        // Build the rest of the keys.
        foreach ($indexes as $indexType => $indexGroups) {
            $createString = val($indexType, ['FK' => 'key', 'IX' => 'index']);
            foreach ($indexGroups as $indexGroup => $columnNames) {
                if (!$indexGroup) {
                    foreach ($columnNames as $columnName) {
                        $keys .= ",\n{$createString} `{$indexType}_{$tableName}_{$columnName}` (`{$columnName}`)";
                    }
                } else {
                    $keys .= ",\n{$createString} `{$indexType}_{$tableName}_{$indexGroup}` (`".implode('`, `', $columnNames).'`)';
                }
            }
        }

        $sql = 'create table `'.$this->_DatabasePrefix.$tableName.'` ('
            .$sql
            .$keys
            ."\n)";

        // Check to see if there are any fulltext columns, otherwise use innodb.
        if (!$this->_TableStorageEngine) {
            $hasFulltext = false;
            foreach ($this->_Columns as $column) {
                $columnKeyTypes = (array)$column->KeyType;
                array_map('strtolower', $columnKeyTypes);
                if (in_array('fulltext', $columnKeyTypes)) {
                    $hasFulltext = true;
                    break;
                }
            }
            if ($hasFulltext) {
                $this->_TableStorageEngine = 'myisam';
            } else {
                $this->_TableStorageEngine = c('Database.DefaultStorageEngine', 'innodb');
            }

            if (!$this->hasEngine($this->_TableStorageEngine)) {
                $this->_TableStorageEngine = 'myisam';
            }
        }

        if ($this->_TableStorageEngine) {
            $sql .= ' engine='.$this->_TableStorageEngine;
        }

        if ($this->_CharacterEncoding !== false && $this->_CharacterEncoding != '') {
            $sql .= ' default character set '.$this->_CharacterEncoding;
        }

        if (array_key_exists('Collate', $this->Database->ExtendedProperties)) {
            $sql .= ' collate '.$this->Database->ExtendedProperties['Collate'];
        }

        $sql .= ';';

        $result = $this->executeQuery($sql);
        $this->reset();

        return $result;
    }

    /**
     * Get the character set for a  collation.
     * @param string $collation The name of the collation.
     * @return string Returns the name of the character set or an empty string if the collation was not found.
     */
    protected function getCharsetFromCollation($collation) {
        static $cache = [];

        $collation = strtolower($collation);

        if (!isset($cache[$collation])) {
            $collationRow = $this->Database->query('show collation where Collation = :c', [':c' => $collation])->firstRow(DATASET_TYPE_ARRAY);
            $cache[$collation] = val('Charset', $collationRow, '');
        }

        return $cache[$collation];
    }

    /**
     * Get the high-level table information for a given table.
     *
     * @param string $tableName The name of the table to get the information for.
     * @return array? Returns an array of table information.
     */
    protected function getTableInfo($tableName) {
        $pxName = $this->_DatabasePrefix.$tableName;
        $status = $this->Database->query("show table status where name = '$pxName'")->firstRow(DATASET_TYPE_ARRAY);

        if (!$status) {
            return null;
        }

        $result = arrayTranslate($status, ['Engine' => 'engine', 'Rows' => 'rows', 'Collation' => 'collation']);

        // Look up the encoding for the collation.
        $result['charset'] = $this->getCharsetFromCollation($result['collation']);
        return $result;
    }

    /**
     *
     *
     * @param $columns
     * @param bool $keyType
     * @return array
     */
    protected function _indexSql($columns, $keyType = false) {
//      if ($this->tableName() != 'Comment')
//         return array();

        $result = [];
        $keys = [];
        $prefixes = ['key' => 'FK_', 'index' => 'IX_', 'unique' => 'UX_', 'fulltext' => 'TX_'];
        $indexes = [];

        // Gather the names of the columns.
        foreach ($columns as $columnName => $column) {
            $columnKeyTypes = (array)$column->KeyType;

            foreach ($columnKeyTypes as $columnKeyType) {
                $parts = explode('.', $columnKeyType, 2);
                $columnKeyType = $parts[0];
                $indexGroup = val(1, $parts, '');

                if (!$columnKeyType || ($keyType && $keyType != $columnKeyType)) {
                    continue;
                }

                // Don't add a fulltext if we don't support.
                if ($columnKeyType == 'fulltext' && !$this->_supportsFulltext()) {
                    continue;
                }

                $indexes[$columnKeyType][$indexGroup][] = $columnName;
            }
        }

        // Make the multi-column keys into sql statements.
        foreach ($indexes as $columnKeyType => $indexGroups) {
            $createType = val($columnKeyType, ['index' => 'index', 'key' => 'key', 'unique' => 'unique index', 'fulltext' => 'fulltext index', 'primary' => 'primary key']);

            if ($columnKeyType == 'primary') {
                $result['PRIMARY'] = 'primary key (`'.implode('`, `', $indexGroups['']).'`)';
            } else {
                foreach ($indexGroups as $indexGroup => $columnNames) {
                    $multi = (strlen($indexGroup) > 0 || in_array($columnKeyType, ['unique', 'fulltext']));

                    if ($multi) {
                        $indexName = "{$prefixes[$columnKeyType]}{$this->_TableName}".($indexGroup ? '_'.$indexGroup : '');

                        $result[$indexName] = "$createType $indexName (`".implode('`, `', $columnNames).'`)';
                    } else {
                        foreach ($columnNames as $columnName) {
                            $indexName = "{$prefixes[$columnKeyType]}{$this->_TableName}_$columnName";

                            $result[$indexName] = "$createType $indexName (`$columnName`)";
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     *
     *
     * @return array
     */
    public function indexSqlDb() {
        return $this->_indexSqlDb();
    }

    /**
     *
     *
     * @return array
     */
    protected function _indexSqlDb() {
        // We don't want this to be captured so send it directly.
        $data = $this->Database->query('show indexes from '.$this->_DatabasePrefix.$this->_TableName);

        $result = [];
        foreach ($data as $row) {
            if (array_key_exists($row->Key_name, $result)) {
                $result[$row->Key_name] .= ', `'.$row->Column_name.'`';
            } else {
                switch (strtoupper(substr($row->Key_name, 0, 2))) {
                    case 'PR':
                        $type = 'primary key';
                        break;
                    case 'FK':
                        $type = 'key '.$row->Key_name;
                        break;
                    case 'IX':
                        $type = 'index '.$row->Key_name;
                        break;
                    case 'UX':
                        $type = 'unique index '.$row->Key_name;
                        break;
                    case 'TX':
                        $type = 'fulltext index '.$row->Key_name;
                        break;
                    default:
                        // Try and guess the index type.
                        if (strcasecmp($row->Index_type, 'fulltext') == 0) {
                            $type = 'fulltext index '.$row->Key_name;
                        } elseif ($row->Non_unique)
                            $type = 'index '.$row->Key_name;
                        else {
                            $type = 'unique index '.$row->Key_name;
                        }

                        break;
                }
                $result[$row->Key_name] = $type.' (`'.$row->Column_name.'`';
            }
        }

        // Cap off the sql.
        foreach ($result as $name => $sql) {
            $result[$name] .= ')';
        }

        return $result;
    }

    /**
     * Modifies $this->table() with the columns specified with $this->column().
     *
     * @param boolean $explicit If TRUE, this method will remove any columns from the table that were not
     * defined with $this->column().
     */
    protected function _modify($explicit = false) {
        $px = $this->_DatabasePrefix;
        $additionalSql = []; // statements executed at the end
        $tableInfo = $this->getTableInfo($this->_TableName);

        // Returns an array of schema data objects for each field in the specified
        // table. The returned array of objects contains the following properties:
        // Name, PrimaryKey, Type, AllowNull, Default, Length, Enum.
        $existingColumns = array_change_key_case($this->existingColumns());
        $columns = array_change_key_case($this->_Columns);
        $alterSql = [];
        $invalidAlterSqlCount = 0;

        // 1. Remove any unnecessary columns if this is an explicit modification
        if ($explicit) {
            // array_diff returns values from the first array that aren't present
            // in the second array. In this example, all columns currently in the
            // table that are NOT in $this->_Columns.
            $removeColumns = array_diff(array_keys($existingColumns), array_keys($columns));
            foreach ($removeColumns as $column) {
                $alterSql[] = "drop column `$column`";
            }
        }

        // Prepare the alter query
        $alterSqlPrefix = 'alter table `'.$this->_DatabasePrefix.$this->_TableName."`\n";

        // 2. Alter the table storage engine.
        $forceDatabaseEngine = c('Database.ForceStorageEngine');
        if ($forceDatabaseEngine && !$this->_TableStorageEngine) {
            $this->_TableStorageEngine = $forceDatabaseEngine;
        }
        $indexes = $this->_indexSql($this->_Columns);
        $indexesDb = $this->_indexSqlDb();

        if ($this->_TableStorageEngine) {
            $currentEngine = val('engine', $tableInfo);

            if (strcasecmp($currentEngine, $this->_TableStorageEngine)) {
                // Check to drop a fulltext index if we don't support it.
                if (!$this->_supportsFulltext()) {
                    foreach ($indexesDb as $indexName => $indexSql) {
                        if (stringBeginsWith($indexSql, 'fulltext', true)) {
                            $dropIndexQuery = "$alterSqlPrefix drop index $indexName;\n";
                            if (!$this->executeQuery($dropIndexQuery)) {
                                throw new Exception(sprintf(t('Failed to drop the index `%1$s` on table `%2$s`.'), $indexName, $this->_TableName));
                            }
                        }
                    }
                }

                $engineQuery = $alterSqlPrefix.' engine = '.$this->_TableStorageEngine;
                if (!$this->executeQuery($engineQuery, true)) {
                    throw new Exception(sprintf(t('Failed to alter the storage engine of table `%1$s` to `%2$s`.'), $this->_DatabasePrefix.$this->_TableName, $this->_TableStorageEngine));
                }
            }
        }

        // 3. Add new columns & modify existing ones

        // array_diff returns values from the first array that aren't present in
        // the second array. In this example, all columns in $this->_Columns that
        // are NOT in the table.
        $prevColumnName = false;
        foreach ($columns as $columnKey => $column) {
            $columnName = val('Name', $column);
            if (!array_key_exists($columnKey, $existingColumns)) {
                // This column name is not in the existing column collection, so add the column
                $addColumnSql = 'add '.$this->_defineColumn($column);
                if ($prevColumnName !== false) {
                    $addColumnSql .= " after `$prevColumnName`";
                }

                $alterSql[] = $addColumnSql;

            } else {
                $existingColumn = $existingColumns[$columnKey];

                $existingColumnDef = $this->_defineColumn($existingColumn);
                $columnDef = $this->_defineColumn($column);
                $comment = "-- [Existing: $existingColumnDef, New: $columnDef]";

                if ($existingColumnDef !== $columnDef) {

                    // The existing & new column types do not match, so modify the column.
                    $changeSql = "$comment\nchange `{$existingColumn->Name}` $columnDef";

                    if (strcasecmp($existingColumn->Type, 'varchar') === 0 && strcasecmp($column->Type, 'varchar') === 0
                            && $existingColumn->Length > $column->Length) {

                        $charLength = $this->Database->query("select max(char_length(`$columnName`)) as MaxLength from `$px{$this->_TableName}`;")
                            ->firstRow(DATASET_TYPE_ARRAY);

                        if ($charLength['MaxLength'] > $column->Length) {
                            if ($this->CaptureOnly) {
                                $changeSql = str_replace($comment."\n", $comment."\n-- [Integrity Error: The column contains data ({$charLength['MaxLength']} characters) that would be truncated]\n-- ", $changeSql);
                                $invalidAlterSqlCount++;
                            } else {
                                $this->addIssue("The table's column was not altered because it contains a varchar of length {$charLength['MaxLength']}.", $comment);

                                // Log an event to be captured and analysed later.
                                Logger::event(
                                    'structure_integrity',
                                    Logger::ALERT,
                                    "Cannot modify {tableName}'s column {column} because it has a value that is {maxVarcharLength,number} characters long and the new length is {newLength,number}.",
                                    [
                                        'tableName' => $this->tableName(),
                                        'column' => $columnName,
                                        'maxVarcharLength' => $charLength['MaxLength'],
                                        'newLength' => $column->Length,
                                        'oldLength' => $existingColumn->Length,
                                    ]
                                );

                                // Skip adding the column to the query.
                                continue;
                            }
                        }
                    }

                    $alterSql[] = $changeSql;

                    // Check for a modification from an enum to an int.
                    if (strcasecmp($existingColumn->Type, 'enum') == 0 && in_array(strtolower($column->Type), $this->types('int'))) {
                        $sql = "update `$px{$this->_TableName}` set `$columnName` = case `$columnName`";
                        foreach ($existingColumn->Enum as $index => $newValue) {
                            $oldValue = $index + 1;

                            if (!is_numeric($newValue)) {
                                continue;
                            }
                            $newValue = (int)$newValue;

                            $sql .= " when $oldValue then $newValue";
                        }
                        $sql .= " else `$columnName` end";
                        $description = "Update {$this->_TableName}.$columnName enum values to {$column->Type}";
                        $additionalSql[$description] = $sql;

                    }
                }
            }
            $prevColumnName = $columnName;
        }

        // 4. Alter the character set and collation.
        if ($this->_CharacterEncoding && (strcasecmp($tableInfo['charset'], $this->_CharacterEncoding)
                || strcasecmp($tableInfo['collation'], val('Collate', $this->Database->ExtendedProperties)))
        ) {
            $charset = $this->_CharacterEncoding;
            $collation = val('Collate', $this->Database->ExtendedProperties);

            $charsetSql = "character set $charset".($collation ? " collate $collation" : '');
            $alterSql[] = $charsetSql;
            $alterSql[] = "convert to $charsetSql";
        }

        if (count($alterSql)) {
            $builtQuery = $alterSqlPrefix.implode(",\n", $alterSql);
            if (count($alterSql) === $invalidAlterSqlCount) {
                $builtQuery = '-- '.$builtQuery;
            }
            if (!$this->executeQuery($builtQuery, true)) {
                throw new Exception(sprintf(t('Failed to alter the `%s` table.'), $this->_DatabasePrefix.$this->_TableName));
            }
        }

        // 5. Update Indexes.
        $indexSql = [];
        // Go through the indexes to add or modify.
        foreach ($indexes as $name => $sql) {
            if (array_key_exists($name, $indexesDb)) {
                if ($indexes[$name] != $indexesDb[$name]) {
//               $IndexSql[$Name][] = "/* '{$IndexesDb[$Name]}' => '{$Indexes[$Name]}' */\n";
                    if ($name == 'PRIMARY') {
                        $indexSql[$name][] = $alterSqlPrefix."drop primary key;\n";
                    } else {
                        $indexSql[$name][] = $alterSqlPrefix.'drop index '.$name.";\n";
                    }
                    $indexSql[$name][] = $alterSqlPrefix."add $sql;\n";
                }
                unset($indexesDb[$name]);
            } else {
                $indexSql[$name][] = $alterSqlPrefix."add $sql;\n";
            }
        }
        // Go through the indexes to drop.
        if ($explicit) {
            foreach ($indexesDb as $name => $sql) {
                if ($name == 'PRIMARY') {
                    $indexSql[$name][] = $alterSqlPrefix."drop primary key;\n";
                } else {
                    $indexSql[$name][] = $alterSqlPrefix.'drop index '.$name.";\n";
                }
            }
        }

        // Modify all of the indexes.
        foreach ($indexSql as $name => $sqls) {
            foreach ($sqls as $sql) {
                if (!$this->executeQuery($sql)) {
                    throw new Exception(sprintf(t('Error.ModifyIndex', 'Failed to add or modify the `%1$s` index in the `%2$s` table.'), $name, $this->_TableName));
                }
            }
        }

        // Run any additional Sql.
        foreach ($additionalSql as $description => $sql) {
            // These queries are just for enum alters. If that changes then pass true as the second argument.
            if (!$this->executeQuery($sql)) {
                throw new Exception("Error modifying table: {$description}.");
            }
        }

        $this->reset();
        return true;
    }

    /**
     *
     *
     * @param stdClass $column
     * @param string $newColumnName For rename action only.
     */
    protected function _defineColumn($column, $newColumnName = null) {
        $column = clone $column;

        $typeAliases = [
            'ipaddress' => ['Type' => 'varbinary', 'Length' => 16]
        ];

        $validColumnTypes = [
            'tinyint',
            'smallint',
            'mediumint',
            'int',
            'bigint',
            'char',
            'varchar',
            'varbinary',
            'date',
            'datetime',
            'mediumtext',
            'longtext',
            'text',
            'decimal',
            'numeric',
            'float',
            'double',
            'enum',
            'timestamp',
            'tinyblob',
            'blob',
            'mediumblob',
            'longblob',
            'bit'
        ];

        $column->Type = strtolower($column->Type);

        if (array_key_exists($column->Type, $typeAliases)) {
            foreach ($typeAliases[$column->Type] as $key => $value) {
                setValue($key, $column, $value);
            }
        }

        if (!in_array($column->Type, $validColumnTypes)) {
            throw new Exception(sprintf(t('The specified data type (%1$s) is not accepted for the MySQL database.'), $column->Type));
        }

        $return = "`{$column->Name}` ";

        // The CHANGE COLUMN syntax requires this ordering.
        // @see $this->renameColumn().
        if (is_string($newColumnName)) {
            $return .= "`{$newColumnName}` ";
        }

        $return .= "{$column->Type}";

        $lengthTypes = $this->types('length');
        if ($column->Length != '' && in_array($column->Type, $lengthTypes)) {
            if ($column->Precision != '') {
                $return .= '('.$column->Length.', '.$column->Precision.')';
            } else {
                $return .= '('.$column->Length.')';
            }
        }
        if (property_exists($column, 'Unsigned') && $column->Unsigned) {
            $return .= ' unsigned';
        }

        if (is_array($column->Enum)) {
            $return .= "('".implode("','", $column->Enum)."')";
        }

        if (!$column->AllowNull) {
            $return .= ' not null';
        } else {
            $return .= ' null';
        }

        if (!is_null($column->Default)) {
            if ($column->Type !== 'timestamp') {
                $return .= " default ".self::_quoteValue($column->Default);
            } else {
                if (in_array(strtolower($column->Default), ['current_timestamp', 'current_timestamp()'])) {
                    $return .= " default ".$column->Default;
                }
            }
        }

        if ($column->AutoIncrement) {
            $return .= ' auto_increment';
        }

        return $return;
    }

    /**
     *
     *
     * @param $value
     * @return string
     */
    protected static function _quoteValue($value) {
        if (is_numeric($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } else {
            return "'".str_replace("'", "''", $value)."'";
        }
    }

    /**
     *
     *
     * @return bool
     */
    protected function _supportsFulltext() {
        return strcasecmp($this->_TableStorageEngine, 'myisam') == 0;
    }
}
