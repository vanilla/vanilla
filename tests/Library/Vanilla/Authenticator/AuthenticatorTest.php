<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Authenticator\MockAuthenticator;


class AuthenticatorTest extends TestCase {

    /**
     * Test that an authenticator with minimal/properly implemented methods will instantiate.
     */
    public function testInstantiateAuthenticator() {
        new MockAuthenticator();
        $this->assertTrue(true);
    }
}
