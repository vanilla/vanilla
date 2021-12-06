<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Models;

use Garden\Web\Exception\ClientException;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Exception\Database\NoResultsException;
use VanillaTests\SiteTestCase;

/**
 * Automated tests for RecordStatusModel
 */
class RecordStatusModelTest extends SiteTestCase {

    public static $addons = ['ideation'];

    /**
     * Test inserting a record status
     *
     * @return int Inserted record ID
     * @throws ClientException Not Applicable.
     * @throws \Garden\Container\ContainerException Not Applicable.
     * @throws \Garden\Container\NotFoundException Not Applicable.
     * @throws \Garden\Schema\ValidationException Not Applicable.
     * @throws \Vanilla\Exception\Database\NoResultsException Not Applicable.
     */
    public function testInsert(): int {
        $insertID = $this->insertNewStatus();
        $this->assertGreaterThanOrEqual(10000, $insertID);
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $inserted = $recordStatusModel->selectSingle(['statusID' => $insertID]);
        $this->assertNotEmpty($inserted);
        $this->assertStringStartsWith("foo", $inserted["name"]);
        return $insertID;
    }

    /**
     * Insert a status for use in testing.
     *
     * @param bool $isDefault
     * @return int ID of status created
     * @throws ClientException Not Applicable.
     * @throws \Garden\Container\ContainerException Not Applicable.
     * @throws \Garden\Container\NotFoundException Not Applicable.
     */
    private function insertNewStatus(bool $isDefault = false): int {
        static $count = 1;
        $count++;
        $baseRecord = [
            'name' => "foo {$count}",
            'state' => 'open',
            'recordType' => 'discussion',
            'recordSubtype' => 'discussion',
            'isDefault' => $isDefault
        ];
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $insertID = $recordStatusModel->insert($baseRecord);
        return intval($insertID);
    }

    /**
     * Test that insert throws a ClientException
     *
     * @param array $insert Record to insert
     * @throws \Garden\Container\ContainerException Not Applicable.
     * @throws \Garden\Container\NotFoundException Not Applicable.
     * @dataProvider insertThrowsExceptionDataProvider
     */
    public function testInsertThrowsClientException(array $insert): void {
        $this->expectException(ClientException::class);
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $recordStatusModel->insert($insert);
    }

    /**
     * Data Provider for insertThrowsClientException test
     * @return iterable
     */
    public function insertThrowsExceptionDataProvider(): iterable {
        $baseRecord = [
            'name' => 'foo',
            'state' => 'open',
            'recordType' => 'discussion',
            'recordSubtype' => 'discussion',
            'isDefault' => true
        ];
        yield "isSystem = true" => [$baseRecord + ["isSystem" => true]];
        yield "isSystem = 1" => [$baseRecord + ["isSystem" => 1]];
    }

    /**
     * Test update throws a ClientException
     *
     * @param array $update
     * @param array $where
     * @throws \Garden\Container\ContainerException Not Applicable.
     * @throws \Garden\Container\NotFoundException Not Applicable.
     * @dataProvider updateThrowsClientExceptionDataProvider
     */
    public function testUpdateThrowsClientException(array $update, ?array $where): void {
        $insertID = $this->insertNewStatus();
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $this->expectException(ClientException::class);
        if (!isset($where)) {
            $where = ['statusID' => $insertID];
        }
        $_ = $recordStatusModel->update($update, $where);
    }

