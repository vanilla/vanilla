<?php
/**
 * Contains useful functions for cleaning up the database.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 */

use Vanilla\PrunableTrait;

/**
 * Handles additional logging.
 */
class LogModel extends Gdn_Pluggable {

    use PrunableTrait;

    /** @var int Timestamp of when to prune delete logs. */
    private $deletePruneAfter;

    private static $instance = null;
    private $recalcIDs = [
        'Discussion' => [],
    ];
    private static $transactionID = null;

    public function __construct() {
        parent::__construct();
        try {
            $this->setPruneAfter(c('Logs.Common.PruneAfter', '3 months'));
        } catch(Exception $e) {
            $this->setPruneAfter('3 months');
        }
        try {
            $this->setDeletePruneAfter(c('Logs.Delete.PruneAfter', '1 year'));
        } catch(Exception $e) {
            $this->setDeletePruneAfter('1 year');
        }
    }

    /**
     * Set the prune time of delete logs.
     *
     * @param string $pruneAfter A string compatible with {@link strtotime()}.
     * @return $this
     */
    private function setDeletePruneAfter($pruneAfter) {
        if ($pruneAfter) {
            // Make sure the string can be converted into a date.
            $now = time();
            $testTime = strtotime($pruneAfter, $now);
            if ($testTime === false) {
                throw new \InvalidArgumentException('Invalid timespan value for "delete prune after".', 400);
            }
        }

        $this->deletePruneAfter = $pruneAfter;
        return $this;
    }

    /**
     * Get the exact timestamp to prune delete logs.
     *
     * @return \DateTimeInterface|null Returns the date that we should prune after.
     */
    public function getDeletePruneDate() {
        if (!$this->deletePruneAfter) {
            return null;
        } else {
            $tz = new \DateTimeZone('UTC');
            $now = new \DateTimeImmutable('now', $tz);
            $test = new \DateTimeImmutable($this->deletePruneAfter, $tz);

            $interval = $test->diff($now);

            if ($interval->invert === 1) {
                return $now->add($interval);
            } else {
                return $test;
            }
        }
    }

    /**
     * Begin a log transaction.
     */
    public static function beginTransaction() {
        self::$transactionID = true;
    }

    /**
     * Delete records from the log table.
     *
     * @param array $where The where clause.
     * @param array $options Options for the delete.
     * @return mixed
     */
    public function delete($where = [], $options = []) {
        if (!is_array($where)) {
            $logIDs = explode(',', $logIDs);
        } else {
            $keysAreIDs = true;
            $keys = array_keys($where);
            foreach ($keys as $key) {
                if (!filter_var($key, FILTER_VALIDATE_INT, ['min_range' => '1'])) {
                    $keysAreIDs = false;
                    break;
                }
            }

            if ($keysAreIDs) {
                $logIDs = $keys;
            }
        }

        if (isset($logIDs)) {
            deprecated('delete(int[])', 'deleteIDs');
            $this->deleteIDs($logIDs);
            return;
        }

        Gdn::sql()->delete('Log', $where, $options['limit'] ?? false);
    }

    /**
     * Prune old rows.
     *
     * @param int|null $limit Then number of rows to delete or **null** to use the default prune limit.
     */
    public function prune($limit = null) {
        $dateCommonPrune = $this->getPruneDate();
        $dateDeletePrune = $this->getDeletePruneDate();

        $options = [];
        if ($limit === null) {
            $options['limit'] = $this->getPruneLimit();
        } elseif ($limit !== 0) {
            $options['limit'] = $limit;
        }

        $this->delete(
            [
                $this->getPruneField().' <' => $dateCommonPrune->format('Y-m-d H:i:s'),
                'Operation' => ['Edit','Spam','Moderate','Error'],
            ],
            $options
        );
        $this->delete(
            [
                $this->getPruneField().' <' => $dateDeletePrune->format('Y-m-d H:i:s'),
                'Operation' => 'Delete',
            ],
            $options
        );
    }

