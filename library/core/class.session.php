<?php
/**
 * Session manager.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

use Garden\StaticCacheConfigTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Events\UserSignInEvent;
use Vanilla\Logger;
use Vanilla\Logging\AuditLogger;
use Vanilla\Permissions;

/**
 * Handles user information throughout a session. This class is a singleton.
 */
class Gdn_Session implements LoggerAwareInterface
{
    use StaticCacheConfigTrait;
    use LoggerAwareTrait;

    /**
     * Parameter name for incoming CSRF tokens.
     */
    const CSRF_NAME = "TransientKey";

    /** Name of Guest AnonymizeData Cookie */
    const COOKIE_ANONYMIZE = "-AnonymizeData";

    /** Maximum length of inactivity, in seconds, before a visit is considered new. */
    const VISIT_LENGTH = 1200; // 20 minutes

    /** Short time interval for short term sessions, such as passwordReset */
    const SHORT_STASH_SESSION_LENGHT = "now + 10 minutes";

    /** @var int Unique user identifier. */
    public $UserID;

    /** @var int Unique session identifier. */
    public $SessionID;

    /** @var array DB Session record. */
    public $Session;

    /** @var object A User object containing properties relevant to session */
    public $User;

    /** @var object Attributes of the current user. */
    protected $_Attributes;

    /** @var object Preferences of the current user. */
    protected $_Preferences;

    /** @var string The current user's transient key. */
    protected $_TransientKey;

    /** @var Permissions */
    private $permissions;

    /**
     * @var DateTimeZone The current timezone of the user.
     */
    private $timeZone;

    private bool $spoofedInUser = false;

    /**
     * Private constructor prevents direct instantiation of object
     *
     */
    public function __construct()
    {
        $this->UserID = 0;
        $this->SessionID = 0;
        $this->Session = [];
        $this->User = false;
        $this->_Attributes = [];
        $this->_Preferences = [];
        $this->_TransientKey = false;
        $this->permissions = new Permissions();
    }

    /**
     * Add the permissions from a permissions array to this session's permissions.
     *
     * @param array $perms The permissions to add.
     */
    public function addPermissions($perms)
    {
        $newPermissions = new Permissions($perms);
        $this->permissions->merge($newPermissions);
    }

    /**
     * Check the given permission, but also return true if the user has a higher permission.
     *
     * @param bool|string $permission The permission to check.  Bool to force true/false.
     * @return boolean True on valid authorization, false on failure to authorize.
     * @deprecated Use `Permissions::hasRanked()` instead.
     */
    public function checkRankedPermission($permission)
    {
        if ($permission === true) {
            return true;
        } elseif ($permission === false) {
            return false;
        } else {
            return $this->permissions->hasRanked($permission);
        }
    }

    /**
     * Checks the currently authenticated user's permissions for the specified permission.
     *
     * Returns a boolean value indicating if the action is permitted.
     *
     * @param string|array $permission The permission (or array of permissions) to check.
     * @param bool $fullMatch If $Permission is an array, $FullMatch indicates if all permissions specified are required.
     * If false, the user only needs one of the specified permissions.
     * @param string $junctionTable The name of the junction table for a junction permission.
     * @param int|string $junctionID The JunctionID associated with $Permission (ie. A discussion category identifier).
     * @param string $mode One of the permission modes.
     *
     * @return boolean Returns **true** if the user has permission or **false** otherwise.
     */
    public function checkPermission(
        $permission,
        bool $fullMatch = true,
        string $junctionTable = "",
        $junctionID = "",
        string $mode = Permissions::CHECK_MODE_GLOBAL_OR_RESOURCE
    ) {
        if ($junctionID === "any" || $junctionID === "" || self::c("Garden.Permissions.Disabled.{$junctionTable}")) {
            $junctionID = null;
            $junctionTable = null;
        }

        if ($junctionTable === "") {
            $junctionTable = null;
            $junctionID = null;
        }

        if (is_array($permission)) {
            if ($fullMatch) {
                return $this->permissions->hasAll($permission, $junctionID, $mode, $junctionTable);
            } else {
                return $this->permissions->hasAny($permission, $junctionID, $mode, $junctionTable);
            }
        } else {
            return $this->permissions->has($permission, $junctionID, $mode, $junctionTable);
        }
    }

