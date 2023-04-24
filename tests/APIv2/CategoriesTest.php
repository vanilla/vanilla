<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoriesApiController;
use CategoryModel;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use UserModel;
use Vanilla\Scheduler\Job\LongRunnerJob;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/categories endpoints.
 */
class CategoriesTest extends AbstractResourceTest
{
    use TestExpandTrait;
    use TestPrimaryKeyRangeFilterTrait;
    use TestFilterDirtyRecordsTrait;
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use SchedulerTestTrait;
    use DatabaseTestTrait;

    /** This category should never exist. */
    const BAD_CATEGORY_ID = 999;

    /** The standard parent category ID. */
    const PARENT_CATEGORY_ID = 1;

    /** @var CategoryModel */
    private static $categoryModel;

    /** @var int A value to ensure new records are unique. */
    protected static $recordCounter = 1;

    /** {@inheritdoc} */
    protected $baseUrl = "/categories";

    /** {@inheritdoc} */
    protected $editFields = ["description", "name", "parentCategoryID", "urlcode", "displayAs", "iconUrl", "bannerUrl"];

    /** {@inheritdoc} */
    protected $patchFields = [
        "description",
        "name",
        "parentCategoryID",
        "urlcode",
        "displayAs",
        "iconUrl",
        "bannerUrl",
    ];

    protected $imageFields = ["bannerUrl", "iconUrl"];

    /** {@inheritdoc} */
    protected $pk = "categoryID";

    /** {@inheritdoc} */
    protected $singular = "category";

    /** {@inheritdoc} */
    protected $testPagingOnIndex = false;

    /**
     * Fix some container setup issues of the breadcrumb model.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setupBeforeClass();
        self::$categoryModel = self::container()->get(CategoryModel::class);
    }

    /**
     * There are no expandable user fields.
     */
    protected function getExpandableUserFields()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function triggerDirtyRecords()
    {
        $this->resetTable("dirtyRecord");
        $category = $this->createCategory();
        $this->createDiscussion(["categoryID" => $category["categoryID"]]);

        return $category;
    }

    /**
     * Get the resource type.
     *
     * @return array
     */
    protected function getResourceInformation(): array
    {
        return [
            "resourceType" => "category",
            "primaryKey" => "categoryID",
        ];
    }

