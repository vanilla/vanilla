<?php
/**
 * Authentication Manager
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.10
 */

/**
 * Manages the authentication system for vanilla, including all authentication modules.
 */
class Gdn_Auth extends Gdn_Pluggable {

    /** @var array  */
    protected $_AuthenticationSchemes = [];

    /** @var object  */
    protected $_Authenticator = null;

    /** @var array  */
    protected $_Authenticators = [];

    /** @var string  */
    protected $_Protocol = 'http';

    /** @var array  */
    protected $_Identity = null;

    /** @var object  */
    protected $_UserModel = null;

    /** @var PermissionModel  */
    protected $_PermissionModel = null;

    /** @var bool  */
    protected $_AllowHandshake;

    /** @var bool  */
    protected $_Started = false;

    /**
     *
     */
    public function __construct() {
        // Prepare Identity storage container
        $this->identity();
        $this->_AllowHandshake = false;
        parent::__construct();
    }

    public function startAuthenticator() {
        if (!c('Garden.Installed', false)) {
            return;
        }
        // Start the 'session'
        Gdn::session()->start(false, false);

        // Get list of enabled authenticators
        $authenticationSchemes = Gdn::config('Garden.Authenticator.EnabledSchemes', []);

        // Bring all enabled authenticator classes into the defined scope to allow them to be picked up by the plugin manager
        foreach ($authenticationSchemes as $authenticationSchemeAlias) {
            $registered = $this->registerAuthenticator($authenticationSchemeAlias);
        }

        $this->_Started = true;
        $this->wakeUpAuthenticators();

        if (Gdn::session()->isValid() && !Gdn::session()->checkPermission('Garden.SignIn.Allow')) {
            return Gdn::authenticator()->authenticateWith('user')->deauthenticate();
        }
    }

    public function registerAuthenticator($AuthenticationSchemeAlias) {
        $AuthenticatorClassPath = PATH_LIBRARY.'/core/authenticators/class.%sauthenticator.php';
        $Alias = strtolower($AuthenticationSchemeAlias);
        $Path = sprintf($AuthenticatorClassPath, $Alias);
        $AuthenticatorClass = sprintf('Gdn_%sAuthenticator', ucfirst($Alias));
        // Include the class if it exists
        if (!class_exists($AuthenticatorClass, false) && file_exists($Path)) {
            require_once($Path);
        }

        if (class_exists($AuthenticatorClass)) {
            $this->_AuthenticationSchemes[$Alias] = [
                'Name' => c("Garden.Authenticators.{$Alias}.Name", $Alias),
                'Configure' => false
            ];

            // Now wake it up so it can do setup work
            if ($this->_Started) {
                $Authenticator = $this->authenticateWith($Alias, false);
                $Authenticator->wakeUp();
            }
        }
    }

    public function wakeUpAuthenticators() {
        foreach ($this->_AuthenticationSchemes as $alias => $properties) {
            $authenticator = $this->authenticateWith($alias, false);
            $authenticator->wakeup();
        }
    }

