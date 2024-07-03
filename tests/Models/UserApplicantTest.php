<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\Models;

use ActivityModel;
use RoleModel;
use Vanilla\Dashboard\Activity\ApplicantActivity;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use VanillaTests\ExpectedNotification;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the user application workflow model.
 */
class UserApplicantTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->userPreferenceModel = $this->container()->get(UserNotificationPreferencesModel::class);
    }

    /**
     * Test that the user has the correct notification (if applicable) for applicants.
     *
     * @dataProvider provideApplicantNotificationData
     * @param int $roleID
     * @param array $userPref
     * @param ExpectedNotification|bool $popup
     * @param ExpectedNotification|bool $email
     * @return void
     */
    public function testApplicantNotificationsSuccess(
        int $roleID,
        array $userPref,
        ExpectedNotification|bool $popup,
        ExpectedNotification|bool $email
    ): void {
        $config = ["Garden.Registration.Method" => "Approval"];
        $user = $this->createUser(["roleID" => [$roleID]]);
        $this->userPreferenceModel->save($user["userID"], $userPref);
        $this->runWithConfig($config, function () use ($user, $popup, $email) {
            $this->createApplicant();
            if ($popup) {
                $this->assertUserHasNotificationsLike($user, [$popup]);
            } else {
                $this->assertUserHasNoNotifications($user);
            }

            if ($email) {
                $this->assertUserHasEmailsLike($user, ActivityModel::SENT_OK, [$email]);
            } else {
                $this->assertUserHasNoEmails($user);
            }
        });
    }

    /**
     * Provide a few different scenarios for testing applicant notifications.
     *
     * @return array[]
     */
    public static function provideApplicantNotificationData(): array
    {
        $notification = new ExpectedNotification(
            "Applicant",
            [" applied for membership."],
            ApplicantActivity::getActivityReason()
        );
        $r = [
            "admin-bothEnabled" => [
                RoleModel::ADMIN_ID,
                ["Popup.Applicant" => true, "Email.Applicant" => true],
                $notification,
                $notification,
            ],
            "admin-popup-only" => [
                RoleModel::ADMIN_ID,
                ["Popup.Applicant" => true, "Email.Applicant" => false],
                $notification,
                false,
            ],
            "admin-email-only" => [
                RoleModel::ADMIN_ID,
                ["Popup.Applicant" => false, "Email.Applicant" => true],
                false,
                $notification,
            ],
            "admin-none-enabled" => [RoleModel::ADMIN_ID, [], false, false],
            "member-enabled" => [
                RoleModel::MEMBER_ID,
                ["Popup.Applicant" => true, "Email.Applicant" => true],
                false,
                false,
            ],
        ];
        return $r;
    }
}
