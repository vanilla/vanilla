<?php
/**
 * Manages users manually authenticating (signing in).
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /entry endpoint.
 */
class EntryController extends Gdn_Controller {

    /** @var array Models to include. */
    public $Uses = ['Database', 'Form', 'UserModel'];

    /** @var Gdn_Form */
    public $Form;

    /** @var UserModel */
    public $UserModel;

    /** @var string Reusable username requirement error message. */
    public $UsernameError = '';

    /** @var string Place to store DeliveryType. */
    protected $_RealDeliveryType;

    /**
     * Setup error message & override MasterView for popups.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct();
        $this->internalMethods[] = 'target';

        // Set error message here so it can run thru t()
        $this->UsernameError = t('UsernameError', 'Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long.');

        // Allow use of a master popup template for easier theming.
        if (Gdn::request()->get('display') === 'popup') {
            $this->MasterView = 'popup';
        }
    }

    /**
     * Include JS and CSS used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        $this->Head = new HeadModule($this);
        $this->Head->addTag('meta', ['name' => 'robots', 'content' => 'noindex']);

        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        parent::initialize();
        Gdn_Theme::section('Entry');

        if ($this->UserModel->isNameUnique() && !$this->UserModel->isEmailUnique()) {
            $this->setData('RecoverPasswordLabelCode', 'Enter your username to continue.');
        } else {
            $this->setData('RecoverPasswordLabelCode', 'Enter your email to continue.');
        }

        $this->addDefinition("userSearchAvailable", $this->UserModel->allowGuestUserSearch());
    }

    /**
     * Obscures the sign in error for private communities. Gives a fuzzy error rather than disclosing whether
     * a username or email exists or not. If private communities is not enabled, adds the passed translation
     * code to the form property.
     *
     * @param String $translationCode The error message translation code.
     */
    public function addCredentialErrorToForm($translationCode) {
        if (c('Garden.PrivateCommunity', false)) {
            $this->Form->addError('Bad login, double-check your credentials and try again.');
        } else {
            $this->Form->addError($translationCode);
        }
    }