    /**
     * Authenticate with a particular type of authenticator (ex. password).
     *
     * @param string $authenticationSchemeAlias
     * @param bool $inheritAuthenticator
     * @return mixed
     * @throws Exception
     */
    public function authenticateWith($authenticationSchemeAlias = 'default', $inheritAuthenticator = true) {
        if ($authenticationSchemeAlias == 'user') {
            if (Gdn::session()->isValid()) {
                $sessionAuthenticator = Gdn::session()->getPreference('Authenticator');
                $authenticationSchemeAlias = ($sessionAuthenticator) ? $sessionAuthenticator : 'default';
            }
        }

        if ($authenticationSchemeAlias == 'default') {
            $authenticationSchemeAlias = Gdn::config('Garden.Authenticator.DefaultScheme', 'password');
        }

        // Lowercase always, for great justice
        $authenticationSchemeAlias = strtolower($authenticationSchemeAlias);

        // Check if we are allowing this kind of authentication right now
        if (!array_key_exists($authenticationSchemeAlias, $this->_AuthenticationSchemes)) {
            throw new Exception("Tried to load authenticator '{$authenticationSchemeAlias}' which was not yet registered.");
        }
        if (array_key_exists($authenticationSchemeAlias, $this->_Authenticators)) {
            if ($inheritAuthenticator) {
                $this->_Authenticator = $this->_Authenticators[$authenticationSchemeAlias];
            }
            return $this->_Authenticators[$authenticationSchemeAlias];
        }

        $authenticatorClassName = 'Gdn_'.ucfirst($authenticationSchemeAlias).'Authenticator';
        if (class_exists($authenticatorClassName)) {
            $authenticator = new $authenticatorClassName();
            $this->_Authenticators[$authenticationSchemeAlias] = $authenticator;
            if ($inheritAuthenticator) {
                $this->_Authenticator = $this->_Authenticators[$authenticationSchemeAlias];
            }

            return $this->_Authenticators[$authenticationSchemeAlias];
        }
    }

    /**
     * Enable a particular authentication scheme.
     *
     * @param string $authenticationSchemeAlias
     * @param bool $setAsDefault
     */
    public function enableAuthenticationScheme($authenticationSchemeAlias, $setAsDefault = false) {
        // Get list of currently enabled schemes.
        $enabledSchemes = Gdn::config('Garden.Authenticator.EnabledSchemes', []);
        $forceWrite = false;

        // If the list is empty (shouldnt ever be empty), add 'password' to it.
        if (!is_array($enabledSchemes)) {
            $forceWrite = true;
            $enabledSchemes = ['password'];
        }

        // First, loop through the list and remove any instances of the supplied authentication scheme
        $haveScheme = false;
        foreach ($enabledSchemes as $schemeIndex => $schemeKey) {
            if ($schemeKey == $authenticationSchemeAlias) {
                if ($haveScheme === true) {
                    unset($enabledSchemes[$schemeIndex]);
                }
                $haveScheme = true;
            }
        }

        // Now add the new scheme to the list (once)
        if (!$haveScheme || $forceWrite) {
            array_push($enabledSchemes, $authenticationSchemeAlias);
            saveToConfig('Garden.Authenticator.EnabledSchemes', $enabledSchemes);
        }

        if ($setAsDefault == true) {
            $this->setDefaultAuthenticator($authenticationSchemeAlias);
        }
    }

    /**
     * Disable an authentication scheme.
     *
     * @param string $authenticationSchemeAlias
     */
    public function disableAuthenticationScheme($authenticationSchemeAlias) {
        $this->unsetDefaultAuthenticator($authenticationSchemeAlias);

        $forceWrite = false;

        // Remove this authenticator from the enabled schemes collection.
        $enabledSchemes = Gdn::config('Garden.Authenticator.EnabledSchemes', []);
        // If the list is empty (shouldnt ever be empty), add 'password' to it.
        if (!is_array($enabledSchemes)) {
            $forceWrite = true;
            $enabledSchemes = ['password'];
        }

        $hadScheme = false;
        // Loop through the list and remove any instances of the supplied authentication scheme
        foreach ($enabledSchemes as $schemeIndex => $schemeKey) {
            if ($schemeKey == $authenticationSchemeAlias) {
                unset($enabledSchemes[$schemeIndex]);
                $hadScheme = true;
            }
        }

        if ($hadScheme || $forceWrite) {
            saveToConfig('Garden.Authenticator.EnabledSchemes', $enabledSchemes);
        }
    }

    /**
     * Unset the default authenticator.
     *
     * @param string $authenticationSchemeAlias
     * @return bool
     */
    public function unsetDefaultAuthenticator($authenticationSchemeAlias) {
        $authenticationSchemeAlias = strtolower($authenticationSchemeAlias);
        if (c('Garden.Authenticator.DefaultScheme') == $authenticationSchemeAlias) {
            removeFromConfig('Garden.Authenticator.DefaultScheme');
            return true;
        }

        return false;
    }

