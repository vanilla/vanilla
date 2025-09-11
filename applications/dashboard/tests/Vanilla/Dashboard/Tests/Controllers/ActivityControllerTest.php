<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 *
 */

namespace Vanilla\Dashboard\Tests\Controllers;

use ActivityModel;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Web\Exception\ResponseException;
use Gdn;
use Gdn_UserException;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Class ActivityControllerTest to test ActivityController.
 *
 * @package Vanilla\Dashboard\Tests\Controllers
 */
class ActivityControllerTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;

    /** @var int Debug activity type. */
    const ACTIVITY_TYPE_ID = 10;

    /** @var ActivityModel */
    private $activityModel;

    /**
     * This method is called before a test is executed.
     *
     * @throws ContainerException If an error was encountered while retrieving an entry from the container.
     * @throws NotFoundException If unable to find an entry in the container.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->activityModel = $this->container()->get(ActivityModel::class);
        $this->resetTable("Activity");
    }

    /**
     * Test that posting on a public profile wall works normally with the profiles.view permission.
     */
    public function testPostOnPublicProfileWall(): void
    {
        $commentingUser = $this->createUser();
        $targetUser = $this->createUser();

        $this->runWithUser(function () use ($targetUser) {
            // Post on the target user's wall
            $comment = "Hello from the wall!";

            $result = $this->bessy()->post("/activity/post/{$targetUser["userID"]}", [
                "Comment" => $comment,
                "Format" => "Text",
                "TransientKey" => Gdn::session()->transientKey(),
            ]);

            $activities = $result->Data["Activities"];
            $this->assertCount(1, $activities);
            $this->assertSame($comment, $activities[0]["Story"]);
        }, $commentingUser);
    }

    /**
     * Test that posting on a public profile wall without profiles.view permission is denied.
     */
    public function testPostOnPublicProfileWithoutPermission(): void
    {
        $targetUser = $this->createUser();

        // Make the target user's profile private
        $userModel = Gdn::getContainer()->get(\UserModel::class);
        $userModel->saveAttribute($targetUser["userID"], "Private", 1);

        // Try to post on the private profile wall without personalInfo.view permission
        $this->expectException(Gdn_UserException::class);
        $this->expectExceptionMessage('You don\'t have permission to do that.');

        $this->runWithPermissions(
            function () use ($targetUser) {
                $this->bessy()->post("/activity/post/{$targetUser["userID"]}", [
                    "Comment" => "Hello from the wall!",
                    "Format" => "Text",
                    "TransientKey" => Gdn::session()->transientKey(),
                ]);
            },
            ["profiles.view" => false]
        );
    }

    /**
     * Test that posting on a private profile wall without personalInfo.view permission is denied.
     */
    public function testPostOnPrivateProfileWallWithoutPermission(): void
    {
        $postingUser = $this->createUser();
        $targetUser = $this->createUser();

        // Make the target user's profile private
        $userModel = Gdn::getContainer()->get(\UserModel::class);
        $userModel->saveAttribute($targetUser["userID"], "Private", 1);

        // Try to post on the private profile wall without personalInfo.view permission
        $this->expectException(Gdn_UserException::class);
        $this->expectExceptionMessage('You don\'t have permission to do that.');

        $this->runWithUser(function () use ($targetUser) {
            $this->bessy()->post("/activity/post/{$targetUser["userID"]}", [
                "Comment" => "Hello from the wall!",
                "Format" => "Text",
                "TransientKey" => Gdn::session()->transientKey(),
            ]);
        }, $postingUser);
    }

    /**
     * Test that posting on a private profile wall with personalInfo.view permission is allowed.
     */
    public function testPostOnPrivateProfileWallWithPermission(): void
    {
        $targetUser = $this->createUser();
        $userWithPermission = $this->createUser(["roleID" => \RoleModel::MOD_ID]);

        $this->runWithUser(function () use ($targetUser) {
            // Make the target user's profile private
            $userModel = Gdn::getContainer()->get(\UserModel::class);
            $userModel->saveAttribute($targetUser["userID"], "Private", 1);

            // Post on the private profile wall with personalInfo.view permission
            $comment = "Hello from the wall!";
            $result = $this->bessy()->post("/activity/post/{$targetUser["userID"]}", [
                "Comment" => $comment,
                "Format" => "Text",
                "TransientKey" => Gdn::session()->transientKey(),
            ]);

            $activities = $result->Data["Activities"];
            $this->assertCount(1, $activities);
            $this->assertSame($comment, $activities[0]["Story"]);
        }, $userWithPermission);
    }

    /**
     * Tests that activity feed can only be accessed up to page 500.
     *
     * @return void
     */
    public function testNoAccessPage501(): void
    {
        $data = $this->bessy()->getJsonData("/activity/feed");
        $this->assertEquals(200, $data->getStatus());

        $data = $this->bessy()->getJsonData("/activity/feed/p500");
        $this->assertEquals(200, $data->getStatus());

        $this->expectExceptionMessage("Activity can't be accessed beyond page 500.");
        $data = $this->bessy()->getJsonData("/activity/feed/p501");
    }

    /**
     * Add a notification for the current user.
     *
     * @param array $overrides Fields to override or add on the insert.
     * @return int Activity ID of the new notification.
     */
    private function addNotification(array $overrides = []): int
    {
        $result = $this->activityModel->insert(
            $overrides + [
                "ActivityTypeID" => self::ACTIVITY_TYPE_ID,
                "DateInserted" => date("Y-m-d H:i:s"),
                "DateUpdated" => date("Y-m-d H:i:s"),
                "Emailed" => ActivityModel::SENT_PENDING,
                "NotifyUserID" => $this->api()->getUserID(),
                "Notified" => ActivityModel::SENT_PENDING,
                "Route" => "/somewhere",
                "HeadlineFormat" => "Something happen",
            ]
        );

        return $result;
    }

    /**
     * Test read and redirect to target notification.
     */
    public function testReadAndRedirect()
    {
        $id = $this->addNotification();
        $getResponse = $this->api()->get("/notifications/{$id}");
        $notification = $getResponse->getBody();
        $this->assertEquals($id, $notification["notificationID"]);
        $this->assertEquals(false, $notification["read"]);
        // TESTMODE_ENABLED we throw a ResponseException with new Redirect() response.
        // We are using try/catch because bessy rethrow the exception.
        try {
            $url = ActivityModel::getReadUrl($id);
            $this->bessy()->get($url, ["transientKey" => Gdn::session()->transientKey()]);
            $this->fail("Expected a redirect.");
        } catch (ResponseException $ex) {
            $response = $ex->getResponse();
            $this->assertSame(302, $response->getStatus());
            $this->assertEquals(url("/somewhere", true), $response->getHeader("Location"));
            $getResponseRead = $this->api()->get("/notifications/{$id}");
            $notificationRead = $getResponseRead->getBody();
            $this->assertTrue($notificationRead["read"]);
        }
    }

    /**
     * Test read and redirect for a batched record. Should mark all records in a batch as read.
     */
    public function testReadAndRedirectBatch()
    {
        // These two notifications will be batched together.
        $notification1 = $this->addNotification(["ParentRecordID" => 888]);
        $notification2 = $this->addNotification(["ParentRecordID" => 888]);
        try {
            $url = ActivityModel::getReadUrl($notification2);
            $this->bessy()->get($url, ["transientKey" => Gdn::session()->transientKey()]);
            $this->fail("Expected a redirect.");
        } catch (ResponseException $ex) {
            $response = $ex->getResponse();
            $this->assertSame(302, $response->getStatus());
            $this->assertEquals(url("/somewhere", true), $response->getHeader("Location"));
            $responseReadNotification2 = $this->api()
                ->get("/notifications/{$notification2}")
                ->getBody();
            $this->assertTrue($responseReadNotification2["read"]);
            // This notification should also be marked as read.
            $responseReadNotification1 = $this->api()
                ->get("/notifications/{$notification1}")
                ->getBody();
            $this->assertTrue($responseReadNotification1["read"]);
        }
    }

    /**
     * Test read and redirect to target notification failed.
     */
    public function testReadAndRedirectFailed()
    {
        $user = $this->createUser();
        $id = $this->runWithUser(function () {
            return $this->addNotification();
        }, $user);

        $newID = $id + 1;
        // We are using try/catch because bessy rethrow the exception.
        // Test invalid transient Key.
        try {
            $url = ActivityModel::getReadUrl($id);
            $this->bessy()->get($url, ["transientKey" => "transientKey"]);
        } catch (Gdn_UserException $ex) {
            $this->assertSame(403, $ex->getCode());
            $this->assertSame('You don\'t have permission to do that.', $ex->getMessage());
        }

        // Test Activity not found.
        try {
            $url = ActivityModel::getReadUrl($newID);
            $this->bessy()->get($url, ["transientKey" => Gdn::session()->transientKey()]);
        } catch (Gdn_UserException $ex) {
            $this->assertSame(404, $ex->getCode());
            $this->assertSame("Activity Not Found", $ex->getMessage());
        }

        // Test has is not the activity owner.
        try {
            $url = ActivityModel::getReadUrl($id);
            $this->bessy()->get($url, ["transientKey" => Gdn::session()->transientKey()]);
        } catch (Gdn_UserException $ex) {
            $this->assertSame(403, $ex->getCode());
            $this->assertSame('You don\'t have permission to do that.', $ex->getMessage());
        }
    }
}
