<?php
/**
 * Contains useful functions for cleaning up the database.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     * @param $Table
     * @param $Column
     * @param bool $From
     * @param bool $To
     * @return mixed
     * @throws Gdn_UserException
     */
    public function counts($Table, $Column, $From = false, $To = false) {
        $Model = $this->createModel($Table);

        if (!method_exists($Model, 'Counts')) {
            throw new Gdn_UserException("The $Table model does not support count recalculation.");
        }

        $Result = $Model->counts($Column, $From, $To);
        return $Result;
    }

    /**
     * Create a model for the given table.
     *
     * @param string $Table
     * @return Gdn_Model
     */
    public function createModel($Table) {
        $ModelName = $Table.'Model';
        if (class_exists($ModelName)) {
            return new $ModelName();
        } else {
            return new Gdn_Model($Table);
        }
    }

    /**
     * Return SQL for updating a count.
     *
     * @param string $Aggregate count, max, min, etc.
     * @param string $ParentTable The name of the parent table.
     * @param string $ChildTable The name of the child table
     * @param string $ParentColumnName
     * @param string $ChildColumnName
     * @param string $ParentJoinColumn
     * @param string $ChildJoinColumn
     * @return type
     */
    public static function getCountSQL(
        $Aggregate,
        // count, max, min, etc.
        $ParentTable,
        $ChildTable,
        $ParentColumnName = '',
        $ChildColumnName = '',
        $ParentJoinColumn = '',
        $ChildJoinColumn = '',
        $Where = array()
    ) {

        if (!$ParentColumnName) {
            switch (strtolower($Aggregate)) {
                case 'count':
                    $ParentColumnName = "Count{$ChildTable}s";
                    break;
                case 'max':
                    $ParentColumnName = "Last{$ChildTable}ID";
                    break;
                case 'min':
                    $ParentColumnName = "First{$ChildTable}ID";
                    break;
                case 'sum':
                    $ParentColumnName = "Sum{$ChildTable}s";
                    break;
            }
        }

        if (!$ChildColumnName) {
            $ChildColumnName = $ChildTable.'ID';
        }

        if (!$ParentJoinColumn) {
            $ParentJoinColumn = $ParentTable.'ID';
        }
        if (!$ChildJoinColumn) {
            $ChildJoinColumn = $ParentJoinColumn;
        }

        $Result = "update :_$ParentTable p
                  set p.$ParentColumnName = (
                     select $Aggregate(c.$ChildColumnName)
                     from :_$ChildTable c
                     where p.$ParentJoinColumn = c.$ChildJoinColumn)";

        if (!empty($Where)) {
            $Wheres = array();
            $PDO = Gdn::database()->connection();
            foreach ($Where as $Column => $Value) {
                $Value = $PDO->quote($Value);
                $Wheres[] = "p.`$Column` = $Value";
            }

            $Result .= "\n where ".implode(" and ", $Wheres);
        }

        $Result = str_replace(':_', Gdn::database()->DatabasePrefix, $Result);
        return $Result;
    }

    /**
     * Remove html entities from a column in the database.
     *
     * @param string $Table The name of the table.
     * @param array $Column The column to decode.
     * @param int $Limit The number of records to work on.
     */
    public function htmlEntityDecode($Table, $Column, $Limit = 100) {
        // Construct a model to save the results.
        $Model = $this->createModel($Table);

        // Get the data to decode.
        $Data = $this->SQL
            ->select($Model->PrimaryKey)
            ->select($Column)
            ->from($Table)
            ->like($Column, '&%;', 'both')
            ->limit($Limit)
            ->get()->resultArray();

        $Result = array();
        $Result['Count'] = count($Data);
        $Result['Complete'] = false;
        $Result['Decoded'] = array();
        $Result['NotDecoded'] = array();

        // Loop through each row in the working set and decode the values.
        foreach ($Data as $Row) {
            $Value = $Row[$Column];
            $DecodedValue = HtmlEntityDecode($Value);

            $Item = array('From' => $Value, 'To' => $DecodedValue);

            if ($Value != $DecodedValue) {
                $Model->setField($Row[$Model->PrimaryKey], $Column, $DecodedValue);
                $Result['Decoded'] = $Item;
            } else {
                $Result['NotDecoded'] = $Item;
            }
        }
        $Result['Complete'] = $Result['Count'] < $Limit;

        return $Result;
    }

    /**
     * Updates a table's InsertUserID values to the system user ID, when invalid.
     *
     * @param $Table The name of table to fix InsertUserID in.
     * @return bool|Gdn_DataSet|string
     * @throws Exception
     */
    public function fixInsertUserID($Table) {
        return $this->SQL
            ->update($Table)
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
        $Roles = RoleModel::roles();
        $RoleModel = new RoleModel();
        $PermissionModel = new PermissionModel();

        // Find roles missing permission records
        foreach ($Roles as $RoleID => $Role) {
            $Permissions = $this->SQL->select('*')->from('Permission p')
                ->where('p.RoleID', $RoleID)->get()->resultArray();

            if (!count($Permissions)) {
                // Set basic permission record
                $DefaultRecord = array(
                    'RoleID' => $RoleID,
                    'JunctionTable' => null,
                    'JunctionColumn' => null,
                    'JunctionID' => null,
                    'Garden.Email.View' => 1,
                    'Garden.SignIn.Allow' => 1,
                    'Garden.Activity.View' => 1,
                    'Garden.Profiles.View' => 1,
                    'Garden.Profiles.Edit' => 1,
                    'Conversations.Conversations.Add' => 1
                );
                $PermissionModel->save($DefaultRecord);

                // Set default category permission
                $DefaultCategory = array(
                    'RoleID' => $RoleID,
                    'JunctionTable' => 'Category',
                    'JunctionColumn' => 'PermissionCategoryID',
                    'JunctionID' => -1,
                    'Vanilla.Discussions.View' => 1,
                    'Vanilla.Discussions.Add' => 1,
                    'Vanilla.Comments.Add' => 1
                );
                $PermissionModel->save($DefaultCategory);
            }
        }

        return array('Complete' => true);
    }

    public function fixUrlCodes($Table, $Column) {
        $Model = $this->createModel($Table);

        // Get the data to decode.
        $Data = $this->SQL
            ->select($Model->PrimaryKey)
            ->select($Column)
            ->from($Table)
//         ->like($Column, '&%;', 'both')
//         ->limit($Limit)
            ->get()->resultArray();

        foreach ($Data as $Row) {
            $Value = $Row[$Column];
            $Encoded = Gdn_Format::url($Value);

            if (!$Value || $Value != $Encoded) {
                $Model->setField($Row[$Model->PrimaryKey], $Column, $Encoded);
                Gdn::controller()->Data['Encoded'][$Row[$Model->PrimaryKey]] = $Encoded;
            }
        }

        return array('Complete' => true);
    }

    /**
     * Apply the specified RoleID to all users without a valid role
     *
     * @param $RoleID
     * @return bool|Gdn_DataSet|string
     * @throws Exception
     */
    public function fixUserRole($RoleID) {
        $PDO = Gdn::database()->connection();
        $InsertQuery = "
         insert into :_UserRole

         select u.UserID, ".$PDO->quote($RoleID)." as RoleID
         from :_User u
            left join :_UserRole ur on u.UserID = ur.UserID
            left join :_Role r on ur.RoleID = r.RoleID
         where r.Name is null";
        $InsertQuery = str_replace(':_', Gdn::database()->DatabasePrefix, $InsertQuery);
        return $this->SQL->query($InsertQuery);
    }

    /**
     *
     *
     * @param $Table
     * @param $Key
     */
    public function resetBatch($Table, $Key) {
        $Key = "DBA.Range.$Key";
        Gdn::set($Key, null);
    }

    /**
     *
     *
     * @param $Table
     * @param $Key
     * @param int $Limit
     * @param bool $Max
     * @return array|mixed
     */
    public function getBatch($Table, $Key, $Limit = 10000, $Max = false) {
        $Key = "DBA.Range.$Key";

        // See if there is already a range.
        $Current = @unserialize(Gdn::get($Key, ''));
        if (!is_array($Current) || !isset($Current['Min']) || !isset($Current['Max'])) {
            list($Current['Min'], $Current['Max']) = $this->primaryKeyRange($Table);

            if ($Max && $Current['Max'] > $Max) {
                $Current['Max'] = $Max;
            }
        }

        if (!isset($Current['To'])) {
            $Current['To'] = $Current['Max'];
        } else {
            $Current['To'] -= $Limit - 1;
        }
        $Current['From'] = $Current['To'] - $Limit;
        Gdn::set($Key, serialize($Current));
        $Current['Complete'] = $Current['To'] < $Current['Min'];

        $Total = $Current['Max'] - $Current['Min'];
        if ($Total > 0) {
            $Complete = $Current['Max'] - $Current['From'];

            $Percent = 100 * $Complete / $Total;
            if ($Percent > 100) {
                $Percent = 100;
            }
            $Current['Percent'] = round($Percent).'%';
        }

        return $Current;
    }

    /**
     * Return the min and max values of a table's primary key.
     *
     * @param string $Table The name of the table to look at.
     * @return array An array in the form (min, max).
     */
    public function primaryKeyRange($Table) {
        $Model = $this->createModel($Table);

        $Data = $this->SQL
            ->select($Model->PrimaryKey, 'min', 'MinValue')
            ->select($Model->PrimaryKey, 'max', 'MaxValue')
            ->from($Table)
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        if ($Data) {
            return array($Data['MinValue'], $Data['MaxValue']);
        } else {
            return array(0, 0);
        }
    }
}