    /**
     * End a session
     *
     * @param Gdn_Authenticator $authenticator
     */
    public function end($authenticator = null)
    {
        $eventManager = Gdn::getContainer()->get(\Garden\EventManager::class);
        $eventManager->fire("Gdn_Session_beginEnd", $this);

        if ($authenticator == null) {
            $authenticator = Gdn::authenticator();
        }

        if ($this->UserID) {
            $this->logger->info("Session ended for {username}.", [
                Logger::FIELD_EVENT => "session_end",
                Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
            ]);
        }
        if ($this->SessionID) {
            $this->logger->info("Session ended for $this->SessionID.", [
                Logger::FIELD_EVENT => "session_end",
                Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
            ]);

            $sessionModel = new SessionModel();
            $sessionModel->expireSession($this->SessionID);
        }

        $authenticator->authenticateWith()->deauthenticate();
        $this->setCookie("-Vv", null, -3600);
        $this->setCookie("-sid", null, -3600);
        $this->setCookie("-tk", null, -3600);

        $eventManager->fire("Gdn_Session_end", $this, []);

        $this->UserID = 0;
        $this->SessionID = 0;
        $this->Session = [];
        $this->User = false;
        $this->_Attributes = [];
        $this->_Preferences = [];
        $this->_TransientKey = false;
        $this->timeZone = null;
    }

    /**
     * Returns the current user's permissions.
     *
     * @return Permissions Returns a {@link Permissions} object with the permissions for the current user.
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Returns all "allowed" permissions for the authenticated user in a one-dimensional array of permission names.
     *
     * @return array
     */
    public function getPermissionsArray()
    {
        return $this->permissions->getPermissions();
    }

    /**
     *
     *
     * @param $suffix
     * @param null $default
     * @return mixed
     */
    public function getCookie($suffix, $default = null)
    {
        return getValue(c("Garden.Cookie.Name") . $suffix, $_COOKIE, $default);
    }

    /**
     * Return the time zone for the current user.
     *
     * @return DateTimeZone Returns the current timezone.
     */
    public function getTimeZone()
    {
        if ($this->timeZone === null) {
            $timeZone = $this->getAttribute("TimeZone", c("Garden.GuestTimeZone"));
            $hourOffset = $this->hourOffset();

            if (!$timeZone) {
                if (is_numeric($hourOffset)) {
                    $timeZone = "Etc/GMT" . sprintf("%+d", -$hourOffset);
                } else {
                    $timeZone = date_default_timezone_get();
                }
            }
            try {
                $this->timeZone = new DateTimeZone($timeZone);
            } catch (\Exception $ex) {
                $this->timeZone = new DateTimeZone("UTC");
            }
        }

        return $this->timeZone;
    }

    /**
     * Return the timezone hour difference between the user and utc.
     * @return int The hour offset.
     */
    public function hourOffset()
    {
        static $guestHourOffset;

        if ($this->UserID > 0) {
            return $this->User->HourOffset;
        } else {
            if (!isset($guestHourOffset)) {
                $guestTimeZone = c("Garden.GuestTimeZone");
                if ($guestTimeZone) {
                    try {
                        $timeZone = new DateTimeZone($guestTimeZone);
                        $offset = $timeZone->getOffset(new DateTime("now", new DateTimeZone("UTC")));
                        $guestHourOffset = floor($offset / 3600);
                    } catch (Exception $ex) {
                        $guestHourOffset = 0;
                        logException($ex);
                    }
                }
            }

            return $guestHourOffset;
        }
    }

