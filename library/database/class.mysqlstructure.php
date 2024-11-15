<?php
/**
 * MySQL structure driver.
 *
 * MySQL-specific structure tools for performing structural changes on MySQL
 * database servers.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

/**
 * Class Gdn_MySQLStructure
 */
class Gdn_MySQLStructure extends Gdn_DatabaseStructure
{
    /** Default options when creating MySQL table indexes. */
    private const INDEX_OPTIONS = "algorithm=inplace, lock=none";

    /**
     * @var array[int] An array of table names to row count estimates.
     */
    private $rowCountEstimates;

    /** @var Gdn_MySQLDriver */
    private $sqlDriver;

    /**
     * Gdn_MySQLStructure constructor.
     *
     * @param Gdn_MySQLDriver $sqlDriver
     * @param Gdn_Database|null $database
     */
    public function __construct(Gdn_MySQLDriver $sqlDriver, $database = null)
    {
        $this->sqlDriver = $sqlDriver;
        parent::__construct($database);
    }

    /**
     * Execute a query. Clears the mysql driver cache afterwards.
     * @inheritdoc
     */
    public function executeQuery($sql, $checkThreshold = false)
    {
        try {
            return parent::executeQuery($sql, $checkThreshold);
        } finally {
            $this->sqlDriver->clearSchemaCache();
        }
    }

    /**
     * Drops $this->table() from the database.
     */
    public function drop()
    {
        if ($this->tableExists()) {
            return $this->executeQuery("drop table `" . $this->_DatabasePrefix . $this->_TableName . "`");
        }
    }

    /**
     * Drops $name column from $this->table().
     *
     * @param string $name The name of the column to drop from $this->table().
     * @return boolean
     */
    public function dropColumn($name)
    {
        if (
            !$this->executeQuery(
                "alter table `" . $this->_DatabasePrefix . $this->_TableName . "` drop column `" . $name . "`"
            )
        ) {
            throw new Exception(
                sprintf(
                    t('Failed to remove the `%1$s` column from the `%2$s` table.'),
                    $name,
                    $this->_DatabasePrefix . $this->_TableName
                )
            );
        }

        return true;
    }

