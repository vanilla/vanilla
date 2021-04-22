<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use ActivityModel;

/**
 * Verify functionality of the notifications API v2 resource.
 */
class NotificationsApiTest extends AbstractAPIv2Test {

    /** @var int Debug activity type. */
    const ACTIVITY_TYPE_ID = 10;

    /** @var ActivityModel */
    private $activityModel;

    /**
     * Add a notification for the current user.
     *
     * @return int Activity ID of the new notification.
     */
    private function addNotification(): int {
        $result = $this->activityModel->insert([
            "ActivityTypeID" => self::ACTIVITY_TYPE_ID,
            "DateInserted" => date("Y-m-d H:i:s", now()),
            "DateUpdated" => date("Y-m-d H:i:s", now()),
            "Emailed" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $this->api()->getUserID(),
            "Notified" => ActivityModel::SENT_PENDING,
        ]);
        return $result;
    }

    /**
     * This method is called before a test is executed.
     *
     * @throws \Garden\Container\ContainerException If an error was encountered while retrieving an entry from the container.
     * @throws \Garden\Container\NotFoundException If unable to find an entry in the container.
     */
    public function setUp(): void {
        parent::setUp();
        $this->activityModel = $this->container()->get(ActivityModel::class);
    }

    /**
     * Test GET /notifications/<id>.
     */
    public function testGet() {
        $id = $this->addNotification();

        $response = $this->api()->get("/notifications/{$id}");
        $this->assertEquals($response->getStatusCode(), 200);

        $notification = $response->getBody();
        $this->assertEquals($id, $notification["notificationID"]);
    }

    /**
     * Test GET /notifications.
     */
    public function testGetIndex() {
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
    public function testPatchIndex() {
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = $this->addNotification();
            $ids[] = $this->addNotification();
        }

        $notifications = array_column(
            $this->api()->get("/notifications")->getBody(),
            null,
            'notificationID'
        );

        // Make sure the notifications were added and are not read.
        foreach ($ids as $id) {
            $this->assertArrayHasKey($id, $notifications);
            $this->assertFalse($notifications[$id]['read']);
        }

        // Flag ALL notifications as read.
        $r = $this->api()->patch("/notifications", ["read" => true]);

        $patched = array_column(
            $this->api()->get("/notifications")->getBody(),
            null,
            'notificationID'
        );

        foreach ($patched as $notification) {
            $this->assertTrue($notification['read']);
        }
    }

    /**
     * Test PATCH /notifications/<id>.
     */
    public function testPatch() {
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
        $this->assertTrue($patchResponse['read']);

        // Get the updated notification.
        $updatedGetResponse = $this->api()->get("/notifications/{$id}");
        $this->assertEquals($updatedGetResponse->getStatusCode(), 200);

        // Verify it's flagged as read.
        $updatedNotification = $updatedGetResponse->getBody();
        $this->assertEquals($id, $updatedNotification["notificationID"]);
        $this->assertEquals(true, $updatedNotification["read"]);
    }

    /**
     * Test get notification response and return the body.
     *
     * @param int $id
     * @return array
     */
    private function getNotification(int $id) {
        $getResponse = $this->api()->get("/notifications/{$id}");
        $this->assertEquals(200, $getResponse->getStatusCode());
        return $getResponse->getBody();
    }

    /**
     * Test PUT /notifications/<id>.
     */
    public function testPutRead() {
        $id = $this->addNotification();

        // Verify the new notification is unread.
        $notification = $this->getNotification($id);
        $this->assertEquals($id, $notification["notificationID"]);
        $this->assertFalse($notification["read"]);

        // Flag the notification as read.
        $patchReadResponse = $this->api()->put("/notifications/{$id}/read");
        $this->assertEquals(200, $patchReadResponse->getStatusCode());
        $this->assertTrue($patchReadResponse['read']);
        $notification = $this->getNotification($id);
        $this->assertTrue($notification["read"]);
    }

    /**
     * Test readUrl on unread notification.
     */
    public function testReadUrl() {
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
        $this->assertTrue($patchReadResponse['read']);
        $this->assertArrayNotHasKey('readUrl', $patchReadResponse);
        $notification = $this->getNotification($id);
        $this->assertArrayNotHasKey('readUrl', $notification);
    }
}
