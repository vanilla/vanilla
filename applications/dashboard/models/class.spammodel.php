<?php
/**
 * Spam model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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

            LogModel::insert('Spam', $RecordType, $Data, $LogOptions);
        }

        return $Spam;
    }
}
