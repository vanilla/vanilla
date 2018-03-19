<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Authenticator;

use Garden\Web\RequestInterface;
use Vanilla\Models\SSOData;

abstract class SSOAuthenticator extends Authenticator {
    /**
     * Tells whether the data returned by this authenticator is authoritative or not.
     * User info/roles can only be synchronized by trusted authenticators.
     *
     * @var bool
     */
    private $isTrusted = false;

    /**
     * Determine whether the authenticator can automatically link users by email.
     *
     * @var bool
     */
    private $autoLinkUser = false;

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
    public final function isTrusted(): bool {
        return $this->isTrusted;
    }

    /**
     * @return bool
     */
    public function canAutoLinkUser(): bool {
        return $this->autoLinkUser;
    }

    /**
     * @param bool $autoLinkUser
     * @return SSOAuthenticator
     */
    public function setAutoLinkUser(bool $autoLinkUser): SSOAuthenticator {
        $this->autoLinkUser = $autoLinkUser;

        return $this;
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
     * Validate an authentication by using the request's data.
     *
     * @throws Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return SSOData The user's information.
     */
    public final function validateAuthentication(RequestInterface $request) {
        $ssoData = $this->sso($request);
        $ssoData->validate();

        return $ssoData;
    }

    /**
     * Core implementation of the validateAuthentication() function.
     *
     * @throws Exception Reason why the authentication failed.
     *
     * @param RequestInterface $request
     * @return SSOData The user's information.
     */
    protected abstract function sso(RequestInterface $request);

    /**
     * Setter of isTrusted.
     *
     * @param bool $isTrusted
     * @return SSOAuthenticator
     */
    protected function setTrusted(bool $isTrusted): SSOAuthenticator {
        $this->isTrusted = $isTrusted;

        return $this;
    }
}
