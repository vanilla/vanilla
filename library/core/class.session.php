<?php
/**
 * Session manager.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

use Vanilla\Permissions;

/**
 * Handles user information throughout a session. This class is a singleton.
 */
class Gdn_Session {

    /**
     * Parameter name for incoming CSRF tokens.
     */
    const CSRF_NAME = 'TransientKey';

    /** Maximum length of inactivity, in seconds, before a visit is considered new. */
    const VISIT_LENGTH = 1200; // 20 minutes

    /** @var int Unique user identifier. */
    public $UserID;

    /** @var object A User object containing properties relevant to session */
    public $User;

    /** @var object Attributes of the current user. */
    protected $_Attributes;

    /** @var object Preferences of the current user. */
    protected $_Preferences;

    /** @var object The current user's transient key. */
    protected $_TransientKey;

    /** @var Permissions */
    private $permissions;

    /**
     * @var DateTimeZone The current timezone of the user.
     */
    private $timeZone;

    /**
     * Private constructor prevents direct instantiation of object
     */
    public function __construct() {
        $this->UserID = 0;
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
    public function addPermissions($perms) {
        $newPermissions = new Permissions($perms);
        $this->permissions->merge($newPermissions);
    }

    /**
     * Check the given permission, but also return true if the user has a higher permission.
     *
     * @param bool|string $permission The permission to check.  Bool to force true/false.
     * @return boolean True on valid authorization, false on failure to authorize
     */
    public function checkRankedPermission($permission) {
        $permissionsRanked = [
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Garden.Moderation.Manage',
            'Garden.SignIn.Allow'
        ];

        if ($permission === true) {
            return true;
        } elseif ($permission === false) {
            return false;
        } elseif (in_array($permission, $permissionsRanked)) {
            // Ordered rank of some permissions, highest to lowest
            $currentPermissionRank = array_search($permission, $permissionsRanked);

            /**
             * If the current permission is in our ranked list, iterate through the list, starting from the highest
             * ranked permission down to our target permission, and determine if any are applicable to the current
             * user.  This is done so that a user with a permission like Garden.Settings.Manage can still validate
             * permissions against a Garden.Moderation.Manage permission check, without explicitly having it
             * assigned to their role.
             */
            for ($i = 0; $i <= $currentPermissionRank; $i++) {
                if ($this->permissions->has($permissionsRanked[$i])) {
                    return true;
                }
            }
            return false;
        }

        // Check to see if the user has at least the given permission.
        return $this->permissions->has($permission);
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
     * @return boolean Returns **true** if the user has permission or **false** otherwise.
     */
    public function checkPermission($permission, $fullMatch = true, $junctionTable = '', $junctionID = '') {
        if ($junctionID === 'any' || $junctionID === '' || empty($junctionTable) ||
            c("Garden.Permissions.Disabled.{$junctionTable}")) {
            $junctionID = null;
        }

        if (is_array($permission)) {
            if ($fullMatch) {
                return $this->permissions->hasAll($permission, $junctionID);
            } else {
                return $this->permissions->hasAny($permission, $junctionID);
            }
        } else {
            return $this->permissions->has($permission, $junctionID);
        }
    }

    /**
     * End a session
     *
     * @param Gdn_Authenticator $authenticator
     */
    public function end($authenticator = null) {
        if ($authenticator == null) {
            $authenticator = Gdn::authenticator();
        }

        if ($this->UserID) {
            Logger::event('session_end', Logger::INFO, 'Session ended for {username}.');
        }

        $authenticator->authenticateWith()->deauthenticate();
        $this->setCookie('-Vv', null, -3600);
        $this->setCookie('-sid', null, -3600);
        $this->setCookie('-tk', null, -3600);

        Gdn::pluginManager()->callEventHandlers($this, 'Gdn_Session', 'End');

        $this->UserID = 0;
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
    public function getPermissions() {
        return $this->permissions;
    }

    /**
     * Returns all "allowed" permissions for the authenticated user in a one-dimensional array of permission names.
     *
     * @return array
     */
    public function getPermissionsArray() {
        return $this->permissions->getPermissions();
    }

    /**
     *
     *
     * @param $suffix
     * @param null $default
     * @return mixed
     */
    public function getCookie($suffix, $default = null) {
        return getValue(c('Garden.Cookie.Name').$suffix, $_COOKIE, $default);
    }

    /**
     * Return the time zone for the current user.
     *
     * @return DateTimeZone Returns the current timezone.
     */
    public function getTimeZone() {
        if ($this->timeZone === null) {
            $timeZone = $this->getAttribute('TimeZone', c('Garden.GuestTimeZone'));
            $hourOffset = $this->hourOffset();

            if (!$timeZone) {
                if (is_numeric($hourOffset)) {
                    $timeZone = 'Etc/GMT'.sprintf('%+d', -$hourOffset);
                } else {
                    $timeZone = date_default_timezone_get();
                }
            }
            try {
                $this->timeZone = new DateTimeZone($timeZone);
            } catch (\Exception $ex) {
                $this->timeZone = new DateTimeZone('UTC');
            }
        }

        return $this->timeZone;
    }

    /**
     * Return the timezone hour difference between the user and utc.
     * @return int The hour offset.
     */
    public function hourOffset() {
        static $guestHourOffset;

        if ($this->UserID > 0) {
            return $this->User->HourOffset;
        } else {
            if (!isset($guestHourOffset)) {
                $guestTimeZone = c('Garden.GuestTimeZone');
                if ($guestTimeZone) {
                    try {
                        $timeZone = new DateTimeZone($guestTimeZone);
                        $offset = $timeZone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
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
    public function setCookie($suffix, $value, $expires) {
        $name = c('Garden.Cookie.Name').$suffix;
        $path = c('Garden.Cookie.Path');
        $domain = c('Garden.Cookie.Domain');

        // If the domain being set is completely incompatible with the current domain then make the domain work.
        $currentHost = Gdn::request()->host();
        if (!stringEndsWith($currentHost, trim($domain, '.'))) {
            $domain = '';
        }

        // Allow people to specify up to a year of expiry.
        if (abs($expires) < 31556926) {
            $expires = time() + $expires;
        }

        safeCookie($name, $value, $expires, $path, $domain);
        $_COOKIE[$name] = $value;
    }

    /**
     * Determine if this is a new visit for this user.
     *
     * @return bool
     */
    public function isNewVisit() {
        if ($this->User) {
            $cookie = $this->getCookie('-Vv', false);
            $userVisitExpiry = Gdn_Format::toTimeStamp($this->User->DateLastActive) + self::VISIT_LENGTH;

            if ($cookie) {
                $result = false; // User has cookie, not a new visit.
            } elseif ($userVisitExpiry > time())
                $result = false; // User was last active less than 20 minutes ago, not a new visit.
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
    public function newVisit() {
        $newVisit = $this->isNewVisit();

        $now = time();
        $expiry = $now + self::VISIT_LENGTH;
        $this->setCookie('-Vv', $now, $expiry);

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
    public function setPermission($permissionName, $value = null) {
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
    public function getPreference($preferenceName, $defaultValue = false) {
        // WARNING: THIS DOES NOT CHECK THE DEFAULT CONFIG-DEFINED SETTINGS.
        // IF A USER HAS NEVER SAVED THEIR PREFERENCES, THIS WILL RETURN
        // INCORRECT VALUES.
        return val($preferenceName, $this->_Preferences, $defaultValue);
    }

    /**
     * Gets the currently authenticated user's attribute for the specified
     * $attributeName.
     *
     * @param unknown_type $attributeName The name of the attribute to get.
     * @param string $defaultValue The default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($attributeName, $defaultValue = false) {
        if (is_array($this->_Attributes)) {
            return val($attributeName, $this->_Attributes, $defaultValue);
        }
        return $defaultValue;
    }

    /**
     *
     *
     * @return array
     */
    public function getAttributes() {
        return is_array($this->_Attributes) ? $this->_Attributes : [];
    }

    /**
     * This is the singleton method that return the static
     * Configuration::Instance.
     *
     * @return Session
     */
    public static function getInstance() {
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
    public function isValid() {
        return $this->UserID > 0;
    }

    /**
     * Authenticates the user with the provided Authenticator class.
     *
     * @param int $userID The UserID to start the session with.
     * @param bool $setIdentity Whether or not to set the identity (cookie) or make this a one request session.
     * @param bool $persist If setting an identity, should we persist it beyond browser restart?
     */
    public function start($userID = false, $setIdentity = true, $persist = false) {
        if (!c('Garden.Installed', false)) {
            return;
        }

        $this->permissions = new Permissions();

        // Retrieve the authenticated UserID from the Authenticator module.
        $userModel = Gdn::authenticator()->getUserModel();
        $this->UserID = $userID !== false ? $userID : Gdn::authenticator()->getIdentity();
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
                $this->permissions->setAdmin($this->User->Admin);
                if (!empty($this->User->Deleted)) {
                    $this->permissions->addBan(Permissions::BAN_DELETED, ['msg' => t('Your account has been deleted.')]);
                }
                if (!empty($this->User->Banned)) {
                    $this->permissions->addBan(Permissions::BAN_BANNED, ['msg' => t('You are banned.')]);
                }

                if ($this->permissions->has('Garden.SignIn.Allow')) {
                    if ($setIdentity) {
                        Gdn::authenticator()->setIdentity($this->UserID, $persist);
                        Logger::event('session_start', Logger::INFO, 'Session started for {username}.');
                        Gdn::pluginManager()->callEventHandlers($this, 'Gdn_Session', 'Start');
                    }

                    $userModel->EventArguments['User'] =& $this->User;
                    $userModel->fireEvent('AfterGetSession');

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
    public function setAttribute($name, $value = '') {
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
    public function setPreference($name, $value = '', $saveToDatabase = true) {
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
    public function ensureTransientKey() {
        $transientKey = $this->loadTransientKey();

        if ($transientKey === false) {
            $transientKey = $this->transientKey(betterRandomString(16, 'Aa0'));
        }

        return $transientKey;
    }

    /**
     * Break down a transient key cookie string into its individual elements.
     *
     * @param string $tkCookie
     * @return array|bool
     */
    protected function decodeTKCookie($tkCookie) {
        if (!is_string($tkCookie)) {
            return false;
        }

        $elements = explode(':', $tkCookie);

        if (count($elements) !== 4) {
            return false;
        }

        return [
            'TransientKey' => $elements[0],
            'UserID' => $elements[1],
            'Timestamp' => $elements[2],
            'Signature' => $elements[3]
        ];
    }

    /**
     * Load the transient key from the user's cookie into the TK property.
     *
     * @return bool|string
     */
    public function loadTransientKey() {
        $cookieString = getAppCookie('tk');
        $result = false;

        if ($cookieString !== null) {
            $cookie = $this->decodeTKCookie($cookieString);
            if ($cookie !== false) {
                $payload = $this->generateTKPayload(
                    $cookie['TransientKey'],
                    $cookie['UserID'],
                    $cookie['Timestamp']
                );

                $userValid = ($cookie['UserID'] == $this->UserID);
                $signatureValid = $this->generateTKSignature($payload) == $cookie['Signature'];
                $currentTKInvalid = $this->transientKey() != $cookie['TransientKey'];
                if ($userValid && $signatureValid && $currentTKInvalid) {
                    $result = $this->transientKey($cookie['TransientKey'], false);
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
    public function generateTKPayload($tk, $userID = null, $timestamp = null) {
        $userID = $userID ?: $this->UserID;

        $timestamp = $timestamp ?: time();

        return "{$tk}:{$userID}:{$timestamp}";
    }

    /**
     * Generate a signature for a transient key cookie payload value.
     *
     * @param string $payload
     * @return string
     */
    public function generateTKSignature($payload) {
        return hash_hmac(c('Garden.Cookie.HashMethod'), $payload, c('Garden.Cookie.Salt'));
    }

    /**
     * Returns the transient key for the authenticated user.
     *
     * @param string|null $newKey
     * @param bool $updateCookie Update the browser cookie when changing the transient key?
     * @return string
     */
    public function transientKey($newKey = null, $updateCookie = true) {
        if (is_string($newKey)) {
            if ($updateCookie) {
                $payload = $this->generateTKPayload($newKey);
                $signature = $this->generateTKSignature($payload);
                setAppCookie('tk', "{$payload}:{$signature}");
            }

            $this->_TransientKey = $newKey;
        }

        return $this->_TransientKey;
    }

    /**
     * Validates that $foreignKey was generated by the current user.
     *
     * @param string $foreignKey The key to validate.
     * @param bool $validateUser Whether or not to validate that a user is signed in.
     * @return bool
     */
    public function validateTransientKey($foreignKey, $validateUser = true) {
        static $forceValid = false;

        if ($foreignKey === true) {
            $forceValid = true;
        }

        if (!$forceValid && $validateUser && $this->UserID <= 0) {
            $return = false;
        }

        if (!isset($return)) {
            // Checking the postback here is a kludge, but is absolutely necessary until we can test the ValidatePostBack more.
            $return = ($forceValid && Gdn::request()->isPostBack()) ||
                (hash_equals($this->_TransientKey, $foreignKey) && !empty($this->_TransientKey));
        }
        if (!$return && $forceValid !== true) {
            if (Gdn::session()->User) {
                Logger::event(
                    'csrf_failure',
                    Logger::ERROR,
                    'Invalid transient key for {username}.'
                );
            } else {
                Logger::event(
                    'csrf_failure',
                    Logger::ERROR,
                    'Invalid transient key.'
                );
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
    public function getPublicStash($name, $unset = false) {
        return $this->stash('@public_'.$name, '', $unset);
    }

    /**
     * Sets a public stash value.
     *
     * @param string $name The key of the stash value.
     * @param mixed $value The value of the stash to set. Pass null to clear the key.
     * @return Gdn_Session $this Returns $this for chaining.
     */
    public function setPublicStash($name, $value) {
        if ($value === null) {
            $this->stash('@public_'.$name, '', true);
        } else {
            $this->stash('@public_'.$name, $value, false);
        }

        return $this;
    }

    /**
     * Place a name/value pair into the user's session stash.
     *
     * @param string $name            The key of the stash value.
     * @param mixed  $value           The value of the stash to set. Pass null to retrieve the key.
     * @param bool   $unsetOnRetrieve Whether or not to unset the key from stash.
     *
     * @return mixed Returns the value of the stash or null on failure.
     */
    public function stash($name = '', $value = '', $unsetOnRetrieve = true) {
        if ($name == '') {
            return;
        }
        $sessionModel = new SessionModel();

        // Grab the user's session.
        $session = $this->getStashSession($sessionModel, $value);
        if (!$session) {
            return;
        }

        // Stash or unstash the value depending on inputs.
        if ($value != '') {
            $session['Attributes'][$name] = $value;
        } else {
            $value = val($name, $session['Attributes']);
            if ($unsetOnRetrieve) {
                unset($session['Attributes'][$name]);
            }
        }
        // Update the attributes.
        $sessionModel->update(
            [
                'DateUpdated' => Gdn_Format::toDateTime(),
                'Attributes' => $session['Attributes'],
            ],
            ['SessionID' => $session['SessionID']]
        );

        return $value;
    }

    /**
     * Used by $this->stash() to create & manage sessions for users & guests.
     *
     * This is a stop-gap solution until full session management for users &
     * guests can be implemented.
     *
     * @param SessionModel $sessionModel
     * @param string $valueToStash The value of the stash to set.
     *
     * @return bool|array Current session.
     */
    private function getStashSession($sessionModel, $valueToStash) {
        $cookieName = c('Garden.Cookie.Name', 'Vanilla');
        $name = $cookieName.'-sid';

        // Grab the entire session record.
        $sessionID = val($name, $_COOKIE, '');

        // If there is no session, and no value for saving, return.
        if ($sessionID == '' && $valueToStash == '') {
            return false;
        }

        $session = $sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);

        if (!$session) {
            $sessionID = betterRandomString(32);

            $session = [
                'SessionID' => $sessionID,
                'UserID' => Gdn::session()->UserID,
                'DateInserted' => Gdn_Format::toDateTime(),
                'Attributes' => [],
            ];

            // Save the session information to the database.
            $sessionModel->insert($session);
            trace("Inserting session stash $sessionID");

            // Save a session cookie.
            $path = c('Garden.Cookie.Path', '/');
            $domain = c('Garden.Cookie.Domain', '');
            $expire = 0;

            // If the domain being set is completely incompatible with the
            // current domain then make the domain work.
            $currentHost = Gdn::request()->host();
            if (!stringEndsWith($currentHost, trim($domain, '.'))) {
                $domain = '';
            }

            safeCookie($name, $sessionID, $expire, $path, $domain);
            $_COOKIE[$name] = $sessionID;
        }

        return $session;
    }
}
