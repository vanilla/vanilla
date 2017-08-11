<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla;

use Garden\Web\RequestInterface;
use Vanilla\Models\SSOUserInfo;

abstract class SSOAuthenticator {

    /**
     * Identifier of this authenticator instance.
     *
     * Currently maps to "UserAuthenticationProvider.AuthenticationKey".
     *
     * Extending classes will most likely require to have a dependency on RequestInterface so that they can
     * fetch the ID from the URL and throw an exception if it is not found or invalid.
     *
     * @var string
     */
    private $id;

    /**
     * Tells whether the data returned by this authenticator is authoritative or not.
     * Only trusted authenticators will results in user's data being synchronized.
     *
     * @var bool
     */
    private $isTrusted = false;

    /**
     * Authenticator constructor.
     *
     * @param string|array $authenticatorID
     * @param bool $isTrusted
     */
    public function __construct($authenticatorID) {
        $this->authenticatorID = $authenticatorID;
    }

    /**
     * Core implementation of the sso() function.
     *
     * @throw Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return array The user's information.
     */
    protected abstract function authenticate(RequestInterface $request);

    /**
     * Getter of id.
     */
    public final function getID() {
        return $this->id;
    }

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
     * Authenticate an user by using the request's data.
     *
     * @throw Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return SSOUserInfo The user's information.
     */
    public final function sso(RequestInterface $request) {
        $ssoUserInfo = $this->authenticate($request);
        $ssoUserInfo['AuthenticatorID'] = $this->getID();
        $this->validateUserInfo($ssoUserInfo);
        return $ssoUserInfo;
    }
}
