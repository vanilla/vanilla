<?php
/**
 * @author Pavel Gonharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;

/**
 * Provide capabilities for generating a category breadcrumb.
 */
class GlobalRecordProvider implements LayoutViewRecordProviderInterface
{
    private static $recordType = "global";
    private static $recordID = -1;

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
            return $id == $this::$recordID;
        });
        return count($recordIDs) > 0;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array
    {
        return [self::$recordType];
    }

    /**
     * Returns the provider's `recordType` & `recordID`.
     */
    public static function getRecordTypeAndID(): array
    {
        return ["recordType" => self::$recordType, "recordID" => self::$recordID];
    }

    /**
     * @inheritdoc
     */
    public function getParentRecordTypeAndID(string $recordType, string $recordID): array
    {
        return [LayoutViewModel::FILE_RECORD_TYPE, $this::$recordID];
    }
}
