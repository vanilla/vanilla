<?php
/**
 * Ban Model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Manage banning of users.
 *
 * @since 2.0.18
 * @package Dashboard
 */
class BanModel extends Gdn_Model {

    /** Manually banned by a moderator. */
    const BAN_MANUAL = 0x1;

    /** Automatically banned by an IP ban, name, or email ban. */
    const BAN_AUTOMATIC = 0x2;

    /** Reserved for future functionality. */
    const BAN_TEMPORARY = 0x4;

    /** Banned by the warnings plugin. */
    const BAN_WARNING = 0x8;

    /* @var array */
    protected static $_AllBans;

    /** @var BanModel The singleton instance of this class. */
    protected static $instance;

    /**
     * Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Ban');
        $this->fireEvent('Init');
    }

    /**
     * Get the singleton instance of the {@link BanModel} class.
     *
     * @return BanModel Returns the singleton instance of this class.
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new BanModel();
        }
        return self::$instance;
    }

    /**
     * Get and store list of current bans.
     *
     * @since 2.0.18
     * @access public
     */
    public static function &allBans() {
        if (!self::$_AllBans) {
            self::$_AllBans = Gdn::sql()->get('Ban')->resultArray();
            self::$_AllBans = Gdn_DataSet::index(self::$_AllBans, ['BanID']);
        }
//      $AllBans =& self::$_AllBans;
        return self::$_AllBans;
    }

    /**
     * Convert bans to new type.
     *
     * @since 2.0.18
     * @access public
     *
     * @param array $newBan Data about the new ban.
     * @param array $oldBan Data about the old ban.
     */
    public function applyBan($newBan = null, $oldBan = null) {
        if (!$newBan && !$oldBan) {
            return;
        }

        $oldUsers = [];
        $newUsers = [];
        $newUserIDs = [];

        $allBans = $this->allBans();

        if ($newBan) {
            // Get a list of users affected by the new ban.
            if (isset($newBan['BanID'])) {
                $allBans[$newBan['BanID']] = $newBan;
            }

            // Protect against a lack of inet6_ntoa, which wasn't introduced until MySQL 5.6.3.
            try {
                $newUsers = $this->SQL
                    ->select('u.UserID, u.Banned')
                    ->from('User u')
                    ->where($this->banWhere($newBan))
                    ->where('Admin', 0)// No banning superadmins, pls.
                    ->get()->resultArray();
            } catch (Exception $e) {
                Logger::log(
                    Logger::ERROR,
                    $e->getMessage()
                );
                $newUsers = [];
            }

            $newUserIDs = array_column($newUsers, 'UserID');
        } elseif (isset($oldBan['BanID'])) {
            unset($allBans[$oldBan['BanID']]);
        }

        if ($oldBan) {
            // Get a list of users affected by the old ban.
            // Protect against a lack of inet6_ntoa, which wasn't introduced until MySQL 5.6.3.
            try {
                $oldUsers = $this->SQL
                    ->select('u.UserID, u.LastIPAddress, u.Name, u.Email, u.Banned')
                    ->from('User u')
                    ->where($this->banWhere($oldBan))
                    ->get()->resultArray();
            } catch (Exception $e) {
                Logger::log(
                    Logger::ERROR,
                    $e->getMessage()
                );
                $oldUsers = [];
            }
        }

        // Check users that need to be unbanned.
        foreach ($oldUsers as $user) {
            if (in_array($user['UserID'], $newUserIDs)) {
                continue;
            }
            // TODO check the user against the other bans.
            $this->saveUser($user, false);
        }

        // Check users that need to be banned.
        foreach ($newUsers as $user) {
            if (self::isBanned($user['Banned'], BanModel::BAN_AUTOMATIC)) {
                continue;
            }
            $this->saveUser($user, true, $newBan);
        }
    }

