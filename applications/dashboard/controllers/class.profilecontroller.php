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
   
   public $Uses = array('Form', 'UserModel');

	const UsernameError = 'Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long.';

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
      $this->_TabApplication = 'Dashboard';
      $this->_CurrentTab = 'Activity';
      $this->_ProfileTabs = array();
      parent::__construct();
   }
   
   public function Initialize() {
      $this->ModuleSortContainer = 'Profile';
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      
      $this->AddCssFile('style.css');
      $GuestModule = new GuestModule($this);
      $this->AddModule($GuestModule);
      parent::Initialize();
   }   
   
   public function Activity($UserReference = '', $Username = '', $UserID = '') {
      $this->Permission('Garden.Profiles.View');
      $this->GetUserInfo($UserReference, $Username, $UserID);
      $this->SetTabView('Activity');
      $this->ActivityModel = new ActivityModel();
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
            '/profile/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name),
            $SendNotification);
         
         if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            Redirect('dashboard/profile/'.$UserReference);
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
            $ActivityData = $this->ActivityData->Result();
            $ActivityIDs = ConsolidateArrayValuesByKey($ActivityData, 'ActivityID');
            $LastActivity = $this->ActivityData->FirstRow();
            $LastModifiedDate = Gdn_Format::ToTimestamp($this->User->DateUpdated);
            $LastActivityDate = Gdn_Format::ToTimestamp($LastActivity->DateInserted);
            if ($LastModifiedDate < $LastActivityDate)
               $LastModifiedDate = $LastActivityDate;
               
            // Make sure to only query this page if the user has no new activity since the requesting browser last saw it.
            $this->SetLastModified($LastModifiedDate);
            $this->CommentData = $this->ActivityModel->GetComments($ActivityIDs);
         } else {
            $this->CommentData = FALSE;
         }
      }

      // Set the canonical Url.
      if (is_numeric($this->User->Name) || Gdn_Format::Url($this->User->Name) != strtolower($this->User->Name)) {
         $this->CanonicalUrl(Url('profile/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name), TRUE));
      } else {
         $this->CanonicalUrl(Url('profile/'.strtolower($this->User->Name), TRUE));
      }
      
      $this->Render();
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
   
   public function Edit($UserReference = '') {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo($UserReference);
      $Session = Gdn::Session();
      if ($Session->UserID != $this->User->UserID)
         $this->Permission('Garden.Users.Edit');
      
      $this->CanEditUsername = Gdn::Config("Garden.Profile.EditUsernames");
      $this->CanEditUsername = $this->CanEditUsername | $Session->CheckPermission('Garden.Users.Edit');
         
      $UserModel = Gdn::UserModel();
      $User = $UserModel->Get($this->User->UserID);
      $this->Form->SetModel($UserModel);
      $this->Form->AddHidden('UserID', $this->User->UserID);
      
      // Define gender dropdown options
      $this->GenderOptions = array(
         'm' => T('Male'),
         'f' => T('Female')
      );
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Get the user data for the requested $UserID and put it into the form.
         $this->Form->SetData($this->User);
      } else {
         if (!$this->CanEditUsername)
            $this->Form->SetFormValue("Name", $User->Name);
            
         $UserModel->Validation->ApplyRule('Name', 'Username', self::UsernameError);
         if ($this->Form->Save() !== FALSE) {
            $User = $UserModel->Get($this->User->UserID);
            $this->StatusMessage = T('Your changes have been saved successfully.');
            $this->RedirectUrl = Url('/profile/'.Gdn_Format::Url($User->Name));
         }
      }
      
      $this->Render();
   }

   public function Index($User = '', $Username = '', $UserID = '') {
      return $this->Activity($User, $Username, $UserID);
   }
   
   public function Invitations() {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo();
      $InvitationModel = new InvitationModel();
      $this->Form->SetModel($InvitationModel);
      if ($this->Form->AuthenticatedPostBack()) {
         // Send the invitation
         if ($this->Form->Save($this->UserModel)) {
            $this->StatusMessage = T('Your invitation has been sent.');
            $this->Form->ClearInputs();
         }
      }
      $Session = Gdn::Session();
      $this->InvitationCount = $this->UserModel->GetInvitationCount($Session->UserID);
      $this->InvitationData = $InvitationModel->GetByUserID($Session->UserID);
      $this->Render();
   }
   
   public function Notifications() {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo(); 
      $Session = Gdn::Session();
      // Drop notification count back to zero.
      $SQL = Gdn::SQL();
      $SQL
         ->Update('User')
         ->Set('CountNotifications', '0')
         ->Where('UserID', $Session->UserID)
         ->Put();
      
      $this->ActivityModel = new ActivityModel();
      $this->ActivityData = $this->ActivityModel->GetNotifications($Session->UserID);
      $this->SetTabView('Notifications');
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
            $this->StatusMessage = T('Your password has been changed.');
            $this->Form->ClearInputs();
         }
      }
      $this->Render();
   }
   
   public function Picture($UserReference = '', $Username = '') {
      $this->Permission('Garden.SignIn.Allow');
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');
      
      $ImageManipOk = FALSE;
      if (function_exists('gd_info')) {
         $GdInfo = gd_info();
         $GdVersion = preg_replace('/[a-z ()]+/i', '', $GdInfo['GD Version']);
         if ($GdVersion < 2)
            throw new Exception(sprintf(T("This installation of GD is too old (v%s). Vanilla requires at least version 2 or compatible."),$GdVersion));
      }
      else {
         throw new Exception(sprintf(T("Unable to detect PHP GD installed on this system. Vanilla requires GD version 2 or better.")));
      }
         
      $this->GetUserInfo($UserReference, $Username);
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
            @unlink( PATH_ROOT.'/uploads/'.ChangeBasename($this->User->Photo, 'p%s'));
            // Don't delete this one because it hangs around in activity streams:
            // @unlink(PATH_ROOT.'/uploads/'.ChangeBasename($this->User->Photo, 't%s'));
            @unlink(PATH_ROOT.'/uploads/'.ChangeBasename($this->User->Photo, 'n%s'));

            // Make sure the avatars folder exists.
            if (!file_exists(PATH_ROOT.'/uploads/userpics'))
               mkdir(PATH_ROOT.'/uploads/userpics');
            
            // Save the uploaded image in profile size
            $UploadImage->SaveImageAs(
               $TmpImage,
               PATH_ROOT.'/uploads/userpics/p'.$ImageBaseName,
               Gdn::Config('Garden.Profile.MaxHeight', 1000),
               Gdn::Config('Garden.Profile.MaxWidth', 250)
            );
            
            // Save the uploaded image in preview size
            $UploadImage->SaveImageAs(
               $TmpImage,
               PATH_ROOT.'/uploads/userpics/t'.$ImageBaseName,
               Gdn::Config('Garden.Preview.MaxHeight', 100),
               Gdn::Config('Garden.Preview.MaxWidth', 75)
            );

            // Save the uploaded image in thumbnail size
            $ThumbSize = Gdn::Config('Garden.Thumbnail.Size', 50);
            $UploadImage->SaveImageAs(
               $TmpImage,
               PATH_ROOT.'/uploads/userpics/n'.$ImageBaseName,
               $ThumbSize,
               $ThumbSize,
               TRUE
            );
            
         } catch (Exception $ex) {
            $this->Form->AddError($ex->getMessage());
         }
         // If there were no errors, associate the image with the user
         if ($this->Form->ErrorCount() == 0) {
            if (!$this->UserModel->Save(array('UserID' => $this->User->UserID, 'Photo' => 'userpics/'.$ImageBaseName)))
               $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         }
         // If there were no problems, redirect back to the user account
         if ($this->Form->ErrorCount() == 0)
            Redirect('dashboard/profile/'.$UserReference);
      }
		if ($this->Form->ErrorCount() > 0)
			$this->DeliveryType(DELIVERY_TYPE_ALL);

      $this->Render();
   }
   
   public function Preferences($UserReference = '', $Username = '', $UserID = '') {
      $Session = Gdn::Session();
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo($UserReference, $Username, $UserID);
		$UserPrefs = Gdn_Format::Unserialize($this->User->Preferences);
      if (!is_array($UserPrefs))
         $UserPrefs = array();

      // Define the preferences to be managed
      $this->Preferences = array(
         'Email Notifications' => array(
            'Email.WallComment' => T('Notify me when people write on my wall.'),
            'Email.ActivityComment' => T('Notify me when people reply to my wall comments.')
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
         $this->StatusMessage = T('Your preferences have been saved.');
      }
      $this->Render();
   }
   
   public function RemovePicture($UserReference = '', $Username = '', $TransientKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');
         
      $this->GetUserInfo($UserReference, $Username);
      $RedirectUrl = 'dashboard/profile/'.$UserReference.'/'.Gdn_Format::Url($Username);
      if ($Session->ValidateTransientKey($TransientKey)
         && is_object($this->User)
         && (
            $this->User->UserID == $Session->UserID
            || $Session->CheckPermission('Garden.Users.Edit')
         )
      ) {
         Gdn::UserModel()->RemovePicture($this->User->UserID);
         $this->StatusMessage = T('Your picture has been removed.');
         $RedirectUrl = 'dashboard/profile/'.Gdn_Format::Url($this->User->Name);
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
   
   public function SendInvite($InvitationID = '', $PostBackKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $InvitationModel = new InvitationModel();
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($PostBackKey)) {
         try {
            $Email = new Gdn_Email();
            $InvitationModel->Send($InvitationID, $Email);
         } catch (Exception $ex) {
            $this->Form->AddError(strip_tags($ex->getMessage()));
         }
         if ($this->Form->ErrorCount() == 0)
            $this->StatusMessage = T('The invitation was sent successfully.');

      }
      
      $this->View = 'Invitations';
      $this->Invitations();
   }
   
   public function Thumbnail($UserReference = '', $Username = '') {
      $this->Permission('Garden.SignIn.Allow');
      $this->AddJsFile('jquery.jcrop.pack.js');
      $this->AddJsFile('profile.js');
            
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');
               
      $this->GetUserInfo($UserReference, $Username);
      
      if ($this->User->UserID != $Session->UserID && !$Session->CheckPermission('Garden.Users.Edit'))
         throw new Exception(T('You cannot edit the thumbnail of another member.'));
      
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('UserID', $this->User->UserID);
      
      if ($this->User->Photo == '')
         $this->Form->AddError('You must first upload a picture before you can create a thumbnail.');
      
      // Define the thumbnail size
      $this->ThumbSize = Gdn::Config('Garden.Thumbnail.Size', 32);
      
      // Define the source (profile sized) picture & dimensions.
      if (preg_match('`https?://`i', $this->User->Photo)) {
         $this->Form->AddError('You cannot edit the thumbnail of an externally linked profile picture.');
         $this->SourceSize = 0;
      } else {
         $Source = PATH_ROOT.'/uploads/'.ChangeBasename($this->User->Photo, 'p%s');
         $this->SourceSize = getimagesize($Source);
      }
      
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
            imagejpeg($TargetImage, PATH_ROOT.'/uploads/'.ChangeBasename($this->User->Photo, 'n%s'));
         } catch (Exception $ex) {
            $this->Form->AddError($ex->getMessage());
         }
         // If there were no problems, redirect back to the user account
         if ($this->Form->ErrorCount() == 0) {
            Redirect('dashboard/profile/'.Gdn_Format::Url($this->User->Name));
         }
      }
      $this->Render();
   }
   
   public function UnInvite($InvitationID = '', $PostBackKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $InvitationModel = new InvitationModel();
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($PostBackKey)) {
         try {
            $InvitationModel->Delete($InvitationID, $this->UserModel);
         } catch (Exception $ex) {
            $this->Form->AddError(strip_tags($ex->getMessage()));
         }
            
         if ($this->Form->ErrorCount() == 0)
            $this->StatusMessage = T('The invitation was removed successfully.');

      }
      
      $this->View = 'Invitations';
      $this->Invitations();
   }
   
   // BEGIN PUBLIC CONVENIENCE FUNCTIONS
   
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
            $Url = '/profile/'.strtolower($Name).'/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name);
            
         $this->_ProfileTabs[$Name] = $Url;
      }
   }

   /**
    * Adds the option menu to the panel asset.
    */
   public function AddSideMenu($CurrentUrl = '') {
      if ($this->User !== FALSE) {
         $SideMenu = new SideMenuModule($this);
         $SideMenu->HtmlId = 'UserOptions';
			$SideMenu->AutoLinkGroups = FALSE;
         $Session = Gdn::Session();
         $ViewingUserID = $Session->UserID;
         $SideMenu->AddItem('Options', '');
         
         // Check that we have the necessary tools to allow image uploading
         $AllowImages = Gdn_UploadImage::CanUploadImages();
         
         if ($this->User->UserID != $ViewingUserID) {
            // Include user js files for people with edit users permissions
            if ($Session->CheckPermission('Garden.Users.Edit')) {
              $this->AddJsFile('jquery.gardenmorepager.js');
              $this->AddJsFile('user.js');
            }
            
            // Add profile options for everyone
            $SideMenu->AddLink('Options', T('Change Picture'), '/profile/picture/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name), 'Garden.Users.Edit', array('class' => 'PictureLink'));
            if ($this->User->Photo != '' && $AllowImages) {
               $SideMenu->AddLink('Options', T('Edit Thumbnail'), '/profile/thumbnail/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name), 'Garden.Users.Edit', array('class' => 'ThumbnailLink'));
               $SideMenu->AddLink('Options', T('Remove Picture'), '/profile/removepicture/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name).'/'.$Session->TransientKey(), 'Garden.Users.Edit', array('class' => 'RemovePictureLink'));
            }
            
            $SideMenu->AddLink('Options', T('Edit Account'), '/user/edit/'.$this->User->UserID, 'Garden.Users.Edit', array('class' => 'Popup'));
            $SideMenu->AddLink('Options', T('Delete Account'), '/user/delete/'.$this->User->UserID, 'Garden.Users.Delete');
            if ($this->User->Photo != '' && $AllowImages)
               $SideMenu->AddLink('Options', T('Remove Picture'), '/profile/removepicture/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name).'/'.$Session->TransientKey(), 'Garden.Users.Edit', array('class' => 'RemovePictureLink'));
            
            $SideMenu->AddLink('Options', T('Edit Preferences'), '/profile/preferences/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name), 'Garden.Users.Edit', array('class' => 'Popup'));
         } else {
            // Add profile options for the profile owner
            if ($AllowImages)
               $SideMenu->AddLink('Options', T('Change My Picture'), '/profile/picture', FALSE, array('class' => 'PictureLink'));
               
            if ($this->User->Photo != '' && $AllowImages) {
               $SideMenu->AddLink('Options', T('Edit My Thumbnail'), '/profile/thumbnail', FALSE, array('class' => 'ThumbnailLink'));
               $SideMenu->AddLink('Options', T('Remove My Picture'), '/profile/removepicture/'.$Session->UserID.'/'.Gdn_Format::Url($Session->User->Name).'/'.$Session->TransientKey(), FALSE, array('class' => 'RemovePictureLink'));
            }
            // Don't allow account editing if it has been turned off.
            if (Gdn::Config('Garden.UserAccount.AllowEdit')) {
               $SideMenu->AddLink('Options', T('Edit My Account'), '/profile/edit', FALSE, array('class' => 'Popup'));
               $SideMenu->AddLink('Options', T('Change My Password'), '/profile/password', FALSE, array('class' => 'Popup'));
            }
            if (Gdn::Config('Garden.Registration.Method') == 'Invitation')
               $SideMenu->AddLink('Options', T('My Invitations'), '/profile/invitations', FALSE, array('class' => 'Popup'));

            $SideMenu->AddLink('Options', T('My Preferences'), '/profile/preferences/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name), FALSE, array('class' => 'Popup'));
         }
            
         $this->EventArguments['SideMenu'] = &$SideMenu;
         $this->FireEvent('AfterAddSideMenu');
         $this->AddModule($SideMenu, 'Panel');
      }
   }
   
   /**
    * Build the user profile: Set the page title, add data to page modules & add
    * modules to assets, Add tabs to tab menu. $this->User must be defined,
    * or this method will throw an exception.
    */
   public function BuildProfile() {
      if (!is_object($this->User))
         throw new Exception(T('Cannot build profile information if user is not defined.'));
         
      $Session = Gdn::Session();
      $this->CssClass = 'Profile';
      $this->Title(Gdn_Format::Text($this->User->Name));
      if ($this->_DeliveryType != DELIVERY_TYPE_VIEW) {
         $UserInfoModule = new UserInfoModule($this);
         $UserInfoModule->User = $this->User;
         $UserInfoModule->Roles = $this->Roles;
         $this->AddModule($UserInfoModule);
         $this->AddJsFile('jquery.jcrop.pack.js');
         $this->AddJsFile('profile.js');
         $this->AddJsFile('activity.js');
         $ActivityUrl = 'profile/activity/';
         if ($this->User->UserID != $Session->UserID)
            $ActivityUrl .= $this->User->UserID.'/'.Gdn_Format::Url($this->User->Name);
            
         $this->AddProfileTab(T('Activity'), $ActivityUrl);
         if ($this->User->UserID == $Session->UserID) {
            $Notifications = T('Notifications');
            $CountNotifications = $Session->User->CountNotifications;
            if (is_numeric($CountNotifications) && $CountNotifications > 0)
               $Notifications .= '<span>'.$CountNotifications.'</span>';
               
            $this->AddProfileTab(array($Notifications => 'profile/notifications'));
         }
            
         $this->FireEvent('AddProfileTabs');
      }
      
      return TRUE;
   }

   public function Get($UserID = FALSE) {
      if (!$UserID)
         $UserID = Gdn::Session()->UserID;

      if (($UserID != Gdn::Session()->UserID || !Gdn::Session()->UserID) && !Gdn::Session()->CheckPermission('Garden.Users.Edit')) {
         throw new Exception(T('You do not have permission to view other profiles.'), 401);
      }

      $UserModel = new UserModel();

      // Get the user.
      $User = $UserModel->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!$User) {
         throw new Exception(T('User not found.'), 404);
      }

      $PhotoUrl = $User['Photo'];
      if ($PhotoUrl && strpos($PhotoUrl, '//') == FALSE) {
         $PhotoUrl = Url('/uploads/'.ChangeBasename($PhotoUrl, 'n%s'), TRUE);
      }
      $User['Photo'] = $PhotoUrl;

      // Remove unwanted fields.
      $this->Data = ArrayTranslate($User, array('UserID', 'Name', 'Email', 'Photo'));

      $this->Render();
   }

   /**
    * Retrieve the user to be manipulated. If no params are passed, this will
    * retrieve the current user from the session.
    */
   public function GetUserInfo($UserReference = '', $Username = '', $UserID = '') {
      if (!C('Garden.Profile.Public') && !Gdn::Session()->IsValid())
         Redirect('dashboard/home/permission');
      
		// If a UserID was provided as a querystring parameter, use it over anything else:
		if ($UserID) {
			$UserReference = $UserID;
			$Username = 'Unknown'; // Fill this with a value so the $UserReference is assumed to be an integer/userid.
		}
		   
      $this->Roles = array();
      if ($UserReference == '') {
         $this->User = $this->UserModel->Get(Gdn::Session()->UserID);
      } else if (is_numeric($UserReference) && $Username != '') {
         $this->User = $this->UserModel->Get($UserReference);
      } else {
         $this->User = $this->UserModel->GetByUsername($UserReference);
      }
         
      if ($this->User === FALSE) {
         throw NotFoundException();
      } else if ($this->User->Deleted == 1) {
         Redirect('dashboard/home/deleted');
      } else {
         $this->RoleData = $this->UserModel->GetRoles($this->User->UserID);
         if ($this->RoleData !== FALSE && $this->RoleData->NumRows(DATASET_TYPE_ARRAY) > 0) 
            $this->Roles = ConsolidateArrayValuesByKey($this->RoleData->Result(), 'Name');
			
			$this->SetData('Profile', $this->User);
			$this->SetData('UserRoles', $this->Roles);
      }
      
      // Make sure the userphoto module gets added to the page
      $UserPhotoModule = new UserPhotoModule($this);
      $UserPhotoModule->User = $this->User;
      $this->AddModule($UserPhotoModule);
      
      $this->AddSideMenu();
      return TRUE;
   }

   /**
    * Define & select the current tab in the tab menu.
    */
   public function SetTabView($CurrentTab, $View = '', $Controller = 'Profile', $Application = 'Dashboard') {
      $this->BuildProfile();
      if ($View == '')
         $View = $CurrentTab;
         
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL && $this->SyndicationMethod == SYNDICATION_NONE) {
         $this->AddDefinition('DefaultAbout', T('Write something about yourself...'));
         $this->View = 'index';
         $this->_TabView = $View;
         $this->_TabController = $Controller;
         $this->_TabApplication = $Application;
      } else {
         $this->View = $View;
         $this->ControllerName = $Controller;
         $this->ApplicationFolder = $Application;
      }
		$this->_CurrentTab = T($CurrentTab);
   }
   
}