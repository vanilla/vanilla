<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the tag model.
 */
class TagModelTest extends TestCase {

    use SiteTestTrait;
    use ModelTestTrait;
    use TestCategoryModelTrait;

    /** @var \TagModel */
    private $tagModel;

    /** @var \DiscussionModel */
    private $discussionModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        $this->setupSiteTestTrait();
        $this->discussionModel = self::container()->get(\DiscussionModel::class);
        $this->categoryModel = self::container()->get(\CategoryModel::class);
        $this->tagModel = self::container()->get(\TagModel::class);
        $this->tagModel->SQL->truncate('Tag');
    }

    /**
     * Test getting tagIDs by their tag name.
     */
    public function testGetTagIDsByName() {
        $tag1 = $this->tagModel->save([
            'Name' => 'Test1',
            'FullName' => 'Test 1 Full',
            'Type' => 'Status',
        ]);

        $tag2 = $this->tagModel->save([
            'Name' => 'Test2',
            'FullName' => 'Test 2 Full',
            'Type' => 'Status',
        ]);

        $this->assertIDsEqual([$tag1, $tag2], $this->tagModel->getTagIDsByName(['Test1', 'Test2']));
    }

    /**
     * Test persistence of tag on a moved discussion.
     */
    public function testSaveMovedDiscussion() {
        $categories = $this->insertCategories(2);
        $taggedDiscussionID = $this->discussionModel->save([
            'Name' => "TagTest",
            'CategoryID' => $categories[0]['CategoryID'],
            'Body' => "TagTest",
            'Format' => 'Text',
            'DateInserted' => TestDate::mySqlDate(),
            'Tags' => 'xxx'
        ]);

        $this->api()->patch('/discussions/'.$taggedDiscussionID, ['CategoryID' => $categories[1]['CategoryID']]);
        $tagInfo = $this->tagModel->getDiscussionTags($taggedDiscussionID);
        $tagName = $tagInfo[''][0]['Name'];
        $this->assertSame('xxx', $tagName);
    }

    /**
     * Test validateTagReference.
     */
    public function testValidateTagReference() {
        // A tag reference that should pass validation (field names are correct and the data is of the correct type).
        $tagSet = ["tagIDs" => [1, 2, 3], "urlcodes" => ["one", "two", "three"]];
        $afterValidation = $this->tagModel->validateTagReference($tagSet);
        $this->assertEquals($tagSet, $afterValidation);

        // A tag reference that should not pass validation (field names have wrong casing).
        $this->expectExceptionMessage("tagids is not a valid field. Fields must be one of: tagIDs, urlcodes.");
        $badTagSet = ["tagids" => [1, 2, 3], "urlcodes" => ["one", "two", "three"]];
        $this->tagModel->validateTagReference($badTagSet);
    }

    /**
     * Test getTagsFromReference.
     */
    public function testGetTagsFromReference() {
        // Make the tags.
        $tag1 = $this->tagModel->save([
            'Name' => 'tag1',
            'FullName' => 'Tag 1 Full'
        ]);

        $tag2 = $this->tagModel->save([
            'Name' => 'tag2',
            'FullName' => 'Tag 2 Full'
        ]);

        // Create a valid tag reference.
        $tagReference = ["urlcodes" => ["tag1"], "tagIDs" => [$tag2]];

        // We should get the tags back.
        $tags = $this->tagModel->getTagsFromReferences($tagReference);

        //Make sure that we actually do.
        $this->assertCount(2, $tags);
        $tagIDs = array_column($tags, "TagID");
        $this->assertContains($tag1, $tagIDs);
        $this->assertContains($tag2, $tagIDs);
    }

    /**
     * Test normalizing tag input and output.
     */
    public function testNormalizeInputOutput() {
        $tag = ["urlcode" => 'tag', "name" => "tag"];

        // This tag should be saved with the keys "Name" and "FullName".
        $normalizedIn = $this->tagModel->normalizeInput([$tag]);
        $this->assertEquals([["Name" => 'tag', "FullName" => "tag"]], $normalizedIn);

        // This tag should be returned with the keys as they were originally.
        $normalizedOut = $this->tagModel->normalizeOutput($normalizedIn);
        $this->assertEquals([["urlcode" => 'tag', "name" => "tag"]], $normalizedOut);
    }

    /**
     * Test setting tags on a discussion.
     */
    public function testPutDiscussionTags() {
        $category = $this->insertCategories(1)[0];
        $tag1 = $this->tagModel->save([
            'Name' => 'Put1',
            'FullName' => 'Put 1 Full'
        ]);
        $tag2 = $this->tagModel->save([
            'Name' => 'Put2',
            'FullName' => 'Put 2 Full'
        ]);
        $tag3 = $this->tagModel->save([
            'Name' => 'Put3',
            'FullName' => 'Put 3 Full'
        ]);

        // Set the tags.
        $putTagsDiscussionID = $this->discussionModel->save([
            'Name' => "PutTest",
            'CategoryID' => $category['CategoryID'],
            'Body' => "PutTest",
            'Format' => 'Text',
            'DateInserted' => TestDate::mySqlDate(),
        ]);

        // This should set the first two tags on the discussion.
        $tagFrags = $this->api()->put("/discussions/{$putTagsDiscussionID}/tags", ["urlcodes" => ["Put1", "Put2"]])->getBody();
        $this->assertCount(2, $tagFrags);
        $tagFragNames = array_column($tagFrags, "urlcode");
        $this->assertContains("Put1", $tagFragNames);
        $this->assertContains("Put2", $tagFragNames);

        // When we set tags 2 and 3 on the discussion, they should be the only ones associated with it (tag1 should go away).
        $tagFrags2 = $this->api()->put("/discussions/{$putTagsDiscussionID}/tags", ["urlcodes" => ["Put2", "Put3"]])->getBody();
        $this->assertCount(2, $tagFrags2);
        $tagFragNames2 = array_column($tagFrags2, "urlcode");
        $this->assertNotContains("Put1", $tagFragNames2);

        // Test adding a tag that doesn't exist. We should get a "Not Found" error.
        $this->expectExceptionCode(404);
        $this->api()->put("/discussions/{$putTagsDiscussionID}/tags", ["urlcodes" => ["non-existent-tag"]]);
    }

    /**
     * Test adding tags to a discussion.
     */
    public function testPostDiscussionTags() {
        $category = $this->insertCategories(1)[0];
        $tag1 = $this->tagModel->save([
            'Name' => 'Post1',
            'FullName' => 'Post 1 Full'
        ]);
        $tag2 = $this->tagModel->save([
            'Name' => 'Post2',
            'FullName' => 'Post 2 Full'
        ]);
        $tag3 = $this->tagModel->save([
            'Name' => 'Post3',
            'FullName' => 'Post 3 Full'
        ]);

        $postTagsDiscussionID = $this->discussionModel->save([
            'Name' => "PostTest",
            'CategoryID' => $category['CategoryID'],
            'Body' => "PostTest",
            'Format' => 'Text',
            'DateInserted' => TestDate::mySqlDate(),
        ]);

        // Add the first two tags to the discussion and make sure they've been added.
        $tagFrags = $this->api()->post("/discussions/{$postTagsDiscussionID}/tags", ["urlcodes" => ["Post1", "Post2"]])->getBody();
        $this->assertCount(2, $tagFrags);
        $tagFragNames = array_column($tagFrags, "urlcode");
        $this->assertContains("Post1", $tagFragNames);
        $this->assertContains("Post2", $tagFragNames);

        // Add the third tag and make sure we get that's been added and that the first two are still associated with the discussion.
        $tagFrags2 = $this->api()->post("/discussions/{$postTagsDiscussionID}/tags", ["urlcodes" => ["Post3"]])->getBody();
        $this->assertCount(3, $tagFrags2);
        $tagFragNames2 = array_column($tagFrags2, "urlcode");
        $this->assertContains("Post3", $tagFragNames2);
        $this->assertContains("Post2", $tagFragNames2);
        $this->assertContains("Post1", $tagFragNames2);
    }
}
