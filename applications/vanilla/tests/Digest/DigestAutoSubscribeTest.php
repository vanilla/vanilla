<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Digest;

use Vanilla\Community\Events\SubscriptionChangeEvent;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the auto-subscribe feature of the digest.
 */
class DigestAutoSubscribeTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use EventSpyTestTrait;

    private UserNotificationPreferencesModel $userPrefsModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userPrefsModel = $this->container()->get(UserNotificationPreferencesModel::class);
        $this->clearDispatchedEvents();
    }

    /**
     * Test that the auto-subscribe flag is set when registering a new user and persists until the preference has been disabled.
     */
    public function testAutoSubscribeRegistrant(): void
    {
        $this->runWithConfig(["Garden.Digest.Enabled" => 1, "Garden.Digest.Autosubscribe.Enabled" => 1], function () {
            $this->getEventManager()->clearDispatchedEvents();
            $registrationRecord = $this->getRegistrationRecord();
            $registrationResults = $this->registerNewUser($registrationRecord);
            $userID = $registrationResults->Data["UserID"];
            $preferences = $this->userPrefsModel->getUserPrefs($userID);
            // The digest should be enabled on registration.
            $this->assertSame(3, $preferences["Email.DigestEnabled"]);

            // An auto-subscribe subscription change event should have been dispatched.
            $this->assertEventDispatched(
                $this->expectedResourceEvent(
                    SubscriptionChangeEvent::COLLECTION_NAME,
                    SubscriptionChangeEvent::ACTION_DIGEST_AUTO_SUBSCRIBE,
                    []
                ),
                []
            );
            $this->clearDispatchedEvents();

            // Setting the preference to 1 should not change the underlying value of 3 for the preference. We need to
            // preserve this setting unless opting out or opting in from a state of being opted out.
            $updatedPreferences = $this->api()
                ->patch("/notification-preferences/{$userID}", ["DigestEnabled" => ["email" => true]])
                ->getBody();
            // Still opted in.
            $this->assertTrue($updatedPreferences["DigestEnabled"]["email"]);
            // Still set as 3 under the hood.
            $preferences = $this->userPrefsModel->getUserPrefs($userID);
            $this->assertSame(3, $preferences["Email.DigestEnabled"]);

            // No events should have been dispatched.
            $this->assertEventNotDispatched([
                "type" => SubscriptionChangeEvent::COLLECTION_NAME,
                "action" => SubscriptionChangeEvent::ACTION_DIGEST_ENABLED,
            ]);
            $this->assertEventNotDispatched([
                "type" => SubscriptionChangeEvent::COLLECTION_NAME,
                "action" => SubscriptionChangeEvent::ACTION_DIGEST_AUTO_SUBSCRIBE,
            ]);
            $this->clearDispatchedEvents();

            // Opting out should set the preference to 0.
            $updatedPreferences = $this->api()
                ->patch("/notification-preferences/{$userID}", ["DigestEnabled" => ["email" => false]])
                ->getBody();
            $this->assertFalse($updatedPreferences["DigestEnabled"]["email"]);

            // An unsubscribe event should have been dispatched.
            $this->assertEventDispatched(
                $this->expectedResourceEvent(
                    SubscriptionChangeEvent::COLLECTION_NAME,
                    SubscriptionChangeEvent::ACTION_DIGEST_DISABLED,
                    []
                ),
                []
            );
            $this->clearDispatchedEvents();

            // Opting back in should set the preference to 1.
            $updatedPreferences = $this->api()
                ->patch("/notification-preferences/{$userID}", ["DigestEnabled" => ["email" => true]])
                ->getBody();
            $this->assertTrue($updatedPreferences["DigestEnabled"]["email"]);
            // Now it's set to 1 in the DB.
            $preferences = $this->userPrefsModel->getUserPrefs($userID);
            $this->assertSame(1, $preferences["Email.DigestEnabled"]);

            // A subscribe event should have been dispatched.
            $this->assertEventDispatched(
                $this->expectedResourceEvent(
                    SubscriptionChangeEvent::COLLECTION_NAME,
                    SubscriptionChangeEvent::ACTION_DIGEST_ENABLED,
                    []
                ),
                []
            );
        });
    }

    /**
     * Test a user is auto-subscribed when signing in.
     *
     * @return void
     */
    public function testAutoSubscribeSigninNoPreferences(): void
    {
        $user = $this->createUser();
        $this->runWithConfig(
            ["Garden.Digest.Enabled" => 1, "Garden.Digest.Autosubscribe.Enabled" => 1],
            function () use ($user) {
                // The user hasn't signed in, so the auto-subscribe setting should not have taken effect.
                $preferences = $this->userPrefsModel->getUserPrefs($user["userID"]);
                $this->assertSame(0, $preferences["Email.DigestEnabled"]);
                // The user should be autosubscribed after signing in.
                $this->bessy()->post("/entry/signin", ["Email" => $user["email"], "Password" => "testpassword"]);
                $updatedPreferences = $this->userPrefsModel->getUserPrefs($user["userID"]);
                $this->assertSame(3, $updatedPreferences["Email.DigestEnabled"]);
                // An auto-subscribe subscription change event should have been dispatched.
                $this->assertEventDispatched(
                    $this->expectedResourceEvent(
                        SubscriptionChangeEvent::COLLECTION_NAME,
                        SubscriptionChangeEvent::ACTION_DIGEST_AUTO_SUBSCRIBE,
                        []
                    ),
                    []
                );
                $this->clearDispatchedEvents();
            }
        );
    }

    /**
     * Test that the auto-subscribe setting does not take effect if the user has already opted in.
     *
     * @return void
     */
    public function testAutoSubscribeSigninAlreadyOptedIn(): void
    {
        $user = $this->createUser();
        $this->api()->patch("/notification-preferences/{$user["userID"]}", ["DigestEnabled" => ["email" => true]]);
        // A subscribe event should have been dispatched.
        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                SubscriptionChangeEvent::COLLECTION_NAME,
                SubscriptionChangeEvent::ACTION_DIGEST_ENABLED,
                []
            ),
            []
        );
        $this->clearDispatchedEvents();
        $this->runWithConfig(
            ["Garden.Digest.Enabled" => 1, "Garden.Digest.Autosubscribe.Enabled" => 1],
            function () use ($user) {
                // The user's preference should be set to true.
                $preferences = $this->userPrefsModel->getUserPrefs($user["userID"]);
                $this->assertSame(1, $preferences["Email.DigestEnabled"]);
                // The user's preference should not have changed.
                $this->bessy()->post("/entry/signin", ["Email" => $user["email"], "Password" => "testpassword"]);
                $updatedPreferences = $this->userPrefsModel->getUserPrefs($user["userID"]);
                $this->assertSame(1, $updatedPreferences["Email.DigestEnabled"]);
                // No events should have been dispatched.
                $this->assertEventNotDispatched([
                    "type" => SubscriptionChangeEvent::COLLECTION_NAME,
                    "action" => SubscriptionChangeEvent::ACTION_DIGEST_ENABLED,
                ]);
                $this->assertEventNotDispatched([
                    "type" => SubscriptionChangeEvent::COLLECTION_NAME,
                    "action" => SubscriptionChangeEvent::ACTION_DIGEST_AUTO_SUBSCRIBE,
                ]);
            }
        );
    }

    /**
     * Test that the auto-subscribe setting does not take effect if the user has already opted out.
     */
    public function testAutoSubscribeSigninAlreadyOptedOut(): void
    {
        $user = $this->createUser();
        $this->api()->patch("/notification-preferences/{$user["userID"]}", ["DigestEnabled" => ["email" => false]]);
        $this->runWithConfig(
            ["Garden.Digest.Enabled" => 1, "Garden.Digest.Autosubscribe.Enabled" => 1],
            function () use ($user) {
                // The user's preference should be set to false.
                $preferences = $this->userPrefsModel->getUserPrefs($user["userID"]);
                $this->assertSame(0, $preferences["Email.DigestEnabled"]);
                // The user's preference should not have changed.
                $this->bessy()->post("/entry/signin", ["Email" => $user["email"], "Password" => "testpassword"]);
                $updatedPreferences = $this->userPrefsModel->getUserPrefs($user["userID"]);
                $this->assertSame(0, $updatedPreferences["Email.DigestEnabled"]);

                // No events should have been dispatched.
                $this->assertEventNotDispatched([
                    "type" => SubscriptionChangeEvent::COLLECTION_NAME,
                    "action" => SubscriptionChangeEvent::ACTION_DIGEST_ENABLED,
                ]);
                $this->assertEventNotDispatched([
                    "type" => SubscriptionChangeEvent::COLLECTION_NAME,
                    "action" => SubscriptionChangeEvent::ACTION_DIGEST_AUTO_SUBSCRIBE,
                ]);
                $this->assertEventNotDispatched([
                    "type" => SubscriptionChangeEvent::COLLECTION_NAME,
                    "action" => SubscriptionChangeEvent::ACTION_DIGEST_DISABLED,
                ]);
            }
        );
    }
}
