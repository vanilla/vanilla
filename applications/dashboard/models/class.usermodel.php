<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class UserModel extends Gdn_Model {
   const DEFAULT_CONFIRM_EMAIL = 'You need to confirm your email address before you can continue. Please confirm your email address by clicking on the following link: {/entry/emailconfirm,exurl,domain}/{User.UserID,rawurlencode}/{EmailKey,rawurlencode}';
   const USERID_KEY = 'user.{UserID}';
   const USERNAME_KEY = 'user.{Name}.name';
   const USERROLES_KEY = 'user.{UserID}.roles';
   const USERPERMISSIONS_KEY = 'user.{UserID}.permissions.{PermissionsIncrement}';
   const INC_PERMISSIONS_KEY = 'permissions.increment';
   const REDIRECT_APPROVE = 'REDIRECT_APPROVE';
   const USERNAME_REGEX_MIN = '^\/"\\\\#@\t\r\n';
   const LOGIN_COOLDOWN_KEY = 'user.login.{Source}.cooldown';
   const LOGIN_RATE_KEY = 'user.login.{Source}.rate';
   
   const LOGIN_RATE = 1;
   public $SessionColumns;
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('User');
   }

   protected function _AddEmailHeaderFooter($Message, $Data) {
      $Header = T('EmailHeader', '');
      if ($Header)
         $Message = FormatString($Header, $Data)."\n".$Message;

      $Footer = T('EmailFooter', '');
      if ($Footer)
         $Message .= "\n".FormatString($Footer, $Data);

      return $Message;
   }
   
   /**
    * 
    * @param Gdn_Controller $Controller
    */
   public function AddPasswordStrength($Controller) {
      $Controller->AddJsFile('password.js');
      $Controller->AddDefinition('MinPassLength', C('Garden.Registration.MinPasswordLength'));
      $Controller->AddDefinition('PasswordTranslations', T('Password Translations', 'Too Short,Contains Username,Very Weak,Weak,Ok,Good,Strong'));
   }
   
   public function Ban($UserID, $Options) {
      $this->SetField($UserID, 'Banned', TRUE);
      
      $LogID = FALSE;
      if (GetValue('DeleteContent', $Options)) {
         $Options['Log'] = 'Ban';
         $LogID = $this->DeleteContent($UserID, $Options);
      }
      
      if ($LogID) {
         $this->SaveAttribute($UserID, 'BanLogID', $LogID);
      }
      
      if (GetValue('AddActivity', $Options, TRUE)) {
         switch (GetValue('Reason', $Options, '')) {
            case '':
               $Story = NULL;
               break;
            case 'Spam':
               $Story = T('Banned for spamming.');
               break;
            case 'Abuse':
               $Story = T('Banned for being abusive.');
               break;
            default:
               $Story = $Options['Reason'];
               break;
         }
         
         $Activity = array(
             'ActivityType' => 'Ban',
             'NotifyUserID' => ActivityModel::NOTIFY_MODS,
             'ActivityUserID' => $UserID,
             'RegardingUserID' => Gdn::Session()->UserID,
             'HeadlineFormat' => T('HeadlineFormat.Ban', '{RegardingUserID,You} banned {ActivityUserID,you}.'),
             'Story' => $Story,
             'Data' => array('LogID' => $LogID));
         
         $ActivityModel = new ActivityModel();
         $ActivityModel->Save($Activity);
      }
   }
   
   /**
    * Checks the specified user's for the given permission. Returns a boolean 
    * value indicating if the action is permitted.
    *
    * @param mixed $User The user to check
    * @param mixed $Permission The permission (or array of permissions) to check.
    * @param int $JunctionID The JunctionID associated with $Permission (ie. A discussion category identifier).
	 * @return boolean
    */
   public function CheckPermission($User, $Permission, $Options = array()) {
      if (is_numeric($User)) {
         $User = $this->GetID($User);
      }
      $User = (object)$User;
      
      if ($User->Banned || $User->Deleted)
         return FALSE;
      
      if ($User->Admin)
         return TRUE;
      
      // Grab the permissions for the user.
      if ($User->UserID == 0)
         $Permissions = $this->DefinePermissions(0, FALSE);
      elseif (is_array($User->Permissions))
         $Permissions = $User->Permissions;
      else {
         $Permissions = $this->DefinePermissions($User->UserID, FALSE);
      }
      
      // TODO: Check for junction table permissions.
      $Result = in_array($Permission, $Permissions) || array_key_exists($Permission, $Permissions);
      return $Result;
   }
   
   /**
    * Merge the old user into the new user.
    * 
    * @param int $OldUserID
    * @param int $NewUserID
    */
   public function Merge($OldUserID, $NewUserID) {
      $OldUser = $this->GetID($OldUserID, DATASET_TYPE_ARRAY);
      $NewUser = $this->GetID($NewUserID, DATASET_TYPE_ARRAY);
      
      if (!$OldUser || !$NewUser) {
         throw new Gdn_UserException("Could not find one or both users to merge.");
      }
      
      $Map = array('UserID', 'Name', 'Email', 'CountVisits', 'CountDiscussions', 'CountComments');
      
      $Result = array('MergeID' => NULL, 'Before' => array(
         'OldUser' => ArrayTranslate($OldUser, $Map),
         'NewUser' => ArrayTranslate($NewUser, $Map)));
      
      // Start the merge.
      $MergeID = $this->MergeStart($OldUserID, $NewUserID);
      
      // Copy all discussions from the old user to the new user.
      $this->MergeCopy($MergeID, 'Discussion', 'InsertUserID', $OldUserID, $NewUserID);
      
      // Copy all the comments from the old user to the new user.
      $this->MergeCopy($MergeID, 'Comment', 'InsertUserID', $OldUserID, $NewUserID);
      
      // Copy all of the activities.
      $this->MergeCopy($MergeID, 'Activity', 'NotifyUserID', $OldUserID, $NewUserID);
      $this->MergeCopy($MergeID, 'Activity', 'InsertUserID', $OldUserID, $NewUserID);
      $this->MergeCopy($MergeID, 'Activity', 'ActivityUserID', $OldUserID, $NewUserID);
      
      // Copy all of the activity comments.
      $this->MergeCopy($MergeID, 'ActivityComment', 'InsertUserID', $OldUserID, $NewUserID);
      
      // Copy all conversations.
      $this->MergeCopy($MergeID, 'Conversation', 'InsertUserID', $OldUserID, $NewUserID);
      $this->MergeCopy($MergeID, 'ConversationMessage', 'InsertUserID', $OldUserID, $NewUserID, 'MessageID');
      $this->MergeCopy($MergeID, 'UserConversation', 'UserID', $OldUserID, $NewUserID, 'ConversationID');
      
      $this->MergeFinish($MergeID);
      
      $OldUser = $this->GetID($OldUserID, DATASET_TYPE_ARRAY);
      $NewUser = $this->GetID($NewUserID, DATASET_TYPE_ARRAY);
      
      $Result['MergeID'] = $MergeID;
      $Result['After'] = array(
         'OldUser' => ArrayTranslate($OldUser, $Map),
         'NewUser' => ArrayTranslate($NewUser, $Map));
      
      return $Result;
   }
   
   protected function MergeCopy($MergeID, $Table, $Column, $OldUserID, $NewUserID, $PK = NULL) {
      if (!$PK)
         $PK = $Table.'ID';
      
      // Insert the columns to the bak table.
      $Sql = "insert ignore GDN_UserMergeItem(`MergeID`, `Table`, `Column`, `RecordID`, `OldUserID`, `NewUserID`)
         select :MergeID, :Table, :Column, `$PK`, :OldUserID, :NewUserID
         from `GDN_$Table` t
         where t.`$Column` = :OldUserID2";
      Gdn::SQL()->Database->Query($Sql,
         array(':MergeID' => $MergeID, ':Table' => $Table, ':Column' => $Column, 
            ':OldUserID' => $OldUserID, ':NewUserID' => $NewUserID, ':OldUserID2' => $OldUserID));
      
      Gdn::SQL()->Options('Ignore', TRUE)->Put(
         $Table,
         array($Column => $NewUserID),
         array($Column => $OldUserID));
   }
   
   protected function MergeStart($OldUserID, $NewUserID) {
      $Model = new Gdn_Model('UserMerge');
      
      // Grab the users.
      $OldUser = $this->GetID($OldUserID, DATASET_TYPE_ARRAY);
      $NewUser = $this->GetID($NewUserID, DATASET_TYPE_ARRAY);
      
      // First see if there is a record with the same merge.
      $Row = $Model->GetWhere(array('OldUserID' => $OldUserID, 'NewUserID' => $NewUserID))->FirstRow(DATASET_TYPE_ARRAY);
      if ($Row) {
         $MergeID = $Row['MergeID'];
         
         // Save this merge in the log.
         if ($Row['Attributes'])
            $Attributes = unserialize($Row['Attributes']);
         else
            $Attributes = array();
        
         $Attributes['Log'][] = array('UserID' => Gdn::Session()->UserID, 'Date' => Gdn_Format::ToDateTime());
         $Row = array('MergeID' => $MergeID, 'Attributes' => $Attributes);
      } else {
         $Row = array(
            'OldUserID' => $OldUserID,
            'NewUserID' => $NewUserID);
      }
      
      $UserSet = array();
      $OldUserSet = array();
      if (DateCompare($OldUser['DateFirstVisit'], $NewUser['DateFirstVisit']) < 0)
         $UserSet['DateFirstVisit'] = $OldUser['DateFirstVisit'];
      
      if (!isset($Row['Attributes']['User']['CountVisits'])) {
         $UserSet['CountVisits'] = $OldUser['CountVisits'] + $NewUser['CountVisits'];
         $OldUserSet['CountVisits'] = 0;
      }
      
      if (!empty($UserSet)) {
         // Save the user information on the merge record.
         foreach ($UserSet as $Key => $Value) {
            // Only save changed values that aren't already there from a previous merge.
            if ($NewUser[$Key] != $Value && !isset($Row['Attributes']['User'][$Key])) {
               $Row['Attributes']['User'][$Key] = $NewUser[$Key];
            }
         }
      }
      
      $MergeID = $Model->Save($Row);
      if (GetValue('MergeID', $Row))
         $MergeID = $Row['MergeID'];
      
      if (!$MergeID) {
         throw new Gdn_UserException($Model->Validation->ResultsText());
      }
      
      // Update the user with the new user-level data.
      $this->SetField($NewUserID, $UserSet);
      if (!empty($OldUserSet)) {
         $this->SetField($OldUserID, $OldUserSet);
      }
      
      return $MergeID;
   }
   
   protected function MergeFinish($MergeID) {
      $Row = Gdn::SQL()->GetWhere('UserMerge', array('MergeID' => $MergeID))->FirstRow(DATASET_TYPE_ARRAY);
      
      if (isset($Row['Attributes'])  && !empty($Row['Attributes'])) {
         Trace(unserialize($Row['Attributes']), 'Merge Attributes');
      }
      
      $UserIDs = array(
         $Row['OldUserID'],
         $Row['NewUserID']);
      
      foreach ($UserIDs as $UserID) {
         $this->Counts('countdiscussions', $UserID);
         $this->Counts('countcomments', $UserID);
      }
   }
   
   public function Counts($Column, $UserID = null) {
      if ($UserID) {
         $Where = array('UserID' => $UserID);
      } else
         $Where = NULL;
      
      switch (strtolower($Column)) {
         case 'countdiscussions':
            Gdn::Database()->Query(DBAModel::GetCountSQL('count', 'User', 'Discussion', 'CountDiscussions', 'DiscussionID', 'UserID', 'InsertUserID', $Where));
            break;
         case 'countcomments':
            Gdn::Database()->Query(DBAModel::GetCountSQL('count', 'User', 'Comment', 'CountComments', 'CommentID', 'UserID', 'InsertUserID', $Where));
            break;
      }
      
      if ($UserID) {
         $this->ClearCache($UserID);
      }
   }

   /**
    * Whether or not the application requires email confirmation.
    * 
    * @return bool
    */
   public static function RequireConfirmEmail() {
      return C('Garden.Registration.ConfirmEmail') && !self::NoEmail();
   }
   
   /**
    * Whether or not users have email addresses.
    * @return bool
    */
   public static function NoEmail() {
      return C('Garden.Registration.NoEmail');
   }
   
   /**
    * Unban a user.
    * @since 2.1
    * @param int $UserID
    * @param array $Options 
    */
   public function UnBan($UserID, $Options = array()) {
      $User = $this->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!$User)
         throw NotFoundException();
      
      if (!$User['Banned'])
         throw new Gdn_UserException(T("The user isn't banned."));
      
      // Unban the user.
      $this->SetField($UserID, 'Banned', FALSE);
      
      // Restore the user's content.
      if (GetValue('RestoreContent', $Options)) {
         $BanLogID = $this->GetAttribute($UserID, 'BanLogID');
         
         if ($BanLogID) {
            $LogModel = new LogModel();

            try {
               $LogModel->Restore($BanLogID);
            } catch (Exception $Ex) {
               if ($Ex->getCode() != 404)
                  throw $Ex;
            }
            $this->SaveAttribute($UserID, 'BanLogID', NULL);
         }
      }
      
      // Add an activity for the unbanning.
      if (GetValue('AddActivity', $Options, TRUE)) {
         $ActivityModel = new ActivityModel();
         
         $Story = GetValue('Story', $Options, NULL);
         
         // Notify the moderators of the unban.
         $Activity = array(
             'ActivityType' => 'Ban',
             'NotifyUserID' => ActivityModel::NOTIFY_MODS,
             'ActivityUserID' => $UserID,
             'RegardingUserID' => Gdn::Session()->UserID,
             'HeadlineFormat' => T('HeadlineFormat.Unban', '{RegardingUserID,You} unbanned {ActivityUserID,you}.'),
             'Story' => $Story);
         
         $ActivityModel->Queue($Activity);
         
         // Notify the user of the unban.
         $Activity['NotifyUserID'] = $UserID;
         $Activity['Emailed'] = ActivityModel::SENT_PENDING;
         $Activity['HeadlineFormat'] = T('HeadlineFormat.Unban.Notification', "You've been unbanned.");
         $ActivityModel->Queue($Activity, FALSE, array('Force' => TRUE));
         
         $ActivityModel->SaveQueue();
      }
   }

   public function ConfirmEmail($User, $EmailKey) {
      $Attributes = GetValue('Attributes', $User);
      $StoredEmailKey = GetValue('EmailKey', $Attributes);
      $UserID = GetValue('UserID', $User);
      
      if (!$StoredEmailKey || $EmailKey != $StoredEmailKey) {
         $this->Validation->AddValidationResult('EmailKey', '@'.T('Couldn\'t confirm email.',
            'We couldn\'t confirm your email. Check the link in the email we sent you or try sending another confirmation email.'));
         return FALSE;
      }

      // Update the user's roles.
      $DefaultRoles = C('Garden.Registration.DefaultRoles', array());
      $ConfirmRoleID = C('Garden.Registration.ConfirmEmailRole');
      $Roles = GetValue('ConfirmedEmailRoles', $Attributes, $DefaultRoles);
      if (in_array($ConfirmRoleID, $Roles)) {
//         throw new Exception('Foo!!!');
         // The user is reconfirming to the confirm email role. At least set to the default roles.
         $Roles = $DefaultRoles;
      }
      
      $this->EventArguments['ConfirmUserID'] = $UserID;
      $this->EventArguments['ConfirmUserRoles'] = &$Roles;
      $this->FireEvent('BeforeConfirmEmail');
      $this->SaveRoles($UserID, $Roles, FALSE);
      
      // Remove the email confirmation attributes.
//      unset($Attributes['EmailKey'], $Attributes['ConfirmedEmailRoles']);
      $this->SaveAttribute($UserID, array('EmailKey' => NULL, 'ConfirmedEmailRoles' => NULL));
      return TRUE;
   }
   
   public function SSO($String) {
      if (!$String)
         return;
      
      $Parts = explode(' ', $String);
      
      $String = $Parts[0];
      $Data = json_decode(base64_decode($String), TRUE);
      Trace($Data, 'RAW SSO Data');
      $Errors = 0;
      
      if (!isset($Parts[1])) {
         Trace('Missing SSO signature', TRACE_ERROR);
         $Errors++;
      }
      if (!isset($Parts[2])) {
         Trace('Missing SSO timestamp', TRACE_ERROR);
         $Errors++;
      }
      if ($Errors)
         return;
      
      $Signature = $Parts[1];
      $Timestamp = $Parts[2];
      $HashMethod = GetValue(3, $Parts, 'hmacsha1');
      $ClientID = GetValue('client_id', $Data);
      if (!$ClientID) {
         Trace('Missing SSO client_id', TRACE_ERROR);
         return;
      }
      
      $Provider = Gdn_AuthenticationProviderModel::GetProviderByKey($ClientID);
      
      if (!$Provider) {
         Trace("Unknown SSO Provider: $ClientID", TRACE_ERROR);
         return;
      }
      
      $Secret = $Provider['AssociationSecret'];
      
      // Check the signature.
      switch ($HashMethod) {
         case 'hmacsha1':
            $CalcSignature = hash_hmac('sha1', "$String $Timestamp", $Secret);
            break;
         default:
            Trace("Invalid SSO hash method $HashMethod.", TRACE_ERROR);
            return;
      }
      if ($CalcSignature != $Signature) {
         Trace("Invalid SSO signature.", TRACE_ERROR);
         return;
      }
      
      $UniqueID = $Data['uniqueid'];
      $User = ArrayTranslate($Data, array(
         'name' => 'Name', 
         'email' => 'Email',
         'photourl' => 'Photo',
         'uniqueid' => NULL,
         'client_id' => NULL), TRUE);
      
      Trace($User, 'SSO User');
      
      $UserID = Gdn::UserModel()->Connect($UniqueID, $ClientID, $User);
      return $UserID;
   }
   
   /**
    *
    * @param array $CurrentUser
    * @param array $NewUser 
    * @since 2.1
    */
   public function SynchUser($CurrentUser, $NewUser) {
      if (is_numeric($CurrentUser)) {
         $CurrentUser = $this->GetID($CurrentUser, DATASET_TYPE_ARRAY);
      }
      
      // Don't synch the user photo if they've uploaded one already.
      $Photo = GetValue('Photo', $NewUser);
      $CurrentPhoto = GetValue('Photo', $CurrentUser);
      if (FALSE
         || ($CurrentPhoto && !StringBeginsWith($CurrentPhoto, 'http')) 
         || !is_string($Photo) 
         || ($Photo && !StringBeginsWith($Photo, 'http')) 
         || strpos($Photo, '.gravatar.') !== FALSE
         || StringBeginsWith($Photo, Url('/', TRUE))) {
         unset($NewUser['Photo']);
         Trace('Not setting photo.');
      }
      
      if (C('Garden.SSO.SynchRoles')) {
         // Translate the role names to IDs.
         
         $Roles = GetValue('Roles', $NewUser, '');
         if (is_string($Roles))
            $Roles = explode(',', $Roles);
         else
            $Roles = array();
         $Roles = array_map('trim', $Roles);
         $Roles = array_map('strtolower', $Roles);
         
         $AllRoles = RoleModel::Roles();
         $RoleIDs = array();
         foreach ($AllRoles as $RoleID => $Role) {
            $Name = strtolower($Role['Name']);
            if (in_array($Name, $Roles)) {
               $RoleIDs[] = $RoleID;
            }
         }
         if (empty($RoleIDs)) {
            $RoleIDs = $this->NewUserRoleIDs();
         }
         $NewUser['RoleID'] = $RoleIDs;
      } else {
         unset($NewUser['Roles']);
         unset($NewUser['RoleID']);
      }
      
      // Save the user information.
      $NewUser['UserID'] = $CurrentUser['UserID'];
      Trace($NewUser);
      
      $Result = $this->Save($NewUser, array('NoConfirmEmail' => TRUE, 'FixUnique' => TRUE, 'SaveRoles' => isset($NewUser['RoleID'])));
      if (!$Result)
         Trace($this->Validation->ResultsText());
   }

   /** Connect a user with a foreign authentication system.
    *
    * @param string $UniqueID The user's unique key in the other authentication system.
    * @param string $ProviderKey The key of the system providing the authentication.
    * @param array $UserData Data to go in the user table.
    * @return int The new/existing user ID.
    */
   public function Connect($UniqueID, $ProviderKey, $UserData, $Options = array()) {
      Trace('UserModel->Connect()');
      
      $UserID = FALSE;
      if (!isset($UserData['UserID'])) {
         // Check to see if the user already exists.
         $Auth = $this->GetAuthentication($UniqueID, $ProviderKey);
         $UserID = GetValue('UserID', $Auth);

         if ($UserID)
            $UserData['UserID'] = $UserID;
      }
      
      $UserInserted = FALSE;
      
      if ($UserID) {
         // Save the user.
         $this->SynchUser($UserID, $UserData);
         return $UserID;
      } else {
         // The user hasn't already been connected. We want to see if we can't find the user based on some critera.
         
         // Check to auto-connect based on email address.
         if (C('Garden.SSO.AutoConnect', C('Garden.Registration.AutoConnect')) && isset($UserData['Email'])) {
            $User = $this->GetByEmail($UserData['Email']);
            Trace($User, "Autoconnect User");
            if ($User) {
               $User = (array)$User;
               // Save the user.
               $this->SynchUser($User, $UserData);
               $UserID = $User['UserID'];
            }
         }
         
         if (!$UserID) {
            // Create a new user.
//            $UserID = $this->InsertForBasic($UserData, FALSE, array('ValidateEmail' => FALSE, 'NoConfirmEmail' => TRUE));
            $UserData['Password'] = md5(microtime());
            $UserData['HashMethod'] = 'Random';
            
            TouchValue('CheckCaptcha', $Options, FALSE);
            TouchValue('NoConfirmEmail', $Options, TRUE);
            TouchValue('NoActivity', $Options, TRUE);
            
            Trace($UserData, 'Registering User');
            $UserID = $this->Register($UserData, $Options);
            $UserInserted = TRUE;
         }
         
         if ($UserID) {
            // Save the authentication.
            $this->SaveAuthentication(array(
                'UniqueID' => $UniqueID, 
                'Provider' => $ProviderKey, 
                'UserID' => $UserID
            ));
            
            if ($UserInserted && C('Garden.Registration.SendConnectEmail', TRUE)) {
               $Provider = $this->SQL->GetWhere('UserAuthenticationProvider', array('AuthenticationKey' => $ProviderKey))->FirstRow(DATASET_TYPE_ARRAY);
            }
         }
      }
      
      return $UserID;
   }
   
   public function FilterForm($Data) {
      $Data = parent::FilterForm($Data);
      $Data = array_diff_key($Data, 
         array('Admin' => 0, 'Deleted' => 0, 'CountVisits' => 0, 'CountInvitations' => 0, 'CountNotifications' => 0, 'Preferences' => 0, 
               'Permissions' => 0, 'LastIPAddress' => 0, 'AllIPAddresses' => 0, 'DateFirstVisit' => 0, 'DateLastActive' => 0, 'CountDiscussions' => 0, 'CountComments' => 0,
               'Score' => 0));
      if (!Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         $Data = array_diff_key($Data, array('Banned' => 0, 'Verified' => 0));
      }
      if (!Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         unset($Data['RankID']);
      }
      if (!Gdn::Session()->CheckPermission('Garden.Users.Edit') && !C("Garden.Profile.EditUsernames")) {
         unset($Data['Name']);
      }
      
      return $Data;
      
   }
   
   /**
    * A convenience method to be called when inserting users (because users
    * are inserted in various methods depending on registration setups).
    */
   protected function _Insert($Fields, $Options = array()) {
      $this->EventArguments['InsertFields'] =& $Fields;
      $this->FireEvent('BeforeInsertUser');
      
      // Massage the roles for email confirmation.
      if (self::RequireConfirmEmail() && !GetValue('NoConfirmEmail', $Options)) {
         $ConfirmRoleID = C('Garden.Registration.ConfirmEmailRole');
         if ($ConfirmRoleID) {
            TouchValue('Attributes', $Fields, array());
            $ConfirmationCode = RandomString(8);
            $Fields['Attributes']['EmailKey'] = $ConfirmationCode;

            if (isset($Fields['Roles'])) {
               $Fields['Attributes']['ConfirmedEmailRoles'] = $Fields['Roles'];
            }

            $Fields['Roles'] = array($ConfirmRoleID);
         }
      }

      // Make sure to encrypt the password for saving...
      if (array_key_exists('Password', $Fields) && !GetValue('HashMethod', $Fields)) {
         $PasswordHash = new Gdn_PasswordHash();
         $Fields['Password'] = $PasswordHash->HashPassword($Fields['Password']);
         $Fields['HashMethod'] = 'Vanilla';
      }
      
      // Certain configurations can allow blank email addresses.
      if (GetValue('Email', $Fields, NULL) === NULL)
         $Fields['Email'] = '';

      $Roles = GetValue('Roles', $Fields);
      unset($Fields['Roles']);
      
      if (array_key_exists('Attributes', $Fields) && !is_string($Fields['Attributes']))
            $Fields['Attributes'] = serialize($Fields['Attributes']);
      
      $UserID = $this->SQL->Insert($this->Name, $Fields);
      if (is_array($Roles)) {
         $this->SaveRoles($UserID, $Roles, FALSE);
      }

      // Approval registration requires an email confirmation.
      if ($UserID && isset($ConfirmationCode) && strtolower(C('Garden.Registration.Method')) == 'approval') {
         // Send the confirmation email.
         $this->SendEmailConfirmationEmail($UserID);
      }

      // Fire an event for user inserts
      $this->EventArguments['InsertUserID'] = $UserID;
      $this->EventArguments['InsertFields'] = $Fields;
      $this->FireEvent('AfterInsertUser');
      return $UserID;
   }
   
   /**
    * Add user data to a result set.
    *
    * @param object $Data Results we need to associate user data with.
    * @param array $Columns Database columns containing UserIDs to get data for.
    * @param array $Options Optionally pass list of user data to collect with key 'Join'.
    */
   public function JoinUsers(&$Data, $Columns, $Options = array()) {
      // Grab all of the user fields that need to be joined.
      $UserIDs = array();
      foreach ($Data as $Row) {
         foreach ($Columns as $ColumnName) {
            $ID = GetValue($ColumnName, $Row);
            if (is_numeric($ID))
               $UserIDs[$ID] = 1;
         }
      }
      
      // Get the users.
      $Users = $this->GetIDs(array_keys($UserIDs));
      
      // Get column name prefix (ex: 'Insert' from 'InsertUserID')
      $Prefixes = array();
      foreach ($Columns as $ColumnName) {
         $Prefixes[] = StringEndsWith($ColumnName, 'UserID', TRUE, TRUE);
      }
      
      // Join the user data using prefixes (ex: 'Name' for 'InsertUserID' becomes 'InsertName')
      $Join = GetValue('Join', $Options, array('Name', 'Email', 'Photo'));
      $UserPhotoDefaultUrl = function_exists('UserPhotoDefaultUrl');
      
      foreach ($Data as &$Row) {
         foreach ($Prefixes as $Px) {
            $ID = GetValue($Px.'UserID', $Row);
            if (is_numeric($ID)) {
               $User = GetValue($ID, $Users, FALSE);
               foreach ($Join as $Column) {
                  $Value = $User[$Column];
                  if ($Column == 'Photo') {
                     if (!$Value) {
                        if ($UserPhotoDefaultUrl)
                           $Value = UserPhotoDefaultUrl($User);
                     } elseif (!IsUrl($Value)) {
                        $Value = Gdn_Upload::Url(ChangeBasename($Value, 'n%s'));
                     }
                  }
                  SetValue($Px.$Column, $Row, $Value);
               }
            } else {
               foreach ($Join as $Column) {
                  SetValue($Px.$Column, $Row, NULL);
               }
            }
            
            
         }
      }
   }

   /**
    * $SafeData makes sure that the query does not return any sensitive
    * information about the user (password, attributes, preferences, etc).
    */
   public function UserQuery($SafeData = FALSE) {
      if ($SafeData) {
         $this->SQL->Select('u.UserID, u.Name, u.Photo, u.About, u.Gender, u.CountVisits, u.InviteUserID, u.DateFirstVisit, u.DateLastActive, u.DateInserted, u.DateUpdated, u.Score, u.Admin, u.Deleted, u.CountDiscussions, u.CountComments');
      } else {
         $this->SQL->Select('u.*');
      }
      $this->SQL->From('User u');
//      $this->SQL->Select('i.Name', '', 'InviteName')
//         ->From('User u')
//         ->Join('User as i', 'u.InviteUserID = i.UserID', 'left');
   }

   public function DefinePermissions($UserID, $Serialize = TRUE) {
      if (Gdn::Cache()->ActiveEnabled()) {
         $PermissionsIncrement = $this->GetPermissionsIncrement();
         $UserPermissionsKey = FormatString(self::USERPERMISSIONS_KEY, array(
            'UserID' => $UserID,
            'PermissionsIncrement' => $PermissionsIncrement
         ));
         
         $CachePermissions = Gdn::Cache()->Get($UserPermissionsKey);
         if ($CachePermissions !== Gdn_Cache::CACHEOP_FAILURE) 
            return $CachePermissions;
      }
      
      $Data = Gdn::PermissionModel()->CachePermissions($UserID);
      
      $Permissions = array();
      foreach($Data as $i => $Row) {
         $JunctionTable = $Row['JunctionTable'];
         $JunctionColumn = $Row['JunctionColumn'];
         $JunctionID = $Row['JunctionID'];
         unset($Row['JunctionColumn'], $Row['JunctionColumn'], $Row['JunctionID'], $Row['RoleID'], $Row['PermissionID']);
         
         foreach($Row as $PermissionName => $Value) {
            if($Value == 0)
               continue;
            
            if(is_numeric($JunctionID) && $JunctionID !== NULL) {
               if (!array_key_exists($PermissionName, $Permissions))
                  $Permissions[$PermissionName] = array();
                  
               if (!is_array($Permissions[$PermissionName]))
                  $Permissions[$PermissionName] = array();
                  
               $Permissions[$PermissionName][] = $JunctionID;
            } else {
               $Permissions[] = $PermissionName;
            }
         }
      }
      
      // Throw a fatal error if the user has no permissions
      // if (count($Permissions) == 0)
      //    trigger_error(ErrorMessage('The requested user ('.$this->UserID.') has no permissions.', 'Session', 'Start'), E_USER_ERROR);

      $PermissionsSerialized = NULL;
      if (Gdn::Cache()->ActiveEnabled()) {
         Gdn::Cache()->Store($UserPermissionsKey, $Permissions);
      } else {
         // Save the permissions to the user table
         $PermissionsSerialized = Gdn_Format::Serialize($Permissions);
         if ($UserID > 0)
            $this->SQL->Put('User', array('Permissions' => $PermissionsSerialized), array('UserID' => $UserID));
      }
      
      if ($Serialize && is_null($PermissionsSerialized))
         $PermissionsSerialized = Gdn_Format::Serialize($Permissions);
      
      return $Serialize ? $PermissionsSerialized : $Permissions;
   }

   /**
    * Default Gdn_Model::Get() behavior.
    * 
    * Prior to 2.0.18 it incorrectly behaved like GetID.
    * This method can be deleted entirely once it's been deprecated long enough.
    *
    * @since 2.0.0
    * @return object DataSet
    */
   public function Get($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      if (is_numeric($OrderFields)) {
         // They're using the old version that was a misnamed GetID()
         Deprecated('UserModel->Get()', 'UserModel->GetID()');
         $Result = $this->GetID($OrderFields);
      }
      else {
         $Result = parent::Get($OrderFields, $OrderDirection, $Limit, $Offset);
      }
      return $Result;  
   }
   
   public function GetByUsername($Username) {
		if ($Username == '')
		 	return FALSE;
      
      // Check page cache, then memcached
      $User = $this->GetUserFromCache($Username, 'name');
      
      if ($User === Gdn_Cache::CACHEOP_FAILURE) {
         $this->UserQuery();
         $User = $this->SQL->Where('u.Name', $Username)->Get()->FirstRow(DATASET_TYPE_ARRAY);
         if ($User) {
            // If success, build more data, then cache user
            $this->SetCalculatedFields($User);
            $this->UserCache($User);
         }
      }
      
      // By default, FirstRow() gives stdClass
      if ($User !== FALSE)
         $User = (object)$User;
      
      return $User;
   }
	public function GetByEmail($Email) {
      $this->UserQuery();
      $User = $this->SQL->Where('u.Email', $Email)->Get()->FirstRow();
      $this->SetCalculatedFields($User);
      return $User;
   }
   
   public function GetByRole($Role) {
      $RoleID = $Role; // Optimistic
      if (is_string($Role)) {
         $RoleModel = new RoleModel();
         $Roles = $RoleModel->GetArray();
         $RolesByName = array_flip($Roles);
         
         $RoleID = GetValue($Role, $RolesByName, NULL);
         
         // No such role
         if (is_null($RoleID)) return new Gdn_DataSet();
      }

      return $this->SQL->Select('u.*')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID')
         ->Where('ur.RoleID', $RoleID, TRUE, FALSE)
//         ->GroupBy('UserID')
         ->OrderBy('DateInserted', 'desc')
         ->Get();
   }

   public function GetActiveUsers($Limit = 5) {
      $UserIDs = $this->SQL
         ->Select('UserID')
         ->From('User')
         ->OrderBy('DateLastActive', 'desc')
         ->Limit($Limit, 0)
         ->Get();
      $UserIDs = ConsolidateArrayValuesByKey($UserIDs, 'UserID');
      
      $Data = $this->SQL->GetWhere('User', array('UserID' => $UserIDs), 'DateLastActive', 'desc');
      return $Data;
   }

   public function GetApplicantCount() {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);
      if ($ApplicantRoleID == 0)
         return 0;

      $Result = $this->SQL->Select('u.UserID', 'count', 'ApplicantCount')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID')
         ->Where('ur.RoleID', $ApplicantRoleID, TRUE, FALSE)
         ->Get()->Value('ApplicantCount', 0);
      return $Result;
   }
   
   /**
    * Returns all users in the applicant role
    */
   public function GetApplicants() {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);
      if ($ApplicantRoleID == 0)
         return new Gdn_DataSet();

      return $this->SQL->Select('u.*')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID')
         ->Where('ur.RoleID', $ApplicantRoleID, TRUE, FALSE)
