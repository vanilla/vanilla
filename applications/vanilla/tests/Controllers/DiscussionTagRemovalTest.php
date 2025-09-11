<?php
/**
 * @author Richard Flynn <richardflynn@gmail.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Tests\Controllers;

use CategoryModel;
use TagModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for removing tags when editing discussions via the legacy controller.
 */
class DiscussionTagRemovalTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    /** @var TagModel */
    private $tagModel;

    /** @var CategoryModel */
    private $categoryModel;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useLegacyLayouts();
        $this->setConfig("Tagging.Discussions.Enabled", true);

        $this->tagModel = self::container()->get(TagModel::class);
        $this->categoryModel = self::container()->get(CategoryModel::class);
    }

    /**
     * Test removing all tags when editing a discussion.
     *
     * This tests the core functionality: when a user edits a discussion and
     * provides an empty Tags field, all existing tags should be removed.
     */
    public function testRemoveAllTagsWhenEditingDiscussion(): void
    {
        // Create tags first
        $tag1 = $this->createTag(["name" => "TestTag1"]);
        $tag2 = $this->createTag(["name" => "TestTag2"]);

        // Create a discussion with tags via the legacy controller
        $discussionData = [
            "Name" => "Test Discussion with Tags",
            "Body" => "This discussion has tags that we want to remove",
            "Format" => "markdown",
            "CategoryID" => 1,
            "Tags" => $tag1["tagID"] . "," . $tag2["tagID"],
        ];

        $result = $this->bessy()->post("/post/discussion", $discussionData);
        $discussionID = $result->data("Discussion.DiscussionID");
        $this->assertNotNull($discussionID, "Discussion should have been created");

        // Verify tags were added
        $discussionTags = $this->tagModel->getDiscussionTags($discussionID, false);
        $this->assertCount(2, $discussionTags, "Discussion should have 2 tags initially");

        // Now edit the discussion and remove all tags by providing empty Tags field
        $editData = [
            "Name" => "Test Discussion with Tags Removed",
            "Body" => "This discussion no longer has tags",
            "Format" => "markdown",
            "CategoryID" => 1,
            "Tags" => "", // Empty tags field should remove all tags
        ];

        $this->bessy()->post("/post/editdiscussion/{$discussionID}", $editData);

        // Verify all tags were removed
        $discussionTagsAfterEdit = $this->tagModel->getDiscussionTags($discussionID, false);
        $this->assertCount(
            0,
            $discussionTagsAfterEdit,
            "All tags should have been removed when editing with empty Tags field"
        );
    }

    /**
     * Test that tags are preserved when moving a discussion to a different category
     * without specifying tags in the edit form.
     *
     * This ensures the original behavior still works: when moving discussions
     * between categories, if no tags are specified, the existing tags should be preserved.
     */
    public function testPreserveTagsWhenMovingDiscussionToNewCategory(): void
    {
        // Create additional category
        $newCategory = $this->createCategory(["name" => "New Category"]);

        // Create tags
        $tag1 = $this->createTag(["name" => "PreserveTag1"]);
        $tag2 = $this->createTag(["name" => "PreserveTag2"]);

        // Create a discussion with tags
        $discussionData = [
            "Name" => "Discussion to Move",
            "Body" => "This discussion will be moved but keep its tags",
            "Format" => "markdown",
            "CategoryID" => 1,
            "Tags" => $tag1["tagID"] . "," . $tag2["tagID"],
        ];

        $result = $this->bessy()->post("/post/discussion", $discussionData);
        $discussionID = $result->data("Discussion.DiscussionID");

        // Verify initial tags
        $discussionTags = $this->tagModel->getDiscussionTags($discussionID, false);
        $this->assertCount(2, $discussionTags, "Discussion should have 2 tags initially");
        $originalTagIDs = array_column($discussionTags, "TagID");

        // Move the discussion to a new category WITHOUT specifying tags
        $editData = [
            "Name" => "Discussion to Move",
            "Body" => "This discussion will be moved but keep its tags",
            "Format" => "markdown",
            "CategoryID" => $newCategory["categoryID"],
            // Note: NOT including Tags field - this should preserve existing tags
        ];

        $this->bessy()->post("/post/editdiscussion/{$discussionID}", $editData);

        // Verify tags were preserved when moving categories
        $discussionTagsAfterMove = $this->tagModel->getDiscussionTags($discussionID, false);
        $this->assertCount(
            2,
            $discussionTagsAfterMove,
            "Tags should be preserved when moving discussion to new category"
        );

        $preservedTagIDs = array_column($discussionTagsAfterMove, "TagID");
        $this->assertEquals(
            sort($originalTagIDs),
            sort($preservedTagIDs),
            "The same tags should be preserved when moving discussion to new category"
        );
    }

    /**
     * Test that the category ID comparison fix works with string vs integer category IDs.
     *
     * This tests the fix for the category comparison logic that now properly
     * compares integer values rather than potentially comparing string vs integer.
     */
    public function testCategoryIDComparisonWithStringAndIntegerValues(): void
    {
        // Create additional category
        $newCategory = $this->createCategory(["name" => "String vs Int Category"]);

        // Create a tag
        $tag = $this->createTag(["name" => "ComparisonTag"]);

        // Create a discussion with tags
        $discussionData = [
            "Name" => "Category Comparison Test",
            "Body" => "Testing category ID comparison",
            "Format" => "markdown",
            "CategoryID" => 1, // Integer
            "Tags" => (string) $tag["tagID"],
        ];

        $result = $this->bessy()->post("/post/discussion", $discussionData);
        $discussionID = $result->data("Discussion.DiscussionID");

        // Verify initial tags
        $discussionTags = $this->tagModel->getDiscussionTags($discussionID, false);
        $this->assertCount(1, $discussionTags, "Discussion should have 1 tag initially");

        // Move the discussion using a string category ID (simulating potential string/int mismatch)
        $editData = [
            "Name" => "Category Comparison Test",
            "Body" => "Testing category ID comparison",
            "Format" => "markdown",
            "CategoryID" => (string) $newCategory["categoryID"], // String representation
            // Not including Tags - should preserve tags when moving categories
        ];

        $this->bessy()->post("/post/editdiscussion/{$discussionID}", $editData);

        // Verify tags were preserved (confirming the category comparison works correctly)
        $discussionTagsAfterMove = $this->tagModel->getDiscussionTags($discussionID, false);
        $this->assertCount(
            1,
            $discussionTagsAfterMove,
            "Tags should be preserved when moving categories with proper int comparison"
        );
    }

    /**
     * Test that the new functionality works with partial tag updates.
     *
     * This verifies that users can update tags (remove some, keep others)
     * when editing discussions.
     */
    public function testPartialTagUpdateWhenEditingDiscussion(): void
    {
        // Create tags
        $tag1 = $this->createTag(["name" => "KeepTag"]);
        $tag2 = $this->createTag(["name" => "RemoveTag"]);
        $tag3 = $this->createTag(["name" => "AddTag"]);

        // Create a discussion with two tags
        $discussionData = [
            "Name" => "Partial Tag Update Test",
            "Body" => "Testing partial tag updates",
            "Format" => "markdown",
            "CategoryID" => 1,
            "Tags" => $tag1["tagID"] . "," . $tag2["tagID"],
        ];

        $result = $this->bessy()->post("/post/discussion", $discussionData);
        $discussionID = $result->data("Discussion.DiscussionID");

        // Verify initial tags
        $discussionTags = $this->tagModel->getDiscussionTags($discussionID, false);
        $this->assertCount(2, $discussionTags, "Discussion should have 2 tags initially");

        // Edit the discussion and update tags: keep tag1, remove tag2, add tag3
        $editData = [
            "Name" => "Partial Tag Update Test - Updated",
            "Body" => "Testing partial tag updates - updated",
            "Format" => "markdown",
            "CategoryID" => 1,
            "Tags" => $tag1["tagID"] . "," . $tag3["tagID"], // Keep tag1, add tag3, remove tag2
        ];

        $this->bessy()->post("/post/editdiscussion/{$discussionID}", $editData);

        // Verify tags were updated correctly
        $discussionTagsAfterEdit = $this->tagModel->getDiscussionTags($discussionID, false);
        $this->assertCount(2, $discussionTagsAfterEdit, "Discussion should have 2 tags after partial update");

        $finalTagIDs = array_column($discussionTagsAfterEdit, "TagID");
        $this->assertContains($tag1["tagID"], $finalTagIDs, "Tag1 should be kept");
        $this->assertContains($tag3["tagID"], $finalTagIDs, "Tag3 should be added");
        $this->assertNotContains($tag2["tagID"], $finalTagIDs, "Tag2 should be removed");
    }
}
