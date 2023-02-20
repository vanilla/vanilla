<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Tests for Spoof plugin.
 */
class SpoofTest extends \VanillaTests\SiteTestCase
{
    use \VanillaTests\UsersAndRolesApiTestTrait;

    protected static $addons = ["vanilla", "spoof"];

    /**
     * Test that an admin can spoof a user.
     */
    public function testValidSpoof()
    {
        $this->createUserFixtures();

        $preSpoofUserID = $this->getSession()->UserID;

        // Spoof a member user.
        @$this->bessy()->post("user/autospoof/{$this->memberID}", [], ["deliveryType" => DELIVERY_TYPE_ALL]);

        $postSpoofSession = $this->getSession();
        $postSpoofUserID = $postSpoofSession->UserID;

        // The userID associated with the session should now be the member user's.
        $this->assertNotEquals($preSpoofUserID, $postSpoofUserID);
        $this->assertSame($this->memberID, $postSpoofUserID);

        // The member userID should also now be associated with the session in the GDN_Session table.
        $memberSessions = $this->api()
            ->get("/sessions", ["userID" => $this->memberID])
            ->getBody();
        $this->assertCount(1, $memberSessions);
        $this->assertSame($postSpoofSession->SessionID, $memberSessions[0]["sessionID"]);
    }
}