    /**
     * Purge entries from the log and clean associated records if needed.
     *
     * @param int[]|string $logIDs
     */
    public function deleteIDs($logIDs) {
        if (is_string($logIDs)) {
            $logIDs = explode(',', $logIDs);
        }

        // Get the log entries.
        $logs = $this->getIDs($logIDs);
        $models = [];
        $models['Discussion'] = new DiscussionModel();
        $models['Comment'] = new CommentModel();

        foreach ($logs as $log) {
            $recordType = $log['RecordType'];
            if (in_array($log['Operation'], ['Spam', 'Moderate']) && array_key_exists($recordType, $models)) {
                /** @var Gdn_Model $model */
                $model = $models[$recordType];
                $recordID = $log['RecordID'];
                $deleteRecord = true;

                // Determine if the original record, if still available, should be deleted too.
                $record = $model->getID($recordID, DATASET_TYPE_ARRAY);
                if ($record) {
                    switch ($recordType) {
                        case 'Discussion':
                            if ($record['CountComments'] >= DiscussionModel::DELETE_COMMENT_THRESHOLD) {
                                $deleteRecord = false;
                            }
                            break;
                    }
                }

                if ($deleteRecord) {
                    $model->deleteID($recordID, ['Log' => false]);
                }
            }
        }

        Gdn::sql()->whereIn('LogID', $logIDs)->delete('Log');
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
     * @param array $log The log entry to format.
     * @return string Returns the formatted log entry.
     */
    public function formatContent($log) {
        $data = $log['Data'];

        $result = '';
        $this->EventArguments['Log'] = $log;
        $this->EventArguments['Result'] = &$result;
        $this->fireEvent('FormatContent');

        if ($result === '') {
            switch ($log['RecordType']) {
                case 'Activity':
                    $result = $this->formatKey('Story', $data);
                    break;
                case 'Discussion':
                    $result =
                        '<b>'.$this->formatKey('Name', $data).'</b><br />'.
                        $this->formatKey('Body', $data);
                    break;
                case 'ActivityComment':
                case 'Comment':
                    $result = $this->formatKey('Body', $data);
                    break;
                case 'Configuration':
                    $result = $this->formatConfiguration($data);
                    break;
                case 'Registration':
                case 'User':
                    $result = $this->formatRecord(['Email', 'Name', 'RecordIPAddress' => 'IP Address'], $data);
                    if ($discoveryText = val('DiscoveryText', $data)) {
                        $result .= '<br /><b>'.t('Why do you want to join?').'</b><br />'.Gdn_Format::display($discoveryText);
                    }
                    if (val('Banned', $data)) {
                        $result .= "<br />".t('Banned');
                    }
                    break;
            }
        }

        return $result;
    }

    /**
     * Format a configuration subtree.
     *
     * @param array $data The data to format.
     * @return string Returns the formatted entry.
     */
    public function formatConfiguration($data) {
        $old = $data;
        $new = $data['_New'];
        unset($old['_New']);

        $old = Gdn_Configuration::format($old);
        $new = Gdn_Configuration::format($new);
        $diffs = $this->formatDiff($old, $new, 'raw');

        $result = [];
        foreach ($diffs as $diff) {
            if (is_array($diff)) {
                if (!empty($diff['del'])) {
                    $result[] = '<del>'.implode("<br />\n", $diff['del']).'</del>';
                }
                if (!empty($diff['ins'])) {
                    $result[] = '<ins>'.implode("<br />\n", $diff['ins']).'</ins>';
                }
            }
        }

        $result = implode("<br />\n", $result);
        if ($result) {
            return $result;
        } else {
            return t('No Change');
        }
    }

    /**
     * Format a specific column from the log.
     *
     * @param string $key The key in the log row to format.
     * @param array $data The log row.
     * @return string Returns the formatted entry.
     */
    public function formatKey($key, $data) {
        if (!is_array($data)) {
            $data = (array)$data;
        }
        if (isset($data['_New'][$key])) {
            $old = htmlspecialchars(val($key, $data, ''));
            $new = htmlspecialchars($data['_New'][$key]);
            $result = $this->formatDiff($old, $new);
        } else {
            $result = htmlspecialchars(val($key, $data, ''));
        }
        return nl2br(trim(($result)));
    }

    /**
     * Format a record that the log points to.
     *
     * @param string[] $keys The keys to use from the record.
     * @param array $data The log row.
     * @return string Returns the formatted record.
     */
    public function formatRecord($keys, $data) {
        $result = [];
        foreach ($keys as $index => $key) {
            if (is_numeric($index)) {
                $index = $key;
            }

            if (!val($index, $data)) {
                continue;
            }
            $result[] = '<b>'.htmlspecialchars($key).'</b>: '.htmlspecialchars(val($index, $data));
        }
        $result = implode('<br />', $result);
        return $result;
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
            require_once(__DIR__.'/tiny_diff.php');
            $TinyDiff = new Tiny_diff();
        }

        $Result = $TinyDiff->compare($Old, $New, $Method);
        return $Result;
    }

