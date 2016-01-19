<?php
/**
 * Spam model.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles spam data.
 */
class SpamModel extends Gdn_Pluggable {

    /** @var SpamModel */
    protected static $_Instance;

    /** @var bool */
    public static $Disabled = false;

    /**
     *
     *
     * @return SpamModel
     */
    protected static function _Instance() {
        if (!self::$_Instance) {
            self::$_Instance = new SpamModel();
        }

        return self::$_Instance;
    }

    /**
     * Return whether or not the spam model is disabled.
     *
     * @param bool|null $value
     * @return bool
     */
    public static function disabled($value = null) {
        if ($value !== null) {
            self::$Disabled = $value;
        }
        return self::$Disabled;
    }

    /**
     * Check whether or not the record is spam.
     * @param string $RecordType By default, this should be one of the following:
     *  - Comment: A comment.
     *  - Discussion: A discussion.
     *  - User: A user registration.
     * @param array $Data The record data.
     * @param array $Options Options for fine-tuning this method call.
     *  - Log: Log the record if it is found to be spam.
     */
    public static function isSpam($RecordType, $Data, $Options = array()) {
        if (self::$Disabled) {
            return false;
        }

        // Set some information about the user in the data.
        if ($RecordType == 'Registration') {
            touchValue('Username', $Data, $Data['Name']);
        } else {
            touchValue('InsertUserID', $Data, Gdn::session()->UserID);

            $User = Gdn::userModel()->getID(val('InsertUserID', $Data), DATASET_TYPE_ARRAY);

            if ($User) {
                if (val('Verified', $User)) {
                    // The user has been verified and isn't a spammer.
                    return false;
                }
                touchValue('Username', $Data, $User['Name']);
                touchValue('Email', $Data, $User['Email']);
                touchValue('IPAddress', $Data, $User['LastIPAddress']);
            }
        }

        if (!isset($Data['Body']) && isset($Data['Story'])) {
            $Data['Body'] = $Data['Story'];
        }

        touchValue('IPAddress', $Data, Gdn::request()->ipAddress());

        $Sp = self::_Instance();

        $Sp->EventArguments['RecordType'] = $RecordType;
        $Sp->EventArguments['Data'] =& $Data;
        $Sp->EventArguments['Options'] =& $Options;
        $Sp->EventArguments['IsSpam'] = false;

        $Sp->fireEvent('CheckSpam');
        $Spam = $Sp->EventArguments['IsSpam'];

        // Log the spam entry.
        if ($Spam && val('Log', $Options, true)) {
            $LogOptions = array();
            switch ($RecordType) {
                case 'Registration':
                    $LogOptions['GroupBy'] = array('RecordIPAddress');
                    break;
                case 'Comment':
                case 'Discussion':
                case 'Activity':
                case 'ActivityComment':
                    $LogOptions['GroupBy'] = array('RecordID');
                    break;
            }

            // If this is a discussion or a comment, it needs some special handling.
            if ($RecordType == 'Comment' || $RecordType == 'Discussion') {
                // Grab the record ID, if available.
                $recordID = intval(val("{$RecordType}ID", $Data));

                /**
                 * If we have a valid record ID, run it through flagForReview.  This will allow us to purge existing
                 * discussions and comments that have been flagged as SPAM after being edited.  If there's no valid ID,
                 * just treat it with regular SPAM logging.
                 */
                if ($recordID) {
                    self::flagForReview($RecordType, $recordID, $Data);
                } else {
                    LogModel::insert('Spam', $RecordType, $Data, $LogOptions);
                }
            } else {
                LogModel::insert('Spam', $RecordType, $Data, $LogOptions);
            }
        }

        return $Spam;
    }

    /**
     * Insert a SPAM Queue entry for the specified record and delete the record, if possible.
     *
     * @param string $recordType The type of record we're flagging: Discussion or Comment.
     * @param int $id ID of the record we're flagging.
     * @param object|array $data Properties used for updating/overriding the record's current values.
     *
     * @throws Exception If an invalid record type is specified, throw an exception.
     */
    protected static function flagForReview($recordType, $id, $data) {
        // We're planning to purge the spammy record.
        $deleteRow = true;

        /**
         * We're only handling two types of content: discussions and comments.  Both need some special setup.
         * Error out if we're not dealing with a discussion or comment.
         */
        switch ($recordType) {
            case 'Comment':
                $model = new CommentModel();
                $row = $model->getID($id, DATASET_TYPE_ARRAY);
                break;
            case 'Discussion':
                $model = new DiscussionModel();
                $row = $model->getID($id, DATASET_TYPE_ARRAY);

                /**
                 * If our discussion has more than three comments, it might be worth saving.  Hold off on deleting and
                 * just flag it.  If we have between 0 and 3 comments, save them along with the discussion.
                 */
                if ($row['CountComments'] > 3) {
                    $deleteRow = false;
                } elseif ($row['CountComments'] > 0) {
                    $comments = Gdn::database()->sql()->getWhere(
                        'Comment',
                        array('DiscussionID' => $id)
                    )->resultArray();

                    if (!array_key_exists('_Data', $row)) {
                        $row['_Data'] = array();
                    }

                    $row['_Data']['Comment'] = $comments;
                }
                break;
            default:
                throw notFoundException($recordType);
        }

        $overrideFields = array('Name', 'Body');
        foreach ($overrideFields as $fieldName) {
            if (($fieldValue = val($fieldName, $data, false)) !== false) {
                $row[$fieldName] = $fieldValue;
            }
        }

        $logOptions = array('GroupBy' => array('RecordID'));

        if ($deleteRow) {
            // Remove the record to the log.
            $model->deleteID($id);
        }

        LogModel::insert('Spam', $recordType, $row, $logOptions);
    }
}
