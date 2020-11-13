<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use VanillaTests\CategoryAndDiscussionApiTestTrait;

/**
 * Test the /api/v2/tags endpoint.
 */
class TagsTest extends AbstractAPIv2Test {

    use CategoryAndDiscussionApiTestTrait;

    /** @var array */
    protected static $addons = ["vanilla"];

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

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
}
