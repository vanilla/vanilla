<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Profile Controller
 *
 * @package Dashboard
 */

/**
 * Manages individual user profiles.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class ProfileController extends Gdn_Controller {
   /** @var array Models to automatically instantiate. */
   public $Uses = array('Form', 'UserModel');

   /** @var object User data to use in building profile. */
   public $User;

   /** @var string Name of current tab. */
   public $CurrentTab;

   /** @var bool Is the page in "edit" mode or not. */
   public $EditMode;

   /** @var array List of available tabs. */
   public $ProfileTabs;

   /** @var string View for current tab. */
   protected $_TabView;

   /** @var string Controller for current tab. */
   protected $_TabController;

   /** @var string Application for current tab. */
   protected $_TabApplication;

   /** @var bool Whether data has been stored in $this->User yet. */
   protected $_UserInfoRetrieved = FALSE;

   /**
    * Prep properties.
    *
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      $this->User = FALSE;
      $this->_TabView = 'Activity';
      $this->_TabController = 'ProfileController';
      $this->_TabApplication = 'Dashboard';
      $this->CurrentTab = 'Activity';
      $this->ProfileTabs = array();
      $this->EditMode = TRUE;
      parent::__construct();
   }

   /**
    * Adds JS, CSS, & modules. Automatically run on every use.
    *
    * @since 2.0.0
    * @access public
    */
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

      Gdn_Theme::Section('Profile');

      if ($this->EditMode)
         $this->CssClass .= 'EditMode';

      $this->SetData('Breadcrumbs', array());
      $this->CanEditPhotos = C('Garden.Profile.EditPhotos') || Gdn::Session()->CheckPermission('Garden.Users.Edit');
   }

   /**
    * Show activity feed for this user.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $UserReference Unique identifier, possible ID or username.
    * @param string $Username Username.
    * @param int $UserID Unique ID.
    * @param int $Offset How many to skip (for paging).
    */
   public function Activity($UserReference = '', $Username = '', $UserID = '', $Page = '') {
      $this->Permission('Garden.Profiles.View');
      $this->EditMode(FALSE);

      // Object setup
      $Session = Gdn::Session();
      $this->ActivityModel = new ActivityModel();

      // Calculate offset.
      list($Offset, $Limit) = OffsetLimit($Page, 30);

      // Get user, tab, and comment
      $this->GetUserInfo($UserReference, $Username, $UserID);
      $UserID = $this->User->UserID;
      $Username = $this->User->Name;

      $this->_SetBreadcrumbs(T('Activity'), '/profile/activity');

      $this->SetTabView('Activity');
      $Comment = $this->Form->GetFormValue('Comment');

      // Load data to display
      $this->ProfileUserID = $this->User->UserID;
      $Limit = 30;

      $NotifyUserIDs = array(ActivityModel::NOTIFY_PUBLIC);
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
         $NotifyUserIDs[] = ActivityModel::NOTIFY_MODS;

      $Activities = $this->ActivityModel->GetWhere(
         array('ActivityUserID' => $UserID, 'NotifyUserID' => $NotifyUserIDs),
         $Offset, $Limit)->ResultArray();
      $this->ActivityModel->JoinComments($Activities);
      $this->SetData('Activities', $Activities);
      if (count($Activities) > 0) {
         $LastActivity = $Activities[0];
         $LastModifiedDate = Gdn_Format::ToTimestamp($this->User->DateUpdated);
         $LastActivityDate = Gdn_Format::ToTimestamp($LastActivity['DateInserted']);
         if ($LastModifiedDate < $LastActivityDate)
            $LastModifiedDate = $LastActivityDate;

         // Make sure to only query this page if the user has no new activity since the requesting browser last saw it.
         $this->SetLastModified($LastModifiedDate);
      }

      // Set the canonical Url.
      if (is_numeric($this->User->Name) || Gdn_Format::Url($this->User->Name) != strtolower($this->User->Name)) {
         $this->CanonicalUrl(Url('profile/'.$this->User->UserID.'/'.Gdn_Format::Url($this->User->Name), TRUE));
      } else {
         $this->CanonicalUrl(Url('profile/'.strtolower($this->User->Name), TRUE));
      }

      $this->Render();
   }

   /**
    * Clear user's current status message.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $UserID
    */
   public function Clear($UserID = '') {
      if (empty($_POST))
         throw PermissionException('Javascript');

      $UserID = is_numeric($UserID) ? $UserID : 0;
      $Session = Gdn::Session();
      if ($UserID != $Session->UserID && !$Session->CheckPermission('Garden.Moderation.Manage'))
         throw PermissionException('Garden.Moderation.Manage');

      if ($UserID > 0)
         $this->UserModel->SaveAbout($UserID, '');

      if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
         Redirect('/profile');
      else {
         $this->JsonTarget('#Status', '', 'Remove');
         $this->Render('Blank', 'Utility');
      }
   }

   public function Connect($Type, $UserReference = '', $Username = '') {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo($UserReference, $Username, '', TRUE);

      // Fire an event and let whatever plugin handle the connection.
      // This will fire an event in the form ProfileController_FacebookConnect_Handler(...).
      $Connected = FALSE;
      $this->EventArguments['Connected'] =& $Connected;


      $this->FireEvent(ucfirst($Type).'Connect');


   }

   /**
    * Lists the connections to other sites.
    *
    * @param int|string $UserReference
    * @param string $Username
    * @since 2.1
    */
   public function Connections($UserReference = '', $Username = '') {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo($UserReference, $Username, '', TRUE);
      $UserID = GetValueR('User.UserID', $this);
      $this->_SetBreadcrumbs(T('Social'), '/profile/connections');

      $PModel = new Gdn_AuthenticationProviderModel();
      $Providers = $PModel->GetProviders();

      $this->SetData('_Providers', $Providers);
      $this->SetData('Connections', array());
      $this->EventArguments['User'] = $this->User;
      $this->FireEvent('GetConnections');

      // Add some connection information.
      foreach ($this->Data['Connections'] as &$Row) {
         $Provider = GetValue($Row['ProviderKey'], $Providers, array());

         TouchValue('Connected', $Row, !is_null(GetValue('UniqueID', $Provider, NULL)));
      }

      $this->CanonicalUrl(UserUrl($this->User, '', 'connections'));
      $this->Title(T('Social'));
      require_once $this->FetchViewLocation('connection_functions');
      $this->Render();
   }

   /**
    * Generic way to get count via UserModel->ProfileCount().
    *
    * @since 2.0.?
    * @access public
    * @param string $Column Name of column to count for this user.
    * @param int $UserID Defaults to current session.
    */
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

   public function Disconnect($UserReference = '', $Username = '', $Provider) {
      if (!$this->Request->IsPostBack())
         throw PermissionException('Javascript');

      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo($UserReference, $Username, '', TRUE);

      // First try and delete the authentication the fast way.
      Gdn::SQL()->Delete('UserAuthentication',
         array('UserID' => $this->User->UserID, 'ProviderKey' => $Provider));

      // Delete the profile information.
      Gdn::UserModel()->SaveAttribute($this->User->UserID, $Provider, NULL);

      if ($this->DeliveryType() == DELIVERY_TYPE_ALL) {
         Redirect(UserUrl($this->User), '', 'connections');
      } else {
         // Grab all of the providers again.
         $PModel = new Gdn_AuthenticationProviderModel();
         $Providers = $PModel->GetProviders();

         $this->SetData('_Providers', $Providers);
         $this->SetData('Connections', array());
         $this->FireEvent('GetConnections');

         // Send back the connection button.
         $Connection = $this->Data("Connections.$Provider");
         require_once $this->FetchViewLocation('connection_functions');
         $this->JsonTarget("#Provider_$Provider .ActivateSlider",
            ConnectButton($Connection),
            'ReplaceWith');

         $this->Render('Blank', 'Utility', 'Dashboard');
      }
   }

   /**
    * Edit user account.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $UserReference Username or User ID.
    */
   public function Edit($UserReference = '', $Username = '', $UserID = '') {
      $this->Permission('Garden.SignIn.Allow');
      $this->GetUserInfo($UserReference, $Username, $UserID, TRUE);
      $UserID = GetValueR('User.UserID', $this);
      $Settings = array();

      // Decide if they have ability to edit the username
      $this->CanEditUsername = C("Garden.Profile.EditUsernames");

      if ($this->CanEditUsername < 0)
         $this->CanEditUsername = FALSE;
      else
         $this->CanEditUsername = $this->CanEditUsername || Gdn::Session()->CheckPermission('Garden.Users.Edit');

      $this->CanEditEmail = C('Garden.Profile.EditEmails', TRUE);

      if ($this->CanEditEmail < 0)
         $this->CanEditEmail = FALSE;
      else
         $this->CanEditEmail = $this->CanEditEmail || Gdn::Session()->CheckPermission('Garden.Users.Edit');

      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      $this->Form->SetModel(Gdn::UserModel());

      // Define gender dropdown options
      $this->GenderOptions = array(
         'u' => T('Unspecified'),
         'm' => T('Male'),
         'f' => T('Female')
      );

      $this->Form->SetData($User);

      $this->FireEvent('BeforeEdit');

      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack()) {
         $this->Form->SetFormValue('UserID', $UserID);

         if (!$this->CanEditUsername)
            $this->Form->SetFormValue("Name", $User['Name']);
         else {
            $UsernameError = T('UsernameError', 'Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long.');
            Gdn::UserModel()->Validation->ApplyRule('Name', 'Username', $UsernameError);
         }

         // API
         // These options become available when POSTing as a user with Garden.Settings.Manage permissions

         if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {

            // Role change

            $RequestedRoles = $this->Form->GetFormValue('RoleID', NULL);
            if (!is_null($RequestedRoles)) {

               $RoleModel = new RoleModel();
               $AllRoles = $RoleModel->GetArray();

               if (!is_array($RequestedRoles))
                  $RequestedRoles = is_numeric($RequestedRoles) ? array($RequestedRoles) : array();

               $RequestedRoles = array_flip($RequestedRoles);
               $UserNewRoles = array_intersect_key($AllRoles, $RequestedRoles);

               // Put the data back into the forum object as if the user had submitted
               // this themselves
               $this->Form->SetFormValue('RoleID', array_keys($UserNewRoles));

               // Allow saving roles
               $Settings['SaveRoles'] = TRUE;

            }

            // Password change

            $NewPassword = $this->Form->GetFormValue('Password', NULL);
            if (!is_null($NewPassword)) {

            }
         }


         if ($this->Form->Save($Settings) !== FALSE) {
            $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
            $this->SetData('Profile', $User);

            $this->InformMessage(Sprite('Check', 'InformSprite').T('Your changes have been saved.'), 'Dismissable AutoDismiss HasSprite');
         }

         if (!$this->CanEditEmail)
            $this->Form->SetFormValue("Email", $User['Email']);

      }

      $this->Title(T('Edit Profile'));
      $this->_SetBreadcrumbs(T('Edit Profile'), '/profile/edit');
      $this->Render();
   }

   /**
    * Default profile page.
    *
    * If current user's profile, get notifications. Otherwise show their activity (if available) or discussions.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $User Unique identifier, possible ID or username.
    * @param string $Username.
    * @param int $UserID Unique ID.
    */
   public function Index($User = '', $Username = '', $UserID = '', $Page = FALSE) {
      $this->EditMode(FALSE);
      $this->GetUserInfo($User, $Username, $UserID);

      if ($this->User->Admin == 2 && $this->Head) {
         // Don't index internal accounts. This is in part to prevent vendors from getting endless Google alerts.
         $this->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex'));
         $this->Head->AddTag('meta', array('name' => 'googlebot', 'content' => 'noindex'));
      }

      // if ($this->User->UserID == Gdn::Session()->UserID)
      //    return $this->Notifications($Page);
      // elseif (C('Garden.Profile.ShowActivities', TRUE))
      if (C('Garden.Profile.ShowActivities', TRUE))
         return $this->Activity($User, $Username, $UserID);
      else
         return Gdn::Dispatcher()->Dispatch(UserUrl($this->User, '', 'discussions'));
   }

   /**
    * Manage current user's invitations.
    *
    * @since 2.0.0
    * @access public
    */
   public function Invitations($UserReference = '', $Username = '', $UserID = '') {
      $this->Permission('Garden.SignIn.Allow');
      $this->EditMode(FALSE);
      $this->GetUserInfo($UserReference, $Username, $UserID, $this->Form->AuthenticatedPostBack());
      $this->SetTabView('Invitations');

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

   /**
    * Set 'NoMobile' cookie for current user to prevent use of mobile theme.
    *
    * @since 2.0.?
    * @access public
    */
   public function NoMobile($Unset = 0) {
      if ($Unset == 1) {
         // Allow mobile again
         Gdn_CookieIdentity::DeleteCookie('VanillaNoMobile');
      }
      else {
         // Set 48-hour "no mobile" cookie
         $Expiration = time() + 172800;
         $Expire = 0;
         $UserID = ((Gdn::Session()->IsValid()) ? Gdn::Session()->UserID : 0);
         $KeyData = $UserID."-{$Expiration}";
         Gdn_CookieIdentity::SetCookie('VanillaNoMobile', $KeyData, array($UserID, $Expiration, 'force'), $Expire);
      }

      Redirect("/", 302);
   }

   /**
    * Show notifications for current user.
    *
    * @since 2.0.0
    * @access public
    * @param int $Page Number to skip (paging).
    */
   public function Notifications($Page = FALSE) {
      $this->Permission('Garden.SignIn.Allow');
      $this->EditMode(FALSE);

      list($Offset, $Limit) = OffsetLimit($Page, 30);

      $this->GetUserInfo();
      $this->_SetBreadcrumbs(T('Notifications'), '/profile/notifications');

      $this->SetTabView('Notifications');
      $Session = Gdn::Session();

      $this->ActivityModel = new ActivityModel();

      // Drop notification count back to zero.
      $this->ActivityModel->MarkRead($Session->UserID);

      // Get notifications data.
      $Activities = $this->ActivityModel->GetNotifications($Session->UserID, $Offset, $Limit)->ResultArray();
      $this->ActivityModel->JoinComments($Activities);
      $this->SetData('Activities', $Activities);
      unset($Activities);
      //$TotalRecords = $this->ActivityModel->GetCountNotifications($Session->UserID);

      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'More';
      $this->Pager->LessCode = 'Newer Notifications';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         FALSE,
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

   public function NotificationsPopin() {
      $this->Permission('Garden.SignIn.Allow');

      $Where = array(
          'NotifyUserID' => Gdn::Session()->UserID,
          'DateUpdated >=' => Gdn_Format::ToDateTime(strtotime('-2 weeks'))
      );

      $this->ActivityModel = new ActivityModel();
      $Activities = $this->ActivityModel->GetWhere($Where, 0, 5)->ResultArray();
      $this->SetData('Activities', $Activities);
      $this->ActivityModel->MarkRead(Gdn::Session()->UserID);

      $this->SetData('Title', T('Notifications'));
      $this->Render('Popin', 'Activity', 'Dashboard');
   }

   /**
    * Set new password for current user.
    *
    * @since 2.0.0
    * @access public
    */
   public function Password() {
      $this->Permission('Garden.SignIn.Allow');

      // Don't allow password editing if using SSO Connect ONLY.
      // This is for security. We encountered the case where a customer charges
      // for membership using their external application and use SSO to let
      // their customers into Vanilla. If you allow those people to change their
      // password in Vanilla, they will then be able to log into Vanilla using
      // Vanilla's login form regardless of the state of their membership in the
      // external app.
      if (C('Garden.Registration.Method') == 'Connect') {
         Gdn::Dispatcher()->Dispatch('DefaultPermission');
         exit();
      }

      Gdn::UserModel()->AddPasswordStrength($this);

      // Get user data and set up form
      $this->GetUserInfo();

      $this->Form->SetModel($this->UserModel);
      $this->AddDefinition('Username', $this->User->Name);

      if ($this->Form->AuthenticatedPostBack() === TRUE) {
         $this->Form->SetFormValue('UserID', $this->User->UserID);
         $this->UserModel->DefineSchema();
//         $this->UserModel->Validation->AddValidationField('OldPassword', $this->Form->FormValues());

         // No password may have been set if they have only signed in with a connect plugin
         if (!$this->User->HashMethod || $this->User->HashMethod == "Vanilla") {
            $this->UserModel->Validation->ApplyRule('OldPassword', 'Required');
            $this->UserModel->Validation->ApplyRule('OldPassword', 'OldPassword', 'Your old password was incorrect.');
         }

         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Strength');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');

         if ($this->Form->Save()) {
            $this->InformMessage(Sprite('Check', 'InformSprite').T('Your password has been changed.'), 'Dismissable AutoDismiss HasSprite');
            $this->Form->ClearInputs();
         }
      }
      $this->Title(T('Change My Password'));
      $this->_SetBreadcrumbs(T('Change My Password'), '/profile/password');
      $this->Render();
   }

   /**
    * Set user's photo (avatar).
    *
    * @since 2.0.0
    * @access public
    * @param mixed $UserReference Unique identifier, possible username or ID.
    * @param string $Username.
    */
   public function Picture($UserReference = '', $Username = '', $UserID = '') {
      if (!C('Garden.Profile.EditPhotos', TRUE)) {
         throw ForbiddenException('@Editing user photos has been disabled.');
      }

      // Permission checks
      $this->Permission('Garden.Profiles.Edit');
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');

      // Check ability to manipulate image
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

      // Get user data & prep form.
      $this->GetUserInfo($UserReference, $Username, $UserID, TRUE);

      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('UserID', $this->User->UserID);

      if ($this->Form->AuthenticatedPostBack() === TRUE) {
         $UploadImage = new Gdn_UploadImage();
         try {
            // Validate the upload
            $TmpImage = $UploadImage->ValidateUpload('Picture');

            // Generate the target image name.
            $TargetImage = $UploadImage->GenerateTargetName(PATH_UPLOADS, '', TRUE);
            $Basename = pathinfo($TargetImage, PATHINFO_BASENAME);
            $Subdir = StringBeginsWith(dirname($TargetImage), PATH_UPLOADS.'/', FALSE, TRUE);

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
//               Gdn::Config('Garden.Preview.MaxHeight', 100),
//               Gdn::Config('Garden.Preview.MaxWidth', 75)
//            );

            // Save the uploaded image in thumbnail size
            $ThumbSize = Gdn::Config('Garden.Thumbnail.Size', 40);
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
            if (!$this->UserModel->Save(array('UserID' => $this->User->UserID, 'Photo' => $UserPhoto), array('CheckExisting' => TRUE)))
               $this->Form->SetValidationResults($this->UserModel->ValidationResults());
            else
               $this->User->Photo = $UserPhoto;
         }
         // If there were no problems, redirect back to the user account
         if ($this->Form->ErrorCount() == 0) {
            $this->InformMessage(Sprite('Check', 'InformSprite').T('Your changes have been saved.'), 'Dismissable AutoDismiss HasSprite');
            Redirect($this->DeliveryType() == DELIVERY_TYPE_VIEW ? UserUrl($this->User) : UserUrl($this->User, '', 'picture'));
         }
      }
      if ($this->Form->ErrorCount() > 0)
         $this->DeliveryType(DELIVERY_TYPE_ALL);

      $this->Title(T('Change Picture'));
      $this->_SetBreadcrumbs(T('Change My Picture'), '/profile/picture');
      $this->Render();
   }

   /**
    * Gets or sets a user's preference. This method is meant for ajax calls.
    * @since 2.1
    * @param string $Key The name of the preference.
    */
   public function Preference($Key = FALSE) {
      $this->Permission('Garden.SignIn.Allow');

      $this->Form->InputPrefix = '';

      if ($this->Form->AuthenticatedPostBack()) {
         $Data = $this->Form->FormValues();
         Gdn::UserModel()->SavePreference(Gdn::Session()->UserID, $Data);
      } else {
         $User = Gdn::UserModel()->GetID(Gdn::Session()->UserID, DATASET_TYPE_ARRAY);
         $Pref = GetValueR($Key, $User['Preferences'], NULL);

         $this->SetData($Key, $Pref);
      }

      $this->Render('Blank', 'Utility');
   }

   /**
    * Edit user's preferences (mostly notification settings).
    *
    * @since 2.0.0
    * @access public
    * @param mixed $UserReference Unique identifier, possibly username or ID.
    * @param string $Username.
    * @param int $UserID Unique identifier.
    */
   public function Preferences($UserReference = '', $Username = '', $UserID = '') {
      $this->AddJsFile('profile.js');
      $Session = Gdn::Session();
      $this->Permission('Garden.SignIn.Allow');

      // Get user data
      $this->GetUserInfo($UserReference, $Username, $UserID, TRUE);
      $UserPrefs = Gdn_Format::Unserialize($this->User->Preferences);
      if ($this->User->UserID != $Session->UserID)
         $this->Permission('Garden.Users.Edit');

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

      // Allow email notification of applicants (if they have permission & are using approval registration)
      if (CheckPermission('Garden.Users.Approve') && C('Garden.Registration.Method') == 'Approval')
         $this->Preferences['Notifications']['Email.Applicant'] = array(T('NotifyApplicant', 'Notify me when anyone applies for membership.'), 'Meta');

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

      // Loop the preferences, setting defaults from the configuration.
      $CurrentPrefs = array();
      foreach ($this->Preferences as $PrefGroup => $Prefs) {
         foreach ($Prefs as $Pref => $Desc) {
            $Location = 'Prefs';
            if (is_array($Desc))
               list($Desc, $Location) = $Desc;

            if ($Location == 'Meta')
               $CurrentPrefs[$Pref] = GetValue($Pref, $MetaPrefs, FALSE);
            else
               $CurrentPrefs[$Pref] = GetValue($Pref, $UserPrefs, C('Preferences.'.$Pref, '0'));

            unset($MetaPrefs[$Pref]);
         }
      }
      $CurrentPrefs = array_merge($CurrentPrefs, $MetaPrefs);
      $CurrentPrefs = array_map('intval', $CurrentPrefs);
      $this->SetData('Preferences', $CurrentPrefs);

      if (UserModel::NoEmail()) {
         $this->PreferenceGroups = self::_RemoveEmailPreferences($this->PreferenceGroups);
         $this->PreferenceTypes = self::_RemoveEmailPreferences($this->PreferenceTypes);
         $this->SetData('NoEmail', TRUE);
      }

      $this->SetData('PreferenceGroups', $this->PreferenceGroups);
      $this->SetData('PreferenceTypes', $this->PreferenceTypes);
      $this->SetData('PreferenceList', $this->Preferences);

      if ($this->Form->AuthenticatedPostBack()) {
         // Get, assign, and save the preferences.
         $NewMetaPrefs = array();
         foreach ($this->Preferences as $PrefGroup => $Prefs) {
            foreach ($Prefs as $Pref => $Desc) {
               $Location = 'Prefs';
               if (is_array($Desc))
                  list($Desc, $Location) = $Desc;

               $Value = $this->Form->GetValue($Pref, NULL);
               if (is_null($Value)) continue;

               if ($Location == 'Meta') {
                  $NewMetaPrefs[$Pref] = $Value ? $Value : NULL;
                  if ($Value)
                     $UserPrefs[$Pref] = $Value; // dup for notifications code.
               } else {
                  if (!$CurrentPrefs[$Pref] && !$Value)
                     unset($UserPrefs[$Pref]); // save some space
                  else
                     $UserPrefs[$Pref] = $Value;
               }
            }
         }

         $this->UserModel->SavePreference($this->User->UserID, $UserPrefs);
         UserModel::SetMeta($this->User->UserID, $NewMetaPrefs, 'Preferences.');

         $this->SetData('Preferences', array_merge($this->Data('Preferences', array()), $UserPrefs, $NewMetaPrefs));

         if (count($this->Form->Errors() == 0))
            $this->InformMessage(Sprite('Check', 'InformSprite').T('Your preferences have been saved.'), 'Dismissable AutoDismiss HasSprite');
      } else {
         $this->Form->SetData($CurrentPrefs);
      }

      $this->Title(T('Notification Preferences'));
      $this->_SetBreadcrumbs($this->Data('Title'), $this->CanonicalUrl());
      $this->Render();
   }

   protected static function _RemoveEmailPreferences($Data) {
      $Data = array_filter($Data, array('ProfileController', '_RemoveEmailFilter'));

      $Result = array();
      foreach ($Data as $K => $V) {
         if (is_array($V))
            $Result[$K] = self::_RemoveEmailPreferences($V);
         else
            $Result[$K] = $V;
      }

      return $Result;
   }

   protected static function _RemoveEmailFilter($Value) {
      if (is_string($Value) && strpos($Value, 'Email') !== FALSE)
         return FALSE;
      return TRUE;
   }

   /**
    * Remove the user's photo.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $UserReference Unique identifier, possibly username or ID.
    * @param string $Username.
    * @param string $TransientKey Security token.
    */
   public function RemovePicture($UserReference = '', $Username = '', $TransientKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');

      // Get user data & another permission check
      $this->GetUserInfo($UserReference, $Username, '', TRUE);
      $RedirectUrl = UserUrl($this->User, '', 'picture');
      if ($Session->ValidateTransientKey($TransientKey)
         && is_object($this->User)
         && (
            $this->User->UserID == $Session->UserID
            || $Session->CheckPermission('Garden.Users.Edit')
         )
      ) {
         // Do removal, set message, redirect
         Gdn::UserModel()->RemovePicture($this->User->UserID);
         $this->InformMessage(T('Your picture has been removed.'));
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

   /**
    * Let user send an invitation.
    *
    * @since 2.0.0
    * @access public
    * @param int $InvitationID Unique identifier.
    * @param string $TransientKey Security token.
    */
   public function SendInvite($InvitationID = '', $TransientKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $InvitationModel = new InvitationModel();
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey)) {
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

   public function _SetBreadcrumbs($Name = NULL, $Url = NULL) {
      // Add the root link.
      if ($this->User->UserID == Gdn::Session()->UserID) {
         $Root = array('Name' => T('Profile'), 'Url' => '/profile');
         $Breadcrumb = array('Name' => $Name, 'Url' => $Url);
      } else {
         $NameUnique = C('Garden.Registration.NameUnique');

         $Root = array('Name' => $this->User->Name, 'Url' => UserUrl($this->User));
         $Breadcrumb = array('Name' => $Name, 'Url' => $Url.'/'.($NameUnique ? '' : $this->User->UserID.'/').rawurlencode($this->User->Name));
      }

      $this->Data['Breadcrumbs'][] = $Root;

      if ($Name && !StringBeginsWith($Root['Url'], $Url)) {
         $this->Data['Breadcrumbs'][] = array('Name' => $Name, 'Url' => $Url);
      }
   }

   /**
    * Set user's thumbnail (crop & center photo).
    *
    * @since 2.0.0
    * @access public
    * @param mixed $UserReference Unique identifier, possible username or ID.
    * @param string $Username.
    */
   public function Thumbnail($UserReference = '', $Username = '') {
      if (!C('Garden.Profile.EditPhotos', TRUE)) {
         throw ForbiddenException('@Editing user photos has been disabled.');
      }

      // Initial permission checks (valid user)
      $this->Permission('Garden.SignIn.Allow');
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');

      // Need some extra JS
      $this->AddJsFile('jquery.jcrop.pack.js');
      $this->AddJsFile('profile.js');

      $this->GetUserInfo($UserReference, $Username, '', TRUE);

      // Permission check (correct user)
      if ($this->User->UserID != $Session->UserID && !$Session->CheckPermission('Garden.Users.Edit'))
         throw new Exception(T('You cannot edit the thumbnail of another member.'));

      // Form prep
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('UserID', $this->User->UserID);

      // Confirm we have a photo to manipulate
      if (!$this->User->Photo)
         $this->Form->AddError('You must first upload a picture before you can create a thumbnail.');

      // Define the thumbnail size
      $this->ThumbSize = Gdn::Config('Garden.Thumbnail.Size', 40);

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

      // We actually need to upload a new file to help with cdb ttls.
      $NewPhoto = $Upload->GenerateTargetName(
         'userpics',
         trim(pathinfo($this->User->Photo, PATHINFO_EXTENSION), '.'),
         TRUE);

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
               ChangeBasename($NewPhoto, 'n%s'),
               $this->ThumbSize, $this->ThumbSize,
               array('Crop' => TRUE, 'SourceX' => $this->Form->GetValue('x'), 'SourceY' => $this->Form->GetValue('y'), 'SourceWidth' => $this->Form->GetValue('w'), 'SourceHeight' => $this->Form->GetValue('h')));

            // Save new profile picture.
            $Parsed = $Upload->SaveAs($Source, ChangeBasename($NewPhoto, 'p%s'));
            $UserPhoto = sprintf($Parsed['SaveFormat'], $NewPhoto);
            // Save the new photo info.
            Gdn::UserModel()->SetField($this->User->UserID, 'Photo', $UserPhoto);

            // Remove the old profile picture.
            @$Upload->Delete($Basename);
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
         // If there were no problems, redirect back to the user account
         if ($this->Form->ErrorCount() == 0) {
            Redirect(UserUrl($this->User, '', 'picture'));
            $this->InformMessage(Sprite('Check', 'InformSprite').T('Your changes have been saved.'), 'Dismissable AutoDismiss HasSprite');
         }
      }
      // Delete the source image if it is externally hosted.
      if ($PhotoParsed['Type']) {
         @unlink($Source);
      }

      $this->Title(T('Edit My Thumbnail'));
      $this->_SetBreadcrumbs(T('Edit My Thumbnail'), '/profile/thumbnail');
      $this->Render();
   }

   /**
    * Revoke an invitation.
    *
    * @since 2.0.0
    * @access public
    * @param int $InvitationID Unique identifier.
    * @param string $TransientKey Security token.
    */
   public function UnInvite($InvitationID = '', $TransientKey = '') {
      $this->Permission('Garden.SignIn.Allow');
      $InvitationModel = new InvitationModel();
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey)) {
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
    * Adds a tab (or array of tabs) to the profile tab collection ($this->ProfileTabs).
    *
    * @since 2.0.0
    * @access public
    * @param mixed $TabName Tab name (or array of tab names) to add to the profile tab collection.
    * @param string $TabUrl URL the tab should point to.
    * @param string $CssClass Class property to apply to tab.
    * @param string $TabHtml Overrides tab's HTML.
    */
   public function AddProfileTab($TabName, $TabUrl = '', $CssClass = '', $TabHtml = '') {
      if (!is_array($TabName)) {
         if ($TabHtml == '')
            $TabHtml = $TabName;

         if (!$CssClass && $TabUrl == Gdn::Request()->Path())
            $CssClass = 'Active';

         $TabName = array($TabName => array('TabUrl' => $TabUrl, 'CssClass' => $CssClass, 'TabHtml' => $TabHtml));
      }

      foreach ($TabName as $Name => $TabInfo) {
         $Url = GetValue('TabUrl', $TabInfo, '');
         if ($Url == '')
            $TabInfo['TabUrl'] = UserUrl($this->User, '', strtolower($Name));

         $this->ProfileTabs[$Name] = $TabInfo;
         $this->_ProfileTabs[$Name] = $TabInfo; // Backwards Compatibility
      }
   }

   /**
    * Adds the option menu to the panel asset.
    *
    * @since 2.0.0
    * @access public
    * @param string $CurrentUrl Path to highlight.
    */
   public function AddSideMenu($CurrentUrl = '') {
      if (!$this->User)
         return;

      // Make sure to add the "Edit Profile" buttons.
      $this->AddModule('ProfileOptionsModule');

      // Show edit menu if in edit mode
      // Show profile pic & filter menu otherwise
      $SideMenu = new SideMenuModule($this);
      $this->EventArguments['SideMenu'] = &$SideMenu; // Doing this out here for backwards compatibility.
      if ($this->EditMode) {
         $this->AddModule('UserBoxModule');
         $this->BuildEditMenu($SideMenu, $CurrentUrl);
         $this->FireEvent('AfterAddSideMenu');
         $this->AddModule($SideMenu, 'Panel');
      } else {
         // Make sure the userphoto module gets added to the page
         $this->AddModule('UserPhotoModule');

         // And add the filter menu module
         $this->FireEvent('AfterAddSideMenu');
         $this->AddModule('ProfileFilterModule');
      }
   }

   /**
    * @param SideMenuModule $Module
    * @param string $CurrentUrl
    */
   public function BuildEditMenu(&$Module, $CurrentUrl = '') {
      if (!$this->User)
         return;

      $Module->HtmlId = 'UserOptions';
      $Module->AutoLinkGroups = FALSE;
      $Session = Gdn::Session();
      $ViewingUserID = $Session->UserID;
      $Module->AddItem('Options', '', FALSE, array('class' => 'SideMenu'));

      // Check that we have the necessary tools to allow image uploading
      $AllowImages = C('Garden.Profile.EditPhotos', TRUE) && Gdn_UploadImage::CanUploadImages();

      // Is the photo hosted remotely?
      $RemotePhoto = IsUrl($this->User->Photo);

      if ($this->User->UserID != $ViewingUserID) {
         // Include user js files for people with edit users permissions
         if ($Session->CheckPermission('Garden.Users.Edit')) {
//              $this->AddJsFile('jquery.gardenmorepager.js');
           $this->AddJsFile('user.js');
         }
         $Module->AddLink('Options', Sprite('SpProfile').' '.T('Edit Profile'), UserUrl($this->User, '', 'edit'), FALSE, array('class' => 'Popup EditAccountLink'));
         $Module->AddLink('Options', Sprite('SpProfile').' '.T('Edit Account'), '/user/edit/'.$this->User->UserID, 'Garden.Users.Edit', array('class' => 'Popup EditAccountLink'));
         $Module->AddLink('Options', Sprite('SpDelete').' '.T('Delete Account'), '/user/delete/'.$this->User->UserID, 'Garden.Users.Delete', array('class' => 'Popup DeleteAccountLink'));

         if ($this->User->Photo != '' && $AllowImages)
            $Module->AddLink('Options', Sprite('SpDelete').' '.T('Remove Picture'), CombinePaths(array(UserUrl($this->User, '', 'removepicture'),$Session->TransientKey())), 'Garden.Users.Edit', array('class' => 'RemovePictureLink'));

         $Module->AddLink('Options', Sprite('SpPreferences').' '.T('Edit Preferences'), UserUrl($this->User, '', 'preferences'), 'Garden.Users.Edit', array('class' => 'Popup PreferencesLink'));

         // Add profile options for everyone
         $Module->AddLink('Options', Sprite('SpPicture').' '.T('Change Picture'), UserUrl($this->User, '', 'picture'), 'Garden.Users.Edit', array('class' => 'PictureLink'));
         if ($this->User->Photo != '' && $AllowImages && !$RemotePhoto) {
            $Module->AddLink('Options', Sprite('SpThumbnail').' '.T('Edit Thumbnail'), UserUrl($this->User, '', 'thumbnail'), 'Garden.Users.Edit', array('class' => 'ThumbnailLink'));
         }
      } else {
         // Add profile options for the profile owner
         // Don't allow account editing if it has been turned off.
         // Don't allow password editing if using SSO Connect ONLY.
         // This is for security. We encountered the case where a customer charges
         // for membership using their external application and use SSO to let
         // their customers into Vanilla. If you allow those people to change their
         // password in Vanilla, they will then be able to log into Vanilla using
         // Vanilla's login form regardless of the state of their membership in the
         // external app.
         if (C('Garden.UserAccount.AllowEdit') && C('Garden.Registration.Method') != 'Connect') {
            $Module->AddLink('Options', Sprite('SpEdit').' '.T('Edit Profile'), '/profile/edit', FALSE, array('class' => 'Popup EditAccountLink'));

            // No password may have been set if they have only signed in with a connect plugin
            $PasswordLabel = T('Change My Password');
            if ($this->User->HashMethod && $this->User->HashMethod != "Vanilla")
               $PasswordLabel = T('Set A Password');
            $Module->AddLink('Options', Sprite('SpPassword').' '.$PasswordLabel, '/profile/password', FALSE, array('class' => 'Popup PasswordLink'));
         }

         $Module->AddLink('Options', Sprite('SpPreferences').' '.T('Notification Preferences'), UserUrl($this->User, '', 'preferences'), FALSE, array('class' => 'Popup PreferencesLink'));
         if ($AllowImages) {
            $Module->AddLink('Options', Sprite('SpPicture').' '.T('Change My Picture'), '/profile/picture', 'Garden.Profiles.Edit', array('class' => 'PictureLink'));
         }

         if ($this->User->Photo != '' && $AllowImages && !$RemotePhoto) {
            $Module->AddLink('Options', Sprite('SpThumbnail').' '.T('Edit My Thumbnail'), '/profile/thumbnail', 'Garden.Profiles.Edit', array('class' => 'ThumbnailLink'));
         }
      }

      if ($this->User->UserID == $ViewingUserID || $Session->CheckPermission('Garden.Users.Edit')) {
         $this->SetData('Connections', array());
         $this->EventArguments['User'] = $this->User;
         $this->FireEvent('GetConnections');
         if (count($this->Data('Connections')) > 0) {
            $Module->AddLink('Options', Sprite('SpConnection').' '.T('Social'), '/profile/connections', 'Garden.SignIn.Allow');
         }
      }
   }

   /**
    * Build the user profile.
    *
    * Set the page title, add data to page modules, add modules to assets,
    * add tabs to tab menu. $this->User must be defined, or this method will throw an exception.
    *
    * @since 2.0.0
    * @access public
    * @return bool Always true.
    */
   public function BuildProfile() {
      if (!is_object($this->User))
         throw new Exception(T('Cannot build profile information if user is not defined.'));

      $Session = Gdn::Session();
      if (strpos($this->CssClass, 'Profile') === FALSE)
         $this->CssClass .= ' Profile';
      $this->Title(Gdn_Format::Text($this->User->Name));

      if ($this->_DeliveryType != DELIVERY_TYPE_VIEW) {
         // Javascript needed
         $this->AddJsFile('jquery.jcrop.pack.js');
         $this->AddJsFile('profile.js');
         $this->AddJsFile('jquery.gardenmorepager.js');
         $this->AddJsFile('activity.js');

         // Build activity URL
         $ActivityUrl = 'profile/activity/';
         if ($this->User->UserID != $Session->UserID)
            $ActivityUrl = UserUrl($this->User, '', 'activity');

         // Show activity?
         if (C('Garden.Profile.ShowActivities', TRUE))
            $this->AddProfileTab(T('Activity'), $ActivityUrl, 'Activity', Sprite('SpActivity').' '.T('Activity'));

         // Show notifications?
         if ($this->User->UserID == $Session->UserID) {
            $Notifications = T('Notifications');
            $NotificationsHtml = Sprite('SpNotifications').' '.$Notifications;
            $CountNotifications = $Session->User->CountNotifications;
            if (is_numeric($CountNotifications) && $CountNotifications > 0)
               $NotificationsHtml .= ' <span class="Aside"><span class="Count">'.$CountNotifications.'</span></span>';

            $this->AddProfileTab($Notifications, 'profile/notifications', 'Notifications', $NotificationsHtml);
         }

         // Show invitations?
         if (C('Garden.Registration.Method') == 'Invitation')
            $this->AddProfileTab(T('Invitations'), 'profile/invitations', 'InvitationsLink');

         $this->FireEvent('AddProfileTabs');
      }

      return TRUE;
   }

   /**
    * Render basic data about user.
    *
    * @since 2.0.?
    * @access public
    * @param int $UserID Unique ID.
    */
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
    * Retrieve the user to be manipulated. Defaults to current user.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $User Unique identifier, possibly username or ID.
    * @param string $Username.
    * @param int $UserID Unique ID.
    * @param bool $CheckPermissions Whether or not to check user permissions.
    * @return bool Always true.
    */
   public function GetUserInfo($UserReference = '', $Username = '', $UserID = '', $CheckPermissions = FALSE) {
      if ($this->_UserInfoRetrieved)
         return;

      if (!C('Garden.Profile.Public') && !Gdn::Session()->IsValid())
         throw PermissionException();

      // If a UserID was provided as a querystring parameter, use it over anything else:
      if ($UserID) {
         $UserReference = $UserID;
         $Username = 'Unknown'; // Fill this with a value so the $UserReference is assumed to be an integer/userid.
      }

      $this->Roles = array();
      if ($UserReference == '') {
         if ($Username) {
            $this->User = $this->UserModel->GetByUsername($Username);
         } else
            $this->User = $this->UserModel->GetID(Gdn::Session()->UserID);
      } elseif (is_numeric($UserReference) && $Username != '') {
         $this->User = $this->UserModel->GetID($UserReference);
      } else {
         $this->User = $this->UserModel->GetByUsername($UserReference);
      }

      if ($this->User === FALSE) {
         throw NotFoundException('User');
      } else if ($this->User->Deleted == 1) {
         Redirect('dashboard/home/deleted');
      } else {
         $this->RoleData = $this->UserModel->GetRoles($this->User->UserID);
         if ($this->RoleData !== FALSE && $this->RoleData->NumRows(DATASET_TYPE_ARRAY) > 0)
            $this->Roles = ConsolidateArrayValuesByKey($this->RoleData->Result(), 'Name');

         if (Gdn::Session()->CheckPermission('Garden.Settings.Manage') || Gdn::Session()->UserID == $this->User->UserID) {
            $this->User->Transient = GetValueR('Attributes.TransientKey', $this->User);
         }

         $this->SetData('Profile', $this->User);
         $this->SetData('UserRoles', $this->Roles);
         if ($CssClass = GetValue('_CssClass', $this->User)) {
            $this->CssClass .= ' '.$CssClass;
         }
      }

      if ($CheckPermissions && Gdn::Session()->UserID != $this->User->UserID)
         $this->Permission('Garden.Users.Edit');

      $this->AddSideMenu();
      $this->_UserInfoRetrieved = TRUE;
      return TRUE;
   }

   /**
    * Build URL to user's profile.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $UserReference Unique identifier, possibly username or ID.
    * @param string $UserID Unique ID.
    * @return string Relative URL path.
    */
   public function ProfileUrl($UserReference = NULL, $UserID = NULL) {
      if (!property_exists($this, 'User'))
         $this->GetUserInfo();

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

   public function GetProfileUrl($UserReference = NULL, $UserID = NULL) {
      if (!property_exists($this, 'User'))
         $this->GetUserInfo();

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
    * Define & select the current tab in the tab menu. Sets $this->_CurrentTab.
    *
    * @since 2.0.0
    * @access public
    * @param string $CurrentTab Name of tab to highlight.
    * @param string $View View name. Defaults to index.
    * @param string $Controller Controller name. Defaults to Profile.
    * @param string $Application Application name. Defaults to Dashboard.
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
      $this->CurrentTab = T($CurrentTab);
      $this->_CurrentTab = $this->CurrentTab; // Backwards Compat
   }

   public function EditMode($Switch) {

      $this->EditMode = $Switch;
      if (!$this->EditMode && strpos($this->CssClass, 'EditMode') !== FALSE)
         $this->CssClass = str_replace('EditMode', '', $this->CssClass);
   }

   /**
    * Fetch multiple users
    *
    * Note: API only
    * @param type $UserID
    */
   public function Multi($UserID) {
      $this->Permission('Garden.Settings.Manage');
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
      $this->DeliveryType(DELIVERY_TYPE_DATA);

      // Get rid of Reactions busybody data
      unset($this->Data['Counts']);

      $UserID = (array)$UserID;
      $Users = Gdn::UserModel()->GetIDs($UserID);

      $AllowedFields = array('UserID','Name','Title','Location','About','Email','Gender','CountVisits','CountInvitations','CountNotifications','Admin','Verified','Banned','Deleted','CountDiscussions','CountComments','CountBookmarks','CountBadges','Points','Punished','RankID','PhotoUrl','Online','LastOnlineDate');
      $AllowedFields = array_fill_keys($AllowedFields, NULL);
      foreach ($Users as &$User)
         $User = array_intersect_key($User, $AllowedFields);
      $Users = array_values($Users);
      $this->SetData('Users', $Users);

      $this->Render();
   }

}
