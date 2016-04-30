<?php
/**
 * Contains useful functions for cleaning up the database.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 */

/**
 * Handles additional logging.
 */
class LogModel extends Gdn_Pluggable {

    private static $instance = null;
    private $recalcIDs = ['Discussion' => []];
    private static $transactionID = null;

    /**
     * Begin a log transaction.
     */
    public static function beginTransaction() {
        self::$transactionID = true;
    }

    /**
     * Purge entries from the log.
     *
     * @param int[]|string $LogIDs An array or CSV of log IDs.
     */
    public function delete($LogIDs) {
        if (!is_array($LogIDs)) {
            $LogIDs = explode(',', $LogIDs);
        }

        // Get the log entries.
        $Logs = $this->getIDs($LogIDs);
        $Models = [];
        $Models['Discussion'] = new DiscussionModel();
        $Models['Comment'] = new CommentModel();

        foreach ($Logs as $Log) {
            if (in_array($Log['Operation'], ['Spam', 'Moderate']) && array_key_exists($Log['RecordType'], $Models)) {
                // Also delete the record.
                $Model = $Models[$Log['RecordType']];
                $Model->deleteID($Log['RecordID'], ['Log' => false]);
            }
        }

        Gdn::sql()->whereIn('LogID', $LogIDs)->delete('Log');
    }

    /**
     * End a log transaction.
     */
    public static function endTransaction() {
        self::$transactionID = null;
    }

    /**
     * Format the content of a log file.
     *
     * @param array $Log The log entry to format.
     * @return string Returns the formatted log entry.
     */
    public function formatContent($Log) {
        $Data = $Log['Data'];

        // TODO: Check for a custom log type handler.

        switch ($Log['RecordType']) {
            case 'Activity':
                $Result = $this->formatKey('Story', $Data);
                break;
            case 'Discussion':
                $Result =
                    '<b>'.$this->formatKey('Name', $Data).'</b><br />'.
                    $this->formatKey('Body', $Data);
                break;
            case 'ActivityComment':
            case 'Comment':
                $Result = $this->formatKey('Body', $Data);
                break;
            case 'Configuration':
                $Result = $this->formatConfiguration($Data);
                break;
            case 'Registration':
            case 'User':
                $Result = $this->formatRecord(['Email', 'Name'], $Data);
                if ($DiscoveryText = val('DiscoveryText', $Data)) {
                    $Result .= '<br /><b>'.t('Why do you want to join?').'</b><br />'.Gdn_Format::display($DiscoveryText);
                }
                if (val('Banned', $Data)) {
                    $Result .= "<br />".t('Banned');
                }
                break;
            default:
                $Result = '';
        }
        return $Result;
    }

    /**
     * Format a configuration subtree.
     *
     * @param array $Data The data to format.
     * @return string Returns the formatted entry.
     */
    public function formatConfiguration($Data) {
        $Old = $Data;
        $New = $Data['_New'];
        unset($Old['_New']);

        $Old = Gdn_Configuration::format($Old);
        $New = Gdn_Configuration::format($New);
        $Diffs = $this->formatDiff($Old, $New, 'raw');

        $Result = [];
        foreach ($Diffs as $Diff) {
            if (is_array($Diff)) {
                if (!empty($Diff['del'])) {
                    $Result[] = '<del>'.implode("<br />\n", $Diff['del']).'</del>';
                }
                if (!empty($Diff['ins'])) {
                    $Result[] = '<ins>'.implode("<br />\n", $Diff['ins']).'</ins>';
                }
            }
        }

        $Result = implode("<br />\n", $Result);
        if ($Result) {
            return $Result;
        } else {
            return t('No Change');
        }
    }

    /**
     * Format a specific column from the log.
     *
     * @param string $Key The key in the log row to format.
     * @param array $Data The log row.
     * @return string Returns the formatted entry.
     */
    public function formatKey($Key, $Data) {
        if (!is_array($Data)) {
            $Data = (array)$Data;
        }
        if (isset($Data['_New'][$Key])) {
            $Old = htmlspecialchars(val($Key, $Data, ''));
            $New = htmlspecialchars($Data['_New'][$Key]);
            $Result = $this->formatDiff($Old, $New);
        } else {
            $Result = htmlspecialchars(val($Key, $Data, ''));
        }
        return nl2br(trim(($Result)));
    }