    /**
     * Data Provider for updateThrowsClientException test
     *
     * @return iterable
     */
    public function updateThrowsClientExceptionDataProvider(): iterable {
        yield 'isSystem = true [only]' => [
            'update' => ['isSystem' => true],
            'where' => null
        ];
        yield 'isSystem = 1 [only]' => [
            'update' => ['isSystem' => 1],
            'where' => null
        ];
        yield 'isSystem = true [with other updates]' => [
            'update' => ["name" => "fizz", 'isDefault' => true, 'isSystem' => true],
            'where' => null
        ];
        yield 'isSystem = 1 [with other updates]' => [
            'update' => ["name" => "fizz", 'recordSubType' => 'fish', 'isSystem' => 1],
            'where' => null
        ];
        yield 'update one field where isSystem = true [only]' => [
            'update' => ['recordType' => 'fuzz'],
            'where' => ['isSystem' => true]
        ];
        yield 'update one field where isSystem = 1 [only]' => [
            'update' => ['state' => 'open'],
            'where' => ['isSystem' => 1]
        ];
        yield 'update one field where isSystem = true and other criteria' => [
            'update' => ['recordType' => 'fuzz'],
            'where' => ['recordSubType' => 'fizz', 'isSystem' => true, 'state' => 'closed']
        ];
        yield 'update one field where isSystem = 1 and other criteria' => [
            'update' => ['state' => 'open'],
            'where' => ['isSystem' => 1, 'state' => 'closed', 'recordSubType' => 'foo']
        ];
        yield "update one field where specifying ALL system status IDs" => [
            'update' => ['state' => 'open'],
            'where' => ['statusID' => RecordStatusModel::$systemDefinedIDs]
        ];
        foreach (RecordStatusModel::$systemDefinedIDs as $systemDefinedID) {
            yield "update one field where specifying system status ID {$systemDefinedID}" => [
                'update' => ['state' => 'open'],
                'where' => ['statusID' => $systemDefinedID]
            ];
        }
        yield 'update one field where isDefault = true [insufficiently constrained]' => [
            'update' => ['state' => 'open'],
            'where' => ['isDefault' => true]
        ];
        yield 'update one field where state = open [insufficiently constrained]' => [
            'update' => ['isDefault' => true],
            'where' => ['state' => 'open']
        ];
    }

    /**
     * Test update record status
     *
     * @param array $update
     * @param array|null $where
     * @dataProvider updateDataProvider
     */
    public function testUpdate(array $update, ?array $where): void {
        $insertID = $this->insertNewStatus();
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        if (!isset($where)) {
            $where = ['statusID' => $insertID];
        }
        $isUpdated = $recordStatusModel->update($update, $where);
        $this->assertTrue($isUpdated);
    }

    /**
     * @return iterable
     */
    public function updateDataProvider(): iterable {
        yield 'update one field, single record' => [
            'update' => ['state' => 'open'],
            'where' => null
        ];
        yield 'update isDefault = true, single record' => [
            'update' => ['isDefault' => true],
            'where' => null
        ];
        yield 'update isDefault = false, single record' => [
            'update' => ['isDefault' => false],
            'where' => null
        ];
        yield 'update one field, where isDefault = true and isSystem = false' => [
            'update' => ['state' => 'open'],
            'where' => ['isDefault' => true, 'isSystem' => false]
        ];
        yield 'update isDefault = false, where state = open and isSystem = false' => [
            'update' => ['state' => 'open'],
            'where' => ['isDefault' => true, 'isSystem' => false]
        ];
    }

    /**
     * Test that record status delete throws a ClientException on invalid invocation
     *
     * @param array $where
     * @throws ClientException Delete system defined status.
     * @throws \Garden\Container\ContainerException Not Applicable.
     * @throws \Garden\Container\NotFoundException Not Applicable.
     * @dataProvider deleteThrowsClientExceptionDataProvider
     */
    public function testDeleteThrowsClientException(array $where) {
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $this->expectException(ClientException::class);
        $_ = $recordStatusModel->delete($where);
    }

    /**
     * Data Provider for deleteThrowsClientException test
     *
     * @return iterable
     */
    public function deleteThrowsClientExceptionDataProvider(): iterable {
        foreach (RecordStatusModel::$systemDefinedIDs as $systemDefinedID) {
            yield "Specifying system status ID {$systemDefinedID}" => [
                'where' => ['statusID' => $systemDefinedID]
            ];
        }
        yield "delete where specifying ALL system status IDs" => [
            'where' => ['statusID' => RecordStatusModel::$systemDefinedIDs]
        ];
        yield 'delete where state = open [insufficiently constrained]' => [
            'where' => ['state' => 'open']
        ];
        yield 'delete where isDefault = false [insufficiently constrained]' => [
            'where' => ['isDefault' => false]
        ];
        yield 'delete where isDefault = true [insufficiently constrained]' => [
            'where' => ['isDefault' => true]
        ];
    }

    /**
     * Test that deleting a default status throws a ClientException.
     */
    public function testDeleteDefaultThrowsClientException() {
        $insertID = $this->insertNewStatus(true);
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $recordStatus = $recordStatusModel->selectSingle(['statusID' => $insertID]);
        $this->assertTrue($recordStatus['isDefault'], "Inserted record status is not default status");

        $this->expectException(ClientException::class);
        $_ = $recordStatusModel->delete(['statusID' => $insertID]);
    }

    /**
     * Test deleting a record status
     */
    public function testDelete() {
        $insertID = $this->insertNewStatus();
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $isDeleted = $recordStatusModel->delete(['statusID' => $insertID]);
        $this->assertTrue($isDeleted);
        $this->expectException(NoResultsException::class);
        $_ = $recordStatusModel->selectSingle(['statusID' => $insertID]);
    }

