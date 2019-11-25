<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;

/**
 * Test the /api/v2/categories endpoints.
 */
class CategoriesTest extends AbstractResourceTest {

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
                case 'displayAs':
                    $value = $value === 'flat' ? 'categories' : 'flat';
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
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        self::$categoryModel = self::container()->get(CategoryModel::class);
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
     *
     * @expectedException \Exception
     * @expectedExceptionMessage The new parent category could not be found.
     */
    public function testMoveParentDoesNotExist() {
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
     *
     * @expectedException \Exception
     * @expectedExceptionMessage A category cannot be the parent of itself.
     */
    public function testMoveSelfParent() {
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
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Cannot move a category under one of its own children.
     */
    public function testMoveUnderChild() {
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
}