    /**
     * Determine whether or not a storage engine exists.
     *
     * @param string $engine
     * @return bool
     */
    public function hasEngine($engine)
    {
        static $viableEngines = null;

        if ($viableEngines === null) {
            $engineList = $this->Database->query("SHOW ENGINES;");
            $viableEngines = [];
            while ($engineList && ($storageEngine = $engineList->value("Engine", false))) {
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
     * Set the storage engine of the table.
     *
     * @param string $engine
     * @param bool $checkAvailability
     * @return $this
     */
    public function engine($engine, $checkAvailability = true)
    {
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
    public function getRowCountEstimate($tableName)
    {
        if (!isset($this->rowCountEstimates)) {
            $data = $this->Database->query("show table status")->resultArray();
            $this->rowCountEstimates = [];
            foreach ($data as $row) {
                $name = stringBeginsWith($row["Name"], $this->Database->DatabasePrefix, false, true);
                $this->rowCountEstimates[$name] = $row["Rows"];
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
    public function renameColumn($oldName, $newName, $tableName = "")
    {
        if ($tableName != "") {
            $this->_TableName = $tableName;
        }

        // Get the schema for this table
        $oldPrefix = $this->Database->DatabasePrefix;
        $this->Database->DatabasePrefix = $this->_DatabasePrefix;
        $schema = $this->Database->sql()->fetchTableSchema($this->_TableName, true);
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
            throw new Exception(
                sprintf(
                    t('You cannot rename the `%1$s` column to `%2$s` because that column already exists.'),
                    $oldName,
                    $newName
                )
            );
        }

        // Rename the column
        // The syntax for renaming a column is:
        // ALTER TABLE tablename CHANGE COLUMN oldname newname originaldefinition;
        if (
            !$this->executeQuery(
                "alter table `" .
                    $oldPrefix .
                    $this->_TableName .
                    "` change column " .
                    $this->_defineColumn($oldColumn, $newName)
            )
        ) {
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
    public function renameTable($oldName, $newName, $usePrefix = false)
    {
        if (!$this->executeQuery("rename table `" . $oldName . "` to `" . $newName . "`")) {
            throw new Exception(sprintf(t('Failed to rename table `%1$s` to `%2$s`.'), $oldName, $newName));
        }

        return true;
    }

    /**
     * Specifies the name of the view to create or modify.
     *
     * @param string $name The name of the view.
     * @param string $sql The actual query to create as the view. Typically this can be generated with the $Database object.
     */
    public function view($name, $sql)
    {
        if (is_string($sql)) {
            $sQLString = $sql;
            $sql = null;
        } else {
            $sQLString = $sql->getSelect();
        }

        $result = $this->executeQuery(
            "create or replace view " . $this->_DatabasePrefix . $name . " as \n" . $sQLString
        );
        if (!is_null($sql)) {
            $sql->reset();
        }
    }

    /**
     * Creates the table defined with $this->table() and $this->column().
     */
    protected function _create()
    {
        $sql = $this->getCreateTable();

        $result = $this->executeQuery($sql);
        $this->reset();

        return $result;
    }

    /**
     * Generate the DDL for creating a table.
     *
     * @return string Returns a DDL statement.
     */
    final protected function getCreateTable(): string
    {
        $keys = "";
        $sql = "";
        $tableName = Gdn_Format::alphaNumeric($this->_TableName);

        foreach ($this->_Columns as $column) {
            if ($sql != "") {
                $sql .= ",";
            }

            $sql .= "\n" . $this->_defineColumn($column);
        }

        $keyDefs = $this->_indexSql($this->_Columns);
        foreach ($keyDefs as $keyDef) {
            $keys .= ",\n" . $keyDef;
        }

        $sql = "create table `" . $this->_DatabasePrefix . $tableName . "` (" . $sql . $keys . "\n)";

        $engine =
            $this->_TableStorageEngine ?:
            Gdn::config("Database.ForceStorageEngine", Gdn::config("Database.DefaultStorageEngine")) ?:
            "innodb";
        $sql .= " engine=" . $engine;

        if ($this->_CharacterEncoding !== false && $this->_CharacterEncoding != "") {
            $sql .= " default character set " . $this->_CharacterEncoding;
        }

        if (array_key_exists("Collate", $this->Database->ExtendedProperties)) {
            $sql .= " collate " . $this->Database->ExtendedProperties["Collate"];
        }

        $sql .= ";";

        return $sql;
    }

    /**
     * Get the character set for a  collation.
     *
     * @param string $collation The name of the collation.
     * @return string Returns the name of the character set or an empty string if the collation was not found.
     */
    protected function getCharsetFromCollation($collation)
    {
        static $cache = [];

        $collation = strtolower($collation);

        if (!isset($cache[$collation])) {
            $collationRow = $this->Database
                ->query("show collation where Collation = :c", [":c" => $collation])
                ->firstRow(DATASET_TYPE_ARRAY);
            $cache[$collation] = val("Charset", $collationRow, "");
        }

        return $cache[$collation];
    }

    /**
     * Get the high-level table information for a given table.
     *
     * @param string $tableName The name of the table to get the information for.
     * @return array? Returns an array of table information.
     */
    protected function getTableInfo($tableName)
    {
        $pxName = $this->_DatabasePrefix . $tableName;
        $status = $this->Database->query("show table status where name = '$pxName'")->firstRow(DATASET_TYPE_ARRAY);

        if (!$status) {
            return null;
        }

        $result = arrayTranslate($status, ["Engine" => "engine", "Rows" => "rows", "Collation" => "collation"]);

        // Look up the encoding for the collation.
        $result["charset"] = $this->getCharsetFromCollation($result["collation"]);
        return $result;
    }

    /**
     * Given an SQL representing a single basic alter-table query to add indexes, append the default index options.
     *
     * @param string $sql
     * @return string
     */
    private function indexSqlWithOptions(string $sql): string
    {
        $result = preg_replace('/;?(?=\n*$)/', ", " . self::INDEX_OPTIONS . ";", $sql, 1);
        return $result;
    }

    /**
     * Generate part of an alter table statement for modifying indexes.
     *
     * @param array $columns
     * @param bool $keyType
     * @return array
     */
    protected function _indexSql($columns, $keyType = false)
    {
        //      if ($this->tableName() != 'Comment')
        //         return array();

        $result = [];
        $keys = [];
        $prefixes = ["key" => "FK_", "index" => "IX_", "unique" => "UX_", "fulltext" => "TX_"];
        $indexes = [];

        // Gather the names of the columns.
        foreach ($columns as $columnName => $column) {
            $columnKeyTypes = (array) $column->KeyType;

            foreach ($columnKeyTypes as $columnKeyType) {
                $parts = explode(".", $columnKeyType, 2);
                $columnKeyType = $parts[0];
                $indexGroup = val(1, $parts, "");

                if (!$columnKeyType || ($keyType && $keyType != $columnKeyType)) {
                    continue;
                }

                $indexes[$columnKeyType][$indexGroup][] = $columnName;
            }
        }

        // Make the multi-column keys into sql statements.
        foreach ($indexes as $columnKeyType => $indexGroups) {
            $createType = val($columnKeyType, [
                "index" => "index",
                "key" => "key",
                "unique" => "unique index",
                "fulltext" => "fulltext index",
                "primary" => "primary key",
            ]);

            if ($columnKeyType == "primary") {
                $result["primary"] = "primary key (`" . implode("`, `", $indexGroups[""]) . "`)";
            } else {
                foreach ($indexGroups as $indexGroup => $columnNames) {
                    $multi = strlen($indexGroup) > 0 || in_array($columnKeyType, ["unique", "fulltext"]);

                    if ($multi) {
                        $indexName =
                            "{$prefixes[$columnKeyType]}{$this->_TableName}" . ($indexGroup ? "_" . $indexGroup : "");

                        $result[strtolower($indexName)] =
                            "$createType $indexName (`" . implode("`, `", $columnNames) . "`)";
                    } else {
                        foreach ($columnNames as $columnName) {
                            $indexName = "{$prefixes[$columnKeyType]}{$this->_TableName}_$columnName";

                            $result[strtolower($indexName)] = "$createType $indexName (`$columnName`)";
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get the SQL used to generate the indexes for this table.
     *
     * @return array
     */
    public function indexSqlDb()
    {
        return $this->_indexSqlDb();
    }

    /**
     * Get the SQL used to generate the indexes for this table.
     *
     * @return array
     */
    protected function _indexSqlDb()
    {
        // We don't want this to be captured so send it directly.
        $data = $this->Database->query("show indexes from " . $this->_DatabasePrefix . $this->_TableName);

        $result = [];
        foreach ($data as $row) {
            $keyName = strtolower($row->Key_name);

            if (array_key_exists($keyName, $result)) {
                $result[$keyName] .= ", `" . $row->Column_name . "`";
            } else {
                switch (strtoupper(substr($row->Key_name, 0, 2))) {
                    case "PR":
                        $type = "primary key";
                        break;
                    case "FK":
                        $type = "key " . $row->Key_name;
                        break;
                    case "IX":
                        $type = "index " . $row->Key_name;
                        break;
                    case "UX":
                        $type = "unique index " . $row->Key_name;
                        break;
                    case "TX":
                        $type = "fulltext index " . $row->Key_name;
                        break;
                    default:
                        // Try and guess the index type.
                        if (strcasecmp($row->Index_type, "fulltext") == 0) {
                            $type = "fulltext index " . $row->Key_name;
                        } elseif ($row->Non_unique) {
                            $type = "index " . $row->Key_name;
                        } else {
                            $type = "unique index " . $row->Key_name;
                        }

                        break;
                }
                $result[$keyName] = $type . " (`" . $row->Column_name . "`";
            }
        }

        // Cap off the sql.
        foreach ($result as $name => $sql) {
            $result[$name] .= ")";
        }

        return $result;
    }

    /**
     * Modifies $this->table() with the columns specified with $this->column().
     *
     * @param bool $explicit If TRUE, this method will remove any columns from the table that were not
     * defined with $this->column().
     * @return bool
     */
    protected function _modify($explicit = false)
    {
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
        $alterSqlPrefix = "alter table `" . $this->_DatabasePrefix . $this->_TableName . "`\n";

        // 2. Alter the table storage engine.
        $forceDatabaseEngine = c("Database.ForceStorageEngine");
        if ($forceDatabaseEngine && !$this->_TableStorageEngine) {
            $this->_TableStorageEngine = $forceDatabaseEngine;
        }
        $indexes = $this->_indexSql($this->_Columns);
        $indexesDb = $this->_indexSqlDb();

        if ($this->_TableStorageEngine) {
            $currentEngine = val("engine", $tableInfo);

            if (strcasecmp($currentEngine, $this->_TableStorageEngine)) {
                $engineQuery = $alterSqlPrefix . " engine = " . $this->_TableStorageEngine;
                if (!$this->executeQuery($engineQuery, true)) {
                    throw new Exception(
                        sprintf(
                            t('Failed to alter the storage engine of table `%1$s` to `%2$s`.'),
                            $this->_DatabasePrefix . $this->_TableName,
                            $this->_TableStorageEngine
                        )
                    );
                }
            }
        }

        // 3. Add new columns & modify existing ones

        // array_diff returns values from the first array that aren't present in
        // the second array. In this example, all columns in $this->_Columns that
        // are NOT in the table.
        $prevColumnName = false;
        foreach ($columns as $columnKey => $column) {
            $columnName = val("Name", $column);
            if (!array_key_exists($columnKey, $existingColumns)) {
                // This column name is not in the existing column collection, so add the column
                $addColumnSql = "add " . $this->_defineColumn($column);
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

                    if (
                        strcasecmp($existingColumn->Type, "varchar") === 0 &&
                        strcasecmp($column->Type, "varchar") === 0 &&
                        $existingColumn->Length > $column->Length
                    ) {
                        $charLength = $this->Database
                            ->query(
                                "select max(char_length(`$columnName`)) as MaxLength from `$px{$this->_TableName}`;"
                            )
                            ->firstRow(DATASET_TYPE_ARRAY);

                        if ($charLength["MaxLength"] > $column->Length) {
                            if ($this->CaptureOnly) {
                                $changeSql = str_replace(
                                    $comment . "\n",
                                    $comment .
                                        "\n-- [Integrity Error: The column contains data ({$charLength["MaxLength"]} characters) that would be truncated]\n-- ",
                                    $changeSql
                                );
                                $invalidAlterSqlCount++;
                            } else {
                                $this->addIssue(
                                    "The table's column was not altered because it contains a varchar of length {$charLength["MaxLength"]}.",
                                    $comment
                                );

                                // Log an event to be captured and analysed later.
                                Logger::event(
                                    "structure_integrity",
                                    Logger::ALERT,
                                    "Cannot modify {tableName}'s column {column} because it has a value that is {maxVarcharLength,number} characters long and the new length is {newLength,number}.",
                                    [
                                        "tableName" => $this->tableName(),
                                        "column" => $columnName,
                                        "maxVarcharLength" => $charLength["MaxLength"],
                                        "newLength" => $column->Length,
                                        "oldLength" => $existingColumn->Length,
                                        Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
                                    ]
                                );

                                // Skip adding the column to the query.
                                continue;
                            }
                        }
                    }

                    $alterSql[] = $changeSql;

                    // Check for a modification from an enum to an int.
                    if (
                        strcasecmp($existingColumn->Type, "enum") == 0 &&
                        in_array(strtolower($column->Type), $this->types("int"))
                    ) {
                        $sql = "update `$px{$this->_TableName}` set `$columnName` = case `$columnName`";
                        foreach ($existingColumn->Enum as $index => $newValue) {
                            $oldValue = $index + 1;

                            if (!is_numeric($newValue)) {
                                continue;
                            }
                            $newValue = (int) $newValue;

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
        if (
            $this->_CharacterEncoding &&
            (strcasecmp($tableInfo["charset"], $this->_CharacterEncoding) ||
                strcasecmp($tableInfo["collation"], val("Collate", $this->Database->ExtendedProperties)))
        ) {
            $charset = $this->_CharacterEncoding;
            $collation = val("Collate", $this->Database->ExtendedProperties);

            $charsetSql = "character set $charset" . ($collation ? " collate $collation" : "");
            $alterSql[] = $charsetSql;
            $alterSql[] = "convert to $charsetSql";
        }

        if (count($alterSql)) {
            $builtQuery = $alterSqlPrefix . implode(",\n", $alterSql);
            if (count($alterSql) === $invalidAlterSqlCount) {
                $builtQuery = "-- " . $builtQuery;
            }
            if (!$this->executeQuery($builtQuery, true)) {
                throw new Exception(
                    sprintf(t("Failed to alter the `%s` table."), $this->_DatabasePrefix . $this->_TableName)
                );
            }
        }

        // 5. Update Indexes.
        $indexSql = [];
        // Go through the indexes to add or modify.
        foreach ($indexes as $name => $sql) {
            if (array_key_exists($name, $indexesDb)) {
                if (strcasecmp($indexes[$name], $indexesDb[$name]) !== 0) {
                    //               $IndexSql[$Name][] = "/* '{$IndexesDb[$Name]}' => '{$Indexes[$Name]}' */\n";
                    if ($name == "primary") {
                        $indexSql[$name][] = $alterSqlPrefix . "drop primary key;\n";
                    } else {
                        $indexSql[$name][] = $alterSqlPrefix . "drop index " . $name . ";\n";
                    }
                    $indexSql[$name][] = $alterSqlPrefix . "add $sql;\n";
                }
                unset($indexesDb[$name]);
            } else {
                $indexSql[$name][] = $alterSqlPrefix . "add $sql;\n";
            }
        }
        // Go through the indexes to drop.
        if ($explicit) {
            foreach ($indexesDb as $name => $sql) {
                if ($name == "primary") {
                    $indexSql[$name][] = $alterSqlPrefix . "drop primary key;\n";
                } else {
                    $indexSql[$name][] = $alterSqlPrefix . "drop index " . $name . ";\n";
                }
            }
        }

        // Modify all of the indexes.
        $indexErrorTemplate = t("Error.ModifyIndex", 'Failed to add or modify the `%1$s` index in the `%2$s` table.');
        foreach ($indexSql as $name => $sqls) {
            foreach ($sqls as $sql) {
                try {
                    $sqlWithOptions = $this->indexSqlWithOptions($sql);
                    if (!$this->executeQuery($sqlWithOptions)) {
                        throw new AlterDatabaseException(sprintf($indexErrorTemplate, $name, $this->_TableName));
                    }
                } catch (Exception $e) {
                    // If index creation fails, try without the default options and enforce the threshold check.
                    if (!$this->executeQuery($sql, true)) {
                        throw new AlterDatabaseException(sprintf($indexErrorTemplate, $name, $this->_TableName));
                    }
                }
            }
        }

        // Run any additional Sql.
        foreach ($additionalSql as $description => $sql) {
            // These queries are just for enum alters. If that changes then pass true as the second argument.
            if (!$this->executeQuery($sql, true)) {
                throw new Exception("Error modifying table: {$description}.");
            }
        }

        $this->reset();
        return true;
    }

    /**
     * Get the DDL expression for a column definition.
     *
     * @param object $column
     * @param string $newColumnName For rename action only.
     * @return string
     */
    protected function _defineColumn($column, $newColumnName = null)
    {
        $column = clone $column;

        $typeAliases = [
            "ipaddress" => ["Type" => "varbinary", "Length" => 16],
        ];

        $validColumnTypes = [
            "tinyint",
            "smallint",
            "mediumint",
            "int",
            "bigint",
            "char",
            "varchar",
            "varbinary",
            "date",
            "datetime",
            "mediumtext",
            "longtext",
            "text",
            "tinytext",
            "decimal",
            "numeric",
            "float",
            "double",
            "enum",
            "timestamp",
            "tinyblob",
            "blob",
            "mediumblob",
            "longblob",
            "bit",
            "json",
        ];

        $column->Type = strtolower($column->Type);

        if (array_key_exists($column->Type, $typeAliases)) {
            foreach ($typeAliases[$column->Type] as $key => $value) {
                setValue($key, $column, $value);
            }
        }

        if (!in_array($column->Type, $validColumnTypes)) {
            throw new Exception(
                sprintf(t('The specified data type (%1$s) is not accepted for the MySQL database.'), $column->Type)
            );
        }

        $return = "`{$column->Name}` ";

        // The CHANGE COLUMN syntax requires this ordering.
        // @see $this->renameColumn().
        if (is_string($newColumnName)) {
            $return .= "`{$newColumnName}` ";
        }

        $return .= "{$column->Type}";

        if ($column->Type === "datetime" && $column->Precision != "") {
            $return .= "($column->Precision)";
        }

        $lengthTypes = $this->types("defineLength");
        if ($column->Length != "" && in_array($column->Type, $lengthTypes)) {
            if ($column->Precision != "") {
                $return .= "(" . $column->Length . ", " . $column->Precision . ")";
            } else {
                $return .= "(" . $column->Length . ")";
            }
        }
        if (property_exists($column, "Unsigned") && $column->Unsigned) {
            $return .= " unsigned";
        }

        if (is_array($column->Enum)) {
            $return .= "('" . implode("','", $column->Enum) . "')";
        }

        if (!$column->AllowNull) {
            $return .= " not null";
        } else {
            $return .= " null";
        }

        if (!is_null($column->Default)) {
            if ($column->Type !== "timestamp") {
                $return .= " default " . self::_quoteValue($column->Default);
            } else {
                if (in_array(strtolower($column->Default), ["current_timestamp", "current_timestamp()", true])) {
                    $return .= " default current_timestamp";
                }
            }
        }

        if ($column->AutoIncrement) {
            $return .= " auto_increment";
        }

        return $return;
    }

    /**
     * Quote a value for the database.
     *
     * @param mixed $value
     * @return string
     */
    protected static function _quoteValue($value)
    {
        if (is_numeric($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return $value ? "1" : "0";
        } else {
            return "'" . str_replace("'", "''", $value) . "'";
        }
    }
}