    /**
     * Set the default authenticator.
     *
     * @param string $authenticationSchemeAlias
     * @return bool
     */
    public function setDefaultAuthenticator($authenticationSchemeAlias) {
        $authenticationSchemeAlias = strtolower($authenticationSchemeAlias);
        $enabledSchemes = Gdn::config('Garden.Authenticator.EnabledSchemes', []);
        if (!in_array($authenticationSchemeAlias, $enabledSchemes)) {
            return false;
        }

        saveToConfig('Garden.Authenticator.DefaultScheme', $authenticationSchemeAlias);
        return true;
    }

    /**
     * Get the authenticator of a given type.
     *
     * @param string $default
     * @return object
     * @throws Exception
     */
    public function getAuthenticator($default = 'default') {
        if (!$this->_Authenticator) {
            $this->authenticateWith($default);
        }

        return $this->_Authenticator;
    }

    /**
     * Get a list of all the currently available authenticators installed.
     */
    public function getAvailable() {
        return $this->_AuthenticationSchemes;
    }

    /**
     * Get the information for an authenticator.
     *
     * @param string $authenticationSchemeAlias
     * @return bool
     */
    public function getAuthenticatorInfo($authenticationSchemeAlias) {
        return (array_key_exists($authenticationSchemeAlias, $this->_AuthenticationSchemes)) ? $this->_AuthenticationSchemes[$authenticationSchemeAlias] : false;
    }

    /**
     * Replace the placeholders in an authenticator's message.
     *
     * @param string $placeholderString
     * @param array $extraReplacements
     * @return mixed
     */
    public function replaceAuthPlaceholders($placeholderString, $extraReplacements = []) {
        $replacements = array_merge([
            'Session_TransientKey' => '',
            'Username' => '',
            'UserID' => ''
        ], (Gdn::session()->isValid() ? [
            'Session_TransientKey' => Gdn::session()->transientKey(),
            'Username' => Gdn::session()->User->Name,
            'UserID' => Gdn::session()->User->UserID
        ] : []), $extraReplacements);
        return Gdn_Format::vanillaSprintf($placeholderString, $replacements);
    }

    /**
     * Associate a user with a foreign ID.
     *
     * @param string $providerKey
     * @param string $userKey
     * @param int $userID
     * @return array|bool
     */
    public function associateUser($providerKey, $userKey, $userID = 0) {
        if ($userID == 0) {
            try {
                $success = Gdn::sql()->insert('UserAuthentication', [
                    'UserID' => 0,
                    'ForeignUserKey' => $userKey,
                    'ProviderKey' => $providerKey
                ]);
                $success = true;
            } catch (Exception $e) {
                $success = true;
            }
        } else {
            $success = Gdn::sql()->replace('UserAuthentication', [
                'UserID' => $userID
            ], [
                'ForeignUserKey' => $userKey,
                'ProviderKey' => $providerKey
            ]);
        }

        if (!$success) {
            return false;
        }

        return [
            'UserID' => $userID,
            'ForeignUserKey' => $userKey,
            'ProviderKey' => $providerKey
        ];
    }

    /**
     * Get the association for a user an a given authentication provider.
     *
     * @param string $userKey
     * @param bool $providerKey
     * @param string $keyType
     * @return array|bool|stdClass
     */
    public function getAssociation($userKey, $providerKey = false, $keyType = Gdn_Authenticator::KEY_TYPE_TOKEN) {
        $query = Gdn::sql()->select('ua.UserID, ua.ForeignUserKey, uat.Token')
            ->from('UserAuthentication ua')
            ->join('UserAuthenticationToken uat', 'ua.ForeignUserKey = uat.ForeignUserKey', 'left')
            ->where('ua.ForeignUserKey', $userKey)
            ->where('UserID >', 0);

        if ($providerKey && $keyType == Gdn_Authenticator::KEY_TYPE_TOKEN) {
            $query->where('uat.Token', $providerKey);
        }

        if ($providerKey && $keyType == Gdn_Authenticator::KEY_TYPE_PROVIDER) {
            $query->where('ua.ProviderKey', $providerKey);
        }

        $userAssociation = $query->get()->firstRow(DATASET_TYPE_ARRAY);
        return $userAssociation ? $userAssociation : false;
    }

