<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum;

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

    /**
     * Test that a notification is sent when a user is mentioned in a discussion.
     */
    public function testMentionNotification(): void
    {
        $user = $this->createUser();
        $discussion = $this->createDiscussion(["body" => "I'm mentioning @{$user["name"]}"]);
        $this->assertUserHasNotificationsLike($user, [["mentioned you in", $discussion["name"]]]);
    }
}
