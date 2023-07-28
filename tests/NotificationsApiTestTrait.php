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

    private function assertNotificationsLike(array $expected, array $actual)
    {
        $expectedCount = count($expected);
        $actualCount = count($actual);
        TestCase::assertCount(
            $expectedCount,
            $actual,
            "Expected exactly $expectedCount notifications. Instead received $actualCount.\n" .
                json_encode($actual, JSON_PRETTY_PRINT)
        );
        foreach ($expected as $i => $expectedNotification) {
            $actualNotification = $actual[$i] ?? null;
            if ($actualNotification === null) {
                $this->fail("Expected notification at index $i to exist, but none was found.");
            }

            $expectedNotification->assertMatches($actualNotification);
        }
    }

    /**
     * Assert that a user has certain notifications.
     *
     * @param int|array $userOrUserID
     * @param ExpectedNotification[] $expectedNotifications
     */
    public function assertUserHasNotificationsLike($userOrUserID, array $expectedNotifications)
    {
        $this->runWithUser(function () use ($expectedNotifications) {
            $notificationResponse = $this->api()->get("/notifications");
            $this->assertEquals(200, $notificationResponse->getStatusCode());
            $result = $notificationResponse->getBody();
            $this->assertNotificationsLike($expectedNotifications, $result);
        }, $userOrUserID);
    }

    /**
     * Assert that a user has no email notifications.
     *
     * @param int|array $userOrUserID
     */
    public function assertUserHasNoEmails($userOrUserID)
    {
        $userID = is_array($userOrUserID) ? $userOrUserID["userID"] : $userOrUserID;
        $activityModel = \Gdn::getContainer()->get(\ActivityModel::class);
        $emailActivities = $activityModel
            ->getWhere([
                "Notified >" => 0, // Apply this to not filter to only items with in app notifications.
                "NotifyUserID" => $userID,
                "Emailed" => [\ActivityModel::SENT_OK, \ActivityModel::SENT_PENDING, \ActivityModel::SENT_FAIL],
            ])
            ->resultArray();
        $this->assertCount(0, $emailActivities);
    }

    /**
     * Assert that a user has certain email notifications with a specific status.
     *
     * @param int|array $userOrUserID
     * @param string $status One of ActivityModel::SENT_* statuses
     * @param ExpectedNotification[] $expectedNotifications
     * @return void
     * @throws \Garden\Container\ContainerException|\Garden\Container\NotFoundException
     */
    public function assertUserHasEmailsLike($userOrUserID, string $status, array $expectedNotifications)
    {
        $userID = is_array($userOrUserID) ? $userOrUserID["userID"] : $userOrUserID;
        $activityModel = \Gdn::getContainer()->get(\ActivityModel::class);
        $emailActivities = $activityModel
            ->getWhere([
                "Notified >" => 0, // Apply this to not filter to only items with in app notifications.
                "NotifyUserID" => $userID,
                "Emailed" => $status,
            ])
            ->resultArray();
        foreach ($emailActivities as &$emailActivity) {
            $emailActivity = $activityModel->normalizeNotificationRow($emailActivity);
        }
        $this->assertNotificationsLike($expectedNotifications, $emailActivities);
    }

    /**
     * Delete notification records for a user.
     *
     * @param $userOrUserID
     */
    public function clearUserNotifications($userOrUserID)
    {
        $userID = is_array($userOrUserID) ? $userOrUserID["userID"] : $userOrUserID;
        \Gdn::sql()->delete("Activity", [
            "NotifyUserID" => $userID,
        ]);
    }

    /**
     * Test get notification response and return the body.
     *
     * @param int $id
     * @return array
     */
    public function getNotification(int $id)
    {
        $getResponse = $this->api()->get("/notifications/{$id}");
        $this->assertEquals(200, $getResponse->getStatusCode());
        return $getResponse->getBody();
    }
}
