<?php if (!defined('APPLICATION')) exit();
/**
 * Ban Model
 *
 * @package Dashboard
 */

/**
 * Manage banning of users.
 *
 * @since 2.0.18
 * @package Dashboard
 */
class BanModel extends Gdn_Model {
   /**
    * Manually banned by a moderator.
    */
   const BAN_MANUAL = 0x1;
   /**
    * Automatically banned by an IP ban, name, or email ban.
    */
   const BAN_AUTOMATIC = 0x2;
   /**
    * Reserved for future functionality.
    */
   const BAN_TEMPORARY = 0x4;
   /**
    * Banned by the warnings plugin.
    */
   const BAN_WARNING = 0x8;

   /* @var array */
   protected static $_AllBans;

   /**
    * @var BanModel The singleton instance of this class.
    */
   protected static $instance;

   /**
    * Defines the related database table name.
    */
   public function  __construct() {
      parent::__construct('Ban');
      $this->FireEvent('Init');
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

   /*
    * Get and store list of current bans.
    *
    * @since 2.0.18
    * @access public
    */
   public static function &AllBans() {
      if (!self::$_AllBans) {
         self::$_AllBans = Gdn::SQL()->Get('Ban')->ResultArray();
         self::$_AllBans = Gdn_DataSet::Index(self::$_AllBans, array('BanID'));
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
    * @param array $NewBan Data about the new ban.
    * @param array $OldBan Data about the old ban.
    */
   public function ApplyBan($NewBan = NULL, $OldBan = NULL) {
      if (!$NewBan && !$OldBan)
         return;

      $OldUsers = array();
      $OldUserIDs = array();

      $NewUsers = array();
      $NewUserIDs = array();

      $AllBans = $this->AllBans();

      if ($NewBan) {
         // Get a list of users affected by the new ban.
         if (isset($NewBan['BanID']))
            $AllBans[$NewBan['BanID']] = $NewBan;

         $NewUsers = $this->SQL
            ->Select('u.UserID, u.Banned')
            ->From('User u')
            ->Where($this->BanWhere($NewBan))
            ->Get()->ResultArray();
         $NewUserIDs = array_column($NewUsers, 'UserID');
      } elseif (isset($OldBan['BanID'])) {
         unset($AllBans[$OldBan['BanID']]);
      }

      if ($OldBan) {
         // Get a list of users affected by the old ban.
         $OldUsers = $this->SQL
            ->Select('u.UserID, u.LastIPAddress, u.Name, u.Email, u.Banned')
            ->From('User u')
            ->Where($this->BanWhere($OldBan))
            ->Get()->ResultArray();
         $OldUserIDs = array_column($OldUsers, 'UserID');
      }

      // Check users that need to be unbanned.
      foreach ($OldUsers as $User) {
         if (in_array($User['UserID'], $NewUserIDs))
            continue;
         // TODO check the user against the other bans.
         $this->SaveUser($User, FALSE);
      }

      // Check users that need to be banned.
      foreach ($NewUsers as $User) {
         if (self::isBanned($User['Banned'], BanModel::BAN_AUTOMATIC)) {
            continue;
         }
         $this->SaveUser($User, TRUE, $NewBan);
      }
   }

   /**
    * Ban users that meet conditions given.
    *
    * @since 2.0.18
    * @access public
    * @param array $Ban Data about the ban.
    *    Valid keys are BanType and BanValue. BanValue is what is to be banned.
    *    Valid values for BanType are email, ipaddress or name.
    */
   public function BanWhere($Ban) {
      $Result = array('u.Admin' => 0, 'u.Deleted' => 0);
      $Ban['BanValue'] = str_replace('*', '%', $Ban['BanValue']);

      switch(strtolower($Ban['BanType'])) {
         case 'email':
            $Result['u.Email like'] = $Ban['BanValue'];
            break;
         case 'ipaddress':
            $Result['u.LastIPAddress like'] = $Ban['BanValue'];
            break;
         case 'name':
            $Result['u.Name like'] = $Ban['BanValue'];
            break;
      }
      return $Result;
   }

   /**
    * Add ban data to all Get requests.
    *
    * @since 2.0.18
    * @access public
    */
   public function  _BeforeGet() {
      $this->SQL
         ->Select('Ban.*')
         ->Select('iu.Name', '', 'InsertName')
         ->Join('User iu', 'Ban.InsertUserID = iu.UserID', 'left');

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
   public static function CheckUser($User, $Validation = NULL, $UpdateBlocks = FALSE, &$BansFound = NULL) {
      $Bans = self::AllBans();
      $Fields = array('Name' => 'Name', 'Email' => 'Email', 'IPAddress' => 'LastIPAddress');
      $Banned = array();

      if (!$BansFound)
         $BansFound = array();

      foreach ($Bans as $Ban) {
         // Convert ban to regex.
         $Parts = explode('*', str_replace('%', '*', $Ban['BanValue']));
         $Parts = array_map('preg_quote', $Parts);
         $Regex = '`^'.implode('.*', $Parts).'$`i';

         if (preg_match($Regex, GetValue($Fields[$Ban['BanType']], $User))) {
            $Banned[$Ban['BanType']] = TRUE;
            $BansFound[] = $Ban;

            if ($UpdateBlocks) {
               Gdn::SQL()
                  ->Update('Ban')
                  ->Set('CountBlockedRegistrations', 'CountBlockedRegistrations + 1', FALSE, FALSE)
                  ->Where('BanID', $Ban['BanID'])
                  ->Put();
            }
         }
      }

      // Add the validation results.
      if ($Validation) {
         foreach ($Banned as $BanType => $Value) {
            $Validation->AddValidationResult(Gdn_Form::LabelCode($BanType), 'ValidateBanned');
         }
      }
      return count($Banned) == 0;
   }

   /**
    * Remove a ban.
    *
    * @since 2.0.18
    * @access public
    *
    * @param array $Where
    * @param int $Limit
    * @param bool $ResetData
    */
   public function  Delete($Where = '', $Limit = FALSE, $ResetData = FALSE) {
      if (isset($Where['BanID'])) {
         $OldBan = $this->GetID($Where['BanID'], DATASET_TYPE_ARRAY);
      }

      parent::Delete($Where, $Limit, $ResetData);

      if (isset($OldBan))
         $this->ApplyBan(NULL, $OldBan);
   }

//   public function GetBanUsers($Ban) {
//      $this->_SetBanWhere($Ban);
//
//      $Result = $this->SQL
//         ->Select('u.UserID, u.Banned')
//         ->From('User u')
//         ->Get()->ResultArray();
//
//      return $Result;
//   }

   /**
    * Explode a banned bit mask into an array of ban constants.
    * @param int $banned The banned bit mask to explode.
    * @return array Returns an array of the set bits.
    */
   public static function explodeBans($banned) {
      $result = array();

      for ($i = 1; $i <= 8; $i++) {
         $bit = pow(2, $i - 1);
         if (($banned &  $bit) === $bit) {
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
    * @param array $FormPostValues
    * @param array $Settings
    */
   public function Save($FormPostValues, $Settings = FALSE) {
      $CurrentBanID = GetValue('BanID', $FormPostValues);

      // Get the current ban before saving.
      if ($CurrentBanID)
         $CurrentBan = $this->GetID($CurrentBanID, DATASET_TYPE_ARRAY);
      else
         $CurrentBan = NULL;

      $this->SetCounts($FormPostValues);
      $BanID = parent::Save($FormPostValues, $Settings);
      $FormPostValues['BanID'] = $BanID;

      $this->EventArguments['CurrentBan'] = $CurrentBan;
      $this->EventArguments['FormPostValues'] = $FormPostValues;
      $this->FireEvent('AfterSave');

      $this->ApplyBan($FormPostValues, $CurrentBan);
   }

   /**
    * Change ban data on a user (ban or unban them).
    *
    * @since 2.0.18
    * @access public
    *
    * @param array $User
    * @param bool $BannedValue Whether user is banned.
    * @param array|false $Ban An array representing the specific auto-ban.
    */
   public function SaveUser($User, $BannedValue, $Ban = FALSE) {
      $BannedValue = (bool)$BannedValue;
      $Banned = $User['Banned'];

      if (static::isBanned($Banned, self::BAN_AUTOMATIC) === $BannedValue) {
         return;
      }

      $NewBanned = static::setBanned($Banned, $BannedValue, self::BAN_AUTOMATIC);
      Gdn::UserModel()->SetField($User['UserID'], 'Banned', $NewBanned);
      $BanningUserID = Gdn::Session()->UserID;
      // This is true when a session is started and the session user has a new ip address and it matches a banning rule ip address
      if ($User['UserID'] == $BanningUserID) {
         $BanningUserID = val('InsertUserID', $Ban, Gdn::UserModel()->GetSystemUserID());
      }

      // Add the activity.
      $ActivityModel = new ActivityModel();
      $Activity = array(
          'ActivityType' => 'Ban',
          'ActivityUserID' => $User['UserID'],
          'RegardingUserID' => $BanningUserID,
          'NotifyUserID' => ActivityModel::NOTIFY_MODS
          );

      $BannedString = $BannedValue ? 'banned' : 'unbanned';
      if ($Ban) {
         $Activity['HeadlineFormat'] = '{ActivityUserID,user} was '.$BannedString.' (based on {Data.BanType}: {Data.BanValue}).';
         $Activity['Data'] = ArrayTranslate($Ban, array('BanType', 'BanValue'));
         $Activity['Story'] = $Ban['Notes'];
         $Activity['RecordType'] = 'Ban';

         if (isset($Ban['BanID'])) {
            $Activity['BanID'] = $Ban['BanID'];
         }
      } else {
         $Activity['HeadlineFormat'] = '{ActivityUserID,user} was '.$BannedString.'.';
      }
      $ActivityModel->Save($Activity);
   }

   /**
    * Set number of banned users in $Data.
    *
    * @since 2.0.18
    * @access public
    * @param array $Data
    */
   public function SetCounts(&$Data) {
      $CountUsers = $this->SQL
         ->Select('UserID', 'count', 'CountUsers')
         ->From('User u')
         ->Where($this->BanWhere($Data))
         ->Get()->Value('CountUsers', 0);

      $Data['CountUsers'] = $CountUsers;
   }
}