    /**
     * Format a record that the log points to.
     *
     * @param string[] $Keys The keys to use from the record.
     * @param array $Data The log row.
     * @return string Returns the formatted record.
     */
    public function formatRecord($Keys, $Data) {
        $Result = [];
        foreach ($Keys as $Index => $Key) {
            if (is_numeric($Index)) {
                $Index = $Key;
            }

            if (!val($Index, $Data)) {
                continue;
            }
            $Result[] = '<b>'.htmlspecialchars($Key).'</b>: '.htmlspecialchars(val($Index, $Data));
        }
        $Result = implode('<br />', $Result);
        return $Result;
    }

    /**
     * Format a diff of an edit.
     *
     * @param string $Old The record before the edit.
     * @param string $New The record after the edit.
     * @param string $Method Either **normal**, **html**, or **mixed**.
     * @return string|array Returns the diff formatted according to {@link $Method}.
     */
    public function formatDiff($Old, $New, $Method = 'html') {
        static $TinyDiff = null;

        if ($TinyDiff === null) {
            require_once(dirname(__FILE__).'/tiny_diff.php');
            $TinyDiff = new Tiny_diff();
        }

        $Result = $TinyDiff->compare($Old, $New, $Method);
        return $Result;
    }

    /**
     * Get the log rows by array of IDs.
     *
     * @param int[]|string $IDs And array or CSV of IDs.
     * @return array Returns an array of log rows.
     */
    public function getIDs($IDs) {
        if (is_string($IDs)) {
            $IDs = explode(',', $IDs);
        }

        $Logs = Gdn::sql()
            ->select('*')
            ->from('Log')
            ->whereIn('LogID', $IDs)
            ->get()->resultArray();
        foreach ($Logs as &$Log) {
            $Log['Data'] = dbdecode($Log['Data']);
            if (!is_array($Log['Data'])) {
                $Log['Data'] = [];
            }
        }

        return $Logs;
    }

    /**
     * Get log rows by a query.
     *
     * @param array|false $Where The where filter.
     * @param string $OrderFields The fields to order by.
     * @param string $OrderDirection The order direction.
     * @param bool $Offset The database offset.
     * @param bool $Limit The database limit.
     * @return array Returns a data set.
     */
    public function getWhere($Where = false, $OrderFields = '', $OrderDirection = 'asc', $Offset = false, $Limit = false) {
        if ($Offset < 0) {
            $Offset = 0;
        }

        if (isset($Where['Operation'])) {
            Gdn::sql()->whereIn('Operation', (array)$Where['Operation']);
            unset($Where['Operation']);
        }

        $Result = Gdn::sql()
            ->select('l.*')
            ->select('ru.Name as RecordName, iu.Name as InsertName')
            ->from('Log l')
            ->join('User ru', 'l.RecordUserID = ru.UserID', 'left')
            ->join('User iu', 'l.InsertUserID = iu.UserID', 'left')
            ->where($Where)
            ->limit($Limit, $Offset)
            ->orderBy($OrderFields, $OrderDirection)
            ->get()->resultArray();

        // Deserialize the data.
        foreach ($Result as &$Row) {
            $Row['Data'] = dbdecode($Row['Data']);
            if (!$Row['Data']) {
                $Row['Data'] = [];
            }
        }

        return $Result;
    }

    /**
     * Get the count of log entries matching a query.
     *
     * @param array $Where The filter.
     * @return int Returns the count.
     */
    public function getCountWhere($Where) {
        if (isset($Where['Operation'])) {
            Gdn::sql()->whereIn('Operation', (array)$Where['Operation']);
            unset($Where['Operation']);
        }

        $Result = Gdn::sql()
            ->select('l.LogID', 'count', 'CountLogID')
            ->from('Log l')
            ->where($Where)
            ->get()->value('CountLogID', 0);

        return $Result;
    }