    /**
     * Assert all dirty records for a specific resource are returned.
     *
     * @param array $records
     */
    protected function assertAllDirtyRecordsReturned($records)
    {
        /** @var DirtyRecordModel $dirtyRecordModel */
        $dirtyRecordModel = \Gdn::getContainer()->get(DirtyRecordModel::class);
        $recordType = $this->getResourceInformation();
        $dirtyRecords = $dirtyRecordModel->select(["recordType" => $recordType]);

        $dirtyRecordIDs = array_column($dirtyRecords, "recordID");
        $categoryIDs = array_column($records, "categoryID");

        foreach ($dirtyRecordIDs as $dirtyRecordID) {
            $this->assertContains($dirtyRecordID, $categoryIDs);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row)
    {
        $row = parent::modifyRow($row);
        foreach ($this->patchFields as $key) {
            $value = $row[$key];
            switch ($key) {
                case "urlcode":
                    $value = md5($value);
                    break;
                case "displayAs":
                    $value = $value === "flat" ? "categories" : "flat";
                    break;
            }
            $row[$key] = $value;
        }
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function indexUrl()
    {
        // Categories are created under a standard parent. For testing the index, make sure we're looking in the right place.
        return $this->baseUrl . "?parentCategoryID=" . self::PARENT_CATEGORY_ID;
    }

    /**
     * {@inheritdoc}
     */
    public function record()
    {
        $count = static::$recordCounter;
        $name = "Test Category {$count}";
        $urlcode = strtolower(preg_replace("/[^A-Z0-9]/i", "-", $name));
        $record = [
            "name" => $name,
            "urlcode" => $urlcode,
            "parentCategoryID" => self::PARENT_CATEGORY_ID,
            "displayAs" => "flat",
            "iconUrl" => null,
            "bannerUrl" => null,
        ];
        static::$recordCounter++;
        return $record;
    }

    /**
     * Test getting only archived categories.
     */
    public function testFilterArchived()
    {
        // Make sure there'es at least one archived category.
        $archived = $this->testPost();
        self::$categoryModel->setField($archived["categoryID"], "Archived", 1);

        $categories = $this->api()
            ->get($this->baseUrl, [
                "archived" => true,
                "parentCategoryID" => self::PARENT_CATEGORY_ID,
            ])
            ->getBody();

        // Iterate through the results, detecting the archived status.
        $notArchived = 0;
        foreach ($categories as $category) {
            if ($category["isArchived"] !== true) {
                $notArchived++;
            }
        }

        // Verify no non-archived categories were included.
        $this->assertEquals(0, $notArchived);
    }

    /**
     * Test getting only categories that are not archived.
     */
    public function testFilterNotArchived()
    {
        // Make sure there's at least one archived category.
        $archived = $this->testPost();
        self::$categoryModel->setField($archived["categoryID"], "Archived", 1);

        // Get only non-archived categories.
        $categories = $this->api()
            ->get($this->baseUrl, [
                "archived" => false,
                "parentCategoryID" => self::PARENT_CATEGORY_ID,
            ])
            ->getBody();

        // Iterate through the results, detecting the archived status.
        $archived = 0;
        foreach ($categories as $category) {
            if ($category["isArchived"] === true) {
                $archived++;
            }
        }

        // Verify no archived categories were returned.
        $this->assertEquals(0, $archived);
    }

    /**
     * Test flagging (and unflagging) a category as followed by the current user.
     */
    public function testFollow()
    {
        $record = $this->record();
        $record["displayAs"] = "discussions";
        $row = $this->testPost($record);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ["followed" => true]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertTrue($followBody["followed"]);

        $index = $this->api()
            ->get($this->baseUrl, ["parentCategoryID" => self::PARENT_CATEGORY_ID, "outputFormat" => "flat"])
            ->getBody();
        $categories = array_column($index, null, "categoryID");
        $this->assertArrayHasKey($row["categoryID"], $categories);
        $this->assertTrue($categories[$row["categoryID"]]["followed"]);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ["followed" => false]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertFalse($followBody["followed"]);

        $index = $this->api()
            ->get($this->baseUrl, ["parentCategoryID" => self::PARENT_CATEGORY_ID, "outputFormat" => "flat"])
            ->getBody();
        $categories = array_column($index, null, "categoryID");
        $this->assertFalse($categories[$row["categoryID"]]["followed"]);
    }

    /**
     * Test getting a list of followed categories.
     *
     * @depends testFollow
     */
    public function testIndexFollowed()
    {
        // Make sure we're starting from scratch.
        $preFollow = $this->api()
            ->get($this->baseUrl, ["followed" => true])
            ->getBody();
        $this->assertEmpty($preFollow);

        // Follow. Make sure we're following.
        $testCategoryID = self::PARENT_CATEGORY_ID;
        $this->api()->put("{$this->baseUrl}/{$testCategoryID}/follow", ["followed" => true]);
        $postFollow = $this->api()
            ->get($this->baseUrl, ["followed" => true])
            ->getBody();
        $this->assertCount(1, $postFollow);
        $this->assertEquals($testCategoryID, $postFollow[0]["categoryID"]);
        $this->assertEquals(true, $postFollow[0]["followed"]);
    }

    /**
     * Ensure moving a category actually moves it and updates the new parent's category count.
     */
    public function testMove()
    {
        $parent = $this->api()
            ->post($this->baseUrl, [
                "name" => "Test Parent Category",
                "urlcode" => "test-parent-category",
                "parentCategoryID" => -1,
            ])
            ->getBody();
        $row = $this->api()
            ->post($this->baseUrl, [
                "name" => "Test Child Category",
                "urlcode" => "test-child-category",
                "parentCategoryID" => self::PARENT_CATEGORY_ID,
            ])
            ->getBody();

        $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", ["parentCategoryID" => $parent[$this->pk]]);

        $updatedRow = $this->api()
            ->get("{$this->baseUrl}/{$row[$this->pk]}")
            ->getBody();
        $updatedParent = $this->api()
            ->get("{$this->baseUrl}/{$parent[$this->pk]}")
            ->getBody();

        $this->assertEquals($parent["categoryID"], $updatedRow["parentCategoryID"]);
        $this->assertEquals($parent["countCategories"] + 1, $updatedParent["countCategories"]);
    }

    /**
     * Verify the proper exception is thrown when moving to a category that doesn't exist.
     */
    public function testMoveParentDoesNotExist()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("The new parent category could not be found.");

        $row = $this->api()
            ->post($this->baseUrl, [
                "name" => "Test Bad Parent",
                "urlcode" => "test-bad-parent",
                "parentCategoryID" => self::PARENT_CATEGORY_ID,
            ])
            ->getBody();
        $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", ["parentCategoryID" => self::BAD_CATEGORY_ID]);
    }

    /**
     * Verify the proper exception is thrown when trying to make a category the parent of itself.
     */
    public function testMoveSelfParent()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("A category cannot be the parent of itself.");

        $row = $this->api()
            ->post($this->baseUrl, [
                "name" => "Test Child Parent",
                "urlcode" => "test-child-parent",
                "parentCategoryID" => self::PARENT_CATEGORY_ID,
            ])
            ->getBody();
        $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", ["parentCategoryID" => $row[$this->pk]]);
    }

