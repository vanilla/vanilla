<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Dashboard\Models\BannerImageModel;

/**
 * Model for getting the "place" of a discussion, either a category or a group if groups is enabled.
 */
class DiscussionPlaceModel
{
    public function __construct(protected \CategoryModel $categoryModel)
    {
    }

    /**
     * @param array $apiRecord
     * @return null|array<{name: string, description: string, bannerImage: string}>
     */
    public function getPlaceFragmentForApiRecord(array $apiRecord): ?array
    {
        return $this->getPlaceRecordForCategory($apiRecord["categoryID"]);
    }

    /**
     * Get a place record for a category.
     *
     * @param int $categoryID
     *
     * @return array|null
     */
    protected function getPlaceRecordForCategory(int $categoryID): ?array
    {
        $categoryFragment = $this->categoryModel->getFragmentByID($categoryID);
        if ($categoryFragment === null) {
            return null;
        }

        return [
            "name" => $categoryFragment["name"],
            "description" => $categoryFragment["description"],
            "bannerImage" => BannerImageModel::getBannerImageUrl($categoryID),
        ];
    }
}
