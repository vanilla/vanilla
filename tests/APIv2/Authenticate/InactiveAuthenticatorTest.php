<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2\Authenticate;

use Exception;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOData;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\Authenticator\MockSSOAuthenticator;

/**
 * Class InactiveAuthenticatorTest
 */
class InactiveAuthenticatorTest extends AbstractAPIv2Test {

    /** @var MockSSOAuthenticator */
    private $authenticator;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        self::container()->rule(MockSSOAuthenticator::class);
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get(\Gdn_Configuration::class);
        $config->set('Feature.'.\AuthenticateApiController::FEATURE_FLAG.'.Enabled', true, true, false);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();


        /** @var \Vanilla\Models\AuthenticatorModel $authenticatorModel */
        $authenticatorModel = $this->container()->get(AuthenticatorModel::class);

        $uniqueID = uniqid('inactv_auth_');
        $authType = MockSSOAuthenticator::getType();
        $this->authenticator = $authenticatorModel->createSSOAuthenticatorInstance([
            'authenticatorID' => $authType,
            'type' => $authType,
            'SSOData' => json_decode(json_encode(new SSOData($authType, $authType, $uniqueID)), true),
        ]);
        $this->authenticator->setActive(false);

        $session = $this->container()->get(\Gdn_Session::class);
        $session->end();
    }

    /**
     * Cannot authenticate with an inactive authenticator.
     */
    public function testInactiveAuth() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot authenticate with an inactive authenticator.');

        $postData = [
            'authenticate' => [
                'authenticatorType' => $this->authenticator::getType(),
                'authenticatorID' => $this->authenticator->getID(),
            ],
        ];

        $this->api()->post('/authenticate', $postData);
    }
}