    /**
     * Verify the proper exception is thrown when trying to move a parent under one of its own children.
     */
    public function testMoveUnderChild()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot move a category under one of its own children.");

        $row = $this->api()
            ->post($this->baseUrl, [
                "name" => "Test Parent as Child",
                "urlcode" => "test-parent-as-child",
                "parentCategoryID" => self::PARENT_CATEGORY_ID,
            ])
            ->getBody();
        $child = $this->api()
            ->post($this->baseUrl, [
                "name" => "Test Child as Parent",
                "urlcode" => "test-child-as-parent",
                "parentCategoryID" => $row[$this->pk],
            ])
            ->getBody();
        $child2 = $this->api()
            ->post($this->baseUrl, [
                "name" => "Test Child as Parent",
                "urlcode" => "second-test-child-as-parent",
                "parentCategoryID" => $child[$this->pk],
            ])
            ->getBody();

        $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", ["parentCategoryID" => $child2[$this->pk]]);
    }

    /**
     * Test getting both archived and non-archived categories.
     */
    public function testNoFilterArchived()
    {
        // Make sure there's at least one archived category.
        $archived = $this->testPost();
        self::$categoryModel->setField($archived["categoryID"], "Archived", 1);

        // ...and one non-archived category.
        $notArchived = $this->testPost();

        // Get only non-archived categories.
        $categories = $this->api()
            ->get($this->baseUrl, [
                "archived" => "",
                "parentCategoryID" => self::PARENT_CATEGORY_ID,
                "outputFormat" => "flat",
            ])
            ->getBody();

        // Iterate through the results, making sure both archived and non-archived categories are included.
        $archivedFound = false;
        $notArchivedFound = false;
        foreach ($categories as $category) {
            if ($archived["categoryID"] === $category["categoryID"]) {
                $archivedFound = true;
            } elseif ($notArchived["categoryID"] === $category["categoryID"]) {
                $notArchivedFound = true;
            }
        }

        // Verify we were able to locate the archived and non-archived categories.
        $this->assertTrue($archivedFound && $notArchivedFound);
    }

    /**
     * Test unfollowing a category after its display type has changed to something incompatible with following.
     */
    public function testUnfollowDisplay()
    {
        $record = $this->record();
        $record["displayAs"] = "discussions";
        $row = $this->testPost($record);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ["followed" => true]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertTrue($followBody["followed"]);

        $index = $this->api()
            ->get($this->baseUrl, ["parentCategoryID" => self::PARENT_CATEGORY_ID, "outputFormat" => "flat"])
            ->getBody();
        $categories = array_column($index, null, "categoryID");
        $this->assertArrayHasKey($row["categoryID"], $categories);
        $this->assertTrue($categories[$row["categoryID"]]["followed"]);

        $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", ["displayAs" => "categories"]);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ["followed" => false]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertFalse($followBody["followed"]);

        $index = $this->api()
            ->get($this->baseUrl, ["parentCategoryID" => self::PARENT_CATEGORY_ID, "outputFormat" => "flat"])
            ->getBody();
        $categories = array_column($index, null, "categoryID");
        $this->assertFalse($categories[$row["categoryID"]]["followed"]);
    }

    /**
     * Make sure `GET /categories` doesn't allow invalid querystring parameters.
     */
    public function testOnlyOneOfIndexQuery(): void
    {
        $this->expectErrorMessage(CategoriesApiController::ERRORINDEXMSG);
        $r = $this->api()->get($this->baseUrl, ["outputFormat" => "flat", "maxDepth" => 2]);
    }

    /**
     * Verify behavior of deleting a category while moving its discussions to a new category.
     */
    public function testDeleteNewCategory(): void
    {
        $origCategory = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID]);
        $newCategory = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID]);

        $discussions = [];
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);

        $origDiscussions = $this->api()
            ->get("discussions", ["categoryID" => $origCategory["categoryID"]])
            ->getBody();
        $this->assertCount(count($discussions), $origDiscussions);

        $this->api()->delete("{$this->baseUrl}/" . $origCategory["categoryID"], [
            "newCategoryID" => $newCategory["categoryID"],
        ]);

        $newDiscussions = $this->api()
            ->get("discussions", ["categoryID" => $newCategory["categoryID"]])
            ->getBody();
        $this->assertCount(count($discussions), $newDiscussions);

        $this->expectException(NotFoundException::class);
        $this->api()->get("{$this->baseUrl}/" . $origCategory["categoryID"]);
    }

    /**
     * Verify ability to delete category content in batches.
     */
    public function testDeleteBatches(): void
    {
        $category = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID]);
        $disc1 = $this->createDiscussion();
        $disc2 = $this->createDiscussion();

        // 2 Discussion records.
        $whereCategoryID = ["CategoryID" => $category["categoryID"]];
        $this->assertRecordsFound("Discussion", $whereCategoryID, 2);

        $this->getLongRunner()->setMaxIterations(1);
        $response = $this->api()->delete("{$this->baseUrl}/" . $category["categoryID"], [], [], ["throw" => false]);
        $this->assertEquals(408, $response->getStatusCode());

        // 1 discussion is deleleted.
        $this->assertRecordsFound("Discussion", $whereCategoryID, 1);
        $this->assertRecordsFound("Category", $whereCategoryID, 1);

        // Continue.
        $response = $this->getLongRunner()
            ->reset()
            ->runApi(
                LongRunnerAction::fromCallbackPayload(
                    $response->getBody()["callbackPayload"],
                    self::container()->getArgs(SystemTokenUtils::class),
                    \Gdn::request()
                )
            );
        $this->assertEquals(200, $response->getStatus());

        $this->assertNoRecordsFound("Discussion", $whereCategoryID);
        $this->assertNoRecordsFound("Category", $whereCategoryID);
    }

    /**
     * Test that the deprecated "batch" parameter results in an async long runner mode.
     */
    public function testDeleteBatchParam()
    {
        $category = $this->createCategory();
        $this->api()->delete("/categories/{$category["categoryID"]}", ["batch" => true]);
        $this->assertEquals(LongRunner::MODE_ASYNC, $this->getLongRunner()->getMode());
        $this->getScheduler()->assertJobScheduled(LongRunnerJob::class);
    }

    /**
     * Verify Category's countCategories
     */
    public function testCountCategories()
    {
        $firstGeneration = [];
        $secondGeneration = [];
        $thirdGeneration = [];

        $firstGenerationNumber = 2;
        $secondGenerationNumber = 3;
        $thirdGenerationNumber = 1;

        $parentCategoryId = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID])["categoryID"];

        for ($i = 0; $i < $firstGenerationNumber; $i++) {
            $firstGeneration[] = $childId = $this->createCategory(["parentCategoryID" => $parentCategoryId])[
                "categoryID"
            ];
            for ($j = 0; $j < $secondGenerationNumber; $j++) {
                $secondGeneration[] = $child2Id = $this->createCategory(["parentCategoryID" => $childId])["categoryID"];
                for ($k = 0; $k < $thirdGenerationNumber; $k++) {
                    $thirdGeneration[] = $this->createCategory(["parentCategoryID" => $child2Id])["categoryID"];
                }
            }
        }

        $verifications = [
            ["value" => $secondGenerationNumber, "ids" => $firstGeneration],
            ["value" => $thirdGenerationNumber, "ids" => $secondGeneration],
            ["value" => 0, "ids" => $thirdGeneration],
        ];

        foreach ($verifications as $verification) {
            foreach ($verification["ids"] as $id) {
                $category = $this->api()
                    ->get("/categories/$id", [])
                    ->getBody();
                self::assertEquals($verification["value"], $category["countCategories"]);
            }
        }
    }

    /**
     * Verify Category's countDiscussions
     */
    public function testCountDiscussions()
    {
        $categoryIds = [];
        $numCategories = 5;
        $numDiscussions = 5;

        for ($i = 0; $i < $numCategories; $i++) {
            $categoryIds[] = $id = $this->createCategory(
                $i === 0 ? ["parentCategoryID" => CategoryModel::ROOT_ID] : []
            )["categoryID"];
            $category = $this->api()
                ->get("/categories/$id", [])
                ->getBody();
            self::assertEquals(0, $category["countDiscussions"]);
            self::assertEquals(0, $category["countAllDiscussions"]);
        }

        $bottomCategoryId = end($categoryIds); // redundant, but resilient

        for ($i = 0; $i < $numDiscussions; $i++) {
            $this->createDiscussion(["categoryID" => $bottomCategoryId]);
        }

        foreach ($categoryIds as $id) {
            $category = $this->api()
                ->get("/categories/$id", [])
                ->getBody();
            self::assertEquals($id === $bottomCategoryId ? $numDiscussions : 0, $category["countDiscussions"]);
            self::assertEquals($numDiscussions, $category["countAllDiscussions"]);
        }
    }

    /**
     * TestGetAllCategories
     */
    public function testGetAllCategories()
    {
        $categoriesBefore = count(CategoryModel::categories());
        $this->createCategory();
        $categoriesAfter = count(CategoryModel::categories());
        self::assertTrue($categoriesAfter === $categoriesBefore + 1);
    }

    /**
     * Test GET /categories/search.
     */
    public function testCategoriesSearch()
    {
        // notably these are in a tree structure.
        $this->resetTable("Category");
        $this->createCategory(["name" => "category 1"]);
        $cat2 = $this->createCategory(["name" => "category 2"]);
        $this->createCategory(["name" => "not related"]);
        $this->createPermissionedCategory(["name" => "not related"], [\RoleModel::ADMIN_ID]);
        $this->createCategory(["name" => "very nested category"]);

        $this->assertApiResults(
            "/categories/search",
            ["query" => "category"],
            [
                "name" => ["category 1", "category 2", "very nested category"],
            ]
        );

        // Check filtering a parent category.
        $this->assertApiResults(
            "/categories/search",
            ["query" => "category", "parentCategoryID" => $cat2["categoryID"]],
            [
                "name" => ["category 2", "very nested category"],
            ]
        );

        // Check permissions.
        $this->runWithUser(function () {
            // Different user. Clear out the local cache.
            CategoryModel::clearCache();
            $this->assertApiResults(
                "/categories/search",
                ["query" => "category"],
                [
                    "name" => ["category 1", "category 2"],
                ]
            );
        }, UserModel::GUEST_USER_ID);
    }

    /**
     * Test that featured categories don't have any depth restrictions.
     *
     * @see https://github.com/vanilla/support/issues/4919
     */
    public function testNestedFeaturedCategories()
    {
        $rootCategory = $this->createCategory();
        $this->createCategory(["featured" => true]);
        $this->createCategory(["featured" => true]);
        $this->createCategory(["featured" => true]);
        $this->createCategory(["featured" => true]);

        $result = $this->api()
            ->get("/categories", ["featured" => true, "parentCategoryID" => $rootCategory["categoryID"]])
            ->getBody();

        $this->assertCount(4, $result);
    }

    /**
     * Test that the outputFormat is properly enforced on /categories
     */
    public function testGetWithOutputFormat()
    {
        $parentCategoryID = $this->createCategory()["categoryID"];
        $this->createCategory(["parentCategoryID" => $parentCategoryID]);

        $result = $this->api()
            ->get("/categories", ["outputFormat" => "tree"])
            ->getBody();
        $this->assertArrayHasKey("children", $result[0]);

        $result = $this->api()
            ->get("/categories", ["outputFormat" => "flat", "parentCategoryID" => $parentCategoryID])
            ->getBody();
        $this->assertEquals(2, count($result));
    }

    /**
     * Test that max depth works.
     */
    public function testMaxDepth()
    {
        $cat1 = $this->createCategory(["name" => "depth1"]);
        $cat2 = $this->createCategory(["name" => "depth2"]);
        $this->createCategory(["name" => "depth3"]);
        $this->createCategory(["name" => "depth4"]);

        // Default
        $this->assertCategoriesInIndex([], ["depth1", "depth2"], ["depth3", "depth4"]);

        // From a parent
        $this->assertCategoriesInIndex(
            [
                "parentCategoryID" => $cat1["categoryID"],
                "outputFormat" => "tree",
            ],
            ["depth1", "depth2", "depth3"],
            ["depth4"]
        );

        // From a parent
        $this->assertCategoriesInIndex(
            [
                "parentCategoryID" => $cat2["categoryID"],
                "maxDepth" => 1,
            ],
            ["depth2", "depth3"],
            ["depth1", "depth4"]
        );
    }

    /**
     * Test that the endpoint filters permissions.
     */
    public function testPermissionFilter()
    {
        $cat1 = $this->createCategory(["name" => "cat1"]);
        $permCat = $this->createPermissionedCategory(["name" => "permcat"], [\RoleModel::ADMIN_ID]);

        $query = [
            "categoryID" => [$cat1["categoryID"], $permCat["categoryID"]],
        ];
        $this->assertCategoriesInIndex($query, ["cat1", "permcat"]);
        $this->runWithUser(function () use ($query) {
            $this->assertCategoriesInIndex($query, ["cat1"], ["permcat"]);
        }, UserModel::GUEST_USER_ID);
    }

    /**
     * Assert that certain category names come back in the index on a particular query.
     *
     * @param array $query
     * @param array $includedNames
     * @param array $excludedNames
     */
    private function assertCategoriesInIndex(array $query, array $includedNames, array $excludedNames = [])
    {
        $results = $this->api()
            ->get("/categories", $query)
            ->getBody();
        $results = \CategoryCollection::treeBuilder()
            ->setChildrenFieldName("children")
            ->flattenTree($results);

        $names = array_column($results, "name");
        $flippedNames = array_flip($names);
        foreach ($includedNames as $includedName) {
            $this->assertArrayHasKey($includedName, $flippedNames);
        }

        foreach ($excludedNames as $excludedName) {
            $this->assertArrayNotHasKey($excludedName, $flippedNames);
        }
    }

    /**
     * Test that description lengths are properly served as a validation error.
     *
     * @see https://higherlogic.atlassian.net/browse/VNLA-2316
     */
    public function testTooLongDescription()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("Description is 201 characters too long.");
        $this->createCategory([
            "description" =>
                "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras placerat aliquet ligula eget finibus. Suspendisse porttitor lorem eu lacus scelerisque, eu efficitur ante tincidunt. Suspendisse potenti. Curabitur porta massa lacus. Nulla facilisi. Vestibulum cursus, eros ut congue porttitor, magna enim eleifend urna, id tempor ipsum elit tincidunt risus. Vivamus felis elit, elementum vitae purus commodo, tempus posuere odio. Vivamus nec tellus faucibus, bibendum nisl vitae, aliquet lacus. Etiam ex felis, ullamcorper sit amet sem ut, vulputate laoreet quam. Donec ullamcorper ultricies dui, at cursus nulla fermentum et. Etiam at ipsum sit amet sem tincidunt volutpat quis vitae sapien. Cras ac felis venenatis tellus tempor euismod.Vestibulum nec massa at velit faucibus sodales vitae sit amet tortor. Morbi pretium euismod massa, vel volutpat urna luctus at. Sed posuere orci non mi vestibulum condimentum. Nam imperdiet nunc ac odio varius condimentum. Nam nec turpis tempus, luctus odio nec, semper ipsum. Donec vestibulum lorem ac interdum luctus. Praesent vel magna libero. Pellentesque mattis turpis a facilisis fringilla. Phasellus scelerisque arcu ligula, vel pellentesque nunc placerat eu.",
        ]);
    }
    /**
     * Test that using emojis won't break validation .
     *
     * @see https://higherlogic.atlassian.net/browse/VNLA-2316
     */
    public function testLongEmojiStringInDescription()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("Description is 4 characters too long.");
        $this->createCategory([
            "description" =>
                "👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦👩‍👧‍👧👩‍👧‍👦‍👩‍👧‍👧👩‍👧‍👦‍👩‍👧‍👦",
        ]);
    }
}
