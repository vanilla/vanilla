<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use VanillaTests\CategoryAndDiscussionApiTestTrait;
use Garden\Http\HttpResponse;

/**
 * Test the /api/v2/tags endpoint.
 */
class TagsTest extends AbstractResourceTest {

    use CategoryAndDiscussionApiTestTrait, \VanillaTests\APIv2\NoGetEditTestTrait;

    /** @var int */
    protected static $testCount = 0;

    /** @var array */
    protected static $addons = ["vanilla"];

    /** @var string */
    protected $baseUrl = '/tags';

    /** @var string */
    protected $pk = 'tagID';

    /** @var string[] */
    protected $patchFields = ["name", "type", "parentTagID"];

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        static::$testCount++;

        $tag1 = $this->createTag([
            "name" => "tag1",
            "fullName" => "tag1"
        ]);
        $tag2 = $this->createTag([
            "name" => "tag2",
            "fullName" => "tag2"
        ]);
        $tag3 = $this->createTag([
            "name" => "tag3",
            "fullName" => "tag3"
        ]);
        $tag4 = $this->createTag([
            "name" => "random",
            "fullName" => "random"
        ]);
    }

    /**
     * Create records for testing.
     *
     * @return array
     */
    public function record() {
        static $totalRecords = 0;

        $name = "API Test Tag " . ++$totalRecords;
        $record = [
            "name" => $name,
            "type" => "someType",
            "parentTagID" => 1000
        ];
        return $record;
    }

    /**
     * Test GET /tags endpoint.
     */
    public function testGetTags() {
        $results = $this->api()->get("/tags", ["query" => "tag"])->getBody();
        $this->assertEquals(3, count($results));
    }
    /**
     * Test GET /tags endpoint.
     */
    public function testGetTagsNoResults() {
        $results = $this->api()->get("/tags", ["query" => "notCreated"])->getBody();
        $this->assertEquals(0, count($results));
    }

    /**
     * Test GET /tags endpoint.
     *
     * Make sure we're returning only user created tags.
     */
    public function testGetTagsOnlyUserGeneratedTags() {
        /** @var \TagModel $tagModel */
        $tagModel = \Gdn::getContainer()->get(\TagModel::class);

        $reactionTag =  $this->createTag([
            "name" => "Like",
            "fullName" => "Like",
            "type" => "Reaction"
        ]);

        $this->createTag([
            "name" => $reactionTag["Name"] . "1",
            "fullName" => $reactionTag["Name"] . "1"
        ]);

        $results = $this->api()->get("/tags", ["query" => $reactionTag["Name"]])->getBody();
        $this->assertEquals(1, count($results));
        $this->assertNotEquals($reactionTag["Name"], $results[0]["name"]);
    }

    /**
     * Test getting an error when posting tag with a duplicate name.
     */
    public function testPostWithDuplicateName() {
        $tagToDuplicate = $this->api()->post($this->baseUrl, $this->record())->getBody();

        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('A tag with this name already exists.');
        $this->api()->post($this->baseUrl, ["name" => $tagToDuplicate["name"]]);
    }

    /**
     * Test getting an error when deleting a tag with a child.
     */
    public function testDeleteTagWithParent() {
        // Create the tag.
        $tagToDelete = $this->api()->post($this->baseUrl, $this->record())->getBody();

        // Give the tag a child.
        $this->api()->post($this->baseUrl, ["name" => "childTag", "parentTagID" => $tagToDelete["tagID"]]);

        // Should get an Error.
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('You cannot delete tags that have associated child tags.');
        $this->api()->delete($this->baseUrl."/{$tagToDelete['tagID']}");
    }

    /**
     * Test getting an error when deleting a tag with an associated discussion.
     */
    public function testDeleteTagWithDiscussion() {
        // Create the tag.
        $tagToDelete = $this->api()->post($this->baseUrl, $this->record())->getBody();

        // Create a discussion.
        $discussion = $this->createDiscussion();

        // Tag it.
        $this->api()->post("discussions/{$discussion["discussionID"]}/tags", ["tagIDs" =>[$tagToDelete["tagID"]]]);

        // Should get an Error.
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('You cannot delete tags that have associated discussions.');
        $this->api()->delete($this->baseUrl."/{$tagToDelete['tagID']}");
    }

    /**
     * Overrides the AbstractResourcesTest's testIndex() method, as our index call doesn't function the same way.
     */
    public function testIndex() {
        $this->markTestSkipped();
    }

    /**
     * Verify ability to filter tags by parent ID.
     */
    public function testIndexParentID(): void {
        // Create our parent.
        $parent = $this->testPost();
        $parentID = $parent["tagID"];

        $totalTags = 0;

        // Create a couple child tags.
        $children = [];
        $children[] = $this->testPost([
            "name" => __FUNCTION__ . ++$totalTags,
            "parentTagID" => $parentID,
        ]);
        $children[] = $this->testPost([
            "name" => __FUNCTION__ . ++$totalTags,
            "parentTagID" => $parentID,
        ]);

        // Create some junk we know will be filtered out.
        $this->testPost(["name" => __FUNCTION__ . ++$totalTags]);
        $this->testPost(["name" => __FUNCTION__ . ++$totalTags]);
        $this->testPost(["name" => __FUNCTION__ . ++$totalTags]);

        $result = $this->api()->get($this->baseUrl, [
            "parentID" => $parent["tagID"],
            "query" => __FUNCTION__, // TODO Ditch this when we remove the required query parameter.
        ])->getBody();

        $this->assertSame(
            array_column($children, "tagID"),
            array_column($result, "tagID")
        );
    }

    /**
     * Verify ability to filter tags by type.
     */
    public function testIndexType(): void {
        $totalTags = 0;
        $type = uniqid();

        // Create some special type tags.
        $tags = [];
        $tags[] = $this->testPost([
            "name" => __FUNCTION__ . ++$totalTags,
            "type" => $type,
        ]);
        $tags[] = $this->testPost([
            "name" => __FUNCTION__ . ++$totalTags,
            "type" => $type,
        ]);

        // Create some junk we know will be filtered out.
        $this->testPost(["name" => __FUNCTION__ . ++$totalTags]);
        $this->testPost(["name" => __FUNCTION__ . ++$totalTags]);
        $this->testPost(["name" => __FUNCTION__ . ++$totalTags]);

        $result = $this->api()->get($this->baseUrl, [
            "type" => $type,
            "query" => __FUNCTION__, // TODO Ditch this when we remove the required query parameter.
        ])->getBody();

        $this->assertSame(
            array_column($tags, "tagID"),
            array_column($result, "tagID")
        );
    }
}
