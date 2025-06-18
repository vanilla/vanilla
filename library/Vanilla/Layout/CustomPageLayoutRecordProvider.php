<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Layout;

use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;
use Vanilla\Models\CustomPageModel;

/**
 * Resolves customPage layouts for customPage records.
 */
class CustomPageLayoutRecordProvider implements LayoutViewRecordProviderInterface
{
    const RECORD_TYPE = "customPage";

    public function __construct(private CustomPageModel $customPageModel)
    {
    }

    /**
     * @inheritdoc
     */
    public function getRecords(array $recordIDs): array
    {
        $records = $this->customPageModel->select(["customPageID" => $recordIDs]);
        return array_column($records, null, "customPageID");
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array
    {
        return [self::RECORD_TYPE];
    }

    /**
     * @inheritdoc
     */
    public function validateRecords(array $recordIDs): bool
    {
        if (empty($recordIDs)) {
            return true;
        }
        $customPages = $this->customPageModel->select(["customPageID" => $recordIDs]);
        $customPageIDs = array_column($customPages, "customPageID");
        return empty(array_diff($recordIDs, $customPageIDs));
    }

    /**
     * @inheritdoc
     */
    public function resolveLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        return $query;
    }

    /**
     * @inheritdoc
     */
    public function resolveParentLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        return $this->resolveLayoutQuery($query);
    }
}
