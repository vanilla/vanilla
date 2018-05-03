<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2\Authenticate;

use Exception;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockSSOAuthenticator;

/**
 * Class InactiveAuthenticatorTest
 */
class InactiveAuthenticatorTest extends AbstractAPIv2Test {

    /** @var MockSSOAuthenticator */
    private $authenticator;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        parent::setupBeforeClass();
        self::container()
            ->rule(MockSSOAuthenticator::class)
            ->setAliasOf('MockSSOAuthenticator');
    }

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        parent::setUp();

        $uniqueID = uniqid('inactv_auth_');
        $this->authenticator = new MockSSOAuthenticator($uniqueID);
        $this->authenticator->setActive(false);

        $this->container()->setInstance('MockSSOAuthenticator', $this->authenticator);

        $session = $this->container()->get(\Gdn_Session::class);
        $session->end();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Cannot authenticate with an inactive authenticator.
     */
    public function testInactiveAuth() {
        $postData = [
            'authenticate' => [
                'authenticatorType' => $this->authenticator::getType(),
                'authenticatorID' => $this->authenticator->getID(),
            ],
        ];

        $this->api()->post('/authenticate', $postData);
    }
}
