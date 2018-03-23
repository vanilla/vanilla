<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2\Authenticate;

use Prophecy\Exception\Exception;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\TestSSOAuthenticator;

/**
 * Class InactiveAuthenticatorTest
 */
class InactiveAuthenticatorTest extends AbstractAPIv2Test {

    /** @var TestSSOAuthenticator */
    private $authenticator;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        parent::setupBeforeClass();
        self::container()
            ->rule(TestSSOAuthenticator::class)
            ->setAliasOf('TestSSOAuthenticator');
    }

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        parent::setUp();

        $this->authenticator = new TestSSOAuthenticator();

        $uniqueID = uniqid('inactv_auth_');
        $this->authenticator->setUniqueID($uniqueID);
        $this->authenticator->setActive(false);

        $this->container()->setInstance('TestSSOAuthenticator', $this->authenticator);

        $session = $this->container()->get(\Gdn_Session::class);
        $session->end();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage The authenticator is not active.
     */
    public function testInactiveAuth() {
        $postData = [
            'authenticator' => $this->authenticator->getName(),
            'authenticatorID' => $this->authenticator->getID(),
        ];

        $this->api()->post('/authenticate', $postData);
    }
}
