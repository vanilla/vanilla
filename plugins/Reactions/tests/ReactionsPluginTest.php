<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Reactions;

use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the Reactions plugin.
 */
class ReactionsPluginTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use ReactionsTestTrait;

    public static $addons = ["dashboard", "vanilla", "reactions"];

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test reacting to a discussion through the /react/discussion/{reactionType} endpoint.
     */
    public function testDiscussionReaction(): void
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();

        $user = $this->createReactingUser();

        $this->getSession()->start($user["userID"]);
        $this->react("discussion", $discussion["discussionID"], "like");

        $retrievedDiscussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "reactions"])
            ->getBody();

        $likeReactions = $this->getPostReaction($retrievedDiscussion, "Like");

        $this->assertSame(1, $likeReactions["count"]);
    }

    /**
     * Test reacting to a comment through the /react/comment/{reactionType} endpoint.
     */
    public function testCommentReaction(): void
    {
        $this->createCategory();
        $this->createDiscussion();
        $comment = $this->createComment();

        $user = $this->createReactingUser();

        $this->getSession()->start($user["userID"]);
        $this->react("comment", $comment["commentID"], "like");

        $retrievedComment = $this->api()
            ->get("/comments/{$comment["commentID"]}", ["expand" => "reactions"])
            ->getBody();

        $likeReactions = $this->getPostReaction($retrievedComment, "Like");

        $this->assertSame(1, $likeReactions["count"]);
    }

    /**
     * Test that an error is thrown when reacting on a post in a category for which the user doesn't have the view permission.
     */
    public function testReactingInDisallowedCategory(): void
    {
        $reactingUser = $this->createReactingUser();

        $disallowedCategory = $this->createPermissionedCategory([], [\RoleModel::ADMIN_ID, \RoleModel::MOD_ID]);

        $discussion = $this->createDiscussion(["categoryID" => $disallowedCategory["categoryID"]]);

        $this->getSession()->start($reactingUser["userID"]);

        $this->expectExceptionCode(403);
        $this->react("discussion", $discussion["discussionID"], "like");
    }
}
