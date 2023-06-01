<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use ActivityModel;
use Garden\EventManager;
use Vanilla\Dashboard\Events\NotificationEvent;
use VanillaTests\ExpectedNotification;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Some basic tests for the `ActivityModel`.
 */
class ActivityModelTest extends SiteTestCase
{
    use NotificationsApiTestTrait, UsersAndRolesApiTestTrait;

    /** @var NotificationEvent */
    private $lastEvent;

    /** @var ActivityModel */
    private $activityModel;

    /**
     * A test listener that increments the counter.
     *
     * @param NotificationEvent $e
     * @return NotificationEvent
     */
    public function handleNotificationEvent(NotificationEvent $e): NotificationEvent
    {
        $this->lastEvent = $e;
        return $e;
    }

    /**
     * Get a new model for each test.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->activityModel = $this->container()->get(ActivityModel::class);

        // Make event testing a little easier.
        $this->container()->setInstance(self::class, $this);
        $this->lastEvent = null;

        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->unbindClass(self::class);
        $eventManager->addListenerMethod(self::class, "handleNotificationEvent");
    }

    /**
     * Verify a notification event is not dispatched when no notifications are pending.
     *
     * @return void
     */
    public function testNotificationEventNotDispatched(): void
    {
        $this->activityModel->save([
            "ActivityUserID" => 1,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_SKIPPED,
            "NotifyUserID" => 2,
        ]);

        $this->assertNull($this->lastEvent);
    }

    public function testGetWhereBatchedByRecordID(): void
    {
        $baseRecord = [
            "Body" => "batchByRecordID1",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => 3,
            "ActivityType" => "Default",
            "RecordID" => 9999,
        ];

        $this->activityModel->save(array_merge($baseRecord, ["ActivityUserID" => 1]));
        $this->activityModel->save(array_merge($baseRecord, ["ActivityUserID" => 2]));

        $activityTypeID = ActivityModel::getActivityType("Default");

        $batch = $this->activityModel
            ->getWhereBatched(
                ["NotifyUserID" => 3, "ActivityTypeID" => $activityTypeID["ActivityTypeID"]],
                "",
                "",
                false,
                false,
                true
            )
            ->resultArray();

        $this->assertCount(1, $batch);
        $this->assertSame($batch[0]["count"], 2);
    }

    public function testGetWhereBatchedByParentRecordID(): void
    {
        $baseRecord = [
            "Body" => "batchByRecordID1",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => 3,
            "ActivityType" => "Default",
            "ParentRecordID" => 9999,
        ];

        $this->activityModel->save(array_merge($baseRecord, ["ActivityUserID" => 1]));
        $this->activityModel->save(array_merge($baseRecord, ["ActivityUserID" => 2]));

        $activityTypeID = ActivityModel::getActivityType("Default");

        $batch = $this->activityModel
            ->getWhereBatched(
                ["NotifyUserID" => 3, "ActivityTypeID" => $activityTypeID["ActivityTypeID"]],
                "",
                "",
                false,
                false,
                true
            )
            ->resultArray();

        $this->assertCount(1, $batch);
        $this->assertSame($batch[0]["count"], 2);
    }

    /**
     * Verify notification event dispatched when adding a new notification.
     *
     * @return void
     */
    public function testNotificationEventDispatched(): void
    {
        $this->activityModel->save([
            "ActivityUserID" => 1,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => 2,
        ]);

        $this->assertInstanceOf(NotificationEvent::class, $this->lastEvent);
        $this->assertEquals("notification", $this->lastEvent->getType());
        $this->assertNull($this->lastEvent->getSender());
        $this->assertArrayHasKey("notification", $this->lastEvent->getPayload());
    }

    /**
     * Verify sending to a nonexistent user doesn't trigger an error and doesn't dispatch an event.
     */
    public function testNotifyInvalidUser(): void
    {
        $this->activityModel->save([
            "HeadlineFormat" => __FUNCTION__,
            "NotifyUserID" => 999999,
            "Notified" => ActivityModel::SENT_PENDING,
        ]);
        $this->assertNull($this->lastEvent);
    }

    /**
     * Verify a notification event is not dispatched when no notifications are pending.
     *
     * @return void
     */
    public function testNotificationCount(): void
    {
        $this->notifyUser(["NotifyUserID" => 3]);
        $this->notifyUser(["NotifyUserID" => 3, "Notified" => ActivityModel::SENT_OK]); // Already "sent".
        $this->notifyUser(["NotifyUserID" => 4]);
        $this->notifyUser(["NotifyUserID" => 4]); // Other user. Independent cache.
        // Enable caching.
        $this->assertEquals(1, $this->activityModel->getUserTotalUnread(3));
        $this->assertEquals(2, $this->activityModel->getUserTotalUnread(4));
        $this->activityModel->getUserTotalUnread(3);
        $this->activityModel->getUserTotalUnread(3);
        $this->activityModel->getUserTotalUnread(3);
    }

    /**
     * Test that the correct email status is recorded in the Activity table when sending batched emails.
     *
     * @return void
     * @throws \Exception
     */
    public function testBatchedEmailsWithCorrectStatusRecorded()
    {
        // Run with email disabled, this should cause the status to be changed from SENT_PENDING to SENT_SKIPPED
        $this->runWithConfig(["Garden.Email.Disabled" => true], function () {
            $user1 = $this->createUser();
            $user2 = $this->createUser();

            $this->queueNotification($user1["userID"], "CommentMention", "hello world 1");
            $this->queueNotification($user2["userID"], "CommentMention", "hello world 2");

            $this->activityModel->saveQueue();

            $this->assertUserHasEmailsLike($user1["userID"], ActivityModel::SENT_SKIPPED, [
                new ExpectedNotification("CommentMention", ["hello world 1"]),
            ]);
            $this->assertUserHasEmailsLike($user2["userID"], ActivityModel::SENT_SKIPPED, [
                new ExpectedNotification("CommentMention", ["hello world 2"]),
            ]);
            $this->clearUserNotifications($user1);
            $this->clearUserNotifications($user2);
        });
    }

    /**
     * Enqueue a notification to a user.
     *
     * @param int $userID
     * @param string $activityType
     * @param string $headline
     * @return void
     * @throws \Exception
     */
    private function queueNotification(int $userID, string $activityType, string $headline)
    {
        $this->activityModel->queue(
            [
                "ActivityType" => $activityType,
                "ActivityUserID" => $this->adminID,
                "Body" => "Hello world.",
                "Format" => "markdown",
                "HeadlineFormat" => $headline,
                "NotifyUserID" => $userID,
            ],
            "Mention",
            ["Force" => true]
        );
    }

    /**
     * Send a notification to a user.
     * @param array $overrides
     * @return array
     */
    private function notifyUser(array $overrides = []): array
    {
        $params = $overrides + [
            "ActivityUserID" => 1,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $this->getSession()->UserID,
        ];
        $result = $this->activityModel->save($params);
        return $result;
    }

    public function testNotificationLink()
    {
        $expected = "Log in here to update your notification preferences: " . url("/profile/preferences", true);
        $link = $this->activityModel->getNotificationPreferencePageLink("text");
        $this->assertEquals($expected, $link);

        $expected =
            '<a href="' .
            url("/profile/preferences", true) .
            '" target="_blank">Log in here to update your notification preferences</a>';
        $link = $this->activityModel->getNotificationPreferencePageLink("html");
        $this->assertEquals($expected, $link);
    }
}