    /**
     * Authenticate the user attempting to sign in.
     *
     * Events: BeforeAuth
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $authenticationSchemeAlias Type of authentication we're attempting.
     */
    public function auth($authenticationSchemeAlias = 'default') {
        $this->EventArguments['AuthenticationSchemeAlias'] = $authenticationSchemeAlias;
        $this->fireEvent('BeforeAuth');

        // Allow hijacking auth type
        $authenticationSchemeAlias = $this->EventArguments['AuthenticationSchemeAlias'];

        // Attempt to set authenticator with requested method or fallback to default
        try {
            $authenticator = Gdn::authenticator()->authenticateWith($authenticationSchemeAlias);
        } catch (Exception $e) {
            $authenticator = Gdn::authenticator()->authenticateWith('default');
        }

        // Set up controller
        $this->View = 'auth/' . $authenticator->getAuthenticationSchemeAlias();
        $this->Form->setModel($this->UserModel);
        $this->Form->addHidden('ClientHour', date('Y-m-d H:00')); // Use the server's current hour as a default.

        $target = $this->target();

        $this->Form->addHidden('Target', $target);

        // Import authenticator data source
        switch ($authenticator->dataSourceType()) {
            case Gdn_Authenticator::DATA_FORM:
                $authenticator->fetchData($this->Form);
                break;

            case Gdn_Authenticator::DATA_REQUEST:
            case Gdn_Authenticator::DATA_COOKIE:
                $authenticator->fetchData(Gdn::request());
                break;
        }

        // By default, just render the view
        $reaction = Gdn_Authenticator::REACT_RENDER;

        // Where are we in the process? Still need to gather (render view) or are we validating?
        $authenticationStep = $authenticator->currentStep();

        switch ($authenticationStep) {
            // User is already logged in
            case Gdn_Authenticator::MODE_REPEAT:
                $reaction = $authenticator->repeatResponse();
                break;

            // Not enough information to perform authentication, render input form
            case Gdn_Authenticator::MODE_GATHER:
                $this->addJsFile('entry.js');
                $reaction = $authenticator->loginResponse();
                if ($this->Form->isPostBack()) {
                    $this->addCredentialErrorToForm('ErrorCredentials');
                    Logger::event(
                        'signin_failure',
                        Logger::WARNING,
                        '{username} failed to sign in. Some or all credentials were missing.',
                        [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
                    );
                }
                break;

            // All information is present, authenticate
            case Gdn_Authenticator::MODE_VALIDATE:
                // Attempt to authenticate.
                try {
                    if (!$this->Request->isAuthenticatedPostBack() && !c('Garden.Embed.Allow')) {
                        $this->Form->addError('Please try again.');
                        $reaction = $authenticator->failedResponse();
                    } else {
                        $authenticationResponse = $authenticator->authenticate();

                        $userInfo = [];
                        $userEventData = array_merge([
                            'UserID' => Gdn::session()->UserID,
                            'Payload' => val('HandshakeResponse', $authenticator, false),
                        ], $userInfo);

                        Gdn::authenticator()->trigger($authenticationResponse, $userEventData);
                        switch ($authenticationResponse) {
                            case Gdn_Authenticator::AUTH_PERMISSION:
                                $this->Form->addError('ErrorPermission');
                                Logger::event(
                                    'signin_failure',
                                    Logger::WARNING,
                                    '{username} failed to sign in. Permission denied.',
                                    [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
                                );
                                $reaction = $authenticator->failedResponse();
                                break;

                            case Gdn_Authenticator::AUTH_DENIED:
                                $this->addCredentialErrorToForm('ErrorCredentials');
                                Logger::event(
                                    'signin_failure',
                                    Logger::WARNING,
                                    '{username} failed to sign in. Authentication denied.',
                                    [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
                                );
                                $reaction = $authenticator->failedResponse();
                                break;

                            case Gdn_Authenticator::AUTH_INSUFFICIENT:
                                // Unable to comply with auth request, more information is needed from user.
                                Logger::event(
                                    'signin_failure',
                                    Logger::WARNING,
                                    '{username} failed to sign in. More information needed from user.',
                                    [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
                                );
                                $this->addCredentialErrorToForm('ErrorInsufficient');

                                $reaction = $authenticator->failedResponse();
                                break;

                            case Gdn_Authenticator::AUTH_PARTIAL:
                                // Partial auth completed.
                                $reaction = $authenticator->partialResponse();
                                break;

                            case Gdn_Authenticator::AUTH_SUCCESS:
                            default:
                                // Full auth completed.
                                if ($authenticationResponse == Gdn_Authenticator::AUTH_SUCCESS) {
                                    $userID = Gdn::session()->UserID;
                                } else {
                                    $userID = $authenticationResponse;
                                }

                                safeHeader("X-Vanilla-Authenticated: yes");
                                safeHeader("X-Vanilla-TransientKey: " . Gdn::session()->transientKey());
                                $reaction = $authenticator->successResponse();
                        }
                    }
                } catch (Exception $ex) {
                    $this->Form->addError($ex);
                }
                break;

            case Gdn_Authenticator::MODE_NOAUTH:
                $reaction = Gdn_Authenticator::REACT_REDIRECT;
                break;
        }

        switch ($reaction) {

            case Gdn_Authenticator::REACT_RENDER:
                // Do nothing (render the view)
                break;

            case Gdn_Authenticator::REACT_EXIT:
                exit();
                break;

            case Gdn_Authenticator::REACT_REMOTE:
                // Let the authenticator handle generating output, using a blank slate
                $this->_DeliveryType = DELIVERY_TYPE_VIEW;

                exit;
                break;

            case Gdn_Authenticator::REACT_REDIRECT:
            default:

                if (is_string($reaction)) {
                    $route = $reaction;
                } else {
                    $route = $this->getTargetRoute();
                }

                if ($this->_RealDeliveryType != DELIVERY_TYPE_ALL && $this->_DeliveryType != DELIVERY_TYPE_ALL) {
                    $this->setRedirectTo($route);
                } else {
                    if ($route !== false) {
                        redirectTo($route);
                    } else {
                        redirectTo(Gdn::router()->getDestination('DefaultController'));
                    }
                }
                break;
        }

        $this->setData('SendWhere', "/entry/auth/{$authenticationSchemeAlias}");
        $this->render();
    }

    /**
     * Check the default provider to see if it overrides one of the entry methods and then redirect.
     *
     * @param string $type One of the following.
     *  - SignIn
     *  - Register
     *  - SignOut (not complete)
     * @param string $target
     * @param string $transientKey
     */
    protected function checkOverride($type, $target, $transientKey = null) {
        if (!$this->Request->get('override', true)) {
            return;
        }

        $provider = Gdn_AuthenticationProviderModel::getDefault();
        if (!$provider) {
            return;
        }

        $this->EventArguments['Target'] = $target;
        $this->EventArguments['DefaultProvider'] =& $provider;
        $this->EventArguments['TransientKey'] = $transientKey;
        $this->fireEvent("Override{$type}");

        $url = $provider[$type . 'Url'];
        if ($url) {
            switch ($type) {
                case 'Register':
                case 'SignIn':
                    // When the other page comes back it needs to go through /sso to force a sso check.
                    $target = '/sso?target=' . urlencode($target);
                    break;
                case 'SignOut':
                    $cookie = c('Garden.Cookie.Name');
                    if (strpos($url, '?') === false) {
                        $url .= '?vfcookie=' . urlencode($cookie);
                    } else {
                        $url .= '&vfcookie=' . urlencode($cookie);
                    }

                    // Check to sign out here.
                    $signedOut = !Gdn::session()->isValid();
                    if (!$signedOut && (Gdn::session()->validateTransientKey($transientKey) || $this->Form->isPostBack())) {
                        Gdn::session()->end();
                        $signedOut = true;
                    }

                    // Sign out is a bit of a tricky thing so we configure the way it works.
                    $signoutType = c('Garden.SSO.Signout');
                    switch ($signoutType) {
                        case 'redirect-only':
                            // Just redirect to the url.
                            break;
                        case 'post-only':
                            $this->setData('Method', 'POST');
                            break;
                        case 'post':
                            // Post to the url after signing out here.
                            if (!$signedOut) {
                                return;
                            }
                            $this->setData('Method', 'POST');
                            break;
                        case 'none':
                            return;
                        case 'redirect':
                        default:
                            if (!$signedOut) {
                                return;
                            }
                            break;
                    }

                    break;
                default:
                    throw new Exception("Unknown entry type $type.");
            }

            $url = str_ireplace('{target}', rawurlencode(url($target, true)), $url);

            if (($this->deliveryType() == DELIVERY_TYPE_ALL && strcasecmp($this->data('Method'), 'POST') != 0) ||
                (defined('TESTMODE_ENABLED') && TESTMODE_ENABLED)
            ) {
                redirectTo(url($url, true), 302, false);
            } else {
                $this->setData('Url', $url);
                $this->render('Redirect', 'Utility');
                die();
            }
        }
    }

    /**
     * SSO facilitator page. Plugins use event `ConnectData` to complete SSO connections.
     *
     * Users only see this page for non-seamless connections that prompt them to finish connecting
     * by entering a username and/or password (and possibly email).
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $method Used to register multiple providers on ConnectData event.
     */
    public function connect($method) {
        // Basic page setup.
        $this->addJsFile('entry.js');
        $this->View = 'connect';
        $this->addDefinition('Username already exists.', t('Username already exists.'));
        $this->addDefinition('Choose a name to identify yourself on the site.', t('Choose a name to identify yourself on the site.'));
        $this->setHeader('Cache-Control', \Vanilla\Web\CacheControlMiddleware::NO_CACHE);
        $this->setHeader('Vary', \Vanilla\Web\CacheControlMiddleware::VARY_COOKIE);
        // Determine what step in the process we're at.
        $isPostBack = $this->Form->isPostBack() && $this->Form->getFormValue('Connect', null) !== null;
        $userSelect = $this->Form->getFormValue('UserSelect');

        /**
         * When a user is connecting through SSO she is prompted to choose a username.
         * If she chooses an existing username, she is prompted to enter the password to claim it.
         * Setting AllowConnect = false disables that workflow, forcing the user to choose a unique username.
         */
        $allowConnect = c('Garden.Registration.AllowConnect', true);
        $this->setData('AllowConnect', $allowConnect);
        $this->addDefinition('AllowConnect', $allowConnect);

        if (!$isPostBack) {
            // Initialize data array that can be set by a plugin.
            $data = [
                'Provider' => '',
                'ProviderName' => '',
                'UniqueID' => '',
                'FullName' => '',
                'Name' => '',
                'Email' => '',
                'Photo' => '',
                'Target' => $this->target(),
            ];
            $this->Form->setData($data);
            $this->Form->addHidden('Target', $this->Request->get('Target', '/'));
        } else {
            // Disallow some sensitive fields unless the provider puts it in.
            $this->Form->removeFormValue('UserID');
        }

        // SSO providers can check to see if they are being used and modify the data array accordingly.
        $this->EventArguments = [$method];

        // Filter the form data for users.
        // SSO plugins must reset validated data each postback.
        $currentData = $this->Form->formValues();
        $filteredData = Gdn::userModel()->filterForm($currentData, true);
        $filteredData = array_replace($filteredData, arrayTranslate($currentData, ['TransientKey', 'hpt']));
        unset($filteredData['Roles'], $filteredData['RoleID'], $filteredData['RankID']);
        $this->Form->formValues($filteredData);

        // Fire ConnectData event & error handling.
        try {
            // Where your SSO plugin does magic.
            $this->EventArguments['Form'] = $this->Form;
            $this->fireEvent('ConnectData');
            $this->fireEvent('AfterConnectData');
        } catch (Gdn_UserException $ex) {
            // Your SSO magic said no.
            $this->Form->addError($ex);

            return $this->render('ConnectError');
        } catch (Exception $ex) {
            // Your SSO magic blew up.
            if (debug()) {
                $this->Form->addError($ex);
            } else {
                $this->Form->addError('There was an error fetching the connection data.');
            }

            return $this->render('ConnectError');
        }

        // Allow a provider to not send an email address but require one be manually entered.
        $userProvidedEmail = false;
        if (!UserModel::noEmail()) {
            $emailProvided = $this->Form->getFormValue('Email');
            $emailRequested = $this->Form->getFormValue('EmailVisible');
            if (!$emailProvided || $emailRequested) {
                $this->Form->setFormValue('EmailVisible', true);
                $this->Form->addHidden('EmailVisible', true);

                if ($isPostBack) {
                    $this->Form->setFormValue('Email', val('Email', $currentData));
                    $userProvidedEmail = true;
                }
            }
            if ($isPostBack && $emailRequested) {
                $this->Form->validateRule('Email', 'ValidateRequired');
                $this->Form->validateRule('Email', 'ValidateEmail');
            }
        }

        // Make sure the minimum required data has been provided by the connection.
        if (!$this->Form->getFormValue('Provider')) {
            $this->Form->addError('ValidateRequired', t('Provider'));
        }
        if (!$this->Form->getFormValue('UniqueID')) {
            $this->Form->addError('ValidateRequired', t('UniqueID'));
        }

        if (!$this->data('Verified')) {
            // Whatever event handler catches this must set the data 'Verified' = true
            // to prevent a random site from connecting without credentials.
            // This must be done EVERY postback and is VERY important.
            $this->Form->addError(t('The connection data has not been verified.'));
        }

        // If we've accrued errors, stop here and show them.
        if ($this->Form->errorCount() > 0) {
            $this->render();

            return;
        }

        $isTrustedProvider = $this->data('Trusted');
        $roles = $this->Form->getFormValue('Roles', $this->Form->getFormValue('roles', null));

        // Check if we need to sync roles
        if (($isTrustedProvider || c('Garden.SSO.SyncRoles')) && $roles !== null) {
            $saveRoles = $saveRolesRegister = true;

            // Translate the role names to IDs.
            $roles = RoleModel::getByName($roles);
            $roleIDs = array_keys($roles);

            // Ensure user has at least one role.
            if (empty($roleIDs)) {
                $roleIDs = $this->UserModel->newUserRoleIDs();
            }

            // Allow role syncing to only happen on first connect.
            if (c('Garden.SSO.SyncRolesBehavior') === 'register') {
                $saveRoles = false;
            }

            $this->Form->setFormValue('RoleID', $roleIDs);
        } else {
            $saveRoles = false;
            $saveRolesRegister = false;
        }

        $userModel = Gdn::userModel();

        // Find an existing user associated with this provider & uniqueid.
        $auth = $userModel->getAuthentication($this->Form->getFormValue('UniqueID'), $this->Form->getFormValue('Provider'));
        $userID = val('UserID', $auth);

        // The user is already in the UserAuthentication table
        if ($userID) {
            $this->Form->setFormValue('UserID', $userID);

            // Update their info.
            if (c('Garden.Registration.ConnectSynchronize', true)) {
                $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
                $data = $this->Form->formValues();

                // Don't overwrite the user photo if the user uploaded a new one.
                $photo = val('Photo', $user);
                if ($photo && !isUrl($photo)) {
                    unset($data['Photo']);
                }

                // Synchronize the user's data.
                $userModel->save($data, [
                    UserModel::OPT_NO_CONFIRM_EMAIL => true,
                    UserModel::OPT_FIX_UNIQUE => true,
                    UserModel::OPT_SAVE_ROLES => $saveRoles,
                    UserModel::OPT_VALIDATE_NAME => !$isTrustedProvider,
                    UserModel::OPT_ROLE_SYNC => $userModel->getConnectRoleSync(),
                ]);
                $this->EventArguments['UserID'] = $userID;
                $this->fireEvent('AfterConnectSave');
            }

            // Always save the attributes because they may contain authorization information.
            if ($attributes = $this->Form->getFormValue('Attributes')) {
                $userModel->saveAttribute($userID, $attributes);
            }

            // Sign the user in.
            Gdn::userModel()->fireEvent('BeforeSignIn', ['UserID' => $userID]);
            Gdn::session()->start($userID, true, (bool)$this->Form->getFormValue('RememberMe', c('Garden.SSO.RememberMe', true)));
            Gdn::userModel()->fireEvent('AfterSignIn');

            // Send them on their way.
            $this->_setRedirect(Gdn::request()->get('display') === 'popup');

            // If a name of email has been provided
        } elseif ($this->Form->getFormValue('Name') || $this->Form->getFormValue('Email')) {
            // Decide how to handle our first time connecting.
            $nameUnique = c('Garden.Registration.NameUnique', true);
            $emailUnique = c('Garden.Registration.EmailUnique', true);
            $autoConnect = c('Garden.Registration.AutoConnect');

            // Decide which name to search for.
            if ($isPostBack && $this->Form->getFormValue('ConnectName')) {
                $searchName = $this->Form->getFormValue('ConnectName');
            } else {
                $searchName = $this->Form->getFormValue('Name');
            }

            // Find existing users that match the name or email of the connection.
            // First, discover if we have search criteria.
            $search = false;
            $existingUsers = [];
            if ($searchName && $nameUnique) {
                $userModel->SQL->orWhere('Name', $searchName);
                $search = true;
            }
            if ($this->Form->getFormValue('Email') && ($emailUnique || $autoConnect)) {
                $userModel->SQL->orWhere('Email', $this->Form->getFormValue('Email'));
                $search = true;
            }
            if (is_numeric($userSelect)) {
                $userModel->SQL->orWhere('UserID', $userSelect);
                $search = true;
            }
            // Now do the search if we found some criteria.
            if ($search) {
                $existingUsers = $userModel->getWhere()->resultArray();
            }

            // Get the email and decide if we can safely find a match.
            $submittedEmail = $this->Form->getFormValue('Email');
            $canMatchEmail = (strlen($submittedEmail) > 0) && !UserModel::noEmail();

            // Check to automatically link the user.
            if ($autoConnect && count($existingUsers) > 0) {
                if ($isPostBack && $this->Form->getFormValue('ConnectName')) {
                    $this->Form->setFormValue('Name', $this->Form->getFormValue('ConnectName'));
                }

                if ($canMatchEmail) {
                    // Check each existing user for an exact email match.
                    foreach ($existingUsers as $row) {
                        if (strcasecmp($submittedEmail, $row['Email']) === 0) {
                            // Add the UserID to the form, then get the unified user data set from it.
                            $userID = $row['UserID'];
                            $this->Form->setFormValue('UserID', $userID);
                            $data = $this->Form->formValues();

                            // User synchronization.
                            if (c('Garden.Registration.ConnectSynchronize', true)) {
                                // Don't overwrite a photo if the user has already uploaded one.
                                $photo = val('Photo', $row);
                                if (!val('Photo', $data) || ($photo && !stringBeginsWith($photo, 'http'))) {
                                    unset($data['Photo']);
                                }

                                // Update the user.
                                $userModel->save($data, [
                                    UserModel::OPT_NO_CONFIRM_EMAIL => true,
                                    UserModel::OPT_FIX_UNIQUE => true,
                                    UserModel::OPT_SAVE_ROLES => $saveRoles,
                                    UserModel::OPT_VALIDATE_NAME => !$isTrustedProvider,
                                    UserModel::OPT_ROLE_SYNC => $userModel->getConnectRoleSync(),
                                ]);
                                $this->EventArguments['UserID'] = $userID;
                                $this->fireEvent('AfterConnectSave');
                            }

                            // Always save the attributes because they may contain authorization information.
                            if ($attributes = $this->Form->getFormValue('Attributes')) {
                                $userModel->saveAttribute($userID, $attributes);
                            }

                            // Save the user authentication association.
                            $userModel->saveAuthentication([
                                'UserID' => $userID,
                                'Provider' => $this->Form->getFormValue('Provider'),
                                'UniqueID' => $this->Form->getFormValue('UniqueID'),
                            ]);

                            // Sign the user in.
                            Gdn::userModel()->fireEvent('BeforeSignIn', ['UserID' => $userID]);
                            Gdn::session()->start($userID, true, (bool)$this->Form->getFormValue('RememberMe', c('Garden.SSO.RememberMe', true)));
                            Gdn::userModel()->fireEvent('AfterSignIn');
                            $this->_setRedirect(Gdn::request()->get('display') === 'popup');
                            $this->render();

                            return;
                        }
                    }
                }
            } // Did not autoconnect!
            // Explore alternatives for a first-time connection.

            // This will be zero for a guest.
            $currentUserID = Gdn::session()->UserID;

            // Evaluate the existing users for matches.
            foreach ($existingUsers as $index => $userRow) {
                if ($emailUnique && $canMatchEmail && $userRow['Email'] == $submittedEmail) {
                    // An email match overrules any other options.
                    $emailFound = $userRow;
                    break;
                }

                // Detect a simple name match.
                if ($userRow['Name'] == $this->Form->getFormValue('Name')) {
                    $nameFound = $userRow;
                }

                // Detect if we have a match on the current user session.
                if ($currentUserID > 0 && $userRow['UserID'] == $currentUserID) {
                    unset($existingUsers[$index]);
                    $currentUserFound = true;
                }
            }

            // Handle special cases for what we matched on.
            if (isset($emailFound)) {
                // The email address was found and can be the only user option.
                $existingUsers = [$userRow];
                $this->setData('NoConnectName', true);
            } elseif (isset($currentUserFound)) {
                // If we're already logged in to Vanilla, assume that's an option we want.
                $existingUsers = array_merge(
                    ['UserID' => 'current', 'Name' => sprintf(t('%s (Current)'), Gdn::session()->User->Name)],
                    $existingUsers
                );
            }

            // Block connecting to an existing user if it's disallowed.
            if (!$allowConnect) {
                // Make sure photo of existing user doesn't show on the form.
                $this->Form->setFormValue("Photo", null);
                // Ignore existing users found.
                $existingUsers = [];
            }

            // Set our final answer on matched users.
            $this->setData('ExistingUsers', $existingUsers);

            // Validate our email address if we have one.
            if (UserModel::noEmail()) {
                $emailValid = true;
            } else {
                $emailValid = validateRequired($this->Form->getFormValue('Email'));
            }

            // Set some nice variable names for logic clarity.
            $noMatches = (!is_array($existingUsers) || count($existingUsers) == 0);
            $didNotPickUser = (!$userSelect || $userSelect == 'other');
            $haveName = $this->Form->getFormValue('Name');

            // Should we create a new user?
            if ($didNotPickUser && $haveName && $emailValid && $noMatches) {
                // Create the user.
                $registerOptions = [
                    'CheckCaptcha' => false,
                    'ValidateEmail' => false,
                    'NoConfirmEmail' => !$userProvidedEmail || !UserModel::requireConfirmEmail(),
                    'SaveRoles' => $saveRolesRegister,
                    'ValidateName' => !$isTrustedProvider,
                ];
                $user = $this->Form->formValues();
                $user['Password'] = randomString(16); // Required field.
                $user['HashMethod'] = 'Random';
                $user['Source'] = $this->Form->getFormValue('Provider');
                $user['SourceID'] = $this->Form->getFormValue('UniqueID');
                $user['Attributes'] = $this->Form->getFormValue('Attributes', null);
                $user['Email'] = $this->Form->getFormValue('ConnectEmail', $this->Form->getFormValue('Email', null));
                $user['Name'] = $this->Form->getFormValue('ConnectName', $this->Form->getFormValue('Name', null));
                $userID = $userModel->register($user, $registerOptions);

                $user['UserID'] = $userID;

                $this->EventArguments['UserID'] = $userID;
                $this->fireEvent('AfterConnectSave');

                $this->Form->setValidationResults($userModel->validationResults());

                // The SPAM filter was likely triggered. Send the registration for approval, add some generic "reason" text.
                if ($userID === false && val('DiscoveryText', $this->Form->validationResults())) {
                    unset($user['UserID']);
                    $user['DiscoveryText'] = sprintft(t('SSO connection (%s)'), $method);
                    $userModel->Validation->reset();
                    $userID = $userModel->register($user, $registerOptions);

                    if ($userID === UserModel::REDIRECT_APPROVE) {
                        $this->Form->setFormValue('Target', '/entry/registerthanks');
                        $this->_setRedirect();

                        return;
                    }

                    $this->Form->setValidationResults($userModel->validationResults());
                    $user['UserID'] = $userID;
                }

                // Save the association to the new user.
                if ($userID) {
                    $userModel->saveAuthentication([
                        'UserID' => $userID,
                        'Provider' => $this->Form->getFormValue('Provider'),
                        'UniqueID' => $this->Form->getFormValue('UniqueID'),
                    ]);

                    $this->Form->setFormValue('UserID', $userID);
                    $this->Form->setFormValue('UserSelect', false);

                    // Sign in as the new user.
                    Gdn::userModel()->fireEvent('BeforeSignIn', ['UserID' => $userID]);
                    Gdn::session()->start($userID, true, (bool)$this->Form->getFormValue('RememberMe', c('Garden.SSO.RememberMe', true)));
                    Gdn::userModel()->fireEvent('AfterSignIn');

                    // Send the welcome email.
                    if (c('Garden.Registration.SendConnectEmail', false)) {
                        try {
                            $providerName = $this->Form->getFormValue('ProviderName', $this->Form->getFormValue('Provider', 'Unknown'));
                            $userModel->sendWelcomeEmail($userID, '', 'Connect', ['ProviderName' => $providerName]);
                        } catch (Exception $ex) {
                            // Do nothing if emailing doesn't work.
                        }
                    }

                    // Move along.
                    $this->_setRedirect(Gdn::request()->get('display') === 'popup');
                }
            }
        } // Finished our connection logic.

        // Save the user's choice.
        if ($isPostBack) {
            $passwordHash = new Gdn_PasswordHash();

            if (!$userSelect || $userSelect == 'other') {
                // The user entered a username. Validate it.
                $connectNameEntered = true;
                if (!empty($this->Form->getFormValue('ConnectName'))) {
                    $connectName = $this->Form->getFormValue('ConnectName');
                    $user = false;

                    if (c('Garden.Registration.NameUnique')) {
                        // Check to see if there is already a user with the given name.
                        $user = $userModel->getWhere(['Name' => $connectName])->firstRow(DATASET_TYPE_ARRAY);
                    }

                    if (!$user) {
                        // Using a new username, so validate it.
                        $this->Form->validateRule('ConnectName', 'ValidateUsername');
                    }
                }
            } else {
                // The user selected an existing user.
                $connectNameEntered = false;
                if ($userSelect == 'current') {
                    if (Gdn::session()->UserID == 0) {
                        // This should never happen, but a user could click submit on a stale form.
                        $this->Form->addError('@You were unexpectedly signed out.');
                    } else {
                        $userSelect = Gdn::session()->UserID;
                    }
                }
                $user = $userModel->getID($userSelect, DATASET_TYPE_ARRAY);
            } // End user selection.

            if (isset($user) && $user) {
                // Make sure the user authenticates.
                if ($allowConnect) {
                    // If the user is connecting to their current account, make sure it was intentional.
                    if (intval($user['UserID']) === intval(Gdn::session()->UserID)) {
                        $this->Request->isAuthenticatedPostBack(true);
                    } else {
                        $hasPassword = $this->Form->validateRule('ConnectPassword', 'ValidateRequired', sprintf(t('ValidateRequired'), t('Password')));
                        if ($hasPassword) {
                            // Validate their password.
                            try {
                                $password = $this->Form->getFormValue('ConnectPassword');
                                $name = $this->Form->getFormValue('ConnectName');

                                $passwordChecked = $passwordHash->checkPassword($password, $user['Password'], $user['HashMethod'], $name);
                                Gdn::userModel()->rateLimit((object)$user, $passwordChecked);
                                if (!$passwordChecked) {
                                    if ($connectNameEntered) {
                                        $this->addCredentialErrorToForm('The username you entered has already been taken.');
                                    } else {
                                        $this->addCredentialErrorToForm('The password you entered is incorrect.');
                                    }
                                }
                            } catch (Gdn_UserException $ex) {
                                $this->Form->addError($ex);
                            }
                        } else {
                            // If we have a user match & there is no password.
                            $this->Form->addError(t('UserMatchNeedsPassword'));
                        }
                    }
                } else {
                    $this->Form->addError('The site does not allow you connect with an existing user.', 'UserSelect');
                    $user = null;
                }
            } elseif ($this->Form->errorCount() == 0) {
                // The user doesn't exist so we need to add another user.
                $user = $this->Form->formValues();
                $user['Name'] = $user['ConnectName'] ?? $user['Name'];
                $user['Password'] = randomString(16); // Required field.
                $user['HashMethod'] = 'Random';
                $userID = $userModel->register($user, [
                    'CheckCaptcha' => false,
                    'NoConfirmEmail' => !$userProvidedEmail || !UserModel::requireConfirmEmail(),
                    'SaveRoles' => $saveRolesRegister,
                    'ValidateName' => !$isTrustedProvider,
                ]);
                $user['UserID'] = $userID;

                $this->EventArguments['UserID'] = $userID;
                $this->fireEvent('AfterConnectSave');

                $this->Form->setValidationResults($userModel->validationResults());

                // Send the welcome email.
                if ($userID && c('Garden.Registration.SendConnectEmail', false)) {
                    $providerName = $this->Form->getFormValue('ProviderName', $this->Form->getFormValue('Provider', 'Unknown'));
                    $userModel->sendWelcomeEmail($userID, '', 'Connect', ['ProviderName' => $providerName]);
                }
            }

            // Save the user authentication association.
            if ($this->Form->errorCount() == 0) {
                if (isset($user) && val('UserID', $user)) {
                    $userModel->saveAuthentication([
                        'UserID' => $user['UserID'],
                        'Provider' => $this->Form->getFormValue('Provider'),
                        'UniqueID' => $this->Form->getFormValue('UniqueID'),
                    ]);
                    $this->Form->setFormValue('UserID', $user['UserID']);
                }

                if (!empty($this->Form->getFormValue('UserID'))) {
                    // Sign the user in.
                    Gdn::userModel()->fireEvent('BeforeSignIn', ['UserID' => $this->Form->getFormValue('UserID')]);
                    Gdn::session()->start(
                        $this->Form->getFormValue('UserID'),
                        true,
                        (bool)$this->Form->getFormValue('RememberMe', c('Garden.SSO.RememberMe', true))
                    );
                    Gdn::userModel()->fireEvent('AfterSignIn');

                    // Move along.
                    $this->_setRedirect(Gdn::request()->get('display') === 'popup');
                } else {
                    // This shouldn't happen, but let's display an error to help troubleshoot.
                    throw new Gdn_UserException("There doesn't seem to be a user to sign in. Something went wrong.");
                }
            }
        } // End of user choice processing.

        $this->render();
    }

    /**
     * After sign in, send them along.
     *
     * @since 2.0.0
     * @access protected
     *
     * @param bool $checkPopup
     */
    protected function _setRedirect($checkPopup = false) {
        $url = url($this->getTargetRoute(), true);

        $this->setRedirectTo($url);
        $this->MasterView = 'popup';
        $this->View = 'redirect';

        if ($this->_RealDeliveryType != DELIVERY_TYPE_ALL && $this->deliveryType() != DELIVERY_TYPE_ALL) {
            $this->deliveryMethod(DELIVERY_METHOD_JSON);
            $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        } elseif ($checkPopup || $this->data('CheckPopup')) {
            $this->addDefinition('CheckPopup', true);
        } else {
            redirectTo($this->redirectTo ?: url($this->RedirectUrl));
        }
    }

    /**
     * Default to signIn().
     *
     * @access public
     * @since 2.0.0
     */
    public function index() {
        $this->View = 'SignIn';
        $this->signIn();
    }

    /**
     * Auth via password.
     *
     * @access public
     * @since 2.0.0
     */
    public function password() {
        $this->auth('password');
    }

    /**
     * Auth via default method. Simpler, old version of signIn().
     *
     * Events: SignIn
     *
     * @access public
     * @return void
     */
    public function signIn2() {
        $this->fireEvent("SignIn");
        $this->auth('default');
    }

    /**
     * Good afternoon, good evening, and goodnight.
     *
     * Events: SignOut
     *
     * @access public
     * @since 2.0.0
     *
     * @param string $transientKey (default: "")
     */
    public function signOut($transientKey = "", $override = "0") {
        $this->checkOverride('SignOut', $this->target(), $transientKey);

        if (Gdn::session()->validateTransientKey($transientKey)) {
            $user = Gdn::session()->User;

            $this->EventArguments['SignoutUser'] = $user;
            $this->fireEvent("BeforeSignOut");

            // Sign the user right out.
            Gdn::session()->end();
            $this->setData('SignedOut', true);

            $this->EventArguments['SignoutUser'] = $user;
            $this->fireEvent("SignOut");

            $this->_setRedirect();
        } elseif (!Gdn::session()->isValid()) {
            $this->_setRedirect();
        }

        $target = url($this->target(), true);
        if (!isTrustedDomain($target)) {
            $target = Gdn::router()->getDestination('DefaultController');
        }

        $this->setData('Override', $override);
        $this->setData('Target', $target);
        $this->Leaving = false;
        $this->render();
    }

    /**
     * Sign in process that multiple authentication methods.
     *
     * @param string|false $method
     * @param array|false $arg1
     * @return string Rendered XHTML template.
     */
    public function signIn($method = false, $arg1 = false) {
        $this->canonicalUrl(url('/entry/signin', true));
        if (!$this->Request->isPostBack()) {
            $this->checkOverride('SignIn', $this->target());
        }

        $this->addJsFile('entry.js');
        $this->setData('Title', t('Sign In'));
        // Add open graph description in case a restricted page is shared.
        $this->description(Gdn::config('Garden.Description'));

        $this->Form->addHidden('Target', $this->target());
        $this->Form->addHidden('ClientHour', date('Y-m-d H:00')); // Use the server's current hour as a default.

        // Additional signin methods are set up with plugins.
        $methods = [];

        $this->setData('Methods', $methods);
        $this->setData('FormUrl', url('entry/signin'));

        $this->fireEvent('SignIn');

        if ($this->Form->isPostBack()) {
            $this->Form->validateRule('Email', 'ValidateRequired', sprintf(t('%s is required.'), t(UserModel::signinLabelCode())));
            $this->Form->validateRule('Password', 'ValidateRequired');

            if (!$this->Request->isAuthenticatedPostBack()) {
                $legacyLogin = \Vanilla\FeatureFlagHelper::featureEnabled('legacyEmbedLogin');
                if ($legacyLogin && c('Garden.Embed.Allow')) {
                    Logger::event(
                        'legacy_embed_signin',
                        Logger::INFO,
                        'Signed in using the legacy embed method',
                        [
                            'login' => $this->Form->getFormValue('Email'),
                            Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                        ]
                    );
                } else {
                    $this->Form->addError('Please try again.');
                    Gdn::session()->ensureTransientKey();
                }
            }

            // Check the user.
            if ($this->Form->errorCount() == 0) {
                $email = $this->Form->getFormValue('Email');
                $user = Gdn::userModel()->getByEmail($email);
                if (!$user) {
                    $user = Gdn::userModel()->getByUsername($email);
                }

                if (!$user) {
                    $this->addCredentialErrorToForm('@' . sprintf(t('User not found.'), strtolower(t(UserModel::signinLabelCode()))));
                    Logger::event(
                        'signin_failure',
                        Logger::INFO,
                        'Failed to sign in. User not found: {signin}',
                        [
                            'signin' => $email,
                            Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                        ]
                    );
                    $this->fireEvent('BadSignIn', [
                        'Email' => $email,
                        'Password' => $this->Form->getFormValue('Password'),
                        'Reason' => 'NotFound',
                    ]);
                } else {
                    // Check the password.
                    $passwordHash = new Gdn_PasswordHash();
                    $password = $this->Form->getFormValue('Password');
                    try {
                        $passwordChecked = $passwordHash->checkPassword($password, val('Password', $user), val('HashMethod', $user));

                        // Rate limiting
                        Gdn::userModel()->rateLimit($user);

                        if ($passwordChecked) {
                            // Update weak passwords
                            $hashMethod = val('HashMethod', $user);
                            if ($passwordHash->Weak || ($hashMethod && strcasecmp($hashMethod, 'Vanilla') != 0)) {
                                $pw = $passwordHash->hashPassword($password);
                                Gdn::userModel()->setField(val('UserID', $user), ['Password' => $pw, 'HashMethod' => 'Vanilla']);
                            }

                            Gdn::userModel()->fireEvent('BeforeSignIn', ['UserID' => $user->UserID ?? false]);
                            Gdn::session()->start(val('UserID', $user), true, (bool)$this->Form->getFormValue('RememberMe'));
                            if (!Gdn::session()->checkPermission('Garden.SignIn.Allow')) {
                                $this->Form->addError('ErrorPermission');
                                Gdn::session()->end();
                            } else {
                                $clientHour = $this->Form->getFormValue('ClientHour');
                                $hourOffset = Gdn::session()->User->HourOffset;
                                if (is_numeric($clientHour) && $clientHour >= 0 && $clientHour < 24) {
                                    $hourOffset = $clientHour - date('G', time());
                                }

                                if ($hourOffset != Gdn::session()->User->HourOffset) {
                                    Gdn::userModel()->setProperty(Gdn::session()->UserID, 'HourOffset', $hourOffset);
                                }

                                Gdn::userModel()->fireEvent('AfterSignIn');

                                $this->_setRedirect();
                            }
                        } else {
                            $this->addCredentialErrorToForm('Invalid password.');
                            Logger::event(
                                'signin_failure',
                                Logger::WARNING,
                                '{username} failed to sign in.  Invalid password.',
                                ['InsertName' => $user->Name, Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
                            );
                            $this->fireEvent('BadSignIn', [
                                'Email' => $email,
                                'Password' => $password,
                                'User' => $user,
                                'Reason' => 'Password',
                            ]);
                        }
                    } catch (Gdn_UserException $ex) {
                        $this->Form->addError($ex);
                    }
                }
            }
        } else {
            if ($target = $this->Request->get('Target')) {
                $this->Form->addHidden('Target', $target);
            }
            $this->Form->setValue('RememberMe', true);
        }

        return $this->render();
    }

    /**
     * Calls the appropriate registration method based on the configuration setting.
     *
     * Events: Register
     *
     * @access public
     * @since 2.0.0
     *
     * @param string $invitationCode Unique code given to invited user.
     */
    public function register($invitationCode = '') {
        if (!$this->Request->isPostBack()) {
            $this->checkOverride('Register', $this->target());
        }

        $this->fireEvent("Register");

        $this->Form->setModel($this->UserModel);

        // Define gender dropdown options
        $this->GenderOptions = [
            'u' => t('Unspecified'),
            'm' => t('Male'),
            'f' => t('Female'),
        ];

        // Make sure that the hour offset for new users gets defined when their account is created
        $this->addJsFile('entry.js');

        $this->Form->addHidden('ClientHour', date('Y-m-d H:00')); // Use the server's current hour as a default
        $this->Form->addHidden('Target', $this->target());

        $this->setData('NoEmail', UserModel::noEmail());

        // Sub-dispatch to a specific handler for each registration method
        $registrationHandler = $this->getRegistrationhandler();
        $this->setData('Method', stringBeginsWith($registrationHandler, 'register', true, true));
        $this->$registrationHandler($invitationCode);
    }

    /**
     * Select view/method to be used for registration (from config).
     *
     * @access protected
     * @since 2.3
     * @return string Method name to invoke for registration
     */
    protected function getRegistrationhandler() {
        $registrationMethod = Gdn::config('Garden.Registration.Method');
        if (!in_array($registrationMethod, ['Closed', 'Basic', 'Captcha', 'Approval', 'Invitation', 'Connect'])) {
            $registrationMethod = 'Basic';
        }

        // We no longer support captcha-less registration, both Basic and Captcha require a captcha
        if ($registrationMethod == 'Captcha') {
            $registrationMethod = 'Basic';
        }

        return "register{$registrationMethod}";
    }

    /**
     * Alias of EntryController::getRegistrationHandler
     *
     * @deprecated since 2.3
     * @return string
     * @codeCoverageIgnore
     */
    protected function _registrationView() {
        return $this->getRegistrationHandler();
    }

    /**
     * Registration that requires approval.
     *
     * Events: RegistrationPending
     *
     * @access private
     * @since 2.0.0
     */
    private function registerApproval() {
        $this->View = 'registerapproval';
        Gdn::userModel()->addPasswordStrength($this);

        // If the form has been posted back...
        if ($this->Form->isPostBack()) {
            // Add validation rules that are not enforced by the model
            $this->UserModel->defineSchema();
            $this->UserModel->Validation->applyRule('Name', 'Username', $this->UsernameError);
            $this->UserModel->Validation->applyRule('TermsOfService', 'Required', t('You must agree to the terms of service.'));
            $this->UserModel->Validation->applyRule('Password', 'Required');
            $this->UserModel->Validation->applyRule('Password', 'Strength');
            $this->UserModel->Validation->applyRule('Password', 'Match');
            $this->UserModel->Validation->applyRule('DiscoveryText', 'Required', 'Tell us why you want to join!');
            // $this->UserModel->Validation->applyRule('DateOfBirth', 'MinimumAge');

            $this->fireEvent('RegisterValidation');

            try {
                $values = $this->Form->formValues();
                $values = $this->UserModel->filterForm($values, true);
                unset($values['Roles']);
                $authUserID = $this->UserModel->register($values);
                $this->setData('UserID', $authUserID);
                if (!$authUserID) {
                    $this->Form->setValidationResults($this->UserModel->validationResults());
                } else {
                    // The user has been created successfully, so sign in now.
                    Gdn::session()->start($authUserID);

                    if ($this->Form->getFormValue('RememberMe')) {
                        Gdn::authenticator()->setIdentity($authUserID, true);
                    }

                    $this->EventArguments['AuthUserID'] = $authUserID;
                    $this->EventArguments['Story'] = &$story;
                    $this->fireEvent('RegistrationPending');
                    $this->View = "RegisterThanks"; // Tell the user their application will be reviewed by an administrator.

                    if ($this->deliveryType() !== DELIVERY_TYPE_ALL) {
                        $this->setRedirectTo('/entry/registerthanks');
                    }
                }
            } catch (Exception $ex) {
                $this->Form->addError($ex);
            }
            $this->render();
        } else {
            $this->render();
        }
    }

    /**
     * Captcha-authenticated registration. Used by default.
     *
     * Allows immediate access upon successful registration, and optionally requires
     * email address confirmation.
     *
     * Events: RegistrationSuccessful
     *
     * @access private
     * @since 2.0.0
     */
    private function registerBasic() {
        $this->View = 'registerbasic';
        Gdn::userModel()->addPasswordStrength($this);

        if ($this->Form->isPostBack() === true) {
            // Add validation rules that are not enforced by the model
            $this->UserModel->defineSchema();
            $this->UserModel->Validation->applyRule('Name', 'Username', $this->UsernameError);
            $this->UserModel->Validation->applyRule('TermsOfService', 'Required', t('You must agree to the terms of service.'));
            $this->UserModel->Validation->applyRule('Password', 'Required');
            $this->UserModel->Validation->applyRule('Password', 'Strength');
            $this->UserModel->Validation->applyRule('Password', 'Match');
            // $this->UserModel->Validation->applyRule('DateOfBirth', 'MinimumAge');

            $this->fireEvent('RegisterValidation');

            try {
                $values = $this->Form->formValues();
                $values = $this->UserModel->filterForm($values, true);
                unset($values['Roles']);
                $authUserID = $this->UserModel->register($values);
                $this->setData('UserID', $authUserID);
                if ($authUserID == UserModel::REDIRECT_APPROVE) {
                    $this->Form->setFormValue('Target', '/entry/registerthanks');
                    $this->_setRedirect();

                    return;
                } elseif (!$authUserID) {
                    $this->Form->setValidationResults($this->UserModel->validationResults());
                } else {
                    // The user has been created successfully, so sign in now.
                    Gdn::session()->start($authUserID);

                    if ($this->Form->getFormValue('RememberMe')) {
                        Gdn::authenticator()->setIdentity($authUserID, true);
                    }

                    try {
                        $this->UserModel->sendWelcomeEmail($authUserID, '', 'Register');
                    } catch (Exception $ex) {
                        // Suppress exceptions from bubbling up.
                    }

                    $this->fireEvent('RegistrationSuccessful');

                    // ... and redirect them appropriately
                    $route = $this->getTargetRoute();
                    if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
                        $this->setRedirectTo($route);
                    } else {
                        if ($route !== false) {
                            redirectTo($route);
                        }
                    }
                }
            } catch (Exception $ex) {
                $this->Form->addError($ex);
            }
        }
        $this->render();
    }

    /**
     * Captcha-authenticated registration.
     *
     * Deprecated in favor of 'basic' registration which now also requires
     * captcha authentication.
     *
     * Events: RegistrationSuccessful
     *
     * @deprecated since v2.2.106
     * @see registerBasic
     * @access private
     * @since 2.0.0
     * @codeCoverageIgnore
     */
    private function registerCaptcha() {
        $this->registerBasic();
    }

    /**
     * Connect registration
     *
     * @deprecated since 2.0.18.
     * @codeCoverageIgnore
     */
    private function registerConnect() {
        throw notFoundException();
    }

    /**
     * Registration not allowed.
     *
     * @access private
     * @since 2.0.0
     */
    private function registerClosed() {
        $this->View = 'registerclosed';
        $this->render();
    }

    /**
     * Invitation-only registration. Requires code.
     *
     * @param int $invitationCode
     * @since 2.0.0
     */
    public function registerInvitation($invitationCode = 0) {
        $this->View = 'registerinvitation';
        $this->Form->setModel($this->UserModel);

        // Define gender dropdown options
        $this->GenderOptions = [
            'u' => t('Unspecified'),
            'm' => t('Male'),
            'f' => t('Female'),
        ];

        if (!$this->Form->isPostBack()) {
            $this->Form->setValue('InvitationCode', $invitationCode);
        }

        $invitationModel = new InvitationModel();

        // Look for the invitation.
        $invitation = $invitationModel
            ->getWhere(['Code' => $this->Form->getValue('InvitationCode')])
            ->firstRow(DATASET_TYPE_ARRAY)
        ;

        if (!$invitation) {
            $this->Form->addError('Invitation not found.', 'Code');
        } else {
            if ($expires = val('DateExpires', $invitation)) {
                $expires = Gdn_Format::toTimestamp($expires);
                if ($expires <= time()) {
                }
            }
        }

        $this->Form->addHidden('ClientHour', date('Y-m-d H:00')); // Use the server's current hour as a default
        $this->Form->addHidden('Target', $this->target());

        Gdn::userModel()->addPasswordStrength($this);

        if ($this->Form->isPostBack() === true) {
            $this->InvitationCode = $this->Form->getValue('InvitationCode');
            // Add validation rules that are not enforced by the model
            $this->UserModel->defineSchema();
            $this->UserModel->Validation->applyRule('Name', 'Username', $this->UsernameError);
            $this->UserModel->Validation->applyRule('TermsOfService', 'Required', t('You must agree to the terms of service.'));
            $this->UserModel->Validation->applyRule('Password', 'Required');
            $this->UserModel->Validation->applyRule('Password', 'Strength');
            $this->UserModel->Validation->applyRule('Password', 'Match');

            $this->fireEvent('RegisterValidation');

            try {
                $values = $this->Form->formValues();
                $values = $this->UserModel->filterForm($values, true);
                unset($values['Roles']);
                $authUserID = $this->UserModel->register($values, ['Method' => 'Invitation']);
                $this->setData('UserID', $authUserID);
                if (!$authUserID) {
                    $this->Form->setValidationResults($this->UserModel->validationResults());
                } else {
                    // The user has been created successfully, so sign in now.
                    Gdn::session()->start($authUserID);
                    if ($this->Form->getFormValue('RememberMe')) {
                        Gdn::authenticator()->setIdentity($authUserID, true);
                    }

                    $this->fireEvent('RegistrationSuccessful');

                    // ... and redirect them appropriately
                    $route = $this->getTargetRoute();
                    if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
                        $this->setRedirectTo($route);
                    } else {
                        if ($route !== false) {
                            redirectTo($route);
                        }
                    }
                }
            } catch (Exception $ex) {
                $this->Form->addError($ex);
            }
        } else {
            // Set some form defaults.
            if ($name = val('Name', $invitation)) {
                $this->Form->setValue('Name', $name);
            }

            $this->InvitationCode = $invitationCode;
        }

        // Make sure that the hour offset for new users gets defined when their account is created
        $this->addJsFile('entry.js');
        $this->render();
    }

    /**
     * Display registration thank-you message
     *
     * @since 2.1
     */
    public function registerThanks() {
        $this->CssClass = 'SplashMessage NoPanel';
        $this->setData('_NoMessages', true);
        $this->setData('Title', t('Thank You!'));
        $this->render();
    }

    /**
     * Request password reset.
     *
     * @access public
     * @throws Gdn_UserException When not an authenticated post back.
     * @since 2.0.0
     */
    public function passwordRequest() {
        $this->canonicalUrl(url('/entry/passwordrequest', true));
        if (!$this->UserModel->isEmailUnique() && $this->UserModel->isNameUnique()) {
            Gdn::locale()->setTranslation('Email', t('Username'));
        }

        if ($this->Form->isPostBack() === true) {
            $this->Request->isAuthenticatedPostBack(true);
            $this->Form->validateRule('Email', 'ValidateRequired');

            if ($this->Form->errorCount() == 0) {
                try {
                    $email = $this->Form->getFormValue('Email');
                    if (!$this->UserModel->passwordRequest($email)) {
                        $this->Form->setValidationResults($this->UserModel->validationResults());
                    }
                } catch (Exception $ex) {
                    $this->Form->addError($ex->getMessage());
                }
                if ($this->Form->errorCount() == 0) {
                    $this->Form->addError('Success!');
                    $this->View = 'passwordrequestsent';
                    $this->fireEvent('PasswordRequest', [
                        'Email' => $email,
                    ]);
                }
            } else {
                if ($this->Form->errorCount() == 0) {
                    $this->addCredentialErrorToForm("Couldn't find an account associated with that email/username.");
                }
            }
        }
        $this->render();
    }

    /**
     * Do password reset.
     *
     * @access public
     * @since 2.0.0
     *
     * @param int $userID Unique.
     * @param string $passwordResetKey Authenticate with unique, 1-time code sent via email.
     */
    public function passwordReset($userID = '', $passwordResetKey = '') {
        safeHeader('Referrer-Policy: no-referrer');
        $this->setHeader("Cache-Control", \Vanilla\Web\CacheControlMiddleware::NO_CACHE);

        $session = Gdn::session();

        // Prevent the token from being leaked by referrer!
        $passwordResetKey = trim($passwordResetKey);
        if ($passwordResetKey) {
            $session->stash('passwordResetKey', $passwordResetKey);
            redirectTo("/entry/passwordreset/$userID");
        }

        $passwordResetKey = $session->stash('passwordResetKey', '', false);
        $this->UserModel->addPasswordStrength($this);

        if (!is_numeric($userID)
            || $passwordResetKey == ''
            || hash_equals($this->UserModel->getAttribute($userID, 'PasswordResetKey', ''), $passwordResetKey) === false
        ) {
            $this->Form->addError('Failed to authenticate your password reset request. Try using the reset request form again.');
            Logger::event(
                'password_reset_failure',
                Logger::NOTICE,
                '{username} failed to authenticate password reset request.',
                [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
            );
            $this->fireEvent('PasswordResetFailed', [
                'UserID' => $userID,
            ]);
        }

        $expires = $this->UserModel->getAttribute($userID, 'PasswordResetExpires');
        if ($this->Form->errorCount() === 0 && $expires < time()) {
            $this->Form->addError('@' . t('Your password reset token has expired.', 'Your password reset token has expired. Try using the reset request form again.'));
            Logger::event(
                'password_reset_failure',
                Logger::NOTICE,
                '{username} has an expired reset token.',
                [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
            );
            $this->fireEvent('PasswordResetFailed', [
                'UserID' => $userID,
            ]);
        }

        if ($this->Form->errorCount() == 0) {
            $user = $this->UserModel->getID($userID, DATASET_TYPE_ARRAY);
            if ($user) {
                $user = arrayTranslate($user, ['UserID', 'Name', 'Email']);
                $this->setData('User', $user);
            }

            if ($this->Form->isPostBack() === true) {
                // Backward compatibility fix.
                $confirm = $this->Form->getFormValue('Confirm');
                $passwordMatch = $this->Form->getFormValue('PasswordMatch');
                if ($confirm && !$passwordMatch) {
                    $this->Form->setValue('PasswordMatch', $confirm);
                }

                $this->UserModel->Validation->applyRule('Password', 'Required');
                $this->UserModel->Validation->applyRule('Password', 'Strength');
                $this->UserModel->Validation->applyRule('Password', 'Match');
                $this->UserModel->Validation->validate($this->Form->formValues());
                $this->Form->setValidationResults($this->UserModel->Validation->results());

                $password = $this->Form->getFormValue('Password', '');
                $passwordMatch = $this->Form->getFormValue('PasswordMatch', '');
                if ($password == '') {
                    Logger::event(
                        'password_reset_failure',
                        Logger::NOTICE,
                        'Failed to reset the password for {username}. Password is invalid.',
                        [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
                    );
                } elseif ($password != $passwordMatch) {
                    Logger::event(
                        'password_reset_failure',
                        Logger::NOTICE,
                        'Failed to reset the password for {username}. Passwords did not match.',
                        [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
                    );
                }

                if ($this->Form->errorCount() == 0) {
                    $user = $this->UserModel->passwordReset($userID, $password);
                    Logger::event(
                        'password_reset',
                        Logger::NOTICE,
                        '{username} has reset their password.',
                        [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY]
                    );
                    Gdn::session()->start($user->UserID, true);
                    $this->setRedirectTo('/', false);
                }
            }
        } else {
            $this->setData('Fatal', true);
        }

        $this->addJsFile('entry.js');
        $this->render();
    }

    /**
     * Confirm email address is valid via sent code.
     *
     * @access public
     * @since 2.0.0
     *
     * @param int $userID
     * @param string $emailKey Authenticate with unique, 1-time code sent via email.
     */
    public function emailConfirm($userID, $emailKey = '') {
        $user = $this->UserModel->getID($userID);

        if (!$user) {
            throw notFoundException('User');
        }

        $emailConfirmed = $this->UserModel->confirmEmail($user, $emailKey);
        $this->Form->setValidationResults($this->UserModel->validationResults());

        $userMatch = ($userID === Gdn::session()->UserID) ? true : false;

        if (!$userMatch) {
            Gdn::session()->end();
            redirectTo('/entry/signin');
        }

        if ($emailConfirmed) {
            $userID = val('UserID', $user);
            Gdn::session()->start($userID);
        }

        $this->setData('UserID', $userID);
        $this->setData('EmailConfirmed', $emailConfirmed);
        $this->setData('Email', $user->Email);
        $this->render();
    }

    /**
     * Send email confirmation message to user.
     *
     * @access public
     * @since 2.0.?
     *
     * @param int $userID
     */
    public function emailConfirmRequest($userID = '') {
        if ($userID && !Gdn::session()->checkPermission('Garden.Users.Edit')) {
            $userID = '';
        }

        try {
            $this->UserModel->sendEmailConfirmationEmail($userID);
        } catch (Exception $ex) {
            // Suppress exceptions from bubbling up.
        }
        $this->Form->setValidationResults($this->UserModel->validationResults());

        $this->render();
    }

    /**
     * Does actual de-authentication of a user. Used by signOut().
     *
     * @access public
     * @since 2.0.0
     *
     * @param string $authenticationSchemeAlias
     * @param string $transientKey Unique value to prove intent.
     * @codeCoverageIgnore
     */
    public function leave($authenticationSchemeAlias = 'default', $transientKey = '') {
        deprecated(__FUNCTION__);
        $this->EventArguments['AuthenticationSchemeAlias'] = $authenticationSchemeAlias;
        $this->fireEvent('BeforeLeave');

        // Allow hijacking deauth type
        $authenticationSchemeAlias = $this->EventArguments['AuthenticationSchemeAlias'];

        try {
            $authenticator = Gdn::authenticator()->authenticateWith($authenticationSchemeAlias);
        } catch (Exception $e) {
            $authenticator = Gdn::authenticator()->authenticateWith('default');
        }

        // Only sign the user out if this is an authenticated postback! Start off pessimistic
        $this->Leaving = false;
        $result = Gdn_Authenticator::REACT_RENDER;

        // Build these before doing anything desctructive as they are supposed to have user context
        $logoutResponse = $authenticator->logoutResponse();
        $loginResponse = $authenticator->loginResponse();

        $authenticatedPostbackRequired = $authenticator->requireLogoutTransientKey();
        if (!$authenticatedPostbackRequired || Gdn::session()->validateTransientKey($transientKey)) {
            $result = $authenticator->deauthenticate();
            $this->Leaving = true;
        }

        if ($result == Gdn_Authenticator::AUTH_SUCCESS) {
            $this->View = 'leave';
            $reaction = $logoutResponse;
        } else {
            $this->View = 'auth/' . $authenticator->getAuthenticationSchemeAlias();
            $reaction = $loginResponse;
        }

        switch ($reaction) {
            case Gdn_Authenticator::REACT_RENDER:
                break;

            case Gdn_Authenticator::REACT_EXIT:
                exit();
                break;

            case Gdn_Authenticator::REACT_REMOTE:
                // Render the view, but set the delivery type to VIEW
                $this->_DeliveryType = DELIVERY_TYPE_VIEW;
                break;

            case Gdn_Authenticator::REACT_REDIRECT:
            default:
                // If we're just told to redirect, but not where... try to figure out somewhere that makes sense.
                if ($reaction == Gdn_Authenticator::REACT_REDIRECT) {
                    $route = '/';
                    $target = $this->target();
                    if (!is_null($target)) {
                        $route = $target;
                    }
                } else {
                    $route = $reaction;
                }

                if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
                    $this->setRedirectTo($route);
                } else {
                    if ($route !== false) {
                        redirectTo($route);
                    } else {
                        redirectTo(Gdn::router()->getDestination('DefaultController'));
                    }
                }
                break;
        }
        $this->render();
    }

    /**
     * Get the the redirect URL.
     *
     * @deprecated 2017-06-29
     * @return string
     * @codeCoverageIgnore
     */
    public function redirectTo() {
        deprecated(__FUNCTION__, 'target');

        return $this->getTargetRoute();
    }

    /**
     * Go to requested target() or the default controller if none was set.
     *
     * @access public
     * @since 2.0.0
     *
     * @return string URL.
     */
    protected function getTargetRoute() {
        $target = $this->target();

        return $target == '' ? Gdn::router()->getDestination('DefaultController') : $target;
    }

    /**
     * Set where to go after signin.
     *
     * @param string|false $target Where we're requested to go to.
     * @return string URL to actually go to (validated & safe).
     */
    public function target($target = false) {
        if ($target === false) {
            $target = $this->Form->getFormValue('Target', false);
            if (!$target) {
                $target = $this->Request->get('Target', $this->Request->get('target', '/'));
                if (empty($target)) {
                    $target = '/';
                }
            }
        }
        $target = url($target, true);

        // Never redirect back to sign in/out pages.
        if (($this->Request->getHost() === parse_url($target, PHP_URL_HOST) &&
            preg_match('`/entry/(?:signin|signout|autosignedout)($|\?)`i', $target)) ||
            !isTrustedDomain($target)
        ) {
            $target = url('/', true);
        }

        return $target;
    }
}
