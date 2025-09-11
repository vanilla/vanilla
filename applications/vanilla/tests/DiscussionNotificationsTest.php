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
     * Test that a discussion post with the "publishedSilently" flag set to true does not send a notification.
     *
     * @return void
     */
    public function testPublishingSilently(): void
    {
        $user = $this->createUser();
        $this->clearUserNotifications($user);
        $discussion = $this->createDiscussion([
            "body" => "I'm mentioning @{$user["name"]}",
            "publishedSilently" => true,
        ]);
        $this->assertUserHasNoNotifications($user);
    }

    /**
     * Test that silent posting is ignored when a user without the "publishedSilently" permission sets the flag
     * while posting a discussion.
     */
    public function testSilentPostingIgnoredForUserWithoutPermission(): void
    {
        $notificationUser = $this->createUser();
        $user = $this->createUser();
        $this->clearUserNotifications($user);
        $discussion = $this->runWithUser(function () use ($user, $notificationUser) {
            return $this->createDiscussion([
                "body" => "I'm mentioning @{$notificationUser["name"]}",
                "publishedSilently" => true,
            ]);
        }, $user);
        $this->assertUserHasNotificationsLike($notificationUser, [
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
            $this->setCategoryPreference($notifyUser, $notifyCategory, [
                "preferences.followed" => true,
                "preferences.popup.posts" => true,
            ]);

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
