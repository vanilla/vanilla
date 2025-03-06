<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Digest;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Forum\Digest\DigestModel;
use Vanilla\Scheduler\LongRunnerAction;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the backfillOptInIterator method in the DigestModel class.
 */
class DigestOptInTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use SchedulerTestTrait;

    private UserNotificationPreferencesModel $userPrefsModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->container()
            ->get(ConfigurationInterface::class)
            ->saveToConfig("Garden.DigestEnabled", true);
        $this->userPrefsModel = $this->container()->get(UserNotificationPreferencesModel::class);
        parent::setUp();
    }

    /**
     * Test the backfillOptInIterator LongRunner.
     */
    public function testDigestOptin(): void
    {
        $this->runWithConfig(["Garden.Digest.Autosubscribe.Enabled" => 1], function () {
            $time = CurrentTimeStamp::getDateTime();
            $oneWeekAgo = $time->modify("-1 week");
            // Create users
            $user1 = $this->createUser();
            $user2 = $this->createUser();
            $user3 = $this->createUser();

            $userAlreadySubscribed = $this->createUser();
            $this->userPrefsModel->save($userAlreadySubscribed["userID"], ["Email.DigestEnabled" => 1]);

            $userExplicitlyOptedOut = $this->createUser();
            $this->userPrefsModel->save($userExplicitlyOptedOut["userID"], ["Email.DigestEnabled" => 0]);

            $userNotLoggedIn = $this->createUser();
            $oneYearAgo = $time->modify("-1 year");
            $this->userModel->setField(
                $userNotLoggedIn["userID"],
                "DateLastActive",
                $oneYearAgo->format("Y-m-d H:i:s")
            );

            $bannedUser = $this->createUser();
            $this->userModel->setField($bannedUser["userID"], "Banned", 1);

            $deletedUser = $this->createUser();
            $this->userModel->setField($deletedUser["userID"], "Deleted", 1);

            // Run the backfill
            $this->getLongRunner()->runImmediately(
                new LongRunnerAction(DigestModel::class, "backfillOptInIterator", [
                    "dateLastActive" => $oneWeekAgo->format("Y-m-d"),
                ])
            );

            // The first 3 users should now be opted in.
            $user1Preferences = $this->userPrefsModel->getUserPrefs($user1["userID"]);
            $this->assertSame(3, $user1Preferences["Email.DigestEnabled"]);
            $user2Preferences = $this->userPrefsModel->getUserPrefs($user2["userID"]);
            $this->assertSame(3, $user2Preferences["Email.DigestEnabled"]);
            $user3Preferences = $this->userPrefsModel->getUserPrefs($user3["userID"]);
            $this->assertSame(3, $user3Preferences["Email.DigestEnabled"]);

            // The user who was already subscribed should still be subscribed, but retain the
            // "Preferences.Email.DigestEnabled" value of 1 in the UserMeta table.
            $userAlreadySubscribedPreferences = $this->userPrefsModel->getUserPrefs($userAlreadySubscribed["userID"]);
            $this->assertSame(1, $userAlreadySubscribedPreferences["Email.DigestEnabled"]);

            // The user who explicitly opted out should still be opted out.
            $userExplicitlyOptedOutPreferences = $this->userPrefsModel->getUserPrefs($userExplicitlyOptedOut["userID"]);
            $this->assertSame(0, $userExplicitlyOptedOutPreferences["Email.DigestEnabled"]);

            // The user who hasn't logged in for a year should still be opted out.
            $userNotLoggedInPreferences = $this->userPrefsModel->getUserPrefs($userNotLoggedIn["userID"]);
            $this->assertSame(0, $userNotLoggedInPreferences["Email.DigestEnabled"]);

            // The banned user should still be opted out.
            $bannedUserPreferences = $this->userPrefsModel->getUserPrefs($bannedUser["userID"]);
            $this->assertSame(0, $bannedUserPreferences["Email.DigestEnabled"]);

            // The deleted user should still be opted out.
            $deletedUserPreferences = $this->userPrefsModel->getUserPrefs($deletedUser["userID"]);
            $this->assertSame(0, $deletedUserPreferences["Email.DigestEnabled"]);
        });
    }

    /**
     * Test pausing and resuming the backfillOptInIterator LongRunner.
     *
     * @return void
     */
    public function testLongRunnerResume(): void
    {
        $time = CurrentTimeStamp::getDateTime();
        $oneWeekAgo = $time->modify("-1 week");

        // Running the auto-subscribe backfill to clear out any already existing users so we can test fresh with new ones.
        $this->runWithConfig(["Garden.Digest.Autosubscribe.Enabled" => 1], function () use ($oneWeekAgo) {
            $this->getLongRunner()->runImmediately(
                new LongRunnerAction(DigestModel::class, "backfillOptInIterator", [
                    "dateLastActive" => $oneWeekAgo->format("Y-m-d"),
                ])
            );
        });

        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $this->runWithConfig(["Garden.Digest.Autosubscribe.Enabled" => 1], function () use (
            $user1,
            $user2,
            $oneWeekAgo
        ) {
            $this->getLongRunner()->reset();
            $this->getLongRunner()->setMaxIterations(1);
            $result = $this->getLongRunner()->runImmediately(
                new LongRunnerAction(DigestModel::class, "backfillOptInIterator", [
                    "dateLastActive" => $oneWeekAgo->format("Y-m-d"),
                ])
            );

            // We have a callback payload.
            $callbackPayload = $result->getCallbackPayload();
            $this->assertNotNull($callbackPayload);

            // The first user should be opted in. The second one should not.
            $user1Prefs = $this->userPrefsModel->getUserPrefs($user1["userID"]);
            $this->assertSame(3, $user1Prefs["Email.DigestEnabled"]);
            $user2Prefs = $this->userPrefsModel->getUserPrefs($user2["userID"]);
            $this->assertSame(0, $user2Prefs["Email.DigestEnabled"]);

            $this->getLongRunner()->reset();
            $response = $this->resumeLongRunner($callbackPayload);
            $this->assertEquals(200, $response->getStatusCode());
            $body = $response->getBody();

            $this->assertNull($body["callbackPayload"]);
            $this->assertSame(2, $body["progress"]["countTotalIDs"]);
            $this->assertCount(1, $body["progress"]["successIDs"]);

            // The second user should now be opted in.
            $user2Prefs = $this->userPrefsModel->getUserPrefs($user2["userID"]);
            $this->assertSame(3, $user2Prefs["Email.DigestEnabled"]);
        });
    }

    /**
     * Test the POST /digest/backfill-optin endpoint with a dateLastActive that is too old.
     */
    public function testInvalidDateLastActive(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("The dateLastActive must be within the last 5 years.");
        $this->runWithConfig(["Garden.Digest.Autosubscribe.Enabled" => 1], function () {
            $sixYearsAgo = CurrentTimeStamp::getDateTime()->modify("-6 years");
            $this->api()->post("digest/backfill-optin", ["dateLastActive" => $sixYearsAgo->format("Y-m-d")]);
        });
    }
}
