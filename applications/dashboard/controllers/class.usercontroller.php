<?php
/**
 * Manage users.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /user endpoint.
 */
class UserController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = ['Database', 'Form'];

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
     * @param mixed $keywords Term or array of terms to filter list of users.
     * @param int $page Page number.
     * @param string $order Sort order for list.
     */
    public function index($keywords = '', $page = '', $order = '') {
        $this->permission(
            [
                'Garden.Users.Add',
                'Garden.Users.Edit',
                'Garden.Users.Delete'
            ],
            '',
            false
        );

        // Page setup
        $this->addJsFile('jquery.gardenmorepager.js');
        $this->addJsFile('user.js');
        $this->title(t('Users'));
        $this->setHighlightRoute('dashboard/user');
        Gdn_Theme::section('Moderation');

        // Form setup
        $this->Form->Method = 'get';

        // Input Validation.
        list($offset, $limit) = offsetLimit($page, PagerModule::$DefaultPageSize);
        if (!$keywords) {
            $keywords = $this->Form->getFormValue('Keywords');
            if ($keywords) {
                $offset = 0;
            }
        }

        if (!is_string($keywords)) {
            $keywords = '';
        }

        // Put the Keyword back in the form
        if ($keywords) {
            $this->Form->setFormValue('Keywords', $keywords);
        }

        $userModel = new UserModel();

        list($offset, $limit) = offsetLimit($page, 30);

        // Determine our data filters.
        $filter = $this->_getFilter();
        if ($filter) {
            $filter['Keywords'] = $keywords;
        } else {
            $filter = ['Keywords' => (string)$keywords];
        }
        $filter['Optimize'] = Gdn::userModel()->pastUserThreshold();

        // Sorting
        if (in_array($order, ['DateInserted', 'DateFirstVisit', 'DateLastActive'])) {
            $order = 'u.'.$order;
            $orderDir = 'desc';
        } else {
            $order = 'u.Name';
            $orderDir = 'asc';
        }

        // Get user list
        $this->UserData = $userModel->search($filter, $order, $orderDir, $limit, $offset);
        $this->setData('Users', $this->UserData);

        // Figure out our number of results and users.
        $showUserCount = $this->UserData->count();
        if (!Gdn::userModel()->pastUserThreshold()) {
            // Pfft, query that sucker however you want.
            $this->setData('RecordCount', $userModel->searchCount($filter));
        } else {
            // We have a user search, so at least set enough data for the Next pager.
            if ($showUserCount) {
                $this->setData('_CurrentRecords', $showUserCount);
            } else {
                // No search was done. Just give the total users overall. First, zero-out our pager.
                $this->setData('_CurrentRecords', 0);
                if (!Gdn::userModel()->pastUserMegaThreshold()) {
                    // Restoring this semi-optimized counter is our compromise to let non-mega sites know their exact total users.
                    $this->setData('UserCount', $userModel->getCount());
                } else {
                    // Dang, yo. Get a table status guess instead of really counting.
                    $this->setData('UserEstimate', Gdn::userModel()->countEstimate());
                }
            }
        }

        // Add roles to the user data.
        RoleModel::setUserRoles($this->UserData->result());

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
        $this->setHighlightRoute('dashboard/user');

        $roleModel = new RoleModel();
        $roleData = $roleModel->getAssignable();
        $userRoleData = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);

        $userModel = new UserModel();
        $this->setData('User', false);

        // Set the model on the form.
        $this->Form->setModel($userModel);

        try {
            // These are all the 'effective' roles for this add action. This list can
            // be trimmed down from the real list to allow subsets of roles to be edited.
            $this->EventArguments['RoleData'] = &$roleData;
            $this->setData('Roles', $roleData);
            $this->fireEvent("BeforeUserAdd");
            if ($this->Form->authenticatedPostBack(true)) {
                // These are the new roles the creating user wishes to apply to the target
                // user, adjusted for his ability to affect those roles
                $requestedRoles = $this->Form->getFormValue('RoleID');

                if (!is_array($requestedRoles)) {
                    $requestedRoles = [];
                }
                $requestedRoles = array_flip($requestedRoles);
                $userNewRoles = array_intersect_key($roleData, $requestedRoles);

                // Put the data back into the forum object as if the user had submitted
                // this themselves
                $this->Form->setFormValue('RoleID', array_keys($userNewRoles));

                $noPassword = (bool)$this->Form->getFormValue('NoPassword');
                if ($noPassword) {
                    $this->Form->setFormValue('Password', betterRandomString(15, 'Aa0'));
                    $this->Form->setFormValue('HashMethod', 'Random');
                }

                $newUserID = $this->Form->save(['SaveRoles' => true, 'NoConfirmEmail' => true]);
                if ($newUserID !== false) {
                    $this->setData('UserID', $newUserID);
                    if ($noPassword) {
                        $password = t('No password');
                    } else {
                        $password = $this->Form->getValue('Password', '');
                    }

                    $userModel->sendWelcomeEmail($newUserID, $password, 'Add');
                    $this->informMessage(t('The user has been created successfully'));
                    $this->setRedirectTo('dashboard/user');
                } elseif ($noPassword) {
                    $this->Form->setFormValue('Password', '');
                    $this->Form->setFormValue('HashMethod', '');
                }
                $userRoleData = $userNewRoles;
            }
        } catch (Exception $ex) {
            $this->Form->addError($ex);
        }

        $this->setData('UserRoles', $userRoleData);
        $this->render('edit', 'user');
    }

    /**
     * Show how many applicants are in the queue.
     *
     * @since 2.0.0
     * @access public
     */
    public function applicantCount() {
        $this->permission('Garden.Users.Approve');
        $roleModel = new RoleModel();
        $count = $roleModel->getApplicantCount();
        if ($count > 0) {
            echo '<span class="Alert">'.$count.'</span>';
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
        $this->setHighlightRoute('dashboard/user/applicants');
        $this->addJsFile('applicants.js');
        $this->title(t('Applicants'));
        $this->fireEvent('BeforeApplicants');
        $userModel = Gdn::userModel();
        $this->UserData = $userModel->getApplicants();
        Gdn_Theme::section('Moderation');
        $this->render();
    }

    /**
     * Approve a user application.
     *
     * @since 2.0.0
     * @access public
     * @param int $userID Unique ID.
     * @param string $TransientKey Security token.
     */
    public function approve($userID = '') {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission('Garden.Users.Approve');

        $this->handleApplicant('Approve', $userID);

        // Prevent an error if ajax failed.
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            $this->render('blank', 'utility');
        } else {
            $this->render();
        }
    }

    /**
     *
     *
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function authenticate() {
        if (!$this->Request->isPostBack()) {
            throw forbiddenException($this->Request->requestMethod());
        }

        $args = array_change_key_case($this->Form->formValues());
        $userModel = new UserModel();

        // Look up the user.
        $user = null;
        if ($email = val('email', $args)) {
            $user = $userModel->getByEmail($email);
        } elseif ($name = val('name', $args)) {
            $user = $userModel->getByUsername($name);
        } else {
            throw new Gdn_UserException("One of the following parameters required: Email, Name.", 400);
        }

        if (!$user) {
            throw notFoundException('User');
        }

        // Check the password.
        $passwordHash = new Gdn_PasswordHash();
        $password = val('password', $args);
        try {
            $passwordChecked = $passwordHash->checkPassword($password, val('Password', $user), val('HashMethod', $user));

            // Rate limiting
            Gdn::userModel()->rateLimit($user, $passwordChecked);

            if ($passwordChecked) {
                $this->setData('User', arrayTranslate((array)$user, ['UserID', 'Name', 'Email', 'PhotoUrl']));

                if (val('session', $args)) {
                    Gdn::session()->start($this->data('User.UserID'));
                    $this->setData('Cookie', [
                        c('Garden.Cookie.Name') => $_COOKIE[c('Garden.Cookie.Name')]
                    ]);
                }
            } else {
                throw new Exception(t('Invalid password.'), 401); // Can't be a user exception.
            }
        } catch (Gdn_UserException $ex) {
            $this->Form->addError($ex);
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
        $q = getIncomingValue('q');
        $userModel = new UserModel();
        $data = $userModel->getLike(['u.Name' => $q], 'u.Name', 'asc', 10, 0);
        foreach ($data->result() as $user) {
            echo htmlspecialchars($user->Name).'|'.Gdn_Format::text($user->UserID)."\n";
        }
        $this->render();
    }

    /**
     * Ban a user and optionally delete their content.
     *
     * @since 2.1
     * @param type $userID
     */
    public function ban($userID, $unban = false) {
        $this->permission(['Garden.Moderation.Manage', 'Garden.Users.Edit', 'Moderation.Users.Ban'], false);

        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw notFoundException($user);
        }

        $userModel = Gdn::userModel();

        // Block banning the super admin or system accounts.
        $user = $userModel->getID($userID);
        if (val('Admin', $user) == 2) {
            throw forbiddenException("@You may not ban a system user.");
        } elseif (val('Admin', $user)) {
            throw forbiddenException("@You may not ban a super admin.");
        }

        // Is the user banned for other reasons?
        $this->setData('OtherReasons', BanModel::isBanned(val('Banned', $user, 0), ~BanModel::BAN_MANUAL));


        if ($this->Form->authenticatedPostBack(true)) {
            if ($unban) {
                $userModel->unban($userID, ['RestoreContent' => $this->Form->getFormValue('RestoreContent')]);
            } else {
                if (!validateRequired($this->Form->getFormValue('Reason'))) {
                    $this->Form->addError('ValidateRequired', 'Reason');
                }
                if ($this->Form->getFormValue('Reason') == 'Other' && !validateRequired($this->Form->getFormValue('ReasonText'))) {
                    $this->Form->addError('ValidateRequired', 'Reason Text');
                }

                if ($this->Form->errorCount() == 0) {
                    if ($this->Form->getFormValue('Reason') == 'Other') {
                        $reason = $this->Form->getFormValue('ReasonText');
                    } else {
                        $reason = $this->Form->getFormValue('Reason');
                    }

                    // Just because we're banning doesn't mean we can nuke their content
                    $deleteContent = (checkPermission('Garden.Moderation.Manage')) ? $this->Form->getFormValue('DeleteContent') : false;
                    $userModel->ban($userID, ['Reason' => $reason, 'DeleteContent' => $deleteContent]);
                }
            }

            if ($this->Form->errorCount() == 0) {
                // Redirect after a successful save.
                if ($this->Request->get('Target')) {
                    $this->setRedirectTo($this->Request->get('Target'));
                } elseif ($this->deliveryType() == DELIVERY_TYPE_ALL) {
                    $this->setRedirectTo(userUrl($user));
                } else {
                    $this->jsonTarget('', '', 'Refresh');
                }
            }
        }

        // Permission flag for view
        $this->setData('_MayDeleteContent', checkPermission('Garden.Moderation.Manage'));

        $this->setData('User', $user);
        $this->title($unban ? t('Unban User') : t('Ban User'));
        if ($unban) {
            $this->View = 'Unban';
        }
        $this->render();
    }

    /**
     * Page thru user list.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $keywords Term or list of terms to limit search.
     * @param int $page Page number.
     * @param string $order Sort order.
     */
    public function browse($keywords = '', $page = '', $order = '') {
        $this->View = 'index';
        $this->index($keywords, $page, $order = '');
    }

    /**
     * Decline a user application.
     *
     * @since 2.0.0
     * @access public
     * @param int $userID Unique ID.
     * @param string $TransientKey Security token.
     */
    public function decline($userID = '') {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->permission('Garden.Users.Approve');

        $this->handleApplicant('Decline', $userID);

        // Prevent an error if ajax failed.
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            $this->render('blank', 'utility');
        } else {
            $this->render();
        }
    }

    /**
     * Delete a user account.
     *
     * @since 2.0.0
     * @access public
     * @param int $userID Unique ID.
     * @param string $method Type of deletion to do (delete, keep, or wipe).
     */
    public function delete($userID = '', $method = '') {
        $this->permission('Garden.Users.Delete');
        $session = Gdn::session();
        if ($session->User->UserID == $userID) {
            trigger_error(errorMessage("You cannot delete the user you are logged in as.", $this->ClassName, 'FetchViewLocation'), E_USER_ERROR);
        }
        $this->setHighlightRoute('dashboard/user');
        $this->title(t('Delete User'));

        $roleModel = new RoleModel();
        $allRoles = $roleModel->getArray();

        // By default, people with access here can freely assign all roles
        $this->RoleData = $allRoles;

        $userModel = new UserModel();
        $this->User = $userModel->getID($userID);

        try {
            $canDelete = true;
            $this->EventArguments['CanDelete'] = &$canDelete;
            $this->EventArguments['TargetUser'] = &$this->User;

            // These are all the 'effective' roles for this delete action. This list can
            // be trimmed down from the real list to allow subsets of roles to be
            // edited.
            $this->EventArguments['RoleData'] = &$this->RoleData;

            $userRoleData = $userModel->getRoles($userID)->resultArray();
            $roleIDs = array_column($userRoleData, 'RoleID');
            $roleNames = array_column($userRoleData, 'Name');
            $this->UserRoleData = arrayCombine($roleIDs, $roleNames);
            $this->EventArguments['UserRoleData'] = &$this->UserRoleData;

            $this->fireEvent("BeforeUserDelete");
            $this->setData('CanDelete', $canDelete);

            $method = in_array($method, ['delete', 'keep', 'wipe']) ? $method : '';
            $this->Method = $method;
            if ($method != '') {
                $this->View = 'deleteconfirm';
                $this->setRedirectTo('/dashboard/user');
            }

            if ($this->Form->authenticatedPostBack(true) && $method != '') {
                $userModel->deleteID($userID, ['DeleteMethod' => $method]);
                $this->View = 'deletecomplete';
            }

        } catch (Exception $ex) {
            $this->Form->addError($ex);
        }
        $this->render();
    }

    public function delete2() {
        $this->permission('Garden.Users.Delete');

        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        $this->Form->validateRule('UserID', 'ValidateRequired');
        $deleteType = $this->Form->getFormValue('DeleteMethod');
        if (!in_array($deleteType, ['delete', 'keep', 'wipe'])) {
            $this->Form->addError(t('DeleteMethod must be one of: delete, keep, wipe.'));
        }

        $userID = $this->Form->getFormValue('UserID');

        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        if ($userID && !$user) {
            throw notFoundException('User');
        }

        if ($user['Admin'] == 2) {
            $this->Form->addError(t('You cannot delete a system-created user.'));
        } elseif ($user['Admin'])
            $this->Form->addError(t('You cannot delete a super-admin.'));

        if ($this->Form->errorCount() == 0) {
            Gdn::userModel()->delete($userID, [
                'DeleteMethod' => $this->Form->getFormValue('DeleteMethod'),
                'Log' => true]);
            $this->setData('Result', sprintf(t('%s was deleted.'), $user['Name']));
        }
        $this->render('Blank', 'Utility');
    }

    /**
     *
     *
     * @param $userID
     * @throws Exception
     */
    public function deleteContent($userID) {
        $this->permission('Garden.Moderation.Manage');

        $user = Gdn::userModel()->getID($userID);
        if (!$user) {
            throw notFoundException('User');
        }

        if ($this->Form->authenticatedPostBack(true)) {
            Gdn::userModel()->deleteContent($userID, ['Log' => true]);

            if ($this->Request->get('Target')) {
                $this->setRedirectTo($this->Request->get('Target'));
            } else {
                $this->setRedirectTo(userUrl($user));
            }
        } else {
            $this->setData('Title', t('Are you sure you want to do this?'));
        }

        $this->setData('User', $user);
        $this->render();
    }

    /**
     * Edit a user account.
     *
     * @since 2.0.0
     * @access public
     * @param int $userID Unique ID.
     */
    public function edit($userID) {
        $this->permission('Garden.Users.Edit');

        // Page setup
        $this->addJsFile('user.js');
        $this->title(t('Edit User'));
        $this->setHighlightRoute('dashboard/user');

        // Only admins can reassign roles
        $roleModel = new RoleModel();
        $allRoles = $roleModel->getArray();
        $roleData = $roleModel->getAssignable();

        $userModel = new UserModel();
        $user = $userModel->getID($userID, DATASET_TYPE_ARRAY);

        // Determine if username can be edited
        $canEditUsername = (bool)c("Garden.Profile.EditUsernames") || Gdn::session()->checkPermission('Garden.Users.Edit');
        $this->setData('_CanEditUsername', $canEditUsername);

        // Determine if emails can be edited
        $canEditEmail = Gdn::session()->checkPermission('Garden.Users.Edit');
        $this->setData('_CanEditEmail', $canEditEmail);

        // Decide if they have ability to confirm users
        $confirmed = (bool)valr('Confirmed', $user);
        $canConfirmEmail = (UserModel::requireConfirmEmail() && Gdn::session()->checkPermission('Garden.Users.Edit'));
        $this->setData('_CanConfirmEmail', $canConfirmEmail);
        $this->setData('_EmailConfirmed', $confirmed);
        $user['ConfirmEmail'] = (int)$confirmed;

        // Determine whether user being edited is privileged (can escalate permissions)
        $userModel = new UserModel();
        $editingPrivilegedUser = $userModel->checkPermission($user, 'Garden.Settings.Manage');

        // Determine our password reset options
        // Anyone with user editing my force reset over email
        $this->ResetOptions = [
            0 => t('Keep current password.'),
            'Auto' => t('Force user to reset their password and send email notification.')
        ];
        // Only admins may manually reset passwords for other admins
        if (checkPermission('Garden.Settings.Manage') || !$editingPrivilegedUser) {
            $this->ResetOptions['Manual'] = t('Manually set user password. No email notification.');
        }

        // Set the model on the form.
        $this->Form->setModel($userModel);

        // Make sure the form knows which item we are editing.
        $this->Form->addHidden('UserID', $userID);

        try {
            $allowEditing = true;
            $this->EventArguments['AllowEditing'] = &$allowEditing;
            $this->EventArguments['TargetUser'] = &$user;

            // These are all the 'effective' roles for this edit action. This list can
            // be trimmed down from the real list to allow subsets of roles to be edited.
            $this->EventArguments['RoleData'] = &$roleData;

            $userRoleData = $userModel->getRoles($userID)->resultArray();
            $roleIDs = array_column($userRoleData, 'RoleID');
            $roleNames = array_column($userRoleData, 'Name');
            $userRoleData = arrayCombine($roleIDs, $roleNames);
            $this->EventArguments['UserRoleData'] = &$userRoleData;

            $this->fireEvent("BeforeUserEdit");

            $banReversible = $user['Banned'] & (BanModel::BAN_AUTOMATIC | BanModel::BAN_MANUAL);
            $this->setData('BanFlag', $banReversible ? $user['Banned'] : 1);
            $this->setData('BannedOtherReasons', $user['Banned'] & ~BanModel::BAN_MANUAL);

            $this->Form->setData($user);
            if ($this->Form->authenticatedPostBack(true)) {
                // Do not re-validate or change the username if disabled or exactly the same.
                $nameUnchanged = ($user['Name'] === $this->Form->getValue('Name'));
                $restoreName = null;
                if (!$canEditUsername || $nameUnchanged) {
                    $this->Form->removeFormValue("Name");
                    $restoreName = $user['Name'];
                }

                // Allow mods to confirm/unconfirm emails
                $this->Form->removeFormValue('Confirmed');
                $confirmation = $this->Form->getFormValue('ConfirmEmail', null);
                $confirmation = !is_null($confirmation) ? (bool)$confirmation : null;

                if ($canConfirmEmail && is_bool($confirmation)) {
                    $this->Form->setFormValue('Confirmed', (int)$confirmation);
                }

                $resetPassword = $this->Form->getValue('ResetPassword', false);

                // If we're an admin or this isn't a privileged user, allow manual setting of password
                $allowManualReset = (checkPermission('Garden.Settings.Manage') || !$editingPrivilegedUser);
                if ($resetPassword == 'Manual' && $allowManualReset) {
                    // If a new password was specified, add it to the form's collection
                    $newPassword = $this->Form->getValue('NewPassword', '');
                    $this->Form->setFormValue('Password', $newPassword);
                }

                // Role changes

                // These are the new roles the editing user wishes to apply to the target
                // user, adjusted for his ability to affect those roles
                $requestedRoles = $this->Form->getFormValue('RoleID');

                if (!is_array($requestedRoles)) {
                    $requestedRoles = [];
                }
                $requestedRoles = array_flip($requestedRoles);
                $userNewRoles = array_intersect_key($roleData, $requestedRoles);

                // These roles will stay turned on regardless of the form submission contents
                // because the editing user does not have permission to modify them
                $immutableRoles = array_diff_key($allRoles, $roleData);
                $userImmutableRoles = array_intersect_key($immutableRoles, $userRoleData);

                // Apply immutable roles
                foreach ($userImmutableRoles as $iMRoleID => $iMRoleName) {
                    $userNewRoles[$iMRoleID] = $iMRoleName;
                }

                // Put the data back into the forum object as if the user had submitted
                // this themselves
                $this->Form->setFormValue('RoleID', array_keys($userNewRoles));

                $banned = $this->Form->getFormValue('Banned');
                if (!$banned) {
                    // Checkbox was unchecked; bitmask to remove any reversible bans.
                    if ($banReversible) {
                        $reversedBans = ($user['Banned'] & (~(BanModel::BAN_AUTOMATIC | BanModel::BAN_MANUAL)));
                        $this->Form->setFormValue('Banned', $reversedBans);
                    }
                } else {
                    // Bitmask to add a manual ban.
                    $this->Form->setFormValue('Banned', $user['Banned'] | BanModel::BAN_MANUAL);
                }

                if ($this->Form->save(['SaveRoles' => true]) !== false) {
                    if ($this->Form->getValue('ResetPassword', '') == 'Auto') {
                        $userModel->passwordRequest($user['Email']);
                        $userModel->setField($userID, 'HashMethod', 'Reset');
                    }

                    $this->informMessage(t('Your changes have been saved.'));
                } else {
                    // We unset the form value on save when username is not edited or user can't edit this field.
                    // On error we need to reset the field to the original value otherwise the field is empty.
                    if (!is_null($restoreName)) {
                        $this->Form->setFormValue("Name", $restoreName);
                    }
                }

                $userRoleData = $userNewRoles;
            }
        } catch (Exception $ex) {
            $this->Form->addError($ex);
        }

        if (!$allowEditing) {
            deprecated('The `AllowEditing` event parameter', '', 'March 2017');
        }

        $this->setData('User', $user);
        $this->setData('Roles', $roleData);
        $this->setData('UserRoles', $userRoleData);

        $this->render();
    }

    /**
     * Determine whether user can register with this email address.
     *
     * @since 2.0.0
     * @access public
     * @param string $email Email address to be checked.
     */
    public function emailAvailable($email = '') {
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;
        $available = true;

        if (c('Garden.Registration.EmailUnique', true) && $email != '') {
            $userModel = Gdn::userModel();
            if ($userModel->getByEmail($email)) {
                $available = false;
            }
        }
        if (!$available) {
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
        $filter = $this->Request->get('Filter');
        if ($filter) {
            $parts = explode(' ', $filter, 3);
            if (count($parts) < 2) {
                return false;
            }

            $field = $parts[0];
            if (count($parts) == 2) {
                $op = '=';
                $filterValue = $parts[1];
            } else {
                $op = $parts[1];
                if (!in_array($op, ['=', 'like'])) {
                    $op = '=';
                }
                $filterValue = $parts[2];
            }

            // If we're using a DB function (e.g. converting binary IPs), separate it from the field.
            if (preg_match('/^(?<function>[A-Za-z0-9_]+)\((?<fieldName>[A-Za-z0-9_\.]+)\)$/', $field, $fieldParts)) {
                $field = $fieldParts['fieldName'];
                $dbFunction = $fieldParts['function'];
            }

            if (strpos($field, '.') !== false) {
                $fieldParts = explode('.', $field);
                $field = array_pop($fieldParts);
            }

            $validFields = ['Name', 'Email', 'LastIPAddress', 'InsertIPAddress', 'RankID', 'DateFirstVisit', 'DateLastActive'];
            if (!in_array($field, $validFields)) {
                return false;
            }

            // If we have a valid DB function, re-apply it to the field.
            $validDBFunctions = ['inet6_ntoa'];
            if (isset($dbFunction) && in_array($dbFunction, $validDBFunctions)) {
                $field = "{$dbFunction}({$field})";
            }

            return ["$field $op" => $filterValue];
        }
        return false;
    }

    /**
     * Handle a user application.
     *
     * @since 2.0.0
     * @access private
     * @see UserModel::decline, UserModel::approve
     *
     * @param string $action Approve or Decline.
     * @param int $userID Unique ID.
     * @return bool Whether handling was successful.
     */
    private function handleApplicant($action, $userID) {
        $this->permission('Garden.Users.Approve');

        if (!in_array($action, ['Approve', 'Decline']) || !is_numeric($userID)) {
            $this->Form->addError('ErrorInput');
            $result = false;
        } else {
            $userModel = new UserModel();
            if (is_numeric($userID)) {
                try {
                    $this->EventArguments['UserID'] = $userID;
                    $this->fireEvent("Before{$action}User");

                    $email = new Gdn_Email();
                    $result = $userModel->$action($userID, $email);

                    // Re-calculate applicant count
                    $roleModel = new RoleModel();
                    $roleModel->getApplicantCount(true);

                    $this->fireEvent("After{$action}User");
                } catch (Exception $ex) {
                    $result = false;
                    $this->Form->addError(strip_tags($ex->getMessage()));
                }
            }
        }

        return $result;
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
        if (!$this->Request->isAuthenticatedPostBack(true)) {
            throw forbiddenException('GET');
        }

        $validation = new Gdn_Validation();
        $validation->applyRule('OldUserID', 'ValidateRequired');
        $validation->applyRule('NewUserID', 'ValidateRequired');
        if ($validation->validate($this->Request->post())) {
            $result = Gdn::userModel()->merge(
                $this->Request->post('OldUserID'),
                $this->Request->post('NewUserID')
            );
            $this->setData($result);
        } else {
            $this->Form->setValidationResults($validation->results());
        }
        $this->render('Blank', 'Utility');
    }

    /**
     * Build URL to order users by value passed.
     *
     * @since 2.0.0
     * @access protected
     * @param string $field Column to order users by.
     * @return string URL of user list with orderby query appended.
     */
    protected function _orderUrl($field) {
        $get = Gdn::request()->get();
        $get['order'] = $field;
        return '/dashboard/user?'.http_build_query($get);
    }



    /**
     * Convenience function for listing users. At time of this writing, it is
     * being used by wordpress widgets to display recently active users.
     *
     * @since 2.0.?
     * @access public
     * @param string $sortField The field to sort users with. Defaults to DateLastActive. Other options are: DateInserted, Name.
     * @param string $sortDirection The direction to sort the users.
     * @param int $limit The number of users to show.
     * @param int $offset The offset to start listing users at.
     */
    public function summary($sortField = 'DateLastActive', $sortDirection = 'desc', $limit = 30, $offset = 0) {
        // Added permission check Oct 2014 - Guest now requires Profiles.View for WP widget to work.
        $this->permission('Garden.Profiles.View');
        $this->title(t('User Summary'));

        // Input validation
        $sortField = !in_array($sortField, ['DateLastActive', 'DateInserted', 'Name']) ? 'DateLastActive' : $sortField;
        $sortDirection = $sortDirection == 'asc' ? 'asc' : 'desc';
        $limit = is_numeric($limit) && $limit < 100 && $limit > 0 ? $limit : 30;
        $offset = is_numeric($offset) ? $offset : 0;

        // Get user list
        $userModel = new UserModel();
        $userData = $userModel->getSummary('u.'.$sortField, $sortDirection, $limit, $offset);
        $this->setData('UserData', $userData);

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
        if (!Gdn::request()->isAuthenticatedPostBack()) {
            throw new Exception('Requires POST', 405);
        }

        $form = new Gdn_Form();

        if ($sSOString = $form->getFormValue('SSOString')) {
            $parts = explode(' ', $sSOString);
            $string = $parts[0];
            $data = json_decode(base64_decode($string), true);
            $user = arrayTranslate($data, ['name' => 'Name', 'email' => 'Email', 'photourl' => 'Photo', 'client_id' => 'ClientID', 'uniqueid' => 'UniqueID']);
        } else {
            $user = $form->formValues();
        }

        if (!isset($user['UserID']) && isset($user['UniqueID'])) {
            // Try and find the user based on SSO.
            $auth = Gdn::userModel()->getAuthentication($user['UniqueID'], $user['ClientID']);
            if ($auth) {
                $user['UserID'] = $auth['UserID'];
            }
        }

        if (!isset($user['UserID'])) {
            // Add some default values to make saving easier.
            if (!isset($user['RoleID'])) {
                $defaultRoles = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
                $user['RoleID'] = $defaultRoles;
            } elseif (is_numeric($user['RoleID'])) {
                // UserModel->save() demands an array for RoleID.
                $user['RoleID'] = [$user['RoleID']];
            }

            if (!isset($user['Password'])) {
                $user['Password'] = md5(microtime());
                $user['HashMethod'] = 'Random';
            }
        }

        $userID = Gdn::userModel()->save($user, ['SaveRoles' => isset($user['RoleID']), 'NoConfirmEmail' => true]);
        if ($userID) {
            if (!isset($user['UserID'])) {
                $user['UserID'] = $userID;
            }

            if (isset($user['ClientID']) && isset($user['UniqueID'])) {
                Gdn::userModel()->saveAuthentication([
                    'UserID' => $user['UserID'],
                    'Provider' => $user['ClientID'],
                    'UniqueID' => $user['UniqueID']
                ]);
            }

            $this->setData('User', $user);
        } else {
            throw new Gdn_UserException(Gdn::userModel()->Validation->resultsText());
        }

        $this->render('Blank', 'Utility');
    }

    /**
     *
     *
     * @param bool $userID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function sso($userID = false) {
        $this->permission('Garden.Users.Edit');

        $providerModel = new Gdn_AuthenticationProviderModel();

        $form = new Gdn_Form();

        if ($this->Request->isAuthenticatedPostBack(true)) {
            // Make sure everything has been posted.
            $form->validateRule('ClientID', 'ValidateRequired');
            $form->validateRule('UniqueID', 'ValidateRequired');

            if (!validateRequired($form->getFormValue('Username')) && !validateRequired($form->getFormValue('Email'))) {
                $form->addError('Username or Email is required.');
            }

            $provider = $providerModel->getProviderByKey($form->getFormValue('ClientID'));
            if (!$provider) {
                $form->addError(sprintf('%1$s "%2$s" not found.', t('Provider'), $form->getFormValue('ClientID')));
            }

            if ($form->errorCount() > 0) {
                throw new Gdn_UserException($form->errorString());
            }

            // Grab the user.
            $user = false;
            if ($email = $form->getFormValue('Email')) {
                $user = Gdn::userModel()->getByEmail($email);
            }
            if (!$user && ($username = $form->getFormValue('Username'))) {
                $user = Gdn::userModel()->getByUsername($username);
            }
            if (!$user) {
                throw new Gdn_UserException(sprintf(t('User not found.'), strtolower(t(UserModel::signinLabelCode()))), 404);
            }

            // Validate the user's password.
            $passwordHash = new Gdn_PasswordHash();
            $password = $this->Form->getFormValue('Password', null);
            if ($password !== null && !$passwordHash->checkPassword($password, val('Password', $user), val('HashMethod', $user))) {
                throw new Gdn_UserException(t('Invalid password.'), 401);
            }

            // Okay. We've gotten this far. Let's save the authentication.
            $user = (array)$user;

            Gdn::userModel()->saveAuthentication([
                'UserID' => $user['UserID'],
                'Provider' => $form->getFormValue('ClientID'),
                'UniqueID' => $form->getFormValue('UniqueID')
            ]);

            $row = Gdn::userModel()->getAuthentication($form->getFormValue('UniqueID'), $form->getFormValue('ClientID'));

            if ($row) {
                $this->setData('Result', $row);
            } else {
                throw new Gdn_UserException(t('There was an error saving the data.'));
            }
        } else {
            $user = Gdn::userModel()->getID($userID);
            if (!$user) {
                throw notFoundException('User');
            }

            $result = Gdn::sql()
                ->select('ua.ProviderKey', '', 'ClientID')
                ->select('ua.ForeignUserKey', '', 'UniqueID')
                ->select('ua.UserID')
                ->select('p.Name')
                ->select('p.AuthenticationSchemeAlias', '', 'Type')
                ->from('UserAuthentication ua')
                ->join('UserAuthenticationProvider p', 'ua.ProviderKey = p.AuthenticationKey')
                ->where('UserID', $userID)
                ->get()->resultArray();

            $this->setData('Result', $result);
        }

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * JSON output of a username search.
     *
     * @param string $query
     * @param int $limit
     */
    public function tagSearch($q, $limit = 10) {
        if (!empty($q)) {
            $data = Gdn::userModel()->tagSearch($q, $limit);
        } else {
            $data = [];
        }
        $this->contentType('application/json; charset=utf-8');
        $this->sendHeaders();
        die(json_encode($data));
    }

    /**
     * Determine whether user can register with this username.
     *
     * @since 2.0.0
     * @access public
     * @param string $name Username to be checked.
     */
    public function usernameAvailable($name = '') {
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;
        $available = true;
        if (c('Garden.Registration.NameUnique', true) && $name != '') {
            $userModel = Gdn::userModel();
            if ($userModel->getByUsername($name)) {
                $available = false;
            }
        }
        if (!$available) {
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

        $User = Gdn::userModel()->getID($UserID);
        if (!$User) {
            throw notFoundException('User');
        }

        // First, set the field value.
        Gdn::userModel()->setField($UserID, 'Verified', $Verified);

        // Send back the verified button.
        require_once $this->fetchViewLocation('helper_functions', 'Profile', 'Dashboard');
        $this->jsonTarget('.User-Verified', userVerified($User), 'ReplaceWith');

        $this->render('Blank', 'Utility', 'Dashboard');
    }
}
