<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the tag model.
 */
class TagModelTest extends VanillaTestCase {

    use SiteTestTrait;
    use ModelTestTrait;
    use TestCategoryModelTrait;
    use TestDiscussionModelTrait;

    /** @var int */
    protected static $tagCount = 0;

    /** @var \TagModel */
    private $tagModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        $this->setupSiteTestTrait();
        $this->discussionModel = self::container()->get(\DiscussionModel::class);
        $this->categoryModel = self::container()->get(\CategoryModel::class);
        $this->tagModel = self::container()->get(\TagModel::class);
        $this->tagModel->SQL->truncate('Tag');

        $config = self::container()->get(\Gdn_Configuration::class);
        $config->saveToConfig('Vanilla.Tagging.Max', 5);
    }

    /**
     * Create a test tag.
     *
     * @param array $override Array of fields to override.
     * @return array
     */
    public function newTag(array $override): array {
        $tag = $override + self::sprintfCounter([
                'Name' => 'tag-%s',
                'FullName' => 'Tag %s',
            ]);


        return $tag;
    }

    /**
     * Insert test records and return them.
     *
     * @param int $count The number of tags to insert.
     * @param array $overrides An array of row overrides.
     * @return array
     */
    public function insertTags(int $count, array $overrides = []): array {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->tagModel->save($this->newTag($overrides));
        }
        $rows = $this->tagModel->getWhere(['TagID' => $ids])->resultArray();
        TestCase::assertCount($count, $rows, "Not enough test discussions were inserted.");

        return $rows;
    }

    /**
     * Test getting tagIDs by their tag name.
     */
    public function testGetTagIDsByName() {
        $tags = $this->insertTags(2, ['Type' => 'Status']);
        $tagNames = array_column($tags, 'Name');
        $tagIDs = array_column($tags, 'TagID');

        $this->assertIDsEqual($tagIDs, $this->tagModel->getTagIDsByName($tagNames));
    }

    /**
     * Test persistence of tag on a moved discussion.
     */
    public function testSaveMovedDiscussion() {
        $categories = $this->insertCategories(2);
        $taggedDiscussion = $this->insertDiscussions(1, [
            'Name' => "TagTest",
            'CategoryID' => $categories[0]['CategoryID'],
            'Body' => "TagTest",
            'Format' => 'Text',
            'DateInserted' => TestDate::mySqlDate(),
            'Tags' => 'xxx'
        ]);

        $taggedDiscussionID = $taggedDiscussion[0]['DiscussionID'];

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
        $tags = $this->insertTags(2);

        // Create a valid tag reference.
        $tagReference = ["urlcodes" => [$tags[0]['Name']], "tagIDs" => [$tags[1]['TagID']]];

        // We should get the tags back.
        $tagsFromRef = $this->tagModel->getTagsFromReferences($tagReference);

        //Make sure that we actually do.
        $this->assertCount(2, $tagsFromRef);
        $tagIDs = array_column($tagsFromRef, "TagID");
        $this->assertContains($tags[0]['TagID'], $tagIDs);
        $this->assertContains($tags[1]['TagID'], $tagIDs);
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
        $tags = $this->insertTags(3);

        // Set the tags.
        $putTagsDiscussion = $this->insertDiscussions(1, [
            'Name' => "PutTest",
            'Body' => "PutTest",
            'Format' => 'Text',
            'DateInserted' => TestDate::mySqlDate(),
        ]);

        $tagUrlCodes = array_column($tags, 'Name');

        $putTagsDiscussionID = $putTagsDiscussion[0]['DiscussionID'];

        // This should set the first two tags on the discussion.
        $tagFrags = $this->api()->put("/discussions/{$putTagsDiscussionID}/tags", ["urlcodes" => [$tagUrlCodes[0], $tagUrlCodes[1]]])->getBody();
        $this->assertCount(2, $tagFrags);
        $tagFragNames = array_column($tagFrags, "urlcode");
        $this->assertContains($tagUrlCodes[0], $tagFragNames);
        $this->assertContains($tagUrlCodes[1], $tagFragNames);

        // When we set tags 2 and 3 on the discussion, they should be the only ones associated with it (tag1 should go away).
        $tagFrags2 = $this->api()->put("/discussions/{$putTagsDiscussionID}/tags", ["urlcodes" => [$tagUrlCodes[1], $tagUrlCodes[2]]])->getBody();
        $this->assertCount(2, $tagFrags2);
        $tagFragNames2 = array_column($tagFrags2, "urlcode");
        $this->assertNotContains($tagUrlCodes[0], $tagFragNames2);

        // Test adding a tag that doesn't exist. We should get a "Not Found" error.
        $this->expectExceptionCode(404);
        $this->api()->put("/discussions/{$putTagsDiscussionID}/tags", ["urlcodes" => ["non-existent-tag"]]);
    }

    /**
     * Test adding tags to a discussion.
     */
    public function testPostDiscussionTags() {
        $tags = $this->insertTags(3);

        $tagUrlCodes = array_column($tags, 'Name');

        $postTagsDiscussion = $this->insertDiscussions(1, [
            'Name' => "PostTest",
            'Body' => "PostTest",
            'Format' => 'Text',
            'DateInserted' => TestDate::mySqlDate(),
        ]);

        $postTagsDiscussionID = $postTagsDiscussion[0]['DiscussionID'];

        // Add the first two tags to the discussion and make sure they've been added.
        $tagFrags = $this->api()->post("/discussions/{$postTagsDiscussionID}/tags", ["urlcodes" => [$tagUrlCodes[0], $tagUrlCodes[1]]])->getBody();
        $this->assertCount(2, $tagFrags);
        $tagFragNames = array_column($tagFrags, "urlcode");
        $this->assertContains($tagUrlCodes[0], $tagFragNames);
        $this->assertContains($tagUrlCodes[1], $tagFragNames);

        // Add the third tag and make sure we get that's been added and that the first two are still associated with the discussion.
        $tagFrags2 = $this->api()->post("/discussions/{$postTagsDiscussionID}/tags", ["urlcodes" => [$tagUrlCodes[2]]])->getBody();
        $this->assertCount(3, $tagFrags2);
        $tagFragNames2 = array_column($tagFrags2, "urlcode");
        $this->assertContains($tagUrlCodes[2], $tagFragNames2);
        $this->assertContains($tagUrlCodes[0], $tagFragNames2);
        $this->assertContains($tagUrlCodes[1], $tagFragNames2);
    }

    /**
     * Test adding more than max tags allowed for a discussion.
     */
    public function testExceedMaxTagsPost(): void {
        $tags = $this->insertTags(2);
        $tagCodes = array_column($tags, 'Name');

        $maxTagsDiscussion = $this->insertDiscussions(1, [
            'Name' => "PostMaxTagsTest",
            'Body' => "PostMaxTagsTest",
            'Format' => 'Text',
            'DateInserted' => TestDate::mySqlDate(),
        ]);

        $maxTagsDiscussionID = $maxTagsDiscussion[0]['DiscussionID'];

        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig('Vanilla.Tagging.Max', 1);

        // Post 1 tag.
        $discussionTags = $this->api()->post("/discussions/{$maxTagsDiscussionID}/tags", ["urlcodes" => [$tagCodes[0]]])->getBody();
        $this->assertCount(1, $discussionTags);

        // Try posting one too many tags.
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('You cannot add more than 1 tag to a discussion');
        $this->api()->post("/discussions/{$maxTagsDiscussionID}/tags", ["urlcodes" => [$tagCodes[1]]]);
    }

    /**
     * Test adding more tags than are allowed through the put endpoint.
     */
    public function testExceedMaxTagsPut(): void {
        // Add the tags.
        $tags = $this->insertTags(2);
        $tagCodes = array_column($tags, 'Name');

        $maxTagsDiscussion = $this->insertDiscussions(1, [
            'Name' => "PutMaxTagsTest",
            'Body' => "PutMaxTagsTest",
            'Format' => 'Text',
            'DateInserted' => TestDate::mySqlDate(),
        ]);

        $maxTagsDiscussionID = $maxTagsDiscussion[0]['DiscussionID'];

        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig('Vanilla.Tagging.Max', 1);

        // Try using post to add too many tags.
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('You cannot add more than 1 tag to a discussion');
        $this->api()->put("/discussions/{$maxTagsDiscussionID}/tags", ["urlcodes" => $tagCodes]);
    }

    /**
     * Test adding a restricted tag type to a discussion (all types are restricted by default).
     */
    public function testAddingTagOfRestrictedTypeToDiscussion(): void {
        // Add a tag.
        $tags = $this->insertTags(1, ['Type' => 'RESTRICTED']);

        $restrictedTagTypeDiscussion = $this->insertDiscussions(1, [
            'Name' => "RestrictedTagsTest",
            'Body' => "RestrictedTagsTest",
            'Format' => 'Text',
            'DateInserted' => TestDate::mySqlDate(),
        ]);

        $restrictedTagTypeDiscussionID = $restrictedTagTypeDiscussion[0]['DiscussionID'];

        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('You cannot add tags with a type of RESTRICTED to a discussion');
        $this->api()->put("/discussions/{$restrictedTagTypeDiscussionID}/tags", ["urlcodes" => [$tags[0]['Name']]]);
    }

    /**
     * Test search() method where parent is false. This test ensures that the search method works when you pass
     * the boolean false to the search() method.
     */
    public function testSearchWithNoParent() {
        $this->insertTags(2);
        $searchedTags = $this->tagModel->search('tag', false, false);
        $this->assertCount(2, $searchedTags);
    }
}
