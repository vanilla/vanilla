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
     * Add a notification for the current user.
     *
     * @param null|array $overrides Fields to override or add on the insert.
     * @return int Activity ID of the new notification.
     */
    private function addNotification(array $overrides = []): int
    {
        $result = $this->activityModel->insert(
            $overrides + [
                "ActivityTypeID" => self::ACTIVITY_TYPE_ID,
                "DateInserted" => date("Y-m-d H:i:s", now()),
                "DateUpdated" => date("Y-m-d H:i:s", now()),
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
