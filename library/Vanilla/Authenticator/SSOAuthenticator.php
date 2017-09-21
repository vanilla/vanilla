<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Authenticator;

use Garden\Web\RequestInterface;
use Vanilla\Models\SSOInfo;

abstract class SSOAuthenticator extends Authenticator {
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
     * @param string $authenticatorID Currently maps to "UserAuthenticationProvider.AuthenticationKey".
     */
    public function __construct($authenticatorID) {
        parent::__construct($authenticatorID);
    }

    /**
     * Getter of isTrusted.
     */
    public final function isTrusted() {
        return $this->isTrusted;
    }

    /**
     * Setter of isTrusted.
     *
     * @param bool $isTrusted
     */
    protected function setTrusted($isTrusted) {
        $this->isTrusted = $isTrusted;
    }

    /**
     * Returns the registration in URL.
     *
     * @return string|false
     */
    public abstract function registrationURL();

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
     * @return SSOInfo The user's information.
     */
    protected abstract function sso(RequestInterface $request);

    /**
     * Authenticate an user by using the request's data.
     *
     * @throw Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return SSOInfo The user's information.
     */
    public final function authenticate(RequestInterface $request) {
        $ssoInfo = $this->sso($request);

        $ssoInfo['authenticatorID'] = $this->getID();
        $ssoInfo['authenticatorName'] = $this->getName();
        $ssoInfo['authenticatorIsTrusted'] = $this->isTrusted();

        $ssoInfo->validate();

        return $ssoInfo;
    }
}
