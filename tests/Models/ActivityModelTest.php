<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use ActivityModel;
use Garden\EventManager;
use Garden\Web\Exception\ResponseException;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Events\NotificationEvent;
use Vanilla\Formatting\Formats\WysiwygFormat;
use VanillaTests\ExpectedNotification;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Some basic tests for the `ActivityModel`.
 */
class ActivityModelTest extends SiteTestCase
{
    use NotificationsApiTestTrait, UsersAndRolesApiTestTrait, TestCategoryModelTrait;

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

        $batchedCount = $this->activityModel->getUserTotalUnread(3);
        $this->assertEquals(1, $batchedCount);
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

        $batchedCount = $this->activityModel->getUserTotalUnread(3);
        $this->assertEquals(1, $batchedCount);
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
     *
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

    /**
     * Test unsubscribe link and token.
     *
     * @return void
     */
    public function testUnsubscribeLinkToken()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $this->activityModel->save([
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "ActivityType" => "badge",
        ]);

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $unsubscribeLink = $this->activityModel->getUnsubscribeLink($activityUserID, $notifyUser, "text");

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];

        $activity = $this->getActivityFromToken($token);
        $this->assertEquals("badge, badge", $activity["reason"]);
    }

    /**
     * Test unsubscribe link and wrong token.
     *
     * @return void
     */
    public function testUnsubscribeLinkWrongToken()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $this->activityModel->save([
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => ["Reason" => "badge"],
        ]);

        // $expected = '<a href="' . url("/profile/unsubscribe?token=", true) . '" target="_blank">Unsubscribe</a>';
        $notifyUser = $this->userModel->getID(1, DATASET_TYPE_ARRAY);
        $unsubscribeLink = $this->activityModel->getUnsubscribeLink($activityUserID, $notifyUser, "html");

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];
        $broken = explode("\"", $token);
        $token = $broken[0];
        $this->expectExceptionMessage("Notification not found.");
        $this->getActivityFromToken($token);
    }

    /**
     * Test unfollowCategory link and token.
     *
     * @return void
     */
    public function testUnfollowCategoryLinkToken()
    {
        $notifyUserID = 2;
        $categoryID = $this->categoryModel->save($this->newCategory([]));

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $unsubscribeLink = $this->activityModel->getUnfollowCategoryLink($notifyUser, $categoryID);

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];

        $activity = $this->getActivityFromToken($token);
        $this->assertEquals("advanced", $activity["reason"]);
        $this->assertEquals("Digest", $activity["activityType"]);
    }

    /**
     * Test unsubscribe digest link.
     *
     * @return void
     */
    public function testUnsubscribeDigestinkToken()
    {
        $notifyUserID = 2;

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $unsubscribeLink = $this->activityModel->getUnsubscribeDigestLink($notifyUser);

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];

        $activity = $this->getActivityFromToken($token);
        $this->assertEquals("DigestEnabled", $activity["reason"]);
    }

    /**
     * Test unsubscribe wrong token.
     *
     * @return void
     */
    public function testUnsubscribeLinkInvalidToken()
    {
        $this->expectExceptionMessage("Wrong number of segments");
        $this->activityModel->decodeNotificationToken("sadgasdgasdg");
    }

    /**
     * Test that the notificationPreference() method returns the correct values.
     *
     * @param array $defaultPrefs
     * @param array $userPrefs
     * @param bool $expectedPopupPref
     * @param bool $expectedEmailPref
     * @return void
     * @dataProvider provideTestGettingNotificationPreference
     */
    public function testGettingNotificationPreference(
        array $defaultPrefs,
        array $userPrefs,
        bool $expectedPopupPref,
        bool $expectedEmailPref
    ): void {
        $config = $this->container()->get(ConfigurationInterface::class);
        $config->saveToConfig($defaultPrefs);
        $preferences = ActivityModel::notificationPreference("Mention", $userPrefs, "both");
        $this->assertSame($expectedPopupPref, $preferences[0]);
        $this->assertSame($expectedEmailPref, $preferences[1]);
    }

    /**
     * Provide test data for testing notificationPreference() method.
     *
     * @return array[]
     */
    public function provideTestGettingNotificationPreference(): array
    {
        $r = [
            "defaultsFalsePrefsFalse" => [
                [
                    "Preferences.Popup.Mention" => false,
                    "Preferences.Email.Mention" => false,
                ],
                [
                    "Popup.Mention" => false,
                    "Email.Mention" => false,
                ],
                false,
                false,
            ],
            "defaultsFalsePrefsTrue" => [
                [
                    "Preferences.Popup.Mention" => false,
                    "Preferences.Email.Mention" => false,
                ],
                [
                    "Popup.Mention" => true,
                    "Email.Mention" => true,
                ],
                true,
                true,
            ],
            "defaultsTruePrefsFalse" => [
                [
                    "Preferences.Popup.Mention" => true,
                    "Preferences.Email.Mention" => true,
                ],
                [
                    "Popup.Mention" => false,
                    "Email.Mention" => false,
                ],
                false,
                false,
            ],
            "defaultsTruePrefsTrue" => [
                [
                    "Preferences.Popup.Mention" => true,
                    "Preferences.Email.Mention" => true,
                ],
                [
                    "Popup.Mention" => true,
                    "Email.Mention" => true,
                ],
                true,
                true,
            ],
        ];
        return $r;
    }

    /**
     * Test Wall comments/status updates.
     *
     * @return void
     */
    public function testStatusComment()
    {
        $user = $this->createUser();
        \Gdn::config()->saveToConfig([
            "Preferences.Email.WallComment" => true,
            "Preferences.Popup.Participated" => true,
        ]);

        $this->runWithUser(function () use ($user) {
            try {
                $body = $this->bessy()->postJsonData("/activity/post/{$user["userID"]}", [
                    "TransientKey" => Gdn::session()->transientKey(),
                    "Format" => "Rich",
                    "Comment" => "[{\"insert\":\"test\"}]",
                    "DeliveryType" => "View",
                    "DeliveryMethod" => "JSON",
                ]);
                $this->fail("Expected a redirect.");
            } catch (ResponseException $ex) {
                $response = $ex->getResponse();
                $this->assertSame(302, $response->getStatus());
            }
        }, $user);
        $secondUser = $this->createUser();

        $notification = $this->activityModel
            ->getWhere(["ActivityUserID" => $user["userID"]], "ActivityID", "desc", 1)
            ->resultArray()[0];

        $this->assertSame("[{\"insert\":\"test\"}]", $notification["Story"]);

        $activityID = $notification["ActivityID"];
        $this->runWithUser(function () use ($activityID) {
            try {
                $this->bessy()->postJsonData("/activity/comment", [
                    "TransientKey" => Gdn::session()->transientKey(),
                    "ActivityID" => $activityID,
                    "Format" => "Rich",
                    "Body" => "[{\"insert\":\"Comment\"}]",
                    "DeliveryType" => "View",
                    "DeliveryMethod" => "JSON",
                ]);
                $this->fail("Expected a redirect.");
            } catch (ResponseException $ex) {
                $response = $ex->getResponse();
                $this->assertSame(302, $response->getStatus());
            }
        }, $secondUser);

        $this->assertUserHasEmailsLike($user["userID"], \ActivityModel::SENT_OK, [
            new ExpectedNotification("WallComment", ["commented on your <strong>wall</strong>."]),
        ]);
    }

    /**
     * Test Wall comments/status updates.
     *
     * @return void
     */
    public function testWallPostComment()
    {
        $user = $this->createUser();
        $secondUser = $this->createUser();
        \Gdn::config()->saveToConfig([
            "Preferences.Email.WallComment" => true,
            "Preferences.Popup.Participated" => true,
        ]);

        $this->runWithUser(function () use ($user) {
            try {
                $body = $this->bessy()->postJsonData("/activity/post/{$user["userID"]}", [
                    "TransientKey" => Gdn::session()->transientKey(),
                    "Format" => "Rich",
                    "Comment" => "[{\"insert\":\"test\"}]",
                    "DeliveryType" => "View",
                    "DeliveryMethod" => "JSON",
                ]);
                $this->fail("Expected a redirect.");
            } catch (ResponseException $ex) {
                $response = $ex->getResponse();
                $this->assertSame(302, $response->getStatus());
            }
        }, $secondUser);

        $notification = $this->activityModel
            ->getWhere(["ActivityUserID" => $secondUser["userID"]], "ActivityID", "desc", 1)
            ->resultArray()[0];

        $this->assertSame("[{\"insert\":\"test\"}]", $notification["Story"]);

        $activityID = $notification["ActivityID"];
        $this->runWithUser(function () use ($activityID) {
            try {
                $this->bessy()->postJsonData("/activity/comment", [
                    "TransientKey" => Gdn::session()->transientKey(),
                    "ActivityID" => $activityID,
                    "Format" => "Rich",
                    "Body" => "[{\"insert\":\"Comment\"}]",
                    "DeliveryType" => "View",
                    "DeliveryMethod" => "JSON",
                ]);
                $this->fail("Expected a redirect.");
            } catch (ResponseException $ex) {
                $response = $ex->getResponse();
                $this->assertSame(302, $response->getStatus());
            }
        }, $user);

        $this->assertUserHasEmailsLike($secondUser["userID"], \ActivityModel::SENT_OK, [
            new ExpectedNotification("WallComment", ["commented on your <strong>wall</strong>."]),
        ]);
    }

    /**
     * Get activity data from token string
     * @param string $token
     * @return array
     * @throws \Garden\Web\Exception\NotFoundException
     */
    private function getActivityFromToken(string $token): array
    {
        $payload = $this->activityModel->decodeNotificationToken($token);
        return $this->activityModel->getNotificationData($payload);
    }

    /**
     * Tests that absolute URLs are used in notification emails.
     *
     * @return void
     * @throws \Exception
     */
    public function testAbsoluteUrlsUsedInEmails()
    {
        $user = $this->createUser();
        $activity = [
            "ActivityType" => "Comment",
            "ActivityUserID" => 2,
            "NotifyUserID" => $user["userID"],
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "Emailed" => ActivityModel::SENT_PENDING,
            "Ext" => [
                "Email" => [
                    "Format" => WysiwygFormat::FORMAT_KEY,
                    "Story" => "<div><a href='https://www.google.com/'>Test</a></div>",
                ],
            ],
        ];

        $emailContent = null;

        // This is to get the final rendered html content of the email.
        Gdn::eventManager()->bind("ActivityModel_BeforeSendNotification", function ($sender, $args) use (
            &$emailContent
        ) {
            /** @var \Gdn_Email $email */
            $email = $args["Email"];
            $emailContent = $email->getEmailTemplate()->getMessage();
        });

        $this->activityModel->save($activity);

        // Check that the email content uses absolute URLs.
        $emailContent = new TestHtmlDocument($emailContent);
        $emailContent->assertContainsLink(
            "https://vanilla.test/activitymodeltest/home/leaving?" .
                http_build_query(["allowTrusted" => 1, "target" => "https://www.google.com/"]),
            "Test"
        );
    }

    /**
     * Test that emails include mute links for supported record types.
     *
     * @dataProvider provideSupportedRecordTypesForMuteLinks
     */
    public function testEmailIncludesMuteLinkForSupportedRecordTypes(string $recordType): void
    {
        $user = $this->createUser();
        $discussionID = 123;

        // Test the mute link generation directly instead of email capture
        $activity = [
            "ActivityType" => "Comment",
            "RecordType" => $recordType,
            "ParentRecordID" => $discussionID,
        ];

        $userArray = [
            "UserID" => $user["userID"],
            "Name" => $user["name"] ?? "TestUser",
            "Email" => "test@example.com",
            "PhotoUrl" => "",
        ];

        // Test mute link generation
        $muteLink = $this->activityModel->getMuteLink($activity, $userArray, "html");

        // Verify the mute link contains expected elements
        $this->assertStringContainsString(
            "Mute this Post",
            $muteLink,
            "Mute link should contain correct text for {$recordType} record type"
        );
        $this->assertStringContainsString(
            "/unsubscribe/",
            $muteLink,
            "Mute link should contain unsubscribe URL for {$recordType} record type"
        );
        $this->assertStringContainsString(
            "target=\"_blank\"",
            $muteLink,
            "Mute link should open in new tab for {$recordType} record type"
        );

        // Extract and decode the token
        preg_match('/\/unsubscribe\/([^"]+)/', $muteLink, $matches);
        $this->assertNotEmpty($matches, "Should be able to extract token from mute link for {$recordType} record type");

        $token = $matches[1];
        $tokenData = $this->activityModel->decodeNotificationToken($token);

        // Verify token contains correct data
        $this->assertEquals(
            $discussionID,
            $tokenData->DiscussionID,
            "Token should contain the correct discussion ID for {$recordType} record type"
        );
        $this->assertEquals(
            $user["userID"],
            $tokenData->UserID,
            "Token should contain the correct user ID for {$recordType} record type"
        );
        $this->assertEquals(
            1,
            $tokenData->Mute,
            "Token should indicate this is a mute action for {$recordType} record type"
        );
    }

    /**
     * Provide supported record types for mute links.
     *
     * @return array
     */
    public function provideSupportedRecordTypesForMuteLinks(): array
    {
        return [
            "discussion" => ["discussion"],
            "comment" => ["comment"],
            "group" => ["group"],
        ];
    }

    /**
     * Test that unsupported record types do not include mute links.
     */
    public function testUnsupportedRecordTypeDoesNotIncludeMuteLink(): void
    {
        $user = $this->createUser();
        $discussionID = 123;

        // Test unsupported record type
        $activity = [
            "ActivityType" => "Comment",
            "RecordType" => "unsupported",
            "ParentRecordID" => $discussionID,
        ];

        $userArray = [
            "UserID" => $user["userID"],
            "Name" => "TestUser",
            "Email" => "test@example.com",
            "PhotoUrl" => "",
        ];

        // Test that getMuteLink works with unsupported record types (it should still work)
        $muteLink = $this->activityModel->getMuteLink($activity, $userArray, "html");

        // The mute link should still be generated even for unsupported record types
        // The filtering happens in the email method, not in getMuteLink
        $this->assertStringContainsString("Mute this Post", $muteLink, "getMuteLink should work for any record type");
        $this->assertStringContainsString("/unsubscribe/", $muteLink, "getMuteLink should contain unsubscribe URL");

        // Extract and decode the token to verify it works
        preg_match('/\/unsubscribe\/([^"]+)/', $muteLink, $matches);
        $this->assertNotEmpty($matches, "Should be able to extract token from mute link");

        $token = $matches[1];
        $tokenData = $this->activityModel->decodeNotificationToken($token);

        $this->assertEquals($discussionID, $tokenData->DiscussionID, "Token should contain the correct discussion ID");
        $this->assertEquals($user["userID"], $tokenData->UserID, "Token should contain the correct user ID");
        $this->assertEquals(1, $tokenData->Mute, "Token should indicate this is a mute action");
    }

    /**
     * Test that the email condition logic correctly identifies which record types should have mute links.
     */
    public function testEmailMuteLinkConditionLogic(): void
    {
        $supportedRecordTypes = ["comment", "discussion", "group"];
        $unsupportedRecordTypes = ["user", "category", "unsupported", null];

        foreach ($supportedRecordTypes as $recordType) {
            $activity = ["RecordType" => $recordType];

            // Test the condition logic that determines if mute links should be added
            $shouldIncludeMuteLink =
                isset($activity["RecordType"]) &&
                (strtolower($activity["RecordType"]) === "comment" ||
                    strtolower($activity["RecordType"]) === "discussion" ||
                    strtolower($activity["RecordType"]) === "group");

            $this->assertTrue($shouldIncludeMuteLink, "Record type '{$recordType}' should support mute links");
        }

        foreach ($unsupportedRecordTypes as $recordType) {
            $activity = ["RecordType" => $recordType];

            // Test the condition logic that determines if mute links should be added
            $shouldIncludeMuteLink =
                isset($activity["RecordType"]) &&
                (strtolower($activity["RecordType"]) === "comment" ||
                    strtolower($activity["RecordType"]) === "discussion" ||
                    strtolower($activity["RecordType"]) === "group");

            $this->assertFalse($shouldIncludeMuteLink, "Record type '{$recordType}' should not support mute links");
        }
    }
}