    /**
     * A wrapper for GetCountWhere that takes care of caching specific operation counts.
     *
     * @param string $Operation Comma-delimited list of operation types to get (sum of) counts for.
     * @return int Returns a count.
     */
    public function getOperationCount($Operation) {
        if ($Operation == 'edits') {
            $Operation = ['edit', 'delete'];
        } else {
            $Operation = explode(',', $Operation);
        }

        sort($Operation);
        array_map('ucfirst', $Operation);
        $CacheKey = 'Moderation.LogCount.'.implode('.', $Operation);
        $Count = Gdn::cache()->get($CacheKey);
        if ($Count === Gdn_Cache::CACHEOP_FAILURE) {
            $Count = $this->getCountWhere(['Operation' => $Operation]);
            Gdn::cache()->store($CacheKey, $Count, [
                Gdn_Cache::FEATURE_EXPIRY => 300 // 5 minutes
            ]);
        }
        return $Count;
    }


    /**
     * Log an operation into the log table.
     *
     * @param string $Operation The operation being performed. This is usually one of:
     *  - Delete: The record has been deleted.
     *  - Edit: The record has been edited.
     *  - Spam: The record has been marked spam.
     *  - Moderate: The record requires moderation.
     *  - Pending: The record needs pre-moderation.
     * @param string $RecordType The type of record being logged. This usually correspond to the tablename of the record.
     * @param array $Data The record data.
     *  - If you are logging just one row then pass the row as an array.
     *  - You can pass an additional _New element to tell the logger what the new data is.
     * @param array $Options Additional options to affect the insert.
     * @return int|false The log ID or **false** if there was a problem.
     */
    public static function insert($Operation, $RecordType, $Data, $Options = []) {
        if ($Operation === false) {
            return false;
        }

        if (!is_array($Data)) {
            $Data = [$Data];
        }

        // Check to see if we are storing two versions of the data.
        if (($InsertUserID = self::logValue($Data, 'Log_InsertUserID')) === null) {
            $InsertUserID = Gdn::session()->UserID;
        }
        if (($InsertIPAddress = self::logValue($Data, 'Log_InsertIPAddress')) == null) {
            $InsertIPAddress = Gdn::request()->ipAddress();
        }
        // Do some known translations for the parent record ID.
        if (($ParentRecordID = self::logValue($Data, 'ParentRecordID')) === null) {
            switch ($RecordType) {
                case 'Activity':
                    $ParentRecordID = self::logValue($Data, 'CommentActivityID', 'CommentActivityID');
                    break;
                case 'Comment':
                    $ParentRecordID = self::logValue($Data, 'DiscussionID', 'DiscussionID');
                    break;
            }
        }

        // Get the row information from the data or determine it based on the type.
        $LogRow = [
            'Operation' => $Operation,
            'RecordType' => $RecordType,
            'RecordID' => self::logValue($Data, 'RecordID', $RecordType.'ID'),
            'RecordUserID' => self::logValue($Data, 'RecordUserID', 'UpdateUserID', 'InsertUserID'),
            'RecordIPAddress' => self::logValue($Data, 'RecordIPAddress', 'LastIPAddress', 'InsertIPAddress'),
            'RecordDate' => self::logValue($Data, 'RecordDate', 'DateUpdated', 'DateInserted'),
            'InsertUserID' => $InsertUserID,
            'InsertIPAddress' => $InsertIPAddress,
            'DateInserted' => Gdn_Format::toDateTime(),
            'ParentRecordID' => $ParentRecordID,
            'CategoryID' => self::logValue($Data, 'CategoryID'),
            'OtherUserIDs' => implode(',', val('OtherUserIDs', $Options, [])),
            'Data' => dbencode($Data)
        ];
        if ($LogRow['RecordDate'] == null) {
            $LogRow['RecordDate'] = Gdn_Format::toDateTime();
        }

        $GroupBy = val('GroupBy', $Options);

        // Make sure we aren't grouping by null values.
        if (is_array($GroupBy)) {
            foreach ($GroupBy as $Name) {
                if (val($Name, $LogRow) === null) {
                    $GroupBy = false;
                    break;
                }
            }
        }

        if ($GroupBy) {
            $GroupBy[] = 'Operation';
            $GroupBy[] = 'RecordType';

            // Check to see if there is a record already logged here.
            $Where = array_combine($GroupBy, arrayTranslate($LogRow, $GroupBy));
            $LogRow2 = Gdn::sql()->getWhere('Log', $Where)->firstRow(DATASET_TYPE_ARRAY);
            if ($LogRow2) {
                $LogID = $LogRow2['LogID'];
                $Set = [];

                $Data = array_merge(dbdecode($LogRow2['Data']), $Data);

                $OtherUserIDs = explode(',', $LogRow2['OtherUserIDs']);
                if (!is_array($OtherUserIDs)) {
                    $OtherUserIDs = [];
                }

                if (!$LogRow2['InsertUserID']) {
                    $Set['InsertUserID'] = $InsertUserID;
                } elseif ($InsertUserID != $LogRow2['InsertUserID'] && !in_array($InsertUserID, $OtherUserIDs)) {
                    $OtherUserIDs[] = $InsertUserID;
                }

                if (array_key_exists('OtherUserIDs', $Options)) {
                    $OtherUserIDs = array_merge($OtherUserIDs, $Options['OtherUserIDs']);
                    $OtherUserIDs = array_unique($OtherUserIDs);
                    $OtherUserIDs = array_diff($OtherUserIDs, [$InsertUserID]);

                    $Count = count($OtherUserIDs) + 1;
                } else {
                    $Count = (int)$LogRow2['CountGroup'] + 1;
                }
                $Set['OtherUserIDs'] = implode(',', $OtherUserIDs);
                $Set['CountGroup'] = $Count;
                $Set['Data'] = dbencode($Data);
                $Set['DateUpdated'] = Gdn_Format::toDateTime();

                if (self::$transactionID > 0) {
                    $Set['TransactionLogID'] = self::$transactionID;
                } elseif (self::$transactionID === true) {
                    if ($LogRow2['TransactionLogID']) {
                        self::$transactionID = $LogRow2['TransactionLogID'];
                    } else {
                        self::$transactionID = $LogID;
                        $Set['TransactionLogID'] = $LogID;
                    }
                }

                Gdn::sql()->put(
                    'Log',
                    $Set,
                    ['LogID' => $LogID]
                );
            } else {
                $L = self::instance();
                $L->EventArguments['Log'] =& $LogRow;
                $L->fireEvent('BeforeInsert');

                if (self::$transactionID > 0) {
                    $LogRow['TransactionLogID'] = self::$transactionID;
                }

                $LogID = Gdn::sql()->insert('Log', $LogRow);

                if (self::$transactionID === true) {
                    // A new transaction was started and needs to assigned.
                    self::$transactionID = $LogID;
                    Gdn::sql()->put('Log', ['TransactionLogID' => $LogID], ['LogID' => $LogID]);
                }

                $L->EventArguments['LogID'] = $LogID;
                $L->fireEvent('AfterInsert');
            }
        } else {
            if (self::$transactionID > 0) {
                $LogRow['TransactionLogID'] = self::$transactionID;
            }

            // Insert the log entry.
            $L = self::instance();
            $L->EventArguments['Log'] =& $LogRow;
            $L->fireEvent('BeforeInsert');

            $LogID = Gdn::sql()->insert('Log', $LogRow);

            if (self::$transactionID === true) {
                // A new transaction was started and needs to assigned.
                self::$transactionID = $LogID;
                Gdn::sql()->put('Log', ['TransactionLogID' => $LogID], ['LogID' => $LogID]);
            }

            $L->EventArguments['LogID'] = $LogID;
            $L->fireEvent('AfterInsert');
        }
        return $LogID;
    }

