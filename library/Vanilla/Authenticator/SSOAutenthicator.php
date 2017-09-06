<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla;

use Garden\Web\RequestInterface;
use Vanilla\Models\SSOUserInfo;

abstract class SSOAuthenticator extends Authenticator {

    /**
     * Authenticator constructor.
     *
     * @param string $authenticatorID Currently maps to "UserAuthenticationProvider.AuthenticationKey".
     */
    public function __construct($authenticatorID) {
        parent::__construct($authenticatorID);
    }

    /**
     * Tells whether the data returned by this authenticator is authoritative or not.
     * Only trusted authenticators will results in user's data being synchronized.
     *
     * @var bool
     */
    private $isTrusted = false;

    /**
     * Getter of isTrusted.
     */
    public final function isTrusted() {
        return $this->isTrusted;
    }

    /**
     * Returns the sign in URL.
     *
     * @return string|false
     */
    public abstract function signInURL();

    /**
     * Returns the sign out URL.
     *
     * @return string|false
     */
    public abstract function signOutURL();

    /**
     * Core implementation of the authenticate() function.
     *
     * @throw Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return SSOUserInfo The user's information.
     */
    protected abstract function sso(RequestInterface $request);

    /**
     * Authenticate an user by using the request's data.
     *
     * @throw Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return array The user's information.
     */
    public final function authenticate(RequestInterface $request) {
        $ssoUserInfo = $this->sso($request);
        $ssoUserInfo['AuthenticatorID'] = $this->getID();
        $this->validateUserInfo($ssoUserInfo);
        return $ssoUserInfo;
    }
}
