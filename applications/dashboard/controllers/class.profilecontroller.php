<?php
/**
 * Manages individual user profiles.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Garden\EventManager;
use Garden\Schema\ValidationException;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Exception\ExitException;
use Vanilla\FloodControlTrait;
use Vanilla\Models\ProfileFieldsPreloadProvider;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Formatting\Formats\TextFormat;

/**
 * Handles /profile endpoint.
 *
 * @property UserModel $UserModel
 */
class ProfileController extends Gdn_Controller
{
    use FloodControlTrait;

    const AVATAR_FOLDER = "userpics";

    const PRIVATE_PROFILE = "This user's profile is private.";

    /** @var array Models to automatically instantiate. */
    public $Uses = ["Form", "UserModel"];

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

    /** @var LongRunner */
    private $longRunner;

    /**
     * Prep properties.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct(LongRunner $longRunner)
    {
        $this->User = false;
        $this->_TabView = "Activity";
        $this->_TabController = "ProfileController";
        $this->_TabApplication = "Dashboard";
        $this->CurrentTab = "Activity";
        $this->ProfileTabs = [];
        $this->editMode(true);

        $this->addInternalMethod("isEditMode");

        Gdn::config()->touch([
            "Vanilla.Password.SpamCount" => 2,
            "Vanilla.Password.SpamTime" => 1,
            "Vanilla.Password.SpamLock" => 120,
        ]);

        $this->longRunner = $longRunner;
        parent::__construct();
    }

    /**
     * Adds JS, CSS, & modules. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize()
    {
        $this->ModuleSortContainer = "Profile";
        $this->Head = new HeadModule($this);
        $this->addJsFile("jquery.js");
        $this->addJsFile("jquery.form.js");
        $this->addJsFile("jquery.popup.js");
        $this->addJsFile("jquery.gardenhandleajaxform.js");
        $this->addJsFile("jquery.autosize.min.js");
        $this->addJsFile("global.js");
        $this->addJsFile("cropimage.js");
        $this->addJsFile("vendors/clipboard.min.js");

        $this->addCssFile("style.css");
        $this->addCssFile("vanillicon.css", "static");
        $this->addModule("GuestModule");
        parent::initialize();

        Gdn_Theme::section("Profile");

        if ($this->EditMode) {
            $this->CssClass .= "EditMode";
        }

        /**
         * The default Cache-Control header does not include no-store, which can cause issues with outdated session
         * information (e.g. message button missing). The same check is performed here as in Gdn_Controller before the
         * Cache-Control header is added, but this value includes the no-store specifier.
         */
        if (Gdn::session()->isValid()) {
            $this->setHeader("Cache-Control", "private, no-cache, no-store, max-age=0, must-revalidate");
        }

