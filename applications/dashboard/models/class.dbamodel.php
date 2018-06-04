<?php
/**
 * Contains useful functions for cleaning up the database.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 */

/**
 * Database Administration task handler.
 */
class DBAModel extends Gdn_Model {

    /** @var int Operations to perform at once. */
    public static $ChunkSize = 10000;

    /**
     * Update the counters.
     *
     * @param $table
     * @param $column
     * @param bool $from
     * @param bool $to
     * @return mixed
     * @throws Gdn_UserException
     */
    public function counts($table, $column, $from = false, $to = false) {
        $model = $this->createModel($table);

        if (!method_exists($model, 'Counts')) {
            throw new Gdn_UserException("The $table model does not support count recalculation.");
        }

        $result = $model->counts($column, $from, $to);
        return $result;
    }

    /**
     * Create a model for the given table.
     *
     * @param string $table
     * @return Gdn_Model
     */
    public function createModel($table) {
        $modelName = $table.'Model';
        if (class_exists($modelName)) {
            return new $modelName();
        } else {
            return new Gdn_Model($table);
        }
    }

    /**
     * Return SQL for updating a count.
     *
     * @param string $aggregate count, max, min, etc.
     * @param string $parentTable The name of the parent table.
     * @param string $childTable The name of the child table
     * @param string $parentColumnName
     * @param string $childColumnName
     * @param string $parentJoinColumn
     * @param string $childJoinColumn
     * @param int|string $default A default value for the field. Passed to MySQL's coalesce function.
     * @return string
     */
    public static function getCountSQL(
        $aggregate,
        // count, max, min, etc.
        $parentTable,
        $childTable,
        $parentColumnName = '',
        $childColumnName = '',
        $parentJoinColumn = '',
        $childJoinColumn = '',
        $where = [],
        $default = 0
    ) {

        $pDO = Gdn::database()->connection();
        $default = $pDO->quote($default);

        if (!$parentColumnName) {
            switch (strtolower($aggregate)) {
                case 'count':
                    $parentColumnName = "Count{$childTable}s";
                    break;
                case 'max':
                    $parentColumnName = "Last{$childTable}ID";
                    break;
                case 'min':
                    $parentColumnName = "First{$childTable}ID";
                    break;
                case 'sum':
                    $parentColumnName = "Sum{$childTable}s";
                    break;
            }
        }

        if (!$childColumnName) {
            $childColumnName = $childTable.'ID';
        }

        if (!$parentJoinColumn) {
            $parentJoinColumn = $parentTable.'ID';
        }
        if (!$childJoinColumn) {
            $childJoinColumn = $parentJoinColumn;
        }

        $result = "update :_$parentTable p
                  set p.$parentColumnName = (
                     select coalesce($aggregate(c.$childColumnName), $default)
                     from :_$childTable c
                     where p.$parentJoinColumn = c.$childJoinColumn)";

        if (!empty($where)) {
            $wheres = [];
            foreach ($where as $column => $value) {
                $value = $pDO->quote($value);
                $wheres[] = "p.`$column` = $value";
            }

            $result .= "\n where ".implode(" and ", $wheres);
        }

        $result = str_replace(':_', Gdn::database()->DatabasePrefix, $result);
        return $result;
    }

    /**
     * Remove html entities from a column in the database.
     *
     * @param string $table The name of the table.
     * @param array $column The column to decode.
     * @param int $limit The number of records to work on.
     */
    public function htmlEntityDecode($table, $column, $limit = 100) {
        // Construct a model to save the results.
        $model = $this->createModel($table);

        // Get the data to decode.
        $data = $this->SQL
            ->select($model->PrimaryKey)
            ->select($column)
            ->from($table)
            ->like($column, '&%;', 'both')
            ->limit($limit)
            ->get()->resultArray();

        $result = [];
        $result['Count'] = count($data);
        $result['Complete'] = false;
        $result['Decoded'] = [];
        $result['NotDecoded'] = [];

        // Loop through each row in the working set and decode the values.
        foreach ($data as $row) {
            $value = $row[$column];
            $decodedValue = htmlEntityDecode($value);

            $item = ['From' => $value, 'To' => $decodedValue];

            if ($value != $decodedValue) {
                $model->setField($row[$model->PrimaryKey], $column, $decodedValue);
                $result['Decoded'] = $item;
            } else {
                $result['NotDecoded'] = $item;
            }
        }
        $result['Complete'] = $result['Count'] < $limit;

        return $result;
    }

    /**
     * Updates a table's InsertUserID values to the system user ID, when invalid.
     *
     * @param $table The name of table to fix InsertUserID in.
     * @return bool|Gdn_DataSet|string
     * @throws Exception
     */
    public function fixInsertUserID($table) {
        return $this->SQL
            ->update($table)
            ->set('InsertUserID', Gdn::userModel()->getSystemUserID())
            ->where('InsertUserID <', 1)
            ->put();
    }

