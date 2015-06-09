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
 * Handles additional logging.
 */
class LogModel extends Gdn_Pluggable {

    protected static $_Instance = null;
    protected $_RecalcIDs = array('Discussion' => array());
    protected static $_TransactionID = null;

    /**
     *
     */
    public static function beginTransaction() {
        self::$_TransactionID = true;
    }

    /**
     *
     *
     * @param $LogIDs
     */
    public function delete($LogIDs) {
        if (!is_array($LogIDs)) {
            $LogIDs = explode(',', $LogIDs);
        }

        // Get the log entries.
        $Logs = $this->getIDs($LogIDs);
        $Models = array();
        $Models['Discussion'] = new DiscussionModel();
        $Models['Comment'] = new CommentModel();

        foreach ($Logs as $Log) {
            if (in_array($Log['Operation'], array('Spam', 'Moderate')) && array_key_exists($Log['RecordType'], $Models)) {
                // Also delete the record.
                $Model = $Models[$Log['RecordType']];
                $Model->delete($Log['RecordID'], array('Log' => false));
            }
        }

        Gdn::sql()->whereIn('LogID', $LogIDs)->delete('Log');
    }

    /**
     *
     */
    public static function endTransaction() {
        self::$_TransactionID = null;
    }

    /**
     * Format the content of a log file.
     *
     * @param $Log
     * @return array|string
     */
    public function formatContent($Log) {
        $Data = $Log['Data'];

        // TODO: Check for a custom log type handler.

        switch ($Log['RecordType']) {
            case 'Activity':
                $Result = $this->FormatKey('Story', $Data);
                break;
            case 'Discussion':
                $Result =
                    '<b>'.$this->FormatKey('Name', $Data).'</b><br />'.
                    $this->FormatKey('Body', $Data);
                break;
            case 'ActivityComment':
            case 'Comment':
                $Result = $this->FormatKey('Body', $Data);
                break;
            case 'Configuration':
                $Result = $this->FormatConfiguration($Data);
                break;
            case 'Registration':
            case 'User':
                $Result = $this->FormatRecord(array('Email', 'Name'), $Data);
                if ($DiscoveryText = val('DiscoveryText', $Data)) {
                    $Result .= '<br /><b>'.t('Why do you want to join?').'</b><br />'.Gdn_Format::Display($DiscoveryText);
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
     *
     *
     * @param $Data
     * @return array|string
     */
    public function formatConfiguration($Data) {
        $Old = $Data;
        $New = $Data['_New'];
        unset($Old['_New']);

        $Old = Gdn_Configuration::format($Old);
        $New = Gdn_Configuration::format($New);
        $Diffs = $this->FormatDiff($Old, $New, 'raw');

        $Result = array();
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
     *
     *
     * @param $Key
     * @param $Data
     * @return string
     */
    public function formatKey($Key, $Data) {
        if (!is_array($Data)) {
            $Data = (array)$Data;
        }
        if (isset($Data['_New']) && isset($Data['_New'][$Key])) {
            $Old = htmlspecialchars(val($Key, $Data, ''));
            $New = htmlspecialchars($Data['_New'][$Key]);
            $Result = $this->FormatDiff($Old, $New);
        } else {
            $Result = htmlspecialchars(val($Key, $Data, ''));
        }
        return nl2br(trim(($Result)));
    }

    /**
     *
     *
     * @param $Keys
     * @param $Data
     * @return array|string
     */
    public function formatRecord($Keys, $Data) {
        $Result = array();
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
     *
     *
     * @param $Old
     * @param $New
     * @param string $Method
     * @return string
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
     *
     *
     * @param $IDs
     * @return array|null
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
            $Log['Data'] = @unserialize($Log['Data']);
            if (!is_array($Log['Data'])) {
                $Log['Data'] = array();
            }
        }

        return $Logs;
    }

    /**
     *
     *
     * @param bool $Where
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param bool $Offset
     * @param bool $Limit
     * @return array|null
     * @throws Exception
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
            $Row['Data'] = @unserialize($Row['Data']);
            if (!$Row['Data']) {
                $Row['Data'] = array();
            }
        }

        return $Result;
    }

    /**
     *
     *
     * @param $Where
     * @return mixed
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
     * Wrapper for GetCountWhere that takes care of caching specific operation counts.
     *
     * @param string $Operation Comma-delimited list of operation types to get (sum of) counts for.
     * @return int
     */
    public function getOperationCount($Operation) {
        if ($Operation == 'edits') {
            $Operation = array('edit', 'delete');
        } else {
            $Operation = explode(',', $Operation);
        }

        sort($Operation);
        array_map('ucfirst', $Operation);
        $CacheKey = 'Moderation.LogCount.'.implode('.', $Operation);
        $Count = Gdn::cache()->get($CacheKey);
        if ($Count === Gdn_Cache::CACHEOP_FAILURE) {
            $Count = $this->getCountWhere(array('Operation' => $Operation));
            Gdn::cache()->store($CacheKey, $Count, array(
                Gdn_Cache::FEATURE_EXPIRY => 300 // 5 minutes
            ));
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
     * @return int The log id.
     */
    public static function insert($Operation, $RecordType, $Data, $Options = array()) {
        if ($Operation === false) {
            return;
        }

        // Check to see if we are storing two versions of the data.
        if (($InsertUserID = self::_LogValue($Data, 'Log_InsertUserID')) === null) {
            $InsertUserID = Gdn::session()->UserID;
        }
        if (($InsertIPAddress = self::_LogValue($Data, 'Log_InsertIPAddress')) == null) {
            $InsertIPAddress = Gdn::request()->IPAddress();
        }
        // Do some known translations for the parent record ID.
        if (($ParentRecordID = self::_LogValue($Data, 'ParentRecordID')) === null) {
            switch ($RecordType) {
                case 'Activity':
                    $ParentRecordID = self::_LogValue($Data, 'CommentActivityID', 'CommentActivityID');
                    break;
                case 'Comment':
                    $ParentRecordID = self::_LogValue($Data, 'DiscussionID', 'DiscussionID');
                    break;
            }
        }

        // Get the row information from the data or determine it based on the type.
        $LogRow = array(
            'Operation' => $Operation,
            'RecordType' => $RecordType,
            'RecordID' => self::_LogValue($Data, 'RecordID', $RecordType.'ID'),
            'RecordUserID' => self::_LogValue($Data, 'RecordUserID', 'UpdateUserID', 'InsertUserID'),
            'RecordIPAddress' => self::_LogValue($Data, 'RecordIPAddress', 'LastIPAddress', 'InsertIPAddress'),
            'RecordDate' => self::_LogValue($Data, 'RecordDate', 'DateUpdated', 'DateInserted'),
            'InsertUserID' => $InsertUserID,
            'InsertIPAddress' => $InsertIPAddress,
            'DateInserted' => Gdn_Format::toDateTime(),
            'ParentRecordID' => $ParentRecordID,
            'CategoryID' => self::_LogValue($Data, 'CategoryID'),
            'OtherUserIDs' => implode(',', val('OtherUserIDs', $Options, array())),
            'Data' => serialize($Data)
        );
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
                $Set = array();

                $Data = array_merge(unserialize($LogRow2['Data']), $Data);

                $OtherUserIDs = explode(',', $LogRow2['OtherUserIDs']);
                if (!is_array($OtherUserIDs)) {
                    $OtherUserIDs = array();
                }

                if (!$LogRow2['InsertUserID']) {
                    $Set['InsertUserID'] = $InsertUserID;
                } elseif ($InsertUserID != $LogRow2['InsertUserID'] && !in_array($InsertUserID, $OtherUserIDs)) {
                    $OtherUserIDs[] = $InsertUserID;
                }

                if (array_key_exists('OtherUserIDs', $Options)) {
                    $OtherUserIDs = array_merge($OtherUserIDs, $Options['OtherUserIDs']);
                    $OtherUserIDs = array_unique($OtherUserIDs);
                    $OtherUserIDs = array_diff($OtherUserIDs, array($InsertUserID));

                    $Count = count($OtherUserIDs) + 1;
                } else {
                    $Count = (int)$LogRow2['CountGroup'] + 1;
                }
                $Set['OtherUserIDs'] = implode(',', $OtherUserIDs);
                $Set['CountGroup'] = $Count;
                $Set['Data'] = serialize($Data);
                $Set['DateUpdated'] = Gdn_Format::toDateTime();

                if (self::$_TransactionID > 0) {
                    $Set['TransactionLogID'] = self::$_TransactionID;
                } elseif (self::$_TransactionID === true) {
                    if ($LogRow2['TransactionLogID']) {
                        self::$_TransactionID = $LogRow2['TransactionLogID'];
                    } else {
                        self::$_TransactionID = $LogID;
                        $Set['TransactionLogID'] = $LogID;
                    }
                }

                Gdn::sql()->put(
                    'Log',
                    $Set,
                    array('LogID' => $LogID)
                );
            } else {
                $L = self::_Instance();
                $L->EventArguments['Log'] =& $LogRow;
                $L->fireEvent('BeforeInsert');

                if (self::$_TransactionID > 0) {
                    $LogRow['TransactionLogID'] = self::$_TransactionID;
                }

                $LogID = Gdn::sql()->insert('Log', $LogRow);

                if (self::$_TransactionID === true) {
                    // A new transaction was started and needs to assigned.
                    self::$_TransactionID = $LogID;
                    Gdn::sql()->put('Log', array('TransactionLogID' => $LogID), array('LogID' => $LogID));
                }

                $L->EventArguments['LogID'] = $LogID;
                $L->fireEvent('AfterInsert');
            }
        } else {
            if (self::$_TransactionID > 0) {
                $LogRow['TransactionLogID'] = self::$_TransactionID;
            }

            // Insert the log entry.
            $L = self::_Instance();
            $L->EventArguments['Log'] =& $LogRow;
            $L->fireEvent('BeforeInsert');

            $LogID = Gdn::sql()->insert('Log', $LogRow);

            if (self::$_TransactionID === true) {
                // A new transaction was started and needs to assigned.
                self::$_TransactionID = $LogID;
                Gdn::sql()->put('Log', array('TransactionLogID' => $LogID), array('LogID' => $LogID));
            }

            $L->EventArguments['LogID'] = $LogID;
            $L->fireEvent('AfterInsert');
        }
        return $LogID;
    }

    /**
     *
     *
     * @return LogModel
     */
    protected static function _Instance() {
        if (!self::$_Instance) {
            self::$_Instance = new LogModel();
        }

        return self::$_Instance;
    }

    /**
     *
     *
     * @param $Operation
     * @param $RecordType
     * @param $NewData
     * @param null $OldData
     */
    public static function logChange($Operation, $RecordType, $NewData, $OldData = null) {
        $RecordID = isset($NewData['RecordID']) ? $NewData['RecordID'] : val($RecordType.'ID', $NewData);

        // Grab the record from the DB.
        if ($OldData === null) {
            $OldData = Gdn::sql()->getWhere($RecordType, array($RecordType.'ID' => $RecordID))->resultArray();
        } elseif (!is_array($OldData))
            $OldData = array($OldData);

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
     *
     *
     * @param $Data
     * @param $LogKey
     * @param string $BakKey1
     * @param string $BakKey2
     * @return null
     */
    protected static function _LogValue($Data, $LogKey, $BakKey1 = '', $BakKey2 = '') {
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
     *
     *
     * @throws Exception
     */
    public function recalculate() {
        if ($DiscussionIDs = val('Discussion', $this->_RecalcIDs)) {
            $In = implode(',', array_keys($DiscussionIDs));

            if (!empty($In)) {
                $Px = Gdn::database()->DatabasePrefix;
                $Sql = "update {$Px}Discussion d set d.CountComments = (select coalesce(count(c.CommentID), 0) + 1 from {$Px}Comment c where c.DiscussionID = d.DiscussionID) where d.DiscussionID in ($In)";
                Gdn::database()->query($Sql);
                $this->_RecalcIDs['Discussion'] = array();
            }
        }

        if ($UserIDsComment = val('UserComment', $this->_RecalcIDs)) {
            $counts = $this->arrayFlipAndCombine($UserIDsComment);

            foreach ($counts as $key => $value) {
                Gdn::sql()
                    ->update('User')
                    ->set('CountComments', 'coalesce(CountComments, 0) + '.$key, false, false)
                    ->where('UserID', $value)
                    ->put();
            }
            $this->_RecalcIDs['UserComment'] = array();
        }

        if ($UserIDsDiscussion = val('UserDiscussion', $this->_RecalcIDs)) {
            $counts = $this->arrayFlipAndCombine($UserIDsDiscussion);

            foreach ($counts as $key => $value) {
                Gdn::sql()
                    ->update('User')
                    ->set('CountDiscussions', 'coalesce(CountDiscussions, 0) + '.$key, false, false)
                    ->where('UserID', $value)
                    ->put();
            }
            $this->_RecalcIDs['UserDiscussion'] = array();
        }
    }

    /**
     * Takes an array and returns a flip, making values the keys and making the keys values.
     *
     * In case of multiple values with the several occurrences, this reserves all original keys by
     * pushing them onto an array.
     *
     * @param array $array An array in the format {[id1] => count, [id2] => count }
     * @return array A 2D array the format {[count] => [id1, id2]}
     */
    public function arrayFlipAndCombine($array) {
        if (!$array) {
            return;
        }
        $uniqueValues = array_unique(array_values($array));
        $newArray = array();
        foreach ($uniqueValues as $uniqueValue) {
            $newArray[$uniqueValue] = array();
            foreach ($array as $key => $value) {
                if ($value == $uniqueValue) {
                    $newArray[$uniqueValue][] = $key;
                }
            }
        }
        return $newArray;
    }

    /**
     *
     *
     * @param $Log
     * @param bool $DeleteLog
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function restore($Log, $DeleteLog = true) {
        static $Columns = array();

        if (is_numeric($Log)) {
            // Grab the log.
            $LogID = $Log;
            $Log = $this->getWhere(array('LogID' => $LogID));

            if (!$Log) {
                throw notFoundException('Log');
            }
            $Log = array_pop($Log);
        }

//      decho($Log, 'Log');

        $this->_RestoreOne($Log, $DeleteLog);
        // Check for a transaction.
        if ($TransactionID = $Log['TransactionLogID']) {
            $Logs = $this->getWhere(array('TransactionLogID' => $TransactionID), '', 'asc', 0, 200);
            foreach ($Logs as $LogRow) {
                if ($LogRow['LogID'] == $Log['LogID']) {
                    continue;
                }

                $this->_RestoreOne($LogRow, $DeleteLog);
            }
        }
        // Check for child data.
        if (isset($Log['Data']['_Data'])) {
            $Data = $Log['Data']['_Data'];
            foreach ($Data as $RecordType => $Rows) {
                foreach ($Rows as $Row) {
                    $LogRow = array_merge($Log, array('RecordType' => $RecordType, 'Data' => $Row));

                    if ($RecordType == 'Comment') {
                        $LogRow['ParentRecordID'] = $Row['DiscussionID'];
                    }

                    $this->_RestoreOne($LogRow, false);
                }
            }
        }

//      die();
    }

    /**
     *
     *
     * @param $Log
     * @param bool $DeleteLog
     * @throws Exception
     * @throws Gdn_UserException
     */
    protected function _RestoreOne($Log, $DeleteLog = true) {
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
        } elseif (isset($Data['Data']))
            $Attr = 'Data';
        else {
            $Attr = '';
        }

        if ($Attr) {
            if (is_string($Data[$Attr])) {
                $Data[$Attr] = @unserialize($Data[$Attr]);
            }

            // Record a bit of information about the restoration.
            if (!is_array($Data[$Attr])) {
                $Data[$Attr] = array();
            }
            $Data[$Attr]['RestoreUserID'] = Gdn::session()->UserID;
            $Data[$Attr]['DateRestored'] = Gdn_Format::toDateTime();
        }

//      decho($Data, 'Row being restored');

        if (!isset($Columns[$TableName])) {
            $Columns[$TableName] = Gdn::sql()->FetchColumns($TableName);
        }

        $Set = array_flip($Columns[$TableName]);
        // Set the sets from the data.
        foreach ($Set as $Key => $Value) {
            if (isset($Data[$Key])) {
                $Value = $Data[$Key];
                if (is_array($Value)) {
                    $Value = serialize($Value);
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
                $Where = array($IDColumn => $Log['RecordID']);
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
                $IDColumn = $Log['RecordType'].'ID';

                if (!$Log['RecordID']) {
                    // This log entry was never in the table.
//               unset($TableName);
                    if (isset($Set['DateInserted'])) {
                        $Set['DateInserted'] = Gdn_Format::toDateTime();
                    }
                }

                // Insert the record back into the db.
                if ($Log['Operation'] == 'Spam' && $Log['RecordType'] == 'Registration') {
                    saveToConfig(array('Garden.Registration.NameUnique' => false, 'Garden.Registration.EmailUnique' => false), '', false);
                    if (isset($Data['Username'])) {
                        $Set['Name'] = $Data['Username'];
                    }
                    $ID = Gdn::userModel()->InsertForBasic($Set, false, array('ValidateSpam' => false));
                    if (!$ID) {
                        throw new Exception(Gdn::userModel()->Validation->resultsText());
                    } else {
                        Gdn::userModel()->SendWelcomeEmail($ID, '', 'Register');
                    }
                } else {
                    $ID = Gdn::sql()
                        ->Options('Replace', true)
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
                                $this->_RecalcIDs['Discussion'][$ID] = true;
                                break;
                            case 'Comment':
                                $this->_RecalcIDs['Discussion'][$Log['ParentRecordID']] = true;
                                break;
                        }
                    }

                    if ($Log['Operation'] == 'Pending') {
                        switch ($Log['RecordType']) {
                            case 'Discussion':
                                if (val('UserDiscussion', $this->_RecalcIDs) && val($Log['RecordUserID'], $this->_RecalcIDs['UserDiscussion'])) {
                                    $this->_RecalcIDs['UserDiscussion'][$Log['RecordUserID']]++;
                                } else {
                                    $this->_RecalcIDs['UserDiscussion'][$Log['RecordUserID']] = 1;
                                }
                                break;
                            case 'Comment':
                                if (val('UserComment', $this->_RecalcIDs) && val($Log['RecordUserID'], $this->_RecalcIDs['UserComment'])) {
                                    $this->_RecalcIDs['UserComment'][$Log['RecordUserID']]++;
                                } else {
                                    $this->_RecalcIDs['UserComment'][$Log['RecordUserID']] = 1;
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
            Gdn::sql()->delete('Log', array('LogID' => $Log['LogID']));
        }

    }
}
