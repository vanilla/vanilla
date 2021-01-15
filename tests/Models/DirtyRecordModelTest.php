<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\CurrentTimeStamp;
use Vanilla\Models\DirtyRecordModel;
use VanillaTests\SiteTestCase;

/**
 * Class DirtyRecordModelTest
 *
 * @package VanillaTests\Models
 */
class DirtyRecordModelTest extends SiteTestCase {

    /**
     * @var DirtyRecordModel
     */
    private $dirtyRecordModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->dirtyRecordModel = self::container()->get(DirtyRecordModel::class);
    }

    /**
     * Test upserting a record.
     */
    public function testUniqueRecordUpsert() {
        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 1
        ]);

        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 1
        ]);

        $record = $this->dirtyRecordModel->select(["recordType" => "category"]);
        $this->assertEquals(1, count($record));
    }

    /**
     * Test upserting a record.
     */
    public function testUniqueRecordUpsertWithRecordTypes() {
        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 1
        ]);

        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 2
        ]);

        $this->dirtyRecordModel->insert([
            "recordType" => "discussion",
            "recordID" => 2
        ]);

        $record = $this->dirtyRecordModel->select(["recordType" => "category"]);
        $this->assertEquals(2, count($record));
    }

    /**
     * Test deleting records less than a certain amount of time.
     */
    public function testDeleteRecordsWithTimeStamp() {
        CurrentTimeStamp::mockTime('Dec 19 2019');
        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 1
        ]);

        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 2
        ]);

        CurrentTimeStamp::mockTime('Sept 19 2020');
        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 3
        ]);
        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 5
        ]);
        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 6
        ]);
        $this->dirtyRecordModel->insert([
            "recordType" => "category",
            "recordID" => 7
        ]);

        $this->dirtyRecordModel->clearRecordTypes('category', new \DateTimeImmutable("Sept 18 2020"));
        $records = $this->dirtyRecordModel->select(["recordType" => "category"]);
        $this->assertEquals(4, count($records));
    }
}
