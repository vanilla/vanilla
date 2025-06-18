<?php

namespace APIv2;

use Vanilla\CurrentTimeStamp;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the /posts endpoint.
 */
class PostsTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("Comment");
        $this->resetTable("Discussion");
    }

    /**
     * Test that the /posts endpoint respects the role filter.
     *
     * @return void
     */
    public function testRoleFilter(): void
    {
        $adminDiscussion = $this->createDiscussion(["body" => "Admin Discussion"]);
        $adminComment = $this->createComment(["body" => "Admin Comment"]);

        $member = $this->createUser();

        $this->runWithUser(function () {
            $this->createDiscussion(["body" => "Member Discussion"]);
            $this->createComment(["body" => "Member Comment"]);
        }, $member);

        $memberDiscussion = $this->api()->get("/discussions", ["insertUserID" => $member["userID"]])[0];
        $memberComment = $this->api()->get("/comments", ["insertUserID" => $member["userID"]])[0];

        // Filtering by both roles gives all posts.
        $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::MEMBER_ID, \RoleModel::ADMIN_ID]])
            ->assertStatus(200)
            ->assertCount(4)
            ->assertJsonArrayContains(["body" => $adminDiscussion["body"]])
            ->assertJsonArrayContains(["body" => $adminComment["body"]])
            ->assertJsonArrayContains(["body" => $memberDiscussion["body"]])
            ->assertJsonArrayContains(["body" => $memberComment["body"]]);

        // Filtering by only the member role gives only the member's posts.
        $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::MEMBER_ID]])
            ->assertStatus(200)
            ->assertCount(2)
            ->assertJsonArrayContains(["body" => $memberDiscussion["body"]])
            ->assertJsonArrayContains(["body" => $memberComment["body"]]);

        // Filtering by only the admin role gives only the admin's posts.
        $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::ADMIN_ID]])
            ->assertStatus(200)
            ->assertCount(2)
            ->assertJsonArrayContains(["body" => $adminDiscussion["body"]])
            ->assertJsonArrayContains(["body" => $adminComment["body"]]);
    }

    /**
     * Test that the /posts endpoint respects the sort parameter.
     *
     * @return void
     */
    public function testScoreSort(): void
    {
        $lowScore = $this->createDiscussion(["score" => 0, "body" => "Low Score"]);
        $midScore = $this->createDiscussion(["score" => 50, "body" => "Mid Score"]);
        $topScore = $this->createComment(["score" => 100, "body" => "Top Score"]);

        $posts = $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::ADMIN_ID]])
            ->getBody();
        $this->assertSame($topScore["body"], $posts[0]["body"]);
        $this->assertSame($midScore["body"], $posts[1]["body"]);
        $this->assertSame($lowScore["body"], $posts[2]["body"]);

        // sort ascending
        $posts = $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::ADMIN_ID], "sort" => "-score"])
            ->getBody();
        $this->assertSame($lowScore["body"], $posts[0]["body"]);
        $this->assertSame($midScore["body"], $posts[1]["body"]);
        $this->assertSame($topScore["body"], $posts[2]["body"]);
    }

    /**
     * Test that the /posts endpoint respects the dateInserted sort parameter.
     *
     * @return void
     */
    public function testDateInsertedSort(): void
    {
        $newest = $this->createDiscussion(["body" => "Newest"]);

        CurrentTimeStamp::mockTime("2020-01-01 10:00:00");
        $oldest = $this->createDiscussion(["body" => "Oldest"]);

        CurrentTimeStamp::mockTime("now");
        $posts = $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::ADMIN_ID], "sort" => "dateInserted"])
            ->getBody();
        $this->assertSame($newest["body"], $posts[0]["body"]);
        $this->assertSame($oldest["body"], $posts[1]["body"]);

        // sort ascending
        $posts = $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::ADMIN_ID], "sort" => "-dateInserted"])
            ->getBody();
        $this->assertSame($oldest["body"], $posts[0]["body"]);
        $this->assertSame($newest["body"], $posts[1]["body"]);
    }

    /**
     * Test that the /posts endpoint respects the dateLastComment sort parameter.
     *
     * @return void
     */
    public function testDateLastCommentSort(): void
    {
        CurrentTimeStamp::mockTime("2022-01-01 10:00:00");
        $newer = $this->createDiscussion(["body" => "Newer"]);

        CurrentTimeStamp::mockTime("2020-01-01 10:00:01");
        $older = $this->createDiscussion(["body" => "Older"]);

        CurrentTimeStamp::mockTime("now");
        $comment = $this->createComment(["body" => "Older Comment"]);

        $posts = $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::ADMIN_ID], "sort" => "dateLastComment"])
            ->getBody();

        // The older discussion should be first because it has a recent comment.
        $this->assertSame($older["body"], $posts[0]["body"]);
        $this->assertSame($comment["body"], $posts[1]["body"]);
        $this->assertSame($newer["body"], $posts[2]["body"]);

        // sort ascending
        $posts = $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::ADMIN_ID], "sort" => "-dateLastComment"])
            ->getBody();
        $this->assertSame($newer["body"], $posts[0]["body"]);
        $this->assertSame($older["body"], $posts[1]["body"]);
        $this->assertSame($comment["body"], $posts[2]["body"]);
    }

    /**
     * Test that the /posts only shows posts from categories the user is allowed to see.
     *
     * @return void
     */
    public function testCategoryPermissions(): void
    {
        $this->createDiscussion(["body" => "visibleDiscussion"]);
        $this->createComment(["body" => "visibleComment"]);

        $this->createPermissionedCategory(
            ["name" => "Admin Members Only", "parentCategoryID" => -1],
            [\RoleModel::ADMIN_ID]
        );

        $this->createDiscussion(["body" => "hiddenDiscussion"]);
        $this->createComment(["body" => "hiddenComment"]);

        // An admin should be able to see all posts.
        $this->api()
            ->get("/posts", ["roleIDs" => [\RoleModel::ADMIN_ID]])
            ->assertSuccess()
            ->assertCount(4);

        // A member should only be able to see posts in categories they have access to.
        $member = $this->createUser();
        $this->runWithUser(function () {
            $this->api()
                ->get("/posts", ["roleIDs" => [\RoleModel::ADMIN_ID]])
                ->assertSuccess()
                ->assertCount(2)
                ->assertJsonArrayContains(["body" => "visibleDiscussion"])
                ->assertJsonArrayContains(["body" => "visibleComment"]);
        }, $member);
    }
}
