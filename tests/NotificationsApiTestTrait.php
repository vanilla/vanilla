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

        // Keep track of which actual notifications have been matched
        $matchedActual = array_fill(0, $actualCount, false);

        // For each expected notification, find a matching actual notification
        foreach ($expected as $expectedNotification) {
            $found = false;
            foreach ($actual as $i => $actualNotification) {
                // Skip notifications that have already been matched
                if ($matchedActual[$i]) {
                    continue;
                }

                try {
                    $expectedNotification->assertMatches($actualNotification);
                    $matchedActual[$i] = true;
                    $found = true;
                    break;
                } catch (\Exception $e) {
                    // Not a match, continue searching
                    continue;
                }
            }

            if (!$found) {
                $this->fail(
                    "Could not find a matching notification for: " .
                        json_encode($expectedNotification, JSON_PRETTY_PRINT) .
                        "\nAvailable notifications:\n" .
                        json_encode($actual, JSON_PRETTY_PRINT)
                );
            }
        }
    }

    /**
     * Assert that a user has certain notifications.
     *
     * @param int|array $userOrUserID
     * @param ExpectedNotification[] $expectedNotifications
     * @param bool $batchNotifications
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

        $emailActivities = $this->getUserEmails($userID);

        $this->assertCount(0, $emailActivities);
    }

    /**
     * Get all email notifications for a user.
     *
     * @param int $userID
     * @return array
     */
    public function getUserEmails(int $userID, ?string $status = null): array
    {
        if ($status === null) {
            $status = [\ActivityModel::SENT_OK, \ActivityModel::SENT_PENDING, \ActivityModel::SENT_FAIL];
        }

        $activityModel = \Gdn::getContainer()->get(\ActivityModel::class);
        // Get all email activities for the user
        $emailActivities = $activityModel
            ->getWhere([
                "Notified >" => 0, // Apply this to not filter to only items with in app notifications.
                "NotifyUserID" => $userID,
                "Emailed" => $status,
            ])
            ->resultArray();
        if (!empty($emailActivities)) {
            foreach ($emailActivities as &$emailActivity) {
                $emailActivity = $activityModel->normalizeNotificationRow($emailActivity);
            }
        }
        return $emailActivities;
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

        $emailActivities = $this->getUserEmails($userID, $status);

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

    /**
     * Get the number of notifications for a user.
     *
     * @param array|int $userOrUserID The user to check notifications for
     * @return int The number of notifications
     */
    public function getUserNotificationCount(array|int $userOrUserID): int
    {
        return $this->runWithUser(function () {
            $notificationResponse = $this->api()->get("/notifications");
            $this->assertEquals(200, $notificationResponse->getStatusCode());
            return count($notificationResponse->getBody());
        }, $userOrUserID);
    }
}
