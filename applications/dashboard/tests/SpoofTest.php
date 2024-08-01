<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use DashboardHooks;
use LogModel;

/**
 * Tests for Spoof plugin.
 */
class SpoofTest extends \VanillaTests\SiteTestCase
{
    use \VanillaTests\UsersAndRolesApiTestTrait;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
    }

    /**
     * Test that an admin can spoof a user.
     */
    public function testValidSpoof()
    {
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

    /**
     * Test that an admin can't spoof as a user when the Spoofing feature is disabled.
     */
    public function testInvalidSpoof()
    {
        $this->runWithConfig(["EnabledPlugins.Spoof" => false], function () {
            $preSpoofUserID = $this->getSession()->UserID;
            // Try to Spoof as a member user.
            @$this->bessy()->post("user/autospoof/{$this->memberID}", [], ["deliveryType" => DELIVERY_TYPE_ALL]);
            $postSpoofUserID = $this->getSession()->UserID;

            // The userID associated with the session should be the same as it was before the spoofing attempt.
            $this->assertEquals($preSpoofUserID, $postSpoofUserID);
        });
    }

    /**
     * Test logModel_formatContent_handler for not Spoof Operation method
     */
    public function testlogModel_formatContent_handlerFail()
    {
        $dashboardHooksClass = $this->container()->get(DashboardHooks::class);
        $logModel = $this->container()->get(LogModel::class);
        $context = [
            "Operation" => "Login",
            "SpoofUserID" => 2,
            "SpoofUserName" => "Test",
            "Data" => ["userSpoofedId" => "3", "userSpoofedName" => "Spoofed"],
        ];
        $args = ["Log" => $context];
        $dashboardHooksClass->logModel_formatContent_handler($logModel, $args);

        $this->assertArrayNotHasKey("Result", $args);
    }
}
