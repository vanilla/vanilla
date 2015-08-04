<?php
/**
 * Authenticator Module: Base Class
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.10
 */

/**
 * Class Gdn_Authenticator
 */
abstract class Gdn_Authenticator extends Gdn_Pluggable {

    const DATA_NONE = 'data none';
    const DATA_FORM = 'data form';
    const DATA_REQUEST = 'data request';
    const DATA_COOKIE = 'data cookie';

    const MODE_REPEAT = 'already logged in';
    const MODE_GATHER = 'gather';
    const MODE_VALIDATE = 'validate';
    const MODE_NOAUTH = 'no foreign identity';

    const AUTH_DENIED = 0;
    const AUTH_PERMISSION = -1;
    const AUTH_INSUFFICIENT = -2;
    const AUTH_PARTIAL = -3;
    const AUTH_SUCCESS = -4;
    const AUTH_ABORTED = -5;
    const AUTH_CREATED = -6;

    const HANDSHAKE_JS = 'javascript';
    const HANDSHAKE_DIRECT = 'direct';
    const HANDSHAKE_IMAGE = 'image';

    const REACT_RENDER = 1;
    const REACT_EXIT = 2;
    const REACT_REDIRECT = 3;
    const REACT_REMOTE = 4;

    const URL_REGISTER = 'RegisterUrl';
    const URL_SIGNIN = 'SignInUrl';
    const URL_SIGNOUT = 'SignOutUrl';

    const URL_REMOTE_REGISTER = 'RemoteRegisterUrl';
    const URL_REMOTE_SIGNIN = 'RemoteSignInUrl';
    const URL_REMOTE_SIGNOUT = 'RemoteSignOutUrl';

    const KEY_TYPE_TOKEN = 'token';
    const KEY_TYPE_PROVIDER = 'provider';

    /** @var string Alias of the authentication scheme to use, e.g. "password" or "openid". */
    protected $_AuthenticationSchemeAlias = null;

    /** @var string  */
    protected $_DataSourceType = self::DATA_FORM;

    /** @var null  */
    protected $_DataSource = null;

    /** @var array  */
    public $_DataHooks = array();

    /**
     * Returns the unique id assigned to the user in the database.
     *
     * This method should return 0 if the username/password combination were not found, or -1 if the user does not
     * have permission to sign in.
     */
    abstract public function authenticate();

    /**
     *
     */
    abstract public function currentStep();

    /**
     *
     */
    abstract public function deAuthenticate();

    /**
     *
     */
    abstract public function wakeUp();

    /**
     * What to do if entry/auth/* is called while the user is logged out.
     *
     * Should normally be REACT_RENDER.
     */
    abstract public function loginResponse();

    /**
     * What to do after part 1 of a 2 part authentication process.
     *
     * This is used in conjunction with OAauth/OpenID type authentication schemes.
     */
    abstract public function partialResponse();

    /**
     * What to do after authentication has succeeded.
     */
    abstract public function successResponse();

    /**
     * What to do if the entry/auth/* page is triggered for a user that is already logged in.
     */
    abstract public function repeatResponse();

    /**
     * What to do if the entry/leave/* page is triggered for a user that is logged in and successfully logs out.
     */
    public function logoutResponse() {
    }

    /**
     * What to do if the entry/auth/* page is triggered but login is denied or fails.
     */
    public function failedResponse() {
    }

    /**
     * Get one of the three Forwarding URLs (Registration, SignIn, SignOut).
     *
     * @param $URLType
     * @return mixed
     */
    abstract public function getURL($URLType);

    /**
     *
     */
    public function __construct() {
        // Figure out what the authenticator alias is
        $this->_AuthenticationSchemeAlias = $this->getAuthenticationSchemeAlias();

        // Initialize gdn_pluggable
        parent::__construct();
    }

    /**
     *
     *
     * @return string
     */
    public function dataSourceType() {
        return $this->_DataSourceType;
    }

