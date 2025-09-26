<?php

namespace APIv2;

use Garden\Web\Exception\ForbiddenException;
use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for adding tags via the /discussions api endpoints.
 */
class DiscussionTagsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->container()->get(ConfigurationInterface::class);
        $config->set("Tagging.Discussions.Enabled", true);
        $adminID = \RoleModel::ADMIN_ID;
        $this->api()->patch("/roles/{$adminID}", [
            "permissions" => [
                [
                    "type" => "global",
                    "permissions" => [
                        "tags.add" => true,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test posting a discussion with an existing tag.
     *
     * @return void
     */
    public function testPostDiscussionWithExistingTag(): void
    {
        $tag = $this->api()->post("/tags", [
            "name" => __FUNCTION__ . "existing",
            "urlCode" => strtolower(__FUNCTION__ . "existing"),
        ]);

        $discussion = $this->createDiscussion(["tagIDs" => [$tag["tagID"]]]);

        $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->assertJsonObjectLike(["tagIDs" => [$tag["tagID"]]]);
    }

    /**
     * Test posting a discussion with a new tag.
     *
     * @return void
     */
    public function testPostDiscussionWithNewTag(): void
    {
        $discussion = $this->createDiscussion(["newTagNames" => [__FUNCTION__ . "new"]]);
        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();
        $this->assertCount(1, $discussion["tagIDs"]);
        $this->api()
            ->get("/tags/{$discussion["tagIDs"][0]}")
            ->assertJsonObjectLike(["name" => __FUNCTION__ . "new"]);
    }

    /**
     * Test posting a discussion with both new and existing tags.
     *
     * @return void
     */
    public function testPostDiscussionWithBothNewAndExistingTags(): void
    {
        $existingTag = $this->api()->post("/tags", [
            "name" => __FUNCTION__ . "new",
            "urlCode" => strtolower(__FUNCTION__ . "existing"),
        ]);
        $discussion = $this->createDiscussion([
            "tagIDs" => [$existingTag["tagID"]],
            "newTagNames" => [__FUNCTION__ . "new"],
        ]);
        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();
        $this->assertCount(2, $discussion["tagIDs"]);
        $this->assertContains($existingTag["tagID"], $discussion["tagIDs"]);
        $this->api()
            ->get("/tags/{$discussion["tagIDs"][1]}")
            ->assertJsonObjectLike(["name" => __FUNCTION__ . "new"]);
    }

    /**
     * Test patching a discussion with an existing tag.
     *
     * @return void
     */
    public function testPatchDiscussionWithExistingTag(): void
    {
        $tag = $this->api()->post("/tags", [
            "name" => __FUNCTION__ . "new",
            "urlCode" => strtolower(__FUNCTION__ . "existing"),
        ]);
        $discussion = $this->createDiscussion();
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", ["tagIDs" => [$tag["tagID"]]]);
        $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->assertJsonObjectLike(["tagIDs" => [$tag["tagID"]]]);
    }

    /**
     * Test patching a discussion with a new tag.
     *
     * @return void
     */
    public function testPatchDiscussionWithNewTag(): void
    {
        $discussion = $this->createDiscussion();
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", ["newTagNames" => [__FUNCTION__ . "new"]]);
        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();
        $this->assertCount(1, $discussion["tagIDs"]);
        $this->api()
            ->get("/tags/{$discussion["tagIDs"][0]}")
            ->assertJsonObjectLike(["name" => __FUNCTION__ . "new"]);
    }

    /**
     * Test patching a discussion with both new and existing tags.
     *
     * @return void
     */
    public function testPatchDiscussionWithBothNewAndExistingTags(): void
    {
        $existingTag = $this->api()->post("/tags", [
            "name" => __FUNCTION__ . "new",
            "urlCode" => strtolower(__FUNCTION__ . "existing"),
        ]);
        $discussion = $this->createDiscussion();
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "tagIDs" => [$existingTag["tagID"]],
            "newTagNames" => [__FUNCTION__ . "new"],
        ]);
        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();
        $this->assertCount(2, $discussion["tagIDs"]);
        $this->assertContains($existingTag["tagID"], $discussion["tagIDs"]);
        $this->api()
            ->get("/tags/{$discussion["tagIDs"][1]}")
            ->assertJsonObjectLike(["name" => __FUNCTION__ . "new"]);
    }

    /**
     * Test that an error is thrown when trying to add a new tag without the proper permission.
     *
     * @return void
     */
    public function testNewTagWithoutTagsAdd(): void
    {
        $this->runWithPermissions(
            function () {
                $this->expectException(ForbiddenException::class);
                $this->expectExceptionMessage("Permission Problem");
                $this->createDiscussion(["newTagNames" => [__FUNCTION__ . "new"]]);
            },
            ["tags.add" => false],
            [
                "type" => "category",
                "id" => -1,
                "permissions" => ["discussions.add" => true],
            ]
        );
    }

    /**
     * Test that patching a discussion with an empty newTagNames array does not throw an error.
     *
     * @return void
     */
    public function testWithEmptyNewTagNames(): void
    {
        $this->runWithPermissions(
            function () {
                // No error should be thrown when sending an empty newTagNames array.
                $discussion = $this->createDiscussion();
                $this->api()
                    ->patch("/discussions/{$discussion["discussionID"]}", [
                        "newTagNames" => [],
                    ])
                    ->assertSuccess();
            },
            ["tags.add" => false],
            [
                "type" => "category",
                "id" => -1,
                "permissions" => ["discussions.add" => true],
            ]
        );
    }

    /**
     * Test creating a discussion with a new tag when the tagging feature is disabled.
     *
     * @return void
     */
    public function testPostNewTagsDisabled(): void
    {
        $this->container()
            ->get(ConfigurationInterface::class)
            ->set("Tagging.Discussions.Enabled", false);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Tagging is not enabled.");
        $this->createDiscussion(["newTagNames" => [__FUNCTION__ . "new"]]);
    }

    /**
     * Test creating a discussion with an existing tag when the tagging feature is disabled.
     *
     * @return void
     */
    public function testPostExistingTagsDisabled(): void
    {
        $this->container()
            ->get(ConfigurationInterface::class)
            ->set("Tagging.Discussions.Enabled", false);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Tagging is not enabled.");
        $this->createDiscussion(["tagIDs" => [9999]]);
    }

    /**
     * Test patching a discussion with a new tag when the tagging feature is disabled.
     *
     * @return void
     */
    public function testPatchNewTagsDisabled(): void
    {
        $this->container()
            ->get(ConfigurationInterface::class)
            ->set("Tagging.Discussions.Enabled", false);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Tagging is not enabled.");
        $discussion = $this->createDiscussion();
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "newTagNames" => [__FUNCTION__ . "new"],
        ]);
    }

    /**
     * Test patching a discussion with an existing tag when the tagging feature is disabled.
     *
     * @return void
     */
    public function testPatchExistingTagsDisabled(): void
    {
        $this->container()
            ->get(ConfigurationInterface::class)
            ->set("Tagging.Discussions.Enabled", false);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Tagging is not enabled.");
        $discussion = $this->createDiscussion();
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "tagIDs" => [9999],
        ]);
    }
}
