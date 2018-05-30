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
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOData;

/**
 * Class SSOAuthenticator
 */
abstract class SSOAuthenticator extends Authenticator {

    /** @var bool */
    private $active = true;

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

    /** @var string */
    private $signInUrl;

    /** @var string */
    private $signOutUrl;

    /** @var string */
    private $registerUrl;

    /** @var AuthenticatorModel */
    protected $authenticatorModel;

    /** @var bool Prevent this object from saving itself in the DB when using set{$propery}() when constructing the object. */
    protected $saveState = false;

    /**
     * Authenticator constructor.
     *
     * @param string $authenticatorID Currently maps to "UserAuthenticationProvider.AuthenticationKey".
     * @param \Vanilla\Models\AuthenticatorModel $authenticatorModel
     *
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function __construct($authenticatorID, AuthenticatorModel $authenticatorModel) {
        $this->authenticatorModel = $authenticatorModel;
        $data = $this->loadData($authenticatorID);
        if (!$data) {
            throw new \Exception('Could not load authenticator data.');
        }

        $this->setAuthenticatorInfo($data);

        parent::__construct($authenticatorID);
        $this->saveState = true;
    }

    /**
     * Load this authenticator instance's data.
     *
     * Highly coupled to AuthenticatorModel.
     *
     * Calling {@link AuthenticatorModel::createSSOAuthenticatorInstance($data)} put $data in memory and that data is
     * then passed back to this function from {@link AuthenticatorModel::getSSOAuthenticatorData()}.
     *
     * Calling {@link AuthenticatorModel::getSSOAuthenticatorData()} will return data from the database.
     *
     * @param string $authenticatorID
     * @return bool|mixed
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    protected function loadData(string $authenticatorID) {
        return $this->authenticatorModel->getSSOAuthenticatorData(self::getType(), $authenticatorID);
    }

    /**
     *
     *
     * @param array $data
     *
     * @throws \Garden\Schema\ValidationException
     */
    protected function setAuthenticatorInfo(array $data) {
        // SSO Stuff
        if ($data['sso']['canSignIn'] ?? false) {
            $this->setSignIn(true);
        }
        if ($data['sso']['canLinkSession'] ?? false) {
            $this->setLinkSession(true);
        }
        if ($data['sso']['isTrusted'] ?? false) {
            $this->setTrusted(true);
        }
        if ($data['sso']['canAutoLinkUser'] ?? false) {
            $this->setAutoLinkUser(true);
        }

        // Set data to appropriate target.
        foreach($data as $key => $value) {
            // Set directly to variable.
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            // Set through method name can{Something}.
            } else if (substr($key, 0, 3) === 'can' && method_exists($this, $key)) {
                $this->{$key}($value);
            // Set through method name set{Something}. Note that the property should be named is{Something}.
            } else if (preg_match('/^is([A-Z].+)/', $key, $matches) && method_exists($this, 'set'.$matches[1])) {
                $this->{'set'.$matches[1]}($value);
            }
        }
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
                    'canAutoLinkUser:b' => 'Whether or not the authenticator can automatically link the incoming user information to an existing user account by using email address.',
                ])
            ])
        );
    }

    /**
     * @inheritdoc
     */
    public function setActive(bool $active) {
        $this->active = $active;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

        return $this;
    }

    public function isActive(): bool {
        return $this->active;
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
     *
     * @return $this
     * @throws \Garden\Schema\ValidationException
     */
    public function setAutoLinkUser(bool $autoLinkUser) {
        $this->autoLinkUser = $autoLinkUser;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

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
     *
     * @return $this
     * @throws \Garden\Schema\ValidationException
     */
    public function setLinkSession(bool $linkSession) {
        $this->linkSession = $linkSession;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

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
     *
     * @return $this
     * @throws \Garden\Schema\ValidationException
     */
    public function setSignIn(bool $signIn) {
        $this->signIn = $signIn;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

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
     *
     * @return $this
     * @throws \Garden\Schema\ValidationException
     */
    protected function setTrusted(bool $trusted) {
        $this->trusted = $trusted;

        if ($this->saveState) {
            $this->authenticatorModel->saveSSOAuthenticatorData($this);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRegisterUrl() {
        return $this->registerUrl;
    }

    /**
     * @inheritdoc
     */
    public function getSignInUrl() {
        return $this->signInUrl;
    }

    /**
     * @inheritdoc
     */
    public function getSignOutUrl() {
        return $this->signOutUrl;
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
