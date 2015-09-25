<?php
/**
 * Manage users.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /user endpoint.
 */
class UserController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = array('Database', 'Form');

    /** @var int The number of users when certain optimizations kick in. */
    public $UserThreshold = 10000;

    /** @var Gdn_Form */
    public $Form;

    /**
     * Highlight menu path. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        if ($this->Menu) {
            $this->Menu->highlightRoute('/dashboard/settings');
        }
        $this->fireEvent('Init');
    }

    /**
     * User management list.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $Keywords Term or array of terms to filter list of users.
     * @param int $Page Page number.
     * @param string $Order Sort order for list.
     */
    public function index($Keywords = '', $Page = '', $Order = '') {
        $this->permission(
            array(
                'Garden.Users.Add',
                'Garden.Users.Edit',
                'Garden.Users.Delete'
            ),
            '',
            false
        );

        // Page setup
        $this->addJsFile('jquery.gardenmorepager.js');
        $this->addJsFile('user.js');
        $this->title(t('Users'));
        $this->addSideMenu('dashboard/user');

        // Form setup
        $this->Form->Method = 'get';

        // Input Validation.
        list($Offset, $Limit) = offsetLimit($Page, PagerModule::$DefaultPageSize);
        if (!$Keywords) {
            $Keywords = $this->Form->getFormValue('Keywords');
            if ($Keywords) {
                $Offset = 0;
            }
        }

        if (!is_string($Keywords)) {
            $Keywords = '';
        }

        // Put the Keyword back in the form
        if ($Keywords) {
            $this->Form->setFormValue('Keywords', $Keywords);
        }

        $UserModel = new UserModel();
        //$Like = trim($Keywords) == '' ? FALSE : array('u.Name' => $Keywords, 'u.Email' => $Keywords);
        list($Offset, $Limit) = offsetLimit($Page, 30);

        $Filter = $this->_GetFilter();
        if ($Filter) {
            $Filter['Keywords'] = $Keywords;
        } else {
            $Filter = array('Keywords' => (string)$Keywords);
        }
        $Filter['Optimize'] = $this->PastUserThreshold();

        // Sorting
        if (in_array($Order, array('DateInserted', 'DateFirstVisit', 'DateLastActive'))) {
            $Order = 'u.'.$Order;
            $OrderDir = 'desc';
        } else {
            $Order = 'u.Name';
            $OrderDir = 'asc';
        }

        // Get user list
        $this->UserData = $UserModel->Search($Filter, $Order, $OrderDir, $Limit, $Offset);
        $this->setData('Users', $this->UserData);
        if ($this->PastUserThreshold()) {
            $this->setData('_CurrentRecords', $this->UserData->count());
        } else {
            $this->setData('RecordCount', $UserModel->SearchCount($Filter));
        }

        RoleModel::SetUserRoles($this->UserData->result());

        // Deliver json data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL && $this->_DeliveryMethod == DELIVERY_METHOD_XHTML) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'users';
        }

        $this->render();
    }

    /**
     * Create a user.
     *
     * @since 2.0.0
     * @access public
     */
    public function add() {
        $this->permission('Garden.Users.Add');

        // Page setup
        $this->addJsFile('user.js');
        $this->title(t('Add User'));
        $this->addSideMenu('dashboard/user');

        $RoleModel = new RoleModel();
        $AllRoles = $RoleModel->getArray();
        $RoleData = $RoleModel->GetAssignable();

        // By default, people with access here can freely assign all roles
        $this->RoleData = $RoleData;

        $UserModel = new UserModel();
        $this->User = false;

        // Set the model on the form.
        $this->Form->setModel($UserModel);

        try {
            // These are all the 'effective' roles for this add action. This list can
            // be trimmed down from the real list to allow subsets of roles to be edited.
            $this->EventArguments['RoleData'] = &$this->RoleData;

            $this->fireEvent("BeforeUserAdd");

            if ($this->Form->authenticatedPostBack()) {
                // These are the new roles the creating user wishes to apply to the target
                // user, adjusted for his ability to affect those roles
                $RequestedRoles = $this->Form->getFormValue('RoleID');

                if (!is_array($RequestedRoles)) {
                    $RequestedRoles = array();
                }
                $RequestedRoles = array_flip($RequestedRoles);
                $UserNewRoles = array_intersect_key($this->RoleData, $RequestedRoles);

                // Put the data back into the forum object as if the user had submitted
                // this themselves
                $this->Form->setFormValue('RoleID', array_keys($UserNewRoles));

                $NewUserID = $this->Form->save(array('SaveRoles' => true, 'NoConfirmEmail' => true));
                if ($NewUserID !== false) {
                    $Password = $this->Form->getValue('Password', '');
                    $UserModel->sendWelcomeEmail($NewUserID, $Password, 'Add');
                    $this->informMessage(t('The user has been created successfully'));
                    $this->RedirectUrl = url('dashboard/user');
                }

                $this->UserRoleData = $UserNewRoles;
            } else {
                // Set the default roles.
                $this->UserRoleData = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
            }

        } catch (Exception $Ex) {
            $this->Form->addError($Ex);
        }
        $this->render();
    }

    /**
     * Show how many applicants are in the queue.
     *
     * @since 2.0.0
     * @access public
     */
    public function applicantCount() {
        $this->permission('Garden.Users.Approve');
        $RoleModel = new RoleModel();
        $Count = $RoleModel->GetApplicantCount();
        if ($Count > 0) {
            echo '<span class="Alert">', $Count, '</span>';
        }
    }

    /**
     * Show applicants queue.
     *
     * @since 2.0.0
     * @access public
     */
    public function applicants() {
        $this->permission('Garden.Users.Approve');
        $this->addSideMenu('dashboard/user/applicants');
        $this->addJsFile('jquery.gardencheckcolumn.js');
        $this->title(t('Applicants'));

        $this->fireEvent('BeforeApplicants');

        if ($this->Form->authenticatedPostBack() === true) {
            $Action = $this->Form->getValue('Submit');
            $Applicants = $this->Form->getValue('Applicants');
            $ApplicantCount = is_array($Applicants) ? count($Applicants) : 0;
            if ($ApplicantCount > 0 && in_array($Action, array('Approve', 'Decline'))) {
                $Session = Gdn::session();
                for ($i = 0; $i < $ApplicantCount; ++$i) {
                    $this->handleApplicant($Action, $Applicants[$i]);
                }
            }
        }
        $UserModel = Gdn::userModel();
        $this->UserData = $UserModel->GetApplicants();
        $this->View = 'applicants';
        $this->render();
    }

    /**
     * Approve a user application.
     *
     * @since 2.0.0
     * @access public
     * @param int $UserID Unique ID.
     * @param string $TransientKey Security token.
     */
    public function approve($UserID = '', $TransientKey = '') {
        $this->permission('Garden.Users.Approve');
        $Session = Gdn::session();
        if ($Session->validateTransientKey($TransientKey)) {
            $Approved = $this->HandleApplicant('Approve', $UserID);
            if ($Approved) {
                $this->informMessage(t('Your changes have been saved.'));
            }
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            return $this->Form->errorCount() == 0 ? true : $this->Form->errors();
        } else {
            $this->applicants();
        }
    }

    public function authenticate() {
        if (!$this->Request->isPostBack()) {
            throw forbiddenException($this->Request->requestMethod());
        }

        $Args = array_change_key_case($this->Form->formValues());
        $UserModel = new UserModel();

        // Look up the user.
        $User = null;
        if ($Email = val('email', $Args)) {
            $User = $UserModel->getByEmail($Email);
        } elseif ($Name = val('name', $Args)) {
            $User = $UserModel->getByUsername($Name);
        } else {
            throw new Gdn_UserException("One of the following parameters required: Email, Name.", 400);
        }

        if (!$User) {
            throw notFoundException('User');
        }

        // Check the password.
        $PasswordHash = new Gdn_PasswordHash();
        $Password = val('password', $Args);
        try {
            $PasswordChecked = $PasswordHash->CheckPassword($Password, val('Password', $User), val('HashMethod', $User));

            // Rate limiting
            Gdn::userModel()->RateLimit($User, $PasswordChecked);

            if ($PasswordChecked) {
                $this->setData('User', arrayTranslate((array)$User, array('UserID', 'Name', 'Email', 'PhotoUrl')));

                if (val('session', $Args)) {
                    Gdn::session()->start($this->data('User.UserID'));
                    $this->setData('Cookie', array(
                        c('Garden.Cookie.Name') => $_COOKIE[C('Garden.Cookie.Name')]
                    ));
                }
            } else {
                throw new Exception(t('Invalid password.'), 401); // Can't be a user exception.
            }
        } catch (Gdn_UserException $Ex) {
            $this->Form->addError($Ex);
        }

        $this->render();
    }

    /**
     * Autocomplete a username.
     *
     * @since 2.0.0
     * @access public
     */
    public function autoComplete() {
        $this->deliveryType(DELIVERY_TYPE_NONE);
        $Q = getIncomingValue('q');
        $UserModel = new UserModel();
        $Data = $UserModel->getLike(array('u.Name' => $Q), 'u.Name', 'asc', 10, 0);
        foreach ($Data->result() as $User) {
            echo htmlspecialchars($User->Name).'|'.Gdn_Format::text($User->UserID)."\n";
        }
        $this->render();
    }

    /**
     * Ban a user and optionally delete their content.
     * @since 2.1
     * @param type $UserID
     */
    public function ban($UserID, $Unban = false) {
        $this->permission(array('Garden.Moderation.Manage', 'Garden.Users.Edit', 'Moderation.Users.Ban'), false);

        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
        if (!$User) {
            throw notFoundException($User);
        }

        $UserModel = Gdn::userModel();

        // Block banning the super admin or system accounts.
        $User = $UserModel->getID($UserID);
        if (val('Admin', $User) == 2) {
            throw forbiddenException("@You may not ban a system user.");
        } elseif (val('Admin', $User)) {
            throw forbiddenException("@You may not ban a super admin.");
        }

        // Is the user banned for other reasons?
        $this->setData('OtherReasons', BanModel::isBanned(val('Banned', $User, 0), ~BanModel::BAN_AUTOMATIC));


        if ($this->Form->authenticatedPostBack()) {
            if ($Unban) {
                $UserModel->unban($UserID, array('RestoreContent' => $this->Form->getFormValue('RestoreContent')));
            } else {
                if (!ValidateRequired($this->Form->getFormValue('Reason'))) {
                    $this->Form->addError('ValidateRequired', 'Reason');
                }
                if ($this->Form->getFormValue('Reason') == 'Other' && !ValidateRequired($this->Form->getFormValue('ReasonText'))) {
                    $this->Form->addError('ValidateRequired', 'Reason Text');
                }

                if ($this->Form->errorCount() == 0) {
                    if ($this->Form->getFormValue('Reason') == 'Other') {
                        $Reason = $this->Form->getFormValue('ReasonText');
                    } else {
                        $Reason = $this->Form->getFormValue('Reason');
                    }

                    // Just because we're banning doesn't mean we can nuke their content
                    $DeleteContent = (checkPermission('Garden.Moderation.Manage')) ? $this->Form->getFormValue('DeleteContent') : false;
                    $UserModel->ban($UserID, array('Reason' => $Reason, 'DeleteContent' => $DeleteContent));
                }
            }

            if ($this->Form->errorCount() == 0) {
                // Redirect after a successful save.
                if ($this->Request->get('Target')) {
                    $this->RedirectUrl = $this->Request->get('Target');
                } elseif ($this->deliveryType() == DELIVERY_TYPE_ALL) {
                    $this->RedirectUrl = url(userUrl($User));
                } else {
                    $this->jsonTarget('', '', 'Refresh');
                }
            }
        }

        // Permission flag for view
        $this->setData('_MayDeleteContent', checkPermission('Garden.Moderation.Manage'));

        $this->setData('User', $User);
        $this->addSideMenu();
        $this->title($Unban ? t('Unban User') : t('Ban User'));
        if ($Unban) {
            $this->View = 'Unban';
        }
        $this->render();
    }

    /**
     * Page thru user list.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $Keywords Term or list of terms to limit search.
     * @param int $Page Page number.
     * @param string $Order Sort order.
     */
    public function browse($Keywords = '', $Page = '', $Order = '') {
        $this->View = 'index';
        $this->Index($Keywords, $Page, $Order = '');
    }

    /**
     * Decline a user application.
     *
     * @since 2.0.0
     * @access public
     * @param int $UserID Unique ID.
     * @param string $TransientKey Security token.
     */
    public function decline($UserID = '', $TransientKey = '') {
        $this->permission('Garden.Users.Approve');
        $Session = Gdn::session();
        if ($Session->validateTransientKey($TransientKey)) {
            if ($this->handleApplicant('Decline', $UserID)) {
                $this->informMessage(t('Your changes have been saved.'));
            }
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            return $this->Form->errorCount() == 0 ? true : $this->Form->errors();
        } else {
            $this->applicants();
        }
    }

    /**
     * Delete a user account.
     *
     * @since 2.0.0
     * @access public
     * @param int $UserID Unique ID.
     * @param string $Method Type of deletion to do (delete, keep, or wipe).
     */
    public function delete($UserID = '', $Method = '') {
        $this->permission('Garden.Users.Delete');
        $Session = Gdn::session();
        if ($Session->User->UserID == $UserID) {
            trigger_error(errorMessage("You cannot delete the user you are logged in as.", $this->ClassName, 'FetchViewLocation'), E_USER_ERROR);
        }
        $this->addSideMenu('dashboard/user');
        $this->title(t('Delete User'));

        $RoleModel = new RoleModel();
        $AllRoles = $RoleModel->getArray();

        // By default, people with access here can freely assign all roles
        $this->RoleData = $AllRoles;

        $UserModel = new UserModel();
        $this->User = $UserModel->getID($UserID);

        try {
            $CanDelete = true;
            $this->EventArguments['CanDelete'] = &$CanDelete;
            $this->EventArguments['TargetUser'] = &$this->User;

            // These are all the 'effective' roles for this delete action. This list can
            // be trimmed down from the real list to allow subsets of roles to be
            // edited.
            $this->EventArguments['RoleData'] = &$this->RoleData;

            $UserRoleData = $UserModel->getRoles($UserID)->resultArray();
            $RoleIDs = array_column($UserRoleData, 'RoleID');
            $RoleNames = array_column($UserRoleData, 'Name');
            $this->UserRoleData = ArrayCombine($RoleIDs, $RoleNames);
            $this->EventArguments['UserRoleData'] = &$this->UserRoleData;

            $this->fireEvent("BeforeUserDelete");
            $this->setData('CanDelete', $CanDelete);

            $Method = in_array($Method, array('delete', 'keep', 'wipe')) ? $Method : '';
            $this->Method = $Method;
            if ($Method != '') {
                $this->View = 'deleteconfirm';
            }

            if ($this->Form->authenticatedPostBack() && $Method != '') {
                $UserModel->delete($UserID, array('DeleteMethod' => $Method));
                $this->View = 'deletecomplete';
            }

        } catch (Exception $Ex) {
            $this->Form->addError($Ex);
        }
        $this->render();
    }

    public function delete2() {
        $this->permission('Garden.Users.Delete');

        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        $this->Form->validateRule('UserID', 'ValidateRequired');
        $DeleteType = $this->Form->getFormValue('DeleteMethod');
        if (!in_array($DeleteType, array('delete', 'keep', 'wipe'))) {
            $this->Form->addError(t('DeleteMethod must be one of: delete, keep, wipe.'));
        }

        $UserID = $this->Form->getFormValue('UserID');

        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
        if ($UserID && !$User) {
            throw notFoundException('User');
        }

        if ($User['Admin'] == 2) {
            $this->Form->addError(t('You cannot delete a system-created user.'));
        } elseif ($User['Admin'])
            $this->Form->addError(t('You cannot delete a super-admin.'));

        if ($this->Form->errorCount() == 0) {
            Gdn::userModel()->delete($UserID, array(
                'DeleteMethod' => $this->Form->getFormValue('DeleteMethod'),
                'Log' => true));
            $this->setData('Result', sprintf(t('%s was deleted.'), $User['Name']));
        }
        $this->render('Blank', 'Utility');
    }

    /**
     *
     *
     * @param $UserID
     * @throws Exception
     */
    public function deleteContent($UserID) {
        $this->permission('Garden.Moderation.Manage');

        $User = Gdn::userModel()->getID($UserID);
        if (!$User) {
            throw notFoundException('User');
        }

        if ($this->Form->authenticatedPostBack()) {
            Gdn::userModel()->deleteContent($UserID, array('Log' => true));

            if ($this->Request->get('Target')) {
                $this->RedirectUrl = url($this->Request->get('Target'));
            } else {
                $this->RedirectUrl = url(userUrl($User));
            }
        } else {
            $this->setData('Title', t('Are you sure you want to do this?'));
        }

        $this->setData('User', $User);
        $this->render();
    }

    /**
     * Edit a user account.
     *
     * @since 2.0.0
     * @access public
     * @param int $UserID Unique ID.
     */
    public function edit($UserID) {
        $this->permission('Garden.Users.Edit');

        // Page setup
        $this->addJsFile('user.js');
        $this->title(t('Edit User'));
        $this->addSideMenu('dashboard/user');

        // Only admins can reassign roles
        $RoleModel = new RoleModel();
        $AllRoles = $RoleModel->getArray();
        $RoleData = $RoleModel->getAssignable();

        $UserModel = new UserModel();
        $User = $UserModel->getID($UserID, DATASET_TYPE_ARRAY);

        // Determine if username can be edited
        $CanEditUsername = (bool)c("Garden.Profile.EditUsernames") || Gdn::session()->checkPermission('Garden.Users.Edit');
        $this->setData('_CanEditUsername', $CanEditUsername);

        // Determine if emails can be edited
        $CanEditEmail = Gdn::session()->checkPermission('Garden.Users.Edit');
        $this->setData('_CanEditEmail', $CanEditEmail);

        // Decide if they have ability to confirm users
        $Confirmed = (bool)valr('Confirmed', $User);
        $CanConfirmEmail = (
            UserModel::RequireConfirmEmail() &&
            Gdn::session()->checkPermission('Garden.Users.Edit'));
        $this->setData('_CanConfirmEmail', $CanConfirmEmail);
        $this->setData('_EmailConfirmed', $Confirmed);
        $User['ConfirmEmail'] = (int)$Confirmed;

        // Determine whether user being edited is privileged (can escalate permissions)
        $UserModel = new UserModel();
        $EditingPrivilegedUser = $UserModel->checkPermission($User, 'Garden.Settings.Manage');

        // Determine our password reset options
        // Anyone with user editing my force reset over email
        $this->ResetOptions = array(
            0 => t('Keep current password.'),
            'Auto' => t('Force user to reset their password and send email notification.')
        );
        // Only admins may manually reset passwords for other admins
        if (checkPermission('Garden.Settings.Manage') || !$EditingPrivilegedUser) {
            $this->ResetOptions['Manual'] = t('Manually set user password. No email notification.');
        }

        // Set the model on the form.
        $this->Form->setModel($UserModel);

        // Make sure the form knows which item we are editing.
        $this->Form->addHidden('UserID', $UserID);

        try {
            $AllowEditing = true;
            $this->EventArguments['AllowEditing'] = &$AllowEditing;
            $this->EventArguments['TargetUser'] = &$User;

            // These are all the 'effective' roles for this edit action. This list can
            // be trimmed down from the real list to allow subsets of roles to be
            // edited.
            $this->EventArguments['RoleData'] = &$RoleData;

            $UserRoleData = $UserModel->getRoles($UserID)->resultArray();
            $RoleIDs = array_column($UserRoleData, 'RoleID');
            $RoleNames = array_column($UserRoleData, 'Name');
            $UserRoleData = arrayCombine($RoleIDs, $RoleNames);
            $this->EventArguments['UserRoleData'] = &$UserRoleData;

            $this->fireEvent("BeforeUserEdit");
            $this->setData('AllowEditing', $AllowEditing);

            $this->Form->setData($User);
            if ($this->Form->authenticatedPostBack()) {
                if (!$CanEditUsername) {
                    $this->Form->setFormValue("Name", $User['Name']);
                }

                // Allow mods to confirm/unconfirm emails
                $this->Form->removeFormValue('Confirmed');
                $Confirmation = $this->Form->getFormValue('ConfirmEmail', null);
                $Confirmation = !is_null($Confirmation) ? (bool)$Confirmation : null;

                if ($CanConfirmEmail && is_bool($Confirmation)) {
                    $this->Form->setFormValue('Confirmed', (int)$Confirmation);
                }

                $ResetPassword = $this->Form->getValue('ResetPassword', false);

                // If we're an admin or this isn't a privileged user, allow manual setting of password
                $AllowManualReset = (checkPermission('Garden.Settings.Manage') || !$EditingPrivilegedUser);
                if ($ResetPassword == 'Manual' && $AllowManualReset) {
                    // If a new password was specified, add it to the form's collection
                    $NewPassword = $this->Form->getValue('NewPassword', '');
                    $this->Form->setFormValue('Password', $NewPassword);
                }

                // Role changes

                // These are the new roles the editing user wishes to apply to the target
                // user, adjusted for his ability to affect those roles
                $RequestedRoles = $this->Form->getFormValue('RoleID');

                if (!is_array($RequestedRoles)) {
                    $RequestedRoles = array();
                }
                $RequestedRoles = array_flip($RequestedRoles);
                $UserNewRoles = array_intersect_key($RoleData, $RequestedRoles);

                // These roles will stay turned on regardless of the form submission contents
                // because the editing user does not have permission to modify them
                $ImmutableRoles = array_diff_key($AllRoles, $RoleData);
                $UserImmutableRoles = array_intersect_key($ImmutableRoles, $UserRoleData);

                // Apply immutable roles
                foreach ($UserImmutableRoles as $IMRoleID => $IMRoleName) {
                    $UserNewRoles[$IMRoleID] = $IMRoleName;
                }

                // Put the data back into the forum object as if the user had submitted
                // this themselves
                $this->Form->setFormValue('RoleID', array_keys($UserNewRoles));

                if ($this->Form->save(array('SaveRoles' => true)) !== false) {
                    if ($this->Form->getValue('ResetPassword', '') == 'Auto') {
                        $UserModel->PasswordRequest($User['Email']);
                        $UserModel->setField($UserID, 'HashMethod', 'Reset');
                    }

                    $this->informMessage(t('Your changes have been saved.'));
                }

                $UserRoleData = $UserNewRoles;
            }
        } catch (Exception $Ex) {
            $this->Form->addError($Ex);
        }

        $this->setData('User', $User);
        $this->setData('Roles', $RoleData);
        $this->setData('UserRoles', $UserRoleData);

        $this->render();
    }

    /**
     * Determine whether user can register with this email address.
     *
     * @since 2.0.0
     * @access public
     * @param string $Email Email address to be checked.
     */
    public function emailAvailable($Email = '') {
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;
        $Available = true;

        if (c('Garden.Registration.EmailUnique', true) && $Email != '') {
            $UserModel = Gdn::userModel();
            if ($UserModel->getByEmail($Email)) {
                $Available = false;
            }
        }
        if (!$Available) {
            $this->Form->addError(sprintf(t('%s unavailable'), t('Email')));
        }

        $this->render();
    }

    /**
     * Get filter from current request.
     *
     * @since 2.0.0
     * @access protected
     */
    protected function _getFilter() {
        $Filter = $this->Request->get('Filter');
        if ($Filter) {
            $Parts = explode(' ', $Filter, 3);
            if (count($Parts) < 2) {
                return false;
            }

            $Field = $Parts[0];
            if (count($Parts) == 2) {
                $Op = '=';
                $FilterValue = $Parts[1];
            } else {
                $Op = $Parts[1];
                if (!in_array($Op, array('=', 'like'))) {
                    $Op = '=';
                }
                $FilterValue = $Parts[2];
            }

            if (strpos($Field, '.') !== false) {
                $Field = array_pop(explode('.', $Field));
            }

            if (!in_array($Field, array('Name', 'Email', 'LastIPAddress', 'InsertIPAddress', 'RankID', 'DateFirstVisit', 'DateLastActive'))) {
                return false;
            }

            return array("$Field $Op" => $FilterValue);
        }
        return false;
    }

    /**
     * Handle a user application.
     *
     * @since 2.0.0
     * @access private
     * @see self::Decline, self::Approve
     * @param string $Action Approve or Decline.
     * @param int $UserID Unique ID.
     */
    private function handleApplicant($Action, $UserID) {
        $this->permission('Garden.Users.Approve');

        //$this->_DeliveryType = DELIVERY_TYPE_BOOL;
        if (!in_array($Action, array('Approve', 'Decline')) || !is_numeric($UserID)) {
            $this->Form->addError('ErrorInput');
            $Result = false;
        } else {
            $Session = Gdn::session();
            $UserModel = new UserModel();
            if (is_numeric($UserID)) {
                try {
                    $this->EventArguments['UserID'] = $UserID;
                    $this->fireEvent("Before{$Action}User");

                    $Email = new Gdn_Email();
                    $Result = $UserModel->$Action($UserID, $Email);

                    // Re-calculate applicant count
                    $RoleModel = new RoleModel();
                    $RoleModel->GetApplicantCount(true);

                    $this->fireEvent("After{$Action}User");
                } catch (Exception $ex) {
                    $Result = false;
                    $this->Form->addError(strip_tags($ex->getMessage()));
                }
            }
        }
    }

    /**
     *
     *
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function merge() {
        $this->permission('Garden.Settings.Manage');

        // This must be a postback.
        if (!$this->Request->isAuthenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $Validation = new Gdn_Validation();
        $Validation->applyRule('OldUserID', 'ValidateRequired');
        $Validation->applyRule('NewUserID', 'ValidateRequired');
        if ($Validation->validate($this->Request->Post())) {
            $Result = Gdn::userModel()->merge(
                $this->Request->post('OldUserID'),
                $this->Request->post('NewUserID')
            );
            $this->setData($Result);
        } else {
            $this->Form->setValidationResults($Validation->results());
        }
        $this->render('Blank', 'Utility');
    }

    /**
     * Build URL to order users by value passed.
     *
     * @since 2.0.0
     * @access protected
     * @param string $Field Column to order users by.
     * @return string URL of user list with orderby query appended.
     */
    protected function _orderUrl($Field) {
        $Get = Gdn::request()->get();
        $Get['order'] = $Field;
        return '/dashboard/user?'.http_build_query($Get);
    }

    /**
     * Whether or not we are past the user threshold.
     */
    protected function pastUserThreshold() {
        $px = Gdn::database()->DatabasePrefix;
        $countEstimate = Gdn::database()->query("show table status like '{$px}User'")->value('Rows', 0);
        return $countEstimate > $this->UserThreshold;
    }

    /**
     * Convenience function for listing users. At time of this writing, it is
     * being used by wordpress widgets to display recently active users.
     *
     * @since 2.0.?
     * @access public
     * @param string $SortField The field to sort users with. Defaults to DateLastActive. Other options are: DateInserted, Name.
     * @param string $SortDirection The direction to sort the users.
     * @param int $Limit The number of users to show.
     * @param int $Offset The offset to start listing users at.
     */
    public function summary($SortField = 'DateLastActive', $SortDirection = 'desc', $Limit = 30, $Offset = 0) {
        // Added permission check Oct 2014 - Guest now requires Profiles.View for WP widget to work.
        $this->permission('Garden.Profiles.View');
        $this->title(t('User Summary'));

        // Input validation
        $SortField = !in_array($SortField, array('DateLastActive', 'DateInserted', 'Name')) ? 'DateLastActive' : $SortField;
        $SortDirection = $SortDirection == 'asc' ? 'asc' : 'desc';
        $Limit = is_numeric($Limit) && $Limit < 100 && $Limit > 0 ? $Limit : 30;
        $Offset = is_numeric($Offset) ? $Offset : 0;

        // Get user list
        $UserModel = new UserModel();
        $UserData = $UserModel->getSummary('u.'.$SortField, $SortDirection, $Limit, $Offset);
        $this->setData('UserData', $UserData);

        $this->MasterView = 'empty';
        $this->render('filenotfound', 'home');
    }

    /**
     *
     *
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function save() {
        $this->permission('Garden.Users.Edit');
        if (!Gdn::request()->isPostBack()) {
            throw new Exception('Requires POST', 405);
        }

        $Form = new Gdn_Form();

        if ($SSOString = $Form->getFormValue('SSOString')) {
            $Parts = explode(' ', $SSOString);
            $String = $Parts[0];
            $Data = json_decode(base64_decode($String), true);
            $User = arrayTranslate($Data, array('name' => 'Name', 'email' => 'Email', 'photourl' => 'Photo', 'client_id' => 'ClientID', 'uniqueid' => 'UniqueID'));
        } else {
            $User = $Form->formValues();
        }

        if (!isset($User['UserID']) && isset($User['UniqueID'])) {
            // Try and find the user based on SSO.
            $Auth = Gdn::userModel()->getAuthentication($User['UniqueID'], $User['ClientID']);
            if ($Auth) {
                $User['UserID'] = $Auth['UserID'];
            }
        }

        if (!isset($User['UserID'])) {
            // Add some default values to make saving easier.
            if (!isset($User['RoleID'])) {
                $DefaultRoles = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
                $User['RoleID'] = $DefaultRoles;
            } elseif (is_numeric($User['RoleID'])) {
                // UserModel->save() demands an array for RoleID.
                $User['RoleID'] = array($User['RoleID']);
            }

            if (!isset($User['Password'])) {
                $User['Password'] = md5(microtime());
                $User['HashMethod'] = 'Random';
            }
        }

        $UserID = Gdn::userModel()->save($User, array('SaveRoles' => isset($User['RoleID']), 'NoConfirmEmail' => true));
        if ($UserID) {
            if (!isset($User['UserID'])) {
                $User['UserID'] = $UserID;
            }

            if (isset($User['ClientID']) && isset($User['UniqueID'])) {
                Gdn::userModel()->saveAuthentication(array(
                    'UserID' => $User['UserID'],
                    'Provider' => $User['ClientID'],
                    'UniqueID' => $User['UniqueID']
                ));
            }

            $this->setData('User', $User);
        } else {
            throw new Gdn_UserException(Gdn::userModel()->Validation->resultsText());
        }

        $this->render('Blank', 'Utility');
    }

    /**
     *
     *
     * @param bool $UserID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function sso($UserID = false) {
        $this->permission('Garden.Users.Edit');

        $ProviderModel = new Gdn_AuthenticationProviderModel();

        $Form = new Gdn_Form();

        if ($this->Request->isPostBack()) {
            // Make sure everything has been posted.
            $Form->validateRule('ClientID', 'ValidateRequired');
            $Form->validateRule('UniqueID', 'ValidateRequired');

            if (!validateRequired($Form->getFormValue('Username')) && !validateRequired($Form->getFormValue('Email'))) {
                $Form->addError('Username or Email is required.');
            }

            $Provider = $ProviderModel->getProviderByKey($Form->getFormValue('ClientID'));
            if (!$Provider) {
                $Form->addError(sprintf('%1$s "%2$s" not found.', t('Provider'), $Form->getFormValue('ClientID')));
            }

            if ($Form->errorCount() > 0) {
                throw new Gdn_UserException($Form->errorString());
            }

            // Grab the user.
            $User = false;
            if ($Email = $Form->getFormValue('Email')) {
                $User = Gdn::userModel()->GetByEmail($Email);
            }
            if (!$User && ($Username = $Form->getFormValue('Username'))) {
                $User = Gdn::userModel()->GetByUsername($Username);
            }
            if (!$User) {
                throw new Gdn_UserException(sprintf(t('User not found.'), strtolower(t(UserModel::SigninLabelCode()))), 404);
            }

            // Validate the user's password.
            $PasswordHash = new Gdn_PasswordHash();
            $Password = $this->Form->getFormValue('Password', null);
            if ($Password !== null && !$PasswordHash->CheckPassword($Password, val('Password', $User), val('HashMethod', $User))) {
                throw new Gdn_UserException(t('Invalid password.'), 401);
            }

            // Okay. We've gotten this far. Let's save the authentication.
            $User = (array)$User;

            Gdn::userModel()->saveAuthentication(array(
                'UserID' => $User['UserID'],
                'Provider' => $Form->getFormValue('ClientID'),
                'UniqueID' => $Form->getFormValue('UniqueID')
            ));

            $Row = Gdn::userModel()->getAuthentication($Form->getFormValue('UniqueID'), $Form->getFormValue('ClientID'));

            if ($Row) {
                $this->setData('Result', $Row);
            } else {
                throw new Gdn_UserException(t('There was an error saving the data.'));
            }
        } else {
            $User = Gdn::userModel()->getID($UserID);
            if (!$User) {
                throw notFoundException('User');
            }

            $Result = Gdn::sql()
                ->select('ua.ProviderKey', '', 'ClientID')
                ->select('ua.ForeignUserKey', '', 'UniqueID')
                ->select('ua.UserID')
                ->select('p.Name')
                ->select('p.AuthenticationSchemeAlias', '', 'Type')
                ->from('UserAuthentication ua')
                ->join('UserAuthenticationProvider p', 'ua.ProviderKey = p.AuthenticationKey')
                ->where('UserID', $UserID)
                ->get()->resultArray();

            $this->setData('Result', $Result);
        }

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     *
     *
     * @param $q
     * @param int $limit
     */
    public function tagSearch($q, $limit = 10) {
        $Data = Gdn::userModel()->tagSearch($q, $limit);
        die(json_encode($Data));
    }

    /**
     * Determine whether user can register with this username.
     *
     * @since 2.0.0
     * @access public
     * @param string $Name Username to be checked.
     */
    public function usernameAvailable($Name = '') {
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;
        $Available = true;
        if (c('Garden.Registration.NameUnique', true) && $Name != '') {
            $UserModel = Gdn::userModel();
            if ($UserModel->getByUsername($Name)) {
                $Available = false;
            }
        }
        if (!$Available) {
            $this->Form->addError(sprintf(t('%s unavailable'), t('Name')));
        }

        $this->render();
    }

    /**
     *
     *
     * @param $UserID
     * @param $Verified
     * @throws Exception
     */
    public function verify($UserID, $Verified) {
        $this->permission('Garden.Moderation.Manage');

        if (!$this->Request->isAuthenticatedPostBack()) {
            throw permissionException('Javascript');
        }

        // First, set the field value.
        Gdn::userModel()->setField($UserID, 'Verified', $Verified);

        $User = Gdn::userModel()->getID($UserID);
        if (!$User) {
            throw notFoundException('User');
        }

        // Send back the verified button.
        require_once $this->fetchViewLocation('helper_functions', 'Profile', 'Dashboard');
        $this->jsonTarget('.User-Verified', userVerified($User), 'ReplaceWith');

        $this->render('Blank', 'Utility', 'Dashboard');
    }
}
