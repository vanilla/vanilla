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
      $this->AddModule('GuestModule');
      parent::Initialize();
   }   
   
   public function Activity($UserReference = '', $Username = '', $UserID = '', $Offset = '0') {
      $this->Permission('Garden.Profiles.View');
		$Offset = is_numeric($Offset) ? $Offset : 0;
      if ($Offset < 0)
         $Offset = 0;

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
            '/profile/'.$this->ProfileUrl(),
            $SendNotification);

         if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            Redirect('dashboard/profile/'.$this->ProfileUrl());
         } else {
            // Load just the single new comment
            $this->HideActivity = TRUE;
            $this->ActivityData = $this->ActivityModel->GetWhere('ActivityID', $NewActivityID);
            $this->View = 'activities';
            $this->ControllerName = 'activity';
         }
      } else {
         $this->ProfileUserID = $this->User->UserID;
			$Limit = 50;
         $this->ActivityData = $this->ActivityModel->Get($this->User->UserID, $Offset, $Limit);
			$TotalRecords = $this->ActivityModel->GetCount($this->User->UserID);
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
			
         // Build a pager
         $PagerFactory = new Gdn_PagerFactory();
         $this->Pager = $PagerFactory->GetPager('MorePager', $this);
         $this->Pager->MoreCode = 'More';
         $this->Pager->LessCode = 'Newer Activity';
         $this->Pager->ClientID = 'Pager';
         $this->Pager->Configure(
            $Offset,
            $Limit,
            $TotalRecords,
            'profile/activity/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name).'/'.$this->User->UserID.'/%1$s/'
         );
         
         // Deliver json data if necessary
         if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->SetJson('LessRow', $this->Pager->ToString('less'));
            $this->SetJson('MoreRow', $this->Pager->ToString('more'));
				if ($Offset > 0) {
					$this->View = 'activities';
					$this->ControllerName = 'Activity';
				}
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

   public function Count($Column, $UserID = FALSE) {
      $Column = 'Count'.ucfirst($Column);
      if (!$UserID)
         $UserID = Gdn::Session()->UserID;

      $Count = $this->UserModel->ProfileCount($UserID, $Column);
      $this->SetData($Column, $Count);
      $this->SetData('_Value', $Count);
      $this->SetData('_CssClass', 'Count');
      $this->Render('Value', 'Utility');
   }
   
   public function Edit($UserReference = '') {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo($UserReference);
      $Session = Gdn::Session();
      if ($Session->UserID != $this->User->UserID)
         $this->Permission('Garden.Users.Edit');
      
      $this->CanEditUsername = C("Garden.Profile.EditUsernames");
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
         else {
            $UsernameError = T('UsernameError', 'Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long.');
            $UserModel->Validation->ApplyRule('Name', 'Username', $UsernameError);
         }
         if ($this->Form->Save() !== FALSE) {
            $User = $UserModel->Get($this->User->UserID);
            $this->InformMessage('<span class="InformSprite Check"></span>'.T('Your changes have been saved.'), 'Dismissable AutoDismiss HasSprite');
            $this->RedirectUrl = Url('/profile/'.$this->ProfileUrl($User->Name));
         }
      }
      
      $this->Render();
   }

   public function Index($User = '', $Username = '', $UserID = '') {
      $this->GetUserInfo($User, $Username, $UserID);

      if ($this->User->Admin == 2 && $this->Head) {
         // Don't index internal accounts. This is in part to prevent vendors from getting endless Google alerts.
         $this->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex'));
         $this->Head->AddTag('meta', array('name' => 'googlebot', 'content' => 'noindex'));
      }

		if ($this->User->UserID == Gdn::Session()->UserID)
			return $this->Notifications();
		else
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
            $this->InformMessage(T('Your invitation has been sent.'));
            $this->Form->ClearInputs();
         }
      }
      $Session = Gdn::Session();
      $this->InvitationCount = $this->UserModel->GetInvitationCount($Session->UserID);
      $this->InvitationData = $InvitationModel->GetByUserID($Session->UserID);
      $this->Render();
   }
   
   public function Notifications($Offset = '0') {
      $this->Permission('Garden.SignIn.Allow');
		
		$Limit = 50;
		$Offset = is_numeric($Offset) ? $Offset : 0;
      if ($Offset < 0)
         $Offset = 0;

      $this->GetUserInfo(); 
      $this->SetTabView('Notifications');
      $Session = Gdn::Session();
      // Drop notification count back to zero.
      $SQL = Gdn::SQL();
      $SQL
         ->Update('User')
         ->Set('CountNotifications', '0')
         ->Where('UserID', $Session->UserID)
         ->Put();
      
      $this->ActivityModel = new ActivityModel();
      $this->ActivityData = $this->ActivityModel->GetNotifications($Session->UserID, $Offset, $Limit);
		$TotalRecords = $this->ActivityModel->GetCountNotifications($Session->UserID);
		
		// Build a pager
		$PagerFactory = new Gdn_PagerFactory();
		$this->Pager = $PagerFactory->GetPager('MorePager', $this);
		$this->Pager->MoreCode = 'More';
		$this->Pager->LessCode = 'Newer Notifications';
		$this->Pager->ClientID = 'Pager';
		$this->Pager->Configure(
			$Offset,
			$Limit,
			$TotalRecords,
			'profile/notifications/%1$s/'
		);
		// Deliver json data if necessary
		if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
			$this->SetJson('LessRow', $this->Pager->ToString('less'));
			$this->SetJson('MoreRow', $this->Pager->ToString('more'));
			if ($Offset > 0) {
				$this->View = 'activities';
				$this->ControllerName = 'Activity';
			}
		}
		
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
				$this->InformMessage('<span class="InformSprite Check"></span>'.T('Your password has been changed.'), 'Dismissable AutoDismiss HasSprite');
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
            
            // Generate the target image name.
            $TargetImage = $UploadImage->GenerateTargetName(PATH_LOCAL_UPLOADS, '', TRUE);
            $Basename = pathinfo($TargetImage, PATHINFO_BASENAME);
            $Subdir = StringBeginsWith(dirname($TargetImage), PATH_LOCAL_UPLOADS.'/', FALSE, TRUE);

            // Delete any previously uploaded image.
            $UploadImage->Delete(ChangeBasename($this->User->Photo, 'p%s'));
            
            // Save the uploaded image in profile size.
            $Props = $UploadImage->SaveImageAs(
               $TmpImage,
               "userpics/$Subdir/p$Basename",
               C('Garden.Profile.MaxHeight', 1000),
               C('Garden.Profile.MaxWidth', 250),
               array('SaveGif' => C('Garden.Thumbnail.SaveGif'))
            );
            $UserPhoto = sprintf($Props['SaveFormat'], "userpics/$Subdir/$Basename");
            
