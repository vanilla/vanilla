<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Authenticator\MockAuthenticator;
use VanillaTests\BootstrapTrait;

class AuthenticatorTest extends TestCase {
    use BootstrapTrait;

    /**
     * Test that an authenticator with minimal/properly implemented methods will instantiate.
     */
    public function testInstantiateAuthenticator() {
        new MockAuthenticator();
        $this->assertTrue(true);
    }
}
