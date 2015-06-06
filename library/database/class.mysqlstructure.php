<?php
/**
 * MySQL structure driver.
 *
 * MySQL-specific structure tools for performing structural changes on MySQL
 * database servers.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Class Gdn_MySQLStructure
 */
class Gdn_MySQLStructure extends Gdn_DatabaseStructure {

    /**
     *
     *
     * @param null $Database
     */
    public function __construct($Database = null) {
        parent::__construct($Database);
    }

    /**
     * Drops $this->Table() from the database.
     */
    public function drop() {
        if ($this->tableExists()) {
            return $this->query('drop table `'.$this->_DatabasePrefix.$this->_TableName.'`');
        }
    }

    /**
     * Drops $Name column from $this->Table().
     *
     * @param string $Name The name of the column to drop from $this->Table().
     * @return boolean
     */
    public function dropColumn($Name) {
        if (!$this->query('alter table `'.$this->_DatabasePrefix.$this->_TableName.'` drop column `'.$Name.'`')) {
            throw new Exception(sprintf(T('Failed to remove the `%1$s` column from the `%2$s` table.'), $Name, $this->_DatabasePrefix.$this->_TableName));
        }

        return true;
    }

    /**
     *
     *
     * @param $Engine
     * @return bool
     */
    public function hasEngine($Engine) {
        static $ViableEngines = null;

        if ($ViableEngines === null) {
            $EngineList = $this->Database->query("SHOW ENGINES;");
            $ViableEngines = array();
            while ($EngineList && $StorageEngine = $EngineList->value('Engine', false)) {
                $EngineName = strtolower($StorageEngine);
                $ViableEngines[$EngineName] = true;
            }
        }

        if (array_key_exists($Engine, $ViableEngines)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     *
     * @param $Engine
     * @param bool $CheckAvailability
     * @return $this
     */
    public function engine($Engine, $CheckAvailability = true) {
        $Engine = strtolower($Engine);

        if ($CheckAvailability) {
            if (!$this->HasEngine($Engine)) {
                return $this;
            }
        }

        $this->_TableStorageEngine = $Engine;
        return $this;
    }

    /**
     * Renames a column in $this->Table().
     *
     * @param string $OldName The name of the column to be renamed.
     * @param string $NewName The new name for the column being renamed.
     * @param string $TableName
     * @return boolean
     */
    public function renameColumn($OldName, $NewName, $TableName = '') {
        if ($TableName != '') {
            $this->_TableName = $TableName;
        }

        // Get the schema for this table
        $OldPrefix = $this->Database->DatabasePrefix;
        $this->Database->DatabasePrefix = $this->_DatabasePrefix;
        $Schema = $this->Database->sql()->fetchTableSchema($this->_TableName);
        $this->Database->DatabasePrefix = $OldPrefix;

        // Get the definition for this column
        $OldColumn = arrayValue($OldName, $Schema);
        $NewColumn = arrayValue($NewName, $Schema);

        // Make sure that one column, or the other exists
        if (!$OldColumn && !$NewColumn) {
            throw new Exception(sprintf(t('The `%1$s` column does not exist.'), $OldName));
        }

        // Make sure the new column name isn't already taken
        if ($OldColumn && $NewColumn) {
            throw new Exception(sprintf(t('You cannot rename the `%1$s` column to `%2$s` because that column already exists.'), $OldName, $NewName));
        }

        // Rename the column
        // The syntax for renaming a column is:
        // ALTER TABLE tablename CHANGE COLUMN oldname newname originaldefinition;
        if (!$this->query('alter table `'.$OldPrefix.$this->_TableName.'` change column `'.$OldName.'` `'.$NewName.'` '.$this->_defineColumn($OldColumn))) {
            throw new Exception(sprintf(t('Failed to rename table `%1$s` to `%2$s`.'), $OldName, $NewName));
        }

        return true;
    }

    /**
     * Renames a table in the database.
     *
     * @param string $OldName The name of the table to be renamed.
     * @param string $NewName The new name for the table being renamed.
     * @param boolean $UsePrefix A boolean value indicating if $this->_DatabasePrefix should be prefixed
     * before $OldName and $NewName.
     * @return boolean
     */
    public function renameTable($OldName, $NewName, $UsePrefix = false) {
        if (!$this->query('rename table `'.$OldName.'` to `'.$NewName.'`')) {
            throw new Exception(sprintf(t('Failed to rename table `%1$s` to `%2$s`.'), $OldName, $NewName));
        }

        return true;
    }

    /**
     * Specifies the name of the view to create or modify.
     *
     * @param string $Name The name of the view.
     * @param string $Query The actual query to create as the view. Typically
     * this can be generated with the $Database object.
     */
    public function view($Name, $SQL) {
        if (is_string($SQL)) {
            $SQLString = $SQL;
            $SQL = null;
        } else {
            $SQLString = $SQL->getSelect();
        }

        $Result = $this->query('create or replace view '.$this->_DatabasePrefix.$Name." as \n".$SQLString);
        if (!is_null($SQL)) {
            $SQL->reset();
        }
    }

    /**
     * Creates the table defined with $this->Table() and $this->Column().
     */
    protected function _create() {
        $PrimaryKey = array();
        $UniqueKey = array();
        $FullTextKey = array();
        $AllowFullText = true;
        $Indexes = array();
        $Keys = '';
        $Sql = '';

        $ForceDatabaseEngine = C('Database.ForceStorageEngine');
        if ($ForceDatabaseEngine && !$this->_TableStorageEngine) {
            $this->_TableStorageEngine = $ForceDatabaseEngine;
            $AllowFullText = $this->_supportsFulltext();
        }

        foreach ($this->_Columns as $ColumnName => $Column) {
            if ($Sql != '') {
                $Sql .= ',';
            }

            $Sql .= "\n".$this->_defineColumn($Column);

            $ColumnKeyTypes = (array)$Column->KeyType;

            foreach ($ColumnKeyTypes as $ColumnKeyType) {
                $KeyTypeParts = explode('.', $ColumnKeyType, 2);
                $ColumnKeyType = $KeyTypeParts[0];
                $IndexGroup = val(1, $KeyTypeParts, '');

                if ($ColumnKeyType == 'primary') {
                    $PrimaryKey[] = $ColumnName;
                } elseif ($ColumnKeyType == 'key')
                    $Indexes['FK'][$IndexGroup][] = $ColumnName;
                elseif ($ColumnKeyType == 'index')
                    $Indexes['IX'][$IndexGroup][] = $ColumnName;
                elseif ($ColumnKeyType == 'unique')
                    $UniqueKey[] = $ColumnName;
                elseif ($ColumnKeyType == 'fulltext' && $AllowFullText)
                    $FullTextKey[] = $ColumnName;
            }
        }
        // Build primary keys
        if (count($PrimaryKey) > 0) {
            $Keys .= ",\nprimary key (`".implode('`, `', $PrimaryKey)."`)";
        }
        // Build unique keys.
        if (count($UniqueKey) > 0) {
            $Keys .= ",\nunique index `".Gdn_Format::alphaNumeric('UX_'.$this->_TableName).'` (`'.implode('`, `', $UniqueKey)."`)";
        }
        // Build full text index.
        if (count($FullTextKey) > 0) {
            $Keys .= ",\nfulltext index `".Gdn_Format::alphaNumeric('TX_'.$this->_TableName).'` (`'.implode('`, `', $FullTextKey)."`)";
        }
        // Build the rest of the keys.
        foreach ($Indexes as $IndexType => $IndexGroups) {
            $CreateString = arrayValue($IndexType, array('FK' => 'key', 'IX' => 'index'));
            foreach ($IndexGroups as $IndexGroup => $ColumnNames) {
                if (!$IndexGroup) {
                    foreach ($ColumnNames as $ColumnName) {
                        $Keys .= ",\n{$CreateString} `{$IndexType}_{$this->_TableName}_{$ColumnName}` (`{$ColumnName}`)";
                    }
                } else {
                    $Keys .= ",\n{$CreateString} `{$IndexType}_{$this->_TableName}_{$IndexGroup}` (`".implode('`, `', $ColumnNames).'`)';
                }
            }
        }

        $Sql = 'create table `'.$this->_DatabasePrefix.$this->_TableName.'` ('
            .$Sql
            .$Keys
            ."\n)";

        // Check to see if there are any fulltext columns, otherwise use innodb.
        if (!$this->_TableStorageEngine) {
            $HasFulltext = false;
            foreach ($this->_Columns as $Column) {
                $ColumnKeyTypes = (array)$Column->KeyType;
                array_map('strtolower', $ColumnKeyTypes);
                if (in_array('fulltext', $ColumnKeyTypes)) {
                    $HasFulltext = true;
                    break;
                }
            }
            if ($HasFulltext) {
                $this->_TableStorageEngine = 'myisam';
            } else {
                $this->_TableStorageEngine = C('Database.DefaultStorageEngine', 'innodb');
            }

            if (!$this->hasEngine($this->_TableStorageEngine)) {
                $this->_TableStorageEngine = 'myisam';
            }
        }

        if ($this->_TableStorageEngine) {
            $Sql .= ' engine='.$this->_TableStorageEngine;
        }

        if ($this->_CharacterEncoding !== false && $this->_CharacterEncoding != '') {
            $Sql .= ' default character set '.$this->_CharacterEncoding;
        }

        if (array_key_exists('Collate', $this->Database->ExtendedProperties)) {
            $Sql .= ' collate '.$this->Database->ExtendedProperties['Collate'];
        }

        $Sql .= ';';

        $Result = $this->query($Sql);
        $this->reset();

        return $Result;
    }

    /**
     * Get the character set for a  collation.
     * @param string $collation The name of the collation.
     * @return string Returns the name of the character set or an empty string if the collation was not found.
     */
    protected function getCharsetFromCollation($collation) {
        static $cache = array();

        $collation = strtolower($collation);

        if (!isset($cache[$collation])) {
            $collationRow = $this->Database->query('show collation where Collation = :c', array(':c' => $collation))->firstRow(DATASET_TYPE_ARRAY);
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

        $result = ArrayTranslate($status, array('Engine' => 'engine', 'Rows' => 'rows', 'Collation' => 'collation'));

        // Look up the encoding for the collation.
        $result['charset'] = $this->getCharsetFromCollation($result['collation']);
        return $result;
    }

    /**
     *
     *
     * @param $Columns
     * @param bool $KeyType
     * @return array
     */
    protected function _indexSql($Columns, $KeyType = false) {
//      if ($this->TableName() != 'Comment')
//         return array();

        $Result = array();
        $Keys = array();
        $Prefixes = array('key' => 'FK_', 'index' => 'IX_', 'unique' => 'UX_', 'fulltext' => 'TX_');
        $Indexes = array();

        // Gather the names of the columns.
        foreach ($Columns as $ColumnName => $Column) {
            $ColumnKeyTypes = (array)$Column->KeyType;

            foreach ($ColumnKeyTypes as $ColumnKeyType) {
                $Parts = explode('.', $ColumnKeyType, 2);
                $ColumnKeyType = $Parts[0];
                $IndexGroup = val(1, $Parts, '');

                if (!$ColumnKeyType || ($KeyType && $KeyType != $ColumnKeyType)) {
                    continue;
                }

                // Don't add a fulltext if we don't support.
                if ($ColumnKeyType == 'fulltext' && !$this->_supportsFulltext()) {
                    continue;
                }

                $Indexes[$ColumnKeyType][$IndexGroup][] = $ColumnName;
            }
        }

        // Make the multi-column keys into sql statements.
        foreach ($Indexes as $ColumnKeyType => $IndexGroups) {
            $CreateType = arrayValue($ColumnKeyType, array('index' => 'index', 'key' => 'key', 'unique' => 'unique index', 'fulltext' => 'fulltext index', 'primary' => 'primary key'));

            if ($ColumnKeyType == 'primary') {
                $Result['PRIMARY'] = 'primary key (`'.implode('`, `', $IndexGroups['']).'`)';
            } else {
                foreach ($IndexGroups as $IndexGroup => $ColumnNames) {
                    $Multi = (strlen($IndexGroup) > 0 || in_array($ColumnKeyType, array('unique', 'fulltext')));

                    if ($Multi) {
                        $IndexName = "{$Prefixes[$ColumnKeyType]}{$this->_TableName}".($IndexGroup ? '_'.$IndexGroup : '');

                        $Result[$IndexName] = "$CreateType $IndexName (`".implode('`, `', $ColumnNames).'`)';
                    } else {
                        foreach ($ColumnNames as $ColumnName) {
                            $IndexName = "{$Prefixes[$ColumnKeyType]}{$this->_TableName}_$ColumnName";

                            $Result[$IndexName] = "$CreateType $IndexName (`$ColumnName`)";
                        }
                    }
                }
            }
        }

        return $Result;
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
        $Data = $this->Database->query('show indexes from '.$this->_DatabasePrefix.$this->_TableName);

        $Result = array();
        foreach ($Data as $Row) {
            if (array_key_exists($Row->Key_name, $Result)) {
                $Result[$Row->Key_name] .= ', `'.$Row->Column_name.'`';
            } else {
                switch (strtoupper(substr($Row->Key_name, 0, 2))) {
                    case 'PR':
                        $Type = 'primary key';
                        break;
                    case 'FK':
                        $Type = 'key '.$Row->Key_name;
                        break;
                    case 'IX':
                        $Type = 'index '.$Row->Key_name;
                        break;
                    case 'UX':
                        $Type = 'unique index '.$Row->Key_name;
                        break;
                    case 'TX':
                        $Type = 'fulltext index '.$Row->Key_name;
                        break;
                    default:
                        // Try and guess the index type.
                        if (strcasecmp($Row->Index_type, 'fulltext') == 0) {
                            $Type = 'fulltext index '.$Row->Key_name;
                        } elseif ($Row->Non_unique)
                            $Type = 'index '.$Row->Key_name;
                        else {
                            $Type = 'unique index '.$Row->Key_name;
                        }

                        break;
                }
                $Result[$Row->Key_name] = $Type.' (`'.$Row->Column_name.'`';
            }
        }

        // Cap off the sql.
        foreach ($Result as $Name => $Sql) {
            $Result[$Name] .= ')';
        }

        return $Result;
    }

    /**
     * Modifies $this->Table() with the columns specified with $this->Column().
     *
     * @param boolean $Explicit If TRUE, this method will remove any columns from the table that were not
     * defined with $this->Column().
     */
    protected function _modify($Explicit = false) {
        $Px = $this->_DatabasePrefix;
        $AdditionalSql = array(); // statements executed at the end
        $tableInfo = $this->getTableInfo($this->_TableName);

        // Returns an array of schema data objects for each field in the specified
        // table. The returned array of objects contains the following properties:
        // Name, PrimaryKey, Type, AllowNull, Default, Length, Enum.
        $ExistingColumns = $this->existingColumns();
        $AlterSql = array();

        // 1. Remove any unnecessary columns if this is an explicit modification
        if ($Explicit) {
            // array_diff returns values from the first array that aren't present
            // in the second array. In this example, all columns currently in the
            // table that are NOT in $this->_Columns.
            $RemoveColumns = array_diff(array_keys($ExistingColumns), array_keys($this->_Columns));
            foreach ($RemoveColumns as $Column) {
                $AlterSql[] = "drop column `$Column`";
            }
        }

        // Prepare the alter query
        $AlterSqlPrefix = 'alter table `'.$this->_DatabasePrefix.$this->_TableName."`\n";

        // 2. Alter the table storage engine.
        $ForceDatabaseEngine = c('Database.ForceStorageEngine');
        if ($ForceDatabaseEngine && !$this->_TableStorageEngine) {
            $this->_TableStorageEngine = $ForceDatabaseEngine;
        }
        $Indexes = $this->_indexSql($this->_Columns);
        $IndexesDb = $this->_indexSqlDb();

        if ($this->_TableStorageEngine) {
            $CurrentEngine = val('engine', $tableInfo);

            if (strcasecmp($CurrentEngine, $this->_TableStorageEngine)) {
                // Check to drop a fulltext index if we don't support it.
                if (!$this->_supportsFulltext()) {
                    foreach ($IndexesDb as $IndexName => $IndexSql) {
                        if (StringBeginsWith($IndexSql, 'fulltext', true)) {
                            $DropIndexQuery = "$AlterSqlPrefix drop index $IndexName;\n";
                            if (!$this->query($DropIndexQuery)) {
                                throw new Exception(sprintf(t('Failed to drop the index `%1$s` on table `%2$s`.'), $IndexName, $this->_TableName));
                            }
                        }
                    }
                }

                $EngineQuery = $AlterSqlPrefix.' engine = '.$this->_TableStorageEngine;
                if (!$this->query($EngineQuery)) {
                    throw new Exception(sprintf(t('Failed to alter the storage engine of table `%1$s` to `%2$s`.'), $this->_DatabasePrefix.$this->_TableName, $this->_TableStorageEngine));
                }
            }
        }

        // 3. Add new columns & modify existing ones

        // array_diff returns values from the first array that aren't present in
        // the second array. In this example, all columns in $this->_Columns that
        // are NOT in the table.
        $PrevColumnName = false;
        foreach ($this->_Columns as $ColumnName => $Column) {
            if (!array_key_exists($ColumnName, $ExistingColumns)) {
                // This column name is not in the existing column collection, so add the column
                $AddColumnSql = 'add '.$this->_defineColumn(val($ColumnName, $this->_Columns));
                if ($PrevColumnName !== false) {
                    $AddColumnSql .= " after `$PrevColumnName`";
                }

                $AlterSql[] = $AddColumnSql;

//            if (!$this->Query($AlterSqlPrefix.$AddColumnSql))
//               throw new Exception(sprintf(T('Failed to add the `%1$s` column to the `%1$s` table.'), $Column, $this->_DatabasePrefix.$this->_TableName));
            } else {
                $ExistingColumn = $ExistingColumns[$ColumnName];

                $ExistingColumnDef = $this->_defineColumn($ExistingColumn);
                $ColumnDef = $this->_defineColumn($Column);
                $Comment = "/* Existing: $ExistingColumnDef, New: $ColumnDef */\n";

                if ($ExistingColumnDef != $ColumnDef) {  //$Column->Type != $ExistingColumn->Type || $Column->AllowNull != $ExistingColumn->AllowNull || ($Column->Length != $ExistingColumn->Length && !in_array($Column->Type, array('tinyint', 'smallint', 'int', 'bigint', 'float', 'double')))) {
                    // The existing & new column types do not match, so modify the column.
                    $ChangeSql = $Comment.'change `'.$ColumnName.'` '.$this->_defineColumn(val($ColumnName, $this->_Columns));
                    $AlterSql[] = $ChangeSql;
//					if (!$this->Query($AlterSqlPrefix.$ChangeSql))
//						throw new Exception(sprintf(T('Failed to modify the data type of the `%1$s` column on the `%2$s` table.'),
//                     $ColumnName,
//                     $this->_DatabasePrefix.$this->_TableName));

                    // Check for a modification from an enum to an int.
                    if (strcasecmp($ExistingColumn->Type, 'enum') == 0 && in_array(strtolower($Column->Type), $this->types('int'))) {
                        $Sql = "update `$Px{$this->_TableName}` set `$ColumnName` = case `$ColumnName`";
                        foreach ($ExistingColumn->Enum as $Index => $NewValue) {
                            $OldValue = $Index + 1;

                            if (!is_numeric($NewValue)) {
                                continue;
                            }
                            $NewValue = (int)$NewValue;

                            $Sql .= " when $OldValue then $NewValue";
                        }
                        $Sql .= " else `$ColumnName` end";
                        $Description = "Update {$this->_TableName}.$ColumnName enum values to {$Column->Type}";
                        $AdditionalSql[$Description] = $Sql;

                    }
                }
            }
            $PrevColumnName = $ColumnName;
        }

        // 4. Alter the character set and collation.
        if ($this->_CharacterEncoding && (strcasecmp($tableInfo['charset'], $this->_CharacterEncoding)
                || strcasecmp($tableInfo['collation'], val('Collate', $this->Database->ExtendedProperties)))
        ) {
            $charset = $this->_CharacterEncoding;
            $collation = val('Collate', $this->Database->ExtendedProperties);

            $charsetSql = "character set $charset".($collation ? " collate $collation" : '');
            $AlterSql[] = $charsetSql;
            $AlterSql[] = "convert to $charsetSql";
        }

        if (count($AlterSql) > 0) {
            if (!$this->query($AlterSqlPrefix.implode(",\n", $AlterSql))) {
                throw new Exception(sprintf(T('Failed to alter the `%s` table.'), $this->_DatabasePrefix.$this->_TableName));
            }
        }

        // 5. Update Indexes.
        $IndexSql = array();
        // Go through the indexes to add or modify.
        foreach ($Indexes as $Name => $Sql) {
            if (array_key_exists($Name, $IndexesDb)) {
                if ($Indexes[$Name] != $IndexesDb[$Name]) {
//               $IndexSql[$Name][] = "/* '{$IndexesDb[$Name]}' => '{$Indexes[$Name]}' */\n";
                    if ($Name == 'PRIMARY') {
                        $IndexSql[$Name][] = $AlterSqlPrefix."drop primary key;\n";
                    } else {
                        $IndexSql[$Name][] = $AlterSqlPrefix.'drop index '.$Name.";\n";
                    }
                    $IndexSql[$Name][] = $AlterSqlPrefix."add $Sql;\n";
                }
                unset($IndexesDb[$Name]);
            } else {
                $IndexSql[$Name][] = $AlterSqlPrefix."add $Sql;\n";
            }
        }
        // Go through the indexes to drop.
        if ($Explicit) {
            foreach ($IndexesDb as $Name => $Sql) {
                if ($Name == 'PRIMARY') {
                    $IndexSql[$Name][] = $AlterSqlPrefix."drop primary key;\n";
                } else {
                    $IndexSql[$Name][] = $AlterSqlPrefix.'drop index '.$Name.";\n";
                }
            }
        }

        // Modify all of the indexes.
        foreach ($IndexSql as $Name => $Sqls) {
            foreach ($Sqls as $Sql) {
                if (!$this->query($Sql)) {
                    throw new Exception(sprintf(T('Error.ModifyIndex', 'Failed to add or modify the `%1$s` index in the `%2$s` table.'), $Name, $this->_TableName));
                }
            }
        }

        // Run any additional Sql.
        foreach ($AdditionalSql as $Description => $Sql) {
            if (!$this->query($Sql)) {
                throw new Exception("Error modifying table: {$Description}.");
            }
        }

        $this->reset();
        return true;
    }

    /**
     *
     *
     * @param string $Column
     */
    protected function _defineColumn($Column) {
        $ValidColumnTypes = array(
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
            'bit',
        );

        if (!is_array($Column->Type) && !in_array(strtolower($Column->Type), $ValidColumnTypes)) {
            throw new Exception(sprintf(t('The specified data type (%1$s) is not accepted for the MySQL database.'), $Column->Type));
        }

        $Return = '`'.$Column->Name.'` '.$Column->Type;

        $LengthTypes = $this->types('length');
        if ($Column->Length != '' && in_array(strtolower($Column->Type), $LengthTypes)) {
            if ($Column->Precision != '') {
                $Return .= '('.$Column->Length.', '.$Column->Precision.')';
            } else {
                $Return .= '('.$Column->Length.')';
            }
        }
        if (property_exists($Column, 'Unsigned') && $Column->Unsigned) {
            $Return .= ' unsigned';
        }

        if (is_array($Column->Enum)) {
            $Return .= "('".implode("','", $Column->Enum)."')";
        }

        if (!$Column->AllowNull) {
            $Return .= ' not null';
        } else {
            $Return .= ' null';
        }

        if (!(is_null($Column->Default)) && strcasecmp($Column->Type, 'timestamp') != 0) {
            $Return .= " default ".self::_quoteValue($Column->Default);
        }

        if ($Column->AutoIncrement) {
            $Return .= ' auto_increment';
        }

        return $Return;
    }

    /**
     *
     *
     * @param $Value
     * @return string
     */
    protected static function _quoteValue($Value) {
        if (is_numeric($Value)) {
            return $Value;
        } elseif (is_bool($Value)) {
            return $Value ? '1' : '0';
        } else {
            return "'".str_replace("'", "''", $Value)."'";
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
