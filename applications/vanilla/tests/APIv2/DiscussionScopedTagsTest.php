<?php

namespace APIv2;

use Garden\Web\Exception\ClientException;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for scoped tags validation via the /discussions api endpoints.
 */
class DiscussionScopedTagsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        // Enable tagging and scoped tagging
        \Gdn::config()->saveToConfig(
            ["Tagging.Discussions.Enabled" => true, "Tagging.ScopedTagging.Enabled" => true],
            options: false
        );
    }

    /**
     * Test posting a discussion with valid scoped tags.
     */
    public function testPostDiscussionWithValidScopedTags(): void
    {
        $category1 = $this->createCategory();

        $globalTag = $this->createTag();

        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
            "tagIDs" => [$globalTag["tagID"], $scopedTag1["tagID"]],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();

        $this->assertContains($globalTag["tagID"], $discussion["tagIDs"]);
        $this->assertContains($scopedTag1["tagID"], $discussion["tagIDs"]);
    }

    /**
     * Test posting a discussion with invalid scoped tags (tag scoped to different category).
     */
    public function testPostDiscussionWithInvalidScopedTags(): void
    {
        $category1 = $this->createCategory();
        $category2 = $this->createCategory();

        $scopedTag2 = $this->createScopedTag(categoryIDs: [$category2["categoryID"]]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("You do not have permission to use the following tags in this category");

        $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
            "tagIDs" => [$scopedTag2["tagID"]], // Tag scoped to category2
        ]);
    }

    /**
     * Test patching a discussion with valid scoped tags.
     */
    public function testPatchDiscussionWithValidScopedTags(): void
    {
        $category1 = $this->createCategory();

        $globalTag = $this->createTag();

        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
        ]);

        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "tagIDs" => [$globalTag["tagID"], $scopedTag1["tagID"]],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();

        $this->assertContains($globalTag["tagID"], $discussion["tagIDs"]);
        $this->assertContains($scopedTag1["tagID"], $discussion["tagIDs"]);
    }

    /**
     * Test patching a discussion with invalid scoped tags.
     */
    public function testPatchDiscussionWithInvalidScopedTags(): void
    {
        $category1 = $this->createCategory();
        $category2 = $this->createCategory();

        $scopedTag2 = $this->createScopedTag(categoryIDs: [$category2["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
        ]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("You do not have permission to use the following tags in this category");

        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "tagIDs" => [$scopedTag2["tagID"]], // Tag scoped to category2
        ]);
    }

    /**
     * Test adding tags to a discussion with valid scoped tags.
     */
    public function testPostTagsWithValidScopedTags(): void
    {
        $category1 = $this->createCategory();

        $globalTag = $this->createTag();

        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
        ]);

        $this->api()
            ->post("/discussions/{$discussion["discussionID"]}/tags", [
                "tagIDs" => [$globalTag["tagID"], $scopedTag1["tagID"]],
            ])
            ->assertSuccess();

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();

        $this->assertContains($globalTag["tagID"], $discussion["tagIDs"]);
        $this->assertContains($scopedTag1["tagID"], $discussion["tagIDs"]);
    }

    /**
     * Test adding tags to a discussion with invalid scoped tags.
     */
    public function testPostTagsWithInvalidScopedTags(): void
    {
        $category1 = $this->createCategory();
        $category2 = $this->createCategory();

        $scopedTag2 = $this->createScopedTag(categoryIDs: [$category2["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
        ]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("You do not have permission to use the following tags in this category");

        $this->api()->post("/discussions/{$discussion["discussionID"]}/tags", [
            "tagIDs" => [$scopedTag2["tagID"]], // Tag scoped to category2
        ]);
    }

    /**
     * Test setting tags on a discussion with valid scoped tags.
     */
    public function testPutTagsWithValidScopedTags(): void
    {
        $category1 = $this->createCategory();

        $globalTag = $this->createTag();

        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
        ]);

        $response = $this->api()->put("/discussions/{$discussion["discussionID"]}/tags", [
            "tagIDs" => [$globalTag["tagID"], $scopedTag1["tagID"]],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();

        $this->assertContains($globalTag["tagID"], $discussion["tagIDs"]);
        $this->assertContains($scopedTag1["tagID"], $discussion["tagIDs"]);
    }

    /**
     * Test setting tags on a discussion with invalid scoped tags.
     */
    public function testPutTagsWithInvalidScopedTags(): void
    {
        $category1 = $this->createCategory();
        $category2 = $this->createCategory();

        $scopedTag2 = $this->createScopedTag(categoryIDs: [$category2["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
        ]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("You do not have permission to use the following tags in this category");

        $this->api()->put("/discussions/{$discussion["discussionID"]}/tags", [
            "tagIDs" => [$scopedTag2["tagID"]], // Tag scoped to category2
        ]);
    }

    /**
     * Test that already assigned tags don't trigger validation errors.
     */
    public function testAlreadyAssignedTagsDontTriggerValidation(): void
    {
        $category1 = $this->createCategory();

        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
            "tagIDs" => [$scopedTag1["tagID"]],
        ]);

        // Try to add the same tag again - should not throw an error
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "tagIDs" => [$scopedTag1["tagID"]], // Same tag already assigned
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();

        $this->assertContains($scopedTag1["tagID"], $discussion["tagIDs"]);
    }

    /**
     * Test that scoped tagging validation is skipped when feature is disabled.
     */
    public function testScopedTaggingValidationSkippedWhenDisabled(): void
    {
        \Gdn::config()->saveToConfig("Tagging.ScopedTagging.Enabled", false, false);

        $category1 = $this->createCategory();
        $category2 = $this->createCategory();

        $scopedTag2 = $this->createScopedTag(categoryIDs: [$category2["categoryID"]]);

        // Should work even with tags scoped to different category
        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
            "tagIDs" => [$scopedTag2["tagID"]], // Tag scoped to category2
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();

        $this->assertContains($scopedTag2["tagID"], $discussion["tagIDs"]);
    }

    /**
     * Test that empty tag arrays don't trigger validation.
     */
    public function testEmptyTagArraysDontTriggerValidation(): void
    {
        $this->expectNotToPerformAssertions();
        $category1 = $this->createCategory();

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
        ]);

        // Should not throw an error with empty tag arrays
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "tagIDs" => [],
        ]);

        $this->api()->post("/discussions/{$discussion["discussionID"]}/tags", [
            "tagIDs" => [],
        ]);

        $this->api()->put("/discussions/{$discussion["discussionID"]}/tags", [
            "tagIDs" => [],
        ]);
    }

    /**
     * Test validation with mixed valid and invalid tags.
     */
    public function testMixedValidAndInvalidTags(): void
    {
        $category1 = $this->createCategory();
        $category2 = $this->createCategory();

        $globalTag = $this->createTag();

        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);

        $scopedTag2 = $this->createScopedTag(categoryIDs: [$category2["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
        ]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("You do not have permission to use the following tags in this category");

        // Mix of valid and invalid tags should still throw an error
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "tagIDs" => [
                $globalTag["tagID"], // Valid
                $scopedTag1["tagID"], // Valid
                $scopedTag2["tagID"], // Invalid - scoped to category2
            ],
        ]);
    }

    /**
     * Test validation when discussion is moved to a different category.
     */
    public function testValidationWhenDiscussionMovedToDifferentCategory(): void
    {
        $category1 = $this->createCategory(["parentCategoryID" => null]);
        $category2 = $this->createCategory(["parentCategoryID" => null]);

        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);
        $scopedTag2 = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);

        $discussion = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
            "tagIDs" => [$scopedTag1["tagID"]], // Valid for category1
        ]);

        // Move discussion to category2
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "categoryID" => $category2["categoryID"],
        ]);

        // The existing tag should still be there (no validation on move)
        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->getBody();

        $this->assertContains($scopedTag1["tagID"], $discussion["tagIDs"]);

        // But trying to add another tag scoped to category1 should fail
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("You do not have permission to use the following tags in this category");

        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "tagIDs" => [$scopedTag2["tagID"]], // Try to add same tag again
        ]);
    }
}
