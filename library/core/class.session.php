<?php
/**
 * Session manager.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handles user information throughout a session. This class is a singleton.
 */
class Gdn_Session {

    /** @var int Unique user identifier. */
    public $UserID;

    /** @var object A User object containing properties relevant to session */
    public $User;

    /** @var object Attributes of the current user. */
    protected $_Attributes;

    /** @var object Permissions of the current user. */
    protected $_Permissions;

    /** @var object Preferences of the current user. */
    protected $_Preferences;

    /** @var object The current user's transient key. */
    protected $_TransientKey;

    /**
     * Private constructor prevents direct instantiation of object
     */
    public function __construct() {
        $this->UserID = 0;
        $this->User = false;
        $this->_Attributes = array();
        $this->_Permissions = array();
        $this->_Preferences = array();
        $this->_TransientKey = false;
    }


    /**
     * Add the permissions from a permissions array to this session's permissions.
     *
     * @param array $perms The permissions to add.
     */
    public function addPermissions($perms) {
        $this->_Permissions = PermissionModel::addPermissions($this->_Permissions, $perms);
    }

    /**
     * Checks the currently authenticated user's permissions for the specified
     * permission. Returns a boolean value indicating if the action is
     * permitted.
     *
     * @param mixed $Permission The permission (or array of permissions) to check.
     * @param int $JunctionID The JunctionID associated with $Permission (ie. A discussion category identifier).
     * @param bool $FullMatch If $Permission is an array, $FullMatch indicates if all permissions specified are required. If false, the user only needs one of the specified permissions.
     * @param string $JunctionTable The name of the junction table for a junction permission.
     * @param in $JunctionID The ID of the junction permission.
     * * @return boolean
     */
    public function checkPermission($Permission, $FullMatch = true, $JunctionTable = '', $JunctionID = '') {
        if (is_object($this->User)) {
            if ($this->User->Banned || GetValue('Deleted', $this->User)) {
                return false;
            } elseif ($this->User->Admin) {
                return true;
            }
        }

        // Allow wildcard permission checks (e.g. 'any' Category)
        if ($JunctionID == 'any') {
            $JunctionID = '';
        }

        $Permissions = $this->getPermissions();
        if ($JunctionTable && !C('Garden.Permissions.Disabled.'.$JunctionTable)) {
            // Junction permission ($Permissions[PermissionName] = array(JunctionIDs))
            if (is_array($Permission)) {
                $Pass = false;
                foreach ($Permission as $PermissionName) {
                    if ($this->checkPermission($PermissionName, false, $JunctionTable, $JunctionID)) {
                        if (!$FullMatch) {
                            return true;
                        }
                        $Pass = true;
                    } else {
                        if ($FullMatch) {
                            return false;
                        }
                    }
                }
                return $Pass;
            } else {
                if ($JunctionID !== '') {
                    $Result = array_key_exists($Permission, $Permissions)
                        && is_array($Permissions[$Permission])
                        && in_array($JunctionID, $Permissions[$Permission]);
                } else {
                    $Result = array_key_exists($Permission, $Permissions)
                        && is_array($Permissions[$Permission])
                        && count($Permissions[$Permission]);
                }
                return $Result;
            }
        } else {
            // Non-junction permission ($Permissions = array(PermissionNames))
            if (is_array($Permission)) {
                return arrayInArray($Permission, $Permissions, $FullMatch);
            } else {
                return in_array($Permission, $Permissions) || array_key_exists($Permission, $Permissions);
            }
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
        $this->_Permissions = array();
        $this->_Preferences = array();
        $this->_TransientKey = false;
    }

    /**
     * Returns all "allowed" permissions for the authenticated user in a
     * one-dimensional array of permission names.
     *
     * @return array
     */
    public function getPermissions() {
        return is_array($this->_Permissions) ? $this->_Permissions : array();
    }

    /**
     *
     *
     * @param $Suffix
     * @param null $Default
     * @return mixed
     */
    public function getCookie($Suffix, $Default = null) {
        return GetValue(C('Garden.Cookie.Name').$Suffix, $_COOKIE, $Default);
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
                $GuestTimeZone = C('Garden.GuestTimeZone');
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
        $Name = C('Garden.Cookie.Name').$Suffix;
        $Path = C('Garden.Cookie.Path');
        $Domain = C('Garden.Cookie.Domain');

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
                $this->_Permissions[] = $PermissionName;
            } elseif ($Value === false) {
                $Index = array_search($PermissionName, $this->_Permissions);
                if ($Index !== false) {
                    unset($this->_Permissions[$Index]);
                }
            } elseif (is_array($Value)) {
                $this->_Permissions[$PermissionName] = $Value;
            }
        } elseif (is_array($PermissionName)) {
            if (array_key_exists(0, $PermissionName)) {
                foreach ($PermissionName as $Name) {
                    $this->setPermission($Name);
                }
            } else {
                foreach ($PermissionName as $Name => $Value) {
                    $this->setPermission($Name, $Value);
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
        return ArrayValue($PreferenceName, $this->_Preferences, $DefaultValue);
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
            return ArrayValue($AttributeName, $this->_Attributes, $DefaultValue);
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
        if (!C('Garden.Installed', false)) {
            return;
        }
        // Retrieve the authenticated UserID from the Authenticator module.
        $UserModel = Gdn::authenticator()->getUserModel();
        $this->UserID = $UserID !== false ? $UserID : Gdn::authenticator()->getIdentity();
        $this->User = false;

        // Now retrieve user information
        if ($this->UserID > 0) {
            // Instantiate a UserModel to get session info
            $this->User = $UserModel->getSession($this->UserID);

            if ($this->User) {
                if ($SetIdentity) {
                    Gdn::authenticator()->setIdentity($this->UserID, $Persist);
                    Logger::event('session_start', Logger::INFO, 'Session started for {username}.');
                }

                $UserModel->EventArguments['User'] =& $this->User;
                $UserModel->fireEvent('AfterGetSession');

                $this->_Permissions = Gdn_Format::unserialize($this->User->Permissions);
                $this->_Preferences = Gdn_Format::unserialize($this->User->Preferences);
                $this->_Attributes = Gdn_Format::unserialize($this->User->Attributes);
                $this->_TransientKey = is_array($this->_Attributes) ? arrayValue('TransientKey', $this->_Attributes) : false;

                if ($this->_TransientKey === false) {
                    $this->_TransientKey = $UserModel->setTransientKey($this->UserID);
                }

                // Save any visit-level information.
                if ($SetIdentity) {
                    $UserModel->updateVisit($this->UserID);
                }

            } else {
                $this->UserID = 0;
                $this->User = false;
                $this->_TransientKey = getAppCookie('tk');

                if ($SetIdentity) {
                    Gdn::authenticator()->setIdentity(null);
                }
            }
        } else {
            // Grab the transient key from the cookie. This doesn't always get set but we'll try it here anyway.
            $this->_TransientKey = getAppCookie('tk');
        }
        // Load guest permissions if necessary
        if ($this->UserID == 0) {
            $this->_Permissions = Gdn_Format::unserialize($UserModel->definePermissions(0));
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
     *
     *
     * @return bool|object|string
     */
    public function ensureTransientKey() {
        if (!$this->_TransientKey) {
            // Generate a transient key in the browser.
            $tk = betterRandomString(16, 'Aa0');
            setAppCookie('tk', $tk);
            $this->_TransientKey = $tk;
        }
        return $this->_TransientKey;
    }

    /**
     * Returns the transient key for the authenticated user.
     *
     * @return string
     */
    public function transientKey($NewKey = null) {
        if (!is_null($NewKey)) {
            $this->_TransientKey = Gdn::authenticator()->getUserModel()->setTransientKey($this->UserID, $NewKey);
        }

//      if ($this->_TransientKey)
        return $this->_TransientKey;
//      else
//         return RandomString(12); // Postbacks will never be authenticated if transientkey is not defined.
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
            $Return = ($ForceValid && Gdn::request()->isPostBack()) || ($ForeignKey == $this->_TransientKey && $this->_TransientKey !== false);
        }
        if (!$Return) {
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
     */
    public function stash($Name = '', $Value = '', $UnsetOnRetrieve = true) {
        if ($Name == '') {
            return;
        }

        // Grab the user's session
        $Session = $this->_getStashSession($Value);
        if (!$Session) {
            return;
        }

        // Stash or unstash the value depending on inputs
        if ($Name != '' && $Value != '') {
            $Session->Attributes[$Name] = $Value;
        } elseif ($Name != '') {
            $Value = val($Name, $Session->Attributes);
            if ($UnsetOnRetrieve) {
                unset($Session->Attributes[$Name]);
            }
        }
        // Update the attributes
        if ($Name != '') {
            Gdn::SQL()->put(
                'Session',
                array(
                    'DateUpdated' => Gdn_Format::toDateTime(),
                    'Attributes' => serialize($Session->Attributes)
                ),
                array(
                    'SessionID' => $Session->SessionID
                )
            );
        }
        return $Value;
    }

    /**
     * Used by $this->Stash() to create & manage sessions for users & guests.
     *
     * This is a stop-gap solution until full session management for users &
     * guests can be imlemented.
     */
    private function _getStashSession($ValueToStash) {
        $CookieName = c('Garden.Cookie.Name', 'Vanilla');
        $Name = $CookieName.'-sid';

        // Grab the entire session record
        $SessionID = val($Name, $_COOKIE, '');

        // If there is no session, and no value for saving, return;
        if ($SessionID == '' && $ValueToStash == '') {
            return false;
        }

        $Session = Gdn::SQL()
            ->select()
            ->from('Session')
            ->where('SessionID', $SessionID)
            ->get()
            ->firstRow();

        if (!$Session) {
            $SessionID = betterRandomString(32);
            $TransientKey = substr(md5(mt_rand()), 0, 11).'!';
            // Save the session information to the database.
            Gdn::SQL()->insert(
                'Session',
                array(
                    'SessionID' => $SessionID,
                    'UserID' => Gdn::session()->UserID,
                    'TransientKey' => $TransientKey,
                    'DateInserted' => Gdn_Format::toDateTime(),
                    'DateUpdated' => Gdn_Format::toDateTime()
                )
            );
            Trace("Inserting session stash $SessionID");

            $Session = Gdn::SQL()
                ->select()
                ->from('Session')
                ->where('SessionID', $SessionID)
                ->get()
                ->firstRow();

            // Save a session cookie
            $Path = C('Garden.Cookie.Path', '/');
            $Domain = C('Garden.Cookie.Domain', '');
            $Expire = 0;

            // If the domain being set is completely incompatible with the current domain then make the domain work.
            $CurrentHost = Gdn::request()->host();
            if (!stringEndsWith($CurrentHost, trim($Domain, '.'))) {
                $Domain = '';
            }

            safeCookie($Name, $SessionID, $Expire, $Path, $Domain);
            $_COOKIE[$Name] = $SessionID;
        }
        $Session->Attributes = @unserialize($Session->Attributes);
        if (!$Session->Attributes) {
            $Session->Attributes = array();
        }

        return $Session;
    }
}