    /**
     * Returns the shared instance of this class.
     *
     * @return LogModel Returns the instance.
     */
    private static function instance() {
        if (!self::$instance) {
            self::$instance = new LogModel();
        }

        return self::$instance;
    }

    /**
     * Log a record edit.
     *
     * @param string $Operation The specific operation being logged.
     * @param string $RecordType The type of record. This matches the name of the record's table.
     * @param array $NewData The record after the edit.
     * @param array|null $OldData The record before the edit.
     */
    public static function logChange($Operation, $RecordType, $NewData, $OldData = null) {
        $RecordID = isset($NewData['RecordID']) ? $NewData['RecordID'] : val($RecordType.'ID', $NewData);

        // Grab the record from the DB.
        if ($OldData === null) {
            $OldData = Gdn::sql()->getWhere($RecordType, [$RecordType.'ID' => $RecordID])->resultArray();
        } elseif (!is_array($OldData)) {
            $OldData = [$OldData];
        }

        foreach ($OldData as $Row) {
            // Don't log the change if it's right after an insert.
            if (val('DateInserted', $Row) && (time() - Gdn_Format::toTimestamp(val('DateInserted', $Row))) < c('Garden.Log.FloodControl', 20) * 60) {
                continue;
            }

            setValue('_New', $Row, $NewData);
            self::insert($Operation, $RecordType, $Row);
        }
    }

