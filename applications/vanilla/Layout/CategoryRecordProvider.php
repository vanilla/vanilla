<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use CategoryModel;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;

/**
 * Category Record Provider. It provides records of the 'category' type.
 */
class CategoryRecordProvider implements LayoutViewRecordProviderInterface {

    private static $recordType = 'category';

    /* CategoryModel $categoryModel */
    private $categoryModel;

    /**
     * Constructor.
     *
     * @param CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel) {
        $this->categoryModel = $categoryModel;
    }

    /**
     * @inheritdoc
     */
    public function getRecords(array $recordIDs): array {
        $result = [];

        $categories = $this->categoryModel->getCollection()->getMulti($recordIDs);
        foreach ($categories as $category) {
            $result[$category['CategoryID']] = [
                'name' => $category['Name'],
                'url' => CategoryModel::categoryUrl($category)
            ];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function validateRecords(array $recordIDs): bool {
        $categories = $this->categoryModel->getCollection()->getMulti($recordIDs);
        foreach ($recordIDs as $categoryID) {
            if ($categories[$categoryID] == null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns an array of valid Record Types for this specific Record Provider.
     */
    public static function getValidRecordTypes(): array {
        return [self::$recordType];
    }
}
