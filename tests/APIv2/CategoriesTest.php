<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/categories endpoints.
 */
class CategoriesTest extends AbstractResourceTest {

    /** The standard parent category ID. */
    const PARENT_CATEGORY_ID = 1;

    /** @var int A value to ensure new records are unique. */
    protected static $recordCounter = 1;

    /** {@inheritdoc} */
    protected $baseUrl = '/categories';

    /** {@inheritdoc} */
    protected $editFields = ['description', 'name', 'parentCategoryID', 'urlCode'];

    /** {@inheritdoc} */
    protected $patchFields = ['description', 'name', 'parentCategoryID', 'urlCode'];

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
                case 'urlCode':
                    $value = md5($value);
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
        $urlCode = strtolower(preg_replace('/[^A-Z0-9]/i', '-', $name));
        $record = [
            'name' => $name,
            'urlCode' => $urlCode,
            'parentCategoryID' => self::PARENT_CATEGORY_ID
        ];
        static::$recordCounter++;
        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function testGetEdit($record = null) {
        $row = $this->testPost();
        $result = parent::testGetEdit($row);
        return $result;
    }
}