    /**
     * Get a value from a log entry.
     *
     * @param array $Data The log row.
     * @param string $LogKey The key in the log row.
     * @param string $BakKey1 A key to look at if the first key isn't found.
     * @param string $BakKey2 A key to look at if the second key isn't found.
     * @return mixed Returns the value.
     */
    private static function logValue($Data, $LogKey, $BakKey1 = '', $BakKey2 = '') {
        $Data = (array)$Data;
        if (isset($Data[$LogKey]) && $LogKey != $BakKey1) {
            $Result = $Data[$LogKey];
            unset($Data[$LogKey]);
        } elseif (isset($Data['_New'][$BakKey1])) {
            $Result = $Data['_New'][$BakKey1];
        } elseif (isset($Data[$BakKey1]) && ($Data[$BakKey1] || !$BakKey2)) {
            $Result = $Data[$BakKey1];
        } elseif (isset($Data[$BakKey2])) {
            $Result = $Data[$BakKey2];
        } else {
            $Result = null;
        }

        return $Result;
    }

    /**
     * Recalculate a record after a log operation.
     */
    public function recalculate() {
        if ($DiscussionIDs = val('Discussion', $this->recalcIDs)) {
            $In = implode(',', array_keys($DiscussionIDs));

            if (!empty($In)) {
                $Px = Gdn::database()->DatabasePrefix;
                $Sql = "update {$Px}Discussion d set d.CountComments = (select coalesce(count(c.CommentID), 0) + 1 from {$Px}Comment c where c.DiscussionID = d.DiscussionID) where d.DiscussionID in ($In)";
                Gdn::database()->query($Sql);
                $this->recalcIDs['Discussion'] = [];
            }
        }

        if ($UserIDsComment = val('UserComment', $this->recalcIDs)) {
            $counts = $this->arrayFlipAndCombine($UserIDsComment);

            foreach ($counts as $key => $value) {
                Gdn::sql()
                    ->update('User')
                    ->set('CountComments', 'coalesce(CountComments, 0) + '.$key, false, false)
                    ->where('UserID', $value)
                    ->put();
            }
            $this->recalcIDs['UserComment'] = [];
        }

        if ($UserIDsDiscussion = val('UserDiscussion', $this->recalcIDs)) {
            $counts = $this->arrayFlipAndCombine($UserIDsDiscussion);

            foreach ($counts as $key => $value) {
                Gdn::sql()
                    ->update('User')
                    ->set('CountDiscussions', 'coalesce(CountDiscussions, 0) + '.$key, false, false)
                    ->where('UserID', $value)
                    ->put();
            }
            $this->recalcIDs['UserDiscussion'] = [];
        }
    }

