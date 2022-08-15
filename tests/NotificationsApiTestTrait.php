<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\TestCase;

/**
 * Utilities for testing notifications.
 */
trait NotificationsApiTestTrait
{
    /**
     * Assert that a user has no notifications.
     *
     * @param $userOrUserID
     */
    public function assertUserHasNoNotifications($userOrUserID)
    {
        $this->runWithUser(function () {
            $notificationResponse = $this->api()->get("/notifications");
            $this->assertEquals(200, $notificationResponse->getStatusCode());
            $this->assertEmpty($notificationResponse->getBody(), "Expected user to not to have any notifications.");
        }, $userOrUserID);
    }

    /**
     * Assert that a user has certain notifications.
     *
     * @param int|array
     * @param string[][] $expectedNotificationsFragments
     */
    public function assertUserHasNotificationsLike($userOrUserID, array $expectedNotificationsFragments)
    {
        $this->runWithUser(function () use ($expectedNotificationsFragments) {
            $notificationResponse = $this->api()->get("/notifications");
            $this->assertEquals(200, $notificationResponse->getStatusCode());
            $result = $notificationResponse->getBody();
            $expectedCount = count($expectedNotificationsFragments);
            $actualCount = count($result);
            TestCase::assertCount(
                $expectedCount,
                $result,
                "Expected exactly $expectedCount notifications. Instead received $actualCount.\n" .
                    json_encode($result, JSON_PRETTY_PRINT)
            );
            foreach ($expectedNotificationsFragments as $i => $bodyFragments) {
                $expectedNotification = $result[$i] ?? null;
                if ($expectedNotification === null) {
                    $this->fail("Expected notification at index $i to exist, but none was found.");
                }

                $notificationBody = $expectedNotification["body"];
                foreach ($bodyFragments as $bodyFragment) {
                    $this->assertStringContainsString(
                        $bodyFragment,
                        $notificationBody,
                        "Notification was missing a fragment."
                    );
                }
            }
        }, $userOrUserID);
    }
}
