<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Models\CollectionRecordModel;
use VanillaTests\SiteTestCase;

class CollectionRecordModelTest extends SiteTestCase
{
    private CollectionRecordModel $collectionRecordModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->collectionRecordModel = $this->container()->get(CollectionRecordModel::class);
    }

    /**
     * Test Iterator for LongRunner Processing
     *
     * @return void
     * @throws \Exception
     */
    public function testGetWhereIterator(): void
    {
        $collectionRecords = $this->generateDummyData();
        $iterator = $this->collectionRecordModel->getWhereIterator(
            ["collectionID" => 1],
            [],
            ["collectionID", "recordID"],
            "asc",
            1
        );
        $iteratorArray = iterator_to_array($iterator);
        $this->assertCount(4, $iteratorArray);
        for ($i = 1; $i <= 4; $i++) {
            $this->assertArraySubsetRecursive($collectionRecords[$i - 1], $iteratorArray["1_$i"]);
        }
    }

    /**
     * Generate some dummy collection records for testing
     *
     * @return array
     * @throws \Exception
     */
    public function generateDummyData(): array
    {
        $collectionRecord = [
            [
                "collectionID" => 1,
                "recordID" => 1,
                "recordType" => "discussion",
                "sort" => 1,
            ],
            [
                "collectionID" => 1,
                "recordID" => 2,
                "recordType" => "discussion",
                "sort" => 2,
            ],
            [
                "collectionID" => 1,
                "recordID" => 3,
                "recordType" => "discussion",
                "sort" => 3,
            ],
            [
                "collectionID" => 1,
                "recordID" => 4,
                "recordType" => "discussion",
                "sort" => 4,
            ],
            [
                "collectionID" => 2,
                "recordID" => 3,
                "recordType" => "discussion",
                "sort" => 1,
            ],
            [
                "collectionID" => 2,
                "recordID" => 4,
                "recordType" => "discussion",
                "sort" => 2,
            ],
            [
                "collectionID" => 2,
                "recordID" => 5,
                "recordType" => "discussion",
                "sort" => 3,
            ],
            [
                "collectionID" => 3,
                "recordID" => 8,
                "recordType" => "discussion",
                "sort" => 4,
            ],
            [
                "collectionID" => 3,
                "recordID" => 9,
                "recordType" => "discussion",
                "sort" => 5,
            ],
        ];

        foreach ($collectionRecord as $record) {
            $this->collectionRecordModel->insert($record);
        }
        return $collectionRecord;
    }
}