//         ->GroupBy('UserID')
         ->OrderBy('DateInserted', 'desc')
         ->Get();
   }

   /**
    * Get the a user authentication row.
    *
    * @param string $UniqueID The unique ID of the user in the foreign authentication scheme.
    * @param string $Provider The key of the provider.
    * @return array|false
    */
   public function GetAuthentication($UniqueID, $Provider) {
      return $this->SQL->GetWhere('UserAuthentication',
         array('ForeignUserKey' => $UniqueID, 'ProviderKey' => $Provider))->FirstRow(DATASET_TYPE_ARRAY);
   }

   public function GetCountLike($Like = FALSE) {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);

      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u')
         ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');
      if (is_array($Like)){
         $this->SQL
				->BeginWhereGroup()
				->OrLike($Like, '', 'right')
				->EndWhereGroup();
		}
		$this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null');
		
		$Data =  $this->SQL->Get()->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }

   public function GetCountWhere($Where = FALSE) {
      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID and ur.RoleID = '.(int)C('Garden.Registration.ApplicantRoleID', 0), 'left');
		
		if (is_array($Where))
         $this->SQL->Where($Where);

		$Data = $this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null')
         ->Get()
         ->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }

   public function GetID($ID, $DatasetType = DATASET_TYPE_OBJECT) {
      if (!$ID)
         return FALSE;
      
      // Check page cache, then memcached
      $User = $this->GetUserFromCache($ID, 'userid');
      
      // If not, query DB
      if ($User === Gdn_Cache::CACHEOP_FAILURE) {
         $User = parent::GetID($ID, DATASET_TYPE_ARRAY);
         
         if ($User) {
            // If success, build more data, then cache user
            $this->SetCalculatedFields($User);
            $this->UserCache($User);
         }
      }
      
      // Allow FALSE returns
      if ($User === FALSE || is_null($User))
         return FALSE;
      
      if (is_array($User) && $DatasetType == DATASET_TYPE_OBJECT)
         $User = (object)$User;
      
      if (is_object($User) && $DatasetType == DATASET_TYPE_ARRAY)
         $User = (array)$User;
      
      $this->EventArguments['LoadedUser'] = &$User;
      $this->FireEvent('AfterGetID');
      
      return $User;
   }
   
   public function GetIDs($IDs, $SkipCacheQuery = FALSE) {
      
      $DatabaseIDs = $IDs;
      $Data = array();
      
      if (!$SkipCacheQuery) {
         $Keys = array();
         // Make keys for cache query
         foreach ($IDs as $UserID) {
            if (!$UserID) continue;
            
            $Keys[] = FormatString(self::USERID_KEY, array('UserID' => $UserID));
         }
         
         // Query cache layer
         $CacheData = Gdn::Cache()->Get($Keys);
         if (!is_array($CacheData))
            $CacheData = array();
            
         foreach ($CacheData as $RealKey => $User) {
            $ResultUserID = GetValue('UserID', $User);
            $Data[$ResultUserID] = $User;
         }
         
         //echo "from cache:\n";
         //print_r($Data);
         
         $DatabaseIDs = array_diff($DatabaseIDs, array_keys($Data));
         unset($CacheData);
      }
      
      // Clean out bogus blank entries
      $DatabaseIDs = array_diff($DatabaseIDs, array(NULL, ''));
      
      // If we are missing any users from cache query, fill em up here
      if (sizeof($DatabaseIDs)) {
         $DatabaseData = $this->SQL->WhereIn('UserID', $DatabaseIDs)->GetWhere('User')->Result(DATASET_TYPE_ARRAY);
         $DatabaseData = Gdn_DataSet::Index($DatabaseData, 'UserID');
         
         //echo "from DB:\n";
         //print_r($DatabaseData);
         
         foreach ($DatabaseData as $DatabaseUserID => $DatabaseUser) {
            $Data[$DatabaseUserID] = $DatabaseUser;
            $this->SetCalculatedFields($DatabaseUser);
            $Result = $this->UserCache($DatabaseUser);
         }
      }
      
      $this->EventArguments['RequestedIDs'] = $IDs;
      $this->EventArguments['LoadedUsers'] = &$Data;
      $this->FireEvent('AfterGetIDs');
      
      return $Data;
   }

   public function GetLike($Like = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);

      $this->UserQuery();
      $this->SQL
         ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');

      if (is_array($Like)) {
         $this->SQL
				->BeginWhereGroup()
				->OrLike($Like, '', 'right')
				->EndWhereGroup();
		}
		
      return $this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null')
         ->OrderBy($OrderFields, $OrderDirection)
         ->Limit($Limit, $Offset)
         ->Get();
   }

   /**
    * Retries UserMeta information for a UserID / Key pair
    *
    * This method takes a $UserID or array of $UserIDs, and a $Key. It converts the
    * $Key to fully qualified format and then queries for the associated value(s). $Key
    * can contain SQL wildcards, in which case multiple results can be returned.
    *
    * If $UserID is an array, the return value will be a multi dimensional array with the first
    * axis containing UserIDs and the second containing fully qualified UserMetaKeys, associated with
    * their values.
    *
    * If $UserID is a scalar, the return value will be a single dimensional array of $UserMetaKey => $Value
    * pairs.
    *
    * @param $UserID integer UserID or array of UserIDs.
    * @param $Key string relative user meta key.
    * @return array results or $Default
    */
   public static function GetMeta($UserID, $Key, $Prefix = '', $Default = '') {
      $Sql = Gdn::SQL()
         ->Select('*')
         ->From('UserMeta u');

      if (is_array($UserID))
         $Sql->WhereIn('u.UserID', $UserID);
      else
         $Sql->Where('u.UserID', $UserID);

      if (strpos($Key, '%') !== FALSE)
         $Sql->Like('u.Name', $Key, 'none');
      else
         $Sql->Where('u.Name', $Key);

      $Data = $Sql->Get()->ResultArray();

      if (is_array($UserID))
         $Result = array_fill_keys($UserID, array());
      else {
         if (strpos($Key, '%') === FALSE)
            $Result = array(StringBeginsWith($Key, $Prefix, FALSE, TRUE) => $Default);
         else
            $Result = array();
      }

      foreach ($Data as $Row) {
         $Name = StringBeginsWith($Row['Name'], $Prefix, FALSE, TRUE);

         if (is_array($UserID)) {
            $Result[$Row['UserID']][$Name] = $Row['Value'];
         } else {
            $Result[$Name] = $Row['Value'];
         }
      }

      return $Result;
   }

   public function GetRoles($UserID) {
      $UserRolesKey = FormatString(self::USERROLES_KEY, array('UserID' => $UserID));
      $RolesDataArray = Gdn::Cache()->Get($UserRolesKey);
      
      if ($RolesDataArray === Gdn_Cache::CACHEOP_FAILURE) {
         $RolesDataArray = $this->SQL->GetWhere('UserRole', array('UserID' => $UserID))->ResultArray();
         $RolesDataArray = ConsolidateArrayValuesByKey($RolesDataArray, 'RoleID');
      }
      
      $Result = array();
      foreach ($RolesDataArray as $RoleID) {
         $Result[] = RoleModel::Roles($RoleID, TRUE);
      }
      return new Gdn_DataSet($Result);
   }

   public function GetSession($UserID, $Refresh = FALSE) {
      // Ask for the user. This will check cache first.
      $User = $this->GetID($UserID, DATASET_TYPE_OBJECT);
      
      // TIM: Removed on Jul 14, 2011 for PennyArcade
      //
      //$this->FireEvent('SessionQuery');
      //if (is_array($this->SessionColumns)) {
      //   $this->SQL->Select($this->SessionColumns);
      //}
      //$User = $this->SQL
      //   ->Get()
      //   ->FirstRow();

      if ($User && ($User->Permissions == '' || Gdn::Cache()->ActiveEnabled()))
         $User->Permissions = $this->DefinePermissions($UserID);
      
      // Remove secret info from session
      unset($User->Password, $User->HashMethod);
      
      return $User;
   }

   /**
    * Retrieve a summary of "safe" user information for external API calls.
    */
   public function GetSummary($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $this->UserQuery(TRUE);
      return $this->SQL
         ->Where('u.Deleted', 0)
         ->OrderBy($OrderFields, $OrderDirection)
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   /**
    * Retrieves a "system user" id that can be used to perform non-real-person tasks.
    */
   public function GetSystemUserID() {
      $SystemUserID = C('Garden.SystemUserID');
      if ($SystemUserID)
         return $SystemUserID;
      
      $SystemUser = array(
         'Name' => T('System'),
         'Photo' => Asset('/applications/dashboard/design/images/usericon.png', TRUE),
         'Password' => RandomString('20'),
         'HashMethod' => 'Random',
         'Email' => 'system@domain.com',
         'DateInserted' => Gdn_Format::ToDateTime(),
         'Admin' => '2'
      );
      
      $this->EventArguments['SystemUser'] = &$SystemUser;
      $this->FireEvent('BeforeSystemUser');
      
      $SystemUserID = $this->SQL->Insert($this->Name, $SystemUser);
      
      SaveToConfig('Garden.SystemUserID', $SystemUserID);
      return $SystemUserID;
   }
   
   /**
    * Add points to a user's total.
    * 
    * @since 2.1.0
    * @access public
    */
   public static function GivePoints($UserID, $Points, $Source = 'Other', $Timestamp = FALSE) {
      if (!$Timestamp)
         $Timestamp = time();
      
      // Increment source points for the user.
      self::_GivePoints($UserID, $Points, 'a', $Source);
      
      // Increment total points for the user.
      self::_GivePoints($UserID, $Points, 'w', 'Total', $Timestamp);
      self::_GivePoints($UserID, $Points, 'm', 'Total', $Timestamp);
      self::_GivePoints($UserID, $Points, 'a', 'Total', $Timestamp);
      
      // Increment global daily points.
      self::_GivePoints(0, $Points, 'd', 'Total', $Timestamp);
      
      // Grab the user's total points.
      $Points = Gdn::SQL()->GetWhere('UserPoints', array('UserID' => $UserID, 'SlotType' => 'a', 'Source' => 'Total'))->Value('Points');
      
//      Gdn::Controller()->InformMessage('Points: '.$Points);
      Gdn::UserModel()->SetField($UserID, 'Points', $Points);
      
      // Fire a give points event.
      Gdn::UserModel()->EventArguments['UserID'] = $UserID;
      Gdn::UserModel()->EventArguments['Points'] = $Points;
      Gdn::UserModel()->FireEvent('GivePoints');
   }
   
   /**
    * Add points to a user's total in a specific timeslot.
    * 
    * @since 2.1.0
    * @access protected
    * @see self::GivePoints
    */
   protected static function _GivePoints($UserID, $Points, $SlotType, $Source = 'Total', $Timestamp = FALSE) {
      $TimeSlot = gmdate('Y-m-d', Gdn_Statistics::TimeSlotStamp($SlotType, $Timestamp));
      
      $Px = Gdn::Database()->DatabasePrefix;
      $Sql = "insert {$Px}UserPoints (UserID, SlotType, TimeSlot, Source, Points)
         values (:UserID, :SlotType, :TimeSlot, :Source, :Points)
         on duplicate key update Points = Points + :Points1";
      
      Gdn::Database()->Query($Sql, array(
          ':UserID' => $UserID, 
          ':Points' => $Points, 
          ':SlotType' => $SlotType, 
          ':Source' => $Source,
          ':TimeSlot' => $TimeSlot, 
          ':Points1' => $Points));
   }

   public function Register($FormPostValues, $Options = array()) {
      $Valid = TRUE;
      $FormPostValues['LastIPAddress'] = Gdn::Request()->IpAddress();
      
      // Throw an error if the registering user has an active session
      if (Gdn::Session()->IsValid())
         $this->Validation->AddValidationResult('Name', 'You are already registered.');

      // Check for banning first.
      $Valid = BanModel::CheckUser($FormPostValues, NULL, TRUE);
      if (!$Valid) {
         $this->Validation->AddValidationResult('UserID', 'Sorry, permission denied.');
      }

      // Throw an event to allow plugins to block the registration.
      unset($this->EventArguments['User']);
      $this->EventArguments['User'] = $FormPostValues;
      $this->EventArguments['Valid'] =& $Valid;
      $this->FireEvent('BeforeRegister');

      if (!$Valid)
         return FALSE; // plugin blocked registration

      switch (strtolower(C('Garden.Registration.Method'))) {
         case 'captcha':
            $UserID = $this->InsertForBasic($FormPostValues, GetValue('CheckCaptcha', $Options, TRUE), $Options);
            break;
         case 'approval':
            $UserID = $this->InsertForApproval($FormPostValues, $Options);
            break;
         case 'invitation':
            $UserID = $this->InsertForInvite($FormPostValues, $Options);
            break;
         case 'closed':
            $UserID = FALSE;
            $this->Validation->AddValidationResult('Registration', 'Registration is closed.');
            break;
         case 'basic':
         default:
            $UserID = $this->InsertForBasic($FormPostValues, GetValue('CheckCaptcha', $Options, FALSE), $Options);
            break;
      }
      
      if ($UserID) {
         $this->EventArguments['UserID'] = $UserID;
         $this->FireEvent('AfterRegister');
      }
      return $UserID;
   }
   
   public function RemovePicture($UserID) {
      // Grab the current photo.
      $User = $this->GetID($UserID, DATASET_TYPE_ARRAY);
      if ($Photo = $User['Photo']) {
         $ProfilePhoto = ChangeBasename($Photo, 'p%s');
         $Upload = new Gdn_Upload();
         $Upload->Delete($ProfilePhoto);
         
         $this->SetField($UserID, 'Photo', NULL);
      }
   }

   public function ProfileCount($User, $Column) {
      if (is_numeric($User))
         $User = $this->SQL->GetWhere('User', array('UserID' => $User))->FirstRow(DATASET_TYPE_ARRAY);
      elseif (is_string($User))
         $User = $this->SQL->GetWhere('User', array('Name' => $User))->FirstRow(DATASET_TYPE_ARRAY);
      elseif (is_object($User))
         $User = (array)$User;
      
      if (!$User)
         return FALSE;

      if (array_key_exists($Column, $User) && $User[$Column] === NULL) {
            $UserID = $User['UserID'];
            switch ($Column) {
               case 'CountComments':
                  $Count = $this->SQL->GetCount('Comment', array('InsertUserID' => $UserID));
                  $this->SetField($UserID, 'CountComments', $Count);
                  break;
               case 'CountDiscussions':
                  $Count = $this->SQL->GetCount('Discussion', array('InsertUserID' => $UserID));
                  $this->SetField($UserID, 'CountDiscussions', $Count);
                  break;
               case 'CountBookmarks':
                  $Count = $this->SQL->GetCount('UserDiscussion', array('UserID' => $UserID, 'Bookmarked' => '1'));
                  $this->SetField($UserID, 'CountBookmarks', $Count);
                  break;
               default:
                  $Count = FALSE;
                  break;
            }
            return $Count;
      } elseif ($User[$Column]) {
         return $User[$Column];
      } else {
         return FALSE;
      }
   }

   /**
    * Generic save procedure.
    */
   public function Save($FormPostValues, $Settings = FALSE) {
      // See if the user's related roles should be saved or not.
      $SaveRoles = GetValue('SaveRoles', $Settings);

      // Define the primary key in this model's table.
      $this->DefineSchema();
      
      

      // Custom Rule: This will make sure that at least one role was selected if saving roles for this user.
      if ($SaveRoles) {
         $this->Validation->AddRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
         // $this->Validation->AddValidationField('RoleID', $FormPostValues);
         $this->Validation->ApplyRule('RoleID', 'OneOrMoreArrayItemRequired');
      }

      // Make sure that the checkbox val for email is saved as the appropriate enum
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');
      
      if (array_key_exists('Banned', $FormPostValues))
         $FormPostValues['Banned'] = ForceBool($FormPostValues['Banned'], '0', '1', '0');

      // Validate the form posted values
      $UserID = GetValue('UserID', $FormPostValues);
      $Insert = $UserID > 0 ? FALSE : TRUE;
      if ($Insert) {
         $this->AddInsertFields($FormPostValues);
      } else {
         $this->AddUpdateFields($FormPostValues);
      }
      
      $this->EventArguments['FormPostValues'] = $FormPostValues;
      $this->FireEvent('BeforeSaveValidation');

      $RecordRoleChange = TRUE;
      
      if ($UserID && GetValue('FixUnique', $Settings)) {
         $UniqueValid = $this->ValidateUniqueFields(GetValue('Name', $FormPostValues), GetValue('Email', $FormPostValues), $UserID, TRUE);
         if (!$UniqueValid['Name'])
            unset($FormPostValues['Name']);
         if (!$UniqueValid['Email'])
            unset($FormPostValues['Email']);
         $UniqueValid = TRUE;
      } else {
         $UniqueValid = $this->ValidateUniqueFields(GetValue('Name', $FormPostValues), GetValue('Email', $FormPostValues), $UserID);
      }
      
      // Add & apply any extra validation rules:
      if (array_key_exists('Email', $FormPostValues) && GetValue('ValidateEmail', $Settings, TRUE))
         $this->Validation->ApplyRule('Email', 'Email');
      
      if ($this->Validate($FormPostValues, $Insert) && $UniqueValid) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $RoleIDs = GetValue('RoleID', $Fields, 0);
         $Username = GetValue('Name', $Fields);
         $Email = GetValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         // Remove the primary key from the fields collection before saving
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
         if (in_array('AllIPAddresses', $Fields) && is_array($Fields)) {
            $Fields['AllIPAddresses'] = implode(',', $Fields['AllIPAddresses']);
         }
         
         if (!$Insert && array_key_exists('Password', $Fields)) {
            // Encrypt the password for saving only if it won't be hashed in _Insert()
            $PasswordHash = new Gdn_PasswordHash();
            $Fields['Password'] = $PasswordHash->HashPassword($Fields['Password']);
            $Fields['HashMethod'] = 'Vanilla';
         }
         
         // Check for email confirmation.
         if (self::RequireConfirmEmail() && !GetValue('NoConfirmEmail', $Settings)) {
            if (isset($Fields['Email']) && $UserID == Gdn::Session()->UserID && $Fields['Email'] != Gdn::Session()->User->Email && !Gdn::Session()->CheckPermission('Garden.Users.Edit')) {
               $User = Gdn::Session()->User;
               $Attributes = Gdn::Session()->User->Attributes;
               
               $ConfirmEmailRoleID = C('Garden.Registration.ConfirmEmailRole');
               if (RoleModel::Roles($ConfirmEmailRoleID)) {
                  // The confirm email role is set and it exists so go ahead with the email confirmation.
                  $EmailKey = TouchValue('EmailKey', $Attributes, RandomString(8));
                  
                  if (isset($Attributes['ConfirmedEmailRoles']) && !in_array($ConfirmEmailRoleID, $Attributes['ConfirmedEmailRoles']))
                     $ConfirmedEmailRoles = $Attributes['ConfirmedEmailRoles'];
                  elseif ($RoleIDs)
                     $ConfirmedEmailRoles = $RoleIDs;
                  else
                     $ConfirmedEmailRoles = ConsolidateArrayValuesByKey($this->GetRoles($UserID), 'RoleID');
                  $Attributes['ConfirmedEmailRoles'] = $ConfirmedEmailRoles;

                  $RoleIDs = (array)C('Garden.Registration.ConfirmEmailRole');

                  $SaveRoles = TRUE;
                  $Fields['Attributes'] = serialize($Attributes);
               }
            } 
         }
         
         $this->EventArguments['Fields'] = $Fields;
         $this->FireEvent('BeforeSave');
         
         // Check the validation results again in case something was added during the BeforeSave event.
         if (count($this->Validation->Results()) == 0) {
            // If the primary key exists in the validated fields and it is a
            // numeric value greater than zero, update the related database row.
            if ($UserID > 0) {
               // If they are changing the username & email, make sure they aren't
               // already being used (by someone other than this user)
               if (ArrayValue('Name', $Fields, '') != '' || ArrayValue('Email', $Fields, '') != '') {
                  if (!$this->ValidateUniqueFields($Username, $Email, $UserID))
                     return FALSE;
               }
               
               if (array_key_exists('Attributes', $Fields) && !is_string($Fields['Attributes'])) {
                  $Fields['Attributes'] = serialize($Fields['Attributes']);
               }
   
               $this->SQL->Put($this->Name, $Fields, array($this->PrimaryKey => $UserID));
   
               // Record activity if the person changed his/her photo.
               $Photo = ArrayValue('Photo', $FormPostValues);
               if ($Photo !== FALSE) {
                  if (GetValue('CheckExisting', $Settings)) {
                     $User = $this->GetID($UserID);
                     $OldPhoto = GetValue('Photo', $User);
                  }

                  if (isset($OldPhoto) && $OldPhoto != $Photo) {
                     if (strpos($Photo, '//'))
                        $PhotoUrl = $Photo;
                     else
                        $PhotoUrl = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));

                     $ActivityModel = new ActivityModel();
                     if ($UserID == Gdn::Session()->UserID) {
                        $HeadlineFormat = T('HeadlineFormat.PictureChange', '{RegardingUserID,You} changed {ActivityUserID,your} profile picture.');
                     } else {
                        $HeadlineFormat = T('HeadlineFormat.PictureChange.ForUser', '{RegardingUserID,You} changed the profile picture for {ActivityUserID,user}.');
                     }
                     
                     $ActivityModel->Save(array(
                         'ActivityUserID' => $UserID,
                         'RegardingUserID' => Gdn::Session()->UserID,
                         'ActivityType' => 'PictureChange',
                         'HeadlineFormat' => $HeadlineFormat,
                         'Story' => Img($PhotoUrl, array('alt' => T('Thumbnail')))
                         ));
                  }
               }
   
            } else {
               $RecordRoleChange = FALSE;
               if (!$this->ValidateUniqueFields($Username, $Email))
                  return FALSE;
   
               // Define the other required fields:
               $Fields['Email'] = $Email;
               
               $Fields['Roles'] = $RoleIDs;
               // Make sure that the user is assigned to one or more roles:
               $SaveRoles = FALSE;
   
               // And insert the new user.
               $UserID = $this->_Insert($Fields, $Settings);
   
               if ($UserID) {
                  // Report that the user was created.
                  $ActivityModel = new ActivityModel();
                  $ActivityModel->Save(array(
                      'ActivityType' => 'Registration',
                      'ActivityUserID' => $UserID,
                      'HeadlineFormat' => T('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                      'Story' => T('Welcome Aboard!')),
                      FALSE,
                      array('GroupBy' => 'ActivityTypeID'));
                  
                  // Report the creation for mods.
                  $ActivityModel->Save(array(
                      'ActivityType' => 'Registration',
                      'ActivityUserID' => Gdn::Session()->UserID,
                      'RegardingUserID' => $UserID,
                      'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                      'HeadlineFormat' => T('HeadlineFormat.AddUser', '{ActivityUserID,user} added an account for {RegardingUserID,user}.')));
               }
            }
            // Now update the role settings if necessary.
            if ($SaveRoles) {
               // If no RoleIDs were provided, use the system defaults
               if (!is_array($RoleIDs))
                  $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles');
   
               $this->SaveRoles($UserID, $RoleIDs, $RecordRoleChange);
            }

            // Send the confirmation email.
            if (isset($EmailKey)) {
               $this->SendEmailConfirmationEmail((array)Gdn::Session()->User);
            }

            $this->EventArguments['UserID'] = $UserID;
            $this->FireEvent('AfterSave');
         } else {
            $UserID = FALSE;
         }
      } else {
//         decho($this->Validation->ResultsText());
         $UserID = FALSE;
      }
      
      // Clear cached user data
      if (!$Insert && $UserID) {
         $this->ClearCache($UserID, array('user'));
      }
      
      return $UserID;
   }
   
   /**
    * Create an admin user account
    */
   public function SaveAdminUser($FormPostValues) {
      $UserID = 0;

      // Add & apply any extra validation rules:
      $Name = GetValue('Name', $FormPostValues, '');
      $FormPostValues['Email'] = GetValue('Email', $FormPostValues, strtolower($Name.'@'.Gdn_Url::Host()));
      $FormPostValues['ShowEmail'] = '0';
      $FormPostValues['TermsOfService'] = '1';
      $FormPostValues['DateOfBirth'] = '1975-09-16';
      $FormPostValues['DateLastActive'] = Gdn_Format::ToDateTime();
      $FormPostValues['DateUpdated'] = Gdn_Format::ToDateTime();
      $FormPostValues['Gender'] = 'u';
      $FormPostValues['Admin'] = '1';

      $this->AddInsertFields($FormPostValues);

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = GetValue('Name', $Fields);
         $Email = GetValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         
         // Insert the new user
         $UserID = $this->_Insert($Fields, array('NoConfirmEmail' => TRUE));
         
         if ($UserID) {
            $ActivityModel = new ActivityModel();
            $ActivityModel->Save(array(
               'ActivityUserID' => $UserID,
               'ActivityType' => 'Registration',
               'HeadlineFormat' => T('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
               'Story' => T('Welcome Aboard!')
               ),
               FALSE,
               array('GroupBy' => 'ActivityTypeID'));
         }
         
         $this->SaveRoles($UserID, array(16), FALSE);
      }
      return $UserID;
   }

   public function SaveRoles($UserID, $RoleIDs, $RecordActivity = TRUE) {
      if (is_string($RoleIDs) && !is_numeric($RoleIDs)) {
         // The $RoleIDs are a comma delimited list of role names.
         $RoleNames = array_map('trim', explode(',', $RoleIDs));
         $RoleIDs = $this->SQL
            ->Select('r.RoleID')
            ->From('Role r')
            ->WhereIn('r.Name', $RoleNames)
            ->Get()->ResultArray();
         $RoleIDs = ConsolidateArrayValuesByKey($RoleIDs, 'RoleID');
      }
      
      if (!is_array($RoleIDs))
         $RoleIDs = array($RoleIDs);

      // Get the current roles.
      $OldRoleIDs = array();
      $OldRoleData = $this->SQL
         ->Select('ur.RoleID, r.Name')
         ->From('Role r')
         ->Join('UserRole ur', 'r.RoleID = ur.RoleID')
         ->Where('ur.UserID', $UserID)
         ->Get()
         ->ResultArray();

      if ($OldRoleData !== FALSE) {
         $OldRoleIDs = ConsolidateArrayValuesByKey($OldRoleData, 'RoleID');
      }
      
      // 1a) Figure out which roles to delete.
      $DeleteRoleIDs = array_diff($OldRoleIDs, $RoleIDs);
      // 1b) Remove old role associations for this user.
      if(count($DeleteRoleIDs) > 0)
         $this->SQL->WhereIn('RoleID', $DeleteRoleIDs)->Delete('UserRole', array('UserID' => $UserID));
      
      // 2a) Figure out which roles to insert.
      $InsertRoleIDs = array_diff($RoleIDs, $OldRoleIDs);
      // 2b) Insert the new role associations for this user.
      foreach($InsertRoleIDs as $InsertRoleID) {
         if (is_numeric($InsertRoleID))
            $this->SQL->Insert('UserRole', array('UserID' => $UserID, 'RoleID' => $InsertRoleID));
      }
      
      $this->ClearCache($UserID, array('roles', 'permissions'));

      if ($RecordActivity && (count($DeleteRoleIDs) > 0 || count($InsertRoleIDs) > 0)) {
         $User = $this->GetID($UserID);
         $Session = Gdn::Session();

         $OldRoles = FALSE;
         if ($OldRoleData !== FALSE)
            $OldRoles = ConsolidateArrayValuesByKey($OldRoleData, 'Name');

         $NewRoles = FALSE;
         $NewRoleData = $this->SQL
            ->Select('r.RoleID, r.Name')
            ->From('Role r')
            ->Join('UserRole ur', 'r.RoleID = ur.RoleID')
            ->Where('ur.UserID', $UserID)
            ->Get()
            ->ResultArray();
         if ($NewRoleData !== FALSE)
            $NewRoles = ConsolidateArrayValuesByKey($NewRoleData, 'Name');


         $RemovedRoles = array_diff($OldRoles, $NewRoles);
         $NewRoles = array_diff($NewRoles, $OldRoles);

         $RemovedCount = count($RemovedRoles);
         $NewCount = count($NewRoles);
         $Story = '';
         if ($RemovedCount > 0 && $NewCount > 0) {
            $Story = sprintf(T('%1$s was removed from the %2$s %3$s and added to the %4$s %5$s.'),
               $User->Name,
               implode(', ', $RemovedRoles),
               Plural($RemovedCount, 'role', 'roles'),
               implode(', ', $NewRoles),
               Plural($NewCount, 'role', 'roles')
            );
         } else if ($RemovedCount > 0) {
            $Story = sprintf(T('%1$s was removed from the %2$s %3$s.'),
               $User->Name,
               implode(', ', $RemovedRoles),
               Plural($RemovedCount, 'role', 'roles')
            );
         } else if ($NewCount > 0) {
            $Story = sprintf(T('%1$s was added to the %2$s %3$s.'),
               $User->Name,
               implode(', ', $NewRoles),
               Plural($NewCount, 'role', 'roles')
            );
         }
      }
   }

   public function Search($Keywords, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      if (C('Garden.Registration.Method') == 'Approval')
         $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);
      else
         $ApplicantRoleID = 0;

      if (is_array($Keywords)) {
         $Where = $Keywords;
         $Keywords = $Where['Keywords'];
         unset($Where['Keywords']);
      }

      // Check for an IP address.
      if (preg_match('`\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}`', $Keywords)) {
         $IPAddress = $Keywords;
      } elseif (strtolower($Keywords) == 'banned') {
         $this->SQL->Where('u.Banned >', 0);
      } elseif (preg_match('/^\d+$/', $Keywords)) {
         $UserID = $Keywords;
      } else {
         // Check to see if the search exactly matches a role name.
         $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Keywords))->Value('RoleID');
      }

      $this->UserQuery();
      if ($ApplicantRoleID != 0) {
         $this->SQL
            ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');
      }

      if (isset($Where))
         $this->SQL->Where($Where);

      if (isset($RoleID) && $RoleID) {
         $this->SQL->Join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $RoleID");
      } elseif (isset($IPAddress)) {
         $this->SQL
            ->BeginWhereGroup()
            ->OrWhere('u.InsertIPAddress', $IPAddress)
            ->OrWhere('u.LastIPAddress', $IPAddress)
            ->EndWhereGroup();
      } elseif (isset($UserID)) {
         $this->SQL->Where('u.UserID', $UserID);
      } else {
         // Search on the user table.
         $Like = trim($Keywords) == '' ? FALSE : array('u.Name' => $Keywords, 'u.Email' => $Keywords);
         
         if (is_array($Like)) {
            $this->SQL
               ->BeginWhereGroup()
               ->OrLike($Like, '', 'right')
               ->EndWhereGroup();
         }
      }

      if ($ApplicantRoleID != 0)
         $this->SQL->Where('ur.RoleID is null');

      $Data = $this->SQL
         ->Where('u.Deleted', 0)
         ->OrderBy($OrderFields, $OrderDirection)
         ->Limit($Limit, $Offset)
         ->Get();
      
      $Result =& $Data->Result();
      
      foreach ($Result as &$Row) {
         if ($Row->Photo && strpos($Row->Photo, '//') === FALSE) {
            $Row->Photo = Gdn_Upload::Url($Row->Photo);
         }
         
         $Row->Attributes = @unserialize($Row->Preferences);
         $Row->Preferences = @unserialize($Row->Preferences);
      }
      
      return $Data;
   }

   public function SearchCount($Keywords = FALSE) {
      if (C('Garden.Registration.Method') == 'Approval')
         $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);
      else
         $ApplicantRoleID = 0;

      if (is_array($Keywords)) {
         $Where = $Keywords;
         $Keywords = $Where['Keywords'];
         unset($Where['Keywords']);
      }

      // Check to see if the search exactly matches a role name.
      $RoleID = FALSE;
      if (strtolower($Keywords) == 'banned') {
         $this->SQL->Where('u.Banned >', 0);
      } elseif (isset($UserID)) {
         $this->SQL->Where('u.UserID', $UserID);
      } else {
         $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Keywords))->Value('RoleID');
      }

      if (isset($Where))
         $this->SQL->Where($Where);
      
      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u');
      if ($ApplicantRoleID != 0)
         $this->SQL->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');

      if ($RoleID) {
         $this->SQL->Join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $RoleID");
      } elseif (isset($UserID)) {
         $this->SQL->Where('u.UserID', $UserID);
      } else {
         // Search on the user table.
         $Like = trim($Keywords) == '' ? FALSE : array('u.Name' => $Keywords, 'u.Email' => $Keywords);

         if (is_array($Like)) {
            $this->SQL
               ->BeginWhereGroup()
               ->OrLike($Like, '', 'right')
               ->EndWhereGroup();
         }
      }

		$this->SQL
         ->Where('u.Deleted', 0);
      
      if ($ApplicantRoleID != 0)
         $this->SQL->Where('ur.RoleID is null');

		$Data =  $this->SQL->Get()->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }
   
   public static function SigninLabelCode() {
      return UserModel::NoEmail() ? 'Username' : 'Email/Username';
   }

   /**
    * To be used for invitation registration
    */
   public function InsertForInvite($FormPostValues, $Options = array()) {
      $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles');
      if (!is_array($RoleIDs) || count($RoleIDs) == 0)
         throw new Exception(T('The default role has not been configured.'), 400);

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      $this->Validation->ApplyRule('Email', 'Email');

      // Make sure that the checkbox val for email is saved as the appropriate enum
      // TODO: DO I REALLY NEED THIS???
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');
      
      if (array_key_exists('Banned', $FormPostValues))
         $FormPostValues['Banned'] = ForceBool($FormPostValues['Banned'], '0', '1', '0');

      $this->AddInsertFields($FormPostValues);

      // Make sure that the user has a valid invitation code, and also grab
      // the user's email from the invitation:
      $InviteUserID = 0;
      $InviteUsername = '';
      $InvitationCode = ArrayValue('InvitationCode', $FormPostValues, '');
      $this->SQL->Select('i.InvitationID, i.InsertUserID, i.Email')
         ->Select('s.Name', '', 'SenderName')
         ->From('Invitation i')
         ->Join('User s', 'i.InsertUserID = s.UserID', 'left')
         ->Where('Code', $InvitationCode)
         ->Where('AcceptedUserID is null'); // Do not let them use the same invitation code twice!
      $InviteExpiration = Gdn::Config('Garden.Registration.InviteExpiration');
      if ($InviteExpiration != 'FALSE' && $InviteExpiration !== FALSE)
         $this->SQL->Where('i.DateInserted >=', Gdn_Format::ToDateTime(strtotime($InviteExpiration)));

      $Invitation = $this->SQL->Get()->FirstRow();
      if ($Invitation !== FALSE) {
         $InviteUserID = $Invitation->InsertUserID;
         $InviteUsername = $Invitation->SenderName;
         $FormPostValues['Email'] = $Invitation->Email;
      }
      if ($InviteUserID <= 0) {
         $this->Validation->AddValidationResult('InvitationCode', 'ErrorBadInvitationCode');
         return FALSE;
      }

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         // Check for spam.
         $Spam = SpamModel::IsSpam('Registration', $FormPostValues);
         if ($Spam) {
            $this->Validation->AddValidationResult('Spam', 'You are not allowed to register at this time.');
            return;
         }

         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

         // Make sure the username & email aren't already being used
         if (!$this->ValidateUniqueFields($Username, $Email))
            return FALSE;

         // Define the other required fields:
         if ($InviteUserID > 0)
            $Fields['InviteUserID'] = $InviteUserID;


         // And insert the new user.
         if (!isset($Options['NoConfirmEmail']))
            $Options['NoConfirmEmail'] = TRUE;

         $Fields['Roles'] = $RoleIDs;
         $UserID = $this->_Insert($Fields, $Options);

         // Associate the new user id with the invitation (so it cannot be used again)
         $this->SQL
            ->Update('Invitation')
            ->Set('AcceptedUserID', $UserID)
            ->Where('InvitationID', $Invitation->InvitationID)
            ->Put();

         // Report that the user was created.
         $ActivityModel = new ActivityModel();
         $ActivityModel->Save(array(
             'ActivityUserID' => $UserID,
             'ActivityType' => 'Registration',
             'HeadlineFormat' => T('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
             'Story' => T('Welcome Aboard!')
             ),
             FALSE,
             array('GroupBy' => 'ActivityTypeID'));
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }

   /**
    * To be used for approval registration
    */
   public function InsertForApproval($FormPostValues, $Options = array()) {
      $RoleIDs = C('Garden.Registration.ApplicantRoleID');
      if (!$RoleIDs) {
         throw new Exception(T('The default role has not been configured.'), 400);
      }

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      $this->Validation->ApplyRule('Email', 'Email');

      // Make sure that the checkbox val for email is saved as the appropriate enum
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');
      
      if (array_key_exists('Banned', $FormPostValues))
         $FormPostValues['Banned'] = ForceBool($FormPostValues['Banned'], '0', '1', '0');

      $this->AddInsertFields($FormPostValues);

      if ($this->Validate($FormPostValues, TRUE)) {
         // Check for spam.
         $Spam = SpamModel::IsSpam('Registration', $FormPostValues);
         if ($Spam) {
            $this->Validation->AddValidationResult('Spam', 'You are not allowed to register at this time.');
            return;
         }

         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

         if (!$this->ValidateUniqueFields($Username, $Email))
            return FALSE;

         // Define the other required fields:
         $Fields['Email'] = $Email;
         $Fields['Roles'] = (array)$RoleIDs;

         // And insert the new user
         $UserID = $this->_Insert($Fields, $Options);
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }

   /**
    * To be used for basic registration, and captcha registration
    */
   public function InsertForBasic($FormPostValues, $CheckCaptcha = TRUE, $Options = array()) {
      $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles');
      if (!is_array($RoleIDs) || count($RoleIDs) == 0)
         throw new Exception(T('The default role has not been configured.'), 400);
      
      if (GetValue('SaveRoles', $Options)) {
         $RoleIDs = GetValue('RoleID', $FormPostValues);
      }

      $UserID = FALSE;

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules.
      if (GetValue('ValidateEmail', $Options, TRUE))
         $this->Validation->ApplyRule('Email', 'Email');

      // TODO: DO I NEED THIS?!
      // Make sure that the checkbox val for email is saved as the appropriate enum
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');
      
      if (array_key_exists('Banned', $FormPostValues))
         $FormPostValues['Banned'] = ForceBool($FormPostValues['Banned'], '0', '1', '0');

      $this->AddInsertFields($FormPostValues);

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields['Roles'] = $RoleIDs;
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

         // If in Captcha registration mode, check the captcha value
         if ($CheckCaptcha && Gdn::Config('Garden.Registration.Method') == 'Captcha') {
            $CaptchaPublicKey = ArrayValue('Garden.Registration.CaptchaPublicKey', $FormPostValues, '');
            $CaptchaValid = ValidateCaptcha($CaptchaPublicKey);
            if ($CaptchaValid !== TRUE) {
               $this->Validation->AddValidationResult('Garden.Registration.CaptchaPublicKey', 'The reCAPTCHA value was not entered correctly. Please try again.');
               return FALSE;
            }
         }

         if (!$this->ValidateUniqueFields($Username, $Email))
            return FALSE;
         
         // Check for spam.
         if (GetValue('ValidateSpam', $Options, TRUE)) {
            $ValidateSpam = $this->ValidateSpamRegistration($FormPostValues);
            if ($ValidateSpam !== TRUE)
               return $ValidateSpam;
         }

         // Define the other required fields:
         $Fields['Email'] = $Email;

         // And insert the new user
         $UserID = $this->_Insert($Fields, $Options);
         if ($UserID && !GetValue('NoActivity', $Options)) {
            $ActivityModel = new ActivityModel();
            $ActivityModel->Save(array(
               'ActivityUserID' => $UserID,
               'ActivityType' => 'Registration',
               'HeadlineFormat' => T('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
               'Story' => T('Welcome Aboard!')
                ),
                FALSE,
                array('GroupBy' => 'ActivityTypeID'));
         }
      }
      return $UserID;
   }

   // parent override
   public function AddInsertFields(&$Fields) {
      $this->DefineSchema();

      // Set the hour offset based on the client's clock.
      $ClientHour = ArrayValue('ClientHour', $Fields, '');
      if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
         $HourOffset = $ClientHour - date('G', time());
         $Fields['HourOffset'] = $HourOffset;
      }

      // Set some required dates.
      $Now = Gdn_Format::ToDateTime();
      $Fields[$this->DateInserted] = $Now;
      $Fields['DateFirstVisit'] = $Now;
      $Fields['DateLastActive'] = $Now;
      $Fields['InsertIPAddress'] = Gdn::Request()->IpAddress();
      $Fields['LastIPAddress'] = Gdn::Request()->IpAddress();
   }

   /**
    * Updates visit level information such as date last active and the user's ip address.
    *
    * @param int $UserID
    * @param string|int|float $ClientHour
    */
   function UpdateVisit($UserID, $ClientHour = FALSE) {
      $UserID = (int) $UserID;
      if (!$UserID) {
         throw new Exception('A valid User ID is required.');
      }
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      
      $Fields = array();
      
      if (Gdn_Format::ToTimestamp($User['DateLastActive']) < strtotime('5 minutes ago')) {
         // We only update the last active date once every 5 minutes to cut down on DB activity.
         $Fields['DateLastActive'] = Gdn_Format::ToDateTime();
      }
      
      // Update session level information if necessary.
      if ($UserID == Gdn::Session()->UserID) {
         $IP = Gdn::Request()->IpAddress();
         $Fields['LastIPAddress'] = $IP;
         
         if (Gdn::Session()->NewVisit()) {
            $Fields['CountVisits'] = GetValue('CountVisits', $User, 0) + 1;
         }
      }
      
      // Generate the AllIPs field.
      $AllIPs = GetValue('AllIPAddresses', $User, array());
      if (is_string($AllIPs)) {
         $AllIPs = explode(',', $AllIPs);
         SetValue('AllIPAddresses', $User, $AllIPs);
      }
      if (!is_array($AllIPs))
         $AllIPs = array();
      if ($IP = GetValue('InsertIPAddress', $User))
         $AllIPs[] = ForceIPv4($IP);
      if ($IP = GetValue('LastIPAddress', $User))
         $AllIPs[] = $IP;
      $AllIPs = array_unique($AllIPs);
      sort($AllIPs);
      $Fields['AllIPAddresses'] = $AllIPs;

      // Set the hour offset based on the client's clock.
      if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
         $HourOffset = $ClientHour - date('G', time());
         $Fields['HourOffset'] = $HourOffset;
      }
      
      // See if the fields have changed.
      $Set = array();
      foreach ($Fields as $Name => $Value) {
         if (GetValue($Name, $User) != $Value) {
            $Set[$Name] = $Value;
         }
      }
      
      if (!empty($Set)) {
         $this->EventArguments['Fields'] =& $Set;
         $this->FireEvent('UpdateVisit');
         
         $this->SetField($UserID, $Set);
      }
      
      if ($User['LastIPAddress'] != $Fields['LastIPAddress']) {
         $User = $this->GetID($UserID, DATASET_TYPE_ARRAY);
         if (!BanModel::CheckUser($User, NULL, TRUE, $Bans)) {
            $BanModel = new BanModel();
            $Ban = array_pop($Bans);
            $BanModel->SaveUser($User, TRUE, $Ban);
            $BanModel->SetCounts($Ban);
         }
      }
   }
   
   /**
    * @param unknown_type $FormPostValues
    * @param unknown_type $Insert
    * @return unknown
    * @todo add doc
    */
   public function Validate($FormPostValues, $Insert = FALSE) {
      $this->DefineSchema();
      
      if (self::NoEmail()) {
         // Remove the email requirement.
         $this->Validation->UnapplyRule('Email', 'Required');
      }
      
      if (!$Insert && !isset($FormPostValues['Name'])) {
         $this->Validation->UnapplyRule('Name');
      }
      
      return $this->Validation->Validate($FormPostValues, $Insert);
   }

   /**
    * Validate User Credential
    *
    * Fetches a user row by email (or name) and compare the password.
    *
    * If the password was not stored as a blowfish hash,
    * the password will be saved again.
    *
    * Return the user's id, admin status and attributes.
    *
    * @param string $Email
    * @param string $Password
    * @return object
    */
   public function ValidateCredentials($Email = '', $ID = 0, $Password) {
      $this->EventArguments['Credentials'] = array('Email'=>$Email, 'ID'=>$ID, 'Password'=>$Password);
      $this->FireEvent('BeforeValidateCredentials');

      if (!$Email && !$ID)
         throw new Exception('The email or id is required');

		try {
			$this->SQL->Select('UserID, Name, Attributes, Admin, Password, HashMethod, Deleted, Banned')
				->From('User');
	
			if ($ID) {
				$this->SQL->Where('UserID', $ID);
			} else {
				if (strpos($Email, '@') > 0) {
					$this->SQL->Where('Email', $Email);
				} else {
					$this->SQL->Where('Name', $Email);
				}
			}
	
			$DataSet = $this->SQL->Get();
		} catch(Exception $Ex) {
         $this->SQL->Reset();
         
			// Try getting the user information without the new fields.
			$this->SQL->Select('UserID, Name, Attributes, Admin, Password')
				->From('User');
	
			if ($ID) {
				$this->SQL->Where('UserID', $ID);
			} else {
				if (strpos($Email, '@') > 0) {
					$this->SQL->Where('Email', $Email);
				} else {
					$this->SQL->Where('Name', $Email);
				}
			}
	
			$DataSet = $this->SQL->Get();
		}
		
      if ($DataSet->NumRows() < 1)
         return FALSE;

      $UserData = $DataSet->FirstRow();
		// Check for a deleted user.
		if(GetValue('Deleted', $UserData))
			return FALSE;
		
      $PasswordHash = new Gdn_PasswordHash();
		$HashMethod = GetValue('HashMethod', $UserData);
      if(!$PasswordHash->CheckPassword($Password, $UserData->Password, $HashMethod, $UserData->Name))
         return FALSE;
      
      if ($PasswordHash->Weak || ($HashMethod && strcasecmp($HashMethod, 'Vanilla') != 0)) {
         $Pw = $PasswordHash->HashPassword($Password);
         $this->SQL->Update('User')
            ->Set('Password', $Pw)
				->Set('HashMethod', 'Vanilla')
            ->Where('UserID', $UserData->UserID)
            ->Put();
      }
      
      $UserData->Attributes = Gdn_Format::Unserialize($UserData->Attributes);
      return $UserData;
   }
   
   /**
    *
    * @param array $User
    * @return bool|string
    * @since 2.1 
    */
   public function ValidateSpamRegistration($User) {
      $DiscoveryText = GetValue('DiscoveryText', $User);
      $Log = ValidateRequired($DiscoveryText);
      $Spam = SpamModel::IsSpam('Registration', $User, array('Log' => $Log));
      
      if ($Spam) {
         if ($Log) {
            // The user entered discovery text.
            return self::REDIRECT_APPROVE;
         } else {
            $this->Validation->AddValidationResult('DiscoveryText', 'Tell us why you want to join!');
            return FALSE;
         }
      }
      return TRUE;
    }

   /**
    * Checks to see if $Username and $Email are already in use by another member.
    */
   public function ValidateUniqueFields($Username, $Email, $UserID = '', $Return = FALSE) {
      $Valid = TRUE;
      $Where = array();
      if (is_numeric($UserID))
         $Where['UserID <> '] = $UserID;
      
      $Result = array('Name' => TRUE, 'Email' => TRUE);

      // Make sure the username & email aren't already being used
      if (C('Garden.Registration.NameUnique', TRUE) && $Username) {
         $Where['Name'] = $Username;
         $TestData = $this->GetWhere($Where);
         if ($TestData->NumRows() > 0) {
            $Result['Name'] = FALSE;
            $Valid = FALSE;
         }
         unset($Where['Name']);
      }
      
      if (C('Garden.Registration.EmailUnique', TRUE) && $Email) {
         $Where['Email'] = $Email;
         $TestData = $this->GetWhere($Where);
         if ($TestData->NumRows() > 0) {
            $Result['Email'] = FALSE;
            $Valid = FALSE;
         }
      }
      
      if ($Return) {
         return $Result;
      } else {
         if (!$Result['Name'])
            $this->Validation->AddValidationResult('Name', 'The name you entered is already in use by another member.');
         if (!$Result['Email'])
            $this->Validation->AddValidationResult('Email', 'The email you entered is in use by another member.');
         return $Valid;
      }
   }

   /**
    * Approve a membership applicant.
    */
   public function Approve($UserID, $Email) {
      $ApplicantRoleID = C('Garden.Registration.ApplicantRoleID', 0);
      
      // Make sure the $UserID is an applicant
      $RoleData = $this->GetRoles($UserID);
      if ($RoleData->NumRows() == 0) {
         throw new Exception(T('ErrorRecordNotFound'));
      } else {
         $AppRoles = $RoleData->Result(DATASET_TYPE_ARRAY);
         $ApplicantFound = FALSE;
         foreach ($AppRoles as $AppRole)
            if (GetValue('RoleID', $AppRole) == $ApplicantRoleID) $ApplicantFound = TRUE;
      }

      if ($ApplicantFound) {
         // Retrieve the default role(s) for new users
         $RoleIDs = C('Garden.Registration.DefaultRoles', array(8));

         // Wipe out old & insert new roles for this user
         $this->SaveRoles($UserID, $RoleIDs, FALSE);

         // Send out a notification to the user
         $User = $this->GetID($UserID);
         if ($User) {
				$Email->Subject(sprintf(T('[%1$s] Membership Approved'), C('Garden.Title')));
				$Email->Message(sprintf(T('EmailMembershipApproved'), $User->Name, ExternalUrl(SignInUrl())));
				$Email->To($User->Email);
				//$Email->From(C('Garden.SupportEmail'), C('Garden.SupportName'));
				$Email->Send();
            
            // Report that the user was approved.
            $ActivityModel = new ActivityModel();
            $ActivityModel->Save(array(
                'ActivityUserID' => $UserID,
                'ActivityType' => 'Registration',
                'HeadlineFormat' => T('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                'Story' => T('Welcome Aboard!')
               ),
               FALSE,
               array('GroupBy' => 'ActivityTypeID'));
            
            // Report the approval for moderators.
            $ActivityModel->Save(array(
                'ActivityType' => 'Registration',
                'ActivityUserID' => Gdn::Session()->UserID,
                'RegardingUserID' => $UserID,
                'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                'HeadlineFormat' => T('HeadlineFormat.RegistrationApproval', '{ActivityUserID,user} approved the applications for {RegardingUserID,user}.')),
                FALSE,
                array('GroupBy' => array('ActivityTypeID', 'ActivityUserID')));
            
            Gdn::UserModel()->SaveAttribute($UserID, 'ApprovedByUserID', Gdn::Session()->UserID);
         }
         
         

         
      }
      return TRUE;
   }
   
   /**
    * Delete a single user.
    *
    * @param int $UserID
    * @param array $Options See DeleteContent(), GetDelete()
    */
   public function Delete($UserID, $Options = array()) {
      if ($UserID == $this->GetSystemUserID()) {
         $this->Validation->AddValidationResult('', 'You cannot delete the system user.');
         return FALSE;
      }
      
      $Content = array();
      
      // Remove shared authentications.
      $this->GetDelete('UserAuthentication', array('UserID' => $UserID), $Content);

      // Remove role associations.
      $this->GetDelete('UserRole', array('UserID' => $UserID), $Content);
      
      $this->DeleteContent($UserID, $Options, $Content);
      
      // Remove the user's information
      $this->SQL->Update('User')
         ->Set(array(
            'Name' => T('[Deleted User]'),
            'Photo' => null,
            'Password' => RandomString('10'),
            'About' => '',
            'Email' => 'user_'.$UserID.'@deleted.email',
            'ShowEmail' => '0',
            'Gender' => 'u',
            'CountVisits' => 0,
            'CountInvitations' => 0,
            'CountNotifications' => 0,
            'InviteUserID' => null,
            'DiscoveryText' => '',
            'Preferences' => null,
            'Permissions' => null,
            'Attributes' => Gdn_Format::Serialize(array('State' => 'Deleted')),
            'DateSetInvitations' => null,
            'DateOfBirth' => null,
            'DateUpdated' => Gdn_Format::ToDateTime(),
            'HourOffset' => '0',
            'Score' => null,
            'Admin' => 0,
            'Deleted' => 1
            ))
         ->Where('UserID', $UserID)
         ->Put();

      // Remove user's cache rows
      $this->ClearCache($UserID);
      
      return TRUE;
   }

   public function DeleteContent($UserID, $Options = array(), $Content = array()) {
      $Log = GetValue('Log', $Options);
      if ($Log === TRUE)
         $Log = 'Delete';
      
      $Result = FALSE;
      
      // Fire an event so applications can remove their associated user data.
      $this->EventArguments['UserID'] = $UserID;
      $this->EventArguments['Options'] = $Options;
      $this->EventArguments['Content'] =& $Content;
      $this->FireEvent('BeforeDeleteUser');
      
      $User = $this->GetID($UserID, DATASET_TYPE_ARRAY);
      
      if (!$Log)
         $Content = NULL;
      
      // Remove photos
      /*$PhotoData = $this->SQL->Select()->From('Photo')->Where('InsertUserID', $UserID)->Get();
      foreach ($PhotoData->Result() as $Photo) {
         @unlink(PATH_UPLOADS.DS.$Photo->Name);
      }
      $this->SQL->Delete('Photo', array('InsertUserID' => $UserID));
      */
      
      // Remove invitations
      $this->GetDelete('Invitation', array('InsertUserID' => $UserID), $Content);
      $this->GetDelete('Invitation', array('AcceptedUserID' => $UserID), $Content);
      
      // Remove activities
      $this->GetDelete('Activity', array('InsertUserID' => $UserID), $Content);
      
      // Remove activity comments.
      $this->GetDelete('ActivityComment', array('InsertUserID' => $UserID), $Content);

      // Remove comments in moderation queue
      $this->GetDelete('Log', array('RecordUserID' => $UserID, 'Operation' => 'Pending'), $Content);

      // Clear out information on the user.
      $this->SetField($UserID, array(
          'About' => NULL,
          'Title' => NULL,
          'Location' => NULL));
      
      if ($Log) {
         $User['_Data'] = $Content;
         unset($Content); // in case data gets copied
         
         $Result = LogModel::Insert($Log, 'User', $User, GetValue('LogOptions', $Options, array()));
      }
      
      return $Result;
   }

   public function Decline($UserID) {
      $ApplicantRoleID = C('Garden.Registration.ApplicantRoleID', 0);
      
      // Make sure the user is an applicant
      $RoleData = $this->GetRoles($UserID);
      if ($RoleData->NumRows() == 0) {
         throw new Exception(T('ErrorRecordNotFound'));
      } else {
         $AppRoles = $RoleData->Result(DATASET_TYPE_ARRAY);
         $ApplicantFound = FALSE;
         foreach ($AppRoles as $AppRole)
            if (GetValue('RoleID', $AppRole) == $ApplicantRoleID) $ApplicantFound = TRUE;
      }

      if ($ApplicantFound) {
         $this->Delete($UserID);
      }
      return TRUE;
   }

   public function GetInvitationCount($UserID) {
      // If this user is master admin, they should have unlimited invites.
      if ($this->SQL
         ->Select('UserID')
         ->From('User')
         ->Where('UserID', $UserID)
         ->Where('Admin', '1')
         ->Get()
         ->NumRows() > 0
      ) return -1;

      // Get the Registration.InviteRoles settings:
      $InviteRoles = Gdn::Config('Garden.Registration.InviteRoles', array());
      if (!is_array($InviteRoles) || count($InviteRoles) == 0)
         return 0;

      // Build an array of roles that can send invitations
      $CanInviteRoles = array();
      foreach ($InviteRoles as $RoleID => $Invites) {
         if ($Invites > 0 || $Invites == -1)
            $CanInviteRoles[] = $RoleID;
      }

      if (count($CanInviteRoles) == 0)
         return 0;

      // See which matching roles the user has
      $UserRoleData = $this->SQL->Select('RoleID')
         ->From('UserRole')
         ->Where('UserID', $UserID)
         ->WhereIn('RoleID', $CanInviteRoles)
         ->Get();

      if ($UserRoleData->NumRows() == 0)
         return 0;

      // Define the maximum number of invites the user is allowed to send
      $InviteCount = 0;
      foreach ($UserRoleData->Result() as $UserRole) {
         $Count = $InviteRoles[$UserRole->RoleID];
         if ($Count == -1) {
            $InviteCount = -1;
         } else if ($InviteCount != -1 && $Count > $InviteCount) {
            $InviteCount = $Count;
         }
      }

      // If the user has unlimited invitations, return that value
      if ($InviteCount == -1)
         return -1;

      // Get the user's current invitation settings from their profile
      $User = $this->SQL->Select('CountInvitations, DateSetInvitations')
         ->From('User')
         ->Where('UserID', $UserID)
         ->Get()
         ->FirstRow();

      // If CountInvitations is null (ie. never been set before) or it is a new month since the DateSetInvitations
      if ($User->CountInvitations == '' || is_null($User->DateSetInvitations) || Gdn_Format::Date($User->DateSetInvitations, 'n Y') != Gdn_Format::Date('', 'n Y')) {
         // Reset CountInvitations and DateSetInvitations
         $this->SQL->Put(
            $this->Name,
            array(
               'CountInvitations' => $InviteCount,
               'DateSetInvitations' => Gdn_Format::Date('', 'Y-m-01') // The first day of this month
            ),
            array('UserID' => $UserID)
         );
         return $InviteCount;
      } else {
         // Otherwise return CountInvitations
         return $User->CountInvitations;
      }
   }
   
   /**
    * Get rows from a table then delete them.
    * 
    * @param string $Table The name of the table.
    * @param array $Where The where condition for the delete.
    * @param array $Data The data to put the result.
    * @since 2.1
    */
   public function GetDelete($Table, $Where, &$Data) {
      if (is_array($Data)) {
         // Grab the records.
         $Result = $this->SQL->GetWhere($Table, $Where)->ResultArray();
         
         if (empty($Result))
            return;
         
         // Put the records in the result array.
         if (isset($Data[$Table])) {
            $Data[$Table] = array_merge($Data[$Table], $Result);
         } else {
            $Data[$Table] = $Result;
         }
      }
      
      $this->SQL->Delete($Table, $Where);
   }

   /**
    * Reduces the user's CountInvitations value by the specified amount.
    *
    * @param int The unique id of the user being affected.
    * @param int The number to reduce CountInvitations by.
    */
   public function ReduceInviteCount($UserID, $ReduceBy = 1) {
      $CurrentCount = $this->GetInvitationCount($UserID);

      // Do not reduce if the user has unlimited invitations
      if ($CurrentCount == -1)
         return TRUE;

      // Do not reduce the count below zero.
      if ($ReduceBy > $CurrentCount)
         $ReduceBy = $CurrentCount;

      $this->SQL->Update($this->Name)
         ->Set('CountInvitations', 'CountInvitations - '.$ReduceBy, FALSE)
         ->Where('UserID', $UserID)
         ->Put();
   }

   /**
    * Increases the user's CountInvitations value by the specified amount.
    *
    * @param int The unique id of the user being affected.
    * @param int The number to increase CountInvitations by.
    */
   public function IncreaseInviteCount($UserID, $IncreaseBy = 1) {
      $CurrentCount = $this->GetInvitationCount($UserID);

      // Do not alter if the user has unlimited invitations
      if ($CurrentCount == -1)
         return TRUE;

      $this->SQL->Update($this->Name)
         ->Set('CountInvitations', 'CountInvitations + '.$IncreaseBy, FALSE)
         ->Where('UserID', $UserID)
         ->Put();
   }

   /**
    * Saves the user's About field.
    *
    * @param int The UserID to save.
    * @param string The about message being saved.
    */
   public function SaveAbout($UserID, $About) {
      $About = substr($About, 0, 1000);
      $this->SetField($UserID, 'About', $About);
   }

   /**
    * Saves a name/value to the user's specified $Column.
    *
    * This method throws exceptions when errors are encountered. Use try ...
    * catch blocks to capture these exceptions.
    *
    * @param string The name of the serialized column to save to. At the time of this writing there are three serialized columns on the user table: Permissions, Preferences, and Attributes.
    * @param int The UserID to save.
    * @param mixed The name of the value being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
    * @param mixed The value being saved.
    */
   public function SaveToSerializedColumn($Column, $UserID, $Name, $Value = '') {
      // Load the existing values
      $UserData = $this->GetID($UserID, DATASET_TYPE_OBJECT);

      if (!$UserData)
         throw new Exception(sprintf('User %s not found.', $UserID));

      $Values = GetValue($Column, $UserData);
      
      if (!is_array($Values) && !is_object($Values))
         $Values = @unserialize($UserData->$Column);
      
      // Throw an exception if the field was not empty but is also not an object or array
      if (is_string($Values) && $Values != '')
         throw new Exception(sprintf(T('Serialized column "%s" failed to be unserialized.'),$Column));

      if (!is_array($Values))
         $Values = array();
      
      // Hook for plugins
      $this->EventArguments['CurrentValues'] = &$Values;
      $this->EventArguments['Column'] = &$Column;
      $this->EventArguments['UserID'] = &$UserID;
      $this->EventArguments['Name'] = &$Name;
      $this->EventArguments['Value'] = &$Value;
      $this->FireEvent('BeforeSaveSerialized');

      // Assign the new value(s)
      if (!is_array($Name))
         $Name = array($Name => $Value);

      
      $RawValues = array_merge($Values, $Name);
      $Values = array();
      foreach ($RawValues as $Key => $RawValue)
         if (!is_null($RawValue))
            $Values[$Key] = $RawValue;
      
      $Values = Gdn_Format::Serialize($Values);

      // Save the values back to the db
      $SaveResult = $this->SQL->Put('User', array($Column => $Values), array('UserID' => $UserID));
      $this->ClearCache($UserID, array('user'));
      
      return $SaveResult;
   }

   /**
    * Saves a user preference to the database.
    *
    * This is a convenience method that uses $this->SaveToSerializedColumn().
    *
    * @param int The UserID to save.
    * @param mixed The name of the preference being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
    * @param mixed The value being saved.
    */
   public function SavePreference($UserID, $Preference, $Value = '') {
      // Make sure that changes to the current user become effective immediately.
      $Session = Gdn::Session();
      if ($UserID == $Session->UserID)
         $Session->SetPreference($Preference, $Value, FALSE);

      return $this->SaveToSerializedColumn('Preferences', $UserID, $Preference, $Value);
   }

   /**
    * Saves a user attribute to the database.
    *
    * This is a convenience method that uses $this->SaveToSerializedColumn().
    *
    * @param int The UserID to save.
    * @param mixed The name of the attribute being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
    * @param mixed The value being saved.
    */
   public function SaveAttribute($UserID, $Attribute, $Value = '') {
      // Make sure that changes to the current user become effective immediately.
      $Session = Gdn::Session();
      if ($UserID == $Session->UserID)
         $Session->SetAttribute($Attribute, $Value);

      return $this->SaveToSerializedColumn('Attributes', $UserID, $Attribute, $Value);
   }

   public function SaveAuthentication($Data) {
      $Cn = $this->Database->Connection();
      $Px = $this->Database->DatabasePrefix;

      $UID = $Cn->quote($Data['UniqueID']);
      $Provider = $Cn->quote($Data['Provider']);
      $UserID = $Cn->quote($Data['UserID']);

      $Sql = "insert {$Px}UserAuthentication (ForeignUserKey, ProviderKey, UserID) values ($UID, $Provider, $UserID) on duplicate key update UserID = $UserID";
      $Result = $this->Database->Query($Sql);
      return $Result;
   }

   public function SetCalculatedFields(&$User) {
      if ($v = GetValue('Attributes', $User))
         if (is_string($v))
            SetValue('Attributes', $User, @unserialize($v));
      if ($v = GetValue('Permissions', $User))
         SetValue('Permissions', $User, @unserialize($v));
      if ($v = GetValue('Preferences', $User))
         SetValue('Preferences', $User, @unserialize($v));
      if ($v = GetValue('Photo', $User)) {
         if (!IsUrl($v)) {
            $PhotoUrl = Gdn_Upload::Url(ChangeBasename($v, 'n%s'));
         } else {
            $PhotoUrl = $v;
         }
         
         SetValue('PhotoUrl', $User, $PhotoUrl);
      }
      if ($v = GetValue('AllIPAddresses', $User)) {
         $IPAddresses = explode(',', $v);
         foreach ($IPAddresses as $i => $IPAddress) {
            $IPAddresses[$i] = ForceIPv4($IPAddress);
         }
         SetValue('AllIPAddresses', $User, $IPAddresses);
      }
      
      TouchValue('_CssClass', $User, '');
      if ($v = GetValue('Banned', $User)) {
         SetValue('_CssClass', $User, 'Banned');
      }
      
      $this->EventArguments['User'] =& $User;
      $this->FireEvent('SetCalculatedFields');
   }

   public static function SetMeta($UserID, $Meta, $Prefix = '') {
      $Deletes = array();
      $Px = Gdn::Database()->DatabasePrefix;
      $Sql = "insert {$Px}UserMeta (UserID, Name, Value) values(:UserID, :Name, :Value) on duplicate key update Value = :Value1";

      foreach ($Meta as $Name => $Value) {
         $Name = $Prefix.$Name;
         if ($Value === NULL)
            $Deletes[] = $Name;
         else
            Gdn::Database()->Query($Sql, array(':UserID' => $UserID, ':Name' => $Name, ':Value' => $Value, ':Value1' => $Value));
      }
      if (count($Deletes))
         Gdn::SQL()->WhereIn('Name', $Deletes)->Where('UserID',$UserID)->Delete('UserMeta');
   }

   public function SetTransientKey($UserID, $ExplicitKey = '') {
      $Key = $ExplicitKey == '' ? RandomString(12) : $ExplicitKey;
      $this->SaveAttribute($UserID, 'TransientKey', $Key);
      return $Key;
   }

   public function GetAttribute($UserID, $Attribute, $DefaultValue = FALSE) {
//
//      $Result = $DefaultValue;
//      if ($Data !== FALSE) {
//         $Attributes = Gdn_Format::Unserialize($Data->Attributes);
//         if (is_array($Attributes))
//            $Result = ArrayValue($Attribute, $Attributes, $DefaultValue);
//
//      }
      
      $User = $this->GetID($UserID, DATASET_TYPE_ARRAY);
      $Result = GetValue($Attribute, $User['Attributes'], $DefaultValue);
      
      return $Result;
   }

   public function SendEmailConfirmationEmail($User = NULL) {
      if (!$User)
         $User = Gdn::Session()->User;
      elseif (is_numeric($User))
         $User = $this->GetID($User);
      elseif (is_string($User)) {
         $User = $this->GetByEmail($User);
      }
      
      if (!$User)
         throw NotFoundException('User');
      
      $User = (array)$User;

      if (is_string($User['Attributes']))
         $User['Attributes'] = @unserialize($User['Attributes']);

      // Make sure the user needs email confirmation.
      $Roles = $this->GetRoles($User['UserID']);
      $Roles = ConsolidateArrayValuesByKey($Roles, 'RoleID');
      if (!in_array(C('Garden.Registration.ConfirmEmailRole'), $Roles)) {
         $this->Validation->AddValidationResult('Role', 'Your email doesn\'t need confirmation.');
         
         // Remove the email key.
         if (isset($User['Attributes']['EmailKey'])) {
            unset($User['Attributes']['EmailKey']);
            $this->SaveAttribute($User['UserID'], $User['Attribute']);
         }

         return;
      }

      // Make sure there is a confirmation code.
      $Code = GetValueR('Attributes.EmailKey', $User);
      if (!$Code) {
         $Code = RandomString(8);
         $Attributes = $User['Attributes'];
         if (!is_array($Attributes))
            $Attributes = array('EmailKey' => $Code);
         else
            $Attributes['EmailKey'] = $Code;
         
         $this->SaveAttribute($User['UserID'], $Attributes);
      }
      
      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%s] Confirm Your Email Address'), $AppTitle));
      $Email->To($User['Email']);

      $EmailFormat = T('EmailConfirmEmail', self::DEFAULT_CONFIRM_EMAIL);
      $Data = array();
      $Data['EmailKey'] = $Code;
      $Data['User'] = ArrayTranslate((array)$User, array('UserID', 'Name', 'Email'));
      $Data['Title'] = $AppTitle;

      $Message = FormatString($EmailFormat, $Data);
      $Message = $this->_AddEmailHeaderFooter($Message, $Data);
      $Email->Message($Message);

      $Email->Send();
   }

   public function SendWelcomeEmail($UserID, $Password, $RegisterType = 'Add', $AdditionalData = NULL) {
      $Session = Gdn::Session();
      $Sender = $this->GetID($Session->UserID);
      $User = $this->GetID($UserID);

      if (!ValidateEmail($User->Email))
         return;

      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%s] Welcome Aboard!'), $AppTitle));
      $Email->To($User->Email);

      $Data = array();
      $Data['User'] = ArrayTranslate((array)$User, array('UserID', 'Name', 'Email'));
      $Data['Sender'] = ArrayTranslate((array)$Sender, array('Name', 'Email'));
      $Data['Title'] = $AppTitle;
      if (is_array($AdditionalData))
         $Data = array_merge($Data, $AdditionalData);

      $Data['EmailKey'] = GetValueR('Attributes.EmailKey', $User);

      // Check for the new email format.
      if (($EmailFormat = T("EmailWelcome{$RegisterType}", '#')) != '#') {
         $Message = FormatString($EmailFormat, $Data);
      } else {
         $Message = sprintf(
            T('EmailWelcome'),
            $User->Name,
            $Sender->Name,
            $AppTitle,
            ExternalUrl('/'),
            $Password,
            $User->Email
         );
      }

      // Add the email confirmation key.
      if ($Data['EmailKey']) {
         $Message .= "\n\n".FormatString(T('EmailConfirmEmail', self::DEFAULT_CONFIRM_EMAIL), $Data);
      }
      $Message = $this->_AddEmailHeaderFooter($Message, $Data);

      $Email->Message($Message);

      $Email->Send();
   }

   public function SendPasswordEmail($UserID, $Password) {
      $Session = Gdn::Session();
      $Sender = $this->GetID($Session->UserID);
      $User = $this->GetID($UserID);
      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%s] Password Reset'), $AppTitle));
      $Email->To($User->Email);

      $Data = array();
      $Data['User'] = ArrayTranslate((array)$User, array('Name', 'Email'));
      $Data['Sender'] = ArrayTranslate((array)$Sender, array('Name', 'Email'));
      $Data['Title'] = $AppTitle;

      $EmailFormat = T('EmailPassword');
      if (strpos($EmailFormat, '{') !== FALSE) {
         $Message = FormatString($EmailFormat, $Data);
      } else {
         $Message = sprintf(
            $EmailFormat,
            $User->Name,
            $Sender->Name,
            $AppTitle,
            ExternalUrl('/'),
            $Password,
            $User->Email
         );
      }

      $Message = $this->_AddEmailHeaderFooter($Message, $Data);
      $Email->Message($Message);

      $Email->Send();
   }
   
   /**
    * Synchronizes the user based on a given UserKey.
    *
    * @param string $UserKey A string that uniquely identifies this user.
    * @param array $Data Information to put in the user table.
    * @return int The ID of the user.
    */
   public function Synchronize($UserKey, $Data) {
      $UserID = 0;
      
      $Attributes = ArrayValue('Attributes', $Data);
      if (is_string($Attributes))
         $Attributes = @unserialize($Attributes);

      if (!is_array($Attributes))
         $Attributes = array();

      // If the user didnt log in, they won't have a UserID yet. That means they want a new
      // account. So create one for them.
      if (!isset($Data['UserID']) || $Data['UserID'] <= 0) {
      
         // Prepare the user data.
         $UserData['Name'] = $Data['Name'];
         $UserData['Password'] = RandomString(16);
         $UserData['Email'] = ArrayValue('Email', $Data, 'no@email.com');
         $UserData['Gender'] = strtolower(substr(ArrayValue('Gender', $Data, 'u'), 0, 1));
         $UserData['HourOffset'] = ArrayValue('HourOffset', $Data, 0);
         $UserData['DateOfBirth'] = ArrayValue('DateOfBirth', $Data, '');
         $UserData['CountNotifications'] = 0;
         $UserData['Attributes'] = $Attributes;
         $UserData['InsertIPAddress'] = Gdn::Request()->IpAddress();
         if ($UserData['DateOfBirth'] == '')
            $UserData['DateOfBirth'] = '1975-09-16';
            
         // Make sure there isn't another user with this username.
         if ($this->ValidateUniqueFields($UserData['Name'], $UserData['Email'])) {
            if (!BanModel::CheckUser($UserData, $this->Validation, TRUE))
               throw PermissionException('Banned');
            
            // Insert the new user.
            $this->AddInsertFields($UserData);
            $UserID = $this->_Insert($UserData);
         }

         if ($UserID) {
            $NewUserRoleIDs = $this->NewUserRoleIDs();
            
            // Save the roles.
            $Roles = GetValue('Roles', $Data, FALSE);
            if (empty($Roles))
               $Roles = $NewUserRoleIDs;
            
            $this->SaveRoles($UserID, $Roles, FALSE);
         }
      } else {
         $UserID = $Data['UserID'];
      }
      
      // Synchronize the transientkey from the external user data source if it is present (eg. WordPress' wpnonce).
      if (array_key_exists('TransientKey', $Attributes) && $Attributes['TransientKey'] != '' && $UserID > 0)
         $this->SetTransientKey($UserID, $Attributes['TransientKey']);

      return $UserID;
   }
   
   public function NewUserRoleIDs() {
      // Registration method
      $RegistrationMethod = C('Garden.Registration.Method', 'Captcha');
      $DefaultRoleID = C('Garden.Registration.DefaultRoles');
      switch ($RegistrationMethod) {
      
         case 'Approval':
            $RoleID = C('Garden.Registration.ApplicantRoleID', $DefaultRoleID);
         break;
         
         case 'Invitation':
            throw new Gdn_UserException(T('This forum is currently set to invitation only mode.'));
         break;
         
         case 'Basic':
         case 'Captcha':
         default:
            $RoleID = $DefaultRoleID;
         break;
      }
      
      if (empty($RoleID))
         Trace("You don't have any default roles defined.", TRACE_WARNING);
      return $RoleID;
   }
   
   public function PasswordRequest($Email) {
      if (!$Email) {
         return FALSE;
      }

      $Users = $this->GetWhere(array('Email' => $Email))->ResultObject();
      if (count($Users) == 0) {
         // Check for the username.
         $Users = $this->GetWhere(array('Name' => $Email))->ResultObject();
      }

      $this->EventArguments['Users'] =& $Users;
      $this->EventArguments['Email'] = $Email;
      $this->FireEvent('BeforePasswordRequest');
      
      if (count($Users) == 0) {
         $this->Validation->AddValidationResult('Name', "Couldn't find an account associated with that email/username.");
         return FALSE;
      }

      $Email = new Gdn_Email();
      $NoEmail = TRUE;
      
      foreach ($Users as $User) {
         if (!$User->Email) {
            continue;
         }

         $Email = new Gdn_Email(); // Instantiate in loop to clear previous settings
         $PasswordResetKey = BetterRandomString(20, 'Aa0');
         $PasswordResetExpires = strtotime('+1 hour');
         $this->SaveAttribute($User->UserID, 'PasswordResetKey', $PasswordResetKey);
         $this->SaveAttribute($User->UserID, 'PasswordResetExpires', $PasswordResetExpires);
         $AppTitle = C('Garden.Title');
         $Email->Subject(sprintf(T('[%s] Password Reset Request'), $AppTitle));
         $Email->To($User->Email);
         
         $Email->Message(
            sprintf(
               T('PasswordRequest'),
               $User->Name,
               $AppTitle,
               ExternalUrl('/entry/passwordreset/'.$User->UserID.'/'.$PasswordResetKey)
            )
         );
         $Email->Send();
         $NoEmail = FALSE;
      }
      
      if ($NoEmail) {
         $this->Validation->AddValidationResult('Name', 'There is no email address associated with that account.');
         return FALSE;
      }
      return TRUE;
   }

   public function PasswordReset($UserID, $Password) {
      // Encrypt the password before saving
      $PasswordHash = new Gdn_PasswordHash();
      $Password = $PasswordHash->HashPassword($Password);

      $this->SQL->Update('User')->Set('Password', $Password)->Set('HashMethod', 'Vanilla')->Where('UserID', $UserID)->Put();
      $this->SaveAttribute($UserID, 'PasswordResetKey', '');
      $this->SaveAttribute($UserID, 'PasswordResetExpires', '');

      $this->EventArguments['UserID'] = $UserID;
      $this->FireEvent('AfterPasswordReset');

      return $this->GetID($UserID);
   }
   
   /**
    * Check and apply login rate limiting
    * 
    * @param array $User
    * @param boolean $PasswordOK
    */
   public static function RateLimit($User, $PasswordOK) {
      if (!Gdn::Cache()->ActiveEnabled()) return FALSE;
//      $CoolingDown = FALSE;
//      
//      // 1. Check if we're in userid cooldown
//      $UserCooldownKey = FormatString(self::LOGIN_COOLDOWN_KEY, array('Source' => $User['UserID']));
//      if (!$CoolingDown) {
//         $InUserCooldown = Gdn::Cache()->Get($UserCooldownKey);
//         if ($InUserCooldown) {
//            $CoolingDown = $InUserCooldown;
//            $CooldownError = T('LoginUserCooldown', "Your account is temporarily locked due to failed login attempts. Try again in %s.");
//         }
//      }
//      
//      // 2. Check if we're in source IP cooldown
//      $SourceCooldownKey = FormatString(self::LOGIN_COOLDOWN_KEY, array('Source' => Gdn::Request()->IpAddress()));
//      if (!$CoolingDown) {
//         $InSourceCooldown = Gdn::Cache()->Get($SourceCooldownKey);
//         if ($InSourceCooldown) {
//            $CoolingDown = $InUserCooldown;
//            $CooldownError = T('LoginSourceCooldown', "Your IP is temporarily blocked due to failed login attempts. Try again in %s.");
//         }
//      }
//      
//      // Block cooled down people
//      if ($CoolingDown) {
//         $Timespan = $InUserCooldown;
//         $Timespan -= 3600 * ($Hours = (int) floor($Timespan / 3600));
//         $Timespan -= 60 * ($Minutes = (int) floor($Timespan / 60));
//         $Seconds = $Timespan;
//      
//         $TimeFormat = array();
//         if ($Hours) $TimeFormat[] = "{$Hours} ".Plural($Hours, 'hour', 'hours');
//         if ($Minutes) $TimeFormat[] = "{$Minutes} ".Plural($Minutes, 'minute', 'minutes');
//         if ($Seconds) $TimeFormat[] = "{$Seconds} ".Plural($Seconds, 'second', 'seconds');
//         $TimeFormat = implode(', ', $TimeFormat);
//         throw new Exception(sprintf($CooldownError, $TimeFormat));
//      }
//      
//      // Logged in OK
//      if ($PasswordOK) {
//         Gdn::Cache()->Remove($UserCooldownKey);
//         Gdn::Cache()->Remove($SourceCooldownKey);
//      }
      
      // Rate limiting
      $UserRateKey = FormatString(self::LOGIN_RATE_KEY, array('Source' => $User->UserID));
      $UserRate = (int)Gdn::Cache()->Get($UserRateKey);
      $UserRate += 1;
      Gdn::Cache()->Store($UserRateKey, 1, array(
         Gdn_Cache::FEATURE_EXPIRY => self::LOGIN_RATE
      ));
      
      $SourceRateKey = FormatString(self::LOGIN_RATE_KEY, array('Source' => Gdn::Request()->IpAddress()));
      $SourceRate = (int)Gdn::Cache()->Get($SourceRateKey);
      $SourceRate += 1;
      Gdn::Cache()->Store($SourceRateKey, 1, array(
         Gdn_Cache::FEATURE_EXPIRY => self::LOGIN_RATE
      ));
      
      // Put user into cooldown mode
      if ($UserRate > 1)
         throw new Gdn_UserException(T('LoginUserCooldown', "You are trying to log in too often. Slow down!."));
      
      if ($SourceRate > 1)
         throw new Gdn_UserException(T('LoginSourceCooldown', "Your IP is trying to log in too often. Slow down!"));
      
      return TRUE;
   }
   
	public function SetField($RowID, $Property, $Value = FALSE) {
      if (!is_array($Property))
         $Property = array($Property => $Value);

      // Convert IP addresses to long.
      if (isset($Property['AllIPAddresses'])) {
         if (is_array($Property['AllIPAddresses'])) {
            $IPs = array_map('ForceIPv4', $Property['AllIPAddresses']);
            $IPs = array_unique($IPs);
            $Property['AllIPAddresses'] = implode(',', $IPs);
         }
      }
      
      $this->DefineSchema();      
      $Set = array_intersect_key($Property, $this->Schema->Fields());
      self::SerializeRow($Set);
      
		$this->SQL
            ->Update($this->Name)
            ->Set($Set)
            ->Where('UserID', $RowID)
            ->Put();
      
      if (in_array($Property, array('Permissions')))
         $this->ClearCache ($RowID, array('permissions'));
      else
         $this->UpdateUserCache($RowID, $Property, $Value);
      
      if (!is_array($Property))
         $Property = array($Property => $Value);
      
      $this->EventArguments['UserID'] = $RowID;
      $this->EventArguments['Fields'] = $Property;
      $this->FireEvent('AfterSetField');
      
		return $Value;
   }
   
   /**
    * Get a user from the cache by name or ID
    * 
    * @param type $UserToken either a userid or a username
    * @param string $TokenType either 'userid' or 'name'
    * @return type user array or FALSE
    */
   public function GetUserFromCache($UserToken, $TokenType) {
      if ($TokenType == 'name') {
         $UserNameKey = FormatString(self::USERNAME_KEY, array('Name' => md5($UserToken)));
         $UserID = Gdn::Cache()->Get($UserNameKey);
         
         if ($UserID === Gdn_Cache::CACHEOP_FAILURE) return FALSE;
         $UserToken = $UserID; $TokenType = 'userid';
      } else {
         $UserID = $UserToken;
      }
      
      if ($TokenType != 'userid') return FALSE;
      
      // Get from memcached
      $UserKey = FormatString(self::USERID_KEY, array('UserID' => $UserToken));
      $User = Gdn::Cache()->Get($UserKey);
      
      return $User;
   }
   
   public function UpdateUserCache($UserID, $Field, $Value = NULL) {
      $User = $this->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!is_array($Field))
         $Field = array($Field => $Value);
      
      foreach ($Field as $f => $v) {
         $User[$f] = $v;
      }
      $this->UserCache($User);
   }
   
   /**
    * Cache user object
    * 
    * @param type $User
    * @return type 
    */
   public function UserCache($User) {
      $UserID = GetValue('UserID', $User, NULL);
      if (is_null($UserID) || !$UserID) return FALSE;
      
      $Cached = TRUE;
      
      $UserKey = FormatString(self::USERID_KEY, array('UserID' => $UserID));
      $Cached = $Cached & Gdn::Cache()->Store($UserKey, $User, array(
         Gdn_Cache::FEATURE_EXPIRY  => 3600
      ));
      
      $UserNameKey = FormatString(self::USERNAME_KEY, array('Name' => md5(GetValue('Name', $User))));
      $Cached = $Cached & Gdn::Cache()->Store($UserNameKey, $UserID, array(
         Gdn_Cache::FEATURE_EXPIRY  => 3600
      ));
      return $Cached;
   }
   
   /**
    * Cache user's roles
    * 
    * @param type $UserID
    * @param type $RoleIDs
    * @return type 
    */
   public function UserCacheRoles($UserID, $RoleIDs) {
      if (is_null($UserID) || !$UserID) return FALSE;
      
      $Cached = TRUE;
      
      $UserRolesKey = FormatString(self::USERROLES_KEY, array('UserID' => $UserID));
      $Cached = $Cached & Gdn::Cache()->Store($UserRolesKey, $RoleIDs);
      return $Cached;
   }
   
   /**
    * Delete cached data for user
    * 
    * @param type $UserID
    * @return type 
    */
   public function ClearCache($UserID, $CacheTypesToClear = NULL) {
      if (is_null($UserID) || !$UserID) return FALSE;
      
      if (is_null($CacheTypesToClear))
         $CacheTypesToClear = array('user', 'roles', 'permissions');
      
      if (in_array('user', $CacheTypesToClear)) {
         $UserKey = FormatString(self::USERID_KEY, array('UserID' => $UserID));
         Gdn::Cache()->Remove($UserKey);
      }
      
      if (in_array('roles', $CacheTypesToClear)) {
         $UserRolesKey = FormatString(self::USERROLES_KEY, array('UserID' => $UserID));
         Gdn::Cache()->Remove($UserRolesKey);
      }
      
      if (in_array('permissions', $CacheTypesToClear)) {
         Gdn::SQL()->Put('User', array('Permissions' => ''), array('UserID' => $UserID));
         
         $PermissionsIncrement = $this->GetPermissionsIncrement();
         $UserPermissionsKey = FormatString(self::USERPERMISSIONS_KEY, array('UserID' => $UserID, 'PermissionsIncrement' => $PermissionsIncrement));
         Gdn::Cache()->Remove($UserPermissionsKey);
      }
      return TRUE;
   }
   
   public function ClearPermissions() {
      if (!Gdn::Cache()->ActiveEnabled())
         $this->SQL->Put('User', array('Permissions' => ''), array('Permissions <>' => ''));
      
      $PermissionsIncrementKey = self::INC_PERMISSIONS_KEY;
      $PermissionsIncrement = $this->GetPermissionsIncrement();
      if ($PermissionsIncrement == 0)
         Gdn::Cache()->Store($PermissionsIncrementKey, 1);
      else
         Gdn::Cache()->Increment($PermissionsIncrementKey);
   }
   
   public function GetPermissionsIncrement() {
      $PermissionsIncrementKey = self::INC_PERMISSIONS_KEY;
      $PermissionsKeyValue = Gdn::Cache()->Get($PermissionsIncrementKey);
      
      if (!$PermissionsKeyValue) {
         $Stored = Gdn::Cache()->Store($PermissionsIncrementKey, 1);
         return $Stored ? 1 : FALSE;
      }
      
      return $PermissionsKeyValue;;
   }
}
