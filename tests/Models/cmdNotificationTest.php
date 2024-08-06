<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\Models;

use ActivityModel;
use Garden\Container\ContainerException;
use Garden\Schema\ValidationException;
use Garden\Container\NotFoundException;
use RoleModel;
use Vanilla\Dashboard\Activity\EscalationActivity;
use Vanilla\Dashboard\Activity\MyEscalationActivity;
use Vanilla\Dashboard\Activity\ReportActivity;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use VanillaTests\ExpectedNotification;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for the notification sent by the Community Management Dashboard.
 */
class cmdNotificationTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userPreferenceModel = $this->container()->get(UserNotificationPreferencesModel::class);
        $this->resetTable("UserMeta");
        $this->createCategory();
    }

    /**
     * Test the user preferences when a new escalation is made (EscalationActivity)`.
     *
     * @param array $roleIDs
     * @param array $preferences
     * @param bool $expectNotifications
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @dataProvider provideTestUserData
     */
    public function testNewEscalationNotification(array $roleIDs, array $preferences, bool $expectNotifications): void
    {
        $user = $this->createUser(["roleID" => $roleIDs], [], ["escalation" => $preferences]);

        $discussion = $this->createDiscussion();
        $this->createEscalation($discussion);

        if ($expectNotifications) {
            $this->assertUserHasNotificationsLike($user, [
                new ExpectedNotification(
                    "Escalation",
                    ["{$discussion["name"]} has been escalated."],
                    EscalationActivity::getActivityReason()
                ),
            ]);

            $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [
                new ExpectedNotification(
                    "Escalation",
                    ["{$discussion["name"]} has been escalated."],
                    EscalationActivity::getActivityReason()
                ),
            ]);
        } else {
            $this->assertUserHasNoNotifications($user);
            $this->assertUserHasNoEmails($user);
        }
    }

    /**
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testNewEscalationNoNotificationForEscalator(): void
    {
        $user = $this->createUser(
            ["roleID" => [RoleModel::ADMIN_ID]],
            [],
            ["escalation" => ["email" => true, "popup" => true]]
        );
        $discussion = $this->createDiscussion();

        $this->runWithUser(function () use ($discussion) {
            $this->createEscalation($discussion);
        }, $user);

        $this->assertUserHasNoNotifications($user);
        $this->assertUserHasNoEmails($user);
    }

    /**
     * Test the notification headline for a rich2 comment. Make sure that the image is not included as part of the headline.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testEscalationOnComment(): void
    {
        $user = $this->createUser(
            ["roleID" => [RoleModel::ADMIN_ID]],
            [],
            ["escalation" => ["email" => true, "popup" => true]]
        );

        $this->createDiscussion();
        $comment = $this->createComment([
            "body" =>
                "[{\"type\":\"rich_embed_card\",\"children\":[{\"text\":\"\"}],\"dataSourceType\":\"url\",\"url\":\"https://ca.v-cdn.net/6032207/uploads/userpics/XHRS8AS9238C/pD678PHVV0I4T.jpeg\",\"embedData\":{\"url\":\"https://ca.v-cdn.net/6032207/uploads/userpics/XHRS8AS9238C/pD678PHVV0I4T.jpeg\",\"name\":\"Untitled Image\",\"type\":\"image/jpeg\",\"size\":0,\"width\":356,\"height\":200,\"displaySize\":\"large\",\"float\":\"none\",\"embedType\":\"image\"}},{\"type\":\"p\",\"children\":[{\"text\":\"test\"}]}]",
            "format" => "rich2",
        ]);
        $this->createEscalation($comment);

        $this->assertUserHasNotificationsLike($user, [
            new ExpectedNotification(
                "Escalation",
                ["{$comment["name"]} has been escalated."],
                EscalationActivity::getActivityReason()
            ),
        ]);

        $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [
            new ExpectedNotification(
                "Escalation",
                ["{$comment["name"]} has been escalated."],
                EscalationActivity::getActivityReason()
            ),
        ]);
    }

    /**
     * Test that the default preferences are respected for escalation notifications.
     *
     * @param array $roleIDs
     * @param array $preferences
     * @param bool $expectNotifications
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @dataProvider provideTestUserData
     */
    public function testNewEscalationDefaultPreferencesNotification(
        array $roleIDs,
        array $preferences,
        bool $expectNotifications
    ): void {
        $this->runWithConfig(
            ["Preferences.Popup.escalation" => true, "Preferences.Email.escalation" => true],
            function () use ($roleIDs, $preferences, $expectNotifications) {
                // Remove the email preference if it is set to true.
                if ($preferences["email"]) {
                    $preferences = [];
                }

                $user = $this->createUser(["roleID" => $roleIDs], [], ["escalation" => $preferences]);
                $discussion = $this->createDiscussion();
                $this->createEscalation($discussion);

                if ($expectNotifications) {
                    $this->assertUserHasNotificationsLike($user, [
                        new ExpectedNotification(
                            "Escalation",
                            ["{$discussion["name"]} has been escalated."],
                            EscalationActivity::getActivityReason()
                        ),
                    ]);

                    $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [
                        new ExpectedNotification(
                            "Escalation",
                            ["{$discussion["name"]} has been escalated."],
                            EscalationActivity::getActivityReason()
                        ),
                    ]);
                } else {
                    $this->assertUserHasNoNotifications($user);
                }
            }
        );
    }

    /**
     * Test that no notifications are sent when the escalation is made by the user who created it.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testNoNotificationForSelfEscalated(): void
    {
        $user = $this->createUser(
            ["roleID" => [RoleModel::ADMIN_ID]],
            [],
            ["escalation" => ["email" => true, "popup" => true]]
        );

        $discussion = $this->createDiscussion();
        $this->runWithUser(function () use ($discussion, $user) {
            $this->createEscalation($discussion);
        }, $user);

        $this->assertUserHasNoNotifications($user);
        $this->assertUserHasNoEmails($user);
    }

    // MyEscalation

    /**
     * Test that the user preferences are respected when a new escalation is assigned to the user (MyEscalationActivity).
     *
     * @param array $roleIDs
     * @param array $preferences
     * @param bool $expectNotifications
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @dataProvider provideTestUserData
     */
    public function testMyEscalationNotification(array $roleIDs, array $preferences, bool $expectNotifications): void
    {
        $user = $this->createUser(["roleID" => $roleIDs], [], ["myEscalation" => $preferences]);
        $discussion = $this->createDiscussion();
        $this->createEscalation($discussion, ["assignedUserID" => $user["userID"]]);

        if ($expectNotifications) {
            $this->assertUserHasNotificationsLike($user, [
                new ExpectedNotification(
                    "MyEscalation",
                    ["{$discussion["name"]} has been escalated and assigned to you."],
                    MyEscalationActivity::getActivityReason()
                ),
            ]);

            $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [
                new ExpectedNotification(
                    "MyEscalation",
                    ["{$discussion["name"]} has been escalated and assigned to you."],
                    MyEscalationActivity::getActivityReason()
                ),
            ]);
        } else {
            $this->assertUserHasNoNotifications($user);
            $this->assertUserHasNoEmails($user);
        }
    }

    /**
     * Test the notification headline for a rich2 comment. Make sure that the image is not included as part of the headline.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testMyEscalationOnComment(): void
    {
        $user = $this->createUser(
            ["roleID" => [RoleModel::ADMIN_ID]],
            [],
            ["myEscalation" => ["email" => true, "popup" => true]]
        );

        $this->createDiscussion();
        $comment = $this->createComment([
            "body" =>
                "[{\"type\":\"rich_embed_card\",\"children\":[{\"text\":\"\"}],\"dataSourceType\":\"url\",\"url\":\"https://ca.v-cdn.net/6032207/uploads/userpics/XHRS8AS9238C/pD678PHVV0I4T.jpeg\",\"embedData\":{\"url\":\"https://ca.v-cdn.net/6032207/uploads/userpics/XHRS8AS9238C/pD678PHVV0I4T.jpeg\",\"name\":\"Untitled Image\",\"type\":\"image/jpeg\",\"size\":0,\"width\":356,\"height\":200,\"displaySize\":\"large\",\"float\":\"none\",\"embedType\":\"image\"}},{\"type\":\"p\",\"children\":[{\"text\":\"test\"}]}]",
            "format" => "rich2",
        ]);
        $this->createEscalation($comment, ["assignedUserID" => $user["userID"]]);

        $this->assertUserHasNotificationsLike($user, [
            new ExpectedNotification(
                "MyEscalation",
                ["{$comment["name"]} has been escalated and assigned to you."],
                MyEscalationActivity::getActivityReason()
            ),
        ]);

        $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [
            new ExpectedNotification(
                "MyEscalation",
                ["{$comment["name"]} has been escalated and assigned to you."],
                MyEscalationActivity::getActivityReason()
            ),
        ]);
    }

    /**
     * Test to make sure default preferences are respected for escalation assignment notifications.
     *
     * @param array $roleIDs
     * @param array $preferences
     * @param bool $expectNotifications
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @dataProvider provideTestUserData
     */
    public function testMyEscalationDefaultPreferencesNotification(
        array $roleIDs,
        array $preferences,
        bool $expectNotifications
    ): void {
        $this->runWithConfig(
            ["Preferences.Popup.myEscalation" => true, "Preferences.Email.myEscalation" => true],
            function () use ($roleIDs, $preferences, $expectNotifications) {
                if ($preferences["email"]) {
                    $preferences = [];
                }

                $user = $this->createUser(["roleID" => $roleIDs], [], ["myEscalation" => $preferences]);
                $discussion = $this->createDiscussion();
                $this->createEscalation($discussion, ["assignedUserID" => $user["userID"]]);

                if ($expectNotifications) {
                    $this->assertUserHasNotificationsLike($user, [
                        new ExpectedNotification(
                            "MyEscalation",
                            ["{$discussion["name"]} has been escalated and assigned to you."],
                            MyEscalationActivity::getActivityReason()
                        ),
                    ]);

                    $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [
                        new ExpectedNotification(
                            "MyEscalation",
                            ["{$discussion["name"]} has been escalated and assigned to you."],
                            MyEscalationActivity::getActivityReason()
                        ),
                    ]);
                } else {
                    $this->assertUserHasNoNotifications($user);
                }
            }
        );
    }

    /**
     * Make sure no notifications are sent when the escalation is assigned to the user who created it.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testNoNotificationForSelfAssignedEscalation(): void
    {
        $user = $this->createUser(
            ["roleID" => [RoleModel::ADMIN_ID]],
            [],
            ["myEscalation" => ["email" => true, "popup" => true]]
        );

        $discussion = $this->createDiscussion();
        $this->runWithUser(function () use ($discussion, $user) {
            $this->createEscalation($discussion, ["assignedUserID" => $user["userID"]]);
        }, $user);

        $this->assertUserHasNoNotifications($user);
        $this->assertUserHasNoEmails($user);
    }

    /**
     * Test to make sure a notification is sent when an assignment is done after the creation of the escalation record.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testMyEscalationNotificationSentWhenUpdating(): void
    {
        $user = $this->createUser(
            ["roleID" => [RoleModel::ADMIN_ID]],
            [],
            ["myEscalation" => ["email" => true, "popup" => true]]
        );

        $discussion = $this->createDiscussion();
        $escalation = $this->createEscalation($discussion);

        // Sanity check to make sure notifications were not sent.
        $this->assertUserHasNoNotifications($user);
        $this->assertUserHasNoEmails($user);

        $this->api()->patch("escalations/{$escalation["escalationID"]}", ["assignedUserID" => $user["userID"]]);

        $this->assertUserHasNotificationsLike($user, [
            new ExpectedNotification(
                "MyEscalation",
                ["{$discussion["name"]} has been escalated and assigned to you."],
                MyEscalationActivity::getActivityReason()
            ),
        ]);

        $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [
            new ExpectedNotification(
                "MyEscalation",
                ["{$discussion["name"]} has been escalated and assigned to you."],
                MyEscalationActivity::getActivityReason()
            ),
        ]);
    }

    // New report

    /**
     * Test that the user preferences are respected when a new report is made (ReportActivity).
     *
     * @param array $roleIDs
     * @param array $preferences
     * @param bool $expectNotifications
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @dataProvider provideTestUserData
     */
    public function testNewReportNotification(array $roleIDs, array $preferences, bool $expectNotifications): void
    {
        $user = $this->createUser(["roleID" => $roleIDs], [], ["report" => $preferences]);

        $discussion = $this->createDiscussion();
        $this->createReport($discussion);
        $username = $this->getSession()->User->Name;

        if ($expectNotifications) {
            $this->assertUserHasNotificationsLike($user, [
                new ExpectedNotification(
                    "Report",
                    ["$username reported {$discussion["name"]}."],
                    ReportActivity::getActivityReason()
                ),
            ]);

            $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [
                new ExpectedNotification(
                    "Report",
                    ["$username reported {$discussion["name"]}."],
                    ReportActivity::getActivityReason()
                ),
            ]);
        } else {
            $this->assertUserHasNoNotifications($user);
            $this->assertUserHasNoEmails($user);
        }
    }

    /**
     * Test that the report notification respect default preferences.
     *
     * @param array $roleIDs
     * @param array $preferences
     * @param bool $expectNotifications
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @dataProvider provideTestUserData
     */
    public function testNewReportDefaultPreferencesNotification(
        array $roleIDs,
        array $preferences,
        bool $expectNotifications
    ): void {
        $this->runWithConfig(["Preferences.Popup.report" => true, "Preferences.Email.report" => true], function () use (
            $roleIDs,
            $preferences,
            $expectNotifications
        ) {
            // Remove the email preference if it is set to true.
            if ($preferences["email"]) {
                $preferences = [];
            }

            $user = $this->createUser(["roleID" => $roleIDs], [], ["report" => $preferences]);
            $discussion = $this->createDiscussion();
            $this->createReport($discussion);
            $username = $this->getSession()->User->Name;

            if ($expectNotifications) {
                $this->assertUserHasNotificationsLike($user, [
                    new ExpectedNotification(
                        "Report",
                        ["$username reported {$discussion["name"]}."],
                        ReportActivity::getActivityReason()
                    ),
                ]);

                $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [
                    new ExpectedNotification(
                        "Report",
                        ["$username reported {$discussion["name"]}."],
                        ReportActivity::getActivityReason()
                    ),
                ]);
            } else {
                $this->assertUserHasNoNotifications($user);
            }
        });
    }

    /**
     * Test that the plural headline is used when multiple reports are made of the same discussion.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testMultipleReportsNotifications(): void
    {
        $user = $this->createUser(
            ["roleID" => [RoleModel::ADMIN_ID]],
            [],
            ["report" => ["email" => true, "popup" => true]]
        );

        $discussion = $this->createDiscussion();
        $this->createReport($discussion);
        $this->createReport($discussion);
        $this->createReport($discussion);

        $this->assertUserHasNotificationsLike($user, [
            new ExpectedNotification(
                "Report",
                ["There are <strong>3</strong> new reports on {$discussion["name"]}."],
                ReportActivity::getActivityReason()
            ),
        ]);
    }

    /**
     * Test that the user who reported the content don't get notified.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testNoNotificationForSelfReport(): void
    {
        $user = $this->createUser(
            ["roleID" => [RoleModel::ADMIN_ID]],
            [],
            ["report" => ["email" => true, "popup" => true]]
        );

        $discussion = $this->createDiscussion();
        $this->runWithUser(function () use ($discussion, $user) {
            $this->createReport($discussion);
        }, $user);

        $this->assertUserHasNoNotifications($user);
        $this->assertUserHasNoEmails($user);
    }

    /**
     * Provide user data to test the notifications.
     *
     * @return array[]
     */
    public static function provideTestUserData(): array
    {
        return [
            "adminToNotify" => [
                "roleIDs" => [RoleModel::ADMIN_ID],
                ["email" => true, "popup" => true],
                true,
            ],
            "adminNotToNotify" => [
                "roleIDs" => [RoleModel::ADMIN_ID],
                ["email" => false, "popup" => false],
                false,
            ],
            "member" => [
                "roleIDs" => [RoleModel::MEMBER_ID],
                ["email" => true, "popup" => true],
                false,
            ],
        ];
    }
}
