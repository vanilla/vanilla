<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use CategoryModel;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;
use Vanilla\Site\SiteSectionModel;

/**
 * Category Record Provider. It provides records of the 'category' type.
 */
class CategoryLayoutRecordProvider implements LayoutViewRecordProviderInterface
{
    public const RECORD_TYPE = "category";

    private CategoryModel $categoryModel;
    private SiteSectionModel $siteSectionModel;

    /**
     * Constructor.
     *
     * @param CategoryModel $categoryModel Category Model.
     * @param SiteSectionModel $siteSectionModel Site section model.
     */
    public function __construct(CategoryModel $categoryModel, SiteSectionModel $siteSectionModel)
    {
        $this->categoryModel = $categoryModel;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * @inheritdoc
     */
    public function getRecords(array $recordIDs): array
    {
        $result = [];

        $categories = $this->categoryModel->getCollection()->getMulti($recordIDs);
        foreach ($categories as $category) {
            $result[$category["CategoryID"]] = [
                "name" => $category["Name"],
                "url" => CategoryModel::categoryUrl($category),
            ];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function validateRecords(array $recordIDs): bool
    {
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
    public static function getValidRecordTypes(): array
    {
        return [self::RECORD_TYPE];
    }

    /**
     * Perform resolution of category layoutViewType
     *
     * @inheritDoc
     */
    public function resolveLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        if ($query->recordType === self::RECORD_TYPE) {
            $recordID = $this->categoryModel->ensureCategoryID($query->recordID);
            if (!$recordID) {
                throw new NotFoundException("Category", [
                    "categoryID" => $query->recordID,
                ]);
            }
            // Resolve the recordID in case we were given a category slug.
            $query = $query->withRecordID($recordID);
        }

        if ($query->layoutViewType === CategoryModel::LAYOUT_CATEGORY_LIST) {
            // Resolve the layoutViewType which is dynamic based on the category.
            $calculatedViewType = $this->categoryModel->calculateCategoryLayoutViewType($query->recordID);
            return $query->withLayoutViewType($calculatedViewType);
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function resolveParentLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        // Our parent will be a site section
        $siteSection = $this->siteSectionModel->getSiteSectionForAttribute("categoryID", $query->recordID);

        return $query
            ->withRecordType($siteSection->getLayoutRecordType())
            ->withRecordID($siteSection->getLayoutRecordID());
    }
}
