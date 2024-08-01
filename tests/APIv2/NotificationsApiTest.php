<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use ActivityModel;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Vanilla\Scheduler\LongRunner;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SchedulerTestTrait;

/**
 * Verify functionality of the notifications API v2 resource.
 */
class NotificationsApiTest extends AbstractAPIv2Test
{
    use NotificationsApiTestTrait;
    use SchedulerTestTrait;

    /** @var int Debug activity type. */
    const ACTIVITY_TYPE_ID = 10;

    /** @var ActivityModel */
    private $activityModel;

    /** @var LongRunner */
    private $longRunner;

    /**
     * Add a notification for the current user.
     *
     * @param null|array $extras Extra fields to add to the notification insert.
     * @return int Activity ID of the new notification.
     */
    private function addNotification(?array $extras = []): int
    {
        $result = $this->activityModel->insert(
            array_merge(
                [
                    "ActivityTypeID" => self::ACTIVITY_TYPE_ID,
                    "DateInserted" => date("Y-m-d H:i:s", now()),
                    "DateUpdated" => date("Y-m-d H:i:s", now()),
                    "Emailed" => ActivityModel::SENT_PENDING,
                    "NotifyUserID" => $this->api()->getUserID(),
                    "Notified" => ActivityModel::SENT_PENDING,
                ],
                $extras
            )
        );
        return $result;
    }

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
        $this->longRunner = $this->getLongRunner();
    }

    /**
     * Test GET /notifications/<id>.
     */
    public function testGet()
    {
        $id = $this->addNotification();

        $response = $this->api()->get("/notifications/{$id}");
        $this->assertEquals($response->getStatusCode(), 200);

        $notification = $response->getBody();
        $this->assertEquals($id, $notification["notificationID"]);
    }

    /**
     * Test GET /notifications.
     */
    public function testGetIndex()
    {
        $originalIndex = $this->api()->get("/notifications");
        $this->assertEquals(200, $originalIndex->getStatusCode());

        for ($i = 1; $i <= 10; $i++) {
            $notificationIDs[] = $this->addNotification();
        }

        $newIndex = $this->api()->get("/notifications");

        $originalRows = $originalIndex->getBody();
        $newRows = $newIndex->getBody();
        $this->assertEquals(count($originalRows) + count($notificationIDs), count($newRows));
        // The index should be a proper indexed array.
        for ($i = 0; $i < count($newRows); $i++) {
            $this->assertArrayHasKey($i, $newRows);
        }

        $this->pagingTest("/notifications");
    }

    /**
     * Test PATCH /notifications.
     */
    public function testPatchIndex()
    {
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = $this->addNotification();
            $ids[] = $this->addNotification();
        }

        $notifications = array_column(
            $this->api()
                ->get("/notifications")
                ->getBody(),
            null,
            "notificationID"
        );

        // Make sure the notifications were added and are not read.
        foreach ($ids as $id) {
            $this->assertArrayHasKey($id, $notifications);
            $this->assertFalse($notifications[$id]["read"]);
        }

        // Flag ALL notifications as read.
        $r = $this->api()->patch("/notifications", ["read" => true]);

        $patched = array_column(
            $this->api()
                ->get("/notifications")
                ->getBody(),
            null,
            "notificationID"
        );

        foreach ($patched as $notification) {
            $this->assertTrue($notification["read"]);
        }
    }

    /**
     * Test that notications with statuses of pending, toast, and none are marked read when marking all notifications read.
     */
    public function testPatchIndexNoStatus(): void
    {
        $ids[] = $this->addNotification();
        $ids[] = $this->addNotification(["Notified" => ActivityModel::SENT_TOAST]);
        $ids[] = $this->addNotification(["Notified" => ActivityModel::SENT_NONE]);

        $notifications = array_column(
            $this->api()
                ->get("/notifications")
                ->getBody(),
            null,
            "notificationID"
        );

        // Make sure the notifications were added and are not read.
        foreach ($ids as $id) {
            $this->assertArrayHasKey($id, $notifications);
            $this->assertFalse($notifications[$id]["read"]);
        }

        // Mark all the notifications as read.
        $this->api()->patch("/notifications", ["read" => true]);

        $patched = array_column(
            $this->api()
                ->get("/notifications")
                ->getBody(),
            null,
            "notificationID"
        );

        // Verify that each notification has been marked read.
        foreach ($patched as $notification) {
            $this->assertTrue($notification["read"]);
        }
    }

    /**
     * Test PATCH /notifications/<id>.
     */
    public function testPatch()
    {
        $id = $this->addNotification();

        $getResponse = $this->api()->get("/notifications/{$id}");
        $this->assertEquals($getResponse->getStatusCode(), 200);

        // Verify the new notification is unread.
        $notification = $getResponse->getBody();
        $this->assertEquals($id, $notification["notificationID"]);
        $this->assertEquals(false, $notification["read"]);

        // Flag the notification as read.
        $patchResponse = $this->api()->patch("/notifications/{$id}", ["read" => true]);
        $this->assertEquals($patchResponse->getStatusCode(), 200);
        $this->assertTrue($patchResponse["read"]);

        // Get the updated notification.
        $updatedGetResponse = $this->api()->get("/notifications/{$id}");
        $this->assertEquals($updatedGetResponse->getStatusCode(), 200);

        // Verify it's flagged as read.
        $updatedNotification = $updatedGetResponse->getBody();
        $this->assertEquals($id, $updatedNotification["notificationID"]);
        $this->assertEquals(true, $updatedNotification["read"]);
    }

    /**
     * Test that all members of a batched group are patched when one is patched.
     */
    public function testPatchBatchedNotification(): void
    {
        $id1 = $this->addNotification(["RecordID" => 9999]);
        // Add a second notification with the same recordID to create a batch.
        $id2 = $this->addNotification(["RecordID" => 9999]);

        $patchResponse = $this->api()->patch("/notifications/{$id1}", ["read" => true]);
        $this->assertEquals($patchResponse->getStatusCode(), 200);
        $this->assertTrue($patchResponse["read"]);

        // Get the updated notification.
        $updatedGetResponse = $this->api()->get("/notifications/{$id1}");
        $this->assertEquals($updatedGetResponse->getStatusCode(), 200);

        // Verify it's flagged as read.
        $updatedNotification = $updatedGetResponse->getBody();
        $this->assertEquals($id1, $updatedNotification["notificationID"]);
        $this->assertEquals(true, $updatedNotification["read"]);

        // Get the other record in the batch.
        $updatedBatchMemberResponse = $this->api()->get("/notifications/{$id2}");
        $this->assertEquals($updatedBatchMemberResponse->getStatusCode(), 200);

        // This one should also be marked as read.
        $updatedBatchMember = $updatedBatchMemberResponse->getBody();
        $this->assertEquals($id2, $updatedBatchMember["notificationID"]);
        $this->assertEquals(true, $updatedBatchMember["read"]);
    }

    /**
     * Test PUT /notifications/<id>.
     */
    public function testPutRead()
    {
        $id = $this->addNotification();

        // Verify the new notification is unread.
        $notification = $this->getNotification($id);
        $this->assertEquals($id, $notification["notificationID"]);
        $this->assertFalse($notification["read"]);

        // Flag the notification as read.
        $patchReadResponse = $this->api()->put("/notifications/{$id}/read");
        $this->assertEquals(200, $patchReadResponse->getStatusCode());
        $this->assertTrue($patchReadResponse["read"]);
        $notification = $this->getNotification($id);
        $this->assertTrue($notification["read"]);
    }

    /**
     * Test readUrl on unread notification.
     */
    public function testReadUrl()
    {
        $id = $this->addNotification();
        // Get the notification
        $getResponse = $this->api()->get("/notifications/{$id}");
        $notification = $getResponse->getBody();
        $this->assertEquals($id, $notification["notificationID"]);
        $this->assertEquals(false, $notification["read"]);
        $this->assertEquals(ActivityModel::getReadUrl($id), $notification["readUrl"]);
        // Flag the notification as read.
        $patchReadResponse = $this->api()->put("/notifications/{$id}/read");
        $this->assertEquals(200, $patchReadResponse->getStatusCode());
        $this->assertTrue($patchReadResponse["read"]);
        $this->assertArrayNotHasKey("readUrl", $patchReadResponse);
        $notification = $this->getNotification($id);
        $this->assertArrayNotHasKey("readUrl", $notification);
    }

    /**
     * Test normalization of bad reasons.
     *
     * @return void
     */
    public function testBadReason()
    {
        $id = $this->addNotification([
            "Data" => json_encode(["Reason" => ["reason as array", "reason2"]]),
        ]);
        $getResponse = $this->api()->get("/notifications/{$id}");
        $notification = $getResponse->getBody();
        $this->assertEquals("reason as array, reason2", $notification["reason"]);
    }

    /**
     * Test that mark all read indexing can be resumed with the long runner.
     */
    public function testMarkAllReadLongRunnerContinue()
    {
        $activityID1 = $this->addNotification();
        $activityID2 = $this->addNotification();

        $this->getLongRunner()->setMaxIterations(1);
        $response = $this->api()->patch("/notifications", ["read" => true], [], ["throw" => false]);
        $this->assertNotNull($response["callbackPayload"]);
        $result = $this->activityModel->getID($activityID1);
        $this->assertEquals(ActivityModel::SENT_OK, $result["Notified"]);

        // Resume and finish.
        $this->getLongRunner()->setMaxIterations(100);
        $response = $this->resumeLongRunner($response["callbackPayload"]);
        $this->assertEquals(200, $response->getStatusCode());
        $result = $this->activityModel->getID($activityID2);
        $this->assertEquals(ActivityModel::SENT_OK, $result["Notified"]);
    }
}
