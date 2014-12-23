<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['EmailPasswordSync'] = array(
   'Name' => 'Email Password Sync',
   'Description' => 'Synchronizes passwords when users with the same email/password combos change their passwords.',
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.0.17'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class MultipleEmailsPlugin extends Gdn_Plugin {
   /// Properties
   protected $_OldPasswordHash;

   /// Methods.
   public function Setup() {
      // Allow multiple emails.
      SaveToConfig('Garden.Registration.EmailUnique', FALSE);
   }

   /**
    *
    * @param UserModel $UserModel
    * @param int $UserID
    * @param bool $CheckPasswords
    */
   protected function _SyncPasswords($UserModel, $UserID, $CheckPasswords = TRUE) {
      $User = $UserModel->GetID($UserID, DATASET_TYPE_ARRAY);

      if ($CheckPasswords) {
         if (is_array($this->_OldPasswordHash)) {
            $UserModel->SQL
               ->Where('Password', $this->_OldPasswordHash[0])
               ->Where('HashMethod', $this->_OldPasswordHash[1]);

            $this->_OldPasswordHash = NULL;
         } else {
            return;
         }
      }

      $UserModel->SQL
         ->Update('User')
         ->Set('Password', $User['Password'])
         ->Set('HashMethod', $User['HashMethod'])
         ->Where('Email', $User['Email'])
         ->Put();
   }

   /**
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function EntryController_Render_Before($Sender, $Args) {
      if ($Sender->RequestMethod != 'passwordreset')
         return;

      if (isset($Sender->Data['User'])) {
         // Get all of the users with the same email.
         $Email = $Sender->Data('User.Email');
         $Users = Gdn::SQL()->Select('Name')->From('User')->Where('Email', $Email)->Get()->ResultArray();
         $Names = ConsolidateArrayValuesByKey($Users, 'Name');

         SetValue('Name', $Sender->Data['User'], implode(', ', $Names));
      }
   }

   /**
    * @param UserModel $UserModel
    * @param array $Args
    */
   public function UserModel_AfterInsertUser_Handler($UserModel, $Args) {
      $Password = GetValue('User/Password', $_POST);
      if (!$Password)
         return;

      // See if there is a user with the same email/password.
      $Users = $UserModel->GetWhere(array('Email' => GetValueR('InsertFields.Email', $Args)))->ResultArray();
      $Hasher = new Gdn_PasswordHash();

      foreach ($Users as $User) {
         if ($Hasher->CheckPassword($Password, $User['Password'], $User['HashMethod'])) {
            $UserModel->SQL->Put(
               'User',
               array('Password' => $User['Password'], 'HashMethod' => $User['HashMethod']),
               array('UserID' => GetValue('InsertUserID', $Args)));
            return;
         }
      }
   }

   /**
    * @param UserModel $UserModel
    * @param array $Args
    */
   public function UserModel_AfterPasswordReset_Handler($UserModel, $Args) {
      $UserID = GetValue('UserID', $Args);

      $this->_SyncPasswords($UserModel, $UserID, FALSE);
   }

   /**
    * @param UserModel $UserModel
    * @param array $Args
    */
   public function UserModel_BeforeSave_Handler($UserModel, $Args) {
      if (isset($Args['Fields']) && !isset($Args['Fields']['Password']))
         return;
      
      // Grab the current passwordhash for comparison.
      $UserID = GetValueR('FormPostValues.UserID', $Args);
      if ($UserID) {
         $CurrentUser = $UserModel->GetID($UserID, DATASET_TYPE_ARRAY);
         $this->_OldPasswordHash = array($CurrentUser['Password'], $CurrentUser['HashMethod']);
      }
   }

   /**
    * @param UserModel $UserModel
    * @param array $Args
    */
   public function UserModel_AfterSave_Handler($UserModel, $Args) {
      if (isset($Args['Fields']) && !isset($Args['Fields']['Password']))
         return;

      $UserID = GetValue('UserID', $Args);

      $this->_SyncPasswords($UserModel, $UserID);
   }

   /**
    * Consolidates users with the same email into one user so only one password request email is sent.
    *
    * @param UserModel $UserModel
    * @param array $Args
    */
   public function UserModel_BeforePasswordRequest_Handler($UserModel, $Args) {
      $Email = $Args['Email'];
      $Users =& $Args['Users'];

      $Names = array();

      foreach ($Users as $Index => $User) {
         if ($User->Email == $Email) {
            if (!isset($EmailUser)) {
               $EmailUser = $User;
            }

            $Names[] = $User->Name;

            if ($User->UserID <> $EmailUser->UserID)
               unset($Users[$Index]);
         }
      }
      if (isset($EmailUser)) {
         sort($Names);
         $EmailUser->Name = implode(', ', $Names);
      }
      
      $this->EventArguments['Users'] = $Users;
      $this->EventArguments['Email'] = $Email;
      $this->FireEvent('PasswordRequestBefore');
   }
}