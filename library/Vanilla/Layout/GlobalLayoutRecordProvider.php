<?php
/**
 * @author Pavel Gonharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;

/**
 * Provide capabilities for generating a category breadcrumb.
 */
class GlobalLayoutRecordProvider implements LayoutViewRecordProviderInterface
{
    public const RECORD_TYPE = "global";
    public const RECORD_ID = -1;

    /**
     * @inheritdoc
     */
    public function getRecords(array $recordIDs): array
    {
        $result = [];
        foreach ($recordIDs as $id) {
            $result[$id] = ["name" => "global", "url" => \Gdn::request()->getSimpleUrl()];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function validateRecords(array $recordIDs): bool
    {
        $recordIDs = array_filter($recordIDs, function ($id) {
            return $id == self::RECORD_ID;
        });
        return count($recordIDs) > 0;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array
    {
        return [
            self::RECORD_TYPE,
            // Legacy support
            "root",
        ];
    }

    /**
     * No resolution needed.
     *
     * @inheritdoc
     */
    public function resolveLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        return $query;
    }

    /**
     * If a global layout is not applied, then we resolve the file based template.
     *
     * @inheritdoc
     */
    public function resolveParentLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        return $query->withRecordType(LayoutViewModel::FILE_RECORD_TYPE)->withRecordID($query->layoutViewType);
    }
}
