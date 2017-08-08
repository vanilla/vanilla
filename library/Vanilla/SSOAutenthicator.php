<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla;

use Vanilla\Models\SSOUserInfo;

abstract class SSOAuthenticator {

    /**
     * Identifier of this authenticator instance.
     *
     * Currently maps to "UserAuthenticationProvider.AuthenticationKey".
     *
     * Extending classes will most likely require to have a dependency on Gdn_Request so that they can
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
     * @param string $authenticatorID
     * @param bool $isTrusted
     */
    function __construct($authenticatorID, $isTrusted) {
        $this->authenticatorID = $authenticatorID;
        $this->isTrusted = $isTrusted;
    }

    /**
     * Core implementation of the sso() function.
     *
     * @throw Exception Reason why the authentication failed.
     * @param Gdn_Request $request
     * @return array The user's information.
     */
    protected abstract function authenticate(Gdn_Request $request);

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
     * @return string
     */
    public abstract function signInURL();

    /**
     * Returns the sign out URL.
     *
     * @return string
     */
    public abstract function signOutURL();

    /**
     * Authenticate an user by using the request's data.
     *
     * @throw Exception Reason why the authentication failed.
     * @param Gdn_Request $request
     * @return SSOUserInfo The user's information.
     */
    public final function sso(Gdn_Request $request) {
        $ssoUserInfo = $this->authenticate($request);
        $ssoUserInfo['AuthenticatorID'] = $this->getID();
        $this->validateUserInfo($ssoUserInfo);
        return $ssoUserInfo;
    }
}