    /**
     * Ban users that meet conditions given.
     *
     * @since 2.0.18
     * @access public
     * @param array $ban Data about the ban.
     *    Valid keys are BanType and BanValue. BanValue is what is to be banned.
     *    Valid values for BanType are email, ipaddress or name.
     * @return array
     */
    public function banWhere($ban) {
        $result = ['u.Admin' => 0, 'u.Deleted' => 0];
        $ban['BanValue'] = str_replace('*', '%', $ban['BanValue']);

        switch (strtolower($ban['BanType'])) {
            case 'email':
                $result['u.Email like'] = $ban['BanValue'];
                break;
            case 'ipaddress':
                $result['inet6_ntoa(u.LastIPAddress) like'] = $ban['BanValue'];
                break;
            case 'name':
                $result['u.Name like'] = $ban['BanValue'];
                break;
        }
        return $result;
    }

    /**
     * Add ban data to all Get requests.
     *
     * @since 2.0.18
     * @access public
     */
    public function _BeforeGet() {
        $this->SQL
            ->select('Ban.*')
            ->select('iu.Name', '', 'InsertName')
            ->join('User iu', 'Ban.InsertUserID = iu.UserID', 'left');

        parent::_BeforeGet();
    }

    /**
     * Add ban data to all Get requests.
     *
     * @since 2.0.18
     * @access public
     *
     * @param mixed User data (array or object).
     * @param Gdn_Validation $Validation
     * @param bool $UpdateBlocks
     * @return bool Whether user is banned.
     */
    public static function checkUser($User, $Validation = null, $UpdateBlocks = false, &$BansFound = null) {
        $Bans = self::allBans();
        $Fields = ['Name' => 'Name', 'Email' => 'Email', 'IPAddress' => 'LastIPAddress'];
        $Banned = [];

        if (!$BansFound) {
            $BansFound = [];
        }

        foreach ($Bans as $Ban) {
            // Convert ban to regex.
            $Parts = explode('*', str_replace('%', '*', $Ban['BanValue']));
            $Parts = array_map('preg_quote', $Parts);
            $Regex = '`^'.implode('.*', $Parts).'$`i';

            $value = val($Fields[$Ban['BanType']], $User);
            if ($Ban['BanType'] === 'IPAddress') {
                $value = ipDecode($value);
            }

            if (preg_match($Regex, $value)) {
                $Banned[$Ban['BanType']] = true;
                $BansFound[] = $Ban;

                if ($UpdateBlocks) {
                    Gdn::sql()
                        ->update('Ban')
                        ->set('CountBlockedRegistrations', 'CountBlockedRegistrations + 1', false, false)
                        ->where('BanID', $Ban['BanID'])
                        ->put();
                }
            }
        }

        // Add the validation results.
        if ($Validation) {
            foreach ($Banned as $BanType => $Value) {
                $Validation->addValidationResult(Gdn_Form::labelCode($BanType), 'ValidateBanned');
            }
        }
        return count($Banned) == 0;
    }

    /**
     * Remove a ban.
     *
     * @param array|int $where The where clause to delete or an integer value.
     * @param array|true $options An array of options to control the delete.
     * @return bool Returns **true** on success or **false** on failure.
     */
    public function delete($where = [], $options = []) {
        if (isset($where['BanID'])) {
            $oldBan = $this->getID($where['BanID'], DATASET_TYPE_ARRAY);
        }

        $result = parent::delete($where, $options);

        if (isset($oldBan)) {
            $this->applyBan(null, $oldBan);
        }

        return $result;
    }

//   public function getBanUsers($Ban) {
//      $this->_SetBanWhere($Ban);
//
//      $Result = $this->SQL
//         ->select('u.UserID, u.Banned')
//         ->from('User u')
//         ->get()->resultArray();
//
//      return $Result;
//   }

    /**
     * Explode a banned bit mask into an array of ban constants.
     * @param int $banned The banned bit mask to explode.
     * @return array Returns an array of the set bits.
     */
    public static function explodeBans($banned) {
        $result = [];

        for ($i = 1; $i <= 8; $i++) {
            $bit = pow(2, $i - 1);
            if (($banned & $bit) === $bit) {
                $result[] = $bit;
            }
        }

        return $result;
    }


    /**
     * Check whether or not a banned value is banned for a given reason.
     *
     * @param int $banned The banned value.
     * @param int $reason The reason for the banning or an empty string to check if banned for any reason.
     * This should be one of the `BanModel::BAN_*` constants.
     * @return bool Returns true if the value is banned or false otherwise.
     */
    public static function isBanned($banned, $reason = 0) {
        if (!$reason) {
            return (bool)$banned;
        } else {
            return ($banned & $reason) > 0;
        }
    }