    /**
     *
     *
     * @param $DataSource
     * @param array $DirectSupplied
     */
    public function fetchData($DataSource, $DirectSupplied = array()) {
        $this->_DataSource = $DataSource;

        if ($DataSource == $this) {
            foreach ($this->_DataHooks as $DataTarget => $DataHook) {
                $this->_DataHooks[$DataTarget]['value'] = arrayValue($DataTarget, $DirectSupplied);
            }

            return;
        }

        if (sizeof($this->_DataHooks)) {
            foreach ($this->_DataHooks as $DataTarget => $DataHook) {
                switch ($this->_DataSourceType) {
                    case self::DATA_REQUEST:
                    case self::DATA_FORM:
                        $this->_DataHooks[$DataTarget]['value'] = $this->_DataSource->getValue(
                            $DataHook['lookup'],
                            false
                        );
                        break;

                    case self::DATA_COOKIE:
                        $this->_DataHooks[$DataTarget]['value'] = $this->_DataSource->getValueFrom(
                            Gdn_Authenticator::INPUT_COOKIES,
                            $DataHook['lookup'],
                            false
                        );
                        break;
                }
            }
        }
    }

    /**
     *
     *
     * @param $InternalFieldName
     * @param $DataFieldName
     * @param bool $DataFieldRequired
     */
    public function hookDataField($InternalFieldName, $DataFieldName, $DataFieldRequired = true) {
        $this->_DataHooks[$InternalFieldName] = array('lookup' => $DataFieldName, 'required' => $DataFieldRequired);
    }

    /**
     *
     *
     * @param $Key
     * @param bool $Default
     * @return bool
     */
    public function getValue($Key, $Default = false) {
        if (array_key_exists($Key, $this->_DataHooks) && array_key_exists('value', $this->_DataHooks[$Key])) {
            return $this->_DataHooks[$Key]['value'];
        }

        return $Default;
    }

    /**
     *
     *
     * @return string
     */
    protected function _checkHookedFields() {
        foreach ($this->_DataHooks as $DataKey => $DataValue) {
            if ($DataValue['required'] == true && (!array_key_exists('value', $DataValue) || $DataValue['value'] == null)) {
                return Gdn_Authenticator::MODE_GATHER;
            }
        }

        return Gdn_Authenticator::MODE_VALIDATE;
    }

    /**
     *
     *
     * @param null $ProviderKey
     * @param bool $Force
     * @return array|bool|stdClass
     */
    public function getProvider($ProviderKey = null, $Force = false) {
        static $AuthModel = null;
        static $Provider = null;

        if (is_null($AuthModel)) {
            $AuthModel = new Gdn_AuthenticationProviderModel();
        }

        $AuthenticationSchemeAlias = $this->getAuthenticationSchemeAlias();
        if (is_null($Provider) || $Force === true) {
            if (!is_null($ProviderKey)) {
                $ProviderData = $AuthModel->getProviderByKey($ProviderKey);
            } else {
                $ProviderData = $AuthModel->getProviderByScheme($AuthenticationSchemeAlias, Gdn::session()->UserID);
                if (!$ProviderData && Gdn::session()->UserID > 0) {
                    $ProviderData = $AuthModel->getProviderByScheme($AuthenticationSchemeAlias, null);
                }
            }

            if ($ProviderData) {
                $Provider = $ProviderData;
            } else {
                return false;
            }
        }

        return $Provider;
    }

    /**
     *
     *
     * @return array|bool|stdClass
     * @throws Exception
     */
    public function getToken() {
        $Provider = $this->getProvider();
        if (is_null($this->Token)) {
            $UserID = Gdn::authenticator()->getIdentity();
            $UserAuthenticationData = Gdn::sql()->select('uat.*')
                ->from('UserAuthenticationToken uat')
                ->join('UserAuthentication ua', 'ua.ForeignUserKey = uat.ForeignUserKey')
                ->where('ua.UserID', $UserID)
                ->where('ua.ProviderKey', $Provider['AuthenticationKey'])
                ->limit(1)
                ->get();

            if ($UserAuthenticationData->numRows()) {
                $this->Token = $UserAuthenticationData->firstRow(DATASET_TYPE_ARRAY);
            } else {
                return false;
            }
        }

        return $this->Token;
    }