    /**
     * Takes an array and returns a flip, making values the keys and making the keys values.
     *
     * In case of multiple values with the several occurrences, this reserves all original keys by
     * pushing them onto an array.
     *
     * @param array $array An array in the format {[id1] => count, [id2] => count }.
     * @return array|null A 2D array the format {[count] => [id1, id2]}
     */
    public function arrayFlipAndCombine($array) {
        if (!$array) {
            return null;
        }
        $uniqueValues = array_unique(array_values($array));
        $newArray = [];
        foreach ($uniqueValues as $uniqueValue) {
            $newArray[$uniqueValue] = [];
            foreach ($array as $key => $value) {
                if ($value == $uniqueValue) {
                    $newArray[$uniqueValue][] = $key;
                }
            }
        }
        return $newArray;
    }

    /**
     * Restore an entry from the log.
     *
     * @param array|int $Log The log row or the ID of the log row.
     * @param bool $DeleteLog Whether or not to delete the log row after restoring.
     * @throws Gdn_UserException Throws an exception if the log entry isn't found.
     */
    public function restore($Log, $DeleteLog = true) {
        if (is_numeric($Log)) {
            // Grab the log.
            $LogID = $Log;
            $Log = $this->getWhere(['LogID' => $LogID]);

            if (!$Log) {
                throw notFoundException('Log');
            }
            $Log = array_pop($Log);
        }

        $this->restoreOne($Log, $DeleteLog);
        // Check for a transaction.
        if ($TransactionID = $Log['TransactionLogID']) {
            $Logs = $this->getWhere(['TransactionLogID' => $TransactionID], '', 'asc', 0, 200);
            foreach ($Logs as $LogRow) {
                if ($LogRow['LogID'] == $Log['LogID']) {
                    continue;
                }

                $this->restoreOne($LogRow, $DeleteLog);
            }
        }
        // Check for child data.
        if (isset($Log['Data']['_Data'])) {
            $Data = $Log['Data']['_Data'];
            foreach ($Data as $RecordType => $Rows) {
                foreach ($Rows as $Row) {
                    $LogRow = array_merge($Log, ['RecordType' => $RecordType, 'Data' => $Row]);

                    if ($RecordType == 'Comment') {
                        $LogRow['ParentRecordID'] = $Row['DiscussionID'];
                    }

                    $this->restoreOne($LogRow, false);
                }
            }
        }
    }