    /**
     * If any role has no permission records, set Member-like permissions on it.
     *
     * @return array
     */
    public function fixPermissions() {
        $roles = RoleModel::roles();
        $roleModel = new RoleModel();
        $permissionModel = new PermissionModel();

        // Find roles missing permission records
        foreach ($roles as $roleID => $role) {
            $permissions = $this->SQL->select('*')->from('Permission p')
                ->where('p.RoleID', $roleID)->get()->resultArray();

            if (!count($permissions)) {
                // Set basic permission record
                $defaultRecord = [
                    'RoleID' => $roleID,
                    'JunctionTable' => null,
                    'JunctionColumn' => null,
                    'JunctionID' => null,
                    'Garden.Email.View' => 1,
                    'Garden.SignIn.Allow' => 1,
                    'Garden.Activity.View' => 1,
                    'Garden.Profiles.View' => 1,
                    'Garden.Profiles.Edit' => 1,
                    'Conversations.Conversations.Add' => 1
                ];
                $permissionModel->save($defaultRecord);

                // Set default category permission
                $defaultCategory = [
                    'RoleID' => $roleID,
                    'JunctionTable' => 'Category',
                    'JunctionColumn' => 'PermissionCategoryID',
                    'JunctionID' => -1,
                    'Vanilla.Discussions.View' => 1,
                    'Vanilla.Discussions.Add' => 1,
                    'Vanilla.Comments.Add' => 1
                ];
                $permissionModel->save($defaultCategory);
            }
        }

        return ['Complete' => true];
    }

    public function fixUrlCodes($table, $column) {
        $model = $this->createModel($table);

        // Get the data to decode.
        $data = $this->SQL
            ->select($model->PrimaryKey)
            ->select($column)
            ->from($table)
//         ->like($Column, '&%;', 'both')
//         ->limit($Limit)
            ->get()->resultArray();

        foreach ($data as $row) {
            $value = $row[$column];
            $encoded = Gdn_Format::url($value);

            if (!$value || $value != $encoded) {
                $model->setField($row[$model->PrimaryKey], $column, $encoded);
                Gdn::controller()->Data['Encoded'][$row[$model->PrimaryKey]] = $encoded;
            }
        }

        return ['Complete' => true];
    }

    /**
     * Apply the specified RoleID to all users without a valid role
     *
     * @param $roleID
     * @return bool|Gdn_DataSet|string
     * @throws Exception
     */
    public function fixUserRole($roleID) {
        $pDO = Gdn::database()->connection();
        $insertQuery = "
         insert into :_UserRole

         select u.UserID, ".$pDO->quote($roleID)." as RoleID
         from :_User u
            left join :_UserRole ur on u.UserID = ur.UserID
            left join :_Role r on ur.RoleID = r.RoleID
         where r.Name is null";
        $insertQuery = str_replace(':_', Gdn::database()->DatabasePrefix, $insertQuery);
        return $this->SQL->query($insertQuery);
    }

    /**
     *
     *
     * @param $table
     * @param $key
     */
    public function resetBatch($table, $key) {
        $key = "DBA.Range.$key";
        Gdn::set($key, null);
    }

    /**
     *
     *
     * @param $table
     * @param $key
     * @param int $limit
     * @param bool $max
     * @return array|mixed
     */
    public function getBatch($table, $key, $limit = 10000, $max = false) {
        $key = "DBA.Range.$key";

        // See if there is already a range.
        $current = dbdecode(Gdn::get($key, ''));
        if (!is_array($current) || !isset($current['Min']) || !isset($current['Max'])) {
            list($current['Min'], $current['Max']) = $this->primaryKeyRange($table);

            if ($max && $current['Max'] > $max) {
                $current['Max'] = $max;
            }
        }

        if (!isset($current['To'])) {
            $current['To'] = $current['Max'];
        } else {
            $current['To'] -= $limit - 1;
        }
        $current['From'] = $current['To'] - $limit;
        Gdn::set($key, dbencode($current));
        $current['Complete'] = $current['To'] < $current['Min'];

        $total = $current['Max'] - $current['Min'];
        if ($total > 0) {
            $complete = $current['Max'] - $current['From'];

            $percent = 100 * $complete / $total;
            if ($percent > 100) {
                $percent = 100;
            }
            $current['Percent'] = round($percent).'%';
        }

        return $current;
    }

    /**
     * Return the min and max values of a table's primary key.
     *
     * @param string $table The name of the table to look at.
     * @return array An array in the form (min, max).
     */
    public function primaryKeyRange($table) {
        $model = $this->createModel($table);

        $data = $this->SQL
            ->select($model->PrimaryKey, 'min', 'MinValue')
            ->select($model->PrimaryKey, 'max', 'MaxValue')
            ->from($table)
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        if ($data) {
            return [$data['MinValue'], $data['MaxValue']];
        } else {
            return [0, 0];
        }
    }

    /**
     * Perform basic validation on a database identifier name.
     *
     * @link https://dev.mysql.com/doc/refman/5.6/en/identifiers.html Identifier name specification.
     * @param string $string A value to be used as a database identifier.
     * @return bool True if valid, otherwise false.
     */
    public function isValidDatabaseIdentifier($string) {
        // Sticking to ASCII.
        $result = (bool)preg_match('/^(?![0-9]+$)[0-9a-zA-Z$_]+$/', $string);
        return $result;
    }
}