        $this->setData("Breadcrumbs", []);
        $this->CanEditPhotos =
            Gdn::session()->checkRankedPermission(c("Garden.Profile.EditPhotos", true)) ||
            Gdn::session()->checkPermission("Garden.Users.Edit");
    }

    /**
     * Show activity feed for this user.
     *
     * @param int|string $userReference Unique identifier, possible ID or username.
     * @param string $username Username.
     * @param int|string $userID Unique ID.
     * @param int|string $page How many to skip (for paging).
     */
    public function activity($userReference = "", $username = "", $userID = "", $page = "")
    {
        $this->permission("Garden.Profiles.View");
        $this->editMode(false);

        // Object setup
        $session = Gdn::session();
        $this->ActivityModel = new ActivityModel();

        // Calculate offset.
        [$offset, $limit] = offsetLimit($page, 30);

        // Get user, tab, and comment
        $this->getUserInfo($userReference, $username, $userID);
        $userID = $this->User->UserID;
        $username = $this->User->Name;

        $this->_setBreadcrumbs(t("Activity"), userUrl($this->User, "", "activity"));

        $this->setTabView("Activity");
        $comment = $this->Form->getFormValue("Comment");

        // Load data to display
        $this->ProfileUserID = $this->User->UserID;
        $limit = 30;

        $notifyUserIDs = [ActivityModel::NOTIFY_PUBLIC];
        if (Gdn::session()->checkPermission("Garden.Moderation.Manage")) {
            $notifyUserIDs[] = ActivityModel::NOTIFY_MODS;
        }

        $activities = $this->ActivityModel
            ->getWhere(["ActivityUserID" => $userID, "NotifyUserID" => $notifyUserIDs], "", "", $limit, $offset)
            ->resultArray();
        $this->ActivityModel->joinComments($activities);
        $this->setData("Activities", $activities);
        if (count($activities) > 0) {
            $lastActivity = reset($activities);
            $lastModifiedDate = Gdn_Format::toTimestamp($this->User->DateUpdated);
            $lastActivityDate = Gdn_Format::toTimestamp($lastActivity["DateInserted"]);
            if ($lastModifiedDate < $lastActivityDate) {
                $lastModifiedDate = $lastActivityDate;
            }

            // Make sure to only query this page if the user has no new activity since the requesting browser last saw it.
            $this->setLastModified($lastModifiedDate);
        }

        // Set the canonical Url.
        $this->canonicalUrl(url(userUrl($this->User), true));

        $this->render();
    }

    /**
     * Clear user's current status message.
     *
     * @param mixed $userID
     * @since 2.0.0
     * @access public
     */
    public function clear($userID = "")
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }

        $userID = is_numeric($userID) ? $userID : 0;
        $session = Gdn::session();
        if ($userID != $session->UserID && !$session->checkPermission("Garden.Moderation.Manage")) {
            throw permissionException("Garden.Moderation.Manage");
        }

        if ($userID > 0) {
            $this->UserModel->saveAbout($userID, "");
        }

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            redirectTo("/profile");
        } else {
            $this->jsonTarget("#Status", "", "Remove");
            $this->render("Blank", "Utility");
        }
    }

    /**
     * Lists the connections to other sites.
     *
     * @param int|string $UserReference
     * @param string $Username
     * @since 2.1
     */
    public function connections($UserReference = "", $Username = "")
    {
        $this->permission("Garden.SignIn.Allow");
        $this->getUserInfo($UserReference, $Username, "", true);
        $UserID = valr("User.UserID", $this);
        $this->_setBreadcrumbs(t("Social"), userUrl($this->User, "", "connections"));

        $PModel = new Gdn_AuthenticationProviderModel();
        $Providers = $PModel->getProviders();

        $this->setData("_Providers", $Providers);
        $this->setData("Connections", []);
        $this->EventArguments["User"] = $this->User;
        $this->fireEvent("GetConnections");

        // Add some connection information.
        foreach ($this->Data["Connections"] as &$Row) {
            $Provider = val($Row["ProviderKey"], $Providers, []);

            touchValue("Connected", $Row, !is_null(val("UniqueID", $Provider, null)));
        }

        $this->canonicalUrl(url(userUrl($this->User, "", "connections"), true));
        $this->title(t("Connections"));
        require_once $this->fetchViewLocation("connection_functions");
        $this->render();
    }

    /**
     * Generic way to get count via UserModel->profileCount().
     *
     * @param string $column Name of column to count for this user.
     * @param int|false $userID Defaults to current session.
     */
    public function count($column, $userID = false)
    {
        $column = "Count" . ucfirst($column);
        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }

        if ($userID !== Gdn::session()->UserID) {
            $this->permission("Garden.Settings.Manage");
        }

        $count = $this->UserModel->profileCount($userID, $column);
        $this->setData($column, $count);
        $this->setData("_Value", $count);
        $this->setData("_CssClass", "Count");
        $this->render("Value", "Utility");
    }

    /**
     * Delete an invitation that has already been accepted.
     * @param int $invitationID
     * @throws Exception The inviation was not found or the user doesn't have permission to remove it.
     */
    public function deleteInvitation($invitationID)
    {
        $this->permission("Garden.SignIn.Allow");

        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException("GET");
        }

        $invitationModel = new InvitationModel();

        $result = $invitationModel->deleteID($invitationID);
        if ($result) {
            $this->informMessage(t("The invitation was removed successfully."));
            $this->jsonTarget(".js-invitation[data-id=\"{$invitationID}\"]", "", "SlideUp");
        } else {
            $this->informMessage(t("Unable to remove the invitation."));
        }

        $this->render("Blank", "Utility");
    }

    /**
     *
     *
     * @param string $UserReference
     * @param string $Username
     * @param $Provider
     * @throws Exception
     */
    public function disconnect($UserReference = "", $Username = "", $Provider = "")
    {
        if (empty($Provider)) {
            throw new InvalidArgumentException("Provider value shouldn't be empty");
        }
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }

        $this->permission("Garden.SignIn.Allow");
        $this->getUserInfo($UserReference, $Username, "", true);

        // First try and delete the authentication the fast way.
        Gdn::sql()->delete("UserAuthentication", ["UserID" => $this->User->UserID, "ProviderKey" => $Provider]);

        // Delete the profile information.
        Gdn::userModel()->saveAttribute($this->User->UserID, $Provider, null);

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            redirectTo(userUrl($this->User, "", "connections"));
        } else {
            // Grab all of the providers again.
            $PModel = new Gdn_AuthenticationProviderModel();
            $Providers = $PModel->getProviders();

            $this->setData("_Providers", $Providers);
            $this->setData("Connections", []);
            $this->fireEvent("GetConnections");

            // Send back the connection buttons.
            require_once $this->fetchViewLocation("connection_functions");

            foreach ($this->data("Connections") as $Key => $Row) {
                $Provider = val($Row["ProviderKey"], $Providers, []);
                touchValue("Connected", $Row, !is_null(val("UniqueID", $Provider, null)));

                ob_start();
                writeConnection($Row);
                $connection = ob_get_contents();
                ob_end_clean();
                $this->jsonTarget("#Provider_$Key", $connection, "ReplaceWith");
            }

            $this->render("Blank", "Utility", "Dashboard");
        }
    }

    /**
     * Edit user account.
     *
     * @param mixed $userReference Username or User ID.
     * @param string $username
     * @param string|int $userID
     */
    public function edit($userReference = "", $username = "", $userID = "")
    {
        if (Gdn::config(ProfileFieldModel::CONFIG_FEATURE_FLAG) && !\Gdn::request()->getMeta("isApi", false)) {
            redirectTo("/profile/account-privacy/" . $userReference);
        }

        $this->permission(["Garden.SignIn.Allow", "Garden.Profiles.Edit"], true);

        $this->getUserInfo($userReference, $username, $userID, true);
        $userID = valr("User.UserID", $this);
        $settings = [];

        $this->buildProfile();

        // Set up form
        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        $canSetPrivateProfile =
            Gdn::session()->checkPermission("Garden.Profiles.Edit") ||
            Gdn::session()->checkPermission("Garden.Users.Edit");
        $this->setData("_CanSetPrivateProfile", $canSetPrivateProfile);
        $user["Private"] = forceBool(Gdn::userModel()->getAttribute($userID, "Private", "0"), "0", "1", "0");
        $this->Form->setModel(Gdn::userModel());
        $this->Form->setData($user);

        // Decide if they have ability to edit the username
        $canEditUsername = Gdn::session()->checkPermission("Garden.Users.Edit");
        $this->setData("_CanEditUsername", $canEditUsername);

        // Decide if they have ability to edit the email
        $emailEnabled = (bool) c("Garden.Profile.EditEmails", true) && !UserModel::noEmail();
        $canEditEmail = ($emailEnabled && $userID == Gdn::session()->UserID) || checkPermission("Garden.Users.Edit");
        $this->setData("_CanEditEmail", $canEditEmail);

        // Decide if they have ability to confirm users
        $confirmed = (bool) valr("User.Confirmed", $this);
        $canConfirmEmail = UserModel::requireConfirmEmail() && checkPermission("Garden.Users.Edit");
        $this->setData("_CanConfirmEmail", $canConfirmEmail);
        $this->setData("_EmailConfirmed", $confirmed);
        $this->Form->setValue("ConfirmEmail", (int) $confirmed);
        $sessionUserID = Gdn::session()->UserID;
        // Decide if we can *see* email
        $this->setData(
            "_CanViewPersonalInfo",
            $sessionUserID == val("UserID", $user) ||
                checkPermission("Garden.PersonalInfo.View") ||
                checkPermission("Garden.Users.Edit")
        );

        // Decide if there will be a `Titles` field.
        $canAddEditTitle = c("Garden.Profile.Titles", false);
        $this->setData("_CanAddEditTitle", $canAddEditTitle);

        // Decide if there will be `Locations` field.
        $canAddEditLocations = c("Garden.Profile.Locations", false);
        $this->setData("_CanAddEditLocation", $canAddEditLocations);

        // Define gender dropdown options
        $this->GenderOptions = self::getGenderOptions();

        $this->fireEvent("BeforeEdit");

        if ($this->Form->authenticatedPostBack(true)) {
            // If we're changing the email address, militarize our reauth with no cooldown allowed.
            $authOptions = [];
            $submittedEmail = $this->Form->getFormValue("Email", null);

            if ($submittedEmail !== null && $canEditEmail && $user["Email"] !== $submittedEmail) {
                $authOptions["ForceTimeout"] = true;
            }

            // Authenticate user once.
            $this->reauth($authOptions);

            $this->Form->setFormValue("UserID", $userID);

            // This field cannot be updated from here.
            $this->Form->removeFormValue("Password");

            // If someone tries to update the email without permission, send back the original email to the form.
            if (!$canEditEmail) {
                $this->Form->setFormValue("Email", $user["Email"]);
            }

            if (!$canEditUsername) {
                $this->Form->setFormValue("Name", $user["Name"]);
            } else {
                $usernameError = t(
                    "UsernameError",
                    "Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long."
                );
                Gdn::userModel()->Validation->applyRule("Name", "Username", $usernameError);
            }

            // Do not accept Title updates if the user isn't allowed.
            if (!$canAddEditTitle) {
                $this->Form->removeFormValue("Title");
            }

            // Do not accept Location updates if the user isn't allowed.
            if (!$canAddEditLocations) {
                $this->Form->removeFormValue("Location");
            }
            // API
            // These options become available when POSTing as a user with Garden.Settings.Manage permissions

            if (Gdn::session()->checkPermission("Garden.Settings.Manage")) {
                // Role change
                $requestedRoles = $this->Form->getFormValue("RoleID", null);
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
                    $this->Form->setFormValue("RoleID", array_keys($userNewRoles));

                    // Allow saving roles
                    $settings["SaveRoles"] = true;
                }
                // Password change
                $newPassword = $this->Form->getFormValue("Password", null);
                if (!is_null($newPassword)) {
                }
            }

            // Allow mods to confirm emails
            $this->Form->removeFormValue("Confirmed");
            $confirmation = $this->Form->getFormValue("ConfirmEmail", null);
            $confirmation = !is_null($confirmation) ? (bool) $confirmation : null;

            if ($canConfirmEmail && is_bool($confirmation)) {
                $this->Form->setFormValue("Confirmed", (int) $confirmation);
            }

            // Don't allow non-mods to set an explicit photo.
            if ($photo = $this->Form->getFormValue("Photo")) {
                if (!Gdn_Upload::isUploadUri($photo)) {
                    if (!checkPermission("Garden.Users.Edit")) {
                        $this->Form->removeFormValue("Photo");
                    } elseif (!filter_var($photo, FILTER_VALIDATE_URL)) {
                        $this->Form->addError("Invalid photo URL.");
                    }
                }
            }

            if ($this->Form->save($settings) !== false) {
                $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
                $user["Private"] = forceBool(Gdn::userModel()->getAttribute($userID, "Private", "0"), "0", "1", "0");
                $this->setData("Profile", $user);

                $this->informMessage(
                    sprite("Check", "InformSprite") . t("Your changes have been saved."),
                    "Dismissable AutoDismiss HasSprite"
                );
            }
        }

        $this->title(t("Edit Profile"));
        $this->_setBreadcrumbs(t("Edit Profile"), "/profile/edit");
        $this->render();
    }

    /**
     * Create EditProfileFields page.
     *
     * @param mixed $userReference Username or User ID.
     * @param string $username
     * @param string|int $userID
     */
    public function editFields($userReference = "", $username = "", $userID = "")
    {
        $this->permission(["Garden.SignIn.Allow", "Garden.Profiles.Edit"], true);
        $profileFieldModel = Gdn::getContainer()->get(ProfileFieldModel::class);
        $hasFields = $profileFieldModel->hasVisibleFields($userID);

        $this->registerReduxActionProvider(\Gdn::getContainer()->get(ProfileFieldsPreloadProvider::class));

        if ($hasFields) {
            $this->getUserInfo($userReference, $username, $userID, true);
            $this->setData("userID", valr("User.UserID", $this));
            $this->_setBreadcrumbs(t("Edit Fields"), "/profile/edit-fields");
            $this->render();
        } else {
            $this->render("ConnectError");
        }
    }

    /**
     * Create FollowedContent page.
     *
     * @param mixed $userReference Username or User ID.
     * @param string $username
     * @param string|int $userID
     */
    public function followedContent($userReference = "", $username = "", $userID = "")
    {
        $this->permission("Garden.SignIn.Allow");
        $this->getUserInfo($userReference, $username, $userID, true);
        $this->setData("userID", valr("User.UserID", $this));
        $this->_setBreadcrumbs(t("Followed Content"), "/profile/followed-content");
        $this->render();
    }

    /**
     * Edit Account & Privacy Settings Page
     *
     * @param mixed $userReference Username or User ID.
     * @param string $username
     * @param string|int $userID
     */
    public function accountPrivacy($userReference = "", $username = "", $userID = "")
    {
        if (!Gdn::config(ProfileFieldModel::CONFIG_FEATURE_FLAG)) {
            redirectTo("/profile/edit");
        }
        $this->permission(["Garden.SignIn.Allow", "Garden.Profiles.Edit"], true);

        $this->registerReduxActionProvider(\Gdn::getContainer()->get(ProfileFieldsPreloadProvider::class));

        // Get the currently viewed user's information
        $this->getUserInfo($userReference, $username, $userID, true);
        $editingUserID = valr("User.UserID", $this);

        // Pass it along to the view
        $this->setData("_EditingUserID", $editingUserID);

        $this->title(t("Account & Privacy Settings"));
        $this->_setBreadcrumbs(t("Account & Privacy Settings"), "/profile/account-privacy");
        $this->render();
    }

    /**
     * Default profile page.
     *
     * If current user's profile, get notifications. Otherwise show their activity (if available) or discussions.
     *
     * @param int|string $user Unique identifier, possible ID or username.
     * @param string $username .
     * @param int|string $userID Unique ID.
     */
    public function index($user = "", $username = "", $userID = "", $page = false)
    {
        if (!$user && !$username && !$userID) {
            $this->permission("session.valid");
        }
        if (!Gdn::session()->isValid()) {
            //if the user hasn't signed in, check if the guest have permission to view profiles
            $this->permission("Garden.Profiles.View");
        }

        $this->editMode(false);
        $this->getUserInfo($user, $username, $userID);

        // Optional profile redirection.
        if ($this->Request->get("redirect") !== "0" && $this->profileRedirect()) {
            $this->render("blank", "utility", "dashboard");
            return;
        }

        if ($this->User->Admin == 2 && $this->Head) {
            // Don't index internal accounts. This is in part to prevent vendors from getting endless Google alerts.
            $this->Head->addTag("meta", ["name" => "robots", "content" => "noindex"]);
            $this->Head->addTag("meta", ["name" => "googlebot", "content" => "noindex"]);
        }

        if (c("Garden.Profile.ShowActivities", true)) {
            return $this->activity($user, $username, $userID, $page);
        } elseif ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            redirectTo(userUrl($this->User, "", "discussions"));
        }

        // Garden.Profile.ShowActivities is false and the user is expecting an xml or json response, so render blank.
        $this->render("blank", "utility", "dashboard");
    }

    /**
     * Default profile page.
     *
     * If current user's profile, get notifications. Otherwise show their activity (if available) or discussions.
     *
     * @param string $redirectUrl Destination URL model, including substitution tags.
     * - If omitted, we'll look into Garden.Profile.RedirectUrl for a value.
     * @return bool Returns **true** if the profile redirected or **false** otherwise.
     */
    protected function profileRedirect(string $redirectUrl = ""): bool
    {
        // If there is no specified redirect url, look for one in configurations.
        if (empty($redirectUrl)) {
            $redirectUrl = c("Garden.Profile.RedirectUrl");
        }

        // If there is one, and the user exists, try/start to build the redirection URL.
        if (!empty($redirectUrl) && !empty($this->User->UserID)) {
            $userSsoID = $this->UserModel->getDefaultSSOIDs([$this->User->UserID])[$this->User->UserID];

            // We build the redirect URL by substituting {TAGS} from the URL model.
            $urlReplacements = [
                "userID" => $this->User->UserID, // just an int, no need to encode
                "name" => rawurlencode($this->User->Name ?? ""),
                "ssoID" => rawurlencode($userSsoID ?? ""),
            ];
            $newUrl = formatString($redirectUrl, $urlReplacements);

            if ($this->deliveryType() === DELIVERY_TYPE_ALL) {
                redirectTo($newUrl, 302, false);
            } else {
                $this->setRedirectTo($newUrl, false);
                return true;
            }
        }
        return false;
    }

    /**
     * Manage current user's invitations.
     *
     * @param null $page
     * @param string $userReference
     * @param string $username
     * @param string $userID
     * @throws Exception If $maxPages is hit.
     * @since 2.0.0
     * @access public
     */
    public function invitations($page = null, $userReference = "", $username = "", $userID = "")
    {
        $this->permission("Garden.SignIn.Allow");
        $session = Gdn::getContainer()->get(Gdn_Session::class);
        $request = Gdn::getContainer()->get(Gdn_Request::class);

        if (!$session->isValid()) {
            redirectTo("/entry/signin?Target=" . $request->getUrl());
        }
        $this->editMode(false);

        // check if first parameter is a page parameter
        // correct parameters values
        if (is_object($this->User) && $this->User->Name === $page) {
            $userReference = $page;
            $page = "p1";
        }
        $this->getUserInfo($userReference, $username, $userID, $this->Form->authenticatedPostBack());
        // Determine if is own profile
        $this->isOwnProfile = $session->User->UserID === $this->User->UserID;

        if (!$this->isOwnProfile) {
            if (!$session->checkPermission("Garden.Moderation.Manage")) {
                throw permissionException();
            }
        }
        $this->setTabView("Invitations");
        // Determine offset from $page
        [$offset, $limit] = offsetLimit($page, c("Vanilla.Discussions.PerPage", 30), true);
        $page = pageNumber($offset, $limit);

        // Allow page manipulation
        $this->EventArguments["Page"] = &$page;
        $this->EventArguments["Offset"] = &$offset;
        $this->EventArguments["Limit"] = &$limit;
        /** @var EventManager $eventManager */
        $eventManager = Gdn::getContainer()->get(EventManager::class);
        $eventManager->fire("AfterPageCalculation");

        // We want to limit the number of pages on large databases because requesting a super-high page can kill the db.
        $maxPages = c("Vanilla.Discussions.MaxPages");
        if ($maxPages && $page > $maxPages) {
            throw notFoundException();
        }

        $invitationModel = new InvitationModel();
        $this->Form->setModel($invitationModel);
        if ($this->Form->authenticatedPostBack()) {
            // Remove insecure invitation data.
            $this->Form->removeFormValue(["Name", "DateExpires", "RoleIDs"]);

            // Send the invitation
            if ($this->Form->save($this->UserModel)) {
                $this->informMessage(t("Your invitation has been sent."));
                $this->Form->clearInputs();
            }
        }

        if (!empty($this->User->UserID)) {
            $insertUserID = $this->User->UserID;
        } else {
            $insertUserID = $session->UserID;
        }

        $this->InvitationsLeft = $this->UserModel->getInvitationCount($session->UserID);

        // start where clause to query
        $where = ["InsertUserID" => $insertUserID];

        $this->InvitationCount = $invitationModel->getCount($where);
        $this->InvitationData = $invitationModel->getWhere($where, "DateInserted", "desc", $limit, $offset);

        $this->checkPageRange($offset, $this->InvitationCount);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments["PagerType"] = "Pager";
        $this->fireEvent("BeforeBuildPager");
        if (!$this->data("_PagerUrl")) {
            $this->setData("_PagerUrl", "profile/invitations/{Page}/" . $this->User->Name);
        }
        $this->setData("_PagerUrl", $this->data("_PagerUrl"));
        $this->Pager = $PagerFactory->getPager($this->EventArguments["PagerType"], $this);
        $this->Pager->ClientID = "Pager";
        $this->Pager->configure($offset, $limit, $this->InvitationCount, $this->data("_PagerUrl"));

        PagerModule::current($this->Pager);

        $this->setData("_Page", $page);
        $this->setData("_Limit", $limit);
        $this->fireEvent("AfterBuildPager");

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
    public function noMobile($type = "desktop")
    {
        // Only validate CSRF token for authenticated users because guests do not get the transient key cookie.
        if (Gdn::session()->isValid()) {
            $valid = Gdn::request()->isAuthenticatedPostBack(true);
        } else {
            $valid = Gdn::request()->isPostBack();
        }

        if (!$valid) {
            throw new Exception("Requires POST", 405);
        }

        $type = strtolower($type);

        if ($type == "1") {
            Gdn_CookieIdentity::deleteCookie("X-UA-Device-Force");
        } else {
            if (in_array($type, ["mobile", "desktop", "tablet", "app"])) {
                $type = $type;
            } else {
                $type = "desktop";
            }

            // Set 48-hour "no mobile" cookie
            $expiration = time() + 172800;
            $path = c("Garden.Cookie.Path");
            $domain = c("Garden.Cookie.Domain");
            safeCookie("X-UA-Device-Force", $type, $expiration, $path, $domain);
        }

        $this->setRedirectTo("/");
        $this->render("Blank", "Utility", "Dashboard");
    }

    /**
     * Show notifications for current user.
     *
     * @param int|false $page Number to skip (paging).
     */
    public function notifications($page = false)
    {
        $this->permission("Garden.SignIn.Allow");
        $this->editMode(false);

        [$offset, $limit] = offsetLimit($page, 30);

        $this->getUserInfo();
        $this->_setBreadcrumbs(t("Notifications"), "/profile/notifications");

        $this->setTabView("Notifications");
        $session = Gdn::session();

        $this->ActivityModel = new ActivityModel();

        // Drop notification count back to zero.
        $this->longRunner->runApi(new LongRunnerAction(ActivityModel::class, "markAllRead", [$session->UserID]));

        // Get notifications data.
        $activities = $this->ActivityModel->getNotifications($session->UserID, $offset, $limit)->resultArray();
        $this->ActivityModel->joinComments($activities);
        $this->setData("Activities", $activities);
        unset($activities);
        //$TotalRecords = $this->ActivityModel->getCountNotifications($Session->UserID);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->Pager = $pagerFactory->getPager("MorePager", $this);
        $this->Pager->MoreCode = "More";
        $this->Pager->LessCode = "Newer Notifications";
        $this->Pager->ClientID = "Pager";
        $this->Pager->configure($offset, $limit, false, 'profile/notifications/%1$s/');
        // Deliver json data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson("LessRow", $this->Pager->toString("less"));
            $this->setJson("MoreRow", $this->Pager->toString("more"));
            if ($offset > 0) {
                $this->View = "activities";
                $this->ControllerName = "Activity";
            }
        }
        $this->render();
    }

    public function notificationsPopin($transientKey = "")
    {
        $this->permission("Garden.SignIn.Allow");

        if (Gdn::session()->validateTransientKey($transientKey) !== true) {
            throw new Gdn_UserException(t("Invalid CSRF token.", "Invalid CSRF token. Please try again."), 403);
        }

        $where = [
            "NotifyUserID" => Gdn::session()->UserID,
            "DateUpdated >=" => Gdn_Format::toDateTime(strtotime("-2 weeks")),
        ];

        $this->ActivityModel = new ActivityModel();
        $activities = $this->ActivityModel->getWhere($where, "", "", 5, 0)->resultArray();
        $this->setData("Activities", $activities);
        $this->longRunner->runApi(new LongRunnerAction(ActivityModel::class, "markAllRead", [Gdn::session()->UserID]));

        $this->setData("Title", t("Notifications"));
        $this->render("Popin", "Activity", "Dashboard");
    }

    /**
     * Set new password for current user.
     *
     * @since 2.0.0
     * @access public
     */
    public function password()
    {
        if (Gdn::config(ProfileFieldModel::CONFIG_FEATURE_FLAG) && !\Gdn::request()->getMeta("isApi", false)) {
            redirectTo("/profile/account-privacy");
        }

        $this->permission("Garden.SignIn.Allow");

        $isSpamming = false;
        $floodGate = FloodControlHelper::configure($this, "Vanilla", "Password");
        $this->setFloodControlEnabled(true);

        // Don't allow password editing if using SSO Connect ONLY.
        // This is for security. We encountered the case where a customer charges
        // for membership using their external application and use SSO to let
        // their customers into Vanilla. If you allow those people to change their
        // password in Vanilla, they will then be able to log into Vanilla using
        // Vanilla's login form regardless of the state of their membership in the
        // external app.
        if (c("Garden.Registration.Method") == "Connect") {
            throw forbiddenException("@" . t("You cannot change your password when using SSO authentication only."));
        }

        Gdn::userModel()->addPasswordStrength($this);

        // Get user data and set up form
        $this->getUserInfo();

        $this->Form->setModel($this->UserModel);
        $this->addDefinition("Username", $this->User->Name);

        if (
            $this->Form->authenticatedPostBack() === true &&
            !($isSpamming = $this->checkUserSpamming(Gdn::session()->UserID, $floodGate))
        ) {
            $this->Form->setFormValue("UserID", $this->User->UserID);
            $this->UserModel->defineSchema();
            //         $this->UserModel->Validation->addValidationField('OldPassword', $this->Form->formValues());

            // No password may have been set if they have only signed in with a connect plugin
            if (!$this->User->HashMethod || $this->User->HashMethod == "Vanilla") {
                $this->UserModel->Validation->applyRule("OldPassword", "Required");
                $this->UserModel->Validation->applyRule(
                    "OldPassword",
                    "OldPassword",
                    "Your old password was incorrect."
                );
            }

            $this->UserModel->Validation->applyRule("Password", "Required");
            $this->UserModel->Validation->applyRule("Password", "Strength");
            $this->UserModel->Validation->applyRule("Password", "Match");

            if ($this->Form->save()) {
                $this->informMessage(
                    sprite("Check", "InformSprite") . t("Your password has been changed."),
                    "Dismissable AutoDismiss HasSprite"
                );

                $this->Form->clearInputs();

                Logger::event("password_change", Logger::INFO, "{InsertName} changed password.", [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                ]);
            } else {
                Logger::event("password_change_failure", Logger::INFO, "{InsertName} failed to change password.", [
                    "Error" => $this->Form->errorString(),
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                ]);
            }
        }

        if ($isSpamming) {
            $message = sprintf(
                t(
                    'You have tried to reset your password %1$s times within %2$s seconds. You must wait at least %3$s seconds before attempting again.'
                ),
                $this->postCountThreshold,
                $this->timeSpan,
                $this->lockTime
            );
            throw new Gdn_UserException($message);
        }

        $this->title(t("Change My Password"));
        $this->_setBreadcrumbs(t("Change My Password"), "/profile/password");
        $this->render();
    }

    /**
     * Set user's photo (avatar).
     *
     * @param mixed $userReference Unique identifier, possible username or ID.
     * @param string $username The username.
     * @param string $userID The user's ID.
     *
     * @throws Exception
     * @throws Gdn_UserException
     * @since 2.0.0
     * @access public
     *
     */
    public function picture($userReference = "", $username = "", $userID = "")
    {
        $this->addJsFile("profile.js");

        if (!$this->CanEditPhotos) {
            throw forbiddenException("@Editing user photos has been disabled.");
        }

        // Permission checks
        $this->permission(["Garden.Profiles.Edit", "Moderation.Profiles.Edit", "Garden.ProfilePicture.Edit"], false);
        $session = Gdn::session();
        if (!$session->isValid()) {
            $this->Form->addError("You must be authenticated in order to use this form.");
        }

        // Check ability to manipulate image
        if (function_exists("gd_info")) {
            $gdInfo = gd_info();
            $gdVersion = preg_replace("/[a-z ()]+/i", "", $gdInfo["GD Version"]);
            if ($gdVersion < 2) {
                throw new Exception(
                    sprintf(
                        t(
                            "This installation of GD is too old (v%s). Vanilla requires at least version 2 or compatible."
                        ),
                        $gdVersion
                    )
                );
            }
        } else {
            throw new Exception(
                sprintf(t("Unable to detect PHP GD installed on this system. Vanilla requires GD version 2 or better."))
            );
        }

        $isSvgImage = Gdn_UploadSvg::isSvg("Avatar");
        // Get user data & prep form.
        if ($this->Form->authenticatedPostBack() && $this->Form->getFormValue("UserID")) {
            $userID = $this->Form->getFormValue("UserID");
        }
        $this->getUserInfo($userReference, $username, $userID, true);

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $this->Form->setModel($configurationModel);
        $avatar = $this->User->Photo;
        if ($avatar === null) {
            $avatar = UserModel::getDefaultAvatarUrl();
        }

        $extension = strtolower(pathinfo($avatar, PATHINFO_EXTENSION));
        $source = "";
        $crop = null;
        // Don't crop if the uploaded image is a svg file

        if (
            ((!isset($_FILES["Avatar"]) && $extension !== "svg") ||
                (isset($_FILES["Avatar"]) && !$isSvgImage && $extension !== "svg")) &&
            $this->isUploadedAvatar($avatar)
        ) {
            // Get the image source so we can manipulate it in the crop module.
            $upload = new Gdn_UploadImage();
            $thumbnailSize = c("Garden.Thumbnail.Size");
            $basename = changeBasename($avatar, "p%s");
            $source = $upload->copyLocal($basename);

            // Set up cropping.
            $crop = new CropImageModule($this, $this->Form, $thumbnailSize, $thumbnailSize, $source);
            $crop->setExistingCropUrl(Gdn_UploadImage::url(changeBasename($avatar, "n%s")));
            $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($avatar, "p%s")));
            $this->setData("crop", $crop);
        } else {
            $this->setData("avatar", isUrl($avatar) ? $avatar : Gdn_UploadImage::url(changeBasename($avatar, "p%s")));
        }

        if (!$this->Form->authenticatedPostBack()) {
            $this->Form->setData($configurationModel->Data);
        } elseif ($this->Form->save() !== false) {
            $upload = $isSvgImage ? new Gdn_UploadSvg() : new Gdn_UploadImage();
            $newAvatar = false;
            if ($tmpAvatar = $upload->validateUpload("Avatar", false)) {
                // New upload
                $thumbOptions = ["Crop" => true, "SaveGif" => c("Garden.Thumbnail.SaveGif")];
                $newAvatar = $this->saveAvatars($tmpAvatar, $thumbOptions, $upload, $isSvgImage);
            } elseif ($avatar && $crop && $crop->isCropped()) {
                // New thumbnail
                $tmpAvatar = $source;
                $thumbOptions = [
                    "Crop" => true,
                    "SourceX" => $crop->getCropXValue(),
                    "SourceY" => $crop->getCropYValue(),
                    "SourceWidth" => $crop->getCropWidth(),
                    "SourceHeight" => $crop->getCropHeight(),
                ];
                $newAvatar = $this->saveAvatars($tmpAvatar, $thumbOptions);
            }
            if ($this->Form->errorCount() == 0) {
                if ($newAvatar !== false && !$isSvgImage) {
                    $thumbnailSize = c("Garden.Thumbnail.Size");
                    // Update crop properties.
                    $basename = changeBasename($newAvatar, "p%s");
                    $source = $upload->copyLocal($basename);
                    $crop = new CropImageModule($this, $this->Form, $thumbnailSize, $thumbnailSize, $source);
                    $crop->setSize($thumbnailSize, $thumbnailSize);
                    $crop->setExistingCropUrl(Gdn_UploadImage::url(changeBasename($newAvatar, "n%s")));
                    $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($newAvatar, "p%s")));
                    $this->setData("crop", $crop);
                } else {
                    $this->setData(
                        "avatar",
                        isUrl($this->User->Photo)
                            ? $this->User->Photo
                            : Gdn_UploadImage::url(changeBasename($this->User->Photo, "p%s"))
                    );
                }
            }
            if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
                $this->jsonTarget("", "", "Refresh");

                $this->setRedirectTo(userUrl($this->User));
            }
            if (!empty($upload->Exception)) {
                $this->Form->addError($upload->Exception);
            } else {
                $this->informMessage(t("Your settings have been saved."));
            }
        }

        if (val("SideMenuModule", val("Panel", val("Assets", $this)))) {
            /** @var SideMenuModule $sidemenu */
            $sidemenu = $this->Assets["Panel"]["SideMenuModule"];
            $sidemenu->highlightRoute("/profile/picture");
        }

        $this->title(t("Change Picture"));
        $this->_setBreadcrumbs(t("Change My Picture"), userUrl($this->User, "", "picture"));
        $this->render("picture", "profile", "dashboard");
    }

    /**
     * Deletes uploaded avatars in the profile size format.
     *
     * @param string $avatar The avatar to delete.
     */
    private function deleteAvatars($avatar = "")
    {
        if ($avatar && $this->isUploadedAvatar($avatar)) {
            $upload = new Gdn_Upload();
            $subdir = stringBeginsWith(dirname($avatar), PATH_UPLOADS . "/", false, true);
            $upload->delete($subdir . "/" . basename(changeBasename($avatar, "p%s")));
        }
    }

    /**
     * Test whether a path is a full url, which gives us an indication whether it's an upload or not.
     *
     * @param string $avatar The path to the avatar image to test
     * @return bool Whether the avatar has been uploaded.
     */
    private function isUploadedAvatar($avatar)
    {
        return !isUrl($avatar);
    }

    /**
     * Saves the avatar to /uploads in two sizes:
     *   p* : The profile-sized image, which is constrained by Garden.Profile.MaxWidth and Garden.Profile.MaxHeight.
     *   n* : The thumbnail-sized image, which is constrained and cropped according to Garden.Thumbnail.Size.
     * Also deletes the old avatars.
     *
     * @param string $source The path to the local copy of the image.
     * @param array $thumbOptions The options to save the thumbnail-sized avatar with.
     * @param Gdn_Upload|null $upload The upload object.
     * @param bool $isSvg Whether the image is an SVG.
     * @return bool Whether the saves were successful.
     */
    private function saveAvatars($source, $thumbOptions, $upload = null, $isSvg = false)
    {
        try {
            $ext = "";
            if (!$upload) {
                $upload = $isSvg ? new Gdn_UploadSvg() : new Gdn_UploadImage();
                $ext = $isSvg ? "svg" : "jpg";
            }

            // Generate the target image name
            $targetImage = $upload->generateTargetName(PATH_UPLOADS, $ext, true);
            $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);
            $subdir = stringBeginsWith(dirname($targetImage), PATH_UPLOADS . "/", false, true);

            if ($isSvg) {
                $parts = $upload->saveAs($source, self::AVATAR_FOLDER . "/$subdir/p$imageBaseName", [], true);
                $upload->saveAs($source, self::AVATAR_FOLDER . "/$subdir/n$imageBaseName", [], true);
            } else {
                // Save the profile size image.
                $parts = Gdn_UploadImage::saveImageAs(
                    $source,
                    self::AVATAR_FOLDER . "/$subdir/p$imageBaseName",
                    c("Garden.Profile.MaxHeight"),
                    c("Garden.Profile.MaxWidth"),
                    ["SaveGif" => c("Garden.Thumbnail.SaveGif")]
                );

                $thumbnailSize = c("Garden.Thumbnail.Size");

                // Save the thumbnail size image.
                Gdn_UploadImage::saveImageAs(
                    $source,
                    self::AVATAR_FOLDER . "/$subdir/n$imageBaseName",
                    $thumbnailSize,
                    $thumbnailSize,
                    $thumbOptions
                );
            }
        } catch (Exception $ex) {
            $this->Form->addError($ex);
            return false;
        }

        $bak = $this->User->Photo;

        $userPhoto = sprintf($parts["SaveFormat"], self::AVATAR_FOLDER . "/$subdir/$imageBaseName");
        if (
            !$this->UserModel->save(["UserID" => $this->User->UserID, "Photo" => $userPhoto], ["CheckExisting" => true])
        ) {
            $this->Form->setValidationResults($this->UserModel->validationResults());
        } else {
            $this->User->Photo = $userPhoto;
        }

        $this->deleteAvatars($bak);

        return $userPhoto;
    }

    /**
     * Edit user's preferences (mostly notification settings).
     *
     * @param int|string $userReference Unique identifier, possibly username or ID.
     * @param string $username .
     * @param int|string $userID Unique identifier.
     */
    public function preferences($userReference = "", $username = "", $userID = "")
    {
        $this->permission("Garden.SignIn.Allow");

        // Get user data
        $this->getUserInfo($userReference, $username, $userID, true);
        $this->setData("userID", valr("User.UserID", $this));

        // Set title, breadcrumbs, and render the page.
        $this->title(t("Notification Preferences"));
        $this->_setBreadcrumbs($this->data("Title"), $this->canonicalUrl());
        $this->render();
    }

    /**
     * Prompt a user to enter their password, then re-submit form. Used for reauthenticating for sensitive actions.
     */
    public function authenticate()
    {
        $this->permission("Garden.SignIn.Allow");

        if (empty(Gdn::session()->User)) {
            throw permissionException("@" . t("You must be signed in."));
        }

        // Make sure the user has a password that can even be checked.
        $pw = Gdn::getContainer()->get(Gdn_PasswordHash::class);
        $user = Gdn::userModel()->getID(Gdn::session()->UserID, DATASET_TYPE_ARRAY);
        if (!$pw->hasAlgorithm($user["HashMethod"] ?? "")) {
            throw permissionException("@" . t("You must reset your password before proceeding."));
        }

        if ($this->Form->getFormValue("DoReauthenticate")) {
            $originalSubmission = $this->Form->getFormValue("OriginalSubmission");
            if ($this->Form->authenticatedPostBack()) {
                $this->Form->validateRule(
                    "AuthenticatePassword",
                    "ValidateRequired",
                    sprintf(t("ValidateRequired"), "Password")
                );
                if ($this->Form->errorCount() === 0) {
                    $password = $this->Form->getFormValue("AuthenticatePassword");
                    $result = Gdn::userModel()->validateCredentials("", Gdn::session()->UserID, $password);
                    if ($result !== false) {
                        $now = time();
                        Gdn::authenticator()
                            ->identity()
                            ->setAuthTime($now);
                        $formData = json_decode($originalSubmission, true);
                        if (is_array($formData)) {
                            Gdn::request()->setRequestArguments(Gdn_Request::INPUT_POST, $formData);
                            self::$isAuthenticated = true;
                        }
                        // Make sure there is no re-authentication flag or else we are headed for a loop.
                        Gdn::request()->setValueOn(Gdn_Request::INPUT_POST, "DoReauthenticate", false);
                        Gdn::dispatcher()->dispatch();
                        throw new ExitException();
                    } else {
                        $this->Form->addError(t("Invalid password."), "AuthenticatePassword");
                    }
                }
            }
        } else {
            $originalSubmission = json_encode(Gdn::request()->post());
        }

        $this->Form->addHidden("DoReauthenticate", 1);
        $this->Form->addHidden("OriginalSubmission", $originalSubmission);

        $this->getUserInfo();
        $this->title(t("Enter Your Password"));
        $this->render();
    }

    /**
     * Remove the user's photo.
     *
     * @param mixed $userReference Unique identifier, possibly username or ID.
     * @param string $username .
     * @param string $tk Security token.
     * @since 2.0.0
     * @access public
     */
    public function removePicture($userReference = "", $username = "", $tk = "", $deliveryType = "")
    {
        $this->permission("Garden.SignIn.Allow");
        $session = Gdn::session();
        if (!$session->isValid()) {
            $this->Form->addError("You must be authenticated in order to use this form.");
        }

        // Get user data & another permission check.
        $this->getUserInfo($userReference, $username, "", true);

        if ($session->validateTransientKey($tk) && is_object($this->User)) {
            $hasRemovePermission = checkPermission("Garden.Users.Edit") || checkPermission("Moderation.Profiles.Edit");
            if ($this->User->UserID == $session->UserID || $hasRemovePermission) {
                // Do removal, set message, redirect
                Gdn::userModel()->removePicture($this->User->UserID);
                $this->informMessage(t("Your picture has been removed."));
            }
        }

        if ($deliveryType === DELIVERY_TYPE_VIEW) {
            $redirectUrl = userUrl($this->User);
        } else {
            $redirectUrl = userUrl($this->User, "", "picture");
        }
        redirectTo($redirectUrl);
    }

    /**
     * Let user send an invitation.
     *
     * @param int|string $invitationID Unique identifier.
     */
    public function sendInvite($invitationID = "")
    {
        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException("GET");
        }

        $this->permission("Garden.SignIn.Allow");
        $invitationModel = new InvitationModel();
        $session = Gdn::session();

        try {
            $email = new Gdn_Email();
            $invitationModel->send($invitationID, $email);
        } catch (Exception $ex) {
            $this->Form->addError(strip_tags($ex->getMessage()));
        }
        if ($this->Form->errorCount() == 0) {
            $this->informMessage(t("The invitation was sent successfully."));
        }

        $this->View = "Invitations";
        $this->invitations();
    }

    public function _setBreadcrumbs($name = null, $url = null)
    {
        // Add the root link.
        if (val("UserID", $this->User) == Gdn::session()->UserID) {
            $root = ["Name" => t("Profile"), "Url" => "/profile"];
            $breadcrumb = ["Name" => $name, "Url" => $url];
        } else {
            $nameUnique = c("Garden.Registration.NameUnique");

            $root = ["Name" => val("Name", $this->User), "Url" => userUrl($this->User)];
            $breadcrumb = [
                "Name" => $name,
                "Url" =>
                    $url .
                    "/" .
                    ($nameUnique ? "" : val("UserID", $this->User) . "/") .
                    rawurlencode(val("Name", $this->User)),
            ];
        }

        $this->Data["Breadcrumbs"][] = $root;

        if ($name && !str_starts_with($root["Url"], $url)) {
            $this->Data["Breadcrumbs"][] = ["Name" => $name, "Url" => $url];
        }
    }

    /**
     * Set user's thumbnail (crop & center photo).
     *
     * @param mixed $userReference Unique identifier, possible username or ID.
     * @param string $username .
     * @since 2.0.0
     * @access public
     */
    public function thumbnail($userReference = "", $username = "")
    {
        $this->picture($userReference, $username);
    }

    /**
     * Edit user's preferences (mostly notification settings).
     *
     * @param int|string $userReference Unique identifier, possibly username or ID.
     * @param string $username .
     * @param int|string $userID Unique identifier.
     */
    public function tokens($userReference = "", $username = "", $userID = "")
    {
        $this->addJsFile("profile.js");
        $this->permission("Garden.SignIn.Allow");

        // Get user data
        $this->getUserInfo($userReference, $username, $userID, true);

        /* @var TokensApiController $tokenApi */
        $tokenApi = Gdn::getContainer()->get(TokensApiController::class);
        $tokens = $tokenApi->index();

        $this->title(t("Personal Access Tokens"));
        $this->_setBreadcrumbs($this->data("Title"), $this->canonicalUrl());
        $this->setData("Tokens", $tokens);
        $this->render();
    }

    public function token($userReference = "", $username = "", $userID = "")
    {
        $this->addJsFile("profile.js");
        $this->permission("Garden.SignIn.Allow");

        // Get user data
        $this->getUserInfo($userReference, $username, $userID, true);

        /* @var TokensApiController $tokenApi */
        $tokenApi = Gdn::getContainer()->get(TokensApiController::class);

        if ($this->Form->authenticatedPostBack(true)) {
            try {
                $token = $tokenApi->post([
                    "name" => $this->Form->getFormValue("Name"),
                    "transientKey" => $this->Form->getFormValue("TransientKey"),
                ]);

                $this->jsonTarget(".DataList-Tokens", $this->revealTokenRow($token), "Prepend");
            } catch (ValidationException $ex) {
                $this->Form->addError($ex);
            }
        }
        $this->title(t("Add Token"));
        $this->_setBreadcrumbs($this->data("Title"), $this->canonicalUrl());
        $this->render();
    }

    public function tokenReveal($accessTokenID)
    {
        $this->permission("Garden.SignIn.Allow");

        /* @var TokensApiController $tokenApi */
        $tokenApi = Gdn::getContainer()->get(TokensApiController::class);

        if ($this->Form->authenticatedPostBack(true)) {
            try {
                $token = $tokenApi->get($accessTokenID, [
                    "transientKey" => $this->Form->getFormValue("TransientKey"),
                ]);
                $this->jsonTarget("#Token_{$token["accessTokenID"]}", $this->revealTokenRow($token), "ReplaceWith");
            } catch (ValidationException $ex) {
                $this->Form->addError($ex);
            }
        }
        $this->render("Blank", "Utility", "Dashboard");
    }

    public function tokenDelete($accessTokenID)
    {
        $this->permission("Garden.SignIn.Allow");

        /* @var TokensApiController $tokenApi */
        $tokenApi = Gdn::getContainer()->get(TokensApiController::class);

        if ($this->Form->authenticatedPostBack(true)) {
            try {
                $tokenApi->delete($accessTokenID);
                $this->jsonTarget("#Token_{$accessTokenID}", "", "SlideUp");
            } catch (ValidationException $ex) {
                $this->Form->addError($ex);
            }
        }
        $this->render("token-delete", "Profile", "Dashboard");
    }

    private function revealTokenRow($token)
    {
        $deleteUrl = url("/profile/tokenDelete?accessTokenID=" . $token["accessTokenID"]);
        $deleteStr = t("Delete");
        $tokenLabel = t("Copy To Clipboard");
        $copiedMessage = t("Copied to Clipboard!");

        return <<<EOT
<li id="Token_{$token["accessTokenID"]}" class="Item Item-Token">{$token["accessToken"]}<a href="javascript:void(0);" title="{$tokenLabel}" data-copymessage="{$copiedMessage}" data-clipboard-text="{$token["accessToken"]}" class="OptionsLink OptionsLink-Clipboard js-copyToClipboard" style="margin-left: 5px; display: none;"><svg class="copyToClipboard-icon" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24"><title>{$tokenLabel}</title><path transform="translate(0 -2)" d="M17,12h4a1,1,0,0,1,1,1h0a1,1,0,0,1-1,1H17v2l-4-3,4-3Zm2-2H18V5H13.75V4.083a1.75,1.75,0,1,0-3.5,0V5H6V21H18V16h1v5a1,1,0,0,1-1,1H6a1,1,0,0,1-1-1V5A1,1,0,0,1,6,4H9.251a2.75,2.75,0,0,1,5.5,0H18a1,1,0,0,1,1,1ZM6,7V6H18V7ZM8,9.509A.461.461,0,0,1,8.389,9h5.692a.461.461,0,0,1,.389.509.461.461,0,0,1-.389.509H8.389A.461.461,0,0,1,8,9.509Zm3.261,2.243c.116,0,.209.228.209.509s-.093.51-.209.51H8.209c-.116,0-.209-.228-.209-.51s.093-.509.209-.509ZM12.2,14.5c.149,0,.269.227.269.509s-.12.509-.269.509H8.269c-.149,0-.269-.228-.269-.509s.12-.509.269-.509Zm2.82,3a.513.513,0,0,1,0,1.018H8.449a.513.513,0,0,1,0-1.018Z" style="fill: currentColor;"></path></svg></a><div class="Meta Options">
    <a href="$deleteUrl" class="OptionsLink Popup">{$deleteStr}</a>
</div>
</li>
EOT;
    }

    /**
     * Revoke an invitation.
     *
     * @param int $invitationID Unique identifier.
     * @throws Exception Throws an exception when the invitation isn't found or the user doesn't have permission to delete it.
     * @since 2.0.0
     */
    public function uninvite($invitationID)
    {
        $this->permission("Garden.SignIn.Allow");

        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException("GET");
        }

        $invitationModel = new InvitationModel();
        try {
            $valid = $invitationModel->deleteID($invitationID);
            if ($valid) {
                $this->informMessage(t("The invitation was removed successfully."));
                $this->jsonTarget(".js-invitation[data-id=\"{$invitationID}\"]", "", "SlideUp");
            }
        } catch (Exception $ex) {
            $this->Form->addError(strip_tags($ex->getMessage()));
        }

        if ($this->Form->errorCount() == 0) {
            $this->render("Blank", "Utility");
        }
    }

    // BEGIN PUBLIC CONVENIENCE FUNCTIONS

    /**
     * Adds a tab (or array of tabs) to the profile tab collection ($this->ProfileTabs).
     *
     * @param mixed $tabName Tab name (or array of tab names) to add to the profile tab collection.
     * @param string $tabUrl URL the tab should point to.
     * @param string $cssClass Class property to apply to tab.
     * @param string $tabHtml Overrides tab's HTML.
     * @since 2.0.0
     * @access public
     */
    public function addProfileTab($tabName, $tabUrl = "", $cssClass = "", $tabHtml = "")
    {
        if (!is_array($tabName)) {
            if ($tabHtml == "") {
                $tabHtml = $tabName;
            }

            $tabName = [$tabName => ["TabUrl" => $tabUrl, "CssClass" => $cssClass, "TabHtml" => $tabHtml]];
        }

        foreach ($tabName as $name => $tabInfo) {
            $url = val("TabUrl", $tabInfo, "");
            if ($url == "") {
                $tabInfo["TabUrl"] = userUrl($this->User, "", strtolower($name));
            }

            $this->ProfileTabs[$name] = $tabInfo;
            $this->_ProfileTabs[$name] = $tabInfo; // Backwards Compatibility
        }
    }

    /**
     * Adds the option menu to the panel asset.
     *
     * @param string $currentUrl Path to highlight.
     * @since 2.0.0
     * @access public
     */
    public function addSideMenu($currentUrl = "")
    {
        if (!$this->User) {
            return;
        }

        if (!Gdn::themeFeatures()->useProfileHeader() || $this->isEditMode()) {
            // Make sure to add the "Edit Profile" buttons if it's not provided through the new profile header.
            $this->addModule("ProfileOptionsModule");
        }

        // Show edit menu if in edit mode
        // Show profile pic & filter menu otherwise
        $sideMenu = new SideMenuModule($this);
        // Doing this out here for backwards compatibility.
        if ($this->EditMode) {
            $this->addModule("UserBoxModule");
            $this->buildEditMenu($sideMenu, $currentUrl);
            $this->addModule($sideMenu, "Panel");
        } else {
            // Make sure the userphoto module gets added to the page
            $this->addModule("UserPhotoModule");
            $this->EventArguments["SideMenu"] = &$sideMenu;
            // And add the filter menu module
            $this->fireEvent("AfterAddSideMenu");
            $this->addModule("ProfileFilterModule");
        }
    }

    /**
     * @param SideMenuModule $module
     * @param string $currentUrl
     */
    public function buildEditMenu(&$module, $currentUrl = "")
    {
        if (!$this->User) {
            return;
        }

        $module->HtmlId = "UserOptions";
        $module->AutoLinkGroups = false;
        $session = Gdn::session();
        $viewingUserID = $session->UserID;
        $module->addItem("Options", "", false, ["class" => "SideMenu"]);

        // Check that we have the necessary tools to allow image uploading
        $allowImages = $this->CanEditPhotos && Gdn_UploadImage::canUploadImages();

        // Is the photo hosted remotely?
        $remotePhoto = isUrl($this->User->Photo);

        $profileFieldModel = Gdn::getContainer()->get(ProfileFieldModel::class);

        if ($this->User->UserID != $viewingUserID) {
            // Include user js files for people with edit users permissions
            if (checkPermission("Garden.Users.Edit") || checkPermission("Moderation.Profiles.Edit")) {
                //              $this->addJsFile('jquery.gardenmorepager.js');
                $this->addJsFile("user.js");
            }
            $module->addLink(
                "Options",
                sprite("SpProfile") . " " . t("Edit Profile"),
                userUrl($this->User, "", "edit"),
                ["Garden.Users.Edit", "Moderation.Profiles.Edit"],
                ["class" => "Popup EditAccountLink"]
            );
            $hasFields = $profileFieldModel->hasVisibleFields($this->User->UserID);
            if ($hasFields) {
                $module->addLink(
                    "Options",
                    sprite("SpProfile") . " " . t("Edit Profile Fields"),
                    userUrl($this->User, "", "edit-fields"),
                    ["Garden.Users.Edit", "Moderation.Profiles.Edit"],
                    ["class" => "Popup EditAccountLink"]
                );
            }
            $module->addLink(
                "Options",
                sprite("SpProfile") . " " . t("Edit Account"),
                "/user/edit/" . $this->User->UserID,
                "Garden.Users.Edit",
                ["class" => "Popup EditAccountLink"]
            );
            $module->addLink(
                "Options",
                sprite("SpDelete") . " " . t("Delete Account"),
                "/user/delete/" . $this->User->UserID,
                "Garden.Users.Delete",
                ["class" => "Popup DeleteAccountLink"]
            );
            $module->addLink(
                "Options",
                sprite("SpPreferences") . " " . t("Edit Preferences"),
                userUrl($this->User, "", "preferences"),
                ["Garden.Users.Edit", "Moderation.Profiles.Edit"],
                ["class" => "Popup PreferencesLink"]
            );

            // Add profile options for everyone
            $module->addLink(
                "Options",
                sprite("SpPicture") . " " . t("Change Picture"),
                userUrl($this->User, "", "picture"),
                ["Garden.Users.Edit", "Moderation.Profiles.Edit"],
                ["class" => "PictureLink"]
            );
            if ($this->User->Photo != "" && $allowImages && !$remotePhoto) {
                $module->addLink(
                    "Options",
                    sprite("SpThumbnail") . " " . t("Edit Thumbnail"),
                    userUrl($this->User, "", "thumbnail"),
                    ["Garden.Users.Edit", "Moderation.Profiles.Edit"],
                    ["class" => "ThumbnailLink"]
                );
            }
        } else {
            if (hasEditProfile($this->User->UserID)) {
                $editLinkUrl = "/profile/edit";

                // Kludge for if we're on /profile/edit/username for the current user.
                $requestUrl = Gdn_Url::request();
                $editUrl = "profile/edit/{$this->User->Name}";
                if ($requestUrl === $editUrl) {
                    $editLinkUrl = $editUrl;
                }

                if (Gdn::config(ProfileFieldModel::CONFIG_FEATURE_FLAG)) {
                    $settingsLink = "/profile/account-privacy";
                    $settingsUrl = "profile/account-privacy/{$this->User->Name}";
                    if ($requestUrl === $settingsUrl) {
                        $settingsLink = $settingsUrl;
                    }

                    $module->addLink(
                        "Options",
                        sprite("SpEdit") . " " . t("Account & Privacy Settings"),
                        $settingsLink,
                        false,
                        ["class" => "Popup EditAccountLink"]
                    );
                } else {
                    $module->addLink("Options", sprite("SpEdit") . " " . t("Edit Profile"), $editLinkUrl, false, [
                        "class" => "Popup EditAccountLink",
                    ]);
                }

                $hasFields = $profileFieldModel->hasVisibleFields($this->User->UserID);
                if ($hasFields) {
                    $editFieldsLinkUrl = "profile/edit-fields";
                    $module->addLink(
                        "Options",
                        sprite("SpEdit") . " " . t("Edit Profile Fields"),
                        $editFieldsLinkUrl,
                        false,
                        [
                            "class" => "Popup EditAccountLink",
                        ]
                    );
                }
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
            if (
                c("Garden.UserAccount.AllowEdit") &&
                c("Garden.Registration.Method") != "Connect" &&
                !Gdn::config(ProfileFieldModel::CONFIG_FEATURE_FLAG)
            ) {
                // No password may have been set if they have only signed in with a connect plugin
                $passwordLabel = t("Change My Password");
                if ($this->User->HashMethod && $this->User->HashMethod != "Vanilla") {
                    $passwordLabel = t("Set A Password");
                }
                $module->addLink("Options", sprite("SpPassword") . " " . $passwordLabel, "/profile/password", false, [
                    "class" => "Popup PasswordLink",
                ]);
            }

            if ($allowImages) {
                $module->addLink(
                    "Options",
                    sprite("SpPicture") . " " . t("Change My Picture"),
                    "/profile/picture",
                    ["Garden.Profiles.Edit", "Garden.ProfilePicture.Edit"],
                    ["class" => "PictureLink"]
                );
            }
            /**
             * Re-ordering the site nav to include addon navs in between
             * ref: VNLA-4000
             */
            $this->EventArguments["SideMenu"] = $module;
            $this->fireEvent("AfterAddSideMenu");

            $module->addLink(
                "Options",
                sprite("SpPreferences") . " " . t("Notification Preferences"),
                userUrl($this->User, "", "preferences"),
                false,
                ["class" => "Popup PreferencesLink margin-top"]
            );

            $module->addLink(
                "Options",
                sprite("SpFollowedContent") . " " . t("Followed Content"),
                userUrl($this->User, "", "followed-content"),
                false,
                ["class" => "Popup FollowedContentLink margin-bottom"]
            );
        }

        if ($this->User->UserID == $viewingUserID || $session->checkPermission("Garden.Users.Edit")) {
            $this->setData("Connections", []);
            $this->EventArguments["User"] = $this->User;
            $this->fireEvent("GetConnections");
            if (count($this->data("Connections")) > 0) {
                $module->addLink(
                    "Options",
                    sprite("SpConnection") . " " . t("Connections"),
                    "/profile/connections",
                    "Garden.SignIn.Allow",
                    ["class" => "link-social link-connections"]
                );
            }
        }

        $module->addLink("Options", t("Access Tokens"), "/profile/tokens", "Garden.Tokens.Add", [
            "class" => "link-tokens",
        ]);
    }

    /**
     * Build the user profile.
     *
     * Set the page title, add data to page modules, add modules to assets,
     * add tabs to tab menu. $this->User must be defined, or this method will throw an exception.
     *
     * @return bool Always true.
     * @since 2.0.0
     * @access public
     */
    public function buildProfile()
    {
        if (!is_object($this->User)) {
            throw new Exception(t("Cannot build profile information if user is not defined."));
        }

        $session = Gdn::session();
        if (strpos($this->CssClass, "Profile") === false) {
            $this->CssClass .= " Profile";
        }
        $this->title(Gdn_Format::text($this->User->Name));

        if ($this->_DeliveryType != DELIVERY_TYPE_VIEW) {
            // Javascript needed
            // see note above about jcrop
            $this->addJsFile("jquery.jcrop.min.js");
            $this->addJsFile("profile.js");
            $this->addJsFile("jquery.gardenmorepager.js");
            $this->addJsFile("activity.js");

            // Build activity URL
            $activityUrl = "profile/activity/";
            if ($this->User->UserID != $session->UserID) {
                $activityUrl = userUrl($this->User, "", "activity");
            }

            // Show activity?
            if (c("Garden.Profile.ShowActivities", true)) {
                $this->addProfileTab(
                    t("Activity"),
                    $activityUrl,
                    "Activity",
                    sprite("SpActivity") . " " . t("Activity")
                );
            }

            $ownProfile = $this->User->UserID == $session->UserID;
            // Show notifications?
            if ($ownProfile) {
                $notifications = t("Notifications");
                $notificationsHtml = sprite("SpNotifications") . " " . $notifications;
                $countNotifications = $session->User->CountNotifications;
                if (is_numeric($countNotifications) && $countNotifications > 0) {
                    $notificationsHtml .=
                        ' <span class="Aside"><span class="Count">' . $countNotifications . "</span></span>";
                }

                $this->addProfileTab($notifications, "profile/notifications", "Notifications", $notificationsHtml);
            }

            $displayInvitations = $ownProfile || $session->checkPermission("Garden.Moderation.Manage");
            // Show invitations?
            if (c("Garden.Registration.Method") == "Invitation" && $displayInvitations) {
                $this->addProfileTab(
                    t("Invitations"),
                    "profile/invitations/p1/" . $this->User->Name,
                    "InvitationsLink",
                    sprite("SpInvitations") . " " . t("Invitations")
                );
            }

            $this->fireEvent("AddProfileTabs");
        }
        return true;
    }

    /**
     * Render basic data about user.
     *
     * @param int|false $userID Unique ID.
     */
    public function get($userID = false)
    {
        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }

        if (($userID != Gdn::session()->UserID || !Gdn::session()->UserID) && !checkPermission("Garden.Users.Edit")) {
            throw new Exception(t("You do not have permission to view other profiles."), 401);
        }

        $userModel = new UserModel();

        // Get the user.
        $user = $userModel->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw notFoundException("User");
        }

        $photoUrl = $user["Photo"];
        if ($photoUrl && strpos($photoUrl, "//") == false) {
            $photoUrl = url("/uploads/" . changeBasename($photoUrl, "n%s"), true);
        }
        $user["Photo"] = $photoUrl;

        // Remove unwanted fields.
        $this->Data = arrayTranslate($user, ["UserID", "Name", "Email", "Photo"]);
        $this->render();
    }

    /**
     * Retrieve the user to be manipulated. Defaults to current user.
     *
     * @param int|string $userReference Unique identifier, possibly username or ID.
     * @param string $username The username slug.
     * @param int|string $userID Unique ID.
     * @param bool $checkPermissions Whether or not to check user permissions.
     * @return bool Always true.
     */
    public function getUserInfo($userReference = "", $username = "", $userID = "", $checkPermissions = false)
    {
        if ($this->_UserInfoRetrieved) {
            return;
        }

        if (!c("Garden.Profile.Public") && !Gdn::session()->isValid()) {
            throw permissionException("@" . t(self::PRIVATE_PROFILE));
        }

        // If a UserID was provided as a querystring parameter, use it over anything else:
        if ($userID) {
            $userReference = $userID;
            $username = "Unknown"; // Fill this with a value so the $UserReference is assumed to be an integer/userid.
        }

        // If we are only given a numerical `$userReference` with no `$username` or `$userID`, we assume `$userReference` is the `$userID`
        if (is_numeric($userReference) && !$username && !$userID) {
            $username = "Unknown"; // Fill this with a value so the $UserReference is assumed to be an integer/userid.
        }

        $this->Roles = [];
        if ($userReference == "") {
            if ($username) {
                $this->User = $this->UserModel->getByUsername($username);
            } else {
                $this->User = $this->UserModel->getID(Gdn::session()->UserID);
            }
        } elseif (is_numeric($userReference) && $username != "") {
            $this->User = $this->UserModel->getID($userReference);
        } else {
            $this->User = $this->UserModel->getByUsername($userReference);
        }

        $this->fireEvent("UserLoaded");

        if ($this->User === false) {
            throw notFoundException("User");
        } elseif ($this->User->Deleted == 1) {
            redirectTo("dashboard/home/deleted");
        } else {
            $this->RoleData = $this->UserModel->getRoles($this->User->UserID);
            if ($this->RoleData !== false && $this->RoleData->numRows(DATASET_TYPE_ARRAY) > 0) {
                $this->Roles = array_column($this->RoleData->resultArray(), "Name");
            }
            if (!empty(val("About", $this->User))) {
                $this->User->About = Gdn::formatService()->renderPlainText($this->User->About, TextFormat::FORMAT_KEY);
            }

            // Hide personal info roles
            if (!checkPermission("Garden.PersonalInfo.View")) {
                $this->Roles = array_filter($this->Roles, "RoleModel::FilterPersonalInfo");
            }

            $this->setData("Profile", $this->User);
            $this->setData("UserRoles", $this->Roles);
            if ($cssClass = val("_CssClass", $this->User)) {
                $this->CssClass .= " " . $cssClass;
            }
        }

        if ($checkPermissions && Gdn::session()->UserID != $this->User->UserID) {
            $this->permission(["Garden.Users.Edit", "Moderation.Profiles.Edit"], false);
        }
        $hasPersonalInfo = checkPermission("Garden.PersonalInfo.View");
        // User Banned, PrivateProfiles enabled or User opted to set their profile as private.
        if (!$hasPersonalInfo) {
            $isOwnProfile = Gdn::session()->UserID === $this->User->UserID;
            $private = $this->UserModel->getAttribute($this->User->UserID, "Private", "0");
            $hasPrivateProfile = (bool) $private;
            if (
                (($this->User->Banned && gdn::config("Vanilla.BannedUsers.PrivateProfiles")) || $hasPrivateProfile) &&
                !$isOwnProfile
            ) {
                throw permissionException("@" . t(self::PRIVATE_PROFILE));
            }
        }
        $this->addSideMenu();
        $this->_UserInfoRetrieved = true;
        return true;
    }

    /**
     * Build URL to user's profile.
     *
     * @param mixed $userReference Unique identifier, possibly username or ID.
     * @param string $userID Unique ID.
     * @return string Relative URL path.
     * @since 2.0.0
     * @access public
     */
    public function profileUrl($userReference = null, $userID = null)
    {
        if (!property_exists($this, "User")) {
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
    public function getProfileUrl($userReference = null, $userID = null)
    {
        if (!property_exists($this, "User")) {
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
     * @param string $currentTab Name of tab to highlight.
     * @param string $view View name. Defaults to index.
     * @param string $controller Controller name. Defaults to Profile.
     * @param string $application Application name. Defaults to Dashboard.
     * @since 2.0.0
     * @access public
     */
    public function setTabView($currentTab, $view = "", $controller = "Profile", $application = "Dashboard")
    {
        $this->buildProfile();
        if ($view == "") {
            $view = $currentTab;
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_ALL && $this->SyndicationMethod == SYNDICATION_NONE) {
            $this->addDefinition("DefaultAbout", t("Write something about yourself..."));
            $this->View = "index";
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

    public function editMode($switch)
    {
        $this->EditMode = $switch;
        if (!$this->EditMode && strpos($this->CssClass, "EditMode") !== false) {
            $this->CssClass = str_replace("EditMode", "", $this->CssClass);
        }

        if ($switch) {
            Gdn_Theme::section("EditProfile");
        } else {
            Gdn_Theme::section("EditProfile", "remove");
        }
    }

    /**
     * Getter for edit mode.
     *
     * @return bool
     */
    public function isEditMode(): bool
    {
        return $this->EditMode;
    }

    /**
     * Fetch multiple users
     *
     * Note: API only
     * @param int $userID
     */
    public function multi($userID)
    {
        $this->permission("Garden.Settings.Manage");
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->deliveryType(DELIVERY_TYPE_DATA);

        // Get rid of Reactions busybody data
        unset($this->Data["Counts"]);

        $userID = (array) $userID;
        $users = Gdn::userModel()->getIDs($userID);

        $allowedFields = [
            "UserID",
            "Name",
            "Title",
            "Location",
            "About",
            "Email",
            "Gender",
            "CountVisits",
            "CountInvitations",
            "CountNotifications",
            "Admin",
            "Verified",
            "Banned",
            "Deleted",
            "CountDiscussions",
            "CountComments",
            "CountBookmarks",
            "CountBadges",
            "Points",
            "Punished",
            "RankID",
            "PhotoUrl",
            "Online",
            "LastOnlineDate",
        ];
        $allowedFields = array_fill_keys($allowedFields, null);
        foreach ($users as &$user) {
            $user = array_intersect_key($user, $allowedFields);
        }
        $users = array_values($users);
        $this->setData("Users", $users);
        $this->render();
    }

    /**
     * Returns an array of [{values} => {translatable_caption}] for available gender options.
     *
     * @return array
     */
    public static function getGenderOptions(): array
    {
        return [
            "u" => t("Unspecified"),
            "m" => t("Male"),
            "f" => t("Female"),
        ];
    }

    /**
     * Show user's reacted-to content by reaction type.
     *
     * @param string|int $userReference A username or userid.
     * @param string $username
     * @param string $reaction Which reaction is selected.
     * @param int|string $page What page to show. Defaults to 1.
     */
    public function reactions($userReference, $username = "", $reaction = "", $page = "")
    {
        $this->permission("Garden.Profiles.View");

        $reactionType = ReactionModel::reactionTypes($reaction);
        if (!$reactionType) {
            throw notFoundException();
        }

        $this->editMode(false);
        $this->getUserInfo($userReference, $username);
        $userID = val("UserID", $this->User);

        [$offset, $limit] = offsetLimit($page, 5);

        // If this value is less-than-or-equal-to _CurrentRecords, we'll get a "next" pagination link.
        $this->setData("_Limit", $limit + 1);

        // Try to query five additional records to compensate for user permission and deleted record issues.
        $reactionModel = new ReactionModel();
        $data = $reactionModel->getRecordsWhere(
            [
                "TagID" => $reactionType["TagID"] ?? null,
                "RecordType" => ["Discussion-Total", "Comment-Total"],
                "UserID" => $userID,
                "Total >" => 0,
            ],
            "DateInserted",
            "desc",
            $limit + 5,
            $offset
        );
        $this->setData("_CurrentRecords", count($data));

        // If necessary, shave records off the end to get back down to the original size limit.
        while (count($data) > $limit) {
            array_pop($data);
        }
        if (
            checkPermission("Garden.Reactions.View") &&
            Gdn::config("Vanilla.Reactions.ShowUserReactions", ReactionModel::RECORD_REACTIONS_DEFAULT) === "avatars"
        ) {
            $reactionModel->joinUserTags($data);
        }

        $this->setData("Data", $data);
        $this->setData("EditMode", false, true);
        $this->setData("_robots", "noindex, nofollow");

        $canonicalUrl = userUrl($this->User, "", "reactions");
        if (!empty($reaction) || !in_array($page, ["", "p1"])) {
            $canonicalUrl .=
                "?" . http_build_query(["reaction" => strtolower($reaction) ?: null, "page" => $page ?: null]);
        }
        $this->canonicalUrl(url($canonicalUrl, true));

        $this->_setBreadcrumbs(t($reactionType["Name"] ?? "Unknown"), $this->canonicalUrl());
        $this->setTabView("Reactions", "DataList", "reactions", "dashboard");
        $this->addJsFile("jquery-ui.min.js");
        $this->addJsFile("reactions.js", "vanilla");

        $this->render();
    }
}
