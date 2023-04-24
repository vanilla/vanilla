<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Logging\LogDecorator;

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

        $logModel = $this->container()->get(LogModel::class);
        //Load last config edit entry.
        $record = $logModel->getWhere(["recordType" => "Spoof", "operation" => "Spoof"], "RecordDate", "desc", 0, 1)[0];
        // Spoofed user is recorded in Log table.
        $this->assertSame($preSpoofUserID, $record["SpoofUserID"]);
        // Check display formatter.
        $spoofPlugin = $this->container()->get(SpoofPlugin::class);
        $expected = "Spoofed in User ID <b>{$record["SpoofUserName"]}</b>($preSpoofUserID) as <b>{$record["Data"]["userSpoofedName"]}</b>($postSpoofUserID)";
        $args = ["Log" => $record];
        $spoofPlugin->logModel_formatContent_handler($logModel, $args);

        $this->assertSame($expected, $args["Result"]);

        // The member userID should also now be associated with the session in the GDN_Session table.
        $memberSessions = $this->api()
            ->get("/sessions", ["userID" => $this->memberID])
            ->getBody();
        $this->assertCount(1, $memberSessions);
        $this->assertSame($postSpoofSession->SessionID, $memberSessions[0]["sessionID"]);
    }

    /**
     * Test logModel_formatContent_handler for not Spoof Operation method
     */
    public function testlogModel_formatContent_handlerFail()
    {
        $spoofPlugin = $this->container()->get(SpoofPlugin::class);
        $logModel = $this->container()->get(LogModel::class);
        $context = [
            "Operation" => "Login",
            "SpoofUserID" => 2,
            "SpoofUserName" => "Test",
            "Data" => ["userSpoofedId" => "3", "userSpoofedName" => "Spoofed"],
        ];
        $args = ["Log" => $context];
        $spoofPlugin->logModel_formatContent_handler($logModel, $args);

        $this->assertArrayNotHasKey("Result", $args);
    }
}