    /**
     * Get the log rows by array of IDs.
     *
     * @param int[]|string $iDs And array or CSV of IDs.
     * @return array Returns an array of log rows.
     */
    public function getIDs($iDs) {
        if (is_string($iDs)) {
            $iDs = explode(',', $iDs);
        }

        $logs = Gdn::sql()
            ->select('*')
            ->from('Log')
            ->whereIn('LogID', $iDs)
            ->get()->resultArray();
        foreach ($logs as &$log) {
            $log['Data'] = dbdecode($log['Data']);
            if (!is_array($log['Data'])) {
                $log['Data'] = [];
            }
        }

        return $logs;
    }

    /**
     * Get log rows by a query.
     *
     * @param array|false $where The where filter.
     * @param string $orderFields The fields to order by.
     * @param string $orderDirection The order direction.
     * @param bool $offset The database offset.
     * @param bool $limit The database limit.
     * @return array Returns a data set.
     */
    public function getWhere($where = false, $orderFields = '', $orderDirection = 'asc', $offset = false, $limit = false) {
        if ($offset < 0) {
            $offset = 0;
        }

        if (isset($where['Operation'])) {
            Gdn::sql()->whereIn('Operation', (array)$where['Operation']);
            unset($where['Operation']);
        }

        $result = Gdn::sql()
            ->select('l.*')
            ->select('ru.Name as RecordName, iu.Name as InsertName')
            ->from('Log l')
            ->join('User ru', 'l.RecordUserID = ru.UserID', 'left')
            ->join('User iu', 'l.InsertUserID = iu.UserID', 'left')
            ->where($where)
            ->limit($limit, $offset)
            ->orderBy($orderFields, $orderDirection)
            ->get()->resultArray();

        // Deserialize the data.
        foreach ($result as &$row) {
            $row['Data'] = dbdecode($row['Data']);
            if (!$row['Data']) {
                $row['Data'] = [];
            }
        }

        return $result;
    }

    /**
     * Get the count of log entries matching a query.
     *
     * @param array $where The filter.
     * @return int Returns the count.
     */
    public function getCountWhere($where) {
        if (isset($where['Operation'])) {
            Gdn::sql()->whereIn('Operation', (array)$where['Operation']);
            unset($where['Operation']);
        }

        $result = Gdn::sql()
            ->select('l.LogID', 'count', 'CountLogID')
            ->from('Log l')
            ->where($where)
            ->get()->value('CountLogID', 0);

        return $result;
    }

    /**
     * A wrapper for GetCountWhere that takes care of caching specific operation counts.
     *
     * @param string $operation Comma-delimited list of operation types to get (sum of) counts for.
     * @return int Returns a count.
     */
    public function getOperationCount($operation) {
        if ($operation == 'edits') {
            $operation = ['edit', 'delete'];
        } else {
            $operation = explode(',', $operation);
        }

        sort($operation);
        array_map('ucfirst', $operation);
        $cacheKey = 'Moderation.LogCount.'.implode('.', $operation);
        $count = Gdn::cache()->get($cacheKey);
        if ($count === Gdn_Cache::CACHEOP_FAILURE) {
            $count = $this->getCountWhere(['Operation' => $operation]);
            Gdn::cache()->store($cacheKey, $count, [
                Gdn_Cache::FEATURE_EXPIRY => 300 // 5 minutes
            ]);
        }
        return $count;
    }


