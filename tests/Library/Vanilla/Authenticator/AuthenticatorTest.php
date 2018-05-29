<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\Library\Vanilla;

use Garden\Container\Container;
use PHPUnit\Framework\TestCase;
use Vanilla\Models\AuthenticatorModel;
use VanillaTests\Bootstrap;
use VanillaTests\Fixtures\MockAuthenticator;

class AuthenticatorTest extends TestCase {

    /** @var Bootstrap */
    private static $bootstrap;

    /** @var Container */
    private static $container;

    /** @var AuthenticatorModel */
    private static $authenticatorModel;

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // Set up the dependency injection container.
        self::$container = $container = new Container();
        self::$bootstrap = new Bootstrap();
        self::$bootstrap->run($container);
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass() {
        self::$bootstrap::cleanup(self::$container);

        parent::tearDownAfterClass();
    }

    /**
     * Test that an authenticator with minimal/properly implemented methods will instantiate.
     */
    public function testInstantiateAuthenticator() {
        new MockAuthenticator();
        $this->assertTrue(true);
    }
}
