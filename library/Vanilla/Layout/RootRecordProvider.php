<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;

/**
 * Root Record Provider. It provides records of the 'root' type.
 */
class RootRecordProvider implements LayoutViewRecordProviderInterface
{
    private static $recordType = "root";
    private static $recordID = -2;

    /**
     * @inheritdoc
     */
    public function getRecords(array $recordIDs): array
    {
        $result = [];
        foreach ($recordIDs as $id) {
            $result[$id] = ["name" => $this::$recordType, "url" => \Gdn::request()->getSimpleUrl()];
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
        $globalBody = GlobalRecordProvider::getRecordTypeAndID();
        return [$globalBody["recordType"], $globalBody["recordID"]];
    }
}
