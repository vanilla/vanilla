<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Models\CollectionRecordProviderInterface;

/**
 * Provide category collection records.
 */
class CategoryCollectionProvider implements CollectionRecordProviderInterface
{
    /** @var \CategoryModel */
    private $categoryModel;

    /** @var \CategoriesApiController */
    private $categoriesApiController;

    /**
     * DI.
     *
     * @param \CategoryModel $categoryModel
     * @param \CategoriesApiController $categoriesApiController
     */
    public function __construct(\CategoryModel $categoryModel, \CategoriesApiController $categoriesApiController)
    {
        $this->categoryModel = $categoryModel;
        $this->categoriesApiController = $categoriesApiController;
    }

    /**
     * @inheritDoc
     */
    public function getRecordType(): string
    {
        return \CategoryModel::RECORD_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function filterValidRecordIDs(array $recordIDs): array
    {
        return $this->categoryModel->filterExistingRecordIDs($recordIDs);
    }

    /**
     * @inheritDoc
     */
    public function getRecords(array $recordIDs, string $locale): array
    {
        $query = ["categoryID" => $recordIDs];
        $result = $this->categoriesApiController->index($query);
        $stripKey = "followed";
        $categoryData = [];
        foreach ($result->getData() as $category) {
            unset($category[$stripKey]);
            $categoryData[$category["categoryID"]] = $category;
        }
        return $categoryData;
    }
}