    /**
     *
     *
     * @return array|bool|stdClass
     */
    public function getNonce() {
        $Token = $this->getToken();
        if (is_null($this->Nonce)) {
            $UserNonceData = Gdn::sql()->select('uan.*')
                ->from('UserAuthenticationNonce uan')
                ->where('uan.Token', $this->Token['Token'])
                ->get();

            if ($UserNonceData->numRows()) {
                $this->Nonce = $UserNonceData->firstRow(DATASET_TYPE_ARRAY);
            } else {
                return false;
            }
        }

        return $this->Nonce;
    }

    /**
     *
     *
     * @param $TokenType
     * @param $ProviderKey
     * @param null $UserKey
     * @param bool $Authorized
     * @return array|bool
     */
    public function createToken($TokenType, $ProviderKey, $UserKey = null, $Authorized = false) {
        $TokenKey = implode('.', array('token', $ProviderKey, time(), mt_rand(0, 100000)));
        $TokenSecret = sha1(md5(implode('.', array($TokenKey, mt_rand(0, 100000)))));
        $Timestamp = time();

        $Lifetime = Gdn::config('Garden.Authenticators.handshake.TokenLifetime', 60);
        if ($Lifetime == 0 && $TokenType == 'request') {
            $Lifetime = 300;
        }

        $InsertArray = array(
            'Token' => $TokenKey,
            'TokenSecret' => $TokenSecret,
            'TokenType' => $TokenType,
            'ProviderKey' => $ProviderKey,
            'Lifetime' => $Lifetime,
            'Authorized' => $Authorized,
            'ForeignUserKey' => null
        );

        if ($UserKey !== null) {
            $InsertArray['ForeignUserKey'] = $UserKey;
        }

        try {
            Gdn::sql()
                ->set('Timestamp', 'NOW()', false)
                ->insert('UserAuthenticationToken', $InsertArray);

            if ($TokenType == 'access' && !is_null($UserKey)) {
                $this->deleteToken($ProviderKey, $UserKey, 'request');
            }
        } catch (Exception $e) {
            return false;
        }

        return $InsertArray;
    }