//            // Save the uploaded image in preview size
//            $UploadImage->SaveImageAs(
//               $TmpImage,
//               'userpics/t'.$ImageBaseName,
//               C('Garden.Preview.MaxHeight', 100),
//               C('Garden.Preview.MaxWidth', 75)
//            );

            // Save the uploaded image in thumbnail size
            $ThumbSize = C('Garden.Thumbnail.Size', 50);
            $UploadImage->SaveImageAs(
               $TmpImage,
               "userpics/$Subdir/n$Basename",
               $ThumbSize,
               $ThumbSize,
               array('Crop' => TRUE, 'SaveGif' => C('Garden.Thumbnail.SaveGif'))
            );
            
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
         // If there were no errors, associate the image with the user
         if ($this->Form->ErrorCount() == 0) {
            if (!$this->UserModel->Save(array('UserID' => $this->User->UserID, 'Photo' => $UserPhoto)))
               $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         }
         // If there were no problems, redirect back to the user account
         if ($this->Form->ErrorCount() == 0)
            Redirect('dashboard/profile/'.$this->ProfileUrl());
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
      $MetaPrefs = UserModel::GetMeta($this->User->UserID, 'Preferences.%', 'Preferences.');


      // Define the preferences to be managed
      $this->Preferences = array(
         'Notifications' => array(
            'Email.WallComment' => T('Notify me when people write on my wall.'),
            'Email.ActivityComment' => T('Notify me when people reply to my wall comments.'),
            'Popup.WallComment' => T('Notify me when people write on my wall.'),
            'Popup.ActivityComment' => T('Notify me when people reply to my wall comments.')
         )
      );
      
      $this->FireEvent('AfterPreferencesDefined');
		
		// Loop through the preferences looking for duplicates, and merge into a single row
		$this->PreferenceGroups = array();
		$this->PreferenceTypes = array();
		foreach ($this->Preferences as $PreferenceGroup => $Preferences) {
			$this->PreferenceGroups[$PreferenceGroup] = array();
			$this->PreferenceTypes[$PreferenceGroup] = array();
			foreach ($Preferences as $Name => $Description) {
            $Location = 'Prefs';
            if (is_array($Description))
               list($Description, $Location) = $Description;

				$NameParts = explode('.', $Name);
				$PrefType = GetValue('0', $NameParts);
				$SubName = GetValue('1', $NameParts);
				if ($SubName != FALSE) {
					// Save an array of all the different types for this group
					if (!in_array($PrefType, $this->PreferenceTypes[$PreferenceGroup]))
						$this->PreferenceTypes[$PreferenceGroup][] = $PrefType;
					
					// Store all the different subnames for the group	
					if (!array_key_exists($SubName, $this->PreferenceGroups[$PreferenceGroup])) {
						$this->PreferenceGroups[$PreferenceGroup][$SubName] = array($Name);
					} else {
						$this->PreferenceGroups[$PreferenceGroup][$SubName][] = $Name;
					}
				} else {
					$this->PreferenceGroups[$PreferenceGroup][$Name] = array($Name);
				}
			}
		}
		
      if ($this->User->UserID != $Session->UserID)
         $this->Permission('Garden.Users.Edit');

      // Loop the preferences, setting defaults from the configuration.
      $Defaults = array();
      foreach ($this->Preferences as $PrefGroup => $Prefs) {
         foreach ($Prefs as $Pref => $Desc) {
            $Location = 'Prefs';
            if (is_array($Desc))
               list($Desc, $Location) = $Desc;

            if ($Location == 'Meta')
               $Defaults[$Pref] = GetValue($Pref, $MetaPrefs, FALSE);
            else
               $Defaults[$Pref] = GetValue($Pref, $UserPrefs, C('Preferences.'.$Pref, '0'));
         }
      }
         
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         $this->Form->SetData($Defaults);
      } else {
         // Get, assign, and save the preferences.
         $Meta = array();
         foreach ($this->Preferences as $PrefGroup => $Prefs) {
            foreach ($Prefs as $Pref => $Desc) {
               $Location = 'Prefs';
               if (is_array($Desc))
                  list($Desc, $Location) = $Desc;

               $Value = $this->Form->GetValue($Pref, FALSE);

               if ($Location == 'Meta') {
                  $Meta[$Pref] = $Value ? $Value : NULL;
                  if ($Value)
                     $UserPrefs[$Pref] = $Value; // dup for notifications code.
               } else {
                  if (!$Defaults[$Pref] && !$Value)
                     unset($UserPrefs[$Pref]); // save some space
                  else
                     $UserPrefs[$Pref] = $Value;
               }
            }
         }
         $this->UserModel->SavePreference($this->User->UserID, $UserPrefs);
         UserModel::SetMeta($this->User->UserID, $Meta, 'Preferences.');
			$this->InformMessage('<span class="InformSprite Check"></span>'.T('Your preferences have been saved.'), 'Dismissable AutoDismiss HasSprite');
      }
      $this->Render();
   }
   
   public function RemovePicture($UserReference = '', $Username = '', $TransientKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');
         
      $this->GetUserInfo($UserReference, $Username);
      $RedirectUrl = 'dashboard/profile/'.$this->ProfileUrl();
      if ($Session->ValidateTransientKey($TransientKey)
         && is_object($this->User)
         && (
            $this->User->UserID == $Session->UserID
            || $Session->CheckPermission('Garden.Users.Edit')
         )
      ) {
         Gdn::UserModel()->RemovePicture($this->User->UserID);
         $this->InformMessage(T('Your picture has been removed.'));
         $RedirectUrl = 'dashboard/profile/'.$this->ProfileUrl();
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
            $this->InformMessage(T('The invitation was sent successfully.'));

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
      
      if (!$this->User->Photo)
         $this->Form->AddError('You must first upload a picture before you can create a thumbnail.');
      
      // Define the thumbnail size
      $this->ThumbSize = C('Garden.Thumbnail.Size', 32);
      
      // Define the source (profile sized) picture & dimensions.
      $Basename = ChangeBasename($this->User->Photo, 'p%s');
      $Upload = new Gdn_UploadImage();
      $PhotoParsed = Gdn_Upload::Parse($Basename);
      $Source = $Upload->CopyLocal($Basename);

      if (!$Source) {
         $this->Form->AddError('You cannot edit the thumbnail of an externally linked profile picture.');
      } else {
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
            // Get the dimensions from the form.
            Gdn_UploadImage::SaveImageAs(
               $Source,
               ChangeBasename($this->User->Photo, 'n%s'),
               $this->ThumbSize, $this->ThumbSize,
               array('Crop' => TRUE, 'SourceX' => $this->Form->GetValue('x'), 'SourceY' => $this->Form->GetValue('y'), 'SourceWidth' => $this->Form->GetValue('w'), 'SourceHeight' => $this->Form->GetValue('h')));
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
         // If there were no problems, redirect back to the user account
         if ($this->Form->ErrorCount() == 0) {
            Redirect('dashboard/profile/'.$this->ProfileUrl());
         }
      }
      // Delete the source image if it is externally hosted.
      if ($PhotoParsed['Type']) {
         @unlink($Source);
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
            $this->InformMessage(T('The invitation was removed successfully.'));

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
   public function AddProfileTab($TabName, $TabUrl = '', $CssClass = '', $TabHtml = '') {
      if (!is_array($TabName)) {
			if ($TabHtml == '')
				$TabHtml = $TabName;
				
         $TabName = array($TabName => array('TabUrl' => $TabUrl, 'CssClass' => $CssClass, 'TabHtml' => $TabHtml));
      }

      foreach ($TabName as $Name => $TabInfo) {
			$Url = GetValue('TabUrl', $TabInfo, '');
         if ($Url == '')
            $TabInfo['TabUrl'] = '/profile/'.strtolower($Name).'/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name);
            
         $this->_ProfileTabs[$Name] = $TabInfo;
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
            
            $SideMenu->AddLink('Options', T('Edit Account'), '/user/edit/'.$this->User->UserID, 'Garden.Users.Edit', array('class' => 'Popup EditAccountLink'));
            $SideMenu->AddLink('Options', T('Delete Account'), '/user/delete/'.$this->User->UserID, 'Garden.Users.Delete', array('class' => 'Popup DeleteAccountLink'));
            if ($this->User->Photo != '' && $AllowImages)
               $SideMenu->AddLink('Options', T('Remove Picture'), '/profile/removepicture/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name).'/'.$Session->TransientKey(), 'Garden.Users.Edit', array('class' => 'RemovePictureLink'));
            
            $SideMenu->AddLink('Options', T('Edit Preferences'), '/profile/preferences/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name), 'Garden.Users.Edit', array('class' => 'Popup PreferencesLink'));
         } else {
            // Add profile options for the profile owner
            if ($AllowImages)
               $SideMenu->AddLink('Options', T('Change My Picture'), '/profile/picture', FALSE, array('class' => 'PictureLink'));
               
            if ($this->User->Photo != '' && $AllowImages) {
               $SideMenu->AddLink('Options', T('Edit My Thumbnail'), '/profile/thumbnail', FALSE, array('class' => 'ThumbnailLink'));
               $SideMenu->AddLink('Options', T('Remove My Picture'), '/profile/removepicture/'.$Session->UserID.'/'.Gdn_Format::Url($Session->User->Name).'/'.$Session->TransientKey(), FALSE, array('class' => 'RemovePictureLink'));
            }
            // Don't allow account editing if it has been turned off.
            if (C('Garden.UserAccount.AllowEdit')) {
               $SideMenu->AddLink('Options', T('Edit My Account'), '/profile/edit', FALSE, array('class' => 'Popup EditAccountLink'));
               $SideMenu->AddLink('Options', T('Change My Password'), '/profile/password', FALSE, array('class' => 'Popup PasswordLink'));
            }
            if (C('Garden.Registration.Method') == 'Invitation')
               $SideMenu->AddLink('Options', T('My Invitations'), '/profile/invitations', FALSE, array('class' => 'Popup InvitationsLink'));

            $SideMenu->AddLink('Options', T('My Preferences'), '/profile/preferences/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name), FALSE, array('class' => 'Popup PreferencesLink'));
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
	      $this->AddJsFile('jquery.gardenmorepager.js');
         $this->AddJsFile('activity.js');
         $ActivityUrl = 'profile/activity/';
         if ($this->User->UserID != $Session->UserID)
            $ActivityUrl .= $this->User->UserID.'/'.Gdn_Format::Url($this->User->Name);
            
         if ($this->User->UserID == $Session->UserID) {
            $Notifications = T('Notifications');
				$NotificationsHtml = $Notifications;
            $CountNotifications = $Session->User->CountNotifications;
            if (is_numeric($CountNotifications) && $CountNotifications > 0)
               $NotificationsHtml .= '<span>'.$CountNotifications.'</span>';
               
            $this->AddProfileTab($Notifications, 'profile/notifications', 'Notifications', $NotificationsHtml);
         }

         $this->AddProfileTab(T('Activity'), $ActivityUrl, 'Activity');
            
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
	
	protected $_UserInfoRetrieved = FALSE;

   /**
    * Retrieve the user to be manipulated. If no params are passed, this will
    * retrieve the current user from the session.
    */
   public function GetUserInfo($UserReference = '', $Username = '', $UserID = '') {
		if ($this->_UserInfoRetrieved)
			return;
		
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
			
			// If the photo contains an http://, it is just an icon (probably from facebook or some external service), don't show it here because the Photo property is used to define logic around allowing thumbnail edits, etc.
			if ($this->User->Photo != '' && in_array(strtolower(substr($this->User->Photo, 0, 7)), array('http://', 'https:/')))
				$this->User->Photo = '';
			
      }
      
      // Make sure the userphoto module gets added to the page
      $UserPhotoModule = new UserPhotoModule($this);
      $UserPhotoModule->User = $this->User;
      $this->AddModule($UserPhotoModule);
      
      $this->AddSideMenu();
		$this->_UserInfoRetrieved = TRUE;
      return TRUE;
   }

   public function ProfileUrl($UserReference = NULL, $UserID = NULL) {
      if ($UserReference === NULL)
         $UserReference = $this->User->Name;
      if ($UserID === NULL)
         $UserID = $this->User->UserID;

      $UserReferenceEnc = rawurlencode($UserReference);
      if ($UserReferenceEnc == $UserReference)
         return $UserReferenceEnc;
      else
         return "$UserID/$UserReferenceEnc";
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
