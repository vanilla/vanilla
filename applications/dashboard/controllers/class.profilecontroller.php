<?php
/**
 * Manages individual user profiles.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /profile endpoint.
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

    /** @var Gdn_Form */
    public $Form;

    /** @var array List of available tabs. */
    public $ProfileTabs;

    /** @var string View for current tab. */
    protected $_TabView;

    /** @var string Controller for current tab. */
    protected $_TabController;

    /** @var string Application for current tab. */
    protected $_TabApplication;

    /** @var bool Whether data has been stored in $this->User yet. */
    protected $_UserInfoRetrieved = false;

    /**
     * Prep properties.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        $this->User = false;
        $this->_TabView = 'Activity';
        $this->_TabController = 'ProfileController';
        $this->_TabApplication = 'Dashboard';
        $this->CurrentTab = 'Activity';
        $this->ProfileTabs = array();
        $this->editMode(true);
        parent::__construct();
    }

    /**
     * Adds JS, CSS, & modules. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        $this->ModuleSortContainer = 'Profile';
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('global.js');

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addModule('GuestModule');
        parent::initialize();

        Gdn_Theme::section('Profile');

        if ($this->EditMode) {
            $this->CssClass .= 'EditMode';
        }

        $this->setData('Breadcrumbs', array());
        $this->CanEditPhotos = c('Garden.Profile.EditPhotos') || Gdn::session()->checkPermission('Garden.Users.Edit');
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
    public function activity($UserReference = '', $Username = '', $UserID = '', $Page = '') {
        $this->permission('Garden.Profiles.View');
        $this->editMode(false);

        // Object setup
        $Session = Gdn::session();
        $this->ActivityModel = new ActivityModel();

        // Calculate offset.
        list($Offset, $Limit) = offsetLimit($Page, 30);

        // Get user, tab, and comment
        $this->getUserInfo($UserReference, $Username, $UserID);
        $UserID = $this->User->UserID;
        $Username = $this->User->Name;

        $this->_setBreadcrumbs(t('Activity'), userUrl($this->User, '', 'activity'));

        $this->setTabView('Activity');
        $Comment = $this->Form->getFormValue('Comment');

        // Load data to display
        $this->ProfileUserID = $this->User->UserID;
        $Limit = 30;

        $NotifyUserIDs = array(ActivityModel::NOTIFY_PUBLIC);
        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $NotifyUserIDs[] = ActivityModel::NOTIFY_MODS;
        }

        $Activities = $this->ActivityModel->getWhere(
            array('ActivityUserID' => $UserID, 'NotifyUserID' => $NotifyUserIDs),
            $Offset,
            $Limit
        )->resultArray();
        $this->ActivityModel->joinComments($Activities);
        $this->setData('Activities', $Activities);
        if (count($Activities) > 0) {
            $LastActivity = reset($Activities);
            $LastModifiedDate = Gdn_Format::toTimestamp($this->User->DateUpdated);
            $LastActivityDate = Gdn_Format::toTimestamp($LastActivity['DateInserted']);
            if ($LastModifiedDate < $LastActivityDate) {
                $LastModifiedDate = $LastActivityDate;
            }

            // Make sure to only query this page if the user has no new activity since the requesting browser last saw it.
            $this->SetLastModified($LastModifiedDate);
        }

        // Set the canonical Url.
        if (is_numeric($this->User->Name) || Gdn_Format::url($this->User->Name) != strtolower($this->User->Name)) {
            $this->canonicalUrl(url('profile/'.$this->User->UserID.'/'.Gdn_Format::url($this->User->Name), true));
        } else {
            $this->canonicalUrl(url('profile/'.strtolower($this->User->Name), true));
        }

        $this->render();
    }

    /**
     * Clear user's current status message.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $UserID
     */
    public function clear($UserID = '') {
        if (empty($_POST)) { // TODO: rm global
            throw permissionException('Javascript');
        }

        $UserID = is_numeric($UserID) ? $UserID : 0;
        $Session = Gdn::session();
        if ($UserID != $Session->UserID && !$Session->checkPermission('Garden.Moderation.Manage')) {
            throw permissionException('Garden.Moderation.Manage');
        }

        if ($UserID > 0) {
            $this->UserModel->saveAbout($UserID, '');
        }

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            redirect('/profile');
        } else {
            $this->jsonTarget('#Status', '', 'Remove');
            $this->render('Blank', 'Utility');
        }
    }

    /**
     *
     *
     * @param $Type
     * @param string $UserReference
     * @param string $Username
     * @throws Exception
     */
    public function connect($Type, $UserReference = '', $Username = '') {
        $this->permission('Garden.SignIn.Allow');
        $this->getUserInfo($UserReference, $Username, '', true);

        // Fire an event and let whatever plugin handle the connection.
        // This will fire an event in the form ProfileController_FacebookConnect_Handler(...).
        $Connected = false;
        $this->EventArguments['Connected'] =& $Connected;


        $this->fireEvent(ucfirst($Type).'Connect');


    }

    /**
     * Lists the connections to other sites.
     *
     * @param int|string $UserReference
     * @param string $Username
     * @since 2.1
     */
    public function connections($UserReference = '', $Username = '') {
        $this->permission('Garden.SignIn.Allow');
        $this->getUserInfo($UserReference, $Username, '', true);
        $UserID = valr('User.UserID', $this);
        $this->_setBreadcrumbs(t('Social'), userUrl($this->User, '', 'connections'));

        $PModel = new Gdn_AuthenticationProviderModel();
        $Providers = $PModel->getProviders();

        $this->setData('_Providers', $Providers);
        $this->setData('Connections', array());
        $this->EventArguments['User'] = $this->User;
        $this->fireEvent('GetConnections');

        // Add some connection information.
        foreach ($this->Data['Connections'] as &$Row) {
            $Provider = val($Row['ProviderKey'], $Providers, array());

            touchValue('Connected', $Row, !is_null(val('UniqueID', $Provider, null)));
        }

        $this->canonicalUrl(userUrl($this->User, '', 'connections'));
        $this->title(t('Social'));
        require_once $this->fetchViewLocation('connection_functions');
        $this->render();
    }

    /**
     * Generic way to get count via UserModel->ProfileCount().
     *
     * @since 2.0.?
     * @access public
     * @param string $Column Name of column to count for this user.
     * @param int $UserID Defaults to current session.
     */
    public function count($Column, $UserID = false) {
        $Column = 'Count'.ucfirst($Column);
        if (!$UserID) {
            $UserID = Gdn::session()->UserID;
        }

        $Count = $this->UserModel->profileCount($UserID, $Column);
        $this->setData($Column, $Count);
        $this->setData('_Value', $Count);
        $this->setData('_CssClass', 'Count');
        $this->render('Value', 'Utility');
    }

    /**
     * Delete an invitation that has already been accepted.
     * @param int $InvitationID
     * @throws Exception The inviation was not found or the user doesn't have permission to remove it.
     */
    public function deleteInvitation($InvitationID) {
        $this->permission('Garden.SignIn.Allow');

        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $InvitationModel = new InvitationModel();

        $InvitationModel->delete($InvitationID);
        $this->informMessage(t('The invitation was removed successfully.'));

        $this->jsonTarget(".js-invitation[data-id=\"{$InvitationID}\"]", '', 'SlideUp');

        $this->render('Blank', 'Utility');
    }

    /**
     *
     *
     * @param string $UserReference
     * @param string $Username
     * @param $Provider
     * @throws Exception
     */
    public function disconnect($UserReference = '', $Username = '', $Provider) {
        if (!$this->Request->isPostBack()) {
            throw permissionException('Javascript');
        }

        $this->permission('Garden.SignIn.Allow');
        $this->getUserInfo($UserReference, $Username, '', true);

        // First try and delete the authentication the fast way.
        Gdn::sql()->delete(
            'UserAuthentication',
            array('UserID' => $this->User->UserID, 'ProviderKey' => $Provider)
        );

        // Delete the profile information.
        Gdn::userModel()->saveAttribute($this->User->UserID, $Provider, null);

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            redirect(userUrl($this->User), '', 'connections');
        } else {
            // Grab all of the providers again.
            $PModel = new Gdn_AuthenticationProviderModel();
            $Providers = $PModel->getProviders();

            $this->setData('_Providers', $Providers);
            $this->setData('Connections', array());
            $this->fireEvent('GetConnections');

            // Send back the connection button.
            $Connection = $this->data("Connections.$Provider");
            require_once $this->fetchViewLocation('connection_functions');
            $this->jsonTarget(
                "#Provider_$Provider .ActivateSlider",
                connectButton($Connection),
                'ReplaceWith'
            );

            $this->render('Blank', 'Utility', 'Dashboard');
        }
    }

    /**
     * Edit user account.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $UserReference Username or User ID.
     */
    public function edit($UserReference = '', $Username = '', $UserID = '') {
        $this->permission('Garden.SignIn.Allow');
        $this->getUserInfo($UserReference, $Username, $UserID, true);
        $UserID = valr('User.UserID', $this);
        $Settings = array();

        // Set up form
        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
        $this->Form->setModel(Gdn::userModel());
        $this->Form->setData($User);
        $this->setData('User', $User);

        // Decide if they have ability to edit the username
        $CanEditUsername = (bool)c("Garden.Profile.EditUsernames") || Gdn::session()->checkPermission('Garden.Users.Edit');
        $this->setData('_CanEditUsername', $CanEditUsername);

        // Decide if they have ability to edit the email
        $EmailEnabled = (bool)c('Garden.Profile.EditEmails', true) && !UserModel::noEmail();
        $CanEditEmail = ($EmailEnabled && $UserID == Gdn::session()->UserID) || checkPermission('Garden.Users.Edit');
        $this->setData('_CanEditEmail', $CanEditEmail);

        // Decide if they have ability to confirm users
        $Confirmed = (bool)valr('User.Confirmed', $this);
        $CanConfirmEmail = (UserModel::requireConfirmEmail() && checkPermission('Garden.Users.Edit'));
        $this->setData('_CanConfirmEmail', $CanConfirmEmail);
        $this->setData('_EmailConfirmed', $Confirmed);
        $this->Form->setValue('ConfirmEmail', (int)$Confirmed);

        // Decide if we can *see* email
        $this->setData('_CanViewPersonalInfo', Gdn::session()->UserID == val('UserID', $User) || checkPermission('Garden.PersonalInfo.View') || checkPermission('Garden.Users.Edit'));

        // Define gender dropdown options
        $this->GenderOptions = array(
            'u' => t('Unspecified'),
            'm' => t('Male'),
            'f' => t('Female')
        );

        $this->fireEvent('BeforeEdit');

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack()) {
            $this->Form->setFormValue('UserID', $UserID);

            if (!$CanEditUsername) {
                $this->Form->setFormValue("Name", $User['Name']);
            } else {
                $UsernameError = t('UsernameError', 'Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long.');
                Gdn::userModel()->Validation->applyRule('Name', 'Username', $UsernameError);
            }

            // API
            // These options become available when POSTing as a user with Garden.Settings.Manage permissions

            if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                // Role change

                $RequestedRoles = $this->Form->getFormValue('RoleID', null);
                if (!is_null($RequestedRoles)) {
                    $RoleModel = new RoleModel();
                    $AllRoles = $RoleModel->getArray();

                    if (!is_array($RequestedRoles)) {
                        $RequestedRoles = is_numeric($RequestedRoles) ? array($RequestedRoles) : array();
                    }

                    $RequestedRoles = array_flip($RequestedRoles);
                    $UserNewRoles = array_intersect_key($AllRoles, $RequestedRoles);

                    // Put the data back into the forum object as if the user had submitted
                    // this themselves
                    $this->Form->setFormValue('RoleID', array_keys($UserNewRoles));

                    // Allow saving roles
                    $Settings['SaveRoles'] = true;

                }

                // Password change

                $NewPassword = $this->Form->getFormValue('Password', null);
                if (!is_null($NewPassword)) {
                }
            }

            // Allow mods to confirm emails
            $this->Form->removeFormValue('Confirmed');
            $Confirmation = $this->Form->getFormValue('ConfirmEmail', null);
            $Confirmation = !is_null($Confirmation) ? (bool)$Confirmation : null;

            if ($CanConfirmEmail && is_bool($Confirmation)) {
                $this->Form->setFormValue('Confirmed', (int)$Confirmation);
            }

            if ($this->Form->save($Settings) !== false) {
                $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
                $this->setData('Profile', $User);

                $this->informMessage(sprite('Check', 'InformSprite').t('Your changes have been saved.'), 'Dismissable AutoDismiss HasSprite');
            }

            if (!$CanEditEmail) {
                $this->Form->setFormValue("Email", $User['Email']);
            }

        }

        $this->title(t('Edit Profile'));
        $this->_setBreadcrumbs(t('Edit Profile'), '/profile/edit');
        $this->render();
    }

    /**
     * Default profile page.
     *
     * If current user's profile, get notifications. Otherwise show their activity (if available) or discussions.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $User Unique identifier, possible ID or username.
     * @param string $Username .
     * @param int $UserID Unique ID.
     */
    public function index($User = '', $Username = '', $UserID = '', $Page = false) {
        $this->editMode(false);
        $this->getUserInfo($User, $Username, $UserID);

        if ($this->User->Admin == 2 && $this->Head) {
            // Don't index internal accounts. This is in part to prevent vendors from getting endless Google alerts.
            $this->Head->addTag('meta', array('name' => 'robots', 'content' => 'noindex'));
            $this->Head->addTag('meta', array('name' => 'googlebot', 'content' => 'noindex'));
        }

        if (c('Garden.Profile.ShowActivities', true)) {
            return $this->activity($User, $Username, $UserID, $Page);
        } else {
            return Gdn::dispatcher()->dispatch(userUrl($this->User, '', 'discussions'));
        }
    }

    /**
     * Manage current user's invitations.
     *
     * @since 2.0.0
     * @access public
     */
    public function invitations($UserReference = '', $Username = '', $UserID = '') {
        $this->permission('Garden.SignIn.Allow');
        $this->editMode(false);
        $this->getUserInfo($UserReference, $Username, $UserID, $this->Form->authenticatedPostBack());
        $this->setTabView('Invitations');

        $InvitationModel = new InvitationModel();
        $this->Form->setModel($InvitationModel);
        if ($this->Form->authenticatedPostBack()) {
            // Remove insecure invitation data.
            $this->Form->removeFormValue(array('Name', 'DateExpires', 'RoleIDs'));

            // Send the invitation
            if ($this->Form->save($this->UserModel)) {
                $this->informMessage(t('Your invitation has been sent.'));
                $this->Form->clearInputs();
            }
        }
        $Session = Gdn::session();
        $this->InvitationCount = $this->UserModel->getInvitationCount($Session->UserID);
        $this->InvitationData = $InvitationModel->getByUserID($Session->UserID);

        $this->render();
    }

    /**
     * Set 'NoMobile' cookie for current user to prevent use of mobile theme.
     *
     * @param string $type The type of mobile device. This can be one of the following:
     * - desktop: Force the desktop theme.
     * - mobile: Force the mobile theme.
     * - tablet: Force the tablet theme (desktop).
     * - app: Force the app theme (app).
     * - 1: Unset the force cookie and use the user agent to determine the theme.
     */
    public function noMobile($type = 'desktop') {
        $type = strtolower($type);

        if ($type == '1') {
            Gdn_CookieIdentity::deleteCookie('X-UA-Device-Force');
            redirect("/", 302);
        }
        if (in_array($type, array('mobile', 'desktop', 'tablet', 'app'))) {
            $type = $type;
        } else {
            $type = 'desktop';
        }

        if ($type == '1') {
            // Allow mobile again
            Gdn_CookieIdentity::deleteCookie('VanillaNoMobile');
        } else {
            // Set 48-hour "no mobile" cookie
            $Expiration = time() + 172800;
            $Path = c('Garden.Cookie.Path');
            $Domain = c('Garden.Cookie.Domain');
            safeCookie('X-UA-Device-Force', $type, $Expiration, $Path, $Domain);
        }

        redirect("/", 302);
    }

    /**
     * Show notifications for current user.
     *
     * @since 2.0.0
     * @access public
     * @param int $Page Number to skip (paging).
     */
    public function notifications($Page = false) {
        $this->permission('Garden.SignIn.Allow');
        $this->editMode(false);

        list($Offset, $Limit) = offsetLimit($Page, 30);

        $this->getUserInfo();
        $this->_setBreadcrumbs(t('Notifications'), '/profile/notifications');

        $this->SetTabView('Notifications');
        $Session = Gdn::session();

        $this->ActivityModel = new ActivityModel();

        // Drop notification count back to zero.
        $this->ActivityModel->MarkRead($Session->UserID);

        // Get notifications data.
        $Activities = $this->ActivityModel->getNotifications($Session->UserID, $Offset, $Limit)->resultArray();
        $this->ActivityModel->joinComments($Activities);
        $this->setData('Activities', $Activities);
        unset($Activities);
        //$TotalRecords = $this->ActivityModel->GetCountNotifications($Session->UserID);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->Pager = $PagerFactory->GetPager('MorePager', $this);
        $this->Pager->MoreCode = 'More';
        $this->Pager->LessCode = 'Newer Notifications';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $Offset,
            $Limit,
            false,
            'profile/notifications/%1$s/'
        );
        // Deliver json data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            if ($Offset > 0) {
                $this->View = 'activities';
                $this->ControllerName = 'Activity';
            }
        }
        $this->render();
    }

    public function notificationsPopin() {
        $this->permission('Garden.SignIn.Allow');

        $Where = array(
            'NotifyUserID' => Gdn::session()->UserID,
            'DateUpdated >=' => Gdn_Format::toDateTime(strtotime('-2 weeks'))
        );

        $this->ActivityModel = new ActivityModel();
        $Activities = $this->ActivityModel->getWhere($Where, 0, 5)->resultArray();
        $this->setData('Activities', $Activities);
        $this->ActivityModel->markRead(Gdn::session()->UserID);

        $this->setData('Title', t('Notifications'));
        $this->render('Popin', 'Activity', 'Dashboard');
    }

    /**
     * Set new password for current user.
     *
     * @since 2.0.0
     * @access public
     */
    public function password() {
        $this->permission('Garden.SignIn.Allow');

        // Don't allow password editing if using SSO Connect ONLY.
        // This is for security. We encountered the case where a customer charges
        // for membership using their external application and use SSO to let
        // their customers into Vanilla. If you allow those people to change their
        // password in Vanilla, they will then be able to log into Vanilla using
        // Vanilla's login form regardless of the state of their membership in the
        // external app.
        if (c('Garden.Registration.Method') == 'Connect') {
            Gdn::dispatcher()->dispatch('DefaultPermission');
            exit();
        }

        Gdn::userModel()->addPasswordStrength($this);

        // Get user data and set up form
        $this->getUserInfo();

        $this->Form->setModel($this->UserModel);
        $this->addDefinition('Username', $this->User->Name);

        if ($this->Form->authenticatedPostBack() === true) {
            $this->Form->setFormValue('UserID', $this->User->UserID);
            $this->UserModel->defineSchema();
//         $this->UserModel->Validation->AddValidationField('OldPassword', $this->Form->formValues());

            // No password may have been set if they have only signed in with a connect plugin
            if (!$this->User->HashMethod || $this->User->HashMethod == "Vanilla") {
                $this->UserModel->Validation->applyRule('OldPassword', 'Required');
                $this->UserModel->Validation->applyRule('OldPassword', 'OldPassword', 'Your old password was incorrect.');
            }

            $this->UserModel->Validation->applyRule('Password', 'Required');
            $this->UserModel->Validation->applyRule('Password', 'Strength');
            $this->UserModel->Validation->applyRule('Password', 'Match');

            if ($this->Form->save()) {
                $this->informMessage(sprite('Check', 'InformSprite').t('Your password has been changed.'), 'Dismissable AutoDismiss HasSprite');
                $this->Form->clearInputs();
                Logger::event(
                    'password_change',
                    Logger::INFO,
                    '{InsertName} changed password.'
                );
            } else {
                Logger::event(
                    'password_change_failure',
                    Logger::INFO,
                    '{InsertName} failed to change password.',
                    array('Error' => $this->Form->errorString())
                );
            }
        }
        $this->title(t('Change My Password'));
        $this->_setBreadcrumbs(t('Change My Password'), '/profile/password');
        $this->render();
    }

    /**
     * Set user's photo (avatar).
     *
     * @since 2.0.0
     * @access public
     * @param mixed $UserReference Unique identifier, possible username or ID.
     * @param string $Username .
     */
    public function picture($UserReference = '', $Username = '', $UserID = '') {
        if (!c('Garden.Profile.EditPhotos', true)) {
            throw forbiddenException('@Editing user photos has been disabled.');
        }

        // Permission checks
        $this->permission(array('Garden.Profiles.Edit', 'Moderation.Profiles.Edit', 'Garden.ProfilePicture.Edit'), false);
        $Session = Gdn::session();
        if (!$Session->isValid()) {
            $this->Form->addError('You must be authenticated in order to use this form.');
        }

        // Check ability to manipulate image
        $ImageManipOk = false;
        if (function_exists('gd_info')) {
            $GdInfo = gd_info();
            $GdVersion = preg_replace('/[a-z ()]+/i', '', $GdInfo['GD Version']);
            if ($GdVersion < 2) {
                throw new Exception(sprintf(t("This installation of GD is too old (v%s). Vanilla requires at least version 2 or compatible."), $GdVersion));
            }
        } else {
            throw new Exception(sprintf(t("Unable to detect PHP GD installed on this system. Vanilla requires GD version 2 or better.")));
        }

        // Get user data & prep form.
        if ($this->Form->authenticatedPostBack() && $this->Form->getFormValue('UserID')) {
            $UserID = $this->Form->getFormValue('UserID');
        }
        $this->getUserInfo($UserReference, $Username, $UserID, true);

        $this->Form->setModel($this->UserModel);

        if ($this->Form->authenticatedPostBack() === true) {
            $this->Form->setFormValue('UserID', $this->User->UserID);
            $UploadImage = new Gdn_UploadImage();
            try {
                // Validate the upload
                $TmpImage = $UploadImage->ValidateUpload('Picture');

                // Generate the target image name.
                $TargetImage = $UploadImage->GenerateTargetName(PATH_UPLOADS, '', true);
                $Basename = pathinfo($TargetImage, PATHINFO_BASENAME);
                $Subdir = stringBeginsWith(dirname($TargetImage), PATH_UPLOADS.'/', false, true);

                // Delete any previously uploaded image.
                $UploadImage->delete(changeBasename($this->User->Photo, 'p%s'));

                // Save the uploaded image in profile size.
                $Props = $UploadImage->SaveImageAs(
                    $TmpImage,
                    "userpics/$Subdir/p$Basename",
                    c('Garden.Profile.MaxHeight', 1000),
                    c('Garden.Profile.MaxWidth', 250),
                    array('SaveGif' => c('Garden.Thumbnail.SaveGif'))
                );
                $UserPhoto = sprintf($Props['SaveFormat'], "userpics/$Subdir/$Basename");

//            // Save the uploaded image in preview size
//            $UploadImage->SaveImageAs(
//               $TmpImage,
//               'userpics/t'.$ImageBaseName,
//               Gdn::config('Garden.Preview.MaxHeight', 100),
//               Gdn::config('Garden.Preview.MaxWidth', 75)
//            );

                // Save the uploaded image in thumbnail size
                $ThumbSize = Gdn::config('Garden.Thumbnail.Size', 40);
                $UploadImage->saveImageAs(
                    $TmpImage,
                    "userpics/$Subdir/n$Basename",
                    $ThumbSize,
                    $ThumbSize,
                    array('Crop' => true, 'SaveGif' => c('Garden.Thumbnail.SaveGif'))
                );

            } catch (Exception $Ex) {
                // Throw the exception on API calls.
                if ($this->deliveryType() === DELIVERY_TYPE_DATA) {
                    throw $Ex;
                }
                $this->Form->addError($Ex);
            }
            // If there were no errors, associate the image with the user
            if ($this->Form->errorCount() == 0) {
                if (!$this->UserModel->save(array('UserID' => $this->User->UserID, 'Photo' => $UserPhoto), array('CheckExisting' => true))) {
                    $this->Form->setValidationResults($this->UserModel->validationResults());
                } else {
                    $this->User->Photo = $UserPhoto;
                    setValue('Photo', $this->Data['Profile'], $UserPhoto);
                    setValue('PhotoUrl', $this->Data['Profile'], Gdn_Upload::url(changeBasename($UserPhoto, 'n%s')));
                }
            }
            // If there were no problems, redirect back to the user account
            if ($this->Form->errorCount() == 0 && $this->deliveryType() !== DELIVERY_TYPE_DATA) {
                $this->informMessage(sprite('Check', 'InformSprite').t('Your changes have been saved.'), 'Dismissable AutoDismiss HasSprite');
                redirect($this->deliveryType() == DELIVERY_TYPE_VIEW ? userUrl($this->User) : userUrl($this->User, '', 'picture'));
            }
        }
        if ($this->Form->errorCount() > 0 && $this->deliveryType() !== DELIVERY_TYPE_DATA) {
            $this->deliveryType(DELIVERY_TYPE_ALL);
        }

        $this->title(t('Change Picture'));
        $this->_setBreadcrumbs(t('Change My Picture'), userUrl($this->User, '', 'picture'));
        $this->render();
    }

    /**
     * Gets or sets a user's preference. This method is meant for ajax calls.
     * @since 2.1
     * @param string $Key The name of the preference.
     */
    public function preference($Key = false) {
        $this->permission('Garden.SignIn.Allow');

        $this->Form->InputPrefix = '';

        if ($this->Form->authenticatedPostBack()) {
            $Data = $this->Form->formValues();
            Gdn::userModel()->SavePreference(Gdn::session()->UserID, $Data);
        } else {
            $User = Gdn::userModel()->getID(Gdn::session()->UserID, DATASET_TYPE_ARRAY);
            $Pref = valr($Key, $User['Preferences'], null);

            $this->setData($Key, $Pref);
        }

        $this->render('Blank', 'Utility');
    }

    /**
     * Edit user's preferences (mostly notification settings).
     *
     * @since 2.0.0
     * @access public
     * @param mixed $UserReference Unique identifier, possibly username or ID.
     * @param string $Username .
     * @param int $UserID Unique identifier.
     */
    public function preferences($UserReference = '', $Username = '', $UserID = '') {
        $this->addJsFile('profile.js');
        $Session = Gdn::session();
        $this->permission('Garden.SignIn.Allow');

        // Get user data
        $this->getUserInfo($UserReference, $Username, $UserID, true);
        $UserPrefs = Gdn_Format::unserialize($this->User->Preferences);
        if ($this->User->UserID != $Session->UserID) {
            $this->permission(array('Garden.Users.Edit', 'Moderation.Profiles.Edit'), false);
        }

        if (!is_array($UserPrefs)) {
            $UserPrefs = array();
        }
        $MetaPrefs = UserModel::GetMeta($this->User->UserID, 'Preferences.%', 'Preferences.');

        // Define the preferences to be managed
        $Notifications = array();

        if (c('Garden.Profile.ShowActivities', true)) {
            $Notifications = array(
                'Email.WallComment' => t('Notify me when people write on my wall.'),
                'Email.ActivityComment' => t('Notify me when people reply to my wall comments.'),
                'Popup.WallComment' => t('Notify me when people write on my wall.'),
                'Popup.ActivityComment' => t('Notify me when people reply to my wall comments.')
            );
        }

        $this->Preferences = array('Notifications' => $Notifications);

        // Allow email notification of applicants (if they have permission & are using approval registration)
        if (checkPermission('Garden.Users.Approve') && c('Garden.Registration.Method') == 'Approval') {
            $this->Preferences['Notifications']['Email.Applicant'] = array(t('NotifyApplicant', 'Notify me when anyone applies for membership.'), 'Meta');
        }

        $this->fireEvent('AfterPreferencesDefined');

        // Loop through the preferences looking for duplicates, and merge into a single row
        $this->PreferenceGroups = array();
        $this->PreferenceTypes = array();
        foreach ($this->Preferences as $PreferenceGroup => $Preferences) {
            $this->PreferenceGroups[$PreferenceGroup] = array();
            $this->PreferenceTypes[$PreferenceGroup] = array();
            foreach ($Preferences as $Name => $Description) {
                $Location = 'Prefs';
                if (is_array($Description)) {
                    list($Description, $Location) = $Description;
                }

                $NameParts = explode('.', $Name);
                $PrefType = val('0', $NameParts);
                $SubName = val('1', $NameParts);
                if ($SubName != false) {
                    // Save an array of all the different types for this group
                    if (!in_array($PrefType, $this->PreferenceTypes[$PreferenceGroup])) {
                        $this->PreferenceTypes[$PreferenceGroup][] = $PrefType;
                    }

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
                if (is_array($Desc)) {
                    list($Desc, $Location) = $Desc;
                }

                if ($Location == 'Meta') {
                    $CurrentPrefs[$Pref] = val($Pref, $MetaPrefs, false);
                } else {
                    $CurrentPrefs[$Pref] = val($Pref, $UserPrefs, c('Preferences.'.$Pref, '0'));
                }

                unset($MetaPrefs[$Pref]);
            }
        }
        $CurrentPrefs = array_merge($CurrentPrefs, $MetaPrefs);
        $CurrentPrefs = array_map('intval', $CurrentPrefs);
        $this->setData('Preferences', $CurrentPrefs);

        if (UserModel::noEmail()) {
            $this->PreferenceGroups = self::_removeEmailPreferences($this->PreferenceGroups);
            $this->PreferenceTypes = self::_removeEmailPreferences($this->PreferenceTypes);
            $this->setData('NoEmail', true);
        }

        $this->setData('PreferenceGroups', $this->PreferenceGroups);
        $this->setData('PreferenceTypes', $this->PreferenceTypes);
        $this->setData('PreferenceList', $this->Preferences);

        if ($this->Form->authenticatedPostBack()) {
            // Get, assign, and save the preferences.
            $NewMetaPrefs = array();
            foreach ($this->Preferences as $PrefGroup => $Prefs) {
                foreach ($Prefs as $Pref => $Desc) {
                    $Location = 'Prefs';
                    if (is_array($Desc)) {
                        list($Desc, $Location) = $Desc;
                    }

                    $Value = $this->Form->getValue($Pref, null);
                    if (is_null($Value)) {
                        continue;
                    }

                    if ($Location == 'Meta') {
                        $NewMetaPrefs[$Pref] = $Value ? $Value : null;
                        if ($Value) {
                            $UserPrefs[$Pref] = $Value; // dup for notifications code.
                        }
                    } else {
                        if (!$CurrentPrefs[$Pref] && !$Value) {
                            unset($UserPrefs[$Pref]); // save some space
                        } else {
                            $UserPrefs[$Pref] = $Value;
                        }
                    }
                }
            }

            $this->UserModel->savePreference($this->User->UserID, $UserPrefs);
            UserModel::setMeta($this->User->UserID, $NewMetaPrefs, 'Preferences.');

            $this->setData('Preferences', array_merge($this->data('Preferences', array()), $UserPrefs, $NewMetaPrefs));

            if (count($this->Form->errors() == 0)) {
                $this->informMessage(sprite('Check', 'InformSprite').t('Your preferences have been saved.'), 'Dismissable AutoDismiss HasSprite');
            }
        } else {
            $this->Form->setData($CurrentPrefs);
        }

        $this->title(t('Notification Preferences'));
        $this->_setBreadcrumbs($this->data('Title'), $this->canonicalUrl());
        $this->render();
    }

    protected static function _removeEmailPreferences($Data) {
        $Data = array_filter($Data, array('ProfileController', '_RemoveEmailFilter'));

        $Result = array();
        foreach ($Data as $K => $V) {
            if (is_array($V)) {
                $Result[$K] = self::_emoveEmailPreferences($V);
            } else {
                $Result[$K] = $V;
            }
        }

        return $Result;
    }

    protected static function _removeEmailFilter($Value) {
        if (is_string($Value) && strpos($Value, 'Email') !== false) {
            return false;
        }
        return true;
    }

    /**
     * Remove the user's photo.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $UserReference Unique identifier, possibly username or ID.
     * @param string $Username .
     * @param string $TransientKey Security token.
     */
    public function removePicture($UserReference = '', $Username = '', $TransientKey = '') {
        $this->permission('Garden.SignIn.Allow');
        $Session = Gdn::session();
        if (!$Session->isValid()) {
            $this->Form->addError('You must be authenticated in order to use this form.');
        }

        // Get user data & another permission check
        $this->getUserInfo($UserReference, $Username, '', true);
        $RedirectUrl = userUrl($this->User, '', 'picture');
        if ($Session->validateTransientKey($TransientKey) && is_object($this->User)) {
            $HasRemovePermission = checkPermission('Garden.Users.Edit') || checkPermission('Moderation.Profiles.Edit');
            if ($this->User->UserID == $Session->UserID || $HasRemovePermission) {
                // Do removal, set message, redirect
                Gdn::userModel()->removePicture($this->User->UserID);
                $this->informMessage(t('Your picture has been removed.'));
            }
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            redirect($RedirectUrl);
        } else {
            $this->ControllerName = 'Home';
            $this->View = 'FileNotFound';
            $this->RedirectUrl = url($RedirectUrl);
            $this->render();
        }
    }

    /**
     * Let user send an invitation.
     *
     * @since 2.0.0
     * @access public
     * @param int $InvitationID Unique identifier.
     */
    public function sendInvite($InvitationID = '') {
        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $this->permission('Garden.SignIn.Allow');
        $InvitationModel = new InvitationModel();
        $Session = Gdn::session();

        try {
            $Email = new Gdn_Email();
            $InvitationModel->send($InvitationID, $Email);
        } catch (Exception $ex) {
            $this->Form->addError(strip_tags($ex->getMessage()));
        }
        if ($this->Form->errorCount() == 0) {
            $this->informMessage(t('The invitation was sent successfully.'));
        }


        $this->View = 'Invitations';
        $this->invitations();
    }

    public function _setBreadcrumbs($Name = null, $Url = null) {
        // Add the root link.
        if (val('UserID', $this->User) == Gdn::session()->UserID) {
            $Root = array('Name' => t('Profile'), 'Url' => '/profile');
            $Breadcrumb = array('Name' => $Name, 'Url' => $Url);
        } else {
            $NameUnique = c('Garden.Registration.NameUnique');

            $Root = array('Name' => val('Name', $this->User), 'Url' => userUrl($this->User));
            $Breadcrumb = array('Name' => $Name, 'Url' => $Url.'/'.($NameUnique ? '' : val('UserID', $this->User).'/').rawurlencode(val('Name', $this->User)));
        }

        $this->Data['Breadcrumbs'][] = $Root;

        if ($Name && !stringBeginsWith($Root['Url'], $Url)) {
            $this->Data['Breadcrumbs'][] = array('Name' => $Name, 'Url' => $Url);
        }
    }

    /**
     * Set user's thumbnail (crop & center photo).
     *
     * @since 2.0.0
     * @access public
     * @param mixed $UserReference Unique identifier, possible username or ID.
     * @param string $Username .
     */
    public function thumbnail($UserReference = '', $Username = '') {
        if (!c('Garden.Profile.EditPhotos', true)) {
            throw forbiddenException('@Editing user photos has been disabled.');
        }

        // Initial permission checks (valid user)
        $this->permission('Garden.SignIn.Allow');
        $Session = Gdn::session();
        if (!$Session->isValid()) {
            $this->Form->addError('You must be authenticated in order to use this form.');
        }

        // Need some extra JS
        // jcrop update jan28, 2014 as jQuery upgrade to 1.10.2 no longer
        // supported browser()
        $this->addJsFile('jquery.jcrop.min.js');
        $this->addJsFile('profile.js');

        $this->getUserInfo($UserReference, $Username, '', true);

        // Permission check (correct user)
        if ($this->User->UserID != $Session->UserID && !checkPermission('Garden.Users.Edit') && !checkPermission('Moderation.Profiles.Edit')) {
            throw new Exception(t('You cannot edit the thumbnail of another member.'));
        }

        // Form prep
        $this->Form->setModel($this->UserModel);
        $this->Form->addHidden('UserID', $this->User->UserID);

        // Confirm we have a photo to manipulate
        if (!$this->User->Photo) {
            $this->Form->addError('You must first upload a picture before you can create a thumbnail.');
        }

        // Define the thumbnail size
        $this->ThumbSize = Gdn::config('Garden.Thumbnail.Size', 40);

        // Define the source (profile sized) picture & dimensions.
        $Basename = changeBasename($this->User->Photo, 'p%s');
        $Upload = new Gdn_UploadImage();
        $PhotoParsed = Gdn_Upload::Parse($Basename);
        $Source = $Upload->CopyLocal($Basename);

        if (!$Source) {
            $this->Form->addError('You cannot edit the thumbnail of an externally linked profile picture.');
        } else {
            $this->SourceSize = getimagesize($Source);
        }

        // We actually need to upload a new file to help with cdb ttls.
        $NewPhoto = $Upload->generateTargetName(
            'userpics',
            trim(pathinfo($this->User->Photo, PATHINFO_EXTENSION), '.'),
            true
        );

        // Add some more hidden form fields for jcrop
        $this->Form->addHidden('x', '0');
        $this->Form->addHidden('y', '0');
        $this->Form->addHidden('w', $this->ThumbSize);
        $this->Form->addHidden('h', $this->ThumbSize);
        $this->Form->addHidden('HeightSource', $this->SourceSize[1]);
        $this->Form->addHidden('WidthSource', $this->SourceSize[0]);
        $this->Form->addHidden('ThumbSize', $this->ThumbSize);
        if ($this->Form->authenticatedPostBack() === true) {
            try {
                // Get the dimensions from the form.
                Gdn_UploadImage::SaveImageAs(
                    $Source,
                    changeBasename($NewPhoto, 'n%s'),
                    $this->ThumbSize,
                    $this->ThumbSize,
                    array('Crop' => true, 'SourceX' => $this->Form->getValue('x'), 'SourceY' => $this->Form->getValue('y'), 'SourceWidth' => $this->Form->getValue('w'), 'SourceHeight' => $this->Form->getValue('h'))
                );

                // Save new profile picture.
                $Parsed = $Upload->SaveAs($Source, changeBasename($NewPhoto, 'p%s'));
                $UserPhoto = sprintf($Parsed['SaveFormat'], $NewPhoto);
                // Save the new photo info.
                Gdn::userModel()->setField($this->User->UserID, 'Photo', $UserPhoto);

                // Remove the old profile picture.
                @$Upload->delete($Basename);
            } catch (Exception $Ex) {
                $this->Form->addError($Ex);
            }
            // If there were no problems, redirect back to the user account
            if ($this->Form->errorCount() == 0) {
                redirect(userUrl($this->User, '', 'picture'));
                $this->informMessage(sprite('Check', 'InformSprite').t('Your changes have been saved.'), 'Dismissable AutoDismiss HasSprite');
            }
        }
        // Delete the source image if it is externally hosted.
        if ($PhotoParsed['Type']) {
            @unlink($Source);
        }

        $this->title(t('Edit My Thumbnail'));
        $this->_setBreadcrumbs(t('Edit My Thumbnail'), '/profile/thumbnail');
        $this->render();
    }

    /**
     * Revoke an invitation.
     *
     * @since 2.0.0
     * @param int $InvitationID Unique identifier.
     * @throws Exception Throws an exception when the invitation isn't found or the user doesn't have permission to delete it.
     */
    public function uninvite($InvitationID) {
        $this->permission('Garden.SignIn.Allow');

        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $InvitationModel = new InvitationModel();
        $Session = Gdn::session();
        try {
            $Valid = $InvitationModel->delete($InvitationID, $this->UserModel);
            if ($Valid) {
                $this->informMessage(t('The invitation was removed successfully.'));
                $this->jsonTarget(".js-invitation[data-id=\"{$InvitationID}\"]", '', 'SlideUp');
            }
        } catch (Exception $ex) {
            $this->Form->addError(strip_tags($ex->getMessage()));
        }

        if ($this->Form->errorCount() == 0) {
            $this->render('Blank', 'Utility');
        }
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
    public function addProfileTab($TabName, $TabUrl = '', $CssClass = '', $TabHtml = '') {
        if (!is_array($TabName)) {
            if ($TabHtml == '') {
                $TabHtml = $TabName;
            }

            if (!$CssClass && $TabUrl == Gdn::request()->path()) {
                $CssClass = 'Active';
            }

            $TabName = array($TabName => array('TabUrl' => $TabUrl, 'CssClass' => $CssClass, 'TabHtml' => $TabHtml));
        }

        foreach ($TabName as $Name => $TabInfo) {
            $Url = val('TabUrl', $TabInfo, '');
            if ($Url == '') {
                $TabInfo['TabUrl'] = userUrl($this->User, '', strtolower($Name));
            }

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
    public function addSideMenu($CurrentUrl = '') {
        if (!$this->User) {
            return;
        }

        // Make sure to add the "Edit Profile" buttons.
        $this->addModule('ProfileOptionsModule');

        // Show edit menu if in edit mode
        // Show profile pic & filter menu otherwise
        $SideMenu = new SideMenuModule($this);
        $this->EventArguments['SideMenu'] = &$SideMenu; // Doing this out here for backwards compatibility.
        if ($this->EditMode) {
            $this->addModule('UserBoxModule');
            $this->buildEditMenu($SideMenu, $CurrentUrl);
            $this->fireEvent('AfterAddSideMenu');
            $this->addModule($SideMenu, 'Panel');
        } else {
            // Make sure the userphoto module gets added to the page
            $this->addModule('UserPhotoModule');

            // And add the filter menu module
            $this->fireEvent('AfterAddSideMenu');
            $this->addModule('ProfileFilterModule');
        }
    }

    /**
     * @param SideMenuModule $Module
     * @param string $CurrentUrl
     */
    public function buildEditMenu(&$Module, $CurrentUrl = '') {
        if (!$this->User) {
            return;
        }

        $Module->HtmlId = 'UserOptions';
        $Module->AutoLinkGroups = false;
        $Session = Gdn::session();
        $ViewingUserID = $Session->UserID;
        $Module->addItem('Options', '', false, array('class' => 'SideMenu'));

        // Check that we have the necessary tools to allow image uploading
        $AllowImages = c('Garden.Profile.EditPhotos', true) && Gdn_UploadImage::canUploadImages();

        // Is the photo hosted remotely?
        $RemotePhoto = isUrl($this->User->Photo);

        if ($this->User->UserID != $ViewingUserID) {
            // Include user js files for people with edit users permissions
            if (checkPermission('Garden.Users.Edit') || checkPermission('Moderation.Profiles.Edit')) {
//              $this->addJsFile('jquery.gardenmorepager.js');
                $this->addJsFile('user.js');
            }
            $Module->addLink('Options', sprite('SpProfile').' '.t('Edit Profile'), userUrl($this->User, '', 'edit'), array('Garden.Users.Edit', 'Moderation.Profiles.Edit'), array('class' => 'Popup EditAccountLink'));
            $Module->addLink('Options', sprite('SpProfile').' '.t('Edit Account'), '/user/edit/'.$this->User->UserID, 'Garden.Users.Edit', array('class' => 'Popup EditAccountLink'));
            $Module->addLink('Options', sprite('SpDelete').' '.t('Delete Account'), '/user/delete/'.$this->User->UserID, 'Garden.Users.Delete', array('class' => 'Popup DeleteAccountLink'));

            if ($this->User->Photo != '' && $AllowImages) {
                $Module->addLink('Options', sprite('SpDelete').' '.t('Remove Picture'), combinePaths(array(userUrl($this->User, '', 'removepicture'), $Session->transientKey())), array('Garden.Users.Edit', 'Moderation.Profiles.Edit'), array('class' => 'RemovePictureLink'));
            }

            $Module->addLink('Options', sprite('SpPreferences').' '.t('Edit Preferences'), userUrl($this->User, '', 'preferences'), array('Garden.Users.Edit', 'Moderation.Profiles.Edit'), array('class' => 'Popup PreferencesLink'));

            // Add profile options for everyone
            $Module->addLink('Options', sprite('SpPicture').' '.t('Change Picture'), userUrl($this->User, '', 'picture'), array('Garden.Users.Edit', 'Moderation.Profiles.Edit'), array('class' => 'PictureLink'));
            if ($this->User->Photo != '' && $AllowImages && !$RemotePhoto) {
                $Module->addLink('Options', sprite('SpThumbnail').' '.t('Edit Thumbnail'), userUrl($this->User, '', 'thumbnail'), array('Garden.Users.Edit', 'Moderation.Profiles.Edit'), array('class' => 'ThumbnailLink'));
            }
        } else {
            if (hasEditProfile($this->User->UserID)) {
                $Module->addLink('Options', sprite('SpEdit').' '.t('Edit Profile'), '/profile/edit', false, array('class' => 'Popup EditAccountLink'));
            }

            // Add profile options for the profile owner
            // Don't allow account editing if it has been turned off.
            // Don't allow password editing if using SSO Connect ONLY.
            // This is for security. We encountered the case where a customer charges
            // for membership using their external application and use SSO to let
            // their customers into Vanilla. If you allow those people to change their
            // password in Vanilla, they will then be able to log into Vanilla using
            // Vanilla's login form regardless of the state of their membership in the
            // external app.
            if (c('Garden.UserAccount.AllowEdit') && c('Garden.Registration.Method') != 'Connect') {
                // No password may have been set if they have only signed in with a connect plugin
                $PasswordLabel = t('Change My Password');
                if ($this->User->HashMethod && $this->User->HashMethod != "Vanilla") {
                    $PasswordLabel = t('Set A Password');
                }
                $Module->addLink('Options', sprite('SpPassword').' '.$PasswordLabel, '/profile/password', false, array('class' => 'Popup PasswordLink'));
            }

            $Module->addLink('Options', sprite('SpPreferences').' '.t('Notification Preferences'), userUrl($this->User, '', 'preferences'), false, array('class' => 'Popup PreferencesLink'));
            if ($AllowImages) {
                $Module->addLink('Options', sprite('SpPicture').' '.t('Change My Picture'), '/profile/picture', array('Garden.Profiles.Edit', 'Garden.ProfilePicture.Edit'), array('class' => 'PictureLink'));
            }

            if ($this->User->Photo != '' && $AllowImages && !$RemotePhoto) {
                $Module->addLink('Options', sprite('SpThumbnail').' '.t('Edit My Thumbnail'), '/profile/thumbnail', array('Garden.Profiles.Edit', 'Garden.ProfilePicture.Edit'), array('class' => 'ThumbnailLink'));
            }
        }

        if ($this->User->UserID == $ViewingUserID || $Session->checkPermission('Garden.Users.Edit')) {
            $this->setData('Connections', array());
            $this->EventArguments['User'] = $this->User;
            $this->fireEvent('GetConnections');
            if (count($this->data('Connections')) > 0) {
                $Module->addLink('Options', sprite('SpConnection').' '.t('Social'), '/profile/connections', 'Garden.SignIn.Allow');
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
    public function buildProfile() {
        if (!is_object($this->User)) {
            throw new Exception(t('Cannot build profile information if user is not defined.'));
        }

        $Session = Gdn::session();
        if (strpos($this->CssClass, 'Profile') === false) {
            $this->CssClass .= ' Profile';
        }
        $this->title(Gdn_Format::text($this->User->Name));

        if ($this->_DeliveryType != DELIVERY_TYPE_VIEW) {
            // Javascript needed
            // see note above about jcrop
            $this->addJsFile('jquery.jcrop.min.js');
            $this->addJsFile('profile.js');
            $this->addJsFile('jquery.gardenmorepager.js');
            $this->addJsFile('activity.js');

            // Build activity URL
            $ActivityUrl = 'profile/activity/';
            if ($this->User->UserID != $Session->UserID) {
                $ActivityUrl = userUrl($this->User, '', 'activity');
            }

            // Show activity?
            if (c('Garden.Profile.ShowActivities', true)) {
                $this->addProfileTab(t('Activity'), $ActivityUrl, 'Activity', sprite('SpActivity').' '.t('Activity'));
            }

            // Show notifications?
            if ($this->User->UserID == $Session->UserID) {
                $Notifications = t('Notifications');
                $NotificationsHtml = sprite('SpNotifications').' '.$Notifications;
                $CountNotifications = $Session->User->CountNotifications;
                if (is_numeric($CountNotifications) && $CountNotifications > 0) {
                    $NotificationsHtml .= ' <span class="Aside"><span class="Count">'.$CountNotifications.'</span></span>';
                }

                $this->addProfileTab($Notifications, 'profile/notifications', 'Notifications', $NotificationsHtml);
            }

            // Show invitations?
            if (c('Garden.Registration.Method') == 'Invitation') {
                $this->addProfileTab(t('Invitations'), 'profile/invitations', 'InvitationsLink', sprite('SpInvitations').' '.t('Invitations'));
            }

            $this->fireEvent('AddProfileTabs');
        }

        return true;
    }

    /**
     * Render basic data about user.
     *
     * @since 2.0.?
     * @access public
     * @param int $UserID Unique ID.
     */
    public function get($UserID = false) {
        if (!$UserID) {
            $UserID = Gdn::session()->UserID;
        }

        if (($UserID != Gdn::session()->UserID || !Gdn::session()->UserID) && !checkPermission('Garden.Users.Edit')) {
            throw new Exception(t('You do not have permission to view other profiles.'), 401);
        }

        $UserModel = new UserModel();

        // Get the user.
        $User = $UserModel->getID($UserID, DATASET_TYPE_ARRAY);
        if (!$User) {
            throw notFoundException('User');
        }

        $PhotoUrl = $User['Photo'];
        if ($PhotoUrl && strpos($PhotoUrl, '//') == false) {
            $PhotoUrl = url('/uploads/'.changeBasename($PhotoUrl, 'n%s'), true);
        }
        $User['Photo'] = $PhotoUrl;

        // Remove unwanted fields.
        $this->Data = arrayTranslate($User, array('UserID', 'Name', 'Email', 'Photo'));

        $this->render();
    }

    /**
     * Retrieve the user to be manipulated. Defaults to current user.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $User Unique identifier, possibly username or ID.
     * @param string $Username .
     * @param int $UserID Unique ID.
     * @param bool $CheckPermissions Whether or not to check user permissions.
     * @return bool Always true.
     */
    public function getUserInfo($UserReference = '', $Username = '', $UserID = '', $CheckPermissions = false) {
        if ($this->_UserInfoRetrieved) {
            return;
        }

        if (!c('Garden.Profile.Public') && !Gdn::session()->isValid()) {
            throw permissionException();
        }

        // If a UserID was provided as a querystring parameter, use it over anything else:
        if ($UserID) {
            $UserReference = $UserID;
            $Username = 'Unknown'; // Fill this with a value so the $UserReference is assumed to be an integer/userid.
        }

        $this->Roles = array();
        if ($UserReference == '') {
            if ($Username) {
                $this->User = $this->UserModel->getByUsername($Username);
            } else {
                $this->User = $this->UserModel->getID(Gdn::session()->UserID);
            }
        } elseif (is_numeric($UserReference) && $Username != '') {
            $this->User = $this->UserModel->getID($UserReference);
        } else {
            $this->User = $this->UserModel->getByUsername($UserReference);
        }

        $this->fireEvent('UserLoaded');

        if ($this->User === false) {
            throw notFoundException('User');
        } elseif ($this->User->Deleted == 1) {
            redirect('dashboard/home/deleted');
        } else {
            $this->RoleData = $this->UserModel->getRoles($this->User->UserID);
            if ($this->RoleData !== false && $this->RoleData->numRows(DATASET_TYPE_ARRAY) > 0) {
                $this->Roles = array_column($this->RoleData->resultArray(), 'Name');
            }

            // Hide personal info roles
            if (!checkPermission('Garden.PersonalInfo.View')) {
                $this->Roles = array_filter($this->Roles, 'RoleModel::FilterPersonalInfo');
            }

            $this->setData('Profile', $this->User);
            $this->setData('UserRoles', $this->Roles);
            if ($CssClass = val('_CssClass', $this->User)) {
                $this->CssClass .= ' '.$CssClass;
            }
        }

        if ($CheckPermissions && Gdn::session()->UserID != $this->User->UserID) {
            $this->permission(array('Garden.Users.Edit', 'Moderation.Profiles.Edit'), false);
        }

        $this->addSideMenu();
        $this->_UserInfoRetrieved = true;
        return true;
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
    public function profileUrl($UserReference = null, $UserID = null) {
        if (!property_exists($this, 'User')) {
            $this->getUserInfo();
        }

        if ($UserReference === null) {
            $UserReference = $this->User->Name;
        }
        if ($UserID === null) {
            $UserID = $this->User->UserID;
        }

        $UserReferenceEnc = rawurlencode($UserReference);
        if ($UserReferenceEnc == $UserReference) {
            return $UserReferenceEnc;
        } else {
            return "$UserID/$UserReferenceEnc";
        }
    }

    /**
     *
     *
     * @param string|int|null $UserReference
     * @param int|null $UserID
     * @return string
     * @throws Exception
     */
    public function getProfileUrl($UserReference = null, $UserID = null) {
        if (!property_exists($this, 'User')) {
            $this->getUserInfo();
        }

        if ($UserReference === null) {
            $UserReference = $this->User->Name;
        }
        if ($UserID === null) {
            $UserID = $this->User->UserID;
        }

        $UserReferenceEnc = rawurlencode($UserReference);
        if ($UserReferenceEnc == $UserReference) {
            return $UserReferenceEnc;
        } else {
            return "$UserID/$UserReferenceEnc";
        }
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
    public function setTabView($CurrentTab, $View = '', $Controller = 'Profile', $Application = 'Dashboard') {
        $this->buildProfile();
        if ($View == '') {
            $View = $CurrentTab;
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_ALL && $this->SyndicationMethod == SYNDICATION_NONE) {
            $this->addDefinition('DefaultAbout', t('Write something about yourself...'));
            $this->View = 'index';
            $this->_TabView = $View;
            $this->_TabController = $Controller;
            $this->_TabApplication = $Application;
        } else {
            $this->View = $View;
            $this->ControllerName = $Controller;
            $this->ApplicationFolder = $Application;
        }
        $this->CurrentTab = t($CurrentTab);
        $this->_CurrentTab = $this->CurrentTab; // Backwards Compat
    }

    public function editMode($Switch) {

        $this->EditMode = $Switch;
        if (!$this->EditMode && strpos($this->CssClass, 'EditMode') !== false) {
            $this->CssClass = str_replace('EditMode', '', $this->CssClass);
        }

        if ($Switch) {
            Gdn_Theme::section('EditProfile');
        } else {
            Gdn_Theme::section('EditProfile', 'remove');
        }
    }

    /**
     * Fetch multiple users
     *
     * Note: API only
     * @param type $UserID
     */
    public function multi($UserID) {
        $this->permission('Garden.Settings.Manage');
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->deliveryType(DELIVERY_TYPE_DATA);

        // Get rid of Reactions busybody data
        unset($this->Data['Counts']);

        $UserID = (array)$UserID;
        $Users = Gdn::userModel()->getIDs($UserID);

        $AllowedFields = array('UserID', 'Name', 'Title', 'Location', 'About', 'Email', 'Gender', 'CountVisits', 'CountInvitations', 'CountNotifications', 'Admin', 'Verified', 'Banned', 'Deleted', 'CountDiscussions', 'CountComments', 'CountBookmarks', 'CountBadges', 'Points', 'Punished', 'RankID', 'PhotoUrl', 'Online', 'LastOnlineDate');
        $AllowedFields = array_fill_keys($AllowedFields, null);
        foreach ($Users as &$User) {
            $User = array_intersect_key($User, $AllowedFields);
        }
        $Users = array_values($Users);
        $this->setData('Users', $Users);

        $this->render();
    }
}
