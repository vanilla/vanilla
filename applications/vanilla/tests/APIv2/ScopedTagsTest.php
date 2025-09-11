<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use VanillaTests\Fixtures\MockSiteSection;
use VanillaTests\Fixtures\MockSiteSectionProvider;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Test the /api/v2/tags endpoint for the scoped tagging feature.
 */
class ScopedTagsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait, UsersAndRolesApiTestTrait;

    /** @var string */
    protected $baseUrl = "/tags";

    /** @var string */
    private const TYPE = "someType";

    private MockSiteSectionProvider $mockSiteSectionProvider;

    /** @var MockSiteSection */
    private $mockSiteSection;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->mockSiteSectionProvider = self::container()->get(MockSiteSectionProvider::class);

        \Gdn::config()->saveToConfig("Tagging.ScopedTagging.Enabled", true, false);

        \Gdn::database()
            ->sql()
            ->truncate("Tag");

        $this->mockSiteSection = new MockSiteSection(
            "test",
            "en",
            "/actual-section",
            "custom-site-section-id",
            "test1"
        );
        $this->mockSiteSectionProvider->addSiteSections([$this->mockSiteSection]);
    }

    /**
     * Create records for testing.
     *
     * @param array $overrides
     * @return array
     */
    public function record(array $overrides = [])
    {
        $record = array_replace(
            [
                "name" => VanillaTestCase::makeRandomKey("API Test Tag "),
                "type" => self::TYPE,
            ],
            $overrides
        );

        return $record;
    }

    /**
     * Test creating a global tag.
     */
    public function testCreateGlobalTag()
    {
        $record = $this->record([
            "scopeType" => "global",
        ]);
        $result = $this->api()
            ->post($this->baseUrl, $record)
            ->getBody();
        $this->assertEquals("global", $result["scopeType"]);
        $this->assertArrayNotHasKey("scope", $result);
    }

    /**
     * Test creating a scoped tag.
     */
    public function testCreateScopedTag()
    {
        $category = $this->createCategory();
        $record = $this->record([
            "scopeType" => "scoped",
            "scope" => ["categoryIDs" => [$category["categoryID"]]],
        ]);
        $result = $this->api()
            ->post($this->baseUrl, $record)
            ->getBody();
        $this->assertEquals("scoped", $result["scopeType"]);
        $this->assertEquals([$category["categoryID"]], $result["scope"]["categoryIDs"]);
    }

    /**
     * Test making a global tag scoped.
     */
    public function testPatchGlobalToScoped()
    {
        $category = $this->createCategory();
        $globalTag = $this->createTag();

        $body = [
            "scopeType" => "scoped",
            "scope" => ["categoryIDs" => [$category["categoryID"]]],
        ];
        $result = $this->api()
            ->patch($this->baseUrl . "/" . $globalTag["tagID"], $body)
            ->getBody();
        $this->assertEquals("scoped", $result["scopeType"]);
        $this->assertEquals([$category["categoryID"]], $result["scope"]["categoryIDs"]);
    }

    /**
     * Test making a scoped tag global.
     */
    public function testPatchScopedToGlobal()
    {
        $category = $this->createCategory();
        $scopedTag = $this->createScopedTag(categoryIDs: [$category["categoryID"]]);

        $body = ["scopeType" => "global"];
        $result = $this->api()
            ->patch($this->baseUrl . "/" . $scopedTag["tagID"], $body)
            ->getBody();
        $this->assertEquals("global", $result["scopeType"]);
        $this->assertArrayNotHasKey("scope", $result);
    }

    /**
     * Test changing the scope of a scoped tag.
     */
    public function testPatchChangeScope()
    {
        $category1 = $this->createCategory();
        $category2 = $this->createCategory();
        $scopedTag = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);

        $body = ["scope" => ["categoryIDs" => [$category2["categoryID"]]]];
        $result = $this->api()
            ->patch($this->baseUrl . "/" . $scopedTag["tagID"], $body)
            ->getBody();
        $this->assertEquals("scoped", $result["scopeType"]);
        $this->assertEquals([$category2["categoryID"]], $result["scope"]["categoryIDs"]);
    }

    /**
     * Test clearing the scope from a scoped tag.
     */
    public function testPatchClearScope()
    {
        $category = $this->createCategory();
        $scopedTag = $this->createScopedTag(categoryIDs: [$category["categoryID"]]);

        $body = ["scope" => []];
        $result = $this->api()
            ->patch($this->baseUrl . "/" . $scopedTag["tagID"], $body)
            ->getBody();
        $this->assertEquals("scoped", $result["scopeType"]);
        $this->assertArrayNotHasKey("scope", $result);
    }

    /**
     * Test filtering tags by category ID.
     */
    public function testIndexFilterByCategory()
    {
        $category1 = $this->createCategory();
        $category2 = $this->createCategory();

        $globalTag = $this->createTag();
        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category1["categoryID"]]);
        $scopedTag2 = $this->createScopedTag(categoryIDs: [$category2["categoryID"]]);

        $query = ["scopeType" => "global,scoped", "scope" => ["categoryIDs" => [$category1["categoryID"]]]];
        $result = $this->api()
            ->get($this->baseUrl, $query)
            ->getBody();

        $tagIDs = array_column($result, "tagID");
        $this->assertContains(
            $globalTag["tagID"],
            $tagIDs,
            "Global tags should always be returned unless filtered out."
        );
        $this->assertContains($scopedTag1["tagID"], $tagIDs, "Tag scoped to the category should be returned.");
        $this->assertNotContains(
            $scopedTag2["tagID"],
            $tagIDs,
            "Tag not scoped to the category should not be returned."
        );
    }

    /**
     * Test filtering tags by site section ID.
     */
    public function testIndexFilterBySiteSection()
    {
        $globalTag = $this->createTag();
        $scopedTag1 = $this->createScopedTag(categoryIDs: [$this->createCategory()["categoryID"]]);
        $scopedTag2 = $this->createScopedTag(siteSectionIDs: ["custom-site-section-id"]);

        $query = ["scope" => ["siteSectionIDs" => ["custom-site-section-id"]]];
        $result = $this->api()
            ->get($this->baseUrl, $query)
            ->getBody();

        $tagIDs = array_column($result, "tagID");
        $this->assertContains(
            $globalTag["tagID"],
            $tagIDs,
            "Global tags should always be returned unless filtered out."
        );
        $this->assertNotContains(
            $scopedTag1["tagID"],
            $tagIDs,
            "Tag not scoped to the site section should not be returned."
        );
        $this->assertContains($scopedTag2["tagID"], $tagIDs, "Tag scoped to the site section should be returned.");
    }

    /**
     * Test filtering tags by scope type "global".
     */
    public function testIndexFilterByScopeTypeGlobal()
    {
        $category = $this->createCategory();

        $globalTag = $this->createTag();
        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category["categoryID"]]);
        $scopedTag2 = $this->createScopedTag(siteSectionIDs: ["custom-site-section-id"]);

        $query = ["scopeType" => ["global"]];
        $result = $this->api()
            ->get($this->baseUrl, $query)
            ->getBody();

        $tagIDs = array_column($result, "tagID");
        $this->assertContains($globalTag["tagID"], $tagIDs);
        $this->assertNotContains($scopedTag1["tagID"], $tagIDs);
        $this->assertNotContains($scopedTag2["tagID"], $tagIDs);
    }

    /**
     * Test filtering tags by scope type "scoped".
     */
    public function testIndexFilterByScopeTypeScoped()
    {
        $category = $this->createCategory();

        $globalTag = $this->createTag();
        $scopedTag1 = $this->createScopedTag(categoryIDs: [$category["categoryID"]]);
        $scopedTag2 = $this->createScopedTag(siteSectionIDs: ["custom-site-section-id"]);

        $query = ["scopeType" => ["scoped"]];
        $result = $this->api()
            ->get($this->baseUrl, $query)
            ->getBody();

        $tagIDs = array_column($result, "tagID");
        $this->assertNotContains($globalTag["tagID"], $tagIDs);
        $this->assertContains($scopedTag1["tagID"], $tagIDs);
        $this->assertContains($scopedTag2["tagID"], $tagIDs);
    }

    /**
     * Test inherited scoped tags functionality for categories.
     *
     * This test verifies that scoped tags are inherited downward from parent categories
     * to their descendant categories.
     */
    public function testIndexFilterByInheritedScopedTags()
    {
        // Create a category hierarchy: parent -> child -> grandchild
        $parentCategory = $this->createCategory();
        $childCategory = $this->createCategory(["parentCategoryID" => $parentCategory["categoryID"]]);
        $grandchildCategory = $this->createCategory(["parentCategoryID" => $childCategory["categoryID"]]);

        // Create a scoped tag that is only scoped to the parent category
        $inheritedScopedTag = $this->createScopedTag(categoryIDs: [$parentCategory["categoryID"]]);

        // Create other tags for comparison
        $globalTag = $this->createTag();
        $unrelatedScopedTag = $this->createScopedTag(categoryIDs: [$this->createCategory()["categoryID"]]);

        // Test 1: Filter by parent category - should return the inherited scoped tag
        $query = ["scopeType" => "global,scoped", "scope" => ["categoryIDs" => [$parentCategory["categoryID"]]]];
        $result = $this->api()
            ->get($this->baseUrl, $query)
            ->getBody();

        $tagIDs = array_column($result, "tagID");
        $this->assertContains($globalTag["tagID"], $tagIDs, "Global tags should always be returned.");
        $this->assertContains(
            $inheritedScopedTag["tagID"],
            $tagIDs,
            "Tag scoped to parent category should be returned when filtering by parent."
        );
        $this->assertNotContains($unrelatedScopedTag["tagID"], $tagIDs, "Unrelated scoped tag should not be returned.");

        // Test 2: Filter by child category - should return the inherited scoped tag (inheritance)
        $query = ["scopeType" => "global,scoped", "scope" => ["categoryIDs" => [$childCategory["categoryID"]]]];
        $result = $this->api()
            ->get($this->baseUrl, $query)
            ->getBody();

        $tagIDs = array_column($result, "tagID");
        $this->assertContains($globalTag["tagID"], $tagIDs, "Global tags should always be returned.");
        $this->assertContains(
            $inheritedScopedTag["tagID"],
            $tagIDs,
            "Tag scoped to parent category should be inherited by child category."
        );
        $this->assertNotContains($unrelatedScopedTag["tagID"], $tagIDs, "Unrelated scoped tag should not be returned.");

        // Test 3: Filter by grandchild category - should return the inherited scoped tag (inheritance)
        $query = ["scopeType" => "global,scoped", "scope" => ["categoryIDs" => [$grandchildCategory["categoryID"]]]];
        $result = $this->api()
            ->get($this->baseUrl, $query)
            ->getBody();

        $tagIDs = array_column($result, "tagID");
        $this->assertContains($globalTag["tagID"], $tagIDs, "Global tags should always be returned.");
        $this->assertContains(
            $inheritedScopedTag["tagID"],
            $tagIDs,
            "Tag scoped to parent category should be inherited by grandchild category."
        );
        $this->assertNotContains($unrelatedScopedTag["tagID"], $tagIDs, "Unrelated scoped tag should not be returned.");

        // Test 4: Filter by multiple categories including both parent and child - should return the inherited scoped tag
        $query = [
            "scopeType" => "global,scoped",
            "scope" => ["categoryIDs" => [$parentCategory["categoryID"], $childCategory["categoryID"]]],
        ];
        $result = $this->api()
            ->get($this->baseUrl, $query)
            ->getBody();

        $tagIDs = array_column($result, "tagID");
        $this->assertContains($globalTag["tagID"], $tagIDs, "Global tags should always be returned.");
        $this->assertContains(
            $inheritedScopedTag["tagID"],
            $tagIDs,
            "Tag scoped to parent category should be returned when filtering by multiple categories."
        );
        $this->assertNotContains($unrelatedScopedTag["tagID"], $tagIDs, "Unrelated scoped tag should not be returned.");
    }

    /**
     * Test that category permissions are respected when filtering tags.
     */
    public function testIndexFilterWithPermissions()
    {
        $customPermCat = $this->createPermissionedCategory();
        $user = $this->createUserWithCategoryPermissions([$customPermCat], ["discussions.add" => false]);

        // Create a tag scoped to a category with custom permissions.
        $customPermCatTag = $this->createScopedTag(categoryIDs: [$customPermCat["categoryID"]]);

        $this->runWithUser(function () use ($customPermCat, $customPermCatTag) {
            $query = ["scope" => ["categoryIDs" => [$customPermCat["categoryID"]]]];
            $result = $this->api()
                ->get($this->baseUrl, $query)
                ->getBody();

            $tagIDs = is_array($result) ? array_column($result, "tagID") : [];
            $this->assertNotContains(
                $customPermCatTag["tagID"],
                $tagIDs,
                "Tag scoped to an inaccessible category should not be returned."
            );
        }, $user);
    }
}
