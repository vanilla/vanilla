<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Authenticator;

use Exception;
use Garden\Schema\Schema;
use Garden\Web\RequestInterface;
use Vanilla\Models\SSOData;

abstract class SSOAuthenticator extends Authenticator {

    /**
     * Determine whether the authenticator can automatically link users by email.
     *
     * @var bool
     */
    private $autoLinkUser = false;

    /**
     * Whether or not, using the authenticator, the user can link his account from the profile page.'
     *
     * @var bool
     */
    private $linkSession = false;

    /**
     * Tells whether the data returned by this authenticator is authoritative or not.
     * User info/roles can only be synchronized by trusted authenticators.
     *
     * @var bool
     */
    private $signIn = false;

    /**
     * Tells whether the data returned by this authenticator is authoritative or not.
     * User info/roles can only be synchronized by trusted authenticators.
     *
     * @var bool
     */
    private $trusted = false;

    /**
     * Authenticator constructor.
     *
     * @throws Exception
     * @param string $authenticatorID Currently maps to "UserAuthenticationProvider.AuthenticationKey".
     */
    public function __construct($authenticatorID) {
        parent::__construct($authenticatorID);
    }

    /**
     * @inheritdoc
     */
    final protected function getAuthenticatorDefaultInfo(): array {
        return array_merge_recursive(
            parent::getAuthenticatorDefaultInfo(),
            [
                'sso' => [
                    'canSignIn' => $this->canSignIn(),
                    'canLinkSession' => $this->canLinkSession(),
                    'isTrusted' => $this->isTrusted(),
                    'canAutoLinkUser' => $this->canAutoLinkUser(),
                ],
            ]
        );
    }

    /**
     * Get this authenticate Schema.
     *
     * @return Schema
     */
    public static function getAuthenticatorSchema(): Schema {
        return parent::getAuthenticatorSchema()->merge(
            Schema::parse([
                'sso:o' => Schema::parse([
                    'canSignIn:b' => 'Whether or not the authenticator can be used to sign in.',
                    'canLinkSession:b' => 'Whether or not, using the authenticator, the user can link his account from the profile page.',
                    'isTrusted:b' => 'Whether or not the authenticator is trusted to synchronize user information.',
                    'canAutoLinkUser:b' => 'Whether or not the authenticator can automatically link the incoming user information to an existing user account.',
                ])
            ])
        );
    }

    /**
     * Getter of autoLinkUser.
     *
     * @return bool
     */
    public function canAutoLinkUser(): bool {
        return $this->autoLinkUser;
    }

    /**
     * Setter of autoLinkUser.
     *
     * @param bool $autoLinkUser
     * @return $this
     */
    public function setAutoLinkUser(bool $autoLinkUser) {
        $this->autoLinkUser = $autoLinkUser;

        return $this;
    }

    /**
     * Tell whether a user is linked or not to this authenticator.
     *
     * @param int $userID
     * @return bool
     */
    abstract public function isUserLinked(int $userID): bool;

    /**
     * Getter of linkSession.
     *
     * @return bool
     */
    public function canLinkSession(): bool {
        return $this->linkSession;
    }

    /**
     * Setter of linkSession.
     *
     * @param bool $linkSession
     * @return $this
     */
    public function setLinkSession(bool $linkSession) {
        $this->linkSession = $linkSession;

        return $this;
    }

    /**
     * Getter of signIn.
     *
     * @return bool
     */
    public function canSignIn(): bool {
        return $this->signIn;
    }

    /**
     * Setter of signIn.
     *
     * @param bool $signIn
     * @return $this
     */
    public function setSignIn(bool $signIn) {
        $this->signIn = $signIn;

        return $this;
    }

    /**
     * Getter of trusted.
     */
    final public function isTrusted(): bool {
        return $this->trusted;
    }

    /**
     * Setter of trusted.
     *
     * @param bool $trusted
     * @return $this
     */
    protected function setTrusted(bool $trusted) {
        $this->trusted = $trusted;

        return $this;
    }

    /**
     * Validate an authentication by using the request's data.
     *
     * @throws Exception Reason why the authentication failed.
     * @param RequestInterface $request
     * @return SSOData The user's information.
     */
    final public function validateAuthenticationImpl(RequestInterface $request) {
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
    protected abstract function sso(RequestInterface $request): SSOData;
}
