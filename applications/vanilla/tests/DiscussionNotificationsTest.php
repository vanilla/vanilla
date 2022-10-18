<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum;

use VanillaTests\ExpectedNotification;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for discussion notifications.
 */
class DiscussionNotificationsTest extends \VanillaTests\SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;

    public static $addons = ["vanilla"];

    /**
     * Test that a notification is sent when a user is mentioned in a discussion.
     */
    public function testMentionNotification(): void
    {
        $user = $this->createUser();
        $discussion = $this->createDiscussion(["body" => "I'm mentioning @{$user["name"]}"]);
        $this->assertUserHasNotificationsLike($user, [
            new ExpectedNotification("DiscussionMention", ["mentioned you in", $discussion["name"]], "mention"),
        ]);
    }

    /**
     * Test that when a user is mentioned and following a category that they do not get duplicate notifications.
     */
    public function testNoDuplicateMentionAndPost(): void
    {
        $this->runWithConfig([\CategoryModel::CONF_CATEGORY_FOLLOWING => true], function () {
            $notifyUser = $this->createUser();
            $authorUser = $this->createUser();
            $notifyCategory = $this->createCategory();
            $this->setCategoryPreference($notifyUser, $notifyCategory, \CategoryModel::NOTIFICATION_DISCUSSIONS);

            $notifyDiscussion = $this->runWithUser(function () use ($notifyUser) {
                return $this->createDiscussion([
                    "body" => "I'm mentioning @{$notifyUser["name"]}",
                ]);
            }, $authorUser);

            $this->assertUserHasNotificationsLike($notifyUser, [
                new ExpectedNotification(
                    "DiscussionMention",
                    ["mentioned you in", $notifyDiscussion["name"]],
                    "mention, advanced"
                ),
            ]);

            // Now disable the mention preference
            \Gdn::userModel()->savePreference($notifyUser["userID"], "Popup.Mention", false);
            $this->clearUserNotifications($notifyUser);

            $notifyDiscussion = $this->runWithUser(function () use ($notifyUser) {
                return $this->createDiscussion([
                    "body" => "I'm mentioning @{$notifyUser["name"]}",
                ]);
            }, $authorUser);

            $this->assertUserHasNotificationsLike($notifyUser, [
                new ExpectedNotification(
                    "Discussion",
                    ["Started a new discussion", $notifyDiscussion["name"]],
                    "advanced"
                ),
            ]);
        });
    }
}
