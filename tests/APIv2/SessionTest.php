<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Tests for the Gdn_Session class.
 *
 * @package VanillaTests\APIv2
 */
class SessionTest extends AbstractAPIv2Test {
    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->createUserFixtures();
    }

    /**
     * Test that when you start a session with a stringified ID, the userID is set as an integer.
     */
    public function testUserIDIsInteger() {
        $stringifiedID = (string)$this->memberID;
        $session = $this->getSession();
        $session->start($stringifiedID);
        $this->assertIsInt($session->UserID);
        $this->assertEquals($session->UserID, $this->memberID);
    }
}
