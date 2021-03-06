<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use UserModel;
use Vanilla\LongRunner;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/categories endpoints.
 */
class CategoriesTest extends AbstractResourceTest {

    use TestExpandTrait;
    use TestPrimaryKeyRangeFilterTrait;
    use TestFilterDirtyRecordsTrait;
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /** This category should never exist. */
    const BAD_CATEGORY_ID = 999;

    /** The standard parent category ID. */
    const PARENT_CATEGORY_ID = 1;

    /** @var CategoryModel */
    private static $categoryModel;

    /** @var int A value to ensure new records are unique. */
    protected static $recordCounter = 1;

    /** {@inheritdoc} */
    protected $baseUrl = '/categories';

    /** {@inheritdoc} */
    protected $editFields = ['description', 'name', 'parentCategoryID', 'urlcode', 'displayAs'];

    /** {@inheritdoc} */
    protected $patchFields = ['description', 'name', 'parentCategoryID', 'urlcode', 'displayAs'];

    /** {@inheritdoc} */
    protected $pk = 'categoryID';

    /** {@inheritdoc} */
    protected $singular = 'category';

    /** {@inheritdoc} */
    protected $testPagingOnIndex = false;

    /** @var MockObject&LongRunner */
    private $longRunner;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->container()->call(function (ContainerInterface $container, SystemTokenUtils $tokenUtils, SchedulerInterface $scheduler) {
            $this->longRunner = $this->getMockBuilder(LongRunner::class)
                ->enableOriginalConstructor()
                ->setConstructorArgs([$container, $tokenUtils, $scheduler])
                ->enableProxyingToOriginalMethods()
                ->onlyMethods(["runApi"])
                ->getMock();
        });
        $this->container()->setInstance(LongRunner::class, $this->longRunner);
    }

    /**
     * Fix some container setup issues of the breadcrumb model.
     */
    public static function setUpBeforeClass(): void {
        parent::setupBeforeClass();
        self::$categoryModel = self::container()->get(CategoryModel::class);
    }

    /**
     * There are no expandable user fields.
     */
    protected function getExpandableUserFields() {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function triggerDirtyRecords() {
        $this->resetTable('dirtyRecord');
        $category = $this->createCategory();
        $this->createDiscussion(['categoryID' => $category['categoryID']]);

        return $category;
    }

    /**
     * Get the resource type.
     *
     * @return array
     */
    protected function getResourceInformation(): array {
        return [
            "resourceType" => "category",
            "primaryKey" => "categoryID"
        ];
    }

    /**
     * Assert all dirty records for a specific resource are returned.
     *
     * INDEX /Categories by default returns a category tree (max 2 levels)
     * we must check if the child-categories are dirty-records as well.
     *
     * @param array $records
     */
    protected function assertAllDirtyRecordsReturned($records) {
        /** @var DirtyRecordModel $dirtyRecordModel */
        $dirtyRecordModel = \Gdn::getContainer()->get(DirtyRecordModel::class);
        $recordType = $this->getResourceInformation();
        $dirtyRecords = $dirtyRecordModel->select(["recordType" => $recordType]);

        // getCategoryTree will return a tree with dirtyRecords
        foreach ($records as $record) {
            if (count($record['children']) > 0) {
                $records = array_merge($records, $record['children']);
            }
        }

        $dirtyRecordIDs = array_column($dirtyRecords, 'recordID');
        $categoryIDs = array_column($records, 'categoryID');

        foreach ($dirtyRecordIDs as $dirtyRecordID) {
            $this->assertContains($dirtyRecordID, $categoryIDs);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row) {
        $row = parent::modifyRow($row);
        $dt = new \DateTimeImmutable();
        foreach ($this->patchFields as $key) {
            $value = $row[$key];
            switch ($key) {
                case 'urlcode':
                    $value = md5($value);
                    break;
                case 'displayAs':
                    $value = $value === 'flat' ? 'categories' : 'flat';
                    break;
            }
            $row[$key] = $value;
        }
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function indexUrl() {
        // Categories are created under a standard parent. For testing the index, make sure we're looking in the right place.
        return $this->baseUrl.'?parentCategoryID='.self::PARENT_CATEGORY_ID;
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        $count = static::$recordCounter;
        $name = "Test Category {$count}";
        $urlcode = strtolower(preg_replace('/[^A-Z0-9]/i', '-', $name));
        $record = [
            'name' => $name,
            'urlcode' => $urlcode,
            'parentCategoryID' => self::PARENT_CATEGORY_ID,
            'displayAs' => 'flat'
        ];
        static::$recordCounter++;
        return $record;
    }

    /**
     * Test getting only archived categories.
     */
    public function testFilterArchived() {
        // Make sure there'es at least one archived category.
        $archived = $this->testPost();
        self::$categoryModel->setField($archived['categoryID'], 'Archived', 1);

        $categories = $this->api()->get($this->baseUrl, [
            'archived' => true,
            'parentCategoryID' => self::PARENT_CATEGORY_ID
        ])->getBody();

        // Iterate through the results, detecting the archived status.
        $notArchived = 0;
        foreach ($categories as $category) {
            if ($category['isArchived'] !== true) {
                $notArchived++;
            }
        }

        // Verify no non-archived categories were included.
        $this->assertEquals(0, $notArchived);
    }

    /**
     * Test getting only categories that are not archived.
     */
    public function testFilterNotArchived() {
        // Make sure there's at least one archived category.
        $archived = $this->testPost();
        self::$categoryModel->setField($archived['categoryID'], 'Archived', 1);

        // Get only non-archived categories.
        $categories = $this->api()->get($this->baseUrl, [
            'archived' => false,
            'parentCategoryID' => self::PARENT_CATEGORY_ID
        ])->getBody();

        // Iterate through the results, detecting the archived status.
        $archived = 0;
        foreach ($categories as $category) {
            if ($category['isArchived'] === true) {
                $archived++;
            }
        }

        // Verify no archived categories were returned.
        $this->assertEquals(0, $archived);
    }

    /**
     * Test flagging (and unflagging) a category as followed by the current user.
     */
    public function testFollow() {
        $record = $this->record();
        $record['displayAs'] = 'discussions';
        $row = $this->testPost($record);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ['followed' => true]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertTrue($followBody['followed']);

        $index = $this->api()->get($this->baseUrl, ['parentCategoryID' => self::PARENT_CATEGORY_ID])->getBody();
        $categories = array_column($index, null, 'categoryID');
        $this->assertArrayHasKey($row['categoryID'], $categories);
        $this->assertTrue($categories[$row['categoryID']]['followed']);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ['followed' => false]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertFalse($followBody['followed']);

        $index = $this->api()->get($this->baseUrl, ['parentCategoryID' => self::PARENT_CATEGORY_ID])->getBody();
        $categories = array_column($index, null, 'categoryID');
        $this->assertFalse($categories[$row['categoryID']]['followed']);
    }

    /**
     * Test getting a list of followed categories.
     */
    public function testIndexFollowed() {
        // Make sure we're starting from scratch.
        $preFollow = $this->api()->get($this->baseUrl, ['followed' => true])->getBody();
        $this->assertEmpty($preFollow);

        // Follow. Make sure we're following.
        $testCategoryID = self::PARENT_CATEGORY_ID;
        $this->api()->put("{$this->baseUrl}/{$testCategoryID}/follow", ['followed' => true]);
        $postFollow = $this->api()->get($this->baseUrl, ['followed' => true])->getBody();
        $this->assertCount(1, $postFollow);
        $this->assertEquals($testCategoryID, $postFollow[0]['categoryID']);
    }

    /**
     * Ensure moving a category actually moves it and updates the new parent's category count.
     */
    public function testMove() {
        $parent = $this->api()->post(
            $this->baseUrl,
            [
                'name' => 'Test Parent Category',
                'urlcode' => 'test-parent-category',
                'parentCategoryID' => -1
            ]
        )->getBody();
        $row = $this->api()->post(
            $this->baseUrl,
            [
                'name' => 'Test Child Category',
                'urlcode' => 'test-child-category',
                'parentCategoryID' => self::PARENT_CATEGORY_ID
            ]
        )->getBody();

        $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            ['parentCategoryID' => $parent[$this->pk]]
        );

        $updatedRow = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}")->getBody();
        $updatedParent = $this->api()->get("{$this->baseUrl}/{$parent[$this->pk]}")->getBody();

        $this->assertEquals($parent['categoryID'], $updatedRow['parentCategoryID']);
        $this->assertEquals($parent['countCategories']+1, $updatedParent['countCategories']);
    }

    /**
     * Verify the proper exception is thrown when moving to a category that doesn't exist.
     */
    public function testMoveParentDoesNotExist() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The new parent category could not be found.');

        $row = $this->api()->post(
            $this->baseUrl,
            [
                'name' => 'Test Bad Parent',
                'urlcode' => 'test-bad-parent',
                'parentCategoryID' => self::PARENT_CATEGORY_ID
            ]
        )->getBody();
        $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            ['parentCategoryID' => self::BAD_CATEGORY_ID]
        );
    }

    /**
     * Verify the proper exception is thrown when trying to make a category the parent of itself.
     */
    public function testMoveSelfParent() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A category cannot be the parent of itself.');

        $row = $this->api()->post(
            $this->baseUrl,
            [
                'name' => 'Test Child Parent',
                'urlcode' => 'test-child-parent',
                'parentCategoryID' => self::PARENT_CATEGORY_ID
            ]
        )->getBody();
        $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            ['parentCategoryID' => $row[$this->pk]]
        );
    }

    /**
     * Verify the proper exception is thrown when trying to move a parent under one of its own children.
     */
    public function testMoveUnderChild() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot move a category under one of its own children.');

        $row = $this->api()->post(
            $this->baseUrl,
            [
                'name' => 'Test Parent as Child',
                'urlcode' => 'test-parent-as-child',
                'parentCategoryID' => self::PARENT_CATEGORY_ID
            ]
        )->getBody();
        $child = $this->api()->post(
            $this->baseUrl,
            [
                'name' => 'Test Child as Parent',
                'urlcode' => 'test-child-as-parent',
                'parentCategoryID' => $row[$this->pk]
            ]
        )->getBody();

        $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            ['parentCategoryID' => $child[$this->pk]]
        );
    }

    /**
     * Test getting both archived and non-archived categories.
     */
    public function testNoFilterArchived() {
        // Make sure there's at least one archived category.
        $archived = $this->testPost();
        self::$categoryModel->setField($archived['categoryID'], 'Archived', 1);

        // ...and one non-archived category.
        $notArchived = $this->testPost();

        // Get only non-archived categories.
        $categories = $this->api()->get($this->baseUrl, [
            'archived' => '',
            'parentCategoryID' => self::PARENT_CATEGORY_ID
        ])->getBody();

        // Iterate through the results, making sure both archived and non-archived categories are included.
        $archivedFound = false;
        $notArchivedFound = false;
        foreach ($categories as $category) {
            if ($archived['categoryID'] === $category['categoryID']) {
                $archivedFound = true;
            } elseif ($notArchived['categoryID'] === $category['categoryID']) {
                $notArchivedFound = true;
            }
        }

        // Verify we were able to locate the archived and non-archived categories.
        $this->assertTrue($archivedFound && $notArchivedFound);
    }

    /**
     * Test unfollowing a category after its display type has changed to something incompatible with following.
     */
    public function testUnfollowDisplay() {
        $record = $this->record();
        $record['displayAs'] = 'discussions';
        $row = $this->testPost($record);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ['followed' => true]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertTrue($followBody['followed']);

        $index = $this->api()->get($this->baseUrl, ['parentCategoryID' => self::PARENT_CATEGORY_ID])->getBody();
        $categories = array_column($index, null, 'categoryID');
        $this->assertArrayHasKey($row['categoryID'], $categories);
        $this->assertTrue($categories[$row['categoryID']]['followed']);

        $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", ['displayAs' => 'categories']);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ['followed' => false]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertFalse($followBody['followed']);

        $index = $this->api()->get($this->baseUrl, ['parentCategoryID' => self::PARENT_CATEGORY_ID])->getBody();
        $categories = array_column($index, null, 'categoryID');
        $this->assertFalse($categories[$row['categoryID']]['followed']);
    }

    /**
     * Make sure `GET /categories` doesn't allow invalid querystring parameters.
     */
    public function testOnlyOneOfIndexQuery(): void {
        $this->expectExceptionMessage('Only one of categoryID, archived, followed, featured are allowed.');
        $r = $this->api()->get($this->baseUrl, ['categoryID' => 123, 'followed' => true]);
    }

    /**
     * Verify behavior of deleting a category while moving its discussions to a new category.
     */
    public function testDeleteNewCategory(): void {
        $origCategory = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID]);
        $newCategory = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID]);

        $discussions = [];
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);

        $origDiscussions = $this->api()->get("discussions", ["categoryID" => $origCategory["categoryID"]])->getBody();
        $this->assertCount(count($discussions), $origDiscussions);

        $this->longRunner->expects($this->once())
            ->method("runApi")
            ->with(
                CategoryModel::class,
                "deleteIDIterable",
                [
                    $origCategory["categoryID"],
                    ["newCategoryID" => $newCategory["categoryID"]],
                ],
                []
            );

        $this->api()->delete(
            "{$this->baseUrl}/" . $origCategory["categoryID"],
            ["newCategoryID" => $newCategory["categoryID"]]
        );

        $newDiscussions = $this->api()->get("discussions", ["categoryID" => $newCategory["categoryID"]])->getBody();
        $this->assertCount(count($discussions), $newDiscussions);

        $this->expectException(NotFoundException::class);
        $this->api()->get("{$this->baseUrl}/" . $origCategory["categoryID"]);
    }

    /**
     * Verify ability to delete category content in batches.
     */
    public function testDeleteBatch(): void {
        $category = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID]);

        $this->longRunner->expects($this->once())
            ->method("runApi")
            ->with(
                CategoryModel::class,
                "deleteIDIterable",
                [
                    $category["categoryID"],
                    [],
                ],
                [LongRunner::OPT_LOCAL_JOB => false]
            );

        $this->api()->delete(
            "{$this->baseUrl}/" . $category["categoryID"],
            ["batch" => true]
        );

        $this->expectException(NotFoundException::class);
        $this->api()->get("{$this->baseUrl}/" . $category["categoryID"]);
    }

    /**
     * Verify ability to delete a category while moving its content in batches.
     */
    public function testDeleteNewCategoryBatch(): void {
        $origCategory = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID]);
        $newCategory = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID]);

        $discussions = [];
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);
        $discussions[] = $this->createDiscussion(["categoryID" => $origCategory["categoryID"]]);

        $origDiscussions = $this->api()->get("discussions", ["categoryID" => $origCategory["categoryID"]])->getBody();
        $this->assertCount(count($discussions), $origDiscussions);

        $this->longRunner->expects($this->once())
            ->method("runApi")
            ->with(
                CategoryModel::class,
                "deleteIDIterable",
                [
                    $origCategory["categoryID"],
                    ["newCategoryID" => $newCategory["categoryID"]],
                ],
                [LongRunner::OPT_LOCAL_JOB => false]
            );

        $this->api()->delete(
            "{$this->baseUrl}/" . $origCategory["categoryID"],
            [
                "batch" => true,
                "newCategoryID" => $newCategory["categoryID"]
            ]
        );
        $newDiscussions = $this->api()->get("discussions", ["categoryID" => $newCategory["categoryID"]])->getBody();
        $this->assertCount(count($discussions), $newDiscussions);

        $this->expectException(NotFoundException::class);
        $this->api()->get("{$this->baseUrl}/" . $origCategory["categoryID"]);
    }

    /**
     * Verify Category's countCategories
     */
    public function testCountCategories() {
        $firstGeneration = [];
        $secondGeneration = [];
        $thirdGeneration = [];

        $firstGenerationNumber = 2;
        $secondGenerationNumber = 3;
        $thirdGenerationNumber = 1;

        $parentCategoryId = $this->createCategory(["parentCategoryID" => CategoryModel::ROOT_ID])["categoryID"];

        for ($i = 0; $i < $firstGenerationNumber; $i++) {
            $firstGeneration[] = $childId = $this->createCategory(["parentCategoryID" => $parentCategoryId])["categoryID"];
            for ($j = 0; $j < $secondGenerationNumber; $j++) {
                $secondGeneration[] = $child2Id = $this->createCategory(["parentCategoryID" => $childId])["categoryID"];
                for ($k = 0; $k < $thirdGenerationNumber; $k++) {
                    $thirdGeneration[] = $this->createCategory(["parentCategoryID" => $child2Id])["categoryID"];
                }
            }
        }

        $verifications = [
            ['value' => $secondGenerationNumber, 'ids' => $firstGeneration],
            ['value' => $thirdGenerationNumber, 'ids' => $secondGeneration],
            ['value' => 0, 'ids' => $thirdGeneration],
        ];

        foreach ($verifications as $verification) {
            foreach ($verification['ids'] as $id) {
                $category = $this->api()->get("/categories/$id", [])->getBody();
                self::assertEquals($verification['value'], $category['countCategories']);
            }
        }
    }

    /**
     * Verify Category's countDiscussions
     */
    public function testCountDiscussions() {
        $categoryIds = [];
        $numCategories = 5;
        $numDiscussions = 5;

        for ($i = 0; $i < $numCategories; $i++) {
            $categoryIds[] = $id = $this->createCategory($i === 0 ? ["parentCategoryID" => CategoryModel::ROOT_ID] : [])["categoryID"];
            $category = $this->api()->get("/categories/$id", [])->getBody();
            self::assertEquals(0, $category['countDiscussions']);
            self::assertEquals(0, $category['countAllDiscussions']);
        }

        $bottomCategoryId = end($categoryIds); // redundant, but resilient

        for ($i = 0; $i < $numDiscussions; $i++) {
            $this->createDiscussion(["categoryID" => $bottomCategoryId]);
        }

        foreach ($categoryIds as $id) {
            $category = $this->api()->get("/categories/$id", [])->getBody();
            self::assertEquals($id === $bottomCategoryId ? $numDiscussions : 0, $category['countDiscussions']);
            self::assertEquals($numDiscussions, $category['countAllDiscussions']);
        }
    }

    /**
     * TestGetAllCategories
     */
    public function testGetAllCategories() {
        $categoriesBefore = count(CategoryModel::categories());
        $this->createCategory();
        $categoriesAfter = count(CategoryModel::categories());
        self::assertTrue($categoriesAfter === $categoriesBefore + 1);
    }

    /*
     * Test GET /categories/search.
     */
    public function testGetCategoriesSearch() {
        $this->resetTable('Category');
        $this->createCategory(['name' => 'category 1']);
        $this->createCategory(['name' => 'category 2']);
        $this->createCategory(['name' => 'not related']);
        $this->createCategory(['name' => 'not related']);

        $response = $this->api()->get("{$this->baseUrl}/search", ["query" => "category"])->getBody();

        $this->assertEquals(2, count($response));
        $this->assertIsArray($response);
    }

    /**
     * Verify ability to successfully retrieve a user's preferences for a single category.
     *
     * @param bool|null $following
     * @param bool|null $discussionsApp
     * @param bool|null $discussionsEmail
     * @param bool|null $commentsApp
     * @param bool|null $commentsEmail
     * @param string|null $expected
     * @dataProvider provideLegacyNotificationData
     */
    public function testNotificationPreferencesGet(
        ?bool $following,
        ?bool $discussionsApp,
        ?bool $discussionsEmail,
        ?bool $commentsApp,
        ?bool $commentsEmail,
        ?string $expected
    ): void {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->api()->setUserID($userID);

        if (is_bool($following)) {
            self::$categoryModel->follow($userID, $categoryID, $following);
        }

        /** @var \UserMetaModel $userMetaModel */
        $userMetaModel = $this->container()->get(\UserMetaModel::class);
        $userMetaModel->setUserMeta($userID, "Preferences.Popup.NewDiscussion.{$categoryID}", $discussionsApp);
        $userMetaModel->setUserMeta($userID, "Preferences.Email.NewDiscussion.{$categoryID}", $discussionsEmail);
        $userMetaModel->setUserMeta($userID, "Preferences.Popup.NewComment.{$categoryID}", $commentsApp);
        $userMetaModel->setUserMeta($userID, "Preferences.Email.NewComment.{$categoryID}", $commentsEmail);

        $preferences = $this->api()->get("{$this->baseUrl}/{$categoryID}/preferences/{$userID}")->getBody();
        $this->assertSame($expected, $preferences[CategoryModel::PREFERENCE_KEY_NOTIFICATION]);
    }

    /**
     * Provide data for verifying a user's legacy notification settings map to the proper notification value.
     *
     * @return array[]
     */
    public function provideLegacyNotificationData(): array {
        return [
            "Following, only" => [true, null, null, null, null, CategoryModel::NOTIFICATION_FOLLOW],
            "Email on comments, only" => [null, null, null, null, true, CategoryModel::NOTIFICATION_ALL],
            "Email on comments, following" => [true, null, null, null, true, CategoryModel::NOTIFICATION_ALL],
            "Email on discussions, only" => [null, null, true, null, null, CategoryModel::NOTIFICATION_DISCUSSIONS],
            "Email on discussions, following" => [true, null, true, null, null, CategoryModel::NOTIFICATION_DISCUSSIONS],
            "In-app on comments, only" => [null, null, null, true, null, CategoryModel::NOTIFICATION_ALL],
            "In-app on comments, following" => [true, null, null, true, null, CategoryModel::NOTIFICATION_ALL],
            "In-app on discussions, only" => [null, true, null, null, null, CategoryModel::NOTIFICATION_DISCUSSIONS],
            "In-app on discussions, following" => [true, true, null, null, null, CategoryModel::NOTIFICATION_DISCUSSIONS],
            "Email on comments and discussions" => [null, null, true, null, true, CategoryModel::NOTIFICATION_ALL],
            "Email on comments and discussions, following" => [true, null, true, null, true, CategoryModel::NOTIFICATION_ALL],
            "In-app on comments and discussions" => [null, true, null, true, null, CategoryModel::NOTIFICATION_ALL],
            "In-app on comments and discussions, following" => [true, true, null, true, null, CategoryModel::NOTIFICATION_ALL],
            "Email and in-app on comments and discussions" => [null, true, true, true, true, CategoryModel::NOTIFICATION_ALL],
            "Email and in-app on comments and discussions, following" => [true, true, true, true, true, CategoryModel::NOTIFICATION_ALL],
        ];
    }

    /**
     * Verify ability to update a user's legacy notification settings via the postNotifications preference.
     *
     * @param ?string $postNotifications
     * @param bool|null $expectedFollowing
     * @param bool|null $expectedDiscussionsApp
     * @param bool|null $expectedDiscussionsEmail
     * @param bool|null $expectedCommentsApp
     * @param bool|null $expectedCommentsEmail
     * @dataProvider providePostNotificationsData
     */
    public function testNotificationPreferencesSet(
        ?string $postNotifications,
        ?bool $expectedFollowing,
        ?bool $expectedDiscussionsApp,
        ?bool $expectedDiscussionsEmail,
        ?bool $expectedCommentsApp,
        ?bool $expectedCommentsEmail
    ): void {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->api()->setUserID($userID);

        $this->api()->patch(
            "{$this->baseUrl}/{$categoryID}/preferences/{$userID}",
            [CategoryModel::PREFERENCE_KEY_NOTIFICATION => $postNotifications]
        );

        $actualFollowing = self::$categoryModel->isFollowed($userID, $categoryID);
        $this->assertSame($expectedFollowing, $actualFollowing);

        /** @var \UserMetaModel $userMetaModel */
        $userMetaModel = $this->container()->get(\UserMetaModel::class);
        $preferences = [
            2 => "Preferences.Popup.NewDiscussion.%d",
            3 => "Preferences.Email.NewDiscussion.%d",
            4 => "Preferences.Popup.NewComment.%d",
            5 => "Preferences.Email.NewComment.%d",
        ];
        foreach ($preferences as $arg => $preference) {
            $expected = func_get_arg($arg);

            $key = sprintf($preference, $categoryID);
            $meta = $userMetaModel->getUserMeta($userID, $key);
            $actual = $meta[$key];
            $this->assertSame(
                $expected,
                $actual === null ? $actual : (bool)$actual
            );
        }
    }

    /**
     * Provide data for verifying a notification preference properly maps to legacy notification settings.
     *
     * @return array[]
     */
    public function providePostNotificationsData(): array {
        return [
            CategoryModel::NOTIFICATION_ALL => [CategoryModel::NOTIFICATION_ALL, true, true, true, true, true],
            CategoryModel::NOTIFICATION_DISCUSSIONS => [CategoryModel::NOTIFICATION_DISCUSSIONS, true, true, true, null, null],
            CategoryModel::NOTIFICATION_FOLLOW => [CategoryModel::NOTIFICATION_FOLLOW, true, null, null, null, null],
            "null" => [null, false, null, null, null, null],
        ];
    }

    /**
     * Verify listing out all of a user's category preferences.
     */
    public function testNotificationPreferencesIndex(): void {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->api()->setUserID($userID);

        self::$categoryModel->follow($userID, $categoryID, true);

        $response = $this->api()->get("{$this->baseUrl}/preferences/{$userID}")->getBody();
        $this->assertCount(1, $response);

        $actual = array_shift($response);
        $this->assertSame([
            "preferences" => [CategoryModel::PREFERENCE_KEY_NOTIFICATION => CategoryModel::NOTIFICATION_FOLLOW],
            "categoryID" => $categoryID,
            "name" => $category["name"],
            "url" => $category["url"],
        ], $actual);
    }

    /**
     * Verify disabling a user's notifications for a particular category.
     */
    public function testNotificationPreferencesNone(): void {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $this->api()->setUserID($userID);

        $preferences = $this->api()->get("{$this->baseUrl}/{$categoryID}/preferences/{$userID}")->getBody();
        $this->assertSame(null, $preferences[CategoryModel::PREFERENCE_KEY_NOTIFICATION]);
    }

    /**
     * Verify users with inadequate permissions cannot see other user's preferences.
     */
    public function testNotificationPreferencesNoPermission(): void {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $targetUser = $this->createUser();
        $targetUserID = $targetUser["userID"];
        $this->api()->setUserID($userID);

        $this->expectException(ForbiddenException::class);
        $this->api()->get("{$this->baseUrl}/{$categoryID}/preferences/{$targetUserID}")->getBody();
    }

    /**
     * Verify users with inadequate permissions cannot see other user's preferences.
     */
    public function testNotificationPreferencesNoPermissionIndex(): void {
        $user = $this->createUser();
        $userID = $user["userID"];
        $targetUser = $this->createUser();
        $targetUserID = $targetUser["userID"];
        $this->api()->setUserID($userID);

        $this->expectException(ForbiddenException::class);
        $this->api()->get("{$this->baseUrl}/preferences/{$targetUserID}")->getBody();
    }

    /**
     * Verify users with inadequate permissions cannot set other user's preferences.
     */
    public function testNotificationPreferencesNoPermissionPatch(): void {
        $category = $this->createCategory();
        $categoryID = $category["categoryID"];
        $user = $this->createUser();
        $userID = $user["userID"];
        $targetUser = $this->createUser();
        $targetUserID = $targetUser["userID"];
        $this->api()->setUserID($userID);

        $this->expectException(ForbiddenException::class);
        $this->api()->patch("{$this->baseUrl}/{$categoryID}/preferences/{$targetUserID}", [
            CategoryModel::PREFERENCE_KEY_NOTIFICATION => CategoryModel::NOTIFICATION_FOLLOW,
        ]);
    }
}
