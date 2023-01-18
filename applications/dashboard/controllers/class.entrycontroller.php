<?php
/**
 * Manages users manually authenticating (signing in).
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Utility\DebugUtils;
use Vanilla\Logger;
use Vanilla\Utility\ModelUtils;

/**
 * Handles /entry endpoint.
 */
class EntryController extends Gdn_Controller implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array Models to include. */
    public $Uses = ["Database", "Form", "UserModel"];

    /** @var Gdn_Form */
    public $Form;

    /** @var UserModel */
    public $UserModel;

    /** @var ProfileFieldModel */
    public $profileFieldModel;

    /** @var string Reusable username requirement error message. */
    public $UsernameError = "";

    /** @var string Place to store DeliveryType. */
    protected $_RealDeliveryType;

    /**
     * @var bool
     */
    private $connectSynchronizeErrors = false;

    /**
     * Setup error message & override MasterView for popups.
     *
     * @param ProfileFieldModel $profileFieldModel
     * @since 2.0.0
     * @access public
     */
    public function __construct(ProfileFieldModel $profileFieldModel)
    {
        parent::__construct();
        $this->internalMethods[] = "target";

        // Set error message here so it can run thru t()
        $this->UsernameError = t(
            "UsernameError",
            "Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long."
        );

        // Allow use of a master popup template for easier theming.
        if (Gdn::request()->get("display") === "popup") {
            $this->MasterView = "popup";
        }
        $this->logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
        $this->profileFieldModel = $profileFieldModel;
    }

    /**
     * Include JS and CSS used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize()
    {
        $this->Head = new HeadModule($this);
        $this->Head->addTag("meta", ["name" => "robots", "content" => "noindex"]);

        $this->addJsFile("jquery.js");
        $this->addJsFile("jquery.form.js");
        $this->addJsFile("jquery.popup.js");
        $this->addJsFile("jquery.gardenhandleajaxform.js");
        $this->addJsFile("global.js");

        $this->addCssFile("style.css");
        $this->addCssFile("vanillicon.css", "static");
        parent::initialize();
        Gdn_Theme::section("Entry");

        if ($this->UserModel->isNameUnique() && !$this->UserModel->isEmailUnique()) {
            $this->setData("RecoverPasswordLabelCode", "Enter your username to continue.");
        } else {
            $this->setData("RecoverPasswordLabelCode", "Enter your email to continue.");
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
    public function addCredentialErrorToForm($translationCode)
    {
        if (c("Garden.PrivateCommunity", false)) {
            $this->Form->addError("Bad login, double-check your credentials and try again.");
        } else {
            $this->Form->addError($translationCode);
        }
    }

    /**
     * Authenticate the user attempting to sign in.
     *
     * Events: BeforeAuth
     *
     * @param string $authenticationSchemeAlias Type of authentication we're attempting.
     * @since 2.0.0
     * @access public
     *
     */
    public function auth($authenticationSchemeAlias = "default")
    {
        $this->EventArguments["AuthenticationSchemeAlias"] = $authenticationSchemeAlias;
        $this->fireEvent("BeforeAuth");

        // Allow hijacking auth type
        $authenticationSchemeAlias = $this->EventArguments["AuthenticationSchemeAlias"];

        // Attempt to set authenticator with requested method or fallback to default
        try {
            $authenticator = Gdn::authenticator()->authenticateWith($authenticationSchemeAlias);
        } catch (Exception $e) {
            $authenticator = Gdn::authenticator()->authenticateWith("default");
        }

        // Set up controller
        $this->View = "auth/" . $authenticator->getAuthenticationSchemeAlias();
        $this->Form->setModel($this->UserModel);
        $this->Form->addHidden("ClientHour", date("Y-m-d H:00")); // Use the server's current hour as a default.

        $target = $this->target();

        $this->Form->addHidden("Target", $target);

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
                $this->addJsFile("entry.js");
                $reaction = $authenticator->loginResponse();
                if ($this->Form->isPostBack()) {
                    $this->addCredentialErrorToForm("ErrorCredentials");
                    $this->logger->warning("{username} failed to sign in. Some or all credentials were missing.", [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                        "event" => "signin_failure",
                    ]);
                }
                break;

            // All information is present, authenticate
            case Gdn_Authenticator::MODE_VALIDATE:
                // Attempt to authenticate.
                try {
                    if (!$this->Request->isAuthenticatedPostBack() && !c("Garden.Embed.Allow")) {
                        $this->Form->addError("Please try again.");
                        $reaction = $authenticator->failedResponse();
                    } else {
                        $authenticationResponse = $authenticator->authenticate();

                        $userInfo = [];
                        $userEventData = array_merge(
                            [
                                "UserID" => Gdn::session()->UserID,
                                "Payload" => val("HandshakeResponse", $authenticator, false),
                            ],
                            $userInfo
                        );

                        Gdn::authenticator()->trigger($authenticationResponse, $userEventData);
                        switch ($authenticationResponse) {
                            case Gdn_Authenticator::AUTH_PERMISSION:
                                $this->Form->addError("ErrorPermission");
                                $this->logger->warning("{username} failed to sign in. Permission denied.", [
                                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                                    "event" => "signin_failure",
                                ]);
                                $reaction = $authenticator->failedResponse();
                                break;

                            case Gdn_Authenticator::AUTH_DENIED:
                                $this->addCredentialErrorToForm("ErrorCredentials");
                                $this->logger->warning("{username} failed to sign in. Authentication denied.", [
                                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                                    "event" => "signin_falure",
                                ]);
                                $reaction = $authenticator->failedResponse();
                                break;

                            case Gdn_Authenticator::AUTH_INSUFFICIENT:
                                // Unable to comply with auth request, more information is needed from user.
                                $this->logger->warning(
                                    "{username} failed to sign in. More information needed from user.",
                                    [Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY, "event" => "signin_failure"]
                                );
                                $this->addCredentialErrorToForm("ErrorInsufficient");

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

                exit();
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
                        redirectTo(Gdn::router()->getDestination("DefaultController"));
                    }
                }
                break;
        }

        $this->setData("SendWhere", "/entry/auth/{$authenticationSchemeAlias}");
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
    protected function checkOverride($type, $target, $transientKey = null)
    {
        if (!$this->Request->get("override", true)) {
            return;
        }

        $provider = Gdn_AuthenticationProviderModel::getDefault();
        if (!$provider) {
            return;
        }

        $this->EventArguments["Target"] = $target;
        $this->EventArguments["DefaultProvider"] = &$provider;
        $this->EventArguments["TransientKey"] = $transientKey;
        $this->fireEvent("Override{$type}");

        $url = $provider[$type . "Url"];
        if ($url) {
            switch ($type) {
                case "Register":
                case "SignIn":
                    // When the other page comes back it needs to go through /sso to force a sso check.
                    $target = "/sso?target=" . urlencode($target);
                    break;
                case "SignOut":
                    $cookie = c("Garden.Cookie.Name");
                    if (strpos($url, "?") === false) {
                        $url .= "?vfcookie=" . urlencode($cookie);
                    } else {
                        $url .= "&vfcookie=" . urlencode($cookie);
                    }

                    // Check to sign out here.
                    $signedOut = !Gdn::session()->isValid();
                    if (
                        !$signedOut &&
                        (Gdn::session()->validateTransientKey($transientKey) || $this->Form->isPostBack())
                    ) {
                        Gdn::session()->end();
                        $signedOut = true;
                    }

                    // Sign out is a bit of a tricky thing so we configure the way it works.
                    $signoutType = c("Garden.SSO.Signout");
                    switch ($signoutType) {
                        case "redirect-only":
                            // Just redirect to the url.
                            break;
                        case "post-only":
                            $this->setData("Method", "POST");
                            break;
                        case "post":
                            // Post to the url after signing out here.
                            if (!$signedOut) {
                                return;
                            }
                            $this->setData("Method", "POST");
                            break;
                        case "none":
                            return;
                        case "redirect":
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

            $url = str_ireplace("{target}", rawurlencode(url($target, true)), $url);

            if (
                ($this->deliveryType() == DELIVERY_TYPE_ALL && strcasecmp($this->data("Method"), "POST") != 0) ||
                DebugUtils::isTestMode()
            ) {
                redirectTo(url($url, true), 302, false);
            } else {
                $this->setData("Url", $url);
                $this->render("Redirect", "Utility");
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
     * @param string|null $method Used to register multiple providers on ConnectData event.
     * @since 2.0.0
     * @access public
     *
     */
    public function connect($method)
    {
        // Basic page setup.
        $this->addJsFile("entry.js");
        $this->View = "connect";
        $this->addDefinition(
            "Choose a name to identify yourself on the site.",
            t("Choose a name to identify yourself on the site.")
        );
        $this->setHeader(self::HEADER_CACHE_CONTROL, self::NO_CACHE);
        $this->setHeader("Vary", self::VARY_COOKIE);
        // Determine what step in the process we're at.
        $isPostBack = $this->isConnectPostBack();
        $userSelect = $this->Form->getFormValue("UserSelect");

        $nameUnique = c("Garden.Registration.NameUnique", true);
        $emailUnique = c("Garden.Registration.EmailUnique", true);
        $autoConnect = c("Garden.Registration.AutoConnect");

        /**
         * When a user is connecting through SSO she is prompted to choose a username.
         * If she chooses an existing username, she is prompted to enter the password to claim it.
         * Setting AllowConnect = false disables that workflow, forcing the user to choose a unique username.
         */
        $allowConnect = c("Garden.Registration.AllowConnect", true);
        $this->setData("AllowConnect", $allowConnect);
        $this->setData("HidePassword", true);
        $this->addDefinition("AllowConnect", $allowConnect);
        $stashID = $this->Request->get("stashID");

        if (!$isPostBack) {
            // Initialize data array that can be set by a plugin.
            $data = [
                "Provider" => "",
                "ProviderName" => "",
                "UniqueID" => "",
                "FullName" => "",
                "Name" => "",
                "Email" => "",
                "Photo" => "",
                "Target" => $this->target(),
                "StashID" => $stashID,
            ];
            $this->Form->setData($data);
            $this->Form->addHidden("Target", $this->Request->get("Target", "/"));
        } else {
            // Disallow some sensitive fields unless the provider puts it in.
            $this->Form->removeFormValue("UserID");
        }

        // SSO providers can check to see if they are being used and modify the data array accordingly.
        $this->EventArguments = [$method];

        // Filter the form data for users.
        // SSO plugins must reset validated data each postback.
        $currentData = $this->Form->formValues();
        $filteredData = Gdn::userModel()->filterForm($currentData, true);
        $filteredData = array_replace($filteredData, arrayTranslate($currentData, ["TransientKey", "hpt"]));
        unset($filteredData["Roles"], $filteredData["RoleID"], $filteredData["RankID"]);
        $this->Form->formValues($filteredData);

        // Fire ConnectData event & error handling.
        try {
            // Where your SSO plugin does magic.
            $this->EventArguments["Form"] = $this->Form;
            $this->fireEvent("ConnectData");
            $this->fireEvent("AfterConnectData");
        } catch (Gdn_UserException $ex) {
            // Your SSO magic said no.
            $this->Form->addError($ex);

            $this->render("ConnectError");
            return;
        } catch (Exception $ex) {
            // Your SSO magic blew up.
            if (debug()) {
                $this->Form->addError($ex);
            } else {
                $this->Form->addError("There was an error fetching the connection data.");
            }

            $this->render("ConnectError");
            return;
        }

        /*We have established connection successfully with our sso provider we need to process
         any custom profile fields accordingly received as part of the sso data*/
        $this->processConnectProfileFields();

        $emailVerified = $this->Form->getFormValue("Verified", null);

        if (!$this->data("Verified")) {
            // Whatever event handler catches this must set the data 'Verified' = true
            // to prevent a random site from connecting without credentials.
            // This must be done EVERY postback and is VERY important.
            throw new Gdn_UserException(t("The connection data has not been verified."));
        }

        // If we allow autoConnect & the SSO provider didn't supply an email address, then panick because this is a vulnerability.
        if ($autoConnect && !$this->Form->getFormValue("Email")) {
            throw new Gdn_UserException("Unable to auto-connect because SSO did not provide an email address.");
        }

        $userModel = $this->UserModel;

        // Find an existing user associated with this provider & uniqueid.
        $auth =
            $userModel->getAuthentication(
                $this->Form->getFormValue("UniqueID"),
                $this->Form->getFormValue("Provider")
            ) ?:
            [];
        $userID = $auth["UserID"] ?? false;
        $alreadyConnected = !empty($userID);

        // Hide form controls for already connected users.
        if ($alreadyConnected) {
            $this->setData("NoConnectName", true);
            $this->setData("HidePassword", true);
        }

        // Allow a provider to not send an email address but require one be manually entered.
        $userProvidedEmail = false;
        // If we require an email address.
        if (!UserModel::noEmail() && !$alreadyConnected) {
            $emailProvided = $this->Form->getFormValue("Email");
            $emailRequested = $this->Form->getFormValue("EmailVisible");
            if (!$emailProvided || $emailRequested) {
                $this->Form->setFormValue("EmailVisible", true);
                $this->Form->addHidden("EmailVisible", true);

                if ($isPostBack && !empty($currentData["Email"])) {
                    $this->Form->setFormValue("Email", $currentData["Email"]);
                    $userProvidedEmail = true;
                }
            }
            if ($isPostBack && $emailRequested) {
                $this->Form->validateRule("Email", "ValidateRequired");
                $this->Form->validateRule("Email", "ValidateEmail");
            }
        } else {
            // Hide the email field for already connected users or when there are no emails.
            $this->Form->setFormValue("EmailVisible", false);
        }

        // Allow a provider to not send a username.
        if (
            $isPostBack &&
            !$alreadyConnected &&
            empty($this->Form->getFormValue("Name")) &&
            empty($this->Form->getFormValue("UserSelect"))
        ) {
            $this->Form->validateRule("ConnectName", "ValidateRequired");
            $this->Form->validateRule("ConnectName", "ValidateUsername");
        }

        // Make sure the minimum required data has been provided by the connection.
        if (!$this->Form->getFormValue("Provider")) {
            $this->Form->addError("ValidateRequired", t("Provider"));
        }
        if (!$this->Form->getFormValue("UniqueID")) {
            $this->Form->addError("ValidateRequired", t("UniqueID"));
        }

        // If we've accrued errors, stop here and show them.
        if ($this->Form->errorCount() > 0) {
            $this->render();

            return;
        }

        $isTrustedProvider = $this->data("Trusted");
        $roles = $this->Form->getFormValue("Roles", $this->Form->getFormValue("roles", null));

        // Check if we need to sync roles
        if (($isTrustedProvider || c("Garden.SSO.SyncRoles")) && $roles !== null) {
            $saveRoles = $saveRolesRegister = true;

            // Translate the role names to IDs.
            $roles = RoleModel::getByName($roles);
            $roleIDs = array_keys($roles);

            // Ensure user has at least one role.
            if (empty($roleIDs)) {
                $roleIDs = $this->UserModel->newUserRoleIDs();
            }

            // Allow role syncing to only happen on first connect.
            if (c("Garden.SSO.SyncRolesBehavior") === "register") {
                $saveRoles = false;
            }

            $this->Form->setFormValue("RoleID", $roleIDs);
        } else {
            $saveRoles = false;
            $saveRolesRegister = false;
        }

        // The user is already in the UserAuthentication table
        if ($userID) {
            // Update their info.
            $this->Form->setFormValue("UserID", $userID);
            $user = $this->syncConnectUser($userID, $this->Form->formValues(), $saveRoles, $isTrustedProvider);
            if ($user === false) {
                return;
            }

            // Always save the attributes because they may contain authorization information.
            if ($attributes = $this->Form->getFormValue("Attributes")) {
                $userModel->saveAttribute($userID, $attributes);
            }

            // Sign the user in.
            Gdn::userModel()->fireEvent("BeforeSignIn", ["UserID" => $userID]);
            Gdn::session()->start(
                $userID,
                true,
                (bool) $this->Form->getFormValue("RememberMe", c("Garden.SSO.RememberMe", true))
            );
            Gdn::userModel()->fireEvent("AfterSignIn");

            // Send them on their way.
            $this->_setRedirect(Gdn::request()->get("display") === "popup");

            // If a name or email has been provided
        } elseif (
            $this->Form->getFormValue("Name") ||
            $this->Form->getFormValue("ConnectName") ||
            $this->Form->getFormValue("Email")
        ) {
            // Decide how to handle our first time connecting.

            // Decide which name to search for.
            if ($isPostBack && $this->Form->getFormValue("ConnectName")) {
                $searchName = $this->Form->getFormValue("ConnectName");
            } else {
                $searchName = $this->Form->getFormValue("Name");
            }

            // Find existing users that match the name or email of the connection.
            // First, discover if we have search criteria.
            $search = false;
            $existingUsers = [];
            if ($searchName && $nameUnique) {
                $userModel->SQL->orWhere("Name", $searchName);
                $search = true;
            }
            if ($this->Form->getFormValue("Email") && ($emailUnique || $autoConnect)) {
                $userModel->SQL->orWhere("Email", $this->Form->getFormValue("Email"));
                $search = true;
            }
            if (is_numeric($userSelect)) {
                $userModel->SQL->orWhere("UserID", $userSelect);
                $search = true;
            }
            // Now do the search if we found some criteria.
            if ($search) {
                $existingUsers = $userModel->getWhere()->resultArray();
            }

            // Get the email and decide if we can safely find a match.
            $submittedEmail = $this->Form->getFormValue("Email");
            $canMatchEmail = strlen($submittedEmail) > 0 && !UserModel::noEmail();

            // Check to automatically link the user.
            if ((forceBool($emailVerified) || ($emailVerified === null && $autoConnect)) && count($existingUsers) > 0) {
                if ($isPostBack && $this->Form->getFormValue("ConnectName")) {
                    $this->Form->setFormValue("Name", $this->Form->getFormValue("ConnectName"));
                }

                if ($canMatchEmail) {
                    // Check each existing user for an exact email match.
                    foreach ($existingUsers as $row) {
                        if (strcasecmp($submittedEmail, $row["Email"]) === 0) {
                            // Add the UserID to the form, then get the unified user data set from it.
                            $userID = $row["UserID"];
                            $this->Form->setFormValue("UserID", $userID);

                            // User synchronization.
                            $user = $this->syncConnectUser(
                                $userID,
                                $this->Form->formValues(),
                                $saveRoles,
                                $isTrustedProvider
                            );
                            if ($user === false) {
                                return;
                            }

                            // Always save the attributes because they may contain authorization information.
                            if ($attributes = $this->Form->getFormValue("Attributes")) {
                                $userModel->saveAttribute($userID, $attributes);
                            }

                            // Save the user authentication association.
                            $userModel->saveAuthentication([
                                "UserID" => $userID,
                                "Provider" => $this->Form->getFormValue("Provider"),
                                "UniqueID" => $this->Form->getFormValue("UniqueID"),
                            ]);

                            // Sign the user in.
                            Gdn::userModel()->fireEvent("BeforeSignIn", ["UserID" => $userID]);
                            Gdn::session()->start(
                                $userID,
                                true,
                                (bool) $this->Form->getFormValue("RememberMe", c("Garden.SSO.RememberMe", true)),
                                $stashID
                            );
                            Gdn::userModel()->fireEvent("AfterSignIn");
                            $this->_setRedirect(Gdn::request()->get("display") === "popup");
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
                if ($emailUnique && $canMatchEmail && $userRow["Email"] == $submittedEmail) {
                    // An email match overrules any other options.
                    $emailFound = $userRow;
                    break;
                }

                // Detect a simple name match.
                if ($userRow["Name"] == $this->Form->getFormValue("Name")) {
                    $nameFound = $userRow;
                }

                // Detect if we have a match on the current user session.
                if ($currentUserID > 0 && $userRow["UserID"] == $currentUserID) {
                    unset($existingUsers[$index]);
                    $currentUserFound = true;
                }
            }

            // Handle special cases for what we matched on.
            if (isset($emailFound)) {
                // The email address was found and can be the only user option.
                $existingUsers = [$userRow];
                $this->setData("NoConnectName", true);
            } elseif (isset($currentUserFound)) {
                // If we're already logged in to Vanilla, assume that's an option we want.
                $existingUsers = array_merge(
                    ["UserID" => "current", "Name" => sprintf(t("%s (Current)"), Gdn::session()->User->Name)],
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
            $this->setData("ExistingUsers", $existingUsers);
            if (!empty($existingUsers)) {
                $this->setData("HidePassword", false);
            }

            // Validate our email address if we have one.
            if (UserModel::noEmail()) {
                $emailValid = true;
            } else {
                $emailValid = validateRequired($this->Form->getFormValue("Email"));
            }

            // Set some nice variable names for logic clarity.
            $noMatches = !is_array($existingUsers) || count($existingUsers) == 0;
            $didNotPickUser = !$userSelect || $userSelect == "other";
            $haveName = $this->Form->getFormValue("Name");

            // Should we create a new user?
            //only if we don't have custom profile fields we will automatically register and sign in our user here,
            //otherwise, we'll apply some more validation rules and do the registration in postBack
            if ($didNotPickUser && $haveName && $emailValid && $noMatches && !$this->hasRegistrationCustomFields()) {
                // Create the user.
                $registerOptions = [
                    "CheckCaptcha" => false,
                    "ValidateEmail" => false,
                    "NoConfirmEmail" => !$userProvidedEmail || !UserModel::requireConfirmEmail(),
                    "SaveRoles" => $saveRolesRegister,
                    "ValidateName" => !$isTrustedProvider,
                ];

                $user = $this->Form->formValues();
                $user["Password"] = randomString(16); // Required field.
                $user["HashMethod"] = "Random";
                $user["Source"] = $this->Form->getFormValue("Provider");
                $user["SourceID"] = $this->Form->getFormValue("UniqueID");
                $user["Attributes"] = $this->Form->getFormValue("Attributes", null);
                $user["Email"] = $this->Form->getFormValue("ConnectEmail", $this->Form->getFormValue("Email", null));
                $user["Name"] = $this->Form->getFormValue("ConnectName", $this->Form->getFormValue("Name", null));
                $userID = $userModel->register($user, $registerOptions);

                $user["UserID"] = $userID;

                $this->EventArguments["UserID"] = $userID;
                $this->fireEvent("AfterConnectSave");

                $this->Form->setValidationResults($userModel->validationResults());

                // The SPAM filter was likely triggered. Send the registration for approval, add some generic "reason" text.
                if ($userID === false && val("DiscoveryText", $this->Form->validationResults())) {
                    unset($user["UserID"]);
                    $user["DiscoveryText"] = sprintft(t("SSO connection (%s)"), $method);
                    $userModel->Validation->reset();
                    $userID = $userModel->register($user, $registerOptions);

                    if ($userID === UserModel::REDIRECT_APPROVE) {
                        $this->Form->setFormValue("Target", "/entry/registerthanks");
                        $this->_setRedirect();

                        return;
                    }

                    $this->Form->setValidationResults($userModel->validationResults());
                    $user["UserID"] = $userID;
                }

                // Save the association to the new user.
                if ($userID) {
                    $userModel->saveAuthentication([
                        "UserID" => $userID,
                        "Provider" => $this->Form->getFormValue("Provider"),
                        "UniqueID" => $this->Form->getFormValue("UniqueID"),
                    ]);

                    $this->Form->setFormValue("UserID", $userID);
                    $this->Form->setFormValue("UserSelect", false);

                    // Sign in as the new user.
                    Gdn::userModel()->fireEvent("BeforeSignIn", ["UserID" => $userID]);
                    Gdn::session()->start(
                        $userID,
                        true,
                        (bool) $this->Form->getFormValue("RememberMe", c("Garden.SSO.RememberMe", true)),
                        $stashID
                    );
                    Gdn::userModel()->fireEvent("AfterSignIn");

                    // Send the welcome email.
                    if (c("Garden.Registration.SendConnectEmail", false)) {
                        try {
                            $providerName = $this->Form->getFormValue(
                                "ProviderName",
                                $this->Form->getFormValue("Provider", "Unknown")
                            );
                            $userModel->sendWelcomeEmail($userID, "", "Connect", ["ProviderName" => $providerName]);
                        } catch (Exception $ex) {
                            // Do nothing if emailing doesn't work.
                        }
                    }

                    // Move along.
                    $this->_setRedirect(Gdn::request()->get("display") === "popup");
                }
            }
        } // Finished our connection logic.

        // Save the user's choice.
        if ($isPostBack) {
            $passwordHash = new Gdn_PasswordHash();

            if (!$userSelect || $userSelect == "other") {
                // The user entered a username. Validate it.
                $connectNameEntered = true;
                if (!empty($this->Form->getFormValue("ConnectName"))) {
                    $connectName = $this->Form->getFormValue("ConnectName");
                    $user = false;

                    if (c("Garden.Registration.NameUnique")) {
                        // Check to see if there is already a user with the given name.
                        $user = $userModel->getWhere(["Name" => $connectName])->firstRow(DATASET_TYPE_ARRAY);
                    }

                    if (!$user) {
                        // Using a new username, so validate it.
                        $this->Form->validateRule("ConnectName", "ValidateUsername");
                    }
                }
            } else {
                // The user selected an existing user.
                $connectNameEntered = false;
                if ($userSelect == "current") {
                    if (Gdn::session()->UserID == 0) {
                        // This should never happen, but a user could click submit on a stale form.
                        $this->Form->addError("@You were unexpectedly signed out.");
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
                    if (intval($user["UserID"]) === intval(Gdn::session()->UserID)) {
                        $this->Request->isAuthenticatedPostBack(true);
                    } else {
                        $hasPassword = $this->Form->validateRule(
                            "ConnectPassword",
                            "ValidateRequired",
                            sprintf(t("ValidateRequired"), t("Password"))
                        );
                        if ($hasPassword) {
                            // Validate their password.
                            try {
                                $password = $this->Form->getFormValue("ConnectPassword");
                                $name = $this->Form->getFormValue("ConnectName");

                                $passwordChecked = $passwordHash->checkPassword(
                                    $password,
                                    $user["Password"],
                                    $user["HashMethod"],
                                    $name
                                );
                                Gdn::userModel()->rateLimit((object) $user, $passwordChecked);
                                if (!$passwordChecked) {
                                    if ($connectNameEntered) {
                                        $this->addCredentialErrorToForm(
                                            "The username you entered has already been taken."
                                        );
                                    } else {
                                        $this->addCredentialErrorToForm("The password you entered is incorrect.");
                                    }
                                } else {
                                    // Update their info.
                                    $user = $this->syncConnectUser(
                                        $user["UserID"],
                                        $this->Form->formValues(),
                                        $saveRoles,
                                        $isTrustedProvider
                                    );
                                    if ($user === false) {
                                        return;
                                    }
                                }
                            } catch (Gdn_UserException $ex) {
                                $this->Form->addError($ex);
                            }
                        } else {
                            // If we have a user match & there is no password.
                            $this->Form->addError(t("UserMatchNeedsPassword"));
                        }
                    }
                } else {
                    $this->Form->addError("The site does not allow you connect with an existing user.", "UserSelect");
                    $user = null;
                }
            } elseif ($this->Form->errorCount() == 0) {
                //if we have custom profile fields we'll apply validation rules on them
                if ($this->hasCustomProfileFields()) {
                    $this->applyValidationOnCustomProfileFields(true);
                }

                // The user doesn't exist so we need to add another user.
                $user = $this->Form->formValues();
                $user["Name"] = $user["ConnectName"] ?? ($user["Name"] ?? "");
                $user["Password"] = randomString(16); // Required field.
                $user["HashMethod"] = "Random";

                $userID = $userModel->register($user, [
                    "CheckCaptcha" => false,
                    "NoConfirmEmail" => !$userProvidedEmail || !UserModel::requireConfirmEmail(),
                    "SaveRoles" => $saveRolesRegister,
                    "ValidateName" => !$isTrustedProvider,
                ]);
                $user["UserID"] = $userID;

                $this->EventArguments["UserID"] = $userID;
                $this->fireEvent("AfterConnectSave");

                $this->Form->setValidationResults($userModel->validationResults());

                // Send the welcome email.
                if ($userID && c("Garden.Registration.SendConnectEmail", false)) {
                    $providerName = $this->Form->getFormValue(
                        "ProviderName",
                        $this->Form->getFormValue("Provider", "Unknown")
                    );
                    $userModel->sendWelcomeEmail($userID, "", "Connect", ["ProviderName" => $providerName]);
                }
            }

            // Save the user authentication association.
            if ($this->Form->errorCount() == 0) {
                if (isset($user) && val("UserID", $user)) {
                    $userModel->saveAuthentication([
                        "UserID" => $user["UserID"],
                        "Provider" => $this->Form->getFormValue("Provider"),
                        "UniqueID" => $this->Form->getFormValue("UniqueID"),
                    ]);
                    $this->Form->setFormValue("UserID", $user["UserID"]);
                }

                if (!empty($this->Form->getFormValue("UserID"))) {
                    // Sign the user in.
                    Gdn::userModel()->fireEvent("BeforeSignIn", ["UserID" => $this->Form->getFormValue("UserID")]);
                    Gdn::session()->start(
                        $this->Form->getFormValue("UserID"),
                        true,
                        (bool) $this->Form->getFormValue("RememberMe", c("Garden.SSO.RememberMe", true)),
                        $stashID
                    );
                    Gdn::userModel()->fireEvent("AfterSignIn");

                    //if we have custom profile fields, we need to update user meta with those
                    if ($this->hasCustomProfileFields() && !empty($this->Form->formValues()["Profile"])) {
                        $this->updateUserCustomProfileFields($userID, $this->Form->formValues()["Profile"]);
                    }

                    // Move along.
                    $this->_setRedirect(Gdn::request()->get("display") === "popup");
                } else {
                    // This shouldn't happen, but let's display an error to help troubleshoot.
                    throw new Gdn_UserException("There doesn't seem to be a user to sign in. Something went wrong.");
                }
            }
        } // End of user choice processing.

        $this->render();
    }

    /**
     * Process the profileFields received via SSO.
     */
    private function processConnectProfileFields(): void
    {
        if (!$this->hasCustomProfileFields()) {
            // There are no configured profile fields at all.
            return;
        }
        $isPostBack = $this->isConnectPostBack();

        //We need to process the fields into the format required to render the page
        $profileFields =
            $this->profileFieldModel->getProfileFields([
                "enabled" => 1,
            ]) ?? [];
        $formData = $this->Form->formValues();

        if (empty($formData)) {
            // We have no data from SSO to process.
            return;
        }

        $ssoProfileFields = $nonVisibleFields = [];
        foreach ($profileFields as $profileField) {
            if (empty($formData[$profileField["apiName"]])) {
                // The SSO data did not contain this profile field so there is no processing to do.
                continue;
            }

            // Hidden and Internal fields shouldn't be shown on registration form for users
            $notVisible =
                $profileField["visibility"] === ProfileFieldModel::VISIBILITY_INTERNAL ||
                $profileField["registrationOptions"] === ProfileFieldModel::REGISTRATION_HIDDEN;
            if ($notVisible && in_array($profileField["formType"], ["dropdown", "tokens"])) {
                // In this case we need to validate that the values coming in is one of the values available in our options else discard them
                $dropdownOptions = json_decode($profileField["dropdownOptions"], true);
                $currentValues = is_array($formData[$profileField["apiName"]])
                    ? $formData[$profileField["apiName"]]
                    : [$formData[$profileField["apiName"]]];
                $properValues = array_intersect($currentValues, $dropdownOptions);

                if (empty($properValues)) {
                    // We can skip this value because there were no valid values.
                    $this->Form->removeFormValue($profileField["apiName"]);
                    continue;
                }

                if ($isPostBack && $profileField["formType"] == "tokens") {
                    // TODO: Check back on this.
                    $formData[$profileField["apiName"]] = $this->convertArrayToTokenValue(
                        $properValues,
                        $dropdownOptions
                    );
                } else {
                    $formData[$profileField["apiName"]] = $properValues;
                }
            }
            if (!$isPostBack && $notVisible) {
                // for syncConnectUser data, who already exists
                $nonVisibleFields[$profileField["apiName"]] = $formData[$profileField["apiName"]];
            }

            // If it's an internal field or hidden Field we don't show them on registration form but patch them through on post submission,
            // otherwise we populate the fields for the user to modify
            if (($isPostBack && $notVisible) || (!$isPostBack && !$notVisible)) {
                $ssoProfileFields[$profileField["apiName"]] = $formData[$profileField["apiName"]];
            }

            // Always remove profile fields from the data because we don't want them to conflict with normal user fields.
            $this->Form->removeFormValue($profileField["apiName"]);
        }
        if ($isPostBack) {
            // Merge in hidden fields and existing fields that might not have been sent into the form.
            $ssoProfileFields = array_merge($formData["Profile"] ?? [], $ssoProfileFields);
        }
        $this->Form->setFormValue("Profile", $ssoProfileFields);
        $this->setData("nonVisibleFields", $nonVisibleFields);
    }

    /**
     * Synchronize a user's information from SSO to an existing user.
     *
     * Note: This method should ONLY be called from within `connect()`. This method will render the current view if there
     * is an error as a kludge around `connect()`'s deep code paths.
     *
     * @param int $userID The ID of the user to synchronize.
     * @param array $data The user data from the submitted form.
     * @param bool $saveRoles Whether or not to save roles.
     * @param bool $isTrustedProvider Whether or not the SSO provider is trusted.
     * @return array|bool Returns the user if connect can continue or **false** if it should bail and return immediately.
     */
    private function syncConnectUser(int $userID, array $data, bool $saveRoles, bool $isTrustedProvider)
    {
        $userModel = $this->UserModel;
        $user = $userModel->getID($userID, DATASET_TYPE_ARRAY);

        // This function will synchronize the user
        if (!c("Garden.Registration.ConnectSynchronize", true)) {
            return $user;
        }

        // Don't overwrite the user photo if the user uploaded a new one.
        $photo = val("Photo", $user);
        if ($photo && !isUrl($photo)) {
            unset($data["Photo"]);
        }

        $name = $data["Name"] ?? null;
        if ($name === null || $name === false || $name === "") {
            unset($data["Name"]);
        }

        // We're about to do a bunch of new validation so clear out anything that was floating around before.
        // TODO: Do we need this?
        $userModel->Validation->reset();

        // Get the profile fields and save them
        $profileFields = ($data["Profile"] ?? []) + ($this->Data["nonVisibleFields"] ?? []);
        $userProfileFieldsSchema = $this->profileFieldModel->getUserProfileFieldSchema(false);
        try {
            $userProfileFieldsSchema->validate($profileFields);
        } catch (\Garden\Schema\ValidationException $ex) {
            foreach ($ex->getValidation()->getErrors() as $error) {
                if ($this->getShowConnectSynchronizeErrors()) {
                    $userModel->Validation->addValidationResult($error["field"], $error["message"]);
                } else {
                    unset($profileFields[$error["field"]]);
                }
            }
        }

        // Synchronize the user's data.
        $saved = $userModel->save(["UserID" => $userID] + $data, [
            UserModel::OPT_NO_CONFIRM_EMAIL => true,
            UserModel::OPT_FIX_UNIQUE => true,
            UserModel::OPT_SAVE_ROLES => $saveRoles,
            UserModel::OPT_VALIDATE_NAME => !$isTrustedProvider,
            UserModel::OPT_ROLE_SYNC => $userModel->getConnectRoleSync(),
        ]);

        if ($saved) {
            // Update all profile fields received from sso.
            $this->profileFieldModel->updateUserProfileFields($userID, $profileFields);
        }

        // If the user failed to save then force a rendering of the connect form.
        if (!$saved) {
            if ($this->getShowConnectSynchronizeErrors()) {
                $this->Form->removeFormValue("UserID");
                $this->setData("NoConnectName", true);
                $this->Form->setValidationResults($userModel->validationResults());
                $this->render();
                return false;
            } else {
                $this->logger->notice("Failed to synchonrize SSO data.\n" . $userModel->Validation->resultsText(), [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                    Logger::FIELD_EVENT => "syncConnect_user_error",
                ]);
            }
        }

        $this->EventArguments["UserID"] = $userID;
        $this->fireEvent("AfterConnectSave");
        // Do a quick overwrite of the DB user with saved data.
        $user = $data + $user;
        return $user;
    }

    /**
     * After sign in, send them along.
     *
     * @param bool $checkPopup
     * @since 2.0.0
     * @access protected
     *
     */
    protected function _setRedirect($checkPopup = false)
    {
        $url = url($this->getTargetRoute(), true);

        $this->setRedirectTo($url);
        $this->MasterView = "popup";
        $this->View = "redirect";

        if ($this->_RealDeliveryType != DELIVERY_TYPE_ALL && $this->deliveryType() != DELIVERY_TYPE_ALL) {
            $this->deliveryMethod(DELIVERY_METHOD_JSON);
            $this->setHeader("Content-Type", "application/json; charset=utf-8");
        } elseif ($checkPopup || $this->data("CheckPopup")) {
            $this->addDefinition("CheckPopup", true);
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
    public function index()
    {
        $this->View = "SignIn";
        $this->signIn();
    }

    /**
     * Auth via password.
     *
     * @access public
     * @since 2.0.0
     */
    public function password()
    {
        $this->auth("password");
    }

    /**
     * Auth via default method. Simpler, old version of signIn().
     *
     * Events: SignIn
     *
     * @access public
     * @return void
     */
    public function signIn2()
    {
        $this->fireEvent("SignIn");
        $this->auth("default");
    }

    /**
     * Good afternoon, good evening, and goodnight.
     *
     * Events: SignOut
     *
     * @access public
     * @param string $transientKey (default: "")
     * @since 2.0.0
     *
     */
    public function signOut($transientKey = "", $override = "0")
    {
        $this->checkOverride("SignOut", $this->target(), $transientKey);

        if (Gdn::session()->validateTransientKey($transientKey)) {
            $user = Gdn::session()->User;

            $this->EventArguments["SignoutUser"] = $user;
            $this->fireEvent("BeforeSignOut");

            // Sign the user right out.
            Gdn::session()->end();
            $this->setData("SignedOut", true);

            $this->EventArguments["SignoutUser"] = $user;
            $this->fireEvent("SignOut");

            $this->_setRedirect();
        } elseif (!Gdn::session()->isValid()) {
            $this->_setRedirect();
        }

        $target = url($this->target(), true);
        if (!isTrustedDomain($target)) {
            $target = Gdn::router()->getDestination("DefaultController");
        }

        $this->setData("Override", $override);
        $this->setData("Target", $target);
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
    public function signIn($method = false, $arg1 = false)
    {
        $this->canonicalUrl(url("/entry/signin", true));
        if (!$this->Request->isPostBack()) {
            $this->checkOverride("SignIn", $this->target());
        }

        $this->addJsFile("entry.js");
        $this->setData("Title", t("Sign In"));
        // Add open graph description in case a restricted page is shared.
        $this->description(Gdn::config("Garden.Description"));

        $this->Form->addHidden("Target", $this->target());
        $this->Form->addHidden("ClientHour", date("Y-m-d H:00")); // Use the server's current hour as a default.

        // Additional signin methods are set up with plugins.
        $methods = [];

        $this->setData("Methods", $methods);
        $this->setData("FormUrl", url("entry/signin"));

        $this->fireEvent("SignIn");

        if ($this->Form->isPostBack()) {
            $this->Form->validateRule(
                "Email",
                "ValidateRequired",
                sprintf(t("%s is required."), t(UserModel::signinLabelCode()))
            );
            $this->Form->validateRule("Password", "ValidateRequired");

            if (!$this->Request->isAuthenticatedPostBack()) {
                $legacyLogin = \Vanilla\FeatureFlagHelper::featureEnabled("legacyEmbedLogin");
                if ($legacyLogin && c("Garden.Embed.Allow")) {
                    $this->logger->info("Signed in using the legacy embed method", [
                        "login" => $this->Form->getFormValue("Email"),
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                        "event" => "legacy_embed_signin",
                    ]);
                } else {
                    $this->Form->addError("Please try again.");
                    Gdn::session()->ensureTransientKey();
                }
            }

            // Check the user.
            if ($this->Form->errorCount() == 0) {
                $email = $this->Form->getFormValue("Email");
                $user = Gdn::userModel()->getByEmail($email);
                if (!$user) {
                    $user = Gdn::userModel()->getByUsername($email);
                }

                if (!$user) {
                    $this->addCredentialErrorToForm(
                        "@" . sprintf(t("Invalid user/password provided."), strtolower(t(UserModel::signinLabelCode())))
                    );
                    $this->logger->info("Failed to sign in. User not found: {signin}", [
                        "signin" => $email,
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                        Logger::FIELD_EVENT => "signin_failure",
                    ]);

                    $this->fireEvent("BadSignIn", [
                        "Email" => $email,
                        "Password" => $this->Form->getFormValue("Password"),
                        "Reason" => "NotFound",
                    ]);
                } else {
                    // Check if the account is suspended, and also try to clear it based on elapsed time.
                    if (Gdn::userModel()->isSuspendedAndResetBasedOnTime($user->UserID)) {
                        Gdn::userModel()->incrementLoginAttempt($user->UserID);
                        $this->addCredentialErrorToForm(Gdn::userModel()->suspendedErrorMessage());
                    } else {
                        // Check the password.
                        $passwordHash = new Gdn_PasswordHash();
                        $password = $this->Form->getFormValue("Password");
                        try {
                            $passwordChecked = $passwordHash->checkPassword(
                                $password,
                                val("Password", $user),
                                val("HashMethod", $user)
                            );

                            // Rate limiting
                            Gdn::userModel()->rateLimit($user);

                            if ($passwordChecked) {
                                // Update weak passwords
                                $hashMethod = val("HashMethod", $user);
                                if ($passwordHash->Weak || ($hashMethod && strcasecmp($hashMethod, "Vanilla") != 0)) {
                                    $pw = $passwordHash->hashPassword($password);
                                    Gdn::userModel()->setField(val("UserID", $user), [
                                        "Password" => $pw,
                                        "HashMethod" => "Vanilla",
                                    ]);
                                }

                                Gdn::userModel()->fireEvent("BeforeSignIn", ["UserID" => $user->UserID ?? false]);
                                Gdn::session()->start(
                                    val("UserID", $user),
                                    true,
                                    (bool) $this->Form->getFormValue("RememberMe")
                                );

                                if (BanModel::isBanned($user->Banned, BanModel::BAN_AUTOMATIC | BanModel::BAN_MANUAL)) {
                                    // If account has been banned manually or by a ban rule.
                                    $this->Form->addError("This account has been banned.");
                                    Gdn::session()->end();
                                } elseif (BanModel::isBanned($user->Banned, BanModel::BAN_WARNING)) {
                                    // If account has been banned by the "Warnings and notes" plugin or similar.
                                    $this->Form->addError("This account has been temporarily banned.");
                                    Gdn::session()->end();
                                } elseif (!Gdn::session()->checkPermission("Garden.SignIn.Allow")) {
                                    // If account does not have the sign in permission
                                    $this->Form->addError("Sorry, permission denied. This account cannot be accessed.");
                                    Gdn::session()->end();
                                } else {
                                    $clientHour = $this->Form->getFormValue("ClientHour");
                                    $hourOffset = Gdn::session()->User->HourOffset;
                                    if (is_numeric($clientHour) && $clientHour >= 0 && $clientHour < 24) {
                                        $hourOffset = $clientHour - date("G", time());
                                    }

                                    if ($hourOffset != Gdn::session()->User->HourOffset) {
                                        Gdn::userModel()->setProperty(
                                            Gdn::session()->UserID,
                                            "HourOffset",
                                            $hourOffset
                                        );
                                    }

                                    Gdn::userModel()->fireEvent("AfterSignIn");

                                    $this->_setRedirect();
                                }
                            } else {
                                $this->addCredentialErrorToForm("Invalid user/password provided.");
                                $this->logger->warning("{username} failed to sign in.  Invalid password.", [
                                    "InsertName" => $user->Name,
                                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                                    Logger::FIELD_EVENT => "signin_failure",
                                ]);
                                Gdn::userModel()->incrementLoginAttempt($user->UserID);
                                if (Gdn::userModel()->isSuspendedAndResetBasedOnTime($user->UserID)) {
                                    $this->addCredentialErrorToForm(Gdn::userModel()->suspendedErrorMessage());
                                }

                                $this->fireEvent("BadSignIn", [
                                    "Email" => $email,
                                    "Password" => $password,
                                    "User" => $user,
                                    "Reason" => "Password",
                                ]);
                            }
                        } catch (Gdn_UserException $ex) {
                            $this->Form->addError($ex);
                        }
                    }
                }
            }
        } else {
            if ($target = $this->Request->get("Target")) {
                $this->Form->addHidden("Target", $target);
            }
            $this->Form->setValue("RememberMe", true);
        }

        return $this->render();
    }

    /**
     * Calls the appropriate registration method based on the configuration setting.
     *
     * Events: Register
     *
     * @access public
     * @param string $invitationCode Unique code given to invited user.
     * @since 2.0.0
     *
     */
    public function register($invitationCode = "")
    {
        if (!$this->Request->isPostBack()) {
            $this->checkOverride("Register", $this->target());
        }

        $this->fireEvent("Register");

        $this->Form->setModel($this->UserModel);

        // Define gender dropdown options
        $this->GenderOptions = ProfileController::getGenderOptions();

        // Make sure that the hour offset for new users gets defined when their account is created
        $this->addJsFile("entry.js");

        $this->Form->addHidden("ClientHour", date("Y-m-d H:00")); // Use the server's current hour as a default
        $this->Form->addHidden("Target", $this->target());

        $this->setData("NoEmail", UserModel::noEmail());

        // Sub-dispatch to a specific handler for each registration method
        $registrationHandler = $this->getRegistrationhandler();
        $this->setData("Method", stringBeginsWith($registrationHandler, "register", true, true));
        $this->$registrationHandler($invitationCode);
    }

    /**
     * Select view/method to be used for registration (from config).
     *
     * @access protected
     * @return string Method name to invoke for registration
     * @since 2.3
     */
    protected function getRegistrationhandler()
    {
        $registrationMethod = Gdn::config("Garden.Registration.Method");
        if (!in_array($registrationMethod, ["Closed", "Basic", "Captcha", "Approval", "Invitation", "Connect"])) {
            $registrationMethod = "Basic";
        }

        // We no longer support captcha-less registration, both Basic and Captcha require a captcha
        if ($registrationMethod == "Captcha") {
            $registrationMethod = "Basic";
        }

        return "register{$registrationMethod}";
    }

    /**
     * Return whether or not the entry/connect page is in a postback state.
     *
     * The entry/connect page supports an HTTP POST for its initial request. This was originally done to support SSO for
     * for SAML, but is a core feature now. Basically, we check to make sure the actual "Connect" button was pressed to
     * determine a "real" postback.
     *
     * @return bool
     */
    public function isConnectPostBack(): bool
    {
        $isPostBack = $this->Form->isPostBack() && $this->Form->getFormValue("Connect", null) !== null;
        return $isPostBack;
    }

    /**
     * Whether or not to halt SSO if there is an error synchronizing user information.
     *
     * @return bool
     */
    public function getShowConnectSynchronizeErrors(): bool
    {
        return $this->connectSynchronizeErrors;
    }

    /**
     * Set whether or not to halt SSO if there is an error synchronizing user information.
     *
     * @param bool $connectSynchronizeErrors
     */
    public function setShowConnectSynchronizeErrors(bool $connectSynchronizeErrors): void
    {
        $this->connectSynchronizeErrors = $connectSynchronizeErrors;
    }

    /**
     * Alias of EntryController::getRegistrationHandler
     *
     * @return string
     * @codeCoverageIgnore
     * @deprecated since 2.3
     */
    protected function _registrationView()
    {
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
    private function registerApproval()
    {
        $this->View = "registerapproval";
        Gdn::userModel()->addPasswordStrength($this);

        // If the form has been posted back...
        if ($this->Form->isPostBack()) {
            // Add validation rules that are not enforced by the model
            $this->UserModel->defineSchema();
            $this->UserModel->Validation->applyRule("Name", "Username", $this->UsernameError);
            $this->UserModel->Validation->applyRule(
                "TermsOfService",
                "Required",
                t("You must agree to the terms of service.")
            );
            $this->UserModel->Validation->applyRule("Password", "Required");
            $this->UserModel->Validation->applyRule("Password", "Strength");
            $this->UserModel->Validation->applyRule("Password", "Match");
            $this->UserModel->Validation->applyRule("DiscoveryText", "Required", "Tell us why you want to join!");
            // $this->UserModel->Validation->applyRule('DateOfBirth', 'MinimumAge');

            $this->fireEvent("RegisterValidation");

            //apply validation rules for our custom profile fields
            if ($this->hasCustomProfileFields()) {
                $this->applyValidationOnCustomProfileFields();
            }

            try {
                $values = $this->Form->formValues();
                $values = $this->UserModel->filterForm($values, true);
                unset($values["Roles"]);
                $authUserID = $this->UserModel->register($values);
                $this->setData("UserID", $authUserID);
                if (!$authUserID) {
                    $this->Form->setValidationResults($this->UserModel->validationResults());
                } else {
                    // The user has been created successfully, so sign in now.
                    Gdn::session()->start($authUserID);

                    if ($this->Form->getFormValue("RememberMe")) {
                        Gdn::authenticator()->setIdentity($authUserID, true);
                    }

                    $story = "";
                    $this->EventArguments["AuthUserID"] = $authUserID;
                    $this->EventArguments["Story"] = &$story;
                    $this->fireEvent("RegistrationPending");

                    //if we have custom profile fields, we need to update user meta with those
                    if ($this->hasCustomProfileFields() && !empty($values["Profile"])) {
                        $this->updateUserCustomProfileFields($authUserID, $values["Profile"]);
                    }

                    $this->View = "RegisterThanks"; // Tell the user their application will be reviewed by an administrator.

                    if ($this->deliveryType() !== DELIVERY_TYPE_ALL) {
                        $this->setRedirectTo("/entry/registerthanks");
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
    private function registerBasic()
    {
        $this->View = "registerbasic";
        Gdn::userModel()->addPasswordStrength($this);

        if ($this->Form->isPostBack() === true) {
            // Add validation rules that are not enforced by the model
            $this->UserModel->defineSchema();
            $this->UserModel->Validation->applyRule("Name", "Username", $this->UsernameError);
            $this->UserModel->Validation->applyRule(
                "TermsOfService",
                "Required",
                t("You must agree to the terms of service.")
            );
            $this->UserModel->Validation->applyRule("Password", "Required");
            $this->UserModel->Validation->applyRule("Password", "Strength");
            $this->UserModel->Validation->applyRule("Password", "Match");
            // $this->UserModel->Validation->applyRule('DateOfBirth', 'MinimumAge');

            $this->fireEvent("RegisterValidation");

            //apply validation rules for our custom profile fields
            if ($this->hasCustomProfileFields()) {
                $this->applyValidationOnCustomProfileFields();
            }

            try {
                $values = $this->Form->formValues();
                $values = $this->UserModel->filterForm($values, true);
                unset($values["Roles"]);
                $authUserID = $this->UserModel->register($values);
                $this->setData("UserID", $authUserID);
                if ($authUserID == UserModel::REDIRECT_APPROVE) {
                    $this->Form->setFormValue("Target", "/entry/registerthanks");
                    $this->_setRedirect();

                    return;
                } elseif (!$authUserID) {
                    $this->Form->setValidationResults($this->UserModel->validationResults());
                } else {
                    // The user has been created successfully, so sign in now.
                    Gdn::session()->start($authUserID);

                    if ($this->Form->getFormValue("RememberMe")) {
                        Gdn::authenticator()->setIdentity($authUserID, true);
                    }

                    try {
                        $this->UserModel->sendWelcomeEmail($authUserID, "", "Register");
                    } catch (Exception $ex) {
                        // Suppress exceptions from bubbling up.
                    }
                    //if we have custom profile fields, we need to update user meta with those
                    if ($this->hasCustomProfileFields() && !empty($values["Profile"])) {
                        //We should be able to save all form fields except internal fields here;
                        $this->updateUserCustomProfileFields($authUserID, $values["Profile"]);
                    }

                    $this->fireEvent("RegistrationSuccessful");

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
    private function registerCaptcha()
    {
        $this->registerBasic();
    }

    /**
     * Connect registration
     *
     * @deprecated since 2.0.18.
     * @codeCoverageIgnore
     */
    private function registerConnect()
    {
        throw notFoundException();
    }

    /**
     * Registration not allowed.
     *
     * @access private
     * @since 2.0.0
     */
    private function registerClosed()
    {
        $this->View = "registerclosed";
        $this->render();
    }

    /**
     * Invitation-only registration. Requires code.
     *
     * @param int $invitationCode
     * @since 2.0.0
     */
    public function registerInvitation($invitationCode = 0)
    {
        $this->View = "registerinvitation";
        $this->Form->setModel($this->UserModel);

        // Define gender dropdown options
        $this->GenderOptions = ProfileController::getGenderOptions();

        if (!$this->Form->isPostBack()) {
            $this->Form->setValue("InvitationCode", $invitationCode);
        }

        $invitationModel = new InvitationModel();

        // Look for the invitation.
        $invitation = $invitationModel
            ->getWhere(["Code" => $this->Form->getValue("InvitationCode")])
            ->firstRow(DATASET_TYPE_ARRAY);

        if (!$invitation) {
            $this->Form->addError("Invitation not found.", "Code");
        } else {
            if ($expires = val("DateExpires", $invitation)) {
                $expires = Gdn_Format::toTimestamp($expires);
                if ($expires <= time()) {
                }
            }
        }

        $this->Form->addHidden("ClientHour", date("Y-m-d H:00")); // Use the server's current hour as a default
        $this->Form->addHidden("Target", $this->target());

        Gdn::userModel()->addPasswordStrength($this);

        if ($this->Form->isPostBack() === true) {
            $this->InvitationCode = $this->Form->getValue("InvitationCode");
            // Add validation rules that are not enforced by the model
            $this->UserModel->defineSchema();
            $this->UserModel->Validation->applyRule("Name", "Username", $this->UsernameError);
            $this->UserModel->Validation->applyRule(
                "TermsOfService",
                "Required",
                t("You must agree to the terms of service.")
            );
            $this->UserModel->Validation->applyRule("Password", "Required");
            $this->UserModel->Validation->applyRule("Password", "Strength");
            $this->UserModel->Validation->applyRule("Password", "Match");

            $this->fireEvent("RegisterValidation");

            try {
                $values = $this->Form->formValues();
                $values = $this->UserModel->filterForm($values, true);
                unset($values["Roles"]);
                $authUserID = $this->UserModel->register($values, ["Method" => "Invitation"]);
                $this->setData("UserID", $authUserID);
                if (!$authUserID) {
                    $this->Form->setValidationResults($this->UserModel->validationResults());
                } else {
                    // The user has been created successfully, so sign in now.
                    Gdn::session()->start($authUserID);
                    if ($this->Form->getFormValue("RememberMe")) {
                        Gdn::authenticator()->setIdentity($authUserID, true);
                    }

                    $this->fireEvent("RegistrationSuccessful");

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
            if ($name = val("Name", $invitation)) {
                $this->Form->setValue("Name", $name);
            }

            $this->InvitationCode = $invitationCode;
        }

        // Make sure that the hour offset for new users gets defined when their account is created
        $this->addJsFile("entry.js");
        $this->render();
    }

    /**
     * Display registration thank-you message
     *
     * @since 2.1
     */
    public function registerThanks()
    {
        $this->CssClass = "SplashMessage NoPanel";
        $this->setData("_NoMessages", true);
        $this->setData("Title", t("Thank You!"));
        $this->render();
    }

    /**
     * Request password reset.
     *
     * @access public
     * @throws Gdn_UserException When not an authenticated post back.
     * @since 2.0.0
     */
    public function passwordRequest()
    {
        $this->canonicalUrl(url("/entry/passwordrequest", true));
        if (!$this->UserModel->isEmailUnique() && $this->UserModel->isNameUnique()) {
            Gdn::locale()->setTranslation("Email", t("Username"));
        }

        if ($this->Form->isPostBack() === true) {
            $this->Request->isAuthenticatedPostBack(true);
            $this->Form->validateRule("Email", "ValidateRequired");

            if ($this->Form->errorCount() == 0) {
                try {
                    $email = $this->Form->getFormValue("Email");
                    if (!$this->UserModel->passwordRequest($email)) {
                        $this->Form->setValidationResults($this->UserModel->validationResults());
                    }
                } catch (Exception $ex) {
                    $this->Form->addError($ex->getMessage());
                }
                if ($this->Form->errorCount() == 0) {
                    $this->Form->addError("Success!");
                    $this->View = "passwordrequestsent";
                    $this->fireEvent("PasswordRequest", [
                        "Email" => $email,
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
     * @param int|string $userID Unique.
     * @param string $passwordResetKey Authenticate with unique, 1-time code sent via email.
     */
    public function passwordReset($userID = "", $passwordResetKey = "")
    {
        safeHeader("Referrer-Policy: no-referrer");
        $this->setHeader(self::HEADER_CACHE_CONTROL, self::NO_CACHE);

        $session = Gdn::session();

        // Prevent the token from being leaked by referrer!
        $passwordResetKey = trim($passwordResetKey);
        if ($passwordResetKey) {
            $session->stash(
                "passwordResetKey",
                $passwordResetKey,
                true,
                (new \DateTimeImmutable(Gdn_Session::SHORT_STASH_SESSION_LENGHT))->format(MYSQL_DATE_FORMAT)
            );
            redirectTo("/entry/passwordreset/$userID");
        }

        $passwordResetKey = $session->stash("passwordResetKey", "", false);
        $this->UserModel->addPasswordStrength($this);

        if (
            !is_numeric($userID) ||
            $passwordResetKey == "" ||
            hash_equals($this->UserModel->getAttribute($userID, "PasswordResetKey", ""), $passwordResetKey) === false
        ) {
            $this->Form->addError(
                "Failed to authenticate your password reset request. Try using the reset request form again."
            );
            $this->logger->notice("{username} failed to authenticate password reset request.", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                "event" => "password_reset_failure",
            ]);
            $this->fireEvent("PasswordResetFailed", [
                "UserID" => $userID,
            ]);
        }

        $expires = $this->UserModel->getAttribute($userID, "PasswordResetExpires");
        if ($this->Form->errorCount() === 0 && $expires < time()) {
            $this->Form->addError(
                "@" .
                    t(
                        "Your password reset token has expired.",
                        "Your password reset token has expired. Try using the reset request form again."
                    )
            );
            $this->logger->notice("{username} has an expired reset token.", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                "event" => "password_reset_failure",
            ]);

            $this->fireEvent("PasswordResetFailed", [
                "UserID" => $userID,
            ]);
        }

        if ($this->Form->errorCount() == 0) {
            $user = $this->UserModel->getID($userID, DATASET_TYPE_ARRAY);
            if ($user) {
                $user = arrayTranslate($user, ["UserID", "Name", "Email"]);
                $this->setData("User", $user);
            }

            if ($this->Form->isPostBack() === true) {
                // Backward compatibility fix.
                $confirm = $this->Form->getFormValue("Confirm");
                $passwordMatch = $this->Form->getFormValue("PasswordMatch");
                if ($confirm && !$passwordMatch) {
                    $this->Form->setValue("PasswordMatch", $confirm);
                }

                $this->UserModel->Validation->applyRule("Password", "Required");
                $this->UserModel->Validation->applyRule("Password", "Strength");
                $this->UserModel->Validation->applyRule("Password", "Match");
                $this->UserModel->Validation->validate($this->Form->formValues());
                $this->Form->setValidationResults($this->UserModel->Validation->results());

                $password = $this->Form->getFormValue("Password", "");
                $passwordMatch = $this->Form->getFormValue("PasswordMatch", "");
                if ($password == "") {
                    $this->logger->notice("Failed to reset the password for {username}. Password is invalid.", [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                        "event" => "password_reset_failure",
                    ]);
                } elseif ($password != $passwordMatch) {
                    $this->logger->notice("Failed to reset the password for {username}. Passwords did not match.", [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                        "event" => "password_reset_failure",
                    ]);
                }

                if ($this->Form->errorCount() == 0) {
                    $user = $this->UserModel->passwordReset($userID, $password);
                    $this->logger->notice("{username} has reset their password.", [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                        "event" => "password_reset",
                    ]);
                    Gdn::session()->start($user->UserID, true);
                    $this->setRedirectTo("/", false);
                }
            }
        } else {
            $this->setData("Fatal", true);
        }

        $this->addJsFile("entry.js");
        $this->render();
    }

    /**
     * Confirm email address is valid via sent code.
     *
     * @access public
     * @param int $userID
     * @param string $emailKey Authenticate with unique, 1-time code sent via email.
     * @since 2.0.0
     *
     */
    public function emailConfirm($userID, $emailKey = "")
    {
        $user = $this->UserModel->getID($userID);

        if (!$user) {
            throw notFoundException("User");
        }

        $emailConfirmed = $this->UserModel->confirmEmail($user, $emailKey);
        $this->Form->setValidationResults($this->UserModel->validationResults());

        $userMatch = (int) $userID === Gdn::session()->UserID ? true : false;

        if (!$userMatch) {
            Gdn::session()->end();
            redirectTo("/entry/signin");
        }

        if ($emailConfirmed) {
            $userID = val("UserID", $user);
            Gdn::session()->start($userID);
        }

        $this->setData("UserID", $userID);
        $this->setData("EmailConfirmed", $emailConfirmed);
        $this->setData("Email", $user->Email);
        $this->render();
    }

    /**
     * Send email confirmation message to user.
     *
     * @param int|string $userID
     */
    public function emailConfirmRequest($userID = "")
    {
        if ($userID && !Gdn::session()->checkPermission("Garden.Users.Edit")) {
            $userID = "";
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
     * @param string $authenticationSchemeAlias
     * @param string $transientKey Unique value to prove intent.
     * @codeCoverageIgnore
     * @since 2.0.0
     *
     */
    public function leave($authenticationSchemeAlias = "default", $transientKey = "")
    {
        deprecated(__FUNCTION__);
        $this->EventArguments["AuthenticationSchemeAlias"] = $authenticationSchemeAlias;
        $this->fireEvent("BeforeLeave");

        // Allow hijacking deauth type
        $authenticationSchemeAlias = $this->EventArguments["AuthenticationSchemeAlias"];

        try {
            $authenticator = Gdn::authenticator()->authenticateWith($authenticationSchemeAlias);
        } catch (Exception $e) {
            $authenticator = Gdn::authenticator()->authenticateWith("default");
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
            $this->View = "leave";
            $reaction = $logoutResponse;
        } else {
            $this->View = "auth/" . $authenticator->getAuthenticationSchemeAlias();
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
                    $route = "/";
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
                        redirectTo(Gdn::router()->getDestination("DefaultController"));
                    }
                }
                break;
        }
        $this->render();
    }

    /**
     * Get the redirect URL.
     *
     * @return string
     * @codeCoverageIgnore
     * @deprecated 2017-06-29
     */
    public function redirectTo()
    {
        deprecated(__FUNCTION__, "target");

        return $this->getTargetRoute();
    }

    /**
     * Go to requested target() or the default controller if none was set.
     *
     * @access public
     * @return string URL.
     * @since 2.0.0
     *
     */
    protected function getTargetRoute()
    {
        $target = $this->target();

        return $target == "" ? Gdn::router()->getDestination("DefaultController") : $target;
    }

    /**
     * Set where to go after signin.
     *
     * @param string|false $target Where we're requested to go to.
     * @return string URL to actually go to (validated & safe).
     */
    public function target($target = false)
    {
        if ($target === false) {
            $target = $this->Form->getFormValue("Target", false);
            if (!$target) {
                $target = $this->Request->get("Target", $this->Request->get("target", "/"));
                if (empty($target)) {
                    $target = "/";
                }
            }
        }
        $target = url($target, true);

        // Never redirect back to sign in/out pages.
        if (
            ($this->Request->getHost() === parse_url($target, PHP_URL_HOST) &&
                preg_match('`/entry/(?:signin|signout|autosignedout)($|\?)`i', $target)) ||
            !isTrustedDomain($target)
        ) {
            $target = url("/", true);
        }

        return $target;
    }

    /**
     * Custom profile fields for register/connect forms
     *
     * @param array|null $wrapperOptions In case we want wrap our fields with a container.
     * @param bool $connectPage // is the call coming from connect page
     */
    public function generateFormCustomProfileFields(?array $wrapperOptions = null, bool $connectPage = false)
    {
        $isPostBack = $this->isConnectPostBack();
        $profileFields =
            $this->profileFieldModel->getProfileFields([
                "enabled" => 1,
                "visibility <>" => ProfileFieldModel::VISIBILITY_INTERNAL,
            ]) ?? [];
        //only required/optional fields should be shown
        $profileFields = array_filter($profileFields, function ($field) {
            return ($field["registrationOptions"] === "required" || $field["registrationOptions"] === "optional") &&
                ($field["enabled"] || false);
        });

        $result = "";

        foreach ($profileFields as $key => $field) {
            // Adjust values for our form, default is TextBox
            $formType = "TextBox";
            switch ($field["formType"]) {
                case "checkbox":
                    $formType = "CheckBox";
                    break;
                case "dropdown":
                    $formType = "Dropdown";
                    break;
                case "tokens":
                    $formType = "tokensInputReact";
                    break;
            }

            $name = "Profile[" . $field["apiName"] . "]";
            $options = [];
            $attributes = [];

            $description = !empty($field["description"])
                ? '<div class="Gloss">' . $field["description"] . "</div>"
                : null;

            if ($field["formType"] === "text-multiline") {
                $options["MultiLine"] = true;
            }

            if ($formType == "Dropdown" || $formType == "tokensInputReact") {
                $values = $field["dropdownOptions"];

                // create key/value associative array so we send the right values from form, values and labels are the same.
                $options = array_combine($values, $values);

                $attributes = ["includeNull" => true];
            }
            if ($connectPage && !$isPostBack) {
                // Has any form values been already set from SSO
                $currentFormValues = $this->Form->getFormValue("Profile");
                if (!empty($currentFormValues[$field["apiName"]])) {
                    if ($formType == "tokensInputReact") {
                        $currentValue = is_array($currentFormValues[$field["apiName"]])
                            ? $currentFormValues[$field["apiName"]]
                            : [$currentFormValues[$field["apiName"]]];
                        $options["value"] = $this->convertArrayToTokenValue($currentValue, $values);
                    } elseif ($formType == "Dropdown") {
                        $currentValue = is_array($currentFormValues[$field["apiName"]])
                            ? $currentFormValues[$field["apiName"]][0]
                            : $currentFormValues[$field["apiName"]];
                        if (in_array($currentValue, $values)) {
                            $attributes["value"] = $currentValue;
                        } else {
                            unset($this->Form->_FormValues["Profile"][$field["apiName"]]);
                        }
                    } elseif ($formType == "CheckBox") {
                        $attributes["value"] = (bool) $currentFormValues[$field["apiName"]];
                        if ($attributes["value"]) {
                            $this->Form->setValue("Profile[{$field["apiName"]}]", $attributes["value"]);
                        }
                    } else {
                        $options["value"] = $currentFormValues[$field["apiName"]];
                    }
                }
            }

            if ($formType == "CheckBox") {
                $result .= wrap(
                    ($description ?? "") . $this->Form->{$formType}($name, $field["label"], $attributes),
                    "li"
                );
            } elseif ($formType == "tokensInputReact") {
                $result .= wrap(
                    $this->Form->{$formType}($name, $options, $field["label"], $field["description"]),
                    "li",
                    [
                        "class" => "form-group",
                    ]
                );
            } else {
                $label = $this->Form->label($field["label"], $name);
                if ($description) {
                    $label .= $description;
                }
                $result .= wrap($label . $this->Form->{$formType}($name, $options, $attributes), "li", [
                    "class" => "form-group",
                ]);
            }
        }

        if ($wrapperOptions) {
            $result = wrap($result, $wrapperOptions["tag"] ?? "", $wrapperOptions["attributes"] ?? "");
        }

        echo $result;
    }

    /**
     * Apply validation rules on custom profile fields.
     * @param bool $isConnectPage is a connectPage/ RegistrationPage ?
     */
    public function applyValidationOnCustomProfileFields(bool $isConnectPage = false)
    {
        // Get all enabled profile fields
        $profileFields = $this->profileFieldModel->getProfileFields(["enabled" => 1]) ?? [];
        $userProfileFieldsSchema = $this->profileFieldModel->getUserProfileFieldSchema(false);
        $profileFormFields = $this->Form->formValues()["Profile"] ?? [];
        try {
            $requiredFields = [];
            // If there is dataType/formType validation error from schema, this will fail, so we should not complete the registration
            foreach ($profileFields as $field) {
                // We need to decode token input values and reformat  it to array of strings instead of tokens value/label format
                if ($field["formType"] === "tokens" && isset($profileFormFields[$field["apiName"]])) {
                    $profileFormFields[$field["apiName"]] = $this->convertTokenValueToArray(
                        $profileFormFields[$field["apiName"]]
                    );
                }

                // we need to filter out any internal or hidden fields if at all the form has those fields
                // (Can happen if someone tried to manipulate form fields deliberately)
                $isHiddenOrInternalField =
                    $field["registrationOptions"] === ProfileFieldModel::REGISTRATION_HIDDEN ||
                    $field["visibility"] === ProfileFieldModel::VISIBILITY_INTERNAL;

                if (!$isConnectPage && isset($profileFormFields[$field["apiName"]]) && $isHiddenOrInternalField) {
                    // Strip off hidden/internal fields when we aren't on the connect page.
                    // The connect workflow takes care of ensuring these fields come through and are trusted.
                    unset($this->Form->_FormValues["Profile"][$field["apiName"]]);
                    unset($profileFormFields[$field["apiName"]]);
                }
                // Check for required only if the form contains the specific field
                // All other form fields type can be accepted/Open on registration forms even if the editing is restricted
                if ($field["registrationOptions"] === "required" && isset($profileFormFields[$field["apiName"]])) {
                    $userProfileFieldsSchema->setField("properties.{$field["apiName"]}.minItems", 1);
                    $userProfileFieldsSchema->setField("properties.{$field["apiName"]}.minLength", 1);
                    $requiredFields[] = $field["apiName"];
                }
            }
            $userProfileFieldsSchema->setField("required", $requiredFields);
            $userProfileFieldsSchema->validate($profileFormFields);
        } catch (\Garden\Schema\ValidationException $ex) {
            $this->UserModel->Validation->addResults($ex);
        } catch (Exception $ex) {
            ErrorLogger::error($ex, ["profileFields", "validation", "error"]);
            // This is not a validation exception, just do general error messaging
            $this->UserModel->Validation->addValidationResult(
                "",
                $ex->getMessage() ?? t("There was an error registering the user.")
            );
        }
    }

    /**
     * Update user with new custom profile fields.
     *
     * @param int $userID The user ID to update.
     * @param array $formProfileFields Profile fields values by name from form.
     */
    public function updateUserCustomProfileFields(int $userID, array $formProfileFields)
    {
        $profileFields = $this->profileFieldModel->getProfileFields(["enabled" => 1]) ?? [];

        //pre-filter only fields with values and re-format token fields so they have the right values schema expects
        $validFields = [];
        foreach ($profileFields as $profileField) {
            $apiName = $profileField["apiName"];
            if (isset($formProfileFields[$apiName]) && $formProfileFields[$apiName] !== "") {
                $validFields[$apiName] =
                    $profileField["formType"] === "tokens"
                        ? $this->convertTokenValueToArray($formProfileFields[$apiName])
                        : $formProfileFields[$apiName];
            }
        }

        /*
         * Make sure we validate the form fields using applyValidationOnCustomProfileFields
         * We should be blocking on editing restrictions here on registration page
         */
        try {
            $this->profileFieldModel->updateUserProfileFields($userID, $validFields);
        } catch (Exception $ex) {
            $this->Form->addError($ex->getMessage());
        }
    }

    /**
     * This confirms if we have the appropriate feature flag and custom profile
     *
     * @return boolean
     */
    public function hasCustomProfileFields(): bool
    {
        $profileFields = $this->profileFieldModel->getProfileFields(["enabled" => true]);
        return Gdn::config()->get(ProfileFieldModel::CONFIG_FEATURE_FLAG, false) && count($profileFields) > 0;
    }

    /**
     * This confirms if we have any profile field or which are visible-public-required/optional, so we can include them in registration form
     *
     * @return boolean
     */
    public function hasRegistrationCustomFields(): bool
    {
        if (!$this->hasCustomProfileFields()) {
            return false;
        }
        $profileFields = $this->profileFieldModel->getProfileFields(["enabled" => true]);
        $profileFields = array_filter($profileFields, function ($profileField) {
            return ($profileField["registrationOptions"] === "required" ||
                $profileField["registrationOptions"] === "optional") &&
                $profileField["visibility"] !== "internal";
        });
        return count($profileFields) > 0;
    }

    /**
     * Generates token values string array from json.
     *
     * @param string $tokensJSON Token input field json value
     *
     * @return array Array from values from original json
     */
    public function convertTokenValueToArray(string $tokensJSON): array
    {
        $tokensData = json_decode($tokensJSON, true);

        //generate an array from value string
        if ($tokensData && count($tokensData) > 0) {
            return array_map(function (array $token) {
                return $token["value"];
            }, $tokensData);
        }

        return [];
    }

    /**
     * Generate json string value from array for token inputs
     *
     * @param array $tokenData
     * @param array $allowedOptions
     *
     * @return string
     */
    public function convertArrayToTokenValue(array $tokenData, array $allowedOptions): string
    {
        $tokenJson = [];
        $keys = ["label", "value"];
        foreach ($tokenData as $value) {
            if (in_array($value, $allowedOptions) && !in_array($value, $tokenJson)) {
                $tokenJson[] = array_fill_keys($keys, $value);
            }
        }

        return !empty($tokenJson) ? json_encode($tokenJson) : "";
    }
}
