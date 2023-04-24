<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TagModel;
use VanillaTests\APIv0\TestDispatcher;
use Garden\EventManager;

/**
 * Tests for the tag model.
 */
class TagModelTest extends \VanillaTests\SiteTestCase
{
    use ModelTestTrait;
    use TestCategoryModelTrait;
    use TestDiscussionModelTrait;

    /** @var int */
    protected static $tagCount = 0;

    /** @var TagModel */
    private $tagModel;

    protected static $addons = ["roletracker"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->setupSiteTestTrait();
        $this->createUserFixtures();

        TagModel::instance()->resetTypes();

        $this->discussionModel = self::container()->get(\DiscussionModel::class);
        $this->categoryModel = self::container()->get(\CategoryModel::class);
        $this->tagModel = self::container()->get(TagModel::class);
        $this->tagModel->SQL->truncate("Tag");

        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->unbindClass(self::class);
        $eventManager->bind("tagModel_types", [$this, "tagModel_types_handler"]);

        $config = self::container()->get(\Gdn_Configuration::class);
        $config->saveToConfig("Vanilla.Tagging.Max", 5);
        $config->saveToConfig("Tagging.Discussions.AllowedTypes", ["AllowedType", ""]);
    }

    /**
     * Create a test tag.
     *
     * @param array $override Array of fields to override.
     * @return array
     */
    public function newTag(array $override): array
    {
        $tag =
            $override +
            self::sprintfCounter([
                "Name" => "tag-%s",
                "FullName" => "Tag %s",
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
    public function insertTags(int $count, array $overrides = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->tagModel->save($this->newTag($overrides));
        }
        $rows = $this->tagModel->getWhere(["TagID" => $ids])->resultArray();
        TestCase::assertCount($count, $rows, "Not enough test tags were inserted.");

        return $rows;
    }

    /**
     * Test getting tagIDs by their tag name.
     */
    public function testGetTagIDsByName()
    {
        $tags = $this->insertTags(2, ["Type" => "Status"]);
        $tagNames = array_column($tags, "Name");
        $tagIDs = array_column($tags, "TagID");

        $this->assertIDsEqual($tagIDs, array_values($this->tagModel->getTagIDsByName($tagNames)));
    }

    /**
     * Test persistence of tag on a moved discussion.
     */
    public function testSaveMovedDiscussion()
    {
        $categories = $this->insertCategories(2);
        $taggedDiscussion = $this->insertDiscussions(1, [
            "Name" => "TagTest",
            "CategoryID" => $categories[0]["CategoryID"],
            "Body" => "TagTest",
            "Format" => "Text",
            "DateInserted" => TestDate::mySqlDate(),
            "Tags" => "xxx",
        ]);

        $taggedDiscussionID = $taggedDiscussion[0]["DiscussionID"];

        $this->api()->patch("/discussions/" . $taggedDiscussionID, ["CategoryID" => $categories[1]["CategoryID"]]);
        $tagInfo = $this->tagModel->getDiscussionTags($taggedDiscussionID);
        $tagName = $tagInfo[""][0]["Name"];
        $this->assertSame("xxx", $tagName);
    }

    /**
     * Test validateTagReference.
     */
    public function testValidateTagReference()
    {
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
    public function testGetTagsFromReference()
    {
        // Make the tags.
        $tags = $this->insertTags(2);

        // Create a valid tag reference.
        $tagReference = ["urlcodes" => [$tags[0]["Name"]], "tagIDs" => [$tags[1]["TagID"]]];

        // We should get the tags back.
        $tagsFromRef = $this->tagModel->getTagsFromReferences($tagReference);

        //Make sure that we actually do.
        $this->assertCount(2, $tagsFromRef);
        $tagIDs = array_column($tagsFromRef, "TagID");
        $this->assertContains($tags[0]["TagID"], $tagIDs);
        $this->assertContains($tags[1]["TagID"], $tagIDs);
    }

    /**
     * Test normalizing tag input and output.
     */
    public function testNormalizeInputOutput()
    {
        $tag = ["urlcode" => "tag", "name" => "tag"];

        // This tag should be saved with the keys "Name" and "FullName".
        $normalizedIn = $this->tagModel->normalizeInput([$tag]);
        $this->assertEquals([["Name" => "tag", "FullName" => "tag"]], $normalizedIn);

        // This tag should be returned with the keys as they were originally.
        $normalizedOut = $this->tagModel->normalizeOutput($normalizedIn);
        $this->assertEquals([["urlcode" => "tag", "name" => "tag"]], $normalizedOut);
    }

    /**
     * Test setting tags on a discussion.
     */
    public function testPutDiscussionTags()
    {
        $tags = $this->insertTags(3);

        // Set the tags.
        $putTagsDiscussion = $this->insertDiscussions(1, [
            "Name" => "PutTest",
            "Body" => "PutTest",
            "Format" => "Text",
            "DateInserted" => TestDate::mySqlDate(),
        ]);

        $tagUrlCodes = array_column($tags, "Name");

        $putTagsDiscussionID = $putTagsDiscussion[0]["DiscussionID"];

        // This should set the first two tags on the discussion.
        $tagFrags = $this->api()
            ->put("/discussions/{$putTagsDiscussionID}/tags", ["urlcodes" => [$tagUrlCodes[0], $tagUrlCodes[1]]])
            ->getBody();
        $this->assertCount(2, $tagFrags);
        $tagFragNames = array_column($tagFrags, "urlcode");
        $this->assertContains($tagUrlCodes[0], $tagFragNames);
        $this->assertContains($tagUrlCodes[1], $tagFragNames);

        // When we set tags 2 and 3 on the discussion, they should be the only ones associated with it (tag1 should go away).
        $tagFrags2 = $this->api()
            ->put("/discussions/{$putTagsDiscussionID}/tags", ["urlcodes" => [$tagUrlCodes[1], $tagUrlCodes[2]]])
            ->getBody();
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
    public function testPostDiscussionTags()
    {
        $tags = $this->insertTags(3);

        $tagUrlCodes = array_column($tags, "Name");

        $postTagsDiscussion = $this->insertDiscussions(1, [
            "Name" => "PostTest",
            "Body" => "PostTest",
            "Format" => "Text",
            "DateInserted" => TestDate::mySqlDate(),
        ]);

        $postTagsDiscussionID = $postTagsDiscussion[0]["DiscussionID"];

        // Add the first two tags to the discussion and make sure they've been added.
        $tagFrags = $this->api()
            ->post("/discussions/{$postTagsDiscussionID}/tags", ["urlcodes" => [$tagUrlCodes[0], $tagUrlCodes[1]]])
            ->getBody();
        $this->assertCount(2, $tagFrags);
        $tagFragNames = array_column($tagFrags, "urlcode");
        $this->assertContains($tagUrlCodes[0], $tagFragNames);
        $this->assertContains($tagUrlCodes[1], $tagFragNames);

        // Add the third tag and make sure we get that's been added and that the first two are still associated with the discussion.
        $tagFrags2 = $this->api()
            ->post("/discussions/{$postTagsDiscussionID}/tags", ["urlcodes" => [$tagUrlCodes[2]]])
            ->getBody();
        $this->assertCount(3, $tagFrags2);
        $tagFragNames2 = array_column($tagFrags2, "urlcode");
        $this->assertContains($tagUrlCodes[2], $tagFragNames2);
        $this->assertContains($tagUrlCodes[0], $tagFragNames2);
        $this->assertContains($tagUrlCodes[1], $tagFragNames2);
    }

    /**
     * Test adding more than max tags allowed for a discussion.
     */
    public function testExceedMaxTagsPost(): void
    {
        $tags = $this->insertTags(2);
        $tagCodes = array_column($tags, "Name");

        $maxTagsDiscussion = $this->insertDiscussions(1, [
            "Name" => "PostMaxTagsTest",
            "Body" => "PostMaxTagsTest",
            "Format" => "Text",
            "DateInserted" => TestDate::mySqlDate(),
        ]);

        $maxTagsDiscussionID = $maxTagsDiscussion[0]["DiscussionID"];

        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig("Vanilla.Tagging.Max", 1);

        // Post 1 tag.
        $discussionTags = $this->api()
            ->post("/discussions/{$maxTagsDiscussionID}/tags", ["urlcodes" => [$tagCodes[0]]])
            ->getBody();
        $this->assertCount(1, $discussionTags);

        // Try posting one too many tags.
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage("You cannot add more than 1 tag to a discussion");
        $this->api()->post("/discussions/{$maxTagsDiscussionID}/tags", ["urlcodes" => [$tagCodes[1]]]);
    }

    /**
     * Test adding more tags than are allowed through the put endpoint.
     */
    public function testExceedMaxTagsPut(): void
    {
        // Add the tags.
        $tags = $this->insertTags(2);
        $tagCodes = array_column($tags, "Name");

        $maxTagsDiscussion = $this->insertDiscussions(1, [
            "Name" => "PutMaxTagsTest",
            "Body" => "PutMaxTagsTest",
            "Format" => "Text",
            "DateInserted" => TestDate::mySqlDate(),
        ]);

        $maxTagsDiscussionID = $maxTagsDiscussion[0]["DiscussionID"];

        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig("Vanilla.Tagging.Max", 1);

        // Try using post to add too many tags.
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage("You cannot add more than 1 tag to a discussion");
        $this->api()->put("/discussions/{$maxTagsDiscussionID}/tags", ["urlcodes" => $tagCodes]);
    }

    /**
     * Test adding a restricted tag type to a discussion (all types are restricted by default).
     */
    public function testAddingTagOfRestrictedTypeToDiscussion(): void
    {
        // Add a tag.
        $tags = $this->insertTags(1, ["Type" => "RESTRICTED"]);

        $restrictedTagTypeDiscussion = $this->insertDiscussions(1, [
            "Name" => "RestrictedTagsTest",
            "Body" => "RestrictedTagsTest",
            "Format" => "Text",
            "DateInserted" => TestDate::mySqlDate(),
        ]);

        $restrictedTagTypeDiscussionID = $restrictedTagTypeDiscussion[0]["DiscussionID"];

        $this->expectExceptionCode(409);
        $this->expectExceptionMessage("You cannot add tags with a type of RESTRICTED to a discussion");
        $this->api()->put("/discussions/{$restrictedTagTypeDiscussionID}/tags", ["urlcodes" => [$tags[0]["Name"]]]);
    }

    /**
     * Test adding a tag of a type that has been specifically allowed.
     */
    public function testAddingTagOfAllowedType()
    {
        // We've added "AllowedType" to the list of allowed types through the config setting "Tagging.Discussions.AllowedTypes" in the setup method.
        // Let's add a tag of that type and see if we can apply it to a discussion.
        $tags = $this->insertTags(1, ["Type" => "AllowedType"]);
        $discussions = $this->insertDiscussions(1);
        $discussionID = $discussions[0]["DiscussionID"];

        $tagFrags = $this->api()
            ->post("/discussions/{$discussionID}/tags", ["urlcodes" => [$tags[0]["Name"]]])
            ->getBody();
        // Discussion should have 1 tag.
        $this->assertCount(1, $tagFrags);
        $returnedTagID = $tagFrags[0]["tagID"];
        $fullTagData = $this->api()
            ->get("/tags/{$returnedTagID}")
            ->getBody();
        // It should have a type of "AllowedType"
        $this->assertSame($fullTagData["type"], "AllowedType");
    }

    /**
     * Test search() method where parent is false. This test ensures that the search method works when you pass
     * the boolean false to the search() method.
     */
    public function testSearchWithNoParent()
    {
        $this->insertTags(2);
        $searchedTags = $this->tagModel->search("tag", false, false);
        $this->assertCount(2, $searchedTags);
    }

    /**
     * Test that the dashboard's UI shows the "Add Tag" button where/when appropriate.
     */
    public function testCustomTagTypesDashboardUI(): void
    {
        // See tagModel_types_handler() for the custom tag types.

        // As an admin...
        $this->getSession()->start($this->adminID);
        // We set ourselves tagging permissions.
        $this->getSession()->addPermissions(["Vanilla.Tagging.Add"]);

        $html = $this->bessy()->getHtml("/settings/tagging/?type=usablecustomtype");
        // Checks that the appropriate "Add Tag" button is there.
        $html->assertCssSelectorExists('.header-block a.btn-primary[href*="add?type=usablecustomtype"]');

        $html = $this->bessy()->getHtml("/settings/tagging/?type=unusablecustomtype");
        // Checks that there is no "Add Tag" button.
        $html->assertCssSelectorNotExists('.header-block a.btn-primary[href*="add"]');
        $this->getSession()->end();
    }

    /**
     * Add Types to TagModel.
     *
     * @param TagModel $sender
     */
    public function tagModel_types_handler($sender)
    {
        // Create 2 custom tag types: One that's usable, one that's unusable.
        $sender->addType("usablecustomtype", [
            "key" => "usablecustomtype",
            "name" => "Usable Custom Type",
            "plural" => "Usable Custom Type(plural)",
            "addtag" => true,
            "default" => false,
        ]);

        $sender->addType("unusablecustomtype", [
            "key" => "UnusableCustomType",
            "name" => "Unusable Custom Type",
            "plural" => "Unusable Custom Type(plural)",
            "addtag" => false,
            "default" => false,
        ]);
    }

    /**
     * Check that we cannot Add Tags without the "Vanilla.Tagging.Add" Permission.
     */
    public function testAddCustomTagTypesThroughDashboardWithputPermission(): void
    {
        $this->getSession()->start($this->adminID);

        $this->assertContains("Garden.Community.Manage", $this->getSession()->getPermissionsArray());
        // By default Admin permissions do not include "Vanilla.Tagging.Add" permission
        $this->assertNotContains("Vanilla.Tagging.Add", $this->getSession()->getPermissionsArray());
        // We set ourselves tagging permissions.
        $this->expectExceptionMessage("You don't have permission to do that.");
        $this->bessy()->post("/settings/tags/add?type=usablecustomtype", [
            "FullName" => "A New Tag",
            "Name" => "a-new-tag",
            "Type" => "usablecustomtype",
        ]);
    }

    /**
     * Check if we can Add Tags of a certain type(has addtag set to true). Also, we shouldn't be able to add tags for
     * tag types that have addtag set to false.
     */
    public function testAddCustomTagTypesThroughDashboard(): void
    {
        // See tagModel_types_handler() for the custom tag types.

        // As an admin...
        $this->getSession()->start($this->adminID);
        // We set ourselves tagging permissions.
        $this->getSession()->addPermissions(["Vanilla.Tagging.Add"]);

        $this->bessy()->post("/settings/tags/add?type=usablecustomtype", [
            "FullName" => "A New Tag",
            "Name" => "a-new-tag",
            "Type" => "usablecustomtype",
        ]);

        // Checks that the newly created "A New Tag" tag is there.
        $html = $this->bessy()->getHtml("/settings/tagging/?type=usablecustomtype");
        $html->assertCssSelectorTextContains(".plank-container", "A New Tag");

        $html = $this->bessy()->getHtml("/settings/tagging/?type=unusablecustomtype");
        // Checks that there is no "Add Tag" button.
        $html->assertCssSelectorNotExists('.header-block a.btn-primary[href*="add"]');

        // we try to add a new tag anyway (This should fail).
        $this->bessy()->post(
            "/settings/tags/add?type=unusablecustomtype",
            ["FullName" => "Another New Tag", "Name" => "another-new-tag", "Type" => "unusablecustomtype"],
            [TestDispatcher::OPT_THROW_FORM_ERRORS => false]
        );
        $this->bessy()->assertFormErrorMessage("That type does not accept manually adding new tags.");

        $this->bessy()->post("/settings/tags/add?type=usablecustomtype", [
            "FullName" => "A New Tag",
            "Name" => "a-new-custom-tag",
        ]);
        $this->bessy()->assertNoFormErrors();
    }

    /**
     * Verify retrieving a set of tags on a set of discussions.
     *
     * @param bool $doTagFilter Include assertions for the $tagID parameter.
     * @dataProvider provideGetTagsByDiscussionIDsParams
     */
    public function testGetTagsByDiscussionIDs(bool $doTagFilter): void
    {
        $primaryTags = array_column($this->insertTags(3), null, "TagID");
        $secondaryTags = array_column($this->insertTags(2), null, "TagID");

        $expectedDiscussions = $this->insertDiscussions(5);
        foreach ($expectedDiscussions as $taggedDiscussion) {
            $this->api()->put("/discussions/" . $taggedDiscussion["DiscussionID"] . "/tags", [
                "urlcodes" => array_column(
                    $doTagFilter ? array_merge($primaryTags, $secondaryTags) : $primaryTags,
                    "Name"
                ),
            ]);
        }

        // Make sure we have some naked discussions to verify proper filtering.
        $this->insertDiscussions(5);

        $actual = $this->tagModel->getTagsByDiscussionIDs(
            array_column($expectedDiscussions, "DiscussionID"),
            $doTagFilter ? array_column($primaryTags, "TagID") : []
        );

        $expectedDiscussionIDs = array_keys($expectedDiscussions);
        $actualDiscussionIDs = array_keys($actual);
        $this->assertSame(sort($expectedDiscussionIDs), sort($actualDiscussionIDs));

        $tagAssertFilter = function ($tag): array {
            unset($tag["CountDiscussions"]);
            return $tag;
        };
        $expectedTags = array_map($tagAssertFilter, $primaryTags);
        foreach ($actual as $discussionTags) {
            $actualTags = array_map($tagAssertFilter, $discussionTags);
            $this->assertSame($expectedTags, $actualTags);
        }
    }

    /**
     * @return array
     */
    public function provideGetTagsByDiscussionIDsParams(): array
    {
        return [
            "Filter by discussion and tag" => [true],
            "Filter by discussion" => [false],
        ];
    }

    /**
     * Verify no discussion IDs means an empty result.
     */
    public function testGetTagsByDiscussionIDsNoDiscussions(): void
    {
        $actual = $this->tagModel->getTagsByDiscussionIDs([]);
        $this->assertSame([], $actual);
    }

    /**
     * Verify a proper exception is thrown when an invalid discussion ID array is passed to getTagsByDiscussionIDs.
     */
    public function testGetTagsByDiscussionIDsInvalidDiscussionIDs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid discussion ID array specified.");
        $this->tagModel->getTagsByDiscussionIDs([1, 2, "foo"]);
    }

    /**
     * Verify a proper exception is thrown when an invalid tag ID array is passed to getTagsByDiscussionIDs.
     */
    public function testGetTagsByDiscussionIDsInvalidTagIDs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid tag ID array specified.");
        $this->tagModel->getTagsByDiscussionIDs([1, 2, 3], [4, 5, "foo"]);
    }

    /**
     * Test that tags mistakenly given empty values don't error out.
     * @return void
     */
    public function testEmptyTag()
    {
        $tagID = $this->tagModel->save($this->newTag([]));
        $this->tagModel->setField($tagID, "Name", "");
        $this->tagModel->setField($tagID, "FullName", "");

        $tag = $this->tagModel->getID($tagID, DATASET_TYPE_ARRAY);
        [$tag] = $this->tagModel->normalizeOutput([$tag]);

        $this->tagModel->getTagFragmentSchema()->validate($tag);
        // No errors occurred.
        $this->assertTrue(true);
    }
}
