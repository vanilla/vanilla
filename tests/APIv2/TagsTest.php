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
class TagsTest extends AbstractResourceTest
{
    use CategoryAndDiscussionApiTestTrait, \VanillaTests\APIv2\NoGetEditTestTrait;

    /** @var int */
    protected static $testCount = 0;

    /** @var array */
    protected static $addons = ["vanilla"];

    /** @var string */
    protected $baseUrl = "/tags";

    /** @var string */
    protected $pk = "tagID";

    /** @var string[] */
    protected $patchFields = ["name", "type"];

    /** @var bool */
    protected $testPagingOnIndex = false;

    /** @var string */
    private const TYPE = "someType";

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        static::$testCount++;

        $tag1 = $this->createTag([
            "name" => "tag1",
            "fullName" => "tag1",
        ]);
        $tag2 = $this->createTag([
            "name" => "tag2",
            "fullName" => "tag2",
        ]);
        $tag3 = $this->createTag([
            "name" => "tag3",
            "fullName" => "tag3",
        ]);
        $tag4 = $this->createTag([
            "name" => "random",
            "fullName" => "random",
        ]);

        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig("Tagging.Discussions.AllowedTypes", [""]);
    }

    /**
     * Create records for testing.
     *
     * @param array $overrides
     * @return array
     */
    public function record(array $overrides = [])
    {
        static $totalRecords = 0;

        $name = "API Test Tag " . ++$totalRecords;
        $record = array_replace(
            [
                "name" => $name,
                "type" => self::TYPE,
            ],
            $overrides
        );

        return $record;
    }

    /**
     * Test GET /tags endpoint.
     */
    public function testGetTags()
    {
        $results = $this->api()
            ->get("/tags", ["query" => "tag"])
            ->getBody();
        $this->assertEquals(3, count($results));
        $this->assertArrayHasKey("type", $results[0]);
    }

    /**
     * Test GET /tags endpoint.
     */
    public function testGetTagsNoResults()
    {
        $results = $this->api()
            ->get("/tags", ["query" => "notCreated"])
            ->getBody();
        $this->assertEquals(0, count($results));
    }

    /**
     * Test GET /tags endpoint.
     *
     * Make sure we're returning only user created tags.
     */
    public function testGetTagsOnlyUserGeneratedTags()
    {
        /** @var \TagModel $tagModel */
        $tagModel = \Gdn::getContainer()->get(\TagModel::class);

        $reactionTag = $this->createTag([
            "name" => "Like",
            "fullName" => "Like",
            "type" => "Reaction",
        ]);

        $this->createTag([
            "name" => $reactionTag["Name"] . "1",
            "fullName" => $reactionTag["Name"] . "1",
        ]);

        $results = $this->api()
            ->get("/tags", ["query" => $reactionTag["Name"], "type" => "tag"])
            ->getBody();
        $this->assertEquals(1, count($results));
        $this->assertNotEquals($reactionTag["Name"], $results[0]["name"]);
    }

    /**
     * Test getting an error when posting tag with a duplicate name.
     */
    public function testPostWithDuplicateName()
    {
        $tagToDuplicate = $this->api()
            ->post($this->baseUrl, $this->record())
            ->getBody();

        $this->expectExceptionCode(409);
        $this->expectExceptionMessage("A tag with this name already exists.");
        $this->api()->post($this->baseUrl, ["name" => $tagToDuplicate["name"]]);
    }

    /**
     * Test getting an error when deleting a tag with a child.
     */
    public function testDeleteTagWithParent()
    {
        // Create the tag.
        $tagToDelete = $this->api()
            ->post($this->baseUrl, $this->record())
            ->getBody();

        // Give the tag a child.
        $this->api()->post($this->baseUrl, ["name" => "childTag", "parentTagID" => $tagToDelete["tagID"]]);

        // Should get an Error.
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage("You cannot delete tags that have associated child tags.");
        $this->api()->delete($this->baseUrl . "/{$tagToDelete["tagID"]}");
    }

    /**
     * Test getting an error when deleting a tag with an associated discussion.
     */
    public function testDeleteTagWithDiscussion()
    {
        // Create the tag.
        $tagToDelete = $this->api()
            ->post($this->baseUrl, $this->record())
            ->getBody();

        // Create a discussion.
        $discussion = $this->createDiscussion();

        // Set the config to allow the discussion type.
        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig("Tagging.Discussions.AllowedTypes", ["", self::TYPE]);

        // Tag it.
        $this->api()->post("discussions/{$discussion["discussionID"]}/tags", ["tagIDs" => [$tagToDelete["tagID"]]]);

        // Should get an Error.
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage("You cannot delete tags that have associated discussions.");
        $this->api()->delete($this->baseUrl . "/{$tagToDelete["tagID"]}");
    }

    /**
     * Test deleting tags.
     */
    public function testDelete(): void
    {
        $tagNotReserved = $this->createTag();
        $result = $this->api()->delete("/tags/{$tagNotReserved["TagID"]}");
        $this->assertEquals(204, $result->getStatusCode());
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage("You cannot delete a reserved tag.");
        $tagReserved = $this->createTag(["type" => "reaction"]);
        $this->api()->delete("/tags/{$tagReserved["TagID"]}");
    }

    /**
     * Verify ability to filter tags by parent ID.
     */
    public function testIndexParentID(): void
    {
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

        $result = $this->api()
            ->get($this->baseUrl, [
                "parentID" => $parent["tagID"],
            ])
            ->getBody();

        $this->assertSame(array_column($children, "tagID"), array_column($result, "tagID"));

        foreach ($result as $row) {
            $this->assertSame($parent["tagID"], $row["parentTagID"]);
        }
    }

    /**
     * Verify ability to filter tags by type.
     */
    public function testIndexType(): void
    {
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

        $result = $this->api()
            ->get($this->baseUrl, [
                "type" => $type,
            ])
            ->getBody();

        $this->assertSame(array_column($tags, "tagID"), array_column($result, "tagID"));
    }

    /**
     * Verify ability to filter tags by CountDiscussions.
     */
    public function testfilterCountDiscussions(): void
    {
        $tag = $this->api()
            ->post($this->baseUrl, $this->record())
            ->getBody();
        $tagModel = \Gdn::getContainer()->get(\TagModel::class);
        $tagModel->setField($tag["tagID"], "CountDiscussions", 3);
        $tagsWithDiscussionCounts = $tagModel->getCount(["CountDiscussions >" => 0]);
        $result = $this->api()
            ->get($this->baseUrl, [
                "excludeNoCountDiscussion" => true,
            ])
            ->getBody();
        $this->assertCount($tagsWithDiscussionCounts, $result);
    }

    /**
     * Verify ability to sort tags.
     */
    public function testIndexSort(): void
    {
        $indexUrl = $this->indexUrl();
        $rows = $this->api()
            ->get($indexUrl)
            ->getBody();

        /** @var \TagModel $tagModel */
        $tagModel = \Gdn::getContainer()->get(\TagModel::class);
        for ($i = 0; $i < count($rows); $i++) {
            $tagModel->setField($rows[$i]["tagID"], "CountDiscussions", rand(1, 5));
        }

        $updatedRows = $this->api()
            ->get($indexUrl)
            ->getBody();
        $updatedRows = array_column($updatedRows, "countDiscussions");
        rsort($updatedRows);

        $result = $this->api()->get($indexUrl, ["sort" => "countDiscussions"]);
        $resultRows = $result->getBody();

        $this->assertSame(array_column($resultRows, "countDiscussions"), $updatedRows);
    }

    /**
     * Test getting an error for an invalid parent tag ID.
     */
    public function testAbsenteeParent()
    {
        $tag = [
            "name" => "absenteeParent",
            "parentTagID" => 100000,
        ];

        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("Parent tag not found.");
        $this->api()->post($this->baseUrl, $tag);
    }

    /**
     * Test patching a null parentID.
     */
    public function testPatchingNullParentID()
    {
        $tag = [
            "name" => "nullifyParent",
            "parentTagID" => 1,
        ];

        $returnedTag = $this->api()
            ->post($this->baseUrl, $tag)
            ->getBody();
        $tagWithParent = $this->api()
            ->get($this->baseUrl . "/" . $returnedTag["tagID"])
            ->getBody();
        // Make sure the parent tag ID is there.
        $this->assertSame($tagWithParent["parentTagID"], 1);
        $this->api()->patch($this->baseUrl . "/" . $returnedTag["tagID"], ["parentTagID" => null]);
        $nullifiedParentTag = $this->api()
            ->get($this->baseUrl . "/" . $returnedTag["tagID"])
            ->getBody();
        // The parent tag ID only comes back if there is one, which there shouldn't be in this case.
        $this->assertArrayNotHasKey("parentTagID", $nullifiedParentTag);
    }

    /**
     * Test patching a tag type to an empty string (the default user type).
     */
    public function testPatchTypeOfEmptyString()
    {
        $tag = [
            "name" => "revertToEmptyString",
            "type" => "thisStringIsNotEmpty",
        ];

        $returnedTag = $this->api()
            ->post($this->baseUrl, $tag)
            ->getBody();
        $tagWithTypeNonEmptyString = $this->api()
            ->get($this->baseUrl . "/" . $returnedTag["tagID"])
            ->getBody();
        // Check that the type was applied.
        $this->assertSame($tag["type"], $tagWithTypeNonEmptyString["type"]);
        $this->api()->patch($this->baseUrl . "/" . $returnedTag["tagID"], ["type" => ""]);
        $tagWithTypeEmptyString = $this->api()
            ->get($this->baseUrl . "/" . $returnedTag["tagID"])
            ->getBody();
        // The type should not come through because it's returned as a null value.
        $this->assertArrayNotHasKey("type", $tagWithTypeEmptyString);
    }

    /**
     * Test that tags with types in the Tagging.Discussions.AllowedTypes config array are editable.
     *
     * @throws \Garden\Container\ContainerException Container Exception.
     * @throws \Garden\Container\NotFoundException Not Found Exception.
     */
    public function testCanEditAllowedTypes()
    {
        $config = \Gdn::getContainer()->get(\Gdn_Configuration::class);
        $config->saveToConfig("Tagging.Discussions.AllowedTypes", ["", "allowed", "notAllowed"]);
        $config->saveToConfig("Tagging.Discussions.Enabled", true);
        $allowedTag = [
            "name" => "allowed tag",
            "type" => "allowed",
        ];
        $notAllowedTag = [
            "name" => "not allowed tag",
            "type" => "notAllowed",
        ];
        $returnedAllowed = $this->api()
            ->post($this->baseUrl, $allowedTag)
            ->getBody();
        $returnedNotAllowed = $this->api()
            ->post($this->baseUrl, $notAllowedTag)
            ->getBody();
        $tagSettingsHtml = $this->bessy()
            ->getHtml("/settings/tagging")
            ->getInnerHtml();
        $this->assertStringContainsString($this->createEditLink($returnedAllowed["tagID"]), $tagSettingsHtml);
        $this->assertStringContainsString($this->createEditLink($returnedNotAllowed["tagID"]), $tagSettingsHtml);

        // Now we're going to remove the "notAllowed" tag from the allowed types, and only the 'allowed tag' should be returned.
        $config->saveToConfig("Tagging.Discussions.AllowedTypes", ["", "allowed"]);
        $updatedTagSettingsHtml = $this->bessy()
            ->getHtml("/settings/tagging")
            ->getInnerHtml();
        $this->assertStringContainsString($this->createEditLink($returnedAllowed["tagID"]), $updatedTagSettingsHtml);
        $this->assertStringNotContainsString(
            $this->createEditLink($returnedNotAllowed["tagID"]),
            $updatedTagSettingsHtml
        );
    }

    /**
     * Create the edit link with the appropriateTagID for the
     *
     * @param int $tagID
     * @return string
     */
    public function createEditLink(int $tagID): string
    {
        return 'href="/tagstest/settings/tags/edit/' . $tagID . '"';
    }
}
