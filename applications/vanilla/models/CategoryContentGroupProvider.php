<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Models\ContentGroupRecordProviderInterface;

/**
 * Provide category content group records.
 */
class CategoryContentGroupProvider implements ContentGroupRecordProviderInterface
{
    /** @var \CategoryModel */
    private $categoryModel;

    /**
     * DI.
     *
     * @param \CategoryModel $categoryModel
     */
    public function __construct(\CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
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
}