    /**
     *
     *
     * @param $suffix
     * @param $value
     * @param $expires
     */
    public function setCookie($suffix, $value, $expires)
    {
        $name = c("Garden.Cookie.Name") . $suffix;
        $path = c("Garden.Cookie.Path");
        $domain = c("Garden.Cookie.Domain");

        // If the domain being set is completely incompatible with the current domain then make the domain work.
        $currentHost = Gdn::request()->host();
        if (!stringEndsWith($currentHost, trim($domain, "."))) {
            $domain = "";
        }

        // Allow people to specify up to a year of expiry.
        if (abs($expires) < 31556926) {
            $expires = CurrentTimeStamp::get() + $expires;
        }

        safeCookie($name, $value == null ? "" : $value, $expires, $path, $domain);
        $_COOKIE[$name] = $value;
    }

    /**
     * Determine if this is a new visit for this user.
     *
     * @return bool
     */
    public function isNewVisit()
    {
        if ($this->User) {
            $cookie = $this->getCookie("-Vv", false);
            $userVisitExpiry = Gdn_Format::toTimeStamp($this->User->DateLastActive) + self::VISIT_LENGTH;

            if ($cookie) {
                $result = false; // User has cookie, not a new visit.
            } elseif ($userVisitExpiry > CurrentTimeStamp::get()) {
                $result = false;
            }
            // User was last active less than 20 minutes ago, not a new visit.
            else {
                $result = true; // No cookie and not active in the last 20 minutes? New visit.
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Update the visit cookie.
     *
     * @return bool Is this a new visit?
     */
    public function newVisit()
    {
        $newVisit = $this->isNewVisit();

        $now = CurrentTimeStamp::get();
        $expiry = $now + self::VISIT_LENGTH;
        $this->setCookie("-Vv", $now, $expiry);

        return $newVisit;
    }

    /**
     * Set a permission for the current runtime.
     *
     * @param string|array $permissionName
     * @param null|bool $value
     *
     * @return NULL
     */
    public function setPermission($permissionName, $value = null)
    {
        if (is_string($permissionName)) {
            if ($value === null || $value === true) {
                $this->permissions->overwrite($permissionName, true);
            } elseif ($value === false) {
                $this->permissions->overwrite($permissionName, false);
            } elseif (is_array($value)) {
                $this->permissions->overwrite($permissionName, $value);
            }
        } elseif (is_array($permissionName)) {
            if (array_key_exists(0, $permissionName)) {
                foreach ($permissionName as $name) {
                    $this->permissions->set($name, true);
                }
            } else {
                foreach ($permissionName as $name => $value) {
                    $this->permissions->set($name, $value);
                }
            }
        }
    }

    /**
     * Gets the currently authenticated user's preference for the specified
     * $preferenceName.
     *
     * @param string $preferenceName The name of the preference to get.
     * @param mixed $defaultValue The default value to return if the preference does not exist.
     * @return mixed
     */
    public function getPreference($preferenceName, $defaultValue = false)
    {
        // WARNING: THIS DOES NOT CHECK THE DEFAULT CONFIG-DEFINED SETTINGS.
        // IF A USER HAS NEVER SAVED THEIR PREFERENCES, THIS WILL RETURN
        // INCORRECT VALUES.
        return val($preferenceName, $this->_Preferences, $defaultValue);
    }

    /**
     * Gets the currently authenticated user's attribute for the specified $attributeName.
     *
     * @param string $attributeName The name of the attribute to get.
     * @param string|false $defaultValue The default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($attributeName, $defaultValue = false)
    {
        if (is_array($this->_Attributes)) {
            $value = val($attributeName, $this->_Attributes, $defaultValue);
            // return same default value type
            if (is_array($defaultValue) && $value === false) {
                $value = $defaultValue;
            }
            return $value;
        }
        return $defaultValue;
    }

    /**
     * Get all of the session attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return is_array($this->_Attributes) ? $this->_Attributes : [];
    }

    /**
     * This is the singleton method that return the static
     * Configuration::Instance.
     *
     * @return self
     */
    public static function getInstance()
    {
        if (!isset(self::$_Instance)) {
            $c = __CLASS__;

            self::$_Instance = new $c();
        }
        return self::$_Instance;
    }

    /**
     * Ensure that there is an active session.
     *
     * If there isn't an active session, send the user to the SignIn Url
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->UserID > 0;
    }

    /**
     * Authenticates the user with the provided Authenticator class.
     *
     * @param int|false $userID The UserID to start the session with.
     * @param bool $setIdentity Whether to set the identity (cookie) or make this a one request session or not.
     * @param bool $persist If setting an identity, should we persist it beyond browser restart?
     * @param string|null $sessionID Session ID to use to start the session.
     */
    public function start(
        $userID = false,
        bool $setIdentity = true,
        bool $persist = false,
        string $sessionID = null,
        $attributes = []
    ) {
        if (!c("Garden.Installed", false)) {
            return;
        }

        $this->permissions = Gdn::permissionModel()->createPermissionInstance();

        // Retrieve the authenticated UserID from the Authenticator module.
        $userModel = Gdn::authenticator()->getUserModel();
        $this->UserID = $userID !== false ? (int) $userID : Gdn::authenticator()->getIdentity();
        $this->SessionID = Gdn::authenticator()->getSession();
        $this->Session = Gdn::authenticator()->getSessionArray();

        if (!empty($this->Session["Attributes"]) && !empty($this->Session["Attributes"]["spoofedUserID"])) {
            $this->spoofedInUser = true;
        }

        $this->User = false;
        $this->loadTransientKey();

        // Now retrieve user information.
        if ($this->UserID > 0) {
            // Instantiate a UserModel to get session info
            $this->User = $userModel->getSession($this->UserID);

            $userSignedIn = false;
            if ($this->User) {
                $this->permissions->setPermissions($this->User->Permissions);

                // Set permission overrides.
                $this->permissions->setAdmin($this->User->Admin > 0);
                $this->permissions->setSysAdmin($this->User->Admin > 1);
                $this->permissions->setSuperAdmin($this->User->Admin > 2);
                if (!empty($this->User->Deleted)) {
                    $this->permissions->addBan(Permissions::BAN_DELETED, [
                        "msg" => t("Your account has been deleted."),
                    ]);
                }
                if (!empty($this->User->Banned)) {
                    $this->permissions->addBan(Permissions::BAN_BANNED, ["msg" => t("You are banned.")]);
                }

                if ($this->permissions->has("Garden.SignIn.Allow")) {
                    // Fire a specific event for setting the session so that event handlers can override permissions.
                    Gdn::getContainer()
                        ->get(\Garden\EventManager::class)
                        ->fire("gdn_session_set", $this);
                    if ($setIdentity) {
                        $this->processAnonymousCookie();
                        $sessionModel = new SessionModel();
                        $this->Session = $sessionModel->startNewSession($this->UserID, $sessionID, $attributes);
                        $userModel->giveRolesByEmail((array) $this->User);
                        Gdn::authenticator()->setIdentity($this->UserID, $persist, $this->Session["SessionID"]);
                        $this->SessionID = $this->Session["SessionID"];
                        $signInEvent = new UserSignInEvent($this->UserID);
                        AuditLogger::log($signInEvent);
                        Gdn::pluginManager()->callEventHandlers($this, "Gdn_Session", "Start");
                    }

                    $userModel->EventArguments["User"] = &$this->User;
                    $userModel->fireEvent("AfterGetSession");

                    $this->_Preferences = $this->User->Preferences;
                    $this->_Attributes = $this->User->Attributes;

                    // Save any visit-level information.
                    if ($setIdentity) {
                        $userModel->updateVisit($this->UserID);
                    }

                    /**
                     * This checks ensures TK cookies aren't set for API calls, but are set for normal users where
                     * $SetIdentity may be false on subsequent page loads after logging in.
                     */
                    if ($setIdentity || $userID === false) {
                        $this->ensureTransientKey();
                    }
                    $userSignedIn = true;
                }
            }

            if (!$userSignedIn) {
                $this->UserID = 0;
                $this->User = false;

                if ($setIdentity) {
                    Gdn::authenticator()->setIdentity(null);
                }
            }
        }

        // Load guest permissions if necessary
        if ($this->UserID == 0) {
            $guestPermissions = $userModel->getPermissions(0);
            $this->permissions->setPermissions($guestPermissions->getPermissions());
        }
    }

    /**
     * Sets a value in the $this->_Attributes array. This setting will persist
     * only to the end of the page load. It is not intended for making permanent
     * changes to user attributes.
     *
     * @param string|array $name
     * @param mixed $value
     */
    public function setAttribute($name, $value = "")
    {
        if (!is_array($name)) {
            $name = [$name => $value];
        }

        foreach ($name as $key => $val) {
            if ($val === null) {
                unset($this->_Attributes[$key]);
            }
            $this->_Attributes[$key] = $val;
        }
    }

    /**
     * Sets a value in the $this->_Preferences array. This setting will persist
     * changes to user prefs.
     *
     * @param string|array $name
     * @param mixed $value
     */
    public function setPreference($name, $value = "", $saveToDatabase = true)
    {
        if (!is_array($name)) {
            $name = [$name => $value];
        }

        foreach ($name as $key => $val) {
            $this->_Preferences[$key] = $val;
        }

        if ($saveToDatabase && $this->UserID > 0) {
            $userModel = Gdn::userModel();
            $userModel->savePreference($this->UserID, $name);
        }
    }

    /**
     * Make sure the transient key matches whats in the user's cookie or create a new one.
     *
     * @return string
     */
    public function ensureTransientKey()
    {
        $transientKey = $this->loadTransientKey();

        if ($transientKey === false) {
            $transientKey = $this->transientKey(betterRandomString(16, "Aa0"));
        }

        return $transientKey;
    }

    /**
     * Break down a transient key cookie string into its individual elements.
     *
     * @param string $tkCookie
     * @return array|bool
     */
    protected function decodeTKCookie($tkCookie)
    {
        if (!is_string($tkCookie)) {
            return false;
        }

        $elements = explode(":", $tkCookie);

        if (count($elements) !== 4) {
            return false;
        }

        return [
            "TransientKey" => $elements[0],
            "UserID" => $elements[1],
            "Timestamp" => $elements[2],
            "Signature" => $elements[3],
        ];
    }

    /**
     * Load the transient key from the user's cookie into the TK property.
     *
     * @return bool|string
     */
    public function loadTransientKey()
    {
        $cookieString = getAppCookie("tk");
        $result = false;

        if ($cookieString !== null) {
            $cookie = $this->decodeTKCookie($cookieString);
            if ($cookie !== false) {
                $payload = $this->generateTKPayload($cookie["TransientKey"], $cookie["UserID"], $cookie["Timestamp"]);

                $userValid = $cookie["UserID"] == $this->UserID;
                $signatureValid = $this->generateTKSignature($payload) == $cookie["Signature"];
                $currentTKInvalid = $this->transientKey() != $cookie["TransientKey"];
                if ($userValid && $signatureValid && $currentTKInvalid) {
                    $result = $this->transientKey($cookie["TransientKey"], false);
                } else {
                    $result = $this->transientKey();
                }
            }
        }

        return $result;
    }

    /**
     * Generate the cookie payload value for a transient key.
     *
     * @param string $tk
     * @param int|null $userID
     * @param int|null $timestamp
     * @return string
     */
    public function generateTKPayload($tk, $userID = null, $timestamp = null)
    {
        $userID = $userID ?: $this->UserID;

        $timestamp = $timestamp ?: CurrentTimeStamp::get();

        return "{$tk}:{$userID}:{$timestamp}";
    }

    /**
     * Generate a signature for a transient key cookie payload value.
     *
     * @param string $payload
     * @return string
     */
    public function generateTKSignature($payload)
    {
        return hash_hmac(c("Garden.Cookie.HashMethod"), $payload, c("Garden.Cookie.Salt"));
    }

    /**
     * Returns the transient key for the authenticated user.
     *
     * @param string|null $newKey
     * @param bool $updateCookie Update the browser cookie when changing the transient key?
     * @return string
     */
    public function transientKey($newKey = null, $updateCookie = true)
    {
        if (is_string($newKey)) {
            if ($updateCookie) {
                $payload = $this->generateTKPayload($newKey);
                $signature = $this->generateTKSignature($payload);
                setAppCookie("tk", "{$payload}:{$signature}");
            }

            $this->_TransientKey = $newKey;
        }

        return $this->_TransientKey;
    }

    /**
     * Validates that $foreignKey was generated by the current user.
     *
     * This method should rarely be called by developers. Instead use `Gdn_Request::isAuthenticatedPostback()`. Only
     * use this method if you are doing the following:
     *
     * 1. Forcing a valid transient key because you have validated the request some other way.
     * 2. Validating a specific transient key passed in something other than the `POST`.
     *
     * @param string $foreignKey The key to validate.
     * @param bool $validateUser Whether or not to validate that a user is signed in.
     * @return bool
     */
    public function validateTransientKey($foreignKey, $validateUser = true)
    {
        static $forceValid = false;

        if ($foreignKey === true) {
            $forceValid = true;
            $return = true;
        }

        if (!$forceValid && $validateUser && $this->UserID <= 0) {
            $return = false;
        }

        if (!isset($return)) {
            /*
             * Use hash_equals to do a time safe comparison.
             * We are not doing `!empty()` first because that would skip hash_equals and would then enable a possible timing attack.
             */
            // Make sure we're testing a string.
            $knownString = $this->_TransientKey ?: "";
            $userString = $foreignKey ?: "";

            $isCorrectHash = hash_equals($knownString, $userString) && !empty($this->_TransientKey);

            // Checking the postback here is a kludge, but is absolutely necessary until we can test the ValidatePostBack more.
            $return = ($forceValid && Gdn::request()->isPostBack()) || $isCorrectHash;
        }

        if (!$return && $forceValid !== true) {
            if (Gdn::session()->User) {
                $this->logger->info("Invalid transient key for {username}.", [
                    Logger::FIELD_EVENT => "csrf_failure",
                    "User TK" => $foreignKey,
                    "Site TK" => $this->_TransientKey,
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                ]);
            } else {
                $this->logger->info("Invalid transient key.", [
                    Logger::FIELD_EVENT => "csrf_failure",
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
                ]);
            }
        }
        return $return;
    }

    /**
     * Get a public stash value.
     *
     * @param string $name The key of the stash.
     * @param bool $unset Whether or not to unset the stash.
     * @return mixed Returns the value of the stash.
     */
    public function getPublicStash($name, $unset = false)
    {
        return $this->stash("@public_" . $name, "", $unset);
    }

    /**
     * Sets a public stash value.
     *
     * @param string $name The key of the stash value.
     * @param mixed $value The value of the stash to set. Pass null to clear the key.
     * @return Gdn_Session $this Returns $this for chaining.
     */
    public function setPublicStash($name, $value)
    {
        if ($value === null) {
            $this->stash("@public_" . $name, "", true);
        } else {
            $this->stash("@public_" . $name, $value, false);
        }

        return $this;
    }

    /**
     * Place a name/value pair into the user's session stash.
     *
     * @param string $name            The key of the stash value.
     * @param mixed  $value           The value of the stash to set. Pass null to retrieve the key.
     * @param string $expireInternal  Date, this session expires, if left blank, defaults to 'getPersistExpiry()'.
     * @param bool   $unsetOnRetrieve Whether or not to unset the key from stash.
     *
     * @return mixed Returns the value of the stash or null on failure.
     */
    public function stash(
        string $name = "",
        $value = "",
        bool $unsetOnRetrieve = true,
        string $expireInternal = "",
        string &$sessionID = null
    ) {
        if ($name == "") {
            return;
        }
        $sessionModel = new SessionModel();

        // Grab the user's session.
        $session = $this->getStashSession($sessionModel, $value, $expireInternal);
        if (!$session) {
            return;
        }
        $sessionID = $session["SessionID"];
        // Stash or unstash the value depending on inputs.
        if ($value != "") {
            $session["Attributes"][$name] = $value;
        } else {
            $value = val($name, $session["Attributes"]);
            if ($unsetOnRetrieve) {
                unset($session["Attributes"][$name]);
            }
        }

        // Update the attributes.
        $sessionModel->update(
            [
                "Attributes" => $session["Attributes"],
            ],
            ["SessionID" => $sessionID]
        );
        $this->Session = $sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);
        return $value;
    }

    /**
     * Used by $this->stash() to create & manage sessions for users & guests.
     *
     * This is a stop-gap solution until full session management for users &
     * guests can be implemented.
     *
     * @param SessionModel $sessionModel
     * @param mixed $valueToStash The value of the stash to set.
     * @param string $expireInternal Date, this session expires, if left blank, defaults to 'getPersistExpiry()'.
     *
     * @return bool|array Current session.
     */
    private function getStashSession(SessionModel $sessionModel, $valueToStash, string $expireInternal = "")
    {
        $cookieName = c("Garden.Cookie.Name", "Vanilla");
        $name = $cookieName . "-sid";
        $sessionID = Gdn::session()->SessionID;
        if ($sessionID == "") {
            // Get session ID from cookie
            $sessionID = val($name, $_COOKIE, "");
        }
        // If there is no session, and no value for saving, return.
        if ($sessionID == "" && $valueToStash == "") {
            return false;
        }

        // Grab the entire session record.
        $this->Session = $sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);

        if (!$this->Session) {
            $this->Session = [
                "UserID" => Gdn::session()->UserID,
                "DateInserted" => CurrentTimeStamp::getMySQL(),
                "Attributes" => [],
                "DateExpires" =>
                    $expireInternal !== ""
                        ? $expireInternal
                        : $sessionModel->getPersistExpiry()->format(MYSQL_DATE_FORMAT),
            ];

            // Save the session information to the database.
            $sessionID = $sessionModel->insert($this->Session);
            $this->Session["SessionID"] = $sessionID;
            trace("Inserting session stash $sessionID");

            // Save a session cookie.
            $path = c("Garden.Cookie.Path", "/");
            $domain = c("Garden.Cookie.Domain", "");
            $expire = 0;

            // If the domain being set is completely incompatible with the
            // current domain then make the domain work.
            $currentHost = Gdn::request()->host();
            if (!stringEndsWith($currentHost, trim($domain, "."))) {
                $domain = "";
            }

            safeCookie($name, $sessionID, $expire, $path, $domain);
            $_COOKIE[$name] = $sessionID;
        }

        return $this->Session;
    }

    /**
     * Locad cookie and save to user meta after login.
     *
     * @return void
     */
    public function processAnonymousCookie()
    {
        $anonymousData = $this->getCookie($this::COOKIE_ANONYMIZE, -1);
        if ($anonymousData !== -1) {
            $userMetaModel = \Gdn::getContainer()->get(UserMetaModel::class);
            $current = $userMetaModel->getUserMeta($this->UserID, UserMetaModel::ANONYMIZE_DATA_USER_META, -1)[
                UserMetaModel::ANONYMIZE_DATA_USER_META
            ];
            // If the values are different update
            if ($current != $anonymousData) {
                $userMetaModel->setUserMeta(
                    $this->UserID,
                    UserMetaModel::ANONYMIZE_DATA_USER_META,
                    $anonymousData == "true" ? "1" : "0"
                );
            }
            $this->setCookie($this::COOKIE_ANONYMIZE, null, -3600);
        }
    }

    /**
     * @return bool
     */
    public function isUserVerified(): bool
    {
        if (!$this->User) {
            return false;
        }
        return (bool) $this->User->Verified;
    }

    /**
     * Check if the current user is a spoofed in user
     *
     * @return bool
     */
    public function isSpoofedInUser(): bool
    {
        return $this->spoofedInUser;
    }
}
