<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\Fixtures\Authenticator;

use Garden\Web\RequestInterface;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\SSOData;
use Vanilla\Models\AuthenticatorModel;

/**
 * Class MockSSOAuthenticator
 */
class MockSSOAuthenticator extends SSOAuthenticator {

    /**
     * MockSSOAuthenticator constructor.
     *
     * @param \Vanilla\Models\AuthenticatorModel $authenticatorModel
     *
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function __construct(AuthenticatorModel $authenticatorModel) {
        parent::__construct('MockSSO', $authenticatorModel);
    }

    protected function setAuthenticatorInfo(array $data) {
        $data['attributes'] = $data['attributes'] ?? [];
        $data['attributes']['SSOData'] = $data['attributes']['SSOData'] ?? [];
        $data['attributes']['SSOData']['uniqueID'] = $data['attributes']['SSOData']['uniqueID'] ?? uniqid('MockSSOUserID_');

        // Defaults to canSignIn
        $data['sso']['canSignIn'] = $data['sso']['canSignIn'] ?? true;
        // Defaults to isTrusted
        $data['sso']['isTrusted'] = $data['sso']['isTrusted'] ?? true;

        parent::setAuthenticatorInfo($data);

        $this->attributes['SSOData'] = SSOData::fromArray($this->attributes['SSOData'] ?? []);
    }

    /**
     * @inheritDoc
     */
    protected function sso(RequestInterface $request): SSOData {
        return $this->getData();
    }

    /**
     * @return \Vanilla\Models\SSOData
     */
    protected function getData() {
        return $this->attributes['SSOData'];
    }

    /**
     * @inheritDoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => 'http://www.example.com/image.jpg',
                'backgroundColor' => '#ffffff',
                'foregroundColor' => '#000000',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function isUnique(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function getAuthenticatorInfoImpl(): array {
        return [
            'ui' => [
                'buttonName' => 'Sign in with MockAuthenticator',
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRegisterUrl() {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSignInUrl() {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSignOutUrl() {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function isUserLinked(int $userID): bool {
        $userModel = new \UserModel();
        return (bool)$userModel->getAuthenticationByUser($userID, $this->getID());
    }

    /**
     * @inheritdoc
     */
    public function setTrusted(bool $trusted) {
        return parent::setTrusted($trusted);
    }
}
