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
class GlobalRecordProvider implements LayoutViewRecordProviderInterface {

    /**
     * @inheritdoc
     */
    public function getRecords(array $recordIDs): array {

        $result = [];
        foreach ($recordIDs as $id) {
            $result[$id] = ['name' => 'global', 'url' => \Gdn::request()->getSimpleUrl()];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function validateRecords(array $recordIDs): bool {
        $recordIDs = array_filter($recordIDs, function ($id) {
            return $id == -1;
        });
        return count($recordIDs) > 0;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array {
        return ['global'];
    }
}
