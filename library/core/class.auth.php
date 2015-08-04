<?php
/**
 * Authentication Manager
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.10
 */

/**
 * Manages the authentication system for vanilla, including all authentication modules.
 */
class Gdn_Auth extends Gdn_Pluggable {

    /** @var array  */
    protected $_AuthenticationSchemes = array();

    /** @var object  */
    protected $_Authenticator = null;

    /** @var array  */
    protected $_Authenticators = array();

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
        $AuthenticationSchemes = Gdn::config('Garden.Authenticator.EnabledSchemes', array());

        // Bring all enabled authenticator classes into the defined scope to allow them to be picked up by the plugin manager
        foreach ($AuthenticationSchemes as $AuthenticationSchemeAlias) {
            $Registered = $this->registerAuthenticator($AuthenticationSchemeAlias);
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
            $this->_AuthenticationSchemes[$Alias] = array(
                'Name' => C("Garden.Authenticators.{$Alias}.Name", $Alias),
                'Configure' => false
            );

            // Now wake it up so it can do setup work
            if ($this->_Started) {
                $Authenticator = $this->authenticateWith($Alias, false);
                $Authenticator->wakeUp();
            }
        }
    }

    public function wakeUpAuthenticators() {
        foreach ($this->_AuthenticationSchemes as $Alias => $Properties) {
            $Authenticator = $this->authenticateWith($Alias, false);
            $Authenticator->wakeup();
        }
    }

    /**
     * Authenticate with a particular type of authenticator (ex. password).
     *
     * @param string $AuthenticationSchemeAlias
     * @param bool $InheritAuthenticator
     * @return mixed
     * @throws Exception
     */
    public function authenticateWith($AuthenticationSchemeAlias = 'default', $InheritAuthenticator = true) {
        if ($AuthenticationSchemeAlias == 'user') {
            if (Gdn::session()->isValid()) {
                $SessionAuthenticator = Gdn::session()->getPreference('Authenticator');
                $AuthenticationSchemeAlias = ($SessionAuthenticator) ? $SessionAuthenticator : 'default';
            }
        }

        if ($AuthenticationSchemeAlias == 'default') {
            $AuthenticationSchemeAlias = Gdn::config('Garden.Authenticator.DefaultScheme', 'password');
        }

        // Lowercase always, for great justice
        $AuthenticationSchemeAlias = strtolower($AuthenticationSchemeAlias);

        // Check if we are allowing this kind of authentication right now
        if (!array_key_exists($AuthenticationSchemeAlias, $this->_AuthenticationSchemes)) {
            throw new Exception("Tried to load authenticator '{$AuthenticationSchemeAlias}' which was not yet registered.");
        }
        if (array_key_exists($AuthenticationSchemeAlias, $this->_Authenticators)) {
            if ($InheritAuthenticator) {
                $this->_Authenticator = $this->_Authenticators[$AuthenticationSchemeAlias];
            }
            return $this->_Authenticators[$AuthenticationSchemeAlias];
        }

        $AuthenticatorClassName = 'Gdn_'.ucfirst($AuthenticationSchemeAlias).'Authenticator';
        if (class_exists($AuthenticatorClassName)) {
            $Authenticator = new $AuthenticatorClassName();
            $this->_Authenticators[$AuthenticationSchemeAlias] = $Authenticator;
            if ($InheritAuthenticator) {
                $this->_Authenticator = $this->_Authenticators[$AuthenticationSchemeAlias];
            }

            return $this->_Authenticators[$AuthenticationSchemeAlias];
        }
    }

    /**
     * Enable a particular authentication scheme.
     *
     * @param string $AuthenticationSchemeAlias
     * @param bool $SetAsDefault
     */
    public function enableAuthenticationScheme($AuthenticationSchemeAlias, $SetAsDefault = false) {
        // Get list of currently enabled schemes.
        $EnabledSchemes = Gdn::config('Garden.Authenticator.EnabledSchemes', array());
        $ForceWrite = false;

        // If the list is empty (shouldnt ever be empty), add 'password' to it.
        if (!is_array($EnabledSchemes)) {
            $ForceWrite = true;
            $EnabledSchemes = array('password');
        }

        // First, loop through the list and remove any instances of the supplied authentication scheme
        $HaveScheme = false;
        foreach ($EnabledSchemes as $SchemeIndex => $SchemeKey) {
            if ($SchemeKey == $AuthenticationSchemeAlias) {
                if ($HaveScheme === true) {
                    unset($EnabledSchemes[$SchemeIndex]);
                }
                $HaveScheme = true;
            }
        }

        // Now add the new scheme to the list (once)
        if (!$HaveScheme || $ForceWrite) {
            array_push($EnabledSchemes, $AuthenticationSchemeAlias);
            saveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledSchemes);
        }

        if ($SetAsDefault == true) {
            $this->setDefaultAuthenticator($AuthenticationSchemeAlias);
        }
    }

    /**
     * Disable an authentication scheme.
     *
     * @param string $AuthenticationSchemeAlias
     */
    public function disableAuthenticationScheme($AuthenticationSchemeAlias) {
        $this->unsetDefaultAuthenticator($AuthenticationSchemeAlias);

        $ForceWrite = false;

        // Remove this authenticator from the enabled schemes collection.
        $EnabledSchemes = Gdn::config('Garden.Authenticator.EnabledSchemes', array());
        // If the list is empty (shouldnt ever be empty), add 'password' to it.
        if (!is_array($EnabledSchemes)) {
            $ForceWrite = true;
            $EnabledSchemes = array('password');
        }

        $HadScheme = false;
        // Loop through the list and remove any instances of the supplied authentication scheme
        foreach ($EnabledSchemes as $SchemeIndex => $SchemeKey) {
            if ($SchemeKey == $AuthenticationSchemeAlias) {
                unset($EnabledSchemes[$SchemeIndex]);
                $HadScheme = true;
            }
        }

        if ($HadScheme || $ForceWrite) {
            saveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledSchemes);
        }
    }

    /**
     * Unset the default authenticator.
     *
     * @param string $AuthenticationSchemeAlias
     * @return bool
     */
    public function unsetDefaultAuthenticator($AuthenticationSchemeAlias) {
        $AuthenticationSchemeAlias = strtolower($AuthenticationSchemeAlias);
        if (C('Garden.Authenticator.DefaultScheme') == $AuthenticationSchemeAlias) {
            removeFromConfig('Garden.Authenticator.DefaultScheme');
            return true;
        }

        return false;
    }

    /**
     * Set the default authenticator.
     *
     * @param string $AuthenticationSchemeAlias
     * @return bool
     */
    public function setDefaultAuthenticator($AuthenticationSchemeAlias) {
        $AuthenticationSchemeAlias = strtolower($AuthenticationSchemeAlias);
        $EnabledSchemes = Gdn::config('Garden.Authenticator.EnabledSchemes', array());
        if (!in_array($AuthenticationSchemeAlias, $EnabledSchemes)) {
            return false;
        }

        saveToConfig('Garden.Authenticator.DefaultScheme', $AuthenticationSchemeAlias);
        return true;
    }

    /**
     * Get the authenticator of a given type.
     *
     * @param string $Default
     * @return object
     * @throws Exception
     */
    public function getAuthenticator($Default = 'default') {
        if (!$this->_Authenticator) {
            $this->authenticateWith($Default);
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
     * @param string $AuthenticationSchemeAlias
     * @return bool
     */
    public function getAuthenticatorInfo($AuthenticationSchemeAlias) {
        return (array_key_exists($AuthenticationSchemeAlias, $this->_AuthenticationSchemes)) ? $this->_AuthenticationSchemes[$AuthenticationSchemeAlias] : false;
    }

    /**
     * Replace the placeholders in an authenticator's message.
     *
     * @param string $PlaceholderString
     * @param array $ExtraReplacements
     * @return mixed
     */
    public function replaceAuthPlaceholders($PlaceholderString, $ExtraReplacements = array()) {
        $Replacements = array_merge(array(
            'Session_TransientKey' => '',
            'Username' => '',
            'UserID' => ''
        ), (Gdn::session()->isValid() ? array(
            'Session_TransientKey' => Gdn::session()->transientKey(),
            'Username' => Gdn::session()->User->Name,
            'UserID' => Gdn::session()->User->UserID
        ) : array()), $ExtraReplacements);
        return Gdn_Format::vanillaSprintf($PlaceholderString, $Replacements);
    }

    /**
     * Associate a user with a foreign ID.
     *
     * @param string $ProviderKey
     * @param string $UserKey
     * @param int $UserID
     * @return array|bool
     */
    public function associateUser($ProviderKey, $UserKey, $UserID = 0) {
        if ($UserID == 0) {
            try {
                $Success = Gdn::sql()->insert('UserAuthentication', array(
                    'UserID' => 0,
                    'ForeignUserKey' => $UserKey,
                    'ProviderKey' => $ProviderKey
                ));
                $Success = true;
            } catch (Exception $e) {
                $Success = true;
            }
        } else {
            $Success = Gdn::sql()->replace('UserAuthentication', array(
                'UserID' => $UserID
            ), array(
                'ForeignUserKey' => $UserKey,
                'ProviderKey' => $ProviderKey
            ));
        }

        if (!$Success) {
            return false;
        }

        return array(
            'UserID' => $UserID,
            'ForeignUserKey' => $UserKey,
            'ProviderKey' => $ProviderKey
        );
    }

    /**
     * Get the association for a user an a given authentication provider.
     *
     * @param string $UserKey
     * @param bool $ProviderKey
     * @param string $KeyType
     * @return array|bool|stdClass
     */
    public function getAssociation($UserKey, $ProviderKey = false, $KeyType = Gdn_Authenticator::KEY_TYPE_TOKEN) {
        $Query = Gdn::sql()->select('ua.UserID, ua.ForeignUserKey, uat.Token')
            ->from('UserAuthentication ua')
            ->join('UserAuthenticationToken uat', 'ua.ForeignUserKey = uat.ForeignUserKey', 'left')
            ->where('ua.ForeignUserKey', $UserKey)
            ->where('UserID >', 0);

        if ($ProviderKey && $KeyType == Gdn_Authenticator::KEY_TYPE_TOKEN) {
            $Query->where('uat.Token', $ProviderKey);
        }

        if ($ProviderKey && $KeyType == Gdn_Authenticator::KEY_TYPE_PROVIDER) {
            $Query->where('ua.ProviderKey', $ProviderKey);
        }

        $UserAssociation = $Query->get()->firstRow(DATASET_TYPE_ARRAY);
        return $UserAssociation ? $UserAssociation : false;
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
     * @param string $AuthenticationSchemeAlias
     * @return bool
     */
    public function isPrimary($AuthenticationSchemeAlias) {
        return ($AuthenticationSchemeAlias == strtolower(Gdn::config('Garden.Authenticator.DefaultScheme', 'password')));
    }

    /**
     * Returns the unique id assigned to the user in the database.
     *
     * This is retrieved from the session cookie if the cookie authenticates) or false if not found or authentication fails.
     *
     * @return int
     */
    public function getIdentity() {
        $Result = $this->getRealIdentity();

        if ($Result < 0) {
            $Result = 0;
        }

        return $Result;
    }

    /**
     *
     *
     * @return mixed
     */
    public function getRealIdentity() {
        $Result = $this->_Identity->getIdentity();
        return $Result;
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
     * @param $Value
     * @param bool $Persist
     */
    public function setIdentity($Value, $Persist = false) {
        $this->_Identity->setIdentity($Value, $Persist);
    }

    /**
     * Get the identity in use for this authenticator.
     *
     * @return Gdn_CookieIdentity
     */
    public function identity() {
        if (is_null($this->_Identity)) {
            $this->_Identity = Gdn::Factory('Identity');
            $this->_Identity->Init();
        }

        return $this->_Identity;
    }

    public function setPermissionModel($PermissionModel) {
        $this->_PermissionModel = $PermissionModel;
    }

    public function setUserModel($UserModel) {
        $this->_UserModel = $UserModel;
    }

    /**
     * Sets/gets the protocol for authentication (http or https).
     *
     * @return string
     */
    public function protocol($Value = null) {
        if (!is_null($Value) && in_array($Value, array('http', 'https'))) {
            $this->_Protocol = $Value;
        }

        return $this->_Protocol;
    }

    /**
     *
     *
     * @param $User
     * @return bool
     */
    public function returningUser($User) {
        if ($this->_Identity->hasVolatileMarker($User->UserID)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the url used to register for an account in the application.
     *
     * @return string
     */
    public function registerUrl($Redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_REGISTER, $Redirect);
    }

    /**
     * Returns the url used to sign in to the application.
     *
     * @return string
     */
    public function signInUrl($Redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_SIGNIN, $Redirect);
    }

    /**
     * Returns the url used to sign out of the application.
     *
     * @return string
     */
    public function signOutUrl($Redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_SIGNOUT, $Redirect);
    }

    /**
     * Get the URL used to register a new account with this authenticator.
     *
     * @param string $Redirect
     * @return bool|mixed|string
     */
    public function remoteRegisterUrl($Redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_REMOTE_REGISTER, $Redirect);
    }

    /**
     * Get the URL used to sign in on the remote site.
     *
     * @param string $Redirect
     * @return bool|mixed|string
     */
    public function remoteSignInUrl($Redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_REMOTE_SIGNIN, $Redirect);
    }

    /**
     * Get the URL used to sign out of the remote site.
     *
     * @param string $Redirect
     * @return bool|mixed|string
     */
    public function remoteSignOutUrl($Redirect = '/') {
        return $this->_getURL(Gdn_Authenticator::URL_REMOTE_SIGNOUT, $Redirect);
    }

    /**
     * Get the URL of a given type.
     *
     * @param string $URLType
     * @param string $Redirect
     * @return bool|mixed|string
     */
    public function getURL($URLType, $Redirect) {
        return $this->_getURL($URLType, $Redirect);
    }

    /**
     * Get the URL of a given type.
     *
     * @param string $URLType
     * @param string $Redirect
     * @return bool|mixed|string
     */
    protected function _getURL($URLType, $Redirect) {
        $SessionAuthenticator = Gdn::session()->getPreference('Authenticator');
        $AuthenticationScheme = ($SessionAuthenticator) ? $SessionAuthenticator : 'default';

        try {
            $Authenticator = $this->getAuthenticator($AuthenticationScheme);
        } catch (Exception $e) {
            $Authenticator = $this->getAuthenticator();
        }

        if (!is_null($Redirect) && ($Redirect == '' || $Redirect == '/')) {
            $Redirect = Gdn::router()->getDestination('DefaultController');
        }

        if (is_null($Redirect)) {
            $Redirect = '';
        }

        // Ask the authenticator for this URLType
        $Return = $Authenticator->getURL($URLType);

        // If it doesn't know, get the default from our config file
        if (!$Return) {
            $Return = c('Garden.Authenticator.'.$URLType, false);
        }
        if (!$Return) {
            return false;
        }

        $ExtraReplacementParameters = array(
            'Path' => $Redirect,
            'Scheme' => $AuthenticationScheme
        );

        // Extended return type, allows provider values to be replaced into final URL
        if (is_array($Return)) {
            $ExtraReplacementParameters = array_merge($ExtraReplacementParameters, $Return['Parameters']);
            $Return = $Return['URL'];
        }

        $FullRedirect = ($Redirect != '') ? Url($Redirect, true) : '';
        $ExtraReplacementParameters['Redirect'] = $FullRedirect;
        $ExtraReplacementParameters['CurrentPage'] = $FullRedirect;

        // Support legacy sprintf syntax
        $Return = sprintf($Return, $AuthenticationScheme, urlencode($Redirect), $FullRedirect);

        // Support new named parameter '{}' syntax
        $Return = $this->replaceAuthPlaceholders($Return, $ExtraReplacementParameters);

        if ($this->protocol() == 'https') {
            $Return = str_replace('http:', 'https:', Url($Return, true));
        }

        return $Return;
    }

    /**
     *
     *
     * @param $AuthResponse
     * @param null $UserData
     * @throws Exception
     */
    public function trigger($AuthResponse, $UserData = null) {
        if (!is_null($UserData)) {
            $this->EventArguments['UserData'] = $UserData;
        } else {
            $this->EventArguments['UserData'] = false;
        }

        switch ($AuthResponse) {
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
