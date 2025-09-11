<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard\Scope;

use VanillaTests\Fixtures\MockSiteSection;
use VanillaTests\Fixtures\MockSiteSectionProvider;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use Vanilla\Dashboard\Scope\ScopeHelper;
use Vanilla\Dashboard\Scope\Models\ScopeModel;

/**
 * Test cases for ScopeHelper class.
 */
class ScopeHelperTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /** @var ScopeHelper  */
    private $scopeHelper;

    /** @var MockSiteSectionProvider  */
    protected $mockSiteSectionProvider;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->scopeHelper = self::container()->get(ScopeHelper::class);
        $this->mockSiteSectionProvider = self::container()->get(MockSiteSectionProvider::class);

        \Gdn::sql()->truncate("Tag");
        \Gdn::sql()->truncate("scope");
    }

    /**
     * Test applyScopeToRecord with null scope.
     */
    public function testApplyScopeToRecordWithNullScope(): void
    {
        // Create a test tag
        $tag = $this->createTag(["name" => "Test Tag"]);
        $tagID = $tag["tagID"];

        // Should not throw any errors when scope is null
        $this->scopeHelper->applyScopeToRecord("tag", $tagID, null);

        // Verify no scope records were created
        $scopeRecords = $this->getScopeRecords("tag", $tagID);
        $this->assertEmpty($scopeRecords);
    }

    /**
     * Test applyScopeToRecord with valid scope data.
     */
    public function testApplyScopeToRecordWithValidScope(): void
    {
        // Create test data
        $tag = $this->createTag(["name" => "Test Tag"]);
        $tagID = $tag["tagID"];
        $category1 = $this->createCategory(["name" => "Category 1"]);
        $categoryID1 = $category1["categoryID"];
        $category2 = $this->createCategory(["name" => "Category 2"]);
        $categoryID2 = $category2["categoryID"];
        $siteSectionID = "test-section";

        $scope = [
            "categoryIDs" => [$categoryID1, $categoryID2],
            "siteSectionIDs" => [$siteSectionID],
        ];

        $this->scopeHelper->applyScopeToRecord("tag", $tagID, $scope);

        // Verify scope records were created
        $scopeRecords = $this->getScopeRecords("tag", $tagID);
        $this->assertCount(3, $scopeRecords);

        // Verify category scope records
        $categoryRecords = array_filter($scopeRecords, function ($record) {
            return $record["scopeRecordType"] === ScopeModel::SCOPE_RECORD_TYPE_CATEGORY;
        });
        $this->assertCount(2, $categoryRecords);

        // Verify site section scope records
        $siteSectionRecords = array_filter($scopeRecords, function ($record) {
            return $record["scopeRecordType"] === ScopeModel::SCOPE_RECORD_TYPE_SITE_SECTION;
        });
        $this->assertCount(1, $siteSectionRecords);
    }

    /**
     * Test applyScopeToRecord with empty scope data.
     */
    public function testApplyScopeToRecordWithEmptyScope(): void
    {
        // Create a test tag
        $tag = $this->createTag(["name" => "Test Tag"]);
        $tagID = $tag["tagID"];

        // Apply empty scope
        $this->scopeHelper->applyScopeToRecord("tag", $tagID, []);

        // Verify no scope records were created
        $scopeRecords = $this->getScopeRecords("tag", $tagID);
        $this->assertEmpty($scopeRecords);
    }

    /**
     * Test clearScopeData method.
     */
    public function testClearScopeData(): void
    {
        // Create test data
        $tag = $this->createTag(["name" => "Test Tag"]);
        $tagID = $tag["tagID"];
        $category = $this->createCategory(["name" => "Category 1"]);
        $categoryID = $category["categoryID"];

        // Apply scope first
        $scope = ["categoryIDs" => [$categoryID]];
        $this->scopeHelper->applyScopeToRecord("tag", $tagID, $scope);

        // Verify scope records exist
        $scopeRecords = $this->getScopeRecords("tag", $tagID);
        $this->assertNotEmpty($scopeRecords);

        // Clear scope data
        $this->scopeHelper->clearScopeData("tag", $tagID);

        // Verify scope records were removed
        $scopeRecords = $this->getScopeRecords("tag", $tagID);
        $this->assertEmpty($scopeRecords);
    }

    /**
     * Test getRecordsScope with valid record IDs.
     */
    public function testGetRecordsScopeWithValidRecordIDs(): void
    {
        // Create test data
        $tag1 = $this->createTag(["name" => "Tag 1"]);
        $tagID1 = $tag1["tagID"];
        $tag2 = $this->createTag(["name" => "Tag 2"]);
        $tagID2 = $tag2["tagID"];
        $category = $this->createCategory(["name" => "Category 1"]);
        $categoryID = $category["categoryID"];

        // Apply scope to first tag
        $scope = ["categoryIDs" => [$categoryID]];
        $this->scopeHelper->applyScopeToRecord("tag", $tagID1, $scope);

        $result = $this->scopeHelper->getRecordsScope("tag", [$tagID1, $tagID2]);

        // Verify result structure
        $this->assertArrayHasKey($tagID1, $result);

        // Verify first tag has scope records
        $this->assertArrayHasKey("scope", $result[$tagID1]);
        $this->assertArrayHasKey("categoryIDs", $result[$tagID1]["scope"]);
        $this->assertContains($categoryID, $result[$tagID1]["scope"]["categoryIDs"]);

        // Verify second tag has no scope records
        $this->assertArrayNotHasKey($tagID2, $result);
    }

    /**
     * Test joinScopes with empty rows.
     */
    public function testJoinScopesWithEmptyRows(): void
    {
        $result = $this->scopeHelper->joinScopes("tag", []);
        $this->assertEquals([], $result);
    }

    /**
     * Test joinScopes with valid rows and scope data.
     */
    public function testJoinScopesWithValidRows(): void
    {
        // Create test data
        $tag1 = $this->createTag(["name" => "Tag 1"]);
        $tagID1 = $tag1["tagID"];
        $tag2 = $this->createTag(["name" => "Tag 2"]);
        $tagID2 = $tag2["tagID"];
        $tag3 = $this->createTag(["name" => "Tag 3"]);
        $tagID3 = $tag3["tagID"];
        $category = $this->createCategory(["name" => "Category 1"]);
        $categoryID = $category["categoryID"];
        $siteSectionID = "test-section";

        // Apply scope to first tag (categories)
        $scope1 = ["categoryIDs" => [$categoryID]];
        $this->scopeHelper->applyScopeToRecord("tag", $tagID1, $scope1);

        // Apply scope to second tag (site sections)
        $scope2 = ["siteSectionIDs" => [$siteSectionID]];
        $this->scopeHelper->applyScopeToRecord("tag", $tagID2, $scope2);

        $rows = [
            ["tagID" => $tagID1, "name" => "Tag 1"],
            ["tagID" => $tagID2, "name" => "Tag 2"],
            ["tagID" => $tagID3, "name" => "Tag 3"],
        ];

        $result = $this->scopeHelper->joinScopes("tag", $rows, "tagID");

        // Verify first tag has category scope
        $this->assertArrayHasKey("scope", $result[0]);
        $this->assertArrayHasKey("categoryIDs", $result[0]["scope"]);
        $this->assertEquals([$categoryID], $result[0]["scope"]["categoryIDs"]);

        // Verify second tag has site section scope
        $this->assertArrayHasKey("scope", $result[1]);
        $this->assertArrayHasKey("siteSectionIDs", $result[1]["scope"]);
        $this->assertEquals([$siteSectionID], $result[1]["scope"]["siteSectionIDs"]);

        // Verify third tag has no scope
        $this->assertArrayNotHasKey("scope", $result[2]);
    }

    /**
     * Test joinScopes with custom ID column.
     */
    public function testJoinScopesWithCustomIDColumn(): void
    {
        // Create test data
        $tag1 = $this->createTag(["name" => "Tag 1"]);
        $tagID1 = $tag1["tagID"];
        $tag2 = $this->createTag(["name" => "Tag 2"]);
        $tagID2 = $tag2["tagID"];
        $category = $this->createCategory(["name" => "Category 1"]);
        $categoryID = $category["categoryID"];

        // Apply scope to first tag
        $scope = ["categoryIDs" => [$categoryID]];
        $this->scopeHelper->applyScopeToRecord("tag", $tagID1, $scope);

        $rows = [["tagID" => $tagID1, "name" => "Tag 1"], ["tagID" => $tagID2, "name" => "Tag 2"]];

        $result = $this->scopeHelper->joinScopes("tag", $rows, "tagID");

        // Verify first tag has scope
        $this->assertArrayHasKey("scope", $result[0]);
        $this->assertArrayHasKey("categoryIDs", $result[0]["scope"]);
        $this->assertEquals([$categoryID], $result[0]["scope"]["categoryIDs"]);

        // Verify second tag has no scope
        $this->assertArrayNotHasKey("scope", $result[1]);
    }

    /**
     * Test resolveScopeRecordIDs with user having access to all categories.
     */
    public function testResolveScopeRecordIDsWithAllCategoriesAccess(): void
    {
        // Create test data
        $category1 = $this->createCategory(["name" => "Category 1"]);
        $categoryID1 = $category1["categoryID"];
        $category2 = $this->createCategory(["name" => "Category 2"]);
        $categoryID2 = $category2["categoryID"];

        $scope = [
            "categoryIDs" => [$categoryID1, $categoryID2],
            "siteSectionIDs" => ["section1", "section2"],
        ];

        $result = $this->scopeHelper->resolveScopeRecordIDs($scope);

        $this->assertEquals([$categoryID1, $categoryID2], $result[0]);
        $this->assertEquals(["section1", "section2"], $result[1]);
    }

    /**
     * Test resolveScopeRecordIDs with limited category access.
     */
    public function testResolveScopeRecordIDsWithLimitedCategoryAccess(): void
    {
        // Create test categories
        $category1 = $this->createCategory(["name" => "Category 1"]);
        $categoryID1 = $category1["categoryID"];
        $category2 = $this->createCategory(["name" => "Category 2"]);
        $categoryID2 = $category2["categoryID"];
        $category3 = $this->createCategory(["name" => "Category 4"]);
        $categoryID3 = $category3["categoryID"];

        $mockSiteSection = new MockSiteSection(
            "test",
            "en",
            "/" . __FUNCTION__,
            "testResolveScopeRecordIDsWithLimitedCategoryAccess",
            __FUNCTION__,
            categoryID: $categoryID3
        );
        $this->mockSiteSectionProvider->addSiteSections([$mockSiteSection]);

        $this->runWithPermissions(
            function () use ($categoryID1, $categoryID2, $categoryID3) {
                $scope = [
                    "categoryIDs" => [$categoryID1, $categoryID2, $categoryID3],
                    "siteSectionIDs" => ["testResolveScopeRecordIDsWithLimitedCategoryAccess"],
                ];

                $result = $this->scopeHelper->resolveScopeRecordIDs($scope);

                // We filtered by categories 1, 2 and 3, but we should only get 2 and 3 because those are the only categories the user has access to.
                $this->assertEquals([$categoryID2, $categoryID3], $result[0]);

                // We should also get the site section associated with category 3.
                $this->assertEquals(["testResolveScopeRecordIDsWithLimitedCategoryAccess"], $result[1]);
            },
            [],
            $this->categoryPermission($categoryID2, ["discussions.view" => true, "discussions.add" => true]),
            $this->categoryPermission($categoryID3, ["discussions.view" => true, "discussions.add" => true])
        );
    }

    /**
     * Test resolveScopeRecordIDs with no category filter.
     */
    public function testResolveScopeRecordIDsWithNoCategoryFilter(): void
    {
        $scope = [
            "siteSectionIDs" => ["section1", "section2"],
        ];

        $result = $this->scopeHelper->resolveScopeRecordIDs($scope);

        // An admin should receive the exact same filters that were passed in.
        $this->assertSame([null, ["section1", "section2"]], $result);
    }

    /**
     * Test resolveScopeRecordIDs with no site section filter.
     */
    public function testResolveScopeRecordIDsWithNoSiteSectionFilter(): void
    {
        // Create test categories
        $category1 = $this->createCategory(["name" => "Category 1"]);
        $categoryID1 = $category1["categoryID"];
        $category2 = $this->createCategory(["name" => "Category 2"]);
        $categoryID2 = $category2["categoryID"];

        $scope = [
            "categoryIDs" => [$categoryID1, $categoryID2],
        ];

        $result = $this->scopeHelper->resolveScopeRecordIDs($scope);

        // An admin should receive the exact same filters that were passed in.
        $this->assertSame([[$categoryID1, $categoryID2], null], $result);
    }

    /**
     * Test resolveScopeRecordIDs with empty scope.
     */
    public function testResolveScopeRecordIDsWithEmptyScopeAsAdmin(): void
    {
        $scope = [];

        $result = $this->scopeHelper->resolveScopeRecordIDs($scope);

        // As admin should return tuple of nulls (no filtering needed).
        $this->assertSame([null, null], $result);
    }

    /**
     * Helper method to get scope records for a record.
     */
    private function getScopeRecords(string $recordType, int $recordID): array
    {
        $scopeModel = self::container()->get(ScopeModel::class);
        return $scopeModel->select([
            "recordType" => $recordType,
            "recordID" => $recordID,
            "relationType" => ScopeModel::RELATION_TYPE_SCOPE,
        ]);
    }
}