    /**
     *
     */
    public function allowHandshake() {
        $this->_AllowHandshake = true;
    }

    /**
     * Determine whether or not an authenticator can handshake.
     *
     * @return bool
     */
    public function canHandshake() {
        return $this->_AllowHandshake;
    }

    /**
     * Get whether or not this is the primary authenticator.
     *
     * @param string $authenticationSchemeAlias
     * @return bool
     */
    public function isPrimary($authenticationSchemeAlias) {
        return ($authenticationSchemeAlias == strtolower(Gdn::config('Garden.Authenticator.DefaultScheme', 'password')));
    }

    /**
     * Returns the unique id assigned to the user in the database.
     *
     * This is retrieved from the session cookie if the cookie authenticates) or false if not found or authentication fails.
     *
     * @return int
     */
    public function getIdentity() {
        $result = $this->getRealIdentity();

        if ($result < 0) {
            $result = 0;
        }

        return $result;
    }

    /**
     *
     *
     * @return mixed
     */
    public function getRealIdentity() {
        $result = $this->_Identity->getIdentity();
        return $result;
    }

    /**
     * Get the active {@link PermissionModel}.
     *
     * @return PermissionModel
     */
    public function getPermissionModel() {
        if ($this->_PermissionModel === null) {
            $this->_PermissionModel = Gdn::permissionModel();
        }
        return $this->_PermissionModel;
    }

    /**
     * Get the active {@link UserModel}.
     *
     * @return UserModel
     */
    public function getUserModel() {
        if ($this->_UserModel === null) {
            $this->_UserModel = Gdn::userModel();
        }
        return $this->_UserModel;
    }

    /**
     *
     *
     * @param $value
     * @param bool $persist
     */
    public function setIdentity($value, $persist = false) {
        $this->_Identity->setIdentity($value, $persist);
    }

    /**
     * Get the identity in use for this authenticator.
     *
     * @return Gdn_CookieIdentity
     */
    public function identity() {
        if (is_null($this->_Identity)) {
            $this->_Identity = Gdn::factory('Identity');
            $this->_Identity->init();
        }

        return $this->_Identity;
    }

    public function setPermissionModel($permissionModel) {
        $this->_PermissionModel = $permissionModel;
    }

    public function setUserModel($userModel) {
        $this->_UserModel = $userModel;
    }

    /**
     * Sets/gets the protocol for authentication (http or https).
     *
     * @return string
     */
    public function protocol($value = null) {
        if (!is_null($value) && in_array($value, ['http', 'https'])) {
            $this->_Protocol = $value;
        }

        return $this->_Protocol;
    }

    /**
     *
     *
     * @param $user
     * @return bool
     */
    public function returningUser($user) {
        if ($this->_Identity->hasVolatileMarker($user->UserID)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the url used to register for an account in the application.
     *
     * @return string
     */
    public function registerUrl($redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_REGISTER, $redirect);
    }

    /**
     * Returns the url used to sign in to the application.
     *
     * @return string
     */
    public function signInUrl($redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_SIGNIN, $redirect);
    }