    /**
     * Set the banned mask value for a reason and return the new value.
     *
     * @param int $banned The current banned value.
     * @param bool $value The new ban value for the given reason.
     * @param int $reason The reason for the banning. This should be one of the `BanModel::BAN_*` constants.
     * @return int Returns the new banned value.
     */
    public static function setBanned($banned, $value, $reason) {
        if ($value) {
            $banned = $banned | $reason;
        } else {
            $banned = $banned & ~$reason;
        }
        return $banned;
    }

    /**
     * Save data about ban from form.
     *
     * @since 2.0.18
     * @access public
     *
     * @param array $formPostValues
     * @param array $settings
     */
    public function save($formPostValues, $settings = false) {
        $currentBanID = val('BanID', $formPostValues);

        // Get the current ban before saving.
        if ($currentBanID) {
            $currentBan = $this->getID($currentBanID, DATASET_TYPE_ARRAY);
        } else {
            $currentBan = null;
        }

        $this->setCounts($formPostValues);
        $banID = parent::save($formPostValues, $settings);
        $formPostValues['BanID'] = $banID;

        $this->EventArguments['CurrentBan'] = $currentBan;
        $this->EventArguments['FormPostValues'] = $formPostValues;
        $this->fireEvent('AfterSave');

        $this->applyBan($formPostValues, $currentBan);
    }

    /**
     * Change ban data on a user (ban or unban them).
     *
     * @since 2.0.18
     * @access public
     *
     * @param array $user
     * @param bool $bannedValue Whether user is banned.
     * @param array|false $ban An array representing the specific auto-ban.
     */
    public function saveUser($user, $bannedValue, $ban = false) {
        $bannedValue = (bool)$bannedValue;
        $banned = $user['Banned'];

        if (static::isBanned($banned, self::BAN_AUTOMATIC) === $bannedValue) {
            return;
        }

        $newBanned = static::setBanned($banned, $bannedValue, self::BAN_AUTOMATIC);
        Gdn::userModel()->setField($user['UserID'], 'Banned', $newBanned);
        $banningUserID = Gdn::session()->UserID;
        // This is true when a session is started and the session user has a new ip address and it matches a banning rule ip address
        if ($user['UserID'] == $banningUserID) {
            $banningUserID = val('InsertUserID', $ban, Gdn::userModel()->getSystemUserID());
        }

        // Add the activity.
        $activityModel = new ActivityModel();
        $activity = [
            'ActivityType' => 'Ban',
            'ActivityUserID' => $user['UserID'],
            'RegardingUserID' => $banningUserID,
            'NotifyUserID' => ActivityModel::NOTIFY_MODS
        ];

        $bannedString = $bannedValue ? 'banned' : 'unbanned';
        if ($ban) {
            $activity['HeadlineFormat'] = '{ActivityUserID,user} was '.$bannedString.' (based on {Data.BanType}: {Data.BanValue}).';
            $activity['Data'] = arrayTranslate($ban, ['BanType', 'BanValue']);
            $activity['Story'] = $ban['Notes'];
            $activity['RecordType'] = 'Ban';

            if (isset($ban['BanID'])) {
                $activity['BanID'] = $ban['BanID'];
            }
        } else {
            $activity['HeadlineFormat'] = '{ActivityUserID,user} was '.$bannedString.'.';
        }
        $activityModel->save($activity);
    }

    /**
     * Set number of banned users in $data.
     *
     * @since 2.0.18
     * @access public
     * @param array $data
     */
    public function setCounts(&$data) {
        // Protect against a lack of inet6_ntoa, which wasn't introduced until MySQL 5.6.3.
        try {
            $countUsers = $this->SQL
                ->select('UserID', 'count', 'CountUsers')
                ->from('User u')
                ->where($this->banWhere($data))
                ->get()->value('CountUsers', 0);
        } catch (Exception $e) {
            Logger::log(
                Logger::ERROR,
                $e->getMessage()
            );
            $countUsers = 0;
        }

        $data['CountUsers'] = $countUsers;
    }
}