    /**
     * Test that converting from ideation status throws exception when invalid status provided
     *
     * @param array $status
     * @throws \Garden\Container\ContainerException Not Applicable.
     * @throws \Garden\Container\NotFoundException Not Applicable.
     * @throws \Garden\Schema\ValidationException Not Applicable.
     * @throws \Vanilla\Exception\Database\NoResultsException Not Applicable.
     * @dataProvider convertFromIdeationStatusThrowsInvalidArgumentExceptionDataProvider
     */
    public function testConvertFromIdeationStatusThrowsInvalidArgumentException(array $status): void {
        $this->expectException(\InvalidArgumentException::class);
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $_ = $recordStatusModel->convertFromIdeationStatus($status);
    }

    /**
     * Data Provider for ConvertFromIdeationStatusThrowsInvalidArgumentException test
     *
     * @return iterable
     */
    public function convertFromIdeationStatusThrowsInvalidArgumentExceptionDataProvider(): iterable {
        yield 'empty array' => [[]];
        yield 'simple array' => [['foo', 'bar', 'baz']];
        yield 'array w/o Name' => [['StatusID' => 42, "State" => "Open", "IsDefault" => 0]];
        yield 'array w/o State' => [['Name' => "FOO!", "IsDefault" => 0]];
        yield 'array w/o IsDefault' => [['Name' => "FOO!", "State" => "Open"]];
    }

    /**
     * Test conversion from ideation status to record status
     *
     * @throws \Garden\Container\ContainerException Not Applicable.
     * @throws \Garden\Container\NotFoundException Not Applicable.
     * @throws \Garden\Schema\ValidationException Not Applicable.
     * @throws \Vanilla\Exception\Database\NoResultsException Not Applicable.
     */
    public function testConvertFromIdeationStatus() {
        // Seed the status model with a new status
        $statusModel = static::container()->get(\StatusModel::class);
        $statusID = $statusModel->upsert(__FUNCTION__.time(), 'Open', 1);
        $this->assertIsNumeric($statusID, 'Failed inserting ideation status');
        $status = $statusModel->getStatus($statusID);

        // Test the conversion
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $convertedToRecordStatus = $recordStatusModel->convertFromIdeationStatus($status);
        $this->assertThat(
            $convertedToRecordStatus,
            $this->logicalAnd(
                $this->logicalNot($this->arrayHasKey('statusID')),
                $this->logicalNot($this->arrayHasKey('dateInserted')),
                $this->logicalNot($this->arrayHasKey('insertUserID')),
                $this->logicalNot($this->arrayHasKey('dateUpdated')),
                $this->logicalNot($this->arrayHasKey('updateUserID'))
            )
        );
        $this->assertEquals($status['Name'], $convertedToRecordStatus['name']);
        $this->assertEqualsIgnoringCase($status['State'], $convertedToRecordStatus['state']);
        $this->assertEquals(1, $convertedToRecordStatus['isDefault']);
        $this->assertEquals(0, $convertedToRecordStatus['isSystem']);
        $this->assertEquals('discussion', $convertedToRecordStatus['recordType']);
        $this->assertEquals('ideation', $convertedToRecordStatus['recordSubtype']);

        // Insert the converted record into recordStatus table
        $insertedID = $recordStatusModel->insert($convertedToRecordStatus);
        $this->assertIsNumeric($insertedID);

        // Update the reference to the inserted recordStatus in the corresponding Status record
        $statusModel->update(['recordStatusID' => intval($insertedID)], ['StatusID' => intval($statusID)]);
        $statusModel->clearStatusesCache();
        $status = $statusModel->getStatus($statusID);

        // Convert the ideation status that references the record status to a record status
        // and verify it'll retrieve from the database which includes fields not present
        // when converting from an ideation status that didn't have a reference to the recordStatus equivalent.
        $convertedToRecordStatus = $recordStatusModel->convertFromIdeationStatus($status);
        $this->assertThat(
            $convertedToRecordStatus,
            $this->logicalAnd(
                $this->arrayHasKey('statusID'),
                $this->arrayHasKey('dateInserted'),
                $this->arrayHasKey('insertUserID'),
                $this->arrayHasKey('dateUpdated'),
                $this->arrayHasKey('updateUserID')
            )
        );
        $this->assertEquals($insertedID, $convertedToRecordStatus['statusID']);
    }
}