    /**
     * Returns the url used to sign out of the application.
     *
     * @return string
     */
    public function signOutUrl($redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_SIGNOUT, $redirect);
    }

    /**
     * Get the URL used to register a new account with this authenticator.
     *
     * @param string $redirect
     * @return bool|mixed|string
     */
    public function remoteRegisterUrl($redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_REMOTE_REGISTER, $redirect);
    }

    /**
     * Get the URL used to sign in on the remote site.
     *
     * @param string $redirect
     * @return bool|mixed|string
     */
    public function remoteSignInUrl($redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_REMOTE_SIGNIN, $redirect);
    }

    /**
     * Get the URL used to sign out of the remote site.
     *
     * @param string $redirect
     * @return bool|mixed|string
     */
    public function remoteSignOutUrl($redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_REMOTE_SIGNOUT, $redirect);
    }

    /**
     * Get the URL of a given type.
     *
     * @param string $uRLType
     * @param string $redirect
     * @return bool|mixed|string
     */
    public function getURL($uRLType, $redirect) {
        return $this->_getURL($uRLType, $redirect);
    }

    /**
     * Get the URL of a given type.
     *
     * @param string $uRLType
     * @param string $redirect
     * @return bool|mixed|string
     */
    protected function _getURL($uRLType, $redirect) {
        $sessionAuthenticator = Gdn::session()->getPreference('Authenticator');
        $authenticationScheme = ($sessionAuthenticator) ? $sessionAuthenticator : 'default';

        try {
            $authenticator = $this->getAuthenticator($authenticationScheme);
        } catch (Exception $e) {
            $authenticator = $this->getAuthenticator();
        }

        if (!is_null($redirect) && ($redirect == '' || $redirect == '/')) {
            $redirect = Gdn::router()->getDestination('DefaultController');
        }

        if (is_null($redirect)) {
            $redirect = '';
        }

        // Ask the authenticator for this URLType
        $return = $authenticator->getURL($uRLType);

        // If it doesn't know, get the default from our config file
        if (!$return) {
            $return = c('Garden.Authenticator.'.$uRLType, false);
        }
        if (!$return) {
            return false;
        }

        $extraReplacementParameters = [
            'Path' => $redirect,
            'Scheme' => $authenticationScheme
        ];

        // Extended return type, allows provider values to be replaced into final URL
        if (is_array($return)) {
            $extraReplacementParameters = array_merge($extraReplacementParameters, $return['Parameters']);
            $return = $return['URL'];
        }

        $fullRedirect = ($redirect != '') ? url($redirect, true) : '';
        $extraReplacementParameters['Redirect'] = $fullRedirect;
        $extraReplacementParameters['CurrentPage'] = $fullRedirect;

        // Support legacy sprintf syntax
        $return = sprintf($return, $authenticationScheme, urlencode($redirect), $fullRedirect);

        // Support new named parameter '{}' syntax
        $return = $this->replaceAuthPlaceholders($return, $extraReplacementParameters);

        if ($this->protocol() == 'https') {
            $return = str_replace('http:', 'https:', url($return, true));
        }

        return $return;
    }

    /**
     *
     *
     * @param $authResponse
     * @param null $userData
     * @throws Exception
     */
    public function trigger($authResponse, $userData = null) {
        if (!is_null($userData)) {
            $this->EventArguments['UserData'] = $userData;
        } else {
            $this->EventArguments['UserData'] = false;
        }

        switch ($authResponse) {
            case Gdn_Authenticator::AUTH_SUCCESS:
                $this->fireEvent('AuthSuccess');
                break;
            case Gdn_Authenticator::AUTH_PARTIAL:
                $this->fireEvent('AuthPartial');
                break;
            case Gdn_Authenticator::AUTH_DENIED:
                $this->fireEvent('AuthDenied');
                break;
            case Gdn_Authenticator::AUTH_INSUFFICIENT:
                $this->fireEvent('AuthInsufficient');
                break;
            case Gdn_Authenticator::AUTH_PERMISSION:
                $this->fireEvent('AuthPermission');
                break;
            case Gdn_Authenticator::AUTH_ABORTED:
                $this->fireEvent('AuthAborted');
                break;
            case Gdn_Authenticator::AUTH_CREATED:
                $this->fireEvent('AuthCreated');
                break;
        }
    }
}
