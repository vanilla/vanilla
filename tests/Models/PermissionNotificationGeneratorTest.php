<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\Models;

use Exception;
use PermissionNotificationGenerator;
use Ramsey\Uuid\Uuid;
use RoleModel;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Scheduler\LongRunnerAction;
use VanillaTests\ExpectedNotification;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the permission notification long runner.
 */
class PermissionNotificationGeneratorTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;
    use SchedulerTestTrait;

    /**
     * Test that the long runner picks up where it left.
     *
     * @return void
     */
    public function testLongRunnerResume(): void
    {
        $member = $this->createUser();
        $admin1 = $this->createUser(["roleID" => [RoleModel::ADMIN_ID]]);
        $admin2 = $this->createUser(["roleID" => [RoleModel::ADMIN_ID]]);
        $admin3 = $this->createUser(["roleID" => [RoleModel::ADMIN_ID]]);

        $userPreferenceModel = $this->container()->get(UserNotificationPreferencesModel::class);
        $userPreferenceModel->save($member["userID"], ["Popup.Mention" => "1", "Email.Mention" => "1"]);
        $userPreferenceModel->save($admin1["userID"], ["Popup.Mention" => "1", "Email.Mention" => "1"]);
        $userPreferenceModel->save($admin2["userID"], ["Popup.Mention" => "1", "Email.Mention" => "1"]);
        $userPreferenceModel->save($admin3["userID"], ["Popup.Mention" => "1"]);

        $activity = [
            "ActivityType" => "Default",
            "ActivityEventID" => str_replace("-", "", Uuid::uuid1()->toString()),
            "ActivityUserID" => 1,
            "HeadlineFormat" => "The whole world must learn our peaceful ways...BY FORCE",
            "PluralHeadlineFormat" => null,
            "RecordType" => "test",
            "RecordID" => "1",
            "Data" => [
                "Reason" => "test",
            ],
        ];

        $notification = new ExpectedNotification(
            "Default",
            ["The whole world must learn our peaceful ways...BY FORCE"],
            "test"
        );

        // Limit to 1 loop of the job.
        $this->getLongRunner()->setMaxIterations(1);
        $action = new LongRunnerAction(PermissionNotificationGenerator::class, "notificationGenerator", [
            $activity,
            "Garden.Settings.Manage",
            "Mention",
        ]);
        $result = $this->getLongRunner()->runImmediately($action);
        $callbackPayload = $result->getCallbackPayload();
        $this->assertNotNull($callbackPayload);
        // Asserting that fist user got notifications
        $this->assertUserHasNotificationsLike($admin1["userID"], [$notification]);
        // Assert that user has new email notification.
        $this->assertUserHasEmailsLike($admin1["userID"], \ActivityModel::SENT_OK, [$notification]);
        // Clear and rerun job for the other user.
        $this->getLongRunner()->reset();
        $response = $this->resumeLongRunner($callbackPayload);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();

        $this->assertNull($body["callbackPayload"]);
        $this->assertSame(3, $body["progress"]["countTotalIDs"]);
        $this->assertCount(2, $body["progress"]["successIDs"]);
        $this->assertEquals(
            "User_{$admin2["userID"]}_NotificationType_Default_Preference_Mention",
            $body["progress"]["successIDs"][0]
        );
        $this->assertUserHasNotificationsLike($admin2["userID"], [$notification]);
        $this->assertUserHasEmailsLike($admin2["userID"], \ActivityModel::SENT_OK, [$notification]);

        $this->assertUserHasNotificationsLike($admin3["userID"], [$notification]);
        $this->assertUserHasNoEmails($admin3["userID"]);

        $this->assertUserHasNoNotifications($member["userID"]);
        $this->assertUserHasNoEmails($member["userID"]);
    }

    /**
     * Test that batching work and that more than one bat
     *
     * @return void
     * @throws Exception
     */
    public function testBatchLimit(): void
    {
        $user0 = $this->createUser(
            notificationPreferences: [
                "Mention" => ["Popup" => true, "email" => true],
            ]
        );
        $user1 = $this->createUser(
            overrides: ["roleID" => [RoleModel::ADMIN_ID]],
            notificationPreferences: [
                "Mention" => ["Popup" => true],
            ]
        );

        $user2 = $this->createUser(
            overrides: ["roleID" => [RoleModel::ADMIN_ID]],
            notificationPreferences: [
                "Mention" => ["Popup" => true, "email" => true],
            ]
        );

        $user3 = $this->createUser(
            overrides: ["roleID" => [RoleModel::ADMIN_ID]],
            notificationPreferences: [
                "Mention" => ["Popup" => true, "email" => true],
            ]
        );

        $this->runWithConfig([PermissionNotificationGenerator::BATCH_SIZE_CONFIG_KEY => 2], function () {
            $activity = [
                "ActivityType" => "Default",
                "ActivityEventID" => str_replace("-", "", Uuid::uuid1()->toString()),
                "ActivityUserID" => 1,
                "HeadlineFormat" => "The whole world must learn our peaceful ways...BY FORCE",
                "PluralHeadlineFormat" => null,
                "RecordType" => "test",
                "RecordID" => "1",
                "Data" => [
                    "Reason" => "test",
                ],
            ];

            $action = new LongRunnerAction(PermissionNotificationGenerator::class, "notificationGenerator", [
                $activity,
                "Garden.Settings.Manage",
                "Mention",
            ]);
            $this->getLongRunner()->runImmediately($action);
        });

        $notification = new ExpectedNotification(
            "Default",
            ["The whole world must learn our peaceful ways...BY FORCE"],
            "test"
        );

        $this->assertUserHasNoNotifications($user0);
        $this->assertUserHasNoEmails($user0);

        $this->assertUserHasNotificationsLike($user1, [$notification]);

        $this->assertUserHasNotificationsLike($user2, [$notification]);
        $this->assertUserHasEmailsLike($user2, \ActivityModel::SENT_OK, [$notification]);

        $this->assertUserHasNotificationsLike($user3, [$notification]);
        $this->assertUserHasEmailsLike($user3, \ActivityModel::SENT_OK, [$notification]);
    }

    /**
     * Test that the notification generator does not send notifications to users who have muted the discussion when the
     * activity in question involves a discussion.
     *
     * @return void
     */
    public function testUsersWhoHaveMutedDiscussionsAreExcluded(): void
    {
        $discussion = $this->createDiscussion();
        $comment = $this->createComment([
            "discussionID" => $discussion["discussionID"],
            "body" => "This has an @mention!",
        ]);

        $userNormal = $this->createUser(
            notificationPreferences: [
                "Mention" => ["Popup" => true, "email" => true],
            ]
        );

        $userMute = $this->createUser(
            notificationPreferences: [
                "Mention" => ["Popup" => true, "email" => true],
            ]
        );

        $this->runWithUser(function () use ($discussion) {
            $this->api()->put("discussions/{$discussion["discussionID"]}/mute", [
                "muted" => true,
            ]);
        }, $userMute);

        $activity = [
            "ActivityType" => "Default",
            "ActivityEventID" => str_replace("-", "", Uuid::uuid1()->toString()),
            "ActivityUserID" => 1,
            "HeadlineFormat" => "The whole world must learn our peaceful ways...BY FORCE",
            "PluralHeadlineFormat" => null,
            "RecordType" => "comment",
            "RecordID" => $comment["commentID"],
            "Data" => [
                "Reason" => "test",
            ],
        ];

        $action = new LongRunnerAction(PermissionNotificationGenerator::class, "notificationGenerator", [
            $activity,
            "session.valid",
            "Mention",
            0,
            null,
            null,
            false,
            [],
            $discussion["discussionID"],
        ]);

        $this->clearUserNotifications($userNormal);
        $this->clearUserNotifications($userMute);

        $this->getLongRunner()->runImmediately($action);

        $notification = new ExpectedNotification(
            "Default",
            ["The whole world must learn our peaceful ways...BY FORCE"],
            "test"
        );

        $this->assertUserHasNotificationsLike($userNormal, [$notification]);
        $this->assertUserHasEmailsLike($userNormal, \ActivityModel::SENT_OK, [$notification]);

        $this->assertUserHasNoNotifications($userMute);
        $this->assertUserHasNoEmails($userMute);
    }
}
