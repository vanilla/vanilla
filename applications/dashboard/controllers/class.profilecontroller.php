<?php
/**
 * Manages individual user profiles.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /profile endpoint.
 */
class ProfileController extends Gdn_Controller {

    const AVATAR_FOLDER = 'userpics';

    /** @var array Models to automatically instantiate. */
    public $Uses = ['Form', 'UserModel'];

    /** @var object User data to use in building profile. */
    public $User;

    /** @var bool Can the current user edit the profile user's photo? */
    public $CanEditPhotos;

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
        $this->ProfileTabs = [];
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
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('global.js');
        $this->addJsFile('cropimage.js');
        $this->addJsFile('vendors/clipboard.min.js');

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addModule('GuestModule');
        parent::initialize();

        Gdn_Theme::section('Profile');

        if ($this->EditMode) {
            $this->CssClass .= 'EditMode';
        }

        /**
         * The default Cache-Control header does not include no-store, which can cause issues with outdated session
         * information (e.g. message button missing). The same check is performed here as in Gdn_Controller before the
         * Cache-Control header is added, but this value includes the no-store specifier.
         */
        if (Gdn::session()->isValid()) {
            $this->setHeader('Cache-Control', 'private, no-cache, no-store, max-age=0, must-revalidate');
        }

        $this->setData('Breadcrumbs', []);
        $this->CanEditPhotos = Gdn::session()->checkRankedPermission(c('Garden.Profile.EditPhotos', true)) || Gdn::session()->checkPermission('Garden.Users.Edit');
    }

    /**
     * Show activity feed for this user.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $userReference Unique identifier, possible ID or username.
     * @param string $username Username.
     * @param int $userID Unique ID.
     * @param int $offset How many to skip (for paging).
     */
    public function activity($userReference = '', $username = '', $userID = '', $page = '') {
        $this->permission('Garden.Profiles.View');
        $this->editMode(false);

        // Object setup
        $session = Gdn::session();
        $this->ActivityModel = new ActivityModel();

        // Calculate offset.
        list($offset, $limit) = offsetLimit($page, 30);

        // Get user, tab, and comment
        $this->getUserInfo($userReference, $username, $userID);
        $userID = $this->User->UserID;
        $username = $this->User->Name;

        $this->_setBreadcrumbs(t('Activity'), userUrl($this->User, '', 'activity'));

        $this->setTabView('Activity');
        $comment = $this->Form->getFormValue('Comment');

        // Load data to display
        $this->ProfileUserID = $this->User->UserID;
        $limit = 30;

        $notifyUserIDs = [ActivityModel::NOTIFY_PUBLIC];
        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $notifyUserIDs[] = ActivityModel::NOTIFY_MODS;
        }

        $activities = $this->ActivityModel->getWhere(
            ['ActivityUserID' => $userID, 'NotifyUserID' => $notifyUserIDs],
            '',
            '',
            $limit,
            $offset
        )->resultArray();
        $this->ActivityModel->joinComments($activities);
        $this->setData('Activities', $activities);
        if (count($activities) > 0) {
            $lastActivity = reset($activities);
            $lastModifiedDate = Gdn_Format::toTimestamp($this->User->DateUpdated);
            $lastActivityDate = Gdn_Format::toTimestamp($lastActivity['DateInserted']);
            if ($lastModifiedDate < $lastActivityDate) {
                $lastModifiedDate = $lastActivityDate;
            }

            // Make sure to only query this page if the user has no new activity since the requesting browser last saw it.
            $this->setLastModified($lastModifiedDate);
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
     * @param mixed $userID
     */
    public function clear($userID = '') {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        $userID = is_numeric($userID) ? $userID : 0;
        $session = Gdn::session();
        if ($userID != $session->UserID && !$session->checkPermission('Garden.Moderation.Manage')) {
            throw permissionException('Garden.Moderation.Manage');
        }

        if ($userID > 0) {
            $this->UserModel->saveAbout($userID, '');
        }

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            redirectTo('/profile');
        } else {
            $this->jsonTarget('#Status', '', 'Remove');
            $this->render('Blank', 'Utility');
        }
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
        $this->setData('Connections', []);
        $this->EventArguments['User'] = $this->User;
        $this->fireEvent('GetConnections');

        // Add some connection information.
        foreach ($this->Data['Connections'] as &$Row) {
            $Provider = val($Row['ProviderKey'], $Providers, []);

            touchValue('Connected', $Row, !is_null(val('UniqueID', $Provider, null)));
        }

        $this->canonicalUrl(userUrl($this->User, '', 'connections'));
        $this->title(t('Social'));
        require_once $this->fetchViewLocation('connection_functions');
        $this->render();
    }

    /**
     * Generic way to get count via UserModel->profileCount().
     *
     * @since 2.0.?
     * @access public
     * @param string $column Name of column to count for this user.
     * @param int $userID Defaults to current session.
     */
    public function count($column, $userID = false) {
        $column = 'Count'.ucfirst($column);
        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }

        if ($userID !== Gdn::session()->UserID) {
            $this->permission('Garden.Settings.Manage');
        }

        $count = $this->UserModel->profileCount($userID, $column);
        $this->setData($column, $count);
        $this->setData('_Value', $count);
        $this->setData('_CssClass', 'Count');
        $this->render('Value', 'Utility');
    }

    /**
     * Delete an invitation that has already been accepted.
     * @param int $invitationID
     * @throws Exception The inviation was not found or the user doesn't have permission to remove it.
     */
    public function deleteInvitation($invitationID) {
        $this->permission('Garden.SignIn.Allow');

        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $invitationModel = new InvitationModel();

        $result = $invitationModel->deleteID($invitationID);
        if ($result) {
            $this->informMessage(t('The invitation was removed successfully.'));
            $this->jsonTarget(".js-invitation[data-id=\"{$invitationID}\"]", '', 'SlideUp');
        } else {
            $this->informMessage(t('Unable to remove the invitation.'));
        }

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
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        $this->permission('Garden.SignIn.Allow');
        $this->getUserInfo($UserReference, $Username, '', true);

        // First try and delete the authentication the fast way.
        Gdn::sql()->delete(
            'UserAuthentication',
            ['UserID' => $this->User->UserID, 'ProviderKey' => $Provider]
        );

        // Delete the profile information.
        Gdn::userModel()->saveAttribute($this->User->UserID, $Provider, null);

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            redirectTo(userUrl($this->User, '', 'connections'));
        } else {
            // Grab all of the providers again.
            $PModel = new Gdn_AuthenticationProviderModel();
            $Providers = $PModel->getProviders();

            $this->setData('_Providers', $Providers);
            $this->setData('Connections', []);
            $this->fireEvent('GetConnections');

            // Send back the connection buttons.
            require_once $this->fetchViewLocation('connection_functions');

            foreach ($this->data('Connections') as $Key => $Row) {
                $Provider = val($Row['ProviderKey'], $Providers, []);
                touchValue('Connected', $Row, !is_null(val('UniqueID', $Provider, null)));

                ob_start();
                    writeConnection($Row);
                    $connection = ob_get_contents();
                ob_end_clean();
                $this->jsonTarget(
                    "#Provider_$Key",
                    $connection,
                    'ReplaceWith'
                );
            }

            $this->render('Blank', 'Utility', 'Dashboard');
        }
    }

    /**
     * Edit user account.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $userReference Username or User ID.
     */
    public function edit($userReference = '', $username = '', $userID = '') {
        $this->permission('Garden.SignIn.Allow');

        $this->getUserInfo($userReference, $username, $userID, true);
        $userID = valr('User.UserID', $this);
        $settings = [];

        // Set up form
        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        $this->Form->setModel(Gdn::userModel());
        $this->Form->setData($user);

        // Decide if they have ability to edit the username
        $canEditUsername = (bool)c("Garden.Profile.EditUsernames") || Gdn::session()->checkPermission('Garden.Users.Edit');
        $this->setData('_CanEditUsername', $canEditUsername);

        // Decide if they have ability to edit the email
        $emailEnabled = (bool)c('Garden.Profile.EditEmails', true) && !UserModel::noEmail();
        $canEditEmail = ($emailEnabled && $userID == Gdn::session()->UserID) || checkPermission('Garden.Users.Edit');
        $this->setData('_CanEditEmail', $canEditEmail);

        // Decide if they have ability to confirm users
        $confirmed = (bool)valr('User.Confirmed', $this);
        $canConfirmEmail = (UserModel::requireConfirmEmail() && checkPermission('Garden.Users.Edit'));
        $this->setData('_CanConfirmEmail', $canConfirmEmail);
        $this->setData('_EmailConfirmed', $confirmed);
        $this->Form->setValue('ConfirmEmail', (int)$confirmed);

        // Decide if we can *see* email
        $this->setData('_CanViewPersonalInfo', Gdn::session()->UserID == val('UserID', $user) || checkPermission('Garden.PersonalInfo.View') || checkPermission('Garden.Users.Edit'));

        // Define gender dropdown options
        $this->GenderOptions = [
            'u' => t('Unspecified'),
            'm' => t('Male'),
            'f' => t('Female')
        ];

        $this->fireEvent('BeforeEdit');

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack(true)) {
            $this->reauth();

            $this->Form->setFormValue('UserID', $userID);

            // This field cannot be updated from here.
            $this->Form->removeFormValue('Password');

            if (!$canEditUsername) {
                $this->Form->setFormValue("Name", $user['Name']);
            } else {
                $usernameError = t('UsernameError', 'Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long.');
                Gdn::userModel()->Validation->applyRule('Name', 'Username', $usernameError);
            }

            // API
            // These options become available when POSTing as a user with Garden.Settings.Manage permissions

            if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {

                // Role change

                $requestedRoles = $this->Form->getFormValue('RoleID', null);
                if (!is_null($requestedRoles)) {
                    $roleModel = new RoleModel();
                    $allRoles = $roleModel->getArray();

                    if (!is_array($requestedRoles)) {
                        $requestedRoles = is_numeric($requestedRoles) ? [$requestedRoles] : [];
                    }

                    $requestedRoles = array_flip($requestedRoles);
                    $userNewRoles = array_intersect_key($allRoles, $requestedRoles);

                    // Put the data back into the forum object as if the user had submitted
                    // this themselves
                    $this->Form->setFormValue('RoleID', array_keys($userNewRoles));

                    // Allow saving roles
                    $settings['SaveRoles'] = true;

                }

                // Password change

                $newPassword = $this->Form->getFormValue('Password', null);
                if (!is_null($newPassword)) {
                }
            }

            // Allow mods to confirm emails
            $this->Form->removeFormValue('Confirmed');
            $confirmation = $this->Form->getFormValue('ConfirmEmail', null);
            $confirmation = !is_null($confirmation) ? (bool)$confirmation : null;

            if ($canConfirmEmail && is_bool($confirmation)) {
                $this->Form->setFormValue('Confirmed', (int)$confirmation);
            }

            // Don't allow non-mods to set an explicit photo.
            if ($photo = $this->Form->getFormValue('Photo')) {
                if (!Gdn_Upload::isUploadUri($photo)) {
                    if (!checkPermission('Garden.Users.Edit')) {
                        $this->Form->removeFormValue('Photo');
                    } elseif (!filter_var($photo, FILTER_VALIDATE_URL)) {
                        $this->Form->addError('Invalid photo URL.');
                    }
                }
            }

            if ($this->Form->save($settings) !== false) {
                $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
                $this->setData('Profile', $user);

                $this->informMessage(sprite('Check', 'InformSprite').t('Your changes have been saved.'), 'Dismissable AutoDismiss HasSprite');
            }

            if (!$canEditEmail) {
                $this->Form->setFormValue("Email", $user['Email']);
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
     * @param mixed $user Unique identifier, possible ID or username.
     * @param string $username .
     * @param int $userID Unique ID.
     */
    public function index($user = '', $username = '', $userID = '', $page = false) {
        $this->editMode(false);
        $this->getUserInfo($user, $username, $userID);


        if ($this->User->Admin == 2 && $this->Head) {
            // Don't index internal accounts. This is in part to prevent vendors from getting endless Google alerts.
            $this->Head->addTag('meta', ['name' => 'robots', 'content' => 'noindex']);
            $this->Head->addTag('meta', ['name' => 'googlebot', 'content' => 'noindex']);
        }

        if (c('Garden.Profile.ShowActivities', true)) {
            return $this->activity($user, $username, $userID, $page);
        } elseif ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            redirectTo(userUrl($this->User, '', 'discussions'));
        }

        // Garden.Profile.ShowActivities is false and the user is expecting an xml or json response, so render blank.
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Manage current user's invitations.
     *
     * @since 2.0.0
     * @access public
     */
    public function invitations($userReference = '', $username = '', $userID = '') {
        $this->permission('Garden.SignIn.Allow');
        $this->editMode(false);
        $this->getUserInfo($userReference, $username, $userID, $this->Form->authenticatedPostBack());
        $this->setTabView('Invitations');

        $invitationModel = new InvitationModel();
        $this->Form->setModel($invitationModel);
        if ($this->Form->authenticatedPostBack()) {
            // Remove insecure invitation data.
            $this->Form->removeFormValue(['Name', 'DateExpires', 'RoleIDs']);

            // Send the invitation
            if ($this->Form->save($this->UserModel)) {
                $this->informMessage(t('Your invitation has been sent.'));
                $this->Form->clearInputs();
            }
        }
        $session = Gdn::session();
        $this->InvitationCount = $this->UserModel->getInvitationCount($session->UserID);
        $this->InvitationData = $invitationModel->getByUserID($session->UserID);

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
        // Only validate CSRF token for authenticated users because guests do not get the transient key cookie.
        if (Gdn::session()->isValid()) {
            $valid = Gdn::request()->isAuthenticatedPostBack(true);
        } else {
            $valid = Gdn::request()->isPostBack();
        }

        if (!$valid) {
            throw new Exception('Requires POST', 405);
        }

        $type = strtolower($type);

        if ($type == '1') {
            Gdn_CookieIdentity::deleteCookie('X-UA-Device-Force');
        } else {
            if (in_array($type, ['mobile', 'desktop', 'tablet', 'app'])) {
                $type = $type;
            } else {
                $type = 'desktop';
            }

            // Set 48-hour "no mobile" cookie
            $expiration = time() + 172800;
            $path = c('Garden.Cookie.Path');
            $domain = c('Garden.Cookie.Domain');
            safeCookie('X-UA-Device-Force', $type, $expiration, $path, $domain);
        }

        $this->setRedirectTo('/');
        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Show notifications for current user.
     *
     * @since 2.0.0
     * @access public
     * @param int $page Number to skip (paging).
     */
    public function notifications($page = false) {
        $this->permission('Garden.SignIn.Allow');
        $this->editMode(false);

        list($offset, $limit) = offsetLimit($page, 30);

        $this->getUserInfo();
        $this->_setBreadcrumbs(t('Notifications'), '/profile/notifications');

        $this->setTabView('Notifications');
        $session = Gdn::session();

        $this->ActivityModel = new ActivityModel();

        // Drop notification count back to zero.
        $this->ActivityModel->markRead($session->UserID);

        // Get notifications data.
        $activities = $this->ActivityModel->getNotifications($session->UserID, $offset, $limit)->resultArray();
        $this->ActivityModel->joinComments($activities);
        $this->setData('Activities', $activities);
        unset($activities);
        //$TotalRecords = $this->ActivityModel->getCountNotifications($Session->UserID);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->Pager = $pagerFactory->getPager('MorePager', $this);
        $this->Pager->MoreCode = 'More';
        $this->Pager->LessCode = 'Newer Notifications';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $offset,
            $limit,
            false,
            'profile/notifications/%1$s/'
        );
        // Deliver json data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            if ($offset > 0) {
                $this->View = 'activities';
                $this->ControllerName = 'Activity';
            }
        }
        $this->render();
    }

    public function notificationsPopin($transientKey = '') {
        $this->permission('Garden.SignIn.Allow');

        if (Gdn::session()->validateTransientKey($transientKey) !== true) {
            throw new Gdn_UserException(t('Invalid CSRF token.', 'Invalid CSRF token. Please try again.'), 403);
        }

        $where = [
            'NotifyUserID' => Gdn::session()->UserID,
            'DateUpdated >=' => Gdn_Format::toDateTime(strtotime('-2 weeks'))
        ];

        $this->ActivityModel = new ActivityModel();
        $activities = $this->ActivityModel->getWhere($where, '', '', 5, 0)->resultArray();
        $this->setData('Activities', $activities);
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
//         $this->UserModel->Validation->addValidationField('OldPassword', $this->Form->formValues());

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
                    ['Error' => $this->Form->errorString()]
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
     *
     * @param mixed $userReference Unique identifier, possible username or ID.
     * @param string $username The username.
     * @param string $userID The user's ID.
     *
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function picture($userReference = '', $username = '', $userID = '') {
        $this->addJsFile('profile.js');

        if (!$this->CanEditPhotos) {
            throw forbiddenException('@Editing user photos has been disabled.');
        }

        // Permission checks
        $this->permission(['Garden.Profiles.Edit', 'Moderation.Profiles.Edit', 'Garden.ProfilePicture.Edit'], false);
        $session = Gdn::session();
        if (!$session->isValid()) {
            $this->Form->addError('You must be authenticated in order to use this form.');
        }

        // Check ability to manipulate image
        if (function_exists('gd_info')) {
            $gdInfo = gd_info();
            $gdVersion = preg_replace('/[a-z ()]+/i', '', $gdInfo['GD Version']);
            if ($gdVersion < 2) {
                throw new Exception(sprintf(t("This installation of GD is too old (v%s). Vanilla requires at least version 2 or compatible."), $gdVersion));
            }
        } else {
            throw new Exception(sprintf(t("Unable to detect PHP GD installed on this system. Vanilla requires GD version 2 or better.")));
        }

        // Get user data & prep form.
        if ($this->Form->authenticatedPostBack() && $this->Form->getFormValue('UserID')) {
            $userID = $this->Form->getFormValue('UserID');
        }
        $this->getUserInfo($userReference, $username, $userID, true);

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $this->Form->setModel($configurationModel);
        $avatar = $this->User->Photo;
        if ($avatar === null) {
            $avatar = UserModel::getDefaultAvatarUrl();
        }

        $source = '';
        $crop = null;

        if ($this->isUploadedAvatar($avatar)) {
            // Get the image source so we can manipulate it in the crop module.
            $upload = new Gdn_UploadImage();
            $thumbnailSize = c('Garden.Thumbnail.Size');
            $basename = changeBasename($avatar, "p%s");
            $source = $upload->copyLocal($basename);

            // Set up cropping.
            $crop = new CropImageModule($this, $this->Form, $thumbnailSize, $thumbnailSize, $source);
            $crop->setExistingCropUrl(Gdn_UploadImage::url(changeBasename($avatar, "n%s")));
            $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($avatar, "p%s")));
            $this->setData('crop', $crop);
        } else {
            $this->setData('avatar', $avatar);
        }

        if (!$this->Form->authenticatedPostBack()) {
            $this->Form->setData($configurationModel->Data);
        } else if ($this->Form->save() !== false) {
            $upload = new Gdn_UploadImage();
            $newAvatar = false;
            if ($tmpAvatar = $upload->validateUpload('Avatar', false)) {
                // New upload
                $thumbOptions = ['Crop' => true, 'SaveGif' => c('Garden.Thumbnail.SaveGif')];
                $newAvatar = $this->saveAvatars($tmpAvatar, $thumbOptions, $upload);
            } else if ($avatar && $crop && $crop->isCropped()) {
                // New thumbnail
                $tmpAvatar = $source;
                $thumbOptions = ['Crop' => true,
                    'SourceX' => $crop->getCropXValue(),
                    'SourceY' => $crop->getCropYValue(),
                    'SourceWidth' => $crop->getCropWidth(),
                    'SourceHeight' => $crop->getCropHeight()];
                $newAvatar = $this->saveAvatars($tmpAvatar, $thumbOptions);
            }
            if ($this->Form->errorCount() == 0) {
                if ($newAvatar !== false) {
                    $thumbnailSize = c('Garden.Thumbnail.Size');
                    // Update crop properties.
                    $basename = changeBasename($newAvatar, "p%s");
                    $source = $upload->copyLocal($basename);
                    $crop = new CropImageModule($this, $this->Form, $thumbnailSize, $thumbnailSize, $source);
                    $crop->setSize($thumbnailSize, $thumbnailSize);
                    $crop->setExistingCropUrl(Gdn_UploadImage::url(changeBasename($newAvatar, "n%s")));
                    $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($newAvatar, "p%s")));
                    $this->setData('crop', $crop);
                }
            }
            if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
                $this->jsonTarget('', '', 'Refresh');

                $this->setRedirectTo(userUrl($this->User));
            }
            $this->informMessage(t("Your settings have been saved."));
        }

        if (val('SideMenuModule', val('Panel', val('Assets', $this)))) {
            /** @var SideMenuModule $sidemenu */
            $sidemenu = $this->Assets['Panel']['SideMenuModule'];
            $sidemenu->highlightRoute('/profile/picture');
        }

        $this->title(t('Change Picture'));
        $this->_setBreadcrumbs(t('Change My Picture'), userUrl($this->User, '', 'picture'));
        $this->render('picture', 'profile', 'dashboard');
    }


    /**
     * Deletes uploaded avatars in the profile size format.
     *
     * @param string $avatar The avatar to delete.
     */
    private function deleteAvatars($avatar = '') {
        if ($avatar && $this->isUploadedAvatar($avatar)) {
            $upload = new Gdn_Upload();
            $subdir = stringBeginsWith(dirname($avatar), PATH_UPLOADS.'/', false, true);
            $upload->delete($subdir.'/'.basename(changeBasename($avatar, 'p%s')));
        }
    }

    /**
     * Test whether a path is a full url, which gives us an indication whether it's an upload or not.
     *
     * @param string $avatar The path to the avatar image to test
     * @return bool Whether the avatar has been uploaded.
     */
    private function isUploadedAvatar($avatar) {
        return (!isUrl($avatar));
    }


    /**
     * Saves the avatar to /uploads in two sizes:
     *   p* : The profile-sized image, which is constrained by Garden.Profile.MaxWidth and Garden.Profile.MaxHeight.
     *   n* : The thumbnail-sized image, which is constrained and cropped according to Garden.Thumbnail.Size.
     * Also deletes the old avatars.
     *
     * @param string $source The path to the local copy of the image.
     * @param array $thumbOptions The options to save the thumbnail-sized avatar with.
     * @param Gdn_UploadImage|null $upload The upload object.
     * @return bool Whether the saves were successful.
     */
    private function saveAvatars($source, $thumbOptions, $upload = null) {
        try {
            $ext = '';
            if (!$upload) {
                $upload = new Gdn_UploadImage();
                $ext = 'jpg';
            }

            // Generate the target image name
            $targetImage = $upload->generateTargetName(PATH_UPLOADS, $ext, true);
            $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);
            $subdir = stringBeginsWith(dirname($targetImage), PATH_UPLOADS.'/', false, true);

            // Save the profile size image.
            $parts = Gdn_UploadImage::saveImageAs(
                $source,
                self::AVATAR_FOLDER."/$subdir/p$imageBaseName",
                c('Garden.Profile.MaxHeight'),
                c('Garden.Profile.MaxWidth'),
                ['SaveGif' => c('Garden.Thumbnail.SaveGif')]
            );

            $thumbnailSize = c('Garden.Thumbnail.Size');

            // Save the thumbnail size image.
            Gdn_UploadImage::saveImageAs(
                $source,
                self::AVATAR_FOLDER."/$subdir/n$imageBaseName",
                $thumbnailSize,
                $thumbnailSize,
                $thumbOptions
            );
        } catch (Exception $ex) {
            $this->Form->addError($ex);
            return false;
        }

        $bak = $this->User->Photo;

        $userPhoto = sprintf($parts['SaveFormat'], self::AVATAR_FOLDER."/$subdir/$imageBaseName");
        if (!$this->UserModel->save(['UserID' => $this->User->UserID, 'Photo' => $userPhoto], ['CheckExisting' => true])) {
            $this->Form->setValidationResults($this->UserModel->validationResults());
        } else {
            $this->User->Photo = $userPhoto;
        }

        $this->deleteAvatars($bak);

        return $userPhoto;
    }

    /**
     * Gets or sets a user's preference. This method is meant for ajax calls.
     * @since 2.1
     * @param string $key The name of the preference.
     */
    public function preference($key = false) {
        $this->permission('Garden.SignIn.Allow');

        if ($this->Form->authenticatedPostBack()) {
            $data = $this->Form->formValues();
            Gdn::userModel()->savePreference(Gdn::session()->UserID, $data);
        } else {
            $user = Gdn::userModel()->getID(Gdn::session()->UserID, DATASET_TYPE_ARRAY);
            $pref = valr($key, $user['Preferences'], null);

            $this->setData($key, $pref);
        }

        $this->render('Blank', 'Utility');
    }

    /**
     * Edit user's preferences (mostly notification settings).
     *
     * @since 2.0.0
     * @access public
     * @param mixed $userReference Unique identifier, possibly username or ID.
     * @param string $username .
     * @param int $userID Unique identifier.
     */
    public function preferences($userReference = '', $username = '', $userID = '') {
        $this->addJsFile('profile.js');
        $session = Gdn::session();
        $this->permission('Garden.SignIn.Allow');

        // Get user data
        $this->getUserInfo($userReference, $username, $userID, true);
        $userPrefs = dbdecode($this->User->Preferences);
        if ($this->User->UserID != $session->UserID) {
            $this->permission(['Garden.Users.Edit', 'Moderation.Profiles.Edit'], false);
        }

        if (!is_array($userPrefs)) {
            $userPrefs = [];
        }
        $metaPrefs = UserModel::getMeta($this->User->UserID, 'Preferences.%', 'Preferences.');

        // Define the preferences to be managed
        $notifications = [];

        if (c('Garden.Profile.ShowActivities', true)) {
            $notifications = [
                'Email.WallComment' => t('Notify me when people write on my wall.'),
                'Email.ActivityComment' => t('Notify me when people reply to my wall comments.'),
                'Popup.WallComment' => t('Notify me when people write on my wall.'),
                'Popup.ActivityComment' => t('Notify me when people reply to my wall comments.')
            ];
        }

        $this->Preferences = ['Notifications' => $notifications];

        // Allow email notification of applicants (if they have permission & are using approval registration)
        if (checkPermission('Garden.Users.Approve') && c('Garden.Registration.Method') == 'Approval') {
            $this->Preferences['Notifications']['Email.Applicant'] = [t('NotifyApplicant', 'Notify me when anyone applies for membership.'), 'Meta'];
        }

        $this->fireEvent('AfterPreferencesDefined');

        // Loop through the preferences looking for duplicates, and merge into a single row
        $this->PreferenceGroups = [];
        $this->PreferenceTypes = [];
        foreach ($this->Preferences as $preferenceGroup => $preferences) {
            $this->PreferenceGroups[$preferenceGroup] = [];
            $this->PreferenceTypes[$preferenceGroup] = [];
            foreach ($preferences as $name => $description) {
                $location = 'Prefs';
                if (is_array($description)) {
                    list($description, $location) = $description;
                }

                $nameParts = explode('.', $name);
                $prefType = val('0', $nameParts);
                $subName = val('1', $nameParts);
                if ($subName != false) {
                    // Save an array of all the different types for this group
                    if (!in_array($prefType, $this->PreferenceTypes[$preferenceGroup])) {
                        $this->PreferenceTypes[$preferenceGroup][] = $prefType;
                    }

                    // Store all the different subnames for the group
                    if (!array_key_exists($subName, $this->PreferenceGroups[$preferenceGroup])) {
                        $this->PreferenceGroups[$preferenceGroup][$subName] = [$name];
                    } else {
                        $this->PreferenceGroups[$preferenceGroup][$subName][] = $name;
                    }
                } else {
                    $this->PreferenceGroups[$preferenceGroup][$name] = [$name];
                }
            }
        }

        // Loop the preferences, setting defaults from the configuration.
        $currentPrefs = [];
        foreach ($this->Preferences as $prefGroup => $prefs) {
            foreach ($prefs as $pref => $desc) {
                $location = 'Prefs';
                if (is_array($desc)) {
                    list($desc, $location) = $desc;
                }

                if ($location == 'Meta') {
                    $currentPrefs[$pref] = val($pref, $metaPrefs, false);
                } else {
                    $currentPrefs[$pref] = val($pref, $userPrefs, c('Preferences.'.$pref, '0'));
                }

                unset($metaPrefs[$pref]);
            }
        }
        $currentPrefs = array_merge($currentPrefs, $metaPrefs);
        $currentPrefs = array_map('intval', $currentPrefs);
        $this->setData('Preferences', $currentPrefs);

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
            $newMetaPrefs = [];
            foreach ($this->Preferences as $prefGroup => $prefs) {
                foreach ($prefs as $pref => $desc) {
                    $location = 'Prefs';
                    if (is_array($desc)) {
                        list($desc, $location) = $desc;
                    }

                    $value = $this->Form->getValue($pref, null);
                    if (is_null($value)) {
                        continue;
                    }

                    if ($location == 'Meta') {
                        $newMetaPrefs[$pref] = $value ? $value : null;
                        if ($value) {
                            $userPrefs[$pref] = $value; // dup for notifications code.
                        }
                    } else {
                        if (!$currentPrefs[$pref] && !$value) {
                            unset($userPrefs[$pref]); // save some space
                        } else {
                            $userPrefs[$pref] = $value;
                        }
                    }
                }
            }

            $this->UserModel->savePreference($this->User->UserID, $userPrefs);
            UserModel::setMeta($this->User->UserID, $newMetaPrefs, 'Preferences.');

            $this->setData('Preferences', array_merge($this->data('Preferences', []), $userPrefs, $newMetaPrefs));

            if (count($this->Form->errors() == 0)) {
                $this->informMessage(sprite('Check', 'InformSprite').t('Your preferences have been saved.'), 'Dismissable AutoDismiss HasSprite');
            }
        } else {
            $this->Form->setData($currentPrefs);
        }

        $this->title(t('Notification Preferences'));
        $this->_setBreadcrumbs($this->data('Title'), $this->canonicalUrl());
        $this->render();
    }

    protected static function _removeEmailPreferences($data) {
        $data = array_filter($data, ['ProfileController', '_RemoveEmailFilter']);

        $result = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $result[$k] = self::_removeEmailPreferences($v);
            } else {
                $result[$k] = $v;
            }
        }

        return $result;
    }

    protected static function _removeEmailFilter($value) {
        if (is_string($value) && strpos($value, 'Email') !== false) {
            return false;
        }
        return true;
    }

    /**
     * Prompt a user to enter their password, then re-submit form. Used for reauthenticating for sensitive actions.
     */
    public function authenticate() {
        $this->permission('Garden.SignIn.Allow');

        // If users are registering with SSO, don't bother with this form.
        if (c('Garden.Registration.Method') == 'Connect') {
            Gdn::dispatcher()->dispatch('DefaultPermission');
            exit();
        }

        if ($this->Form->getFormValue('DoReauthenticate')) {
            $originalSubmission = $this->Form->getFormValue('OriginalSubmission');
            if ($this->Form->authenticatedPostBack()) {
                $this->Form->validateRule(
                    'AuthenticatePassword',
                    'ValidateRequired',
                    sprintf(t('ValidateRequired'), 'Password')
                );
                if ($this->Form->errorCount() === 0) {
                    $password = $this->Form->getFormValue('AuthenticatePassword');
                    $result = Gdn::userModel()->validateCredentials('', Gdn::session()->UserID, $password);
                    if ($result !== false) {
                        $now = time();
                        Gdn::authenticator()->identity()->setAuthTime($now);
                        $formData = json_decode($originalSubmission, true);
                        if (is_array($formData)) {
                            Gdn::request()->setRequestArguments(Gdn_Request::INPUT_POST, $formData);
                        }
                        Gdn::dispatcher()->dispatch();
                        exit();
                    } else {
                        $this->Form->addError(t('Invalid password.'), 'AuthenticatePassword');
                    }
                }
            }
        } else {
            $originalSubmission = json_encode(Gdn::request()->post());
        }

        $this->Form->addHidden('DoReauthenticate', 1);
        $this->Form->addHidden('OriginalSubmission', $originalSubmission);

        $this->getUserInfo();
        $this->title(t('Enter Your Password'));
        $this->render();
    }

    /**
     * Remove the user's photo.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $userReference Unique identifier, possibly username or ID.
     * @param string $username .
     * @param string $tk Security token.
     */
    public function removePicture($userReference = '', $username = '', $tk = '', $deliveryType = '') {
        $this->permission('Garden.SignIn.Allow');
        $session = Gdn::session();
        if (!$session->isValid()) {
            $this->Form->addError('You must be authenticated in order to use this form.');
        }

        // Get user data & another permission check.
        $this->getUserInfo($userReference, $username, '', true);

        if ($session->validateTransientKey($tk) && is_object($this->User)) {
            $hasRemovePermission = checkPermission('Garden.Users.Edit') || checkPermission('Moderation.Profiles.Edit');
            if ($this->User->UserID == $session->UserID || $hasRemovePermission) {
                // Do removal, set message, redirect
                Gdn::userModel()->removePicture($this->User->UserID);
                $this->informMessage(t('Your picture has been removed.'));
            }
        }

        if ($deliveryType === DELIVERY_TYPE_VIEW) {
            $redirectUrl = userUrl($this->User);
        } else {
            $redirectUrl = userUrl($this->User, '', 'picture');
        }
        redirectTo($redirectUrl);
    }

    /**
     * Let user send an invitation.
     *
     * @since 2.0.0
     * @access public
     * @param int $invitationID Unique identifier.
     */
    public function sendInvite($invitationID = '') {
        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $this->permission('Garden.SignIn.Allow');
        $invitationModel = new InvitationModel();
        $session = Gdn::session();

        try {
            $email = new Gdn_Email();
            $invitationModel->send($invitationID, $email);
        } catch (Exception $ex) {
            $this->Form->addError(strip_tags($ex->getMessage()));
        }
        if ($this->Form->errorCount() == 0) {
            $this->informMessage(t('The invitation was sent successfully.'));
        }


        $this->View = 'Invitations';
        $this->invitations();
    }

    public function _setBreadcrumbs($name = null, $url = null) {
        // Add the root link.
        if (val('UserID', $this->User) == Gdn::session()->UserID) {
            $root = ['Name' => t('Profile'), 'Url' => '/profile'];
            $breadcrumb = ['Name' => $name, 'Url' => $url];
        } else {
            $nameUnique = c('Garden.Registration.NameUnique');

            $root = ['Name' => val('Name', $this->User), 'Url' => userUrl($this->User)];
            $breadcrumb = ['Name' => $name, 'Url' => $url.'/'.($nameUnique ? '' : val('UserID', $this->User).'/').rawurlencode(val('Name', $this->User))];
        }

        $this->Data['Breadcrumbs'][] = $root;

        if ($name && !stringBeginsWith($root['Url'], $url)) {
            $this->Data['Breadcrumbs'][] = ['Name' => $name, 'Url' => $url];
        }
    }

    /**
     * Set user's thumbnail (crop & center photo).
     *
     * @since 2.0.0
     * @access public
     * @param mixed $userReference Unique identifier, possible username or ID.
     * @param string $username .
     */
    public function thumbnail($userReference = '', $username = '') {
        $this->picture($userReference, $username);
    }

    /**
     * Edit user's preferences (mostly notification settings).
     *
     * @param mixed $userReference Unique identifier, possibly username or ID.
     * @param string $username .
     * @param int $userID Unique identifier.
     */
    public function tokens($userReference = '', $username = '', $userID = '') {
        $this->addJsFile('profile.js');
        $this->permission('Garden.SignIn.Allow');

        // Get user data
        $this->getUserInfo($userReference, $username, $userID, true);

        /* @var TokensApiController $tokenApi */
        $tokenApi = Gdn::getContainer()->get(TokensApiController::class);
        $tokens = $tokenApi->index();

        $this->title(t('Personal Access Tokens'));
        $this->_setBreadcrumbs($this->data('Title'), $this->canonicalUrl());
        $this->setData('Tokens', $tokens);
        $this->render();
    }

    public function token($userReference = '', $username = '', $userID = '') {
        $this->addJsFile('profile.js');
        $this->permission('Garden.SignIn.Allow');

        // Get user data
        $this->getUserInfo($userReference, $username, $userID, true);

        /* @var TokensApiController $tokenApi */
        $tokenApi = Gdn::getContainer()->get(TokensApiController::class);

        if ($this->Form->authenticatedPostBack(true)) {
            try {
                $token = $tokenApi->post([
                    'name' => $this->Form->getFormValue('Name'),
                    'transientKey' => $this->Form->getFormValue('TransientKey')
                ]);

                $this->jsonTarget(".DataList-Tokens", $this->revealTokenRow($token), 'Prepend');

            } catch (\Garden\Schema\ValidationException $ex) {
                $this->Form->addError($ex);
            }
        }

        $this->title(t('Add Token'));
        $this->_setBreadcrumbs($this->data('Title'), $this->canonicalUrl());
        $this->render();
    }

    public function tokenReveal($accessTokenID) {
        $this->permission('Garden.SignIn.Allow');

        /* @var TokensApiController $tokenApi */
        $tokenApi = Gdn::getContainer()->get(TokensApiController::class);

        if ($this->Form->authenticatedPostBack(true)) {
            try {
                $token = $tokenApi->get($accessTokenID, [
                    'transientKey' => $this->Form->getFormValue('TransientKey')
                ]);

                $this->jsonTarget("#Token_{$token['accessTokenID']}", $this->revealTokenRow($token), 'ReplaceWith');


            } catch (\Garden\Schema\ValidationException $ex) {
                $this->Form->addError($ex);
            }
        }

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    public function tokenDelete($accessTokenID) {
        $this->permission('Garden.SignIn.Allow');

        /* @var TokensApiController $tokenApi */
        $tokenApi = Gdn::getContainer()->get(TokensApiController::class);

        if ($this->Form->authenticatedPostBack(true)) {
            try {
                $tokenApi->delete($accessTokenID);

                $this->jsonTarget("#Token_{$accessTokenID}", '', 'SlideUp');


            } catch (\Garden\Schema\ValidationException $ex) {
                $this->Form->addError($ex);
            }
        }

        $this->render('token-delete', 'Profile', 'Dashboard');
    }


    private function revealTokenRow($token) {
        $deleteUrl = url('/profile/tokenDelete?accessTokenID='.$token['accessTokenID']);
        $deleteStr = t('Delete');
        $tokenLabel = t('Copy To Clipboard');
        $copiedMessage = t('Copied to Clipboard!');

        return <<<EOT
<li id="Token_{$token['accessTokenID']}" class="Item Item-Token">{$token['accessToken']}<a href="javascript:void(0);" title="{$tokenLabel}" data-copymessage="{$copiedMessage}" data-clipboard-text="{$token['accessToken']}" class="OptionsLink OptionsLink-Clipboard js-copyToClipboard" style="margin-left: 5px; display: none;"><svg class="copyToClipboard-icon" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24"><title>{$tokenLabel}</title><path transform="translate(0 -2)" d="M17,12h4a1,1,0,0,1,1,1h0a1,1,0,0,1-1,1H17v2l-4-3,4-3Zm2-2H18V5H13.75V4.083a1.75,1.75,0,1,0-3.5,0V5H6V21H18V16h1v5a1,1,0,0,1-1,1H6a1,1,0,0,1-1-1V5A1,1,0,0,1,6,4H9.251a2.75,2.75,0,0,1,5.5,0H18a1,1,0,0,1,1,1ZM6,7V6H18V7ZM8,9.509A.461.461,0,0,1,8.389,9h5.692a.461.461,0,0,1,.389.509.461.461,0,0,1-.389.509H8.389A.461.461,0,0,1,8,9.509Zm3.261,2.243c.116,0,.209.228.209.509s-.093.51-.209.51H8.209c-.116,0-.209-.228-.209-.51s.093-.509.209-.509ZM12.2,14.5c.149,0,.269.227.269.509s-.12.509-.269.509H8.269c-.149,0-.269-.228-.269-.509s.12-.509.269-.509Zm2.82,3a.513.513,0,0,1,0,1.018H8.449a.513.513,0,0,1,0-1.018Z" style="fill: currentColor;"></path></svg></a><div class="Meta Options">
    <a href="$deleteUrl" class="OptionsLink Popup">{$deleteStr}</a>
</div>
</li>
EOT;
    }

    /**
     * Revoke an invitation.
     *
     * @since 2.0.0
     * @param int $invitationID Unique identifier.
     * @throws Exception Throws an exception when the invitation isn't found or the user doesn't have permission to delete it.
     */
    public function uninvite($invitationID) {
        $this->permission('Garden.SignIn.Allow');

        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $invitationModel = new InvitationModel();
        try {
            $valid = $invitationModel->deleteID($invitationID);
            if ($valid) {
                $this->informMessage(t('The invitation was removed successfully.'));
                $this->jsonTarget(".js-invitation[data-id=\"{$invitationID}\"]", '', 'SlideUp');
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
     * @param mixed $tabName Tab name (or array of tab names) to add to the profile tab collection.
     * @param string $tabUrl URL the tab should point to.
     * @param string $cssClass Class property to apply to tab.
     * @param string $tabHtml Overrides tab's HTML.
     */
    public function addProfileTab($tabName, $tabUrl = '', $cssClass = '', $tabHtml = '') {
        if (!is_array($tabName)) {
            if ($tabHtml == '') {
                $tabHtml = $tabName;
            }

            $tabName = [$tabName => ['TabUrl' => $tabUrl, 'CssClass' => $cssClass, 'TabHtml' => $tabHtml]];
        }

        foreach ($tabName as $name => $tabInfo) {
            $url = val('TabUrl', $tabInfo, '');
            if ($url == '') {
                $tabInfo['TabUrl'] = userUrl($this->User, '', strtolower($name));
            }

            $this->ProfileTabs[$name] = $tabInfo;
            $this->_ProfileTabs[$name] = $tabInfo; // Backwards Compatibility
        }
    }

    /**
     * Adds the option menu to the panel asset.
     *
     * @since 2.0.0
     * @access public
     * @param string $currentUrl Path to highlight.
     */
    public function addSideMenu($currentUrl = '') {
        if (!$this->User) {
            return;
        }

        // Make sure to add the "Edit Profile" buttons.
        $this->addModule('ProfileOptionsModule');

        // Show edit menu if in edit mode
        // Show profile pic & filter menu otherwise
        $sideMenu = new SideMenuModule($this);
        $this->EventArguments['SideMenu'] = &$sideMenu; // Doing this out here for backwards compatibility.
        if ($this->EditMode) {
            $this->addModule('UserBoxModule');
            $this->buildEditMenu($sideMenu, $currentUrl);
            $this->fireEvent('AfterAddSideMenu');
            $this->addModule($sideMenu, 'Panel');
        } else {
            // Make sure the userphoto module gets added to the page
            $this->addModule('UserPhotoModule');

            // And add the filter menu module
            $this->fireEvent('AfterAddSideMenu');
            $this->addModule('ProfileFilterModule');
        }
    }

    /**
     * @param SideMenuModule $module
     * @param string $currentUrl
     */
    public function buildEditMenu(&$module, $currentUrl = '') {
        if (!$this->User) {
            return;
        }

        $module->HtmlId = 'UserOptions';
        $module->AutoLinkGroups = false;
        $session = Gdn::session();
        $viewingUserID = $session->UserID;
        $module->addItem('Options', '', false, ['class' => 'SideMenu']);

        // Check that we have the necessary tools to allow image uploading
        $allowImages = $this->CanEditPhotos && Gdn_UploadImage::canUploadImages();

        // Is the photo hosted remotely?
        $remotePhoto = isUrl($this->User->Photo);

        if ($this->User->UserID != $viewingUserID) {
            // Include user js files for people with edit users permissions
            if (checkPermission('Garden.Users.Edit') || checkPermission('Moderation.Profiles.Edit')) {
//              $this->addJsFile('jquery.gardenmorepager.js');
                $this->addJsFile('user.js');
            }
            $module->addLink('Options', sprite('SpProfile').' '.t('Edit Profile'), userUrl($this->User, '', 'edit'), ['Garden.Users.Edit', 'Moderation.Profiles.Edit'], ['class' => 'Popup EditAccountLink']);
            $module->addLink('Options', sprite('SpProfile').' '.t('Edit Account'), '/user/edit/'.$this->User->UserID, 'Garden.Users.Edit', ['class' => 'Popup EditAccountLink']);
            $module->addLink('Options', sprite('SpDelete').' '.t('Delete Account'), '/user/delete/'.$this->User->UserID, 'Garden.Users.Delete', ['class' => 'Popup DeleteAccountLink']);
            $module->addLink('Options', sprite('SpPreferences').' '.t('Edit Preferences'), userUrl($this->User, '', 'preferences'), ['Garden.Users.Edit', 'Moderation.Profiles.Edit'], ['class' => 'Popup PreferencesLink']);

            // Add profile options for everyone
            $module->addLink('Options', sprite('SpPicture').' '.t('Change Picture'), userUrl($this->User, '', 'picture'), ['Garden.Users.Edit', 'Moderation.Profiles.Edit'], ['class' => 'PictureLink']);
            if ($this->User->Photo != '' && $allowImages && !$remotePhoto) {
                $module->addLink('Options', sprite('SpThumbnail').' '.t('Edit Thumbnail'), userUrl($this->User, '', 'thumbnail'), ['Garden.Users.Edit', 'Moderation.Profiles.Edit'], ['class' => 'ThumbnailLink']);
            }
        } else {
            if (hasEditProfile($this->User->UserID)) {
                $module->addLink('Options', sprite('SpEdit').' '.t('Edit Profile'), '/profile/edit', false, ['class' => 'Popup EditAccountLink']);
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
                $passwordLabel = t('Change My Password');
                if ($this->User->HashMethod && $this->User->HashMethod != "Vanilla") {
                    $passwordLabel = t('Set A Password');
                }
                $module->addLink('Options', sprite('SpPassword').' '.$passwordLabel, '/profile/password', false, ['class' => 'Popup PasswordLink']);
            }

            $module->addLink('Options', sprite('SpPreferences').' '.t('Notification Preferences'), userUrl($this->User, '', 'preferences'), false, ['class' => 'Popup PreferencesLink']);
            if ($allowImages) {
                $module->addLink('Options', sprite('SpPicture').' '.t('Change My Picture'), '/profile/picture', ['Garden.Profiles.Edit', 'Garden.ProfilePicture.Edit'], ['class' => 'PictureLink']);
            }
        }

        if ($this->User->UserID == $viewingUserID || $session->checkPermission('Garden.Users.Edit')) {
            $this->setData('Connections', []);
            $this->EventArguments['User'] = $this->User;
            $this->fireEvent('GetConnections');
            if (count($this->data('Connections')) > 0) {
                $module->addLink('Options', sprite('SpConnection').' '.t('Social'), '/profile/connections', 'Garden.SignIn.Allow', ['class' => 'link-social']);
            }
        }

        $module->addLink('Options', t('Access Tokens'), '/profile/tokens', 'Garden.Tokens.Add', ['class' => 'link-tokens']);
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

        $session = Gdn::session();
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
            $activityUrl = 'profile/activity/';
            if ($this->User->UserID != $session->UserID) {
                $activityUrl = userUrl($this->User, '', 'activity');
            }

            // Show activity?
            if (c('Garden.Profile.ShowActivities', true)) {
                $this->addProfileTab(t('Activity'), $activityUrl, 'Activity', sprite('SpActivity').' '.t('Activity'));
            }

            // Show notifications?
            if ($this->User->UserID == $session->UserID) {
                $notifications = t('Notifications');
                $notificationsHtml = sprite('SpNotifications').' '.$notifications;
                $countNotifications = $session->User->CountNotifications;
                if (is_numeric($countNotifications) && $countNotifications > 0) {
                    $notificationsHtml .= ' <span class="Aside"><span class="Count">'.$countNotifications.'</span></span>';
                }

                $this->addProfileTab($notifications, 'profile/notifications', 'Notifications', $notificationsHtml);
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
     * @param int $userID Unique ID.
     */
    public function get($userID = false) {
        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }

        if (($userID != Gdn::session()->UserID || !Gdn::session()->UserID) && !checkPermission('Garden.Users.Edit')) {
            throw new Exception(t('You do not have permission to view other profiles.'), 401);
        }

        $userModel = new UserModel();

        // Get the user.
        $user = $userModel->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw notFoundException('User');
        }

        $photoUrl = $user['Photo'];
        if ($photoUrl && strpos($photoUrl, '//') == false) {
            $photoUrl = url('/uploads/'.changeBasename($photoUrl, 'n%s'), true);
        }
        $user['Photo'] = $photoUrl;

        // Remove unwanted fields.
        $this->Data = arrayTranslate($user, ['UserID', 'Name', 'Email', 'Photo']);

        $this->render();
    }

    /**
     * Retrieve the user to be manipulated. Defaults to current user.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $User Unique identifier, possibly username or ID.
     * @param string $username .
     * @param int $userID Unique ID.
     * @param bool $checkPermissions Whether or not to check user permissions.
     * @return bool Always true.
     */
    public function getUserInfo($userReference = '', $username = '', $userID = '', $checkPermissions = false) {
        if ($this->_UserInfoRetrieved) {
            return;
        }

        if (!c('Garden.Profile.Public') && !Gdn::session()->isValid()) {
            throw permissionException();
        }

        // If a UserID was provided as a querystring parameter, use it over anything else:
        if ($userID) {
            $userReference = $userID;
            $username = 'Unknown'; // Fill this with a value so the $UserReference is assumed to be an integer/userid.
        }

        $this->Roles = [];
        if ($userReference == '') {
            if ($username) {
                $this->User = $this->UserModel->getByUsername($username);
            } else {
                $this->User = $this->UserModel->getID(Gdn::session()->UserID);
            }
        } elseif (is_numeric($userReference) && $username != '') {
            $this->User = $this->UserModel->getID($userReference);
        } else {
            $this->User = $this->UserModel->getByUsername($userReference);
        }

        $this->fireEvent('UserLoaded');

        if ($this->User === false) {
            throw notFoundException('User');
        } elseif ($this->User->Deleted == 1) {
            redirectTo('dashboard/home/deleted');
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
            if ($cssClass = val('_CssClass', $this->User)) {
                $this->CssClass .= ' '.$cssClass;
            }
        }

        if ($checkPermissions && Gdn::session()->UserID != $this->User->UserID) {
            $this->permission(['Garden.Users.Edit', 'Moderation.Profiles.Edit'], false);
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
     * @param mixed $userReference Unique identifier, possibly username or ID.
     * @param string $userID Unique ID.
     * @return string Relative URL path.
     */
    public function profileUrl($userReference = null, $userID = null) {
        if (!property_exists($this, 'User')) {
            $this->getUserInfo();
        }

        if ($userReference === null) {
            $userReference = $this->User->Name;
        }
        if ($userID === null) {
            $userID = $this->User->UserID;
        }

        $userReferenceEnc = rawurlencode($userReference);
        if ($userReferenceEnc == $userReference) {
            return $userReferenceEnc;
        } else {
            return "$userID/$userReferenceEnc";
        }
    }

    /**
     *
     *
     * @param string|int|null $userReference
     * @param int|null $userID
     * @return string
     * @throws Exception
     */
    public function getProfileUrl($userReference = null, $userID = null) {
        if (!property_exists($this, 'User')) {
            $this->getUserInfo();
        }

        if ($userReference === null) {
            $userReference = $this->User->Name;
        }
        if ($userID === null) {
            $userID = $this->User->UserID;
        }

        $userReferenceEnc = rawurlencode($userReference);
        if ($userReferenceEnc == $userReference) {
            return $userReferenceEnc;
        } else {
            return "$userID/$userReferenceEnc";
        }
    }

    /**
     * Define & select the current tab in the tab menu. Sets $this->_CurrentTab.
     *
     * @since 2.0.0
     * @access public
     * @param string $currentTab Name of tab to highlight.
     * @param string $view View name. Defaults to index.
     * @param string $controller Controller name. Defaults to Profile.
     * @param string $application Application name. Defaults to Dashboard.
     */
    public function setTabView($currentTab, $view = '', $controller = 'Profile', $application = 'Dashboard') {
        $this->buildProfile();
        if ($view == '') {
            $view = $currentTab;
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_ALL && $this->SyndicationMethod == SYNDICATION_NONE) {
            $this->addDefinition('DefaultAbout', t('Write something about yourself...'));
            $this->View = 'index';
            $this->_TabView = $view;
            $this->_TabController = $controller;
            $this->_TabApplication = $application;
        } else {
            $this->View = $view;
            $this->ControllerName = $controller;
            $this->ApplicationFolder = $application;
        }
        $this->CurrentTab = t($currentTab);
        $this->_CurrentTab = $this->CurrentTab; // Backwards Compat
    }

    public function editMode($switch) {

        $this->EditMode = $switch;
        if (!$this->EditMode && strpos($this->CssClass, 'EditMode') !== false) {
            $this->CssClass = str_replace('EditMode', '', $this->CssClass);
        }

        if ($switch) {
            Gdn_Theme::section('EditProfile');
        } else {
            Gdn_Theme::section('EditProfile', 'remove');
        }
    }

    /**
     * Fetch multiple users
     *
     * Note: API only
     * @param type $userID
     */
    public function multi($userID) {
        $this->permission('Garden.Settings.Manage');
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->deliveryType(DELIVERY_TYPE_DATA);

        // Get rid of Reactions busybody data
        unset($this->Data['Counts']);

        $userID = (array)$userID;
        $users = Gdn::userModel()->getIDs($userID);

        $allowedFields = ['UserID', 'Name', 'Title', 'Location', 'About', 'Email', 'Gender', 'CountVisits', 'CountInvitations', 'CountNotifications', 'Admin', 'Verified', 'Banned', 'Deleted', 'CountDiscussions', 'CountComments', 'CountBookmarks', 'CountBadges', 'Points', 'Punished', 'RankID', 'PhotoUrl', 'Online', 'LastOnlineDate'];
        $allowedFields = array_fill_keys($allowedFields, null);
        foreach ($users as &$user) {
            $user = array_intersect_key($user, $allowedFields);
        }
        $users = array_values($users);
        $this->setData('Users', $users);

        $this->render();
    }
}
