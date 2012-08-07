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
   /* @var array */
   protected static $_AllBans;

   /**
    * Defines the related database table name.
    */
   public function  __construct() {
      parent::__construct('Ban');
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
         $NewUserIDs = ConsolidateArrayValuesByKey($NewUsers, 'UserID');
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
         $OldUserIDs = ConsolidateArrayValuesByKey($OldUsers, 'UserID');
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
         if ($User['Banned'])
            continue;
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
    */
   public function SaveUser($User, $BannedValue, $Ban = FALSE) {
      $Banned = $User['Banned'];

      if ($Banned == $BannedValue)
         return;
      
      Gdn::UserModel()->SetField($User['UserID'], 'Banned', $BannedValue);

      // Add the activity.
      $ActivityModel = new ActivityModel();
      $Activity = array(
          'ActivityType' => 'Ban',
          'ActivityUserID' => $User['UserID'],
          'RegardingUserID' => Gdn::Session()->UserID,
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