    /**
     * Restores a single entry from the log.
     *
     * @param array $Log The log entry.
     * @param bool $DeleteLog Whether or not to delete the log entry after the restore.
     * @throws Exception Throws an exception if restoring the record causes a validation error.
     */
    private function restoreOne($Log, $DeleteLog = true) {
        // Throw an event to see if the restore is being overridden.
        $Handled = false;
        $this->EventArguments['Handled'] =& $Handled;
        $this->EventArguments['Log'] =& $Log;
        $this->fireEvent('BeforeRestore');
        if ($Handled) {
            return; // a plugin handled the restore.
        }
        if ($Log['RecordType'] == 'Configuration') {
            throw new Gdn_UserException('Restoring configuration edits is currently not supported.');
        }

        if ($Log['RecordType'] == 'Registration') {
            $TableName = 'User';
        } else {
            $TableName = $Log['RecordType'];
        }

        $Data = $Log['Data'];

        if (isset($Data['Attributes'])) {
            $Attr = 'Attributes';
        } elseif (isset($Data['Data'])) {
            $Attr = 'Data';
        } else {
            $Attr = '';
        }

        if ($Attr) {
            if (is_string($Data[$Attr])) {
                $Data[$Attr] = dbdecode($Data[$Attr]);
            }

            // Record a bit of information about the restoration.
            if (!is_array($Data[$Attr])) {
                $Data[$Attr] = [];
            }
            $Data[$Attr]['RestoreUserID'] = Gdn::session()->UserID;
            $Data[$Attr]['DateRestored'] = Gdn_Format::toDateTime();
        }

        if (!isset($Columns[$TableName])) {
            $Columns[$TableName] = Gdn::sql()->fetchColumns($TableName);
        }

        $Set = array_flip($Columns[$TableName]);
        // Set the sets from the data.
        foreach ($Set as $Key => $Value) {
            if (isset($Data[$Key])) {
                $Value = $Data[$Key];
                if (is_array($Value)) {
                    $Value = dbencode($Value);
                }
                $Set[$Key] = $Value;
            } else {
                unset($Set[$Key]);
            }
        }

        switch ($Log['Operation']) {
            case 'Edit':
                // We are restoring an edit so just update the record.
                $IDColumn = $Log['RecordType'].'ID';
                $Where = [$IDColumn => $Log['RecordID']];
                unset($Set[$IDColumn]);
                Gdn::sql()->put(
                    $TableName,
                    $Set,
                    $Where
                );

                break;
            case 'Delete':
            case 'Spam':
            case 'Moderate':
            case 'Pending':
            case 'Ban':
                if (!$Log['RecordID']) {
                    // This log entry was never in the table.
                    if (isset($Set['DateInserted'])) {
                        $Set['DateInserted'] = Gdn_Format::toDateTime();
                    }
                }

                // Insert the record back into the db.
                if ($Log['Operation'] == 'Spam' && $Log['RecordType'] == 'Registration') {
                    saveToConfig(['Garden.Registration.NameUnique' => false, 'Garden.Registration.EmailUnique' => false], '', false);
                    if (isset($Data['Username'])) {
                        $Set['Name'] = $Data['Username'];
                    }
                    $ID = Gdn::userModel()->insertForBasic($Set, false, ['ValidateSpam' => false]);
                    if (!$ID) {
                        throw new Exception(Gdn::userModel()->Validation->resultsText());
                    } else {
                        Gdn::userModel()->sendWelcomeEmail($ID, '', 'Register');
                    }
                } else {
                    $ID = Gdn::sql()
                        ->options('Replace', true)
                        ->insert($TableName, $Set);
                    if (!$ID && isset($Log['RecordID'])) {
                        $ID = $Log['RecordID'];
                    }

                    // Unban a user.
                    if ($Log['RecordType'] == 'User' && $Log['Operation'] == 'Ban') {
                        Gdn::userModel()->setField($ID, 'Banned', 0);
                    }

                    // Keep track of a discussion ID so that its count can be recalculated.
                    if ($Log['Operation'] != 'Edit') {
                        switch ($Log['RecordType']) {
                            case 'Discussion':
                                $this->recalcIDs['Discussion'][$ID] = true;
                                break;
                            case 'Comment':
                                $this->recalcIDs['Discussion'][$Log['ParentRecordID']] = true;
                                break;
                        }
                    }

                    if ($Log['Operation'] == 'Pending') {
                        switch ($Log['RecordType']) {
                            case 'Discussion':
                                if (val('UserDiscussion', $this->recalcIDs) && val($Log['RecordUserID'], $this->recalcIDs['UserDiscussion'])) {
                                    $this->recalcIDs['UserDiscussion'][$Log['RecordUserID']]++;
                                } else {
                                    $this->recalcIDs['UserDiscussion'][$Log['RecordUserID']] = 1;
                                }
                                break;
                            case 'Comment':
                                if (val('UserComment', $this->recalcIDs) && val($Log['RecordUserID'], $this->recalcIDs['UserComment'])) {
                                    $this->recalcIDs['UserComment'][$Log['RecordUserID']]++;
                                } else {
                                    $this->recalcIDs['UserComment'][$Log['RecordUserID']] = 1;
                                }
                                break;
                        }
                    }
                }

                break;
        }

        // Fire 'after' event
        if (isset($ID)) {
            $this->EventArguments['InsertID'] = $ID;
        }
        $this->fireEvent('AfterRestore');

        if ($DeleteLog) {
            Gdn::sql()->delete('Log', ['LogID' => $Log['LogID']]);
        }

    }
}
