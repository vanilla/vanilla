<?php
/**
 * Session manager.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
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

    /** @var  Vanilla\Permissions */
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
        $this->_Attributes = array();
        $this->_Preferences = array();
        $this->_TransientKey = false;

        $this->permissions = new Vanilla\Permissions();
    }


    /**
     * Add the permissions from a permissions array to this session's permissions.
     *
     * @param array $perms The permissions to add.
     */
    public function addPermissions($perms) {
        $newPermissions = new Vanilla\Permissions($perms);
        $this->permissions->merge($newPermissions);
    }

    /**
     * Check the given permission, but also return true if the user has a higher permission.
     *
     * @param bool|string $permission The permission to check.  Bool to force true/false.
     * @return boolean True on valid authorization, false on failure to authorize
     */
    public function checkRankedPermission($permission) {
        $permissionsRanked = array(
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Garden.Moderation.Manage',
            'Garden.SignIn.Allow'
        );

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
     * @param Gdn_Authenticator $Authenticator
     */
    public function end($Authenticator = null) {
        if ($Authenticator == null) {
            $Authenticator = Gdn::authenticator();
        }

        if ($this->UserID) {
            Logger::event('session_end', Logger::INFO, 'Session ended for {username}.');
        }

        $Authenticator->authenticateWith()->deauthenticate();
        $this->setCookie('-Vv', null, -3600);
        $this->setCookie('-sid', null, -3600);
        $this->setCookie('-tk', null, -3600);

        Gdn::PluginManager()->CallEventHandlers($this, 'Gdn_Session', 'End');

        $this->UserID = 0;
        $this->User = false;
        $this->_Attributes = array();
        $this->_Preferences = array();
        $this->_TransientKey = false;
        $this->timeZone = null;
    }

    /**
     * Returns all "allowed" permissions for the authenticated user in a one-dimensional array of permission names.
     *
     * @return array
     * @deprecated We want to make this an accessor for the permissions property.
     */
    public function getPermissions() {
        deprecated('Gdn_Session->getPermissions()', 'Gdn_Session->getPermissionsArray()');
        return $this->permissions->getPermissions();
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
     * @param $Suffix
     * @param null $Default
     * @return mixed
     */
    public function getCookie($Suffix, $Default = null) {
        return GetValue(c('Garden.Cookie.Name').$Suffix, $_COOKIE, $Default);
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
        static $GuestHourOffset;

        if ($this->UserID > 0) {
            return $this->User->HourOffset;
        } else {
            if (!isset($GuestHourOffset)) {
                $GuestTimeZone = c('Garden.GuestTimeZone');
                if ($GuestTimeZone) {
                    try {
                        $TimeZone = new DateTimeZone($GuestTimeZone);
                        $Offset = $TimeZone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
                        $GuestHourOffset = floor($Offset / 3600);
                    } catch (Exception $Ex) {
                        $GuestHourOffset = 0;
                        LogException($Ex);
                    }
                }
            }

            return $GuestHourOffset;
        }
    }

    /**
     *
     *
     * @param $Suffix
     * @param $Value
     * @param $Expires
     */
    public function setCookie($Suffix, $Value, $Expires) {
        $Name = c('Garden.Cookie.Name').$Suffix;
        $Path = c('Garden.Cookie.Path');
        $Domain = c('Garden.Cookie.Domain');

        // If the domain being set is completely incompatible with the current domain then make the domain work.
        $CurrentHost = Gdn::request()->host();
        if (!StringEndsWith($CurrentHost, trim($Domain, '.'))) {
            $Domain = '';
        }

        // Allow people to specify up to a year of expiry.
        if (abs($Expires) < 31556926) {
            $Expires = time() + $Expires;
        }

        safeCookie($Name, $Value, $Expires, $Path, $Domain);
        $_COOKIE[$Name] = $Value;
    }

    /**
     *
     *
     * @return bool
     */
    public function newVisit() {
        static $NewVisit = null;

        if ($NewVisit !== null) {
            return $NewVisit;
        }

        if (!$this->User) {
            return false;
        }

        $Current = $this->getCookie('-Vv');
        $Now = time();
        $TimeToExpire = 1200; // 20 minutes
        $Expires = $Now + $TimeToExpire;

        // Figure out if this is a new visit.
        if ($Current) {
            $NewVisit = false; // user has cookie, not a new visit.
        } elseif (Gdn_Format::toTimeStamp($this->User->DateLastActive) + $TimeToExpire > $Now)
            $NewVisit = false; // user was last active less than 20 minutes ago, not a new visit.
        else {
            $NewVisit = true;
        }

        $this->setCookie('-Vv', $Now, $Expires);

        return $NewVisit;
    }

    /**
     * Set a permission for the current runtime.
     *
     * @param string|array $PermissionName
     * @param null|bool $Value
     *
     * @return NULL
     */
    public function setPermission($PermissionName, $Value = null) {
        if (is_string($PermissionName)) {
            if ($Value === null || $Value === true) {
                $this->permissions->overwrite($PermissionName, true);
            } elseif ($Value === false) {
                $this->permissions->overwrite($PermissionName, false);
            } elseif (is_array($Value)) {
                $this->permissions->overwrite($PermissionName, $Value);
            }
        } elseif (is_array($PermissionName)) {
            if (array_key_exists(0, $PermissionName)) {
                foreach ($PermissionName as $Name) {
                    $this->permissions->set($Name, true);
                }
            } else {
                foreach ($PermissionName as $Name => $Value) {
                    $this->permissions->set($Name, $Value);
                }
            }
        }
    }

    /**
     * Gets the currently authenticated user's preference for the specified
     * $PreferenceName.
     *
     * @param string $PreferenceName The name of the preference to get.
     * @param mixed $DefaultValue The default value to return if the preference does not exist.
     * @return mixed
     */
    public function getPreference($PreferenceName, $DefaultValue = false) {
        // WARNING: THIS DOES NOT CHECK THE DEFAULT CONFIG-DEFINED SETTINGS.
        // IF A USER HAS NEVER SAVED THEIR PREFERENCES, THIS WILL RETURN
        // INCORRECT VALUES.
        return val($PreferenceName, $this->_Preferences, $DefaultValue);
    }

    /**
     * Gets the currently authenticated user's attribute for the specified
     * $AttributeName.
     *
     * @param unknown_type $AttributeName The name of the attribute to get.
     * @param string $DefaultValue The default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($AttributeName, $DefaultValue = false) {
        if (is_array($this->_Attributes)) {
            return val($AttributeName, $this->_Attributes, $DefaultValue);
        }
        return $DefaultValue;
    }

    /**
     *
     *
     * @return array
     */
    public function getAttributes() {
        return is_array($this->_Attributes) ? $this->_Attributes : array();
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
     * @param int $UserID The UserID to start the session with.
     * @param bool $SetIdentity Whether or not to set the identity (cookie) or make this a one request session.
     * @param bool $Persist If setting an identity, should we persist it beyond browser restart?
     */
    public function start($UserID = false, $SetIdentity = true, $Persist = false) {
        if (!c('Garden.Installed', false)) {
            return;
        }

        // Retrieve the authenticated UserID from the Authenticator module.
        $UserModel = Gdn::authenticator()->getUserModel();
        $this->UserID = $UserID !== false ? $UserID : Gdn::authenticator()->getIdentity();
        $this->User = false;

        $this->ensureTransientKey();

        // Now retrieve user information
        if ($this->UserID > 0) {
            // Instantiate a UserModel to get session info
            $this->User = $UserModel->getSession($this->UserID);

            if ($this->User) {
                if ($SetIdentity) {
                    Gdn::authenticator()->setIdentity($this->UserID, $Persist);
                    Logger::event('session_start', Logger::INFO, 'Session started for {username}.');
                    Gdn::pluginManager()->callEventHandlers($this, 'Gdn_Session', 'Start');
                }

                $UserModel->EventArguments['User'] =& $this->User;
                $UserModel->fireEvent('AfterGetSession');

                $this->permissions->setPermissions($this->User->Permissions);

                // Set permission overrides.
                $this->permissions->setAdmin($this->User->Admin);
                if (!empty($this->User->Deleted)) {
                    $this->permissions->addBan(Permissions::BAN_DELETED, ['msg' => t("Your account has been deleted.")]);
                }
                if (!empty($this->User->Banned)) {
                    $this->permissions->addBan(Permissions::BAN_BANNED, ['msg' => t('You are banned.')]);
                }

                $this->_Preferences = $this->User->Preferences;
                $this->_Attributes = $this->User->Attributes;

                // Save any visit-level information.
                if ($SetIdentity) {
                    $UserModel->updateVisit($this->UserID);
                }

            } else {
                $this->UserID = 0;
                $this->User = false;

                if ($SetIdentity) {
                    Gdn::authenticator()->setIdentity(null);
                }
            }
        }
        // Load guest permissions if necessary
        if ($this->UserID == 0) {
            $guestPermissions = $UserModel->getPermissions(0);
            $this->permissions->setPermissions($guestPermissions->getPermissions());
        }
    }

    /**
     * Sets a value in the $this->_Attributes array. This setting will persist
     * only to the end of the page load. It is not intended for making permanent
     * changes to user attributes.
     *
     * @param string|array $Name
     * @param mixed $Value
     */
    public function setAttribute($Name, $Value = '') {
        if (!is_array($Name)) {
            $Name = array($Name => $Value);
        }

        foreach ($Name as $Key => $Val) {
            if ($Val === null) {
                unset($this->_Attributes[$Key]);
            }
            $this->_Attributes[$Key] = $Val;
        }
    }

    /**
     * Sets a value in the $this->_Preferences array. This setting will persist
     * changes to user prefs.
     *
     * @param string|array $Name
     * @param mixed $Value
     */
    public function setPreference($Name, $Value = '', $SaveToDatabase = true) {
        if (!is_array($Name)) {
            $Name = array($Name => $Value);
        }

        foreach ($Name as $Key => $Val) {
            $this->_Preferences[$Key] = $Val;
        }

        if ($SaveToDatabase && $this->UserID > 0) {
            $UserModel = Gdn::userModel();
            $UserModel->savePreference($this->UserID, $Name);
        }
    }

    /**
     * Make sure the transient key matches whats in the user's cookie or create a new one.
     *
     * @return string
     */
    public function ensureTransientKey() {
        $cookieString = getAppCookie('tk');
        $reset = false;

        if ($cookieString === null) {
            $reset = true;
        } else {
            $cookie = $this->decodeTKCookie($cookieString);
            if ($cookie === false) {
                $reset = true;
            } else {
                $payload = $this->generateTKPayload(
                    $cookie['TransientKey'],
                    $cookie['UserID'],
                    $cookie['Timestamp']
                );

                $userInvalid = ($cookie['UserID'] != $this->UserID);
                $signatureInvalid = $this->generateTKSignature($payload) !== $cookie['Signature'];
                if ($userInvalid || $signatureInvalid) {
                    $reset = true;
                } elseif ($this->transientKey() !== $cookie['TransientKey']) {
                    $this->transientKey($cookie['TransientKey'], false);
                }
            }
        }

        if ($reset) {
            return $this->transientKey(betterRandomString(16, 'Aa0'));
        } else {
            return $this->transientKey();
        }
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
     * Generate the cookie payload value for a transient key.
     *
     * @param string $tk
     * @param int|null $userID
     * @param int|null $timestamp
     * @return string
     */
    protected function generateTKPayload($tk, $userID = null, $timestamp = null) {
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
    protected function generateTKSignature($payload) {
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
     * Validates that $ForeignKey was generated by the current user.
     *
     * @param string $ForeignKey The key to validate.
     * @return bool
     */
    public function validateTransientKey($ForeignKey, $ValidateUser = true) {
        static $ForceValid = false;

        if ($ForeignKey === true) {
            $ForceValid = true;
        }

        if (!$ForceValid && $ValidateUser && $this->UserID <= 0) {
            $Return = false;
        }

        if (!isset($Return)) {
            // Checking the postback here is a kludge, but is absolutely necessary until we can test the ValidatePostBack more.
            $Return = ($ForceValid && Gdn::request()->isPostBack()) || ($ForeignKey === $this->_TransientKey && $this->_TransientKey !== false);
        }
        if (!$Return && $ForceValid !== true) {
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
        return $Return;
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

        // Create a fresh copy of the Sql object to avoid pollution.
        $sql = clone Gdn::sql();
        $sql->reset();

        // Grab the user's session.
        $session = $this->getStashSession($sql, $value);
        if (!$session) {
            return;
        }

        // Stash or unstash the value depending on inputs.
        if ($value != '') {
            $session->Attributes[$name] = $value;
        } else {
            $value = val($name, $session->Attributes);
            if ($unsetOnRetrieve) {
                unset($session->Attributes[$name]);
            }
        }
        // Update the attributes.
        $sql->put(
            'Session',
            [
                'DateUpdated' => Gdn_Format::toDateTime(),
                'Attributes' => dbencode($session->Attributes)
            ],
            ['SessionID' => $session->SessionID]
        );
        return $value;
    }

    /**
     * Used by $this->stash() to create & manage sessions for users & guests.
     *
     * This is a stop-gap solution until full session management for users &
     * guests can be implemented.
     *
     * @param Gdn_SQLDriver $sql          Local clone of the sql driver.
     * @param string        $valueToStash The value of the stash to set.
     *
     * @return bool|Gdn_DataSet Current session.
     */
    private function getStashSession($sql, $valueToStash) {
        $cookieName = c('Garden.Cookie.Name', 'Vanilla');
        $name = $cookieName.'-sid';

        // Grab the entire session record.
        $sessionID = val($name, $_COOKIE, '');

        // If there is no session, and no value for saving, return.
        if ($sessionID == '' && $valueToStash == '') {
            return false;
        }

        $session = $sql
            ->select()
            ->from('Session')
            ->where('SessionID', $sessionID)
            ->get()
            ->firstRow();

        if (!$session) {
            $sessionID = betterRandomString(32);
            $transientKey = substr(md5(mt_rand()), 0, 11).'!';
            // Save the session information to the database.
            $sql->insert(
                'Session',
                [
                    'SessionID' => $sessionID,
                    'UserID' => Gdn::session()->UserID,
                    'TransientKey' => $transientKey,
                    'DateInserted' => Gdn_Format::toDateTime(),
                    'DateUpdated' => Gdn_Format::toDateTime()
                ]
            );
            trace("Inserting session stash $sessionID");

            $session = $sql
                ->select()
                ->from('Session')
                ->where('SessionID', $sessionID)
                ->get()
                ->firstRow();

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
        $session->Attributes = dbdecode($session->Attributes);
        if (!$session->Attributes) {
            $session->Attributes = [];
        }

        return $session;
    }
}
