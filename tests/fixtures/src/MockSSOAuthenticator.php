<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\Fixtures;

use Garden\Web\RequestInterface;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\SSOData;

/**
 * Class MockSSOAuthenticator
 */
class MockSSOAuthenticator extends SSOAuthenticator {

    /** @var SSOData */
    protected $data;

    /**
     * MockSSOAuthenticator constructor.
     *
     * @throws \Exception
     * @param $uniqueID
     * @param $userData
     * @param $extraData
     */
    public function __construct($uniqueID = null, $userData = [], $extraData = []) {
        parent::__construct('MockSSO');

        if ($uniqueID === null) {
            $uniqueID = uniqid('MockSSOUserID_');
        }

        $this
            ->setData(new SSOData(
                self::getType(),
                $this->getID(),
                $uniqueID,
                $userData,
                $extraData
            ))
            ->setTrusted(true)
            ->setSignIn(true)
        ;
    }

    /**
     * @inheritDoc
     */
    protected function sso(RequestInterface $request): SSOData {
        return $this->getData();
    }

    /**
     * Getter of data.
     *
     * @return SSOData
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Setter of data.
     *
     * @param SSOData $data
     * @return $this
     */
    public function setData(SSOData $data) {
        $this->data = $data;

        return $this;
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