    /**
     * Log an operation into the log table.
     *
     * @param string $operation The operation being performed. This is usually one of:
     *  - Delete: The record has been deleted.
     *  - Edit: The record has been edited.
     *  - Spam: The record has been marked spam.
     *  - Moderate: The record requires moderation.
     *  - Pending: The record needs pre-moderation.
     * @param string $recordType The type of record being logged. This usually correspond to the tablename of the record.
     * @param array $data The record data.
     *  - If you are logging just one row then pass the row as an array.
     *  - You can pass an additional _New element to tell the logger what the new data is.
     * @param array $options Additional options to affect the insert.
     * @return int|false The log ID or **false** if there was a problem.
     */
    public static function insert($operation, $recordType, $data, $options = []) {
        if ($operation === false) {
            return false;
        }

        if (!is_array($data)) {
            $data = [$data];
        }

        // Check to see if we are storing two versions of the data.
        if (($insertUserID = self::logValue($data, 'Log_InsertUserID')) === null) {
            $insertUserID = Gdn::session()->UserID;
        }
        if (($insertIPAddress = self::logValue($data, 'Log_InsertIPAddress')) == null) {
            $insertIPAddress = ipEncode(Gdn::request()->ipAddress());
        }
        // Do some known translations for the parent record ID.
        if (($parentRecordID = self::logValue($data, 'ParentRecordID')) === null) {
            switch ($recordType) {
                case 'Activity':
                    $parentRecordID = self::logValue($data, 'CommentActivityID', 'CommentActivityID');
                    break;
                case 'Comment':
                    $parentRecordID = self::logValue($data, 'DiscussionID', 'DiscussionID');
                    break;
            }
        }

        // Get the row information from the data or determine it based on the type.
        $logRow = [
            'Operation' => $operation,
            'RecordType' => $recordType,
            'RecordID' => self::logValue($data, 'RecordID', $recordType.'ID'),
            'RecordUserID' => self::logValue($data, 'RecordUserID', 'UpdateUserID', 'InsertUserID'),
            'RecordIPAddress' => self::logValue($data, 'RecordIPAddress', 'LastIPAddress', 'InsertIPAddress'),
            'RecordDate' => self::logValue($data, 'RecordDate', 'DateUpdated', 'DateInserted'),
            'InsertUserID' => $insertUserID,
            'InsertIPAddress' => $insertIPAddress,
            'DateInserted' => Gdn_Format::toDateTime(),
            'ParentRecordID' => $parentRecordID,
            'CategoryID' => self::logValue($data, 'CategoryID'),
            'OtherUserIDs' => implode(',', val('OtherUserIDs', $options, [])),
            'Data' => dbencode($data)
        ];
        if ($logRow['RecordDate'] == null) {
            $logRow['RecordDate'] = Gdn_Format::toDateTime();
        }

        $groupBy = val('GroupBy', $options);

        // Make sure we aren't grouping by null values.
        if (is_array($groupBy)) {
            foreach ($groupBy as $name) {
                if (val($name, $logRow) === null) {
                    $groupBy = false;
                    break;
                }
            }
        }

        if ($groupBy) {
            $groupBy[] = 'Operation';
            $groupBy[] = 'RecordType';

            // Check to see if there is a record already logged here.
            $where = array_combine($groupBy, arrayTranslate($logRow, $groupBy));
            $logRow2 = Gdn::sql()->getWhere('Log', $where)->firstRow(DATASET_TYPE_ARRAY);
            if ($logRow2) {
                $logID = $logRow2['LogID'];
                $set = [];

                $data = array_merge(dbdecode($logRow2['Data']), $data);

                $otherUserIDs = explode(',', $logRow2['OtherUserIDs']);
                if (!is_array($otherUserIDs)) {
                    $otherUserIDs = [];
                }

                if (!$logRow2['InsertUserID']) {
                    $set['InsertUserID'] = $insertUserID;
                } elseif ($insertUserID != $logRow2['InsertUserID'] && !in_array($insertUserID, $otherUserIDs)) {
                    $otherUserIDs[] = $insertUserID;
                }

                if (array_key_exists('OtherUserIDs', $options)) {
                    $otherUserIDs = array_merge($otherUserIDs, $options['OtherUserIDs']);
                    $otherUserIDs = array_unique($otherUserIDs);
                    $otherUserIDs = array_diff($otherUserIDs, [$insertUserID]);

                    $count = count($otherUserIDs) + 1;
                } else {
                    $count = (int)$logRow2['CountGroup'] + 1;
                }
                $set['OtherUserIDs'] = implode(',', $otherUserIDs);
                $set['CountGroup'] = $count;
                $set['Data'] = dbencode($data);
                $set['DateUpdated'] = Gdn_Format::toDateTime();

                if (self::$transactionID > 0) {
                    $set['TransactionLogID'] = self::$transactionID;
                } elseif (self::$transactionID === true) {
                    if ($logRow2['TransactionLogID']) {
                        self::$transactionID = $logRow2['TransactionLogID'];
                    } else {
                        self::$transactionID = $logID;
                        $set['TransactionLogID'] = $logID;
                    }
                }

                Gdn::sql()->put(
                    'Log',
                    $set,
                    ['LogID' => $logID]
                );
            } else {
                $l = self::instance();
                $l->EventArguments['Log'] =& $logRow;
                $l->fireEvent('BeforeInsert');

                if (self::$transactionID > 0) {
                    $logRow['TransactionLogID'] = self::$transactionID;
                }

                $logID = Gdn::sql()->insert('Log', $logRow);

                if (self::$transactionID === true) {
                    // A new transaction was started and needs to assigned.
                    self::$transactionID = $logID;
                    Gdn::sql()->put('Log', ['TransactionLogID' => $logID], ['LogID' => $logID]);
                }

                $l->EventArguments['LogID'] = $logID;
                $l->fireEvent('AfterInsert');
            }
        } else {
            if (self::$transactionID > 0) {
                $logRow['TransactionLogID'] = self::$transactionID;
            }

            // Insert the log entry.
            $l = self::instance();
            $l->EventArguments['Log'] =& $logRow;
            $l->fireEvent('BeforeInsert');

            $logID = Gdn::sql()->insert('Log', $logRow);

            if (self::$transactionID === true) {
                // A new transaction was started and needs to assigned.
                self::$transactionID = $logID;
                Gdn::sql()->put('Log', ['TransactionLogID' => $logID], ['LogID' => $logID]);
            }

            $l->EventArguments['LogID'] = $logID;
            $l->fireEvent('AfterInsert');
        }

        self::instance()->prune();

        return $logID;
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
     * @param string $operation The specific operation being logged.
     * @param string $recordType The type of record. This matches the name of the record's table.
     * @param array $newData The record after the edit.
     * @param array|null $oldData The record before the edit.
     */
    public static function logChange($operation, $recordType, $newData, $oldData = null) {
        $recordID = isset($newData['RecordID']) ? $newData['RecordID'] : val($recordType.'ID', $newData);

        // Grab the record from the DB.
        if ($oldData === null) {
            $oldData = Gdn::sql()->getWhere($recordType, [$recordType.'ID' => $recordID])->resultArray();
        } elseif (!is_array($oldData)) {
            $oldData = [$oldData];
        }

        foreach ($oldData as $row) {
            // Don't log the change if it's right after an insert.
            if (val('DateInserted', $row) && (time() - Gdn_Format::toTimestamp(val('DateInserted', $row))) < c('Garden.Log.FloodControl', 20) * 60) {
                continue;
            }

            setValue('_New', $row, $newData);
            self::insert($operation, $recordType, $row);
        }
    }

    /**
     * Get a value from a log entry.
     *
     * @param array $data The log row.
     * @param string $logKey The key in the log row.
     * @param string $bakKey1 A key to look at if the first key isn't found.
     * @param string $bakKey2 A key to look at if the second key isn't found.
     * @return mixed Returns the value.
     */
    private static function logValue($data, $logKey, $bakKey1 = '', $bakKey2 = '') {
        $data = (array)$data;
        if (isset($data[$logKey]) && $logKey != $bakKey1) {
            $result = $data[$logKey];
            unset($data[$logKey]);
        } elseif (isset($data['_New'][$bakKey1])) {
            $result = $data['_New'][$bakKey1];
        } elseif (isset($data[$bakKey1]) && ($data[$bakKey1] || !$bakKey2)) {
            $result = $data[$bakKey1];
        } elseif (isset($data[$bakKey2])) {
            $result = $data[$bakKey2];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Recalculate a record after a log operation.
     */
    public function recalculate() {
        $categoryModel = CategoryModel::instance();
        $commentModel = CommentModel::instance();

        if ($discussionIDs = val('Discussion', $this->recalcIDs)) {
            foreach ($discussionIDs as $discussionID => $tmp) {
                $commentModel->updateCommentCount($discussionID);
                $categoryModel->incrementLastDiscussion($discussionID);
            }
        }

        if ($commentIDs = val('Comment', $this->recalcIDs)) {
            foreach ($commentIDs as $commentID => $tmp) {
                $categoryModel->incrementLastComment($commentID);
            }
        }

        if ($userIDsComment = val('UserComment', $this->recalcIDs)) {
            $counts = $this->arrayFlipAndCombine($userIDsComment);

            foreach ($counts as $key => $value) {
                Gdn::sql()
                    ->update('User')
                    ->set('CountComments', 'coalesce(CountComments, 0) + '.$key, false, false)
                    ->where('UserID', $value)
                    ->put();
            }
            $this->recalcIDs['UserComment'] = [];
        }

        if ($userIDsDiscussion = val('UserDiscussion', $this->recalcIDs)) {
            $counts = $this->arrayFlipAndCombine($userIDsDiscussion);

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
     * @param array|int $log The log row or the ID of the log row.
     * @param bool $deleteLog Whether or not to delete the log row after restoring.
     * @throws Gdn_UserException Throws an exception if the log entry isn't found.
     */
    public function restore($log, $deleteLog = true) {
        if (is_numeric($log)) {
            // Grab the log.
            $logID = $log;
            $log = $this->getWhere(['LogID' => $logID]);

            if (!$log) {
                throw notFoundException('Log');
            }
            $log = array_pop($log);
        }

        $this->restoreOne($log, $deleteLog);
        // Check for a transaction.
        if ($transactionID = $log['TransactionLogID']) {
            $logs = $this->getWhere(['TransactionLogID' => $transactionID], '', 'asc', 0, 200);
            foreach ($logs as $logRow) {
                if ($logRow['LogID'] == $log['LogID']) {
                    continue;
                }

                $this->restoreOne($logRow, $deleteLog);
            }
        }
        // Check for child data.
        if (isset($log['Data']['_Data'])) {
            $data = $log['Data']['_Data'];
            foreach ($data as $recordType => $rows) {
                foreach ($rows as $row) {
                    $logRow = array_merge($log, ['RecordType' => $recordType, 'Data' => $row]);

                    if ($recordType == 'Comment') {
                        $logRow['ParentRecordID'] = $row['DiscussionID'];
                    }

                    $this->restoreOne($logRow, false);
                }
            }
        }
    }

    /**
     * Restores a single entry from the log.
     *
     * @param array $log The log entry.
     * @param bool $deleteLog Whether or not to delete the log entry after the restore.
     * @throws Exception Throws an exception if restoring the record causes a validation error.
     */
    private function restoreOne($log, $deleteLog = true) {
        // Keep track of table structures we've already fetched.
        static $columns = [];

        // Throw an event to see if the restore is being overridden.
        $handled = false;
        $this->EventArguments['Handled'] =& $handled;
        $this->EventArguments['Log'] =& $log;
        $this->fireEvent('BeforeRestore');
        if ($handled) {
            return; // a plugin handled the restore.
        }
        if ($log['RecordType'] == 'Configuration') {
            throw new Gdn_UserException('Restoring configuration edits is currently not supported.');
        }

        if ($log['RecordType'] == 'Registration') {
            $tableName = 'User';
        } else {
            $tableName = $log['RecordType'];
        }

        $data = $log['Data'];

        if (isset($data['Attributes'])) {
            $attr = 'Attributes';
        } elseif (isset($data['Data'])) {
            $attr = 'Data';
        } else {
            $attr = '';
        }

        if ($attr) {
            if (is_string($data[$attr])) {
                $data[$attr] = dbdecode($data[$attr]);
            }

            // Record a bit of information about the restoration.
            if (!is_array($data[$attr])) {
                $data[$attr] = [];
            }
            $data[$attr]['RestoreUserID'] = Gdn::session()->UserID;
            $data[$attr]['DateRestored'] = Gdn_Format::toDateTime();
        }

        if (!isset($columns[$tableName])) {
            $columns[$tableName] = Gdn::sql()->fetchColumns($tableName);
        }

        $set = array_flip($columns[$tableName]);
        // Set the sets from the data.
        foreach ($set as $key => $value) {
            if (isset($data[$key])) {
                $value = $data[$key];
                if (is_array($value)) {
                    $value = dbencode($value);
                }
                $set[$key] = $value;
            } else {
                unset($set[$key]);
            }
        }

        switch ($log['Operation']) {
            case 'Edit':
                // We are restoring an edit so just update the record.
                $iDColumn = $log['RecordType'].'ID';
                $where = [$iDColumn => $log['RecordID']];
                unset($set[$iDColumn]);
                Gdn::sql()->put(
                    $tableName,
                    $set,
                    $where
                );

                break;
            case 'Delete':
            case 'Spam':
            case 'Moderate':
            case 'Pending':
            case 'Ban':
                if (!$log['RecordID']) {
                    // This log entry was never in the table.
                    if (isset($set['DateInserted'])) {
                        $set['DateInserted'] = Gdn_Format::toDateTime();
                    }
                }

                // Insert the record back into the db.
                if ($log['Operation'] == 'Spam' && $log['RecordType'] == 'Registration') {
                    saveToConfig(['Garden.Registration.NameUnique' => false, 'Garden.Registration.EmailUnique' => false], '', false);
                    if (isset($data['Username'])) {
                        $set['Name'] = $data['Username'];
                    }
                    if (c('Garden.Registration.Method') === 'Approval') {
                        $iD = Gdn::userModel()->insertForApproval($set, ['ValidateSpam' => false, 'CheckCaptcha' => false]);
                    } else {
                        $iD = Gdn::userModel()->insertForBasic($set, false, ['ValidateSpam' => false]);
                    }
                    if (!$iD) {
                        throw new Exception(Gdn::userModel()->Validation->resultsText());
                    } else {
                        Gdn::userModel()->sendWelcomeEmail($iD, '', 'Register');

                        // If this record has a Source and a SourceID, it has an SSO mapping that needs to be created.
                        $source = val('Source', $data);
                        $sourceID = val('SourceID', $data);
                        if ($source && $sourceID) {
                            Gdn::userModel()->saveAuthentication([
                                'UserID' => $iD,
                                'Provider' => $source,
                                'UniqueID' => $sourceID
                            ]);
                        }
                    }
                } else {
                    $iD = Gdn::sql()
                        ->options('Replace', true)
                        ->insert($tableName, $set);
                    if (!$iD && isset($log['RecordID'])) {
                        $iD = $log['RecordID'];
                    }

                    // Unban a user.
                    if ($log['RecordType'] == 'User' && $log['Operation'] == 'Ban') {
                        Gdn::userModel()->setField($iD, 'Banned', 0);
                    }

                    // Keep track of discussions and categories so that their counts can be recalculated.
                    switch ($log['RecordType']) {
                        case 'Discussion':
                            $this->recalcIDs['Discussion'][$iD] = true;
                            break;
                        case 'Comment':
                            $this->recalcIDs['Discussion'][$log['ParentRecordID']] = true;
                            $this->recalcIDs['Comment'][$iD] = true;
                            break;
                    }

                    if ($log['Operation'] == 'Pending') {
                        switch ($log['RecordType']) {
                            case 'Discussion':
                                if (val('UserDiscussion', $this->recalcIDs) && val($log['RecordUserID'], $this->recalcIDs['UserDiscussion'])) {
                                    $this->recalcIDs['UserDiscussion'][$log['RecordUserID']]++;
                                } else {
                                    $this->recalcIDs['UserDiscussion'][$log['RecordUserID']] = 1;
                                }
                                break;
                            case 'Comment':
                                if (val('UserComment', $this->recalcIDs) && val($log['RecordUserID'], $this->recalcIDs['UserComment'])) {
                                    $this->recalcIDs['UserComment'][$log['RecordUserID']]++;
                                } else {
                                    $this->recalcIDs['UserComment'][$log['RecordUserID']] = 1;
                                }
                                break;
                        }
                    }
                }

                break;
        }

        // Fire 'after' event
        if (isset($iD)) {
            $this->EventArguments['InsertID'] = $iD;
        }
        $this->fireEvent('AfterRestore');

        if ($deleteLog) {
            Gdn::sql()->delete('Log', ['LogID' => $log['LogID']]);
        }

    }
}
