<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ProfileController extends Gdn_Controller {
   
   public $Uses = array('Form', 'Gdn_UserModel');

   public $User;
   protected $_TabView;
   protected $_TabController;
   protected $_TabApplication;
   protected $_CurrentTab;
   protected $_ProfileTabs;
   
   public function __construct() {
      $this->User = FALSE;
      $this->_TabView = 'Activity';
      $this->_TabController = 'ProfileController';
      $this->_TabApplication = 'Garden';
      $this->_CurrentTab = 'Activity';
      $this->_ProfileTabs = array();
      parent::__construct();
   }
   
   public function GetUserInfo($UserReference = '') {
      $this->Roles = array();
      if ($UserReference == '') {
         $Session = Gdn::Session();
         $this->User = $this->UserModel->Get($Session->UserID);
      } else {
         $UserReference = is_numeric($UserReference) ? $UserReference : urldecode($UserReference);
         $this->User = $this->UserModel->Get($UserReference);
      }
         
      if ($this->User === FALSE) {
         Redirect('garden/home/filenotfound');
      } else {
         $this->RoleData = $this->UserModel->GetRoles($this->User->UserID);
         if ($this->RoleData !== FALSE && $this->RoleData->NumRows() > 0) 
            $this->Roles = ConsolidateArrayValuesByKey($this->RoleData->ResultArray(), 'Name');
      }
      
      // Make sure the userphoto module gets added to the page
      $UserPhotoModule = new UserPhotoModule($this);
      $UserPhotoModule->User = $this->User;
      $this->AddModule($UserPhotoModule);
      
      $this->AddSideMenu();
      return TRUE;
   }
   
   public function BuildProfile($UserReference = '') {
      $Session = Gdn::Session();
      
      $this->CssClass = 'Profile';
      if (!$this->GetUserInfo($UserReference))
         return FALSE;

      $this->Title(Format::Text($this->User->Name));
      
      if ($this->_DeliveryType != DELIVERY_TYPE_VIEW) {
         $UserInfoModule = new UserInfoModule($this);
         $UserInfoModule->User = $this->User;
         $UserInfoModule->Roles = $this->Roles;
         $this->AddModule($UserInfoModule);
         $this->AddJsFile('/js/library/jquery.jcrop.pack.js');
         $this->AddJsFile('profile.js');
         $this->AddJsFile('activity.js');
         $this->AddProfileTab('Activity');
         if ($this->User->UserID == $Session->UserID) {
            $Notifications = Gdn::Translate('Notifications');
            $CountNotifications = $Session->User->CountNotifications;
            if (is_numeric($CountNotifications) && $CountNotifications > 0)
               $Notifications .= '<span>'.$CountNotifications.'</span>';
               
            $this->AddProfileTab(array($Notifications => 'profile/notifications'));
         }
            
         $this->FireEvent('AddProfileTabs');
      }
      
      return TRUE;
   }
   
   public function Index($UserReference = '') {
      $this->Activity($UserReference);
   }
   
   public function Clear($UserID = '', $TransientKey = '') {
      $UserID = is_numeric($UserID) ? $UserID : 0;
      $Session = Gdn::Session();
      if ($Session->IsValid() && $Session->ValidateTransientKey($TransientKey)) {
         if ($UserID != $Session->UserID && !$Session->CheckPermission('Garden.Users.Edit'))
            $UserID = 0;

         if ($UserID > 0)
            $this->UserModel->SaveAbout($UserID, '');
      }

      if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
         Redirect('/profile');
   }

   public function SetTabView($UserReference, $CurrentTab, $View = '', $Controller = 'Profile', $Application = 'Garden') {
      if (!$this->BuildProfile($UserReference))
         Redirect('garden/home/filenotfound');

      if ($View == '')
         $View = $CurrentTab;
         
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL && $this->SyndicationMethod == SYNDICATION_NONE) {
         $this->AddDefinition('DefaultAbout', Gdn::Translate('Write something about yourself...'));
         $this->View = 'index';
         $this->_TabView = $View;
         $this->_TabController = $Controller;
         $this->_TabApplication = $Application;
      } else {
         $this->View = $View;
         $this->ControllerName = $Controller;
         $this->ApplicationFolder = $Application;
      }
      $this->_CurrentTab = $CurrentTab;
   }
   
   public function Activity($UserReference = '') {
      $this->SetTabView($UserReference, 'Activity');
      $this->ActivityModel = new Gdn_ActivityModel();
      $Session = Gdn::Session();
      $Comment = $this->Form->GetFormValue('Comment');
      if ($Session->UserID > 0 && $this->Form->AuthenticatedPostBack() && !StringIsNullOrEmpty($Comment)) {
         $Comment = substr($Comment, 0, 1000); // Limit to 1000 characters...
         
         // Update About if necessary
         $ActivityType = 'WallComment';
         $SendNotification = TRUE;
         if ($Session->UserID == $this->User->UserID) {
            $SendNotification = FALSE;
            $this->UserModel->SaveAbout($Session->UserID, $Comment);
            $this->User->About = $Comment;
            $this->SetJson('UserData', $this->FetchView('user'));
            $ActivityType = 'AboutUpdate';
         }
         $NewActivityID = $this->ActivityModel->Add(
            $Session->UserID,
            $ActivityType,
            $Comment,
            $this->User->UserID,
            '',
            '/profile/'.$this->User->UserID.'/'.Format::Url($this->User->Name),
            $SendNotification);
         
         if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            Redirect('garden/profile/'.$UserReference);
         } else {
            // Load just the single new comment
            $this->HideActivity = TRUE;
            $this->ActivityData = $this->ActivityModel->GetWhere('ActivityID', $NewActivityID);
            $this->View = 'activities';
            $this->ControllerName = 'activity';
         }
      } else {
         $this->ProfileUserID = $this->User->UserID;
         $this->ActivityData = $this->ActivityModel->Get($this->User->UserID);
         if ($this->ActivityData->NumRows() > 0) {
            $ActivityData = $this->ActivityData->ResultArray();
            $ActivityIDs = ConsolidateArrayValuesByKey($ActivityData, 'ActivityID');
            $LastActivity = $this->ActivityData->FirstRow();
            $LastModifiedDate = Format::ToTimestamp($this->User->DateUpdated);
            $LastActivityDate = Format::ToTimestamp($LastActivity->DateInserted);
            if ($LastModifiedDate < $LastActivityDate)
               $LastModifiedDate = $LastActivityDate;
               
            // Make sure to only query this page if the user has no new activity since the requesting browser last saw it.
            $this->SetLastModified($LastModifiedDate);
            $this->CommentData = $this->ActivityModel->GetComments($ActivityIDs);
         } else {
            $this->CommentData = FALSE;
         }
      }
      
      $this->Render();
   }
   
   public function Edit($UserReference = '') {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo($UserReference);
      $Session = Gdn::Session();
      if ($Session->UserID != $this->User->UserID)
         $this->Permission('Garden.User.Edit');
         
      $UserModel = Gdn::UserModel();
      $this->Form->SetModel($UserModel);
      $this->Form->AddHidden('UserID', $this->User->UserID);
      
      // Define gender dropdown options
      $this->GenderOptions = array(
         'm' => Gdn::Translate('Male'),
         'f' => Gdn::Translate('Female')
      );
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Get the user data for the requested $UserID and put it into the form.
         $this->Form->SetData($this->User);
      } else {
         $UserModel->Validation->ApplyRule('Name', 'Username', 'Username can only contain letters, numbers, and underscores.');
         if ($this->Form->Save() !== FALSE) {
            $User = $UserModel->Get($this->User->UserID);
            $this->StatusMessage = Translate("Your changes have been saved successfully.");
            $this->RedirectUrl = Url('/profile/'.urlencode($User->Name));
         }
      }
      
      $this->Render();
   }

   public function Notifications() {
      $this->Permission('Garden.SignIn.Allow');
      $Session = Gdn::Session();
      // Drop notification count back to zero.
      $SQL = Gdn::SQL();
      $SQL
         ->Update('User')
         ->Set('CountNotifications', '0')
         ->Where('UserID', $Session->UserID)
         ->Put();
      
      $this->ActivityModel = new Gdn_ActivityModel();
      $this->ActivityData = $this->ActivityModel->GetNotifications($Session->UserID);
      $this->SetTabView($Session->UserID, 'Notifications');
      $this->Render();
   }   
   
   public function Password() {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo();
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('UserID', $this->User->UserID);
      if ($this->Form->AuthenticatedPostBack() === TRUE) {
         $this->UserModel->DefineSchema();
         // $this->UserModel->Validation->AddValidationField('OldPassword', $this->Form->FormValues());
         $this->UserModel->Validation->ApplyRule('OldPassword', 'Required');
         $this->UserModel->Validation->ApplyRule('OldPassword', 'OldPassword', 'Your old password was incorrect.');
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         if ($this->Form->Save()) {
            $this->StatusMessage = Translate("Your password has been changed.");
            $this->Form->ClearInputs();
         }
      }
      $this->Render();
   }
   
   public function Picture($UserReference = '') {
      $this->Permission('Garden.SignIn.Allow');
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');
         
      $this->GetUserInfo($UserReference);
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('UserID', $this->User->UserID);
      if ($this->Form->AuthenticatedPostBack() === TRUE) {
         $UploadImage = new Gdn_UploadImage();
         try {
            // Validate the upload
            $TmpImage = $UploadImage->ValidateUpload('Picture');
            
            // Generate the target image name
            $TargetImage = $UploadImage->GenerateTargetName(PATH_ROOT . DS . 'uploads');
            $ImageBaseName = pathinfo($TargetImage, PATHINFO_BASENAME);
            
            // Delete any previously uploaded images
            @unlink(PATH_ROOT . DS . 'uploads' . DS . 'p' . $this->User->Photo);
            // Don't delete this one because it hangs around in activity streams:
            // @unlink(PATH_ROOT . DS . 'uploads' . DS . 't' . $this->User->Photo); 
            @unlink(PATH_ROOT . DS . 'uploads' . DS . 'n' . $this->User->Photo);
            
            // Save the uploaded image in profile size
            $UploadImage->SaveImageAs(
               $TmpImage,
               PATH_ROOT . DS . 'uploads' . DS . 'p'.$ImageBaseName,
               Gdn::Config('Garden.Profile.MaxHeight', 1000),
               Gdn::Config('Garden.Profile.MaxWidth', 250)
            );
            
            // Save the uploaded image in preview size
            $UploadImage->SaveImageAs(
               $TmpImage,
               PATH_ROOT . DS . 'uploads' . DS . 't'.$ImageBaseName,
               Gdn::Config('Garden.Preview.MaxHeight', 100),
               Gdn::Config('Garden.Preview.MaxWidth', 75)
            );

            // Save the uploaded image in thumbnail size
            $ThumbSize = Gdn::Config('Garden.Thumbnail.Size', 50);
            $UploadImage->SaveImageAs(
               $TmpImage,
               PATH_ROOT . DS . 'uploads' . DS . 'n'.$ImageBaseName,
               $ThumbSize,
               $ThumbSize,
               TRUE
            );
            
         } catch (Exception $ex) {
            $this->Form->AddError($ex->getMessage());
         }
         // If there were no errors, associate the image with the user
         if ($this->Form->ErrorCount() == 0) {
            $PhotoModel = new Gdn_Model('Photo');
            $PhotoID = $PhotoModel->Insert(array('Name' => $ImageBaseName));
            if (!$this->UserModel->Save(array('UserID' => $this->User->UserID, 'PhotoID' => $PhotoID, 'Photo' => $ImageBaseName)))
               $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         }
         // If there were no problems, redirect back to the user account
         if ($this->Form->ErrorCount() == 0)
            Redirect('garden/profile/'.$UserReference);
      }
      $this->Render();
   }
   
   public function Preferences($UserReference = '') {
      $Session = Gdn::Session();
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo($UserReference);
      $UserPrefs = Format::Unserialize($this->User->Preferences);
      if (!is_array($UserPrefs))
         $UserPrefs = array();

      // Define the preferences to be managed
      $this->Preferences = array(
         'Email Notifications' => array(
            'Email.WallComment' => Gdn::Translate('Notify me when people write on my wall.'),
            'Email.ActivityComment' => Gdn::Translate('Notify me when people reply to my wall comments.')
         )
      );
      
      $this->FireEvent('AfterPreferencesDefined');

      if ($this->User->UserID != $Session->UserID)
         $this->Permission('Garden.Users.Edit');
         
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Loop the preferences, setting defaults from the configuration
         $Defaults = array();
         foreach ($this->Preferences as $PrefGroup => $Prefs) {
            foreach ($Prefs as $Pref => $Desc) {
               $Defaults[$Pref] = ArrayValue($Pref, $UserPrefs, Gdn::Config('Preferences.'.$Pref, '0'));
            }
         }
         $this->Form->SetData($Defaults);
      } else {
         // Get, assign, and save the preferences
         foreach ($this->Preferences as $PrefGroup => $Prefs) {
            foreach ($Prefs as $Pref => $Desc) {
               $UserPrefs[$Pref] = $this->Form->GetValue($Pref, '0');
            }
         }
         $this->UserModel->SavePreference($this->User->UserID, $UserPrefs);
         $this->StatusMessage = Translate("Your preferences have been saved.");
      }
      $this->Render();
   }
   
   public function RemovePicture($UserReference = '', $TransientKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');
         
      $this->GetUserInfo($UserReference);
      $RedirectUrl = 'garden/profile/'.$UserReference;
      if ($Session->ValidateTransientKey($TransientKey)
         && is_object($this->User)
         && (
            $this->User->UserID == $Session->UserID
            || $Session->CheckPermission('Garden.Users.Edit')
         )
      ) {
         Gdn::UserModel()->RemovePicture($this->User->UserID);
         $this->StatusMessage = Gdn::Translate('Your picture has been removed.');
         $RedirectUrl = 'garden/profile/'.urlencode($this->User->Name);
      }
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
          Redirect($RedirectUrl);
      } else {
         $this->ControllerName = 'Home';
         $this->View = 'FileNotFound';
         $this->RedirectUrl = Url($RedirectUrl);
         $this->Render();
      }
   }
   
   public function Thumbnail() {
      $this->Permission('Garden.SignIn.Allow');
      $this->AddJsFile('/js/library/jquery.jcrop.pack.js');
      $this->AddJsFile('profile.js');
            
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');
         
      $this->GetUserInfo();
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('UserID', $this->User->UserID);
      
      if ($this->User->Photo == '')
         $this->Form->AddError('You must first upload a picture before you can create a thumbnail.');
      
      // Define the thumbnail size
      $this->ThumbSize = Gdn::Config('Garden.Thumbnail.Size', 32);
      
      // Define the source (profile sized) picture & dimensions
      $Source = PATH_ROOT . DS . 'uploads' . DS . 'p'. $this->User->Photo;
      $this->SourceSize = getimagesize($Source);
      
      // Add some more hidden form fields for jcrop
      $this->Form->AddHidden('x', '0');
      $this->Form->AddHidden('y', '0');
      $this->Form->AddHidden('w', $this->ThumbSize);
      $this->Form->AddHidden('h', $this->ThumbSize);
      $this->Form->AddHidden('HeightSource', $this->SourceSize[1]);
      $this->Form->AddHidden('WidthSource', $this->SourceSize[0]);
      $this->Form->AddHidden('ThumbSize', $this->ThumbSize);      
      if ($this->Form->AuthenticatedPostBack() === TRUE) {
         try {
            // Get the dimensions from the form
            
            // Get the source image 
            $SourceImage = imagecreatefromjpeg($Source);
            
            // Create the new target image
            $TargetImage = imagecreatetruecolor($this->ThumbSize, $this->ThumbSize);
            
            // Fill the target thumbnail
            imagecopyresampled(
               $TargetImage,
               $SourceImage,
               0,
               0,
               $this->Form->GetValue('x'),
               $this->Form->GetValue('y'),
               $this->ThumbSize,
               $this->ThumbSize,
               $this->Form->GetValue('w'),
               $this->Form->GetValue('h')
            );
            
            // Save the target thumbnail
            imagejpeg($TargetImage, PATH_ROOT . DS . 'uploads' . DS . 'n'.$this->User->Photo);
         } catch (Exception $ex) {
            $this->Form->AddError($ex->getMessage());
         }
         // If there were no problems, redirect back to the user account
         if ($this->Form->ErrorCount() == 0) {
            Redirect('garden/profile/'.urlencode($this->User->Name));
         }
      }
      $this->Render();
   }
   
   public function Invitations() {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo();
      $InvitationModel = new Gdn_InvitationModel();
      $this->Form->SetModel($InvitationModel);
      if ($this->Form->AuthenticatedPostBack()) {
         // Send the invitation
         if ($this->Form->Save($this->UserModel)) {
            $this->StatusMessage = Translate("Your invitation has been sent.");
            $this->Form->ClearInputs();
         }
      }
      $Session = Gdn::Session();
      $this->InvitationCount = $this->UserModel->GetInvitationCount($Session->UserID);
      $this->InvitationData = $InvitationModel->GetByUserID($Session->UserID);
      $this->Render();
   }
   
   public function SendInvite($InvitationID = '', $PostBackKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $InvitationModel = new Gdn_InvitationModel();
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($PostBackKey)) {
         try {
            $Email = new Gdn_Email();
            $InvitationModel->Send($InvitationID, $Email);
         } catch (Exception $ex) {
            $this->Form->AddError(strip_tags($ex->getMessage()));
         }
         if ($this->Form->ErrorCount() == 0)
            $this->StatusMessage = Gdn::Translate('The invitation was sent successfully.');

      }
      
      $this->View = 'Invitations';
      $this->Invitations();
   }
   
   public function UnInvite($InvitationID = '', $PostBackKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $InvitationModel = new Gdn_InvitationModel();
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($PostBackKey)) {
         try {
            $InvitationModel->Delete($InvitationID, $this->UserModel);
         } catch (Exception $ex) {
            $this->Form->AddError(strip_tags($ex->getMessage()));
         }
            
         if ($this->Form->ErrorCount() == 0)
            $this->StatusMessage = Gdn::Translate('The invitation was removed successfully.');

      }
      
      $this->View = 'Invitations';
      $this->Invitations();
   }
   
   public function AddSideMenu($CurrentUrl = '') {
      if ($this->User !== FALSE) {
         $SideMenu = new Gdn_SideMenuModule($this);
         $SideMenu->HtmlId = 'UserOptions';
         $Session = Gdn::Session();
         $ViewingUserID = $Session->UserID;
         $SideMenu->AddItem('Options', '');
         
         if ($this->User->UserID != $ViewingUserID) {
            // Include user js files for people with edit users permissions
            if ($Session->CheckPermission('Garden.Users.Edit')) {
              $this->AddJsFile('js/library/jquery.gardenmorepager.js');
              $this->AddJsFile('user.js');
            }
            
            // Add profile options for everyone
            if ($this->User->Photo != '')
               $SideMenu->AddLink('Options', Gdn::Translate('Change Picture'), '/profile/picture/'.$this->User->UserID, 'Garden.Users.Edit', array('class' => 'PictureLink'));
            
            $SideMenu->AddLink('Options', Gdn::Translate('Edit Account'), '/user/edit/'.$this->User->UserID, 'Garden.Users.Edit', array('class' => 'Popup'));
            if ($this->User->Photo != '')
               $SideMenu->AddLink('Options', Gdn::Translate('Remove Picture'), '/profile/removepicture/'.$this->User->UserID.'/'.$Session->TransientKey(), 'Garden.User.Edit', array('class' => 'RemovePictureLink'));
         } else {
            // Add profile options for the profile owner
            $SideMenu->AddLink('Options', Gdn::Translate('Change My Picture'), '/profile/picture', FALSE, array('class' => 'PictureLink'));
            if ($this->User->Photo != '') {
               $SideMenu->AddLink('Options', Gdn::Translate('Edit My Thumbnail'), '/profile/thumbnail', FALSE, array('class' => 'ThumbnailLink'));
               $SideMenu->AddLink('Options', Gdn::Translate('Remove My Picture'), '/profile/removepicture/'.$Session->UserID.'/'.$Session->TransientKey(), FALSE, array('class' => 'RemovePictureLink'));
            }
            // Don't allow account editing if it has been turned off.
            if (Gdn::Config('Garden.UserAccount.AllowEdit')) {
               $SideMenu->AddLink('Options', Gdn::Translate('Edit My Account'), '/profile/edit', FALSE, array('class' => 'Popup'));
               $SideMenu->AddLink('Options', Gdn::Translate('Change My Password'), '/profile/password', FALSE, array('class' => 'Popup'));
            }
            if (Gdn::Config('Garden.Registration.Method') == 'Invitation')
               $SideMenu->AddLink('Options', Gdn::Translate('My Invitations'), '/profile/invitations', FALSE, array('class' => 'Popup'));
         }
         if ($this->User->UserID == $ViewingUserID || $Session->CheckPermission('Garden.Users.Edit'))
            $SideMenu->AddLink('Options', Gdn::Translate('My Preferences'), '/profile/preferences/'.$this->User->UserID, FALSE, array('class' => 'Popup'));
            
         $this->EventArguments['SideMenu'] = &$SideMenu;
         $this->FireEvent('AfterAddSideMenu');
         $this->AddModule($SideMenu, 'Panel');
      }
   }
   
   public function Initialize() {
      $this->ModuleSortContainer = 'Profile';
      $this->Head = new HeadModule($this);
      $this->AddJsFile('js/library/jquery.js');
      $this->AddJsFile('js/library/jquery.livequery.js');
      $this->AddJsFile('js/library/jquery.form.js');
      $this->AddJsFile('js/library/jquery.popup.js');
      $this->AddJsFile('js/library/jquery.menu.js');
      $this->AddJsFile('js/library/jquery.gardenhandleajaxform.js');
      $this->AddJsFile('js/global.js');
      
      $this->AddCssFile('style.css');
      $GuestModule = new GuestModule($this);
      $GuestModule->MessageCode = "It looks like you're new here. If you want to take part in the discussions, click one of these buttons!";
      $this->AddModule($GuestModule);
      parent::Initialize();
   }
   
   /**
    * Adds a tab (or array of tabs) to the profile tab collection.
    *
    * @param mixed The tab name (or array of tab names) to add to the profile tab collection.
    * @param string URL the tab should point to.
    */
   public function AddProfileTab($TabName, $TabUrl = '') {
      if (!is_array($TabName))
         $TabName = array($TabName => $TabUrl);
      foreach ($TabName as $Name => $Url) {
         if ($Url == '')
            $Url = '/profile/'.strtolower($Name).'/'.$this->User->UserID.'/'.Format::Url($this->User->Name);
            
         $this->_ProfileTabs[$Name] = $Url;
      }
   }
}