<?php
/**
 * Authenticator Module: Base Class
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
    public $_DataHooks = [];

    /** @var string */
    public $Token;

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
     * @param $uRLType
     * @return mixed
     */
    abstract public function getURL($uRLType);

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
     * @param $dataSource
     * @param array $directSupplied
     */
    public function fetchData($dataSource, $directSupplied = []) {
        $this->_DataSource = $dataSource;

        if ($dataSource == $this) {
            foreach ($this->_DataHooks as $dataTarget => $dataHook) {
                $this->_DataHooks[$dataTarget]['value'] = val($dataTarget, $directSupplied);
            }

            return;
        }

        if (sizeof($this->_DataHooks)) {
            foreach ($this->_DataHooks as $dataTarget => $dataHook) {
                switch ($this->_DataSourceType) {
                    case self::DATA_REQUEST:
                    case self::DATA_FORM:
                        $this->_DataHooks[$dataTarget]['value'] = $this->_DataSource->getValue(
                            $dataHook['lookup'],
                            false
                        );
                        break;

                    case self::DATA_COOKIE:
                        $this->_DataHooks[$dataTarget]['value'] = $this->_DataSource->getValueFrom(
                            Gdn_Authenticator::INPUT_COOKIES,
                            $dataHook['lookup'],
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
     * @param $internalFieldName
     * @param $dataFieldName
     * @param bool $dataFieldRequired
     */
    public function hookDataField($internalFieldName, $dataFieldName, $dataFieldRequired = true) {
        $this->_DataHooks[$internalFieldName] = ['lookup' => $dataFieldName, 'required' => $dataFieldRequired];
    }

    /**
     *
     *
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function getValue($key, $default = false) {
        if (array_key_exists($key, $this->_DataHooks) && array_key_exists('value', $this->_DataHooks[$key])) {
            return $this->_DataHooks[$key]['value'];
        }

        return $default;
    }

    /**
     *
     *
     * @return string
     */
    protected function _checkHookedFields() {
        foreach ($this->_DataHooks as $dataKey => $dataValue) {
            if ($dataValue['required'] == true && (!array_key_exists('value', $dataValue) || $dataValue['value'] == null)) {
                return Gdn_Authenticator::MODE_GATHER;
            }
        }

        return Gdn_Authenticator::MODE_VALIDATE;
    }

    /**
     *
     *
     * @param null $providerKey
     * @param bool $force
     * @return array|bool|stdClass
     */
    public function getProvider($providerKey = null, $force = false) {
        static $authModel = null;
        static $provider = null;

        if (is_null($authModel)) {
            $authModel = new Gdn_AuthenticationProviderModel();
        }

        $authenticationSchemeAlias = $this->getAuthenticationSchemeAlias();
        if (is_null($provider) || $force === true) {
            if (!is_null($providerKey)) {
                $providerData = $authModel->getProviderByKey($providerKey);
            } else {
                $providerData = $authModel->getProviderByScheme($authenticationSchemeAlias, Gdn::session()->UserID);
                if (!$providerData && Gdn::session()->UserID > 0) {
                    $providerData = $authModel->getProviderByScheme($authenticationSchemeAlias, null);
                }
            }

            if ($providerData) {
                $provider = $providerData;
            } else {
                return false;
            }
        }

        return $provider;
    }

    /**
     *
     *
     * @return array|bool|stdClass
     */
    public function getToken() {
        $uatModel = new UserAuthenticationTokenModel();
        $provider = $this->getProvider();

        if (is_null($this->Token)) {
            $token = $uatModel->getByAuth(
                Gdn::authenticator()->getIdentity(),
                $provider['AuthenticationKey']
            );

            if ($token === false) {
                return false;
            } else {
                $this->Token = $token;
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
        $token = $this->getToken();
        if (is_null($this->Nonce)) {
            $userNonceData = Gdn::sql()->select('uan.*')
                ->from('UserAuthenticationNonce uan')
                ->where('uan.Token', $this->Token['Token'])
                ->get();

            if ($userNonceData->numRows()) {
                $this->Nonce = $userNonceData->firstRow(DATASET_TYPE_ARRAY);
            } else {
                return false;
            }
        }

        return $this->Nonce;
    }

    /**
     *
     *
     * @param $tokenType
     * @param $providerKey
     * @param null $userKey
     * @param bool $authorized
     * @return array|bool
     */
    public function createToken($tokenType, $providerKey, $userKey = null, $authorized = false) {
        $tokenKey = implode('.', ['token', $providerKey, time(), mt_rand(0, 100000)]);
        $tokenSecret = sha1(md5(implode('.', [$tokenKey, mt_rand(0, 100000)])));
        $uatModel = new UserAuthenticationTokenModel();

        $lifetime = Gdn::config('Garden.Authenticators.handshake.TokenLifetime', 60);
        if ($lifetime == 0 && $tokenType == 'request') {
            $lifetime = 300;
        }

        $insertArray = [
            'Token' => $tokenKey,
            'TokenSecret' => $tokenSecret,
            'TokenType' => $tokenType,
            'ProviderKey' => $providerKey,
            'Lifetime' => $lifetime,
            'Authorized' => ($authorized ? 1 : 0),
            'ForeignUserKey' => null
        ];

        if ($userKey !== null) {
            $insertArray['ForeignUserKey'] = $userKey;
        }

        try {
            $uatModel->insert($insertArray);

            if ($tokenType == 'access' && !is_null($userKey)) {
                $this->deleteToken($providerKey, $userKey, 'request');
            }
        } catch (Exception $e) {
            return false;
        }

        return $insertArray;
    }

    /**
     *
     *
     * @param $tokenKey
     * @return bool
     */
    public function authorizeToken($tokenKey) {
        $uatModel = new UserAuthenticationTokenModel();
        $result = true;
        try {
            $uatModel->update(['Authorized' => 1], ['Token' => $tokenKey]);
        } catch (Exception $e) {
            $result = false;
        }
        return $result;
    }

    /**
     *
     *
     * @param $providerKey
     * @param $userKey
     * @param null $tokenType
     * @return array|bool|stdClass
     */
    public function lookupToken($providerKey, $userKey, $tokenType = null) {
        deprecated(self::class.'::'.__METHOD__, 'UserAuthenticationTokenModel::lookup');
        $uatModel = new UserAuthenticationTokenModel();
        $result = $uatModel->lookup($providerKey, $userKey, $tokenType);
        return $result;
    }

    /**
     * Remove a record from the UserAuthenticationToken table.
     *
     * @param string $providerKey
     * @param string $userKey
     * @param string $tokenType
     */
    public function deleteToken($providerKey, $userKey, $tokenType) {
        $uatModel = new UserAuthenticationTokenModel();
        $uatModel->delete([
            'ProviderKey' => $providerKey,
            'ForeignUserKey' => $userKey,
            'TokenType' => $tokenType
        ]);
    }

    /**
     *
     *
     * @param $tokenKey
     * @param $nonce
     * @param null $timestamp
     * @return bool
     */
    public function setNonce($tokenKey, $nonce, $timestamp = null) {
        $insertArray = [
            'Token' => $tokenKey,
            'Nonce' => $nonce,
            'Timestamp' => date('Y-m-d H:i:s', (is_null($timestamp)) ? time() : $timestamp)
        ];

        try {
            $numAffected = Gdn::database()->sql()->update('UserAuthenticationNonce')
                ->set('Nonce', $insertArray['Nonce'])
                ->set('Timestamp', $insertArray['Timestamp'])
                ->where('Token', $insertArray['Token'])
                ->put();

            if (!$numAffected || !$numAffected->pdoStatement() || !$numAffected->pdoStatement()->rowCount()) {
                throw new Exception("Nothing to update.");
            }

        } catch (Exception $e) {
            $inserted = Gdn::database()->sql()->insert('UserAuthenticationNonce', $insertArray);
        }
        return true;
    }

    /**
     *
     *
     * @param $tokenKey
     * @param null $nonce
     * @return bool
     */
    public function lookupNonce($tokenKey, $nonce = null) {

        $nonceData = Gdn::database()->sql()
            ->select('uan.*')
            ->from('UserAuthenticationNonce uan')
            ->where('uan.Token', $tokenKey)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        if ($nonceData && (is_null($nonce) || $nonceData['Nonce'] == $nonce)) {
            return $nonceData['Nonce'];
        }

        return false;
    }

    /**
     *
     *
     * @param $tokenKey
     */
    public function clearNonces($tokenKey) {
        Gdn::sql()->delete('UserAuthenticationNonce', [
            'Token' => $tokenKey
        ]);
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
        $className = str_replace('Gdn_', '', get_class($this));
        $className = substr($className, -strlen($stripSuffix)) == $stripSuffix ? substr($className, 0, -strlen($stripSuffix)) : $className;
        return strtolower($className);
    }

    /**
     *
     *
     * @param $userID
     * @param bool $persist
     */
    public function setIdentity($userID, $persist = true) {
        $authenticationSchemeAlias = $this->getAuthenticationSchemeAlias();
        Gdn::authenticator()->setIdentity($userID, $persist);
        Gdn::session()->start();

        if ($userID > 0) {
            Gdn::session()->setPreference('Authenticator', $authenticationSchemeAlias);
        } else {
            Gdn::session()->setPreference('Authenticator', '');
        }
    }

    /**
     *
     *
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function getProviderValue($key, $default = false) {
        $provider = $this->getProvider();
        if (array_key_exists($key, $provider)) {
            return $provider[$key];
        }

        return $default;
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
