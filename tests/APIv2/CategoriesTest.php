<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
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
     * Test flagging (and unflagging) a category as followed by the current user.
     */
    public function testFollow() {
        $record = $this->record();
        $record['displayAs'] = 'discussions';
        $row = $this->testPost($record);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ['follow' => true]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertTrue($followBody['follow']);

        $index = $this->api()->get($this->baseUrl, ['parentCategoryID' => self::PARENT_CATEGORY_ID])->getBody();
        $categories = array_column($index, null, 'categoryID');
        $this->assertArrayHasKey($row['categoryID'], $categories);
        $this->assertTrue($categories[$row['categoryID']]['follow']);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ['follow' => false]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertFalse($followBody['follow']);

        $index = $this->api()->get($this->baseUrl, ['parentCategoryID' => self::PARENT_CATEGORY_ID])->getBody();
        $categories = array_column($index, null, 'categoryID');
        $this->assertFalse($categories[$row['categoryID']]['follow']);
    }

    /**
     * {@inheritdoc}
     */
    public function testGetEdit($record = null) {
        $row = $this->testPost();
        $result = parent::testGetEdit($row);
        return $result;
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
     * Test unfollowing a category after its display type has changed to something incompatible with following.
     */
    public function testUnfollowDisplay() {
        $record = $this->record();
        $record['displayAs'] = 'discussions';
        $row = $this->testPost($record);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ['follow' => true]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertTrue($followBody['follow']);

        $index = $this->api()->get($this->baseUrl, ['parentCategoryID' => self::PARENT_CATEGORY_ID])->getBody();
        $categories = array_column($index, null, 'categoryID');
        $this->assertArrayHasKey($row['categoryID'], $categories);
        $this->assertTrue($categories[$row['categoryID']]['follow']);

        $this->api()->patch("{$this->baseUrl}/{$row[$this->pk]}", ['displayAs' => 'categories']);

        $follow = $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/follow", ['follow' => false]);
        $this->assertEquals(200, $follow->getStatusCode());
        $followBody = $follow->getBody();
        $this->assertFalse($followBody['follow']);

        $index = $this->api()->get($this->baseUrl, ['parentCategoryID' => self::PARENT_CATEGORY_ID])->getBody();
        $categories = array_column($index, null, 'categoryID');
        $this->assertFalse($categories[$row['categoryID']]['follow']);
    }
}