    /**
     *
     *
     * @param $TokenKey
     * @return bool
     */
    public function authorizeToken($TokenKey) {
        try {
            Gdn::database()->sql()->update('UserAuthenticationToken uat')
                ->set('Authorized', 1)
                ->where('Token', $TokenKey)
                ->put();
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     *
     *
     * @param $ProviderKey
     * @param $UserKey
     * @param null $TokenType
     * @return array|bool|stdClass
     */
    public function lookupToken($ProviderKey, $UserKey, $TokenType = null) {

        $TokenData = Gdn::database()->sql()
            ->select('uat.*')
            ->from('UserAuthenticationToken uat')
            ->where('uat.ForeignUserKey', $UserKey)
            ->where('uat.ProviderKey', $ProviderKey)
            ->beginWhereGroup()
            ->where('(uat.Timestamp + uat.Lifetime) >=', 'NOW()')
            ->orWHere('uat.Lifetime', 0)
            ->endWhereGroup()
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        if ($TokenData && (is_null($TokenType) || strtolower($TokenType) == strtolower($TokenData['TokenType']))) {
            return $TokenData;
        }

        return false;
    }

    /**
     *
     *
     * @param $ProviderKey
     * @param $UserKey
     * @param $TokenType
     */
    public function deleteToken($ProviderKey, $UserKey, $TokenType) {
        Gdn::database()->sql()
            ->from('UserAuthenticationToken')
            ->where('ProviderKey', $ProviderKey)
            ->where('ForeignUserKey', $UserKey)
            ->where('TokenType', $TokenType)
            ->delete();
    }

    /**
     *
     *
     * @param $TokenKey
     * @param $Nonce
     * @param null $Timestamp
     * @return bool
     */
    public function setNonce($TokenKey, $Nonce, $Timestamp = null) {
        $InsertArray = array(
            'Token' => $TokenKey,
            'Nonce' => $Nonce,
            'Timestamp' => date('Y-m-d H:i:s', (is_null($Timestamp)) ? time() : $Timestamp)
        );

        try {
            $NumAffected = Gdn::database()->sql()->update('UserAuthenticationNonce')
                ->set('Nonce', $InsertArray['Nonce'])
                ->set('Timestamp', $InsertArray['Timestamp'])
                ->where('Token', $InsertArray['Token'])
                ->put();

            if (!$NumAffected || !$NumAffected->pdoStatement() || !$NumAffected->pdoStatement()->rowCount()) {
                throw new Exception("Nothing to update.");
            }

        } catch (Exception $e) {
            $Inserted = Gdn::database()->sql()->insert('UserAuthenticationNonce', $InsertArray);
        }
        return true;
    }

    /**
     *
     *
     * @param $TokenKey
     * @param null $Nonce
     * @return bool
     */
    public function lookupNonce($TokenKey, $Nonce = null) {

        $NonceData = Gdn::database()->sql()
            ->select('uan.*')
            ->from('UserAuthenticationNonce uan')
            ->where('uan.Token', $TokenKey)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        if ($NonceData && (is_null($Nonce) || $NonceData['Nonce'] == $Nonce)) {
            return $NonceData['Nonce'];
        }

        return false;
    }

    /**
     *
     *
     * @param $TokenKey
     */
    public function clearNonces($TokenKey) {
        Gdn::sql()->delete('UserAuthenticationNonce', array(
            'Token' => $TokenKey
        ));
    }

    /**
     *
     *
     * @return bool
     */
    public function requireLogoutTransientKey() {
        return true;
    }

    /**
     *
     *
     * @return string
     */
    public function getAuthenticationSchemeAlias() {
        $stripSuffix = str_replace('Gdn_', '', __CLASS__);
        $ClassName = str_replace('Gdn_', '', get_class($this));
        $ClassName = substr($ClassName, -strlen($stripSuffix)) == $stripSuffix ? substr($ClassName, 0, -strlen($stripSuffix)) : $ClassName;
        return strtolower($ClassName);
    }

    /**
     *
     *
     * @param $UserID
     * @param bool $Persist
     */
    public function setIdentity($UserID, $Persist = true) {
        $AuthenticationSchemeAlias = $this->getAuthenticationSchemeAlias();
        Gdn::authenticator()->setIdentity($UserID, $Persist);
        Gdn::session()->start();

        if ($UserID > 0) {
            Gdn::session()->setPreference('Authenticator', $AuthenticationSchemeAlias);
        } else {
            Gdn::session()->setPreference('Authenticator', '');
        }
    }

    /**
     *
     *
     * @param $Key
     * @param bool $Default
     * @return bool
     */
    public function getProviderValue($Key, $Default = false) {
        $Provider = $this->getProvider();
        if (array_key_exists($Key, $Provider)) {
            return $Provider[$Key];
        }

        return $Default;
    }

    /**
     *
     *
     * @return bool
     */
    public function getProviderKey() {
        return $this->getProviderValue('AuthenticationKey');
    }

    /**
     *
     *
     * @return bool
     */
    public function getProviderSecret() {
        return $this->getProviderValue('AssociationSecret');
    }

    /**
     *
     *
     * @return bool
     */
    public function getProviderUrl() {
        return $this->getProviderValue('URL');
    }
}
