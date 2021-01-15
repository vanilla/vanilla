<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Models\DirtyRecordModel;

/**
 * Test resource index endpoints filtering on dirtyRecords
 */
trait TestFilterDirtyRecordsTrait {

    /**
     * Ensure that there are dirtyRecords for a specific resource.
     */
    abstract protected function triggerDirtyRecords();

    /**
     * Get the resource type.
     *
     * @return array
     */
    abstract protected function getResourceInformation(): array;

    /**
     * Test whether each of the filter fields works on a resources index endpoint.
     */
    public function testFilterDirtyRecords(): void {
        $this->triggerDirtyRecords();
        $query = $this->getQuery();
        $rows = $this->api()->get($this->baseUrl, $query)->getBody();

        $this->assertAllDirtyRecordsReturned($rows);
    }

    /**
     * Get the api query.
     *
     * @return array
     */
    protected function getQuery():array {
        return [DirtyRecordModel::DIRTY_RECORD_OPT => true];
    }

    /**
     * Assert all dirty records for a specific resource are returned.
     *
     * @param array $records
     */
    protected function assertAllDirtyRecordsReturned(array $records) {
        /** @var DirtyRecordModel $dirtyRecordModel */
        $dirtyRecordModel = \Gdn::getContainer()->get(DirtyRecordModel::class);
        $resourceInformation = $this->getResourceInformation();
        $recordType = $resourceInformation["resourceType"] ?? '';
        $primaryKey = $resourceInformation["primaryKey"] ?? '';
        $dirtyRecords = $dirtyRecordModel->select(["recordType" => $recordType]);

        $dirtyRecordIDs = array_column($dirtyRecords, 'recordID');
        $ids = array_column($records, $primaryKey);

        foreach ($dirtyRecordIDs as $dirtyRecordID) {
            $this->assertContains($dirtyRecordID, $ids);
        }
    }
}
