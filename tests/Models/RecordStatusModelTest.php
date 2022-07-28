<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Models;

use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Exception\Database\NoResultsException;
use VanillaTests\SiteTestCase;

/**
 * Automated tests for RecordStatusModel
 */
class RecordStatusModelTest extends SiteTestCase
{
    public static $addons = ["ideation", "qna"];

    /** @var RecordStatusModel */
    private $recordStatusModel;

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->recordStatusModel = static::container()->get(RecordStatusModel::class);
    }

    /**
     * Test inserting a record status
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     */
    public function testInsert()
    {
        $insertID = $this->insertNewStatus("discussion");
        $this->assertGreaterThanOrEqual(10000, $insertID);
        $inserted = $this->recordStatusModel->selectSingle(["statusID" => $insertID]);
        $this->assertNotEmpty($inserted);
        $this->assertStringStartsWith("foo", $inserted["name"]);

        $insertID = $this->insertNewStatus(null);
        $this->assertGreaterThanOrEqual(10000, $insertID);
        $inserted = $this->recordStatusModel->selectSingle(["statusID" => $insertID]);
        $this->assertNotEmpty($inserted);
        $this->assertStringStartsWith("foo", $inserted["name"]);

        $insertID = $this->insertNewStatus("discussion", false, "Internal Status", "open", true);
        $this->assertGreaterThanOrEqual(10000, $insertID);
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $inserted = $recordStatusModel->selectSingle(["statusID" => $insertID]);
        $this->assertNotEmpty($inserted);
        $this->assertStringStartsWith("Internal Status", $inserted["name"]);
        $this->assertSame(true, $inserted["isInternal"]);

        $this->insertNewStatus(null, false, "Open Status");
        $this->insertNewStatus("discussion", false, "Closed Status", "closed");
        $inactiveStatusID = $this->insertNewStatus("discussion", false, "Inactive Status", "closed");
        $inactiveInternalStatusID = $this->insertNewStatus(
            "discussion",
            false,
            "Inactive Internal Status",
            "open",
            true
        );
        $this->insertNewStatus("discussion", false, "Active Internal Status", "closed", true);
        $this->recordStatusModel->update(
            ["isActive" => false],
            ["statusID" => [$inactiveStatusID, $inactiveInternalStatusID]]
        );
    }

    /**
     * Insert a status for use in testing.
     *
     * @param string|null $recordSubType
     * @param bool $isDefault
     * @param string $statusName name of the status.
     * @param string $statusState open/closed state of the status
     * @param bool $isInternal internal status.
     *
     * @return int ID of status created
     * @throws ClientException Not Applicable.
     */
    private function insertNewStatus(
        string $recordSubType = null,
        bool $isDefault = false,
        string $statusName = "foo ",
        string $statusState = "open",
        bool $isInternal = false
    ): int {
        static $count = 1;
        $count++;
        $baseRecord = [
            "name" => "{$statusName} {$count}",
            "state" => $statusState,
            "recordType" => "discussion",
            "recordSubtype" => $recordSubType,
            "isDefault" => $isDefault,
            "isInternal" => $isInternal,
        ];
        $insertID = $this->recordStatusModel->insert($baseRecord);
        return intval($insertID);
    }

    /**
     * Test that insert throws a ClientException
     *
     * @param array $insert Record to insert
     * @dataProvider insertThrowsExceptionDataProvider
     */
    public function testInsertThrowsClientException(array $insert): void
    {
        $this->expectException(ClientException::class);
        $this->recordStatusModel->insert($insert);
    }

    /**
     * Data Provider for insertThrowsClientException test
     * @return iterable
     */
    public function insertThrowsExceptionDataProvider(): iterable
    {
        $baseRecord = [
            "name" => "foo",
            "state" => "open",
            "recordType" => "discussion",
            "recordSubtype" => "discussion",
            "isDefault" => true,
        ];
        yield "isSystem = true" => [$baseRecord + ["isSystem" => true]];
        yield "isSystem = 1" => [$baseRecord + ["isSystem" => 1]];
    }

    /**
     * Test update throws a ClientException
     *
     * @param array $update
     * @param array|null $where
     * @dataProvider updateThrowsClientExceptionDataProvider
     */
    public function testUpdateThrowsClientException(array $update, ?array $where): void
    {
        $insertID = $this->insertNewStatus("discussion");
        $this->expectException(ClientException::class);
        if (!isset($where)) {
            $where = ["statusID" => $insertID];
        }
        $_ = $this->recordStatusModel->update($update, $where);
    }

    /**
     * Data Provider for updateThrowsClientException test
     *
     * @return iterable
     */
    public function updateThrowsClientExceptionDataProvider(): iterable
    {
        yield "isSystem = true [only]" => [
            "update" => ["isSystem" => true],
            "where" => null,
        ];
        yield "isSystem = 1 [only]" => [
            "update" => ["isSystem" => 1],
            "where" => null,
        ];
        yield "isSystem = true [with other updates]" => [
            "update" => ["name" => "fizz", "isDefault" => true, "isSystem" => true],
            "where" => null,
        ];
        yield "isSystem = 1 [with other updates]" => [
            "update" => ["name" => "fizz", "recordSubType" => "fish", "isSystem" => 1],
            "where" => null,
        ];
        yield "update one field where isSystem = true [only]" => [
            "update" => ["recordType" => "fuzz"],
            "where" => ["isSystem" => true],
        ];
        yield "update one field where isSystem = 1 [only]" => [
            "update" => ["state" => "open"],
            "where" => ["isSystem" => 1],
        ];
        yield "update one field where isSystem = true and other criteria" => [
            "update" => ["recordType" => "fuzz"],
            "where" => ["recordSubType" => "fizz", "isSystem" => true, "state" => "closed"],
        ];
        yield "update one field where isSystem = 1 and other criteria" => [
            "update" => ["state" => "open"],
            "where" => ["isSystem" => 1, "state" => "closed", "recordSubType" => "foo"],
        ];
        yield "update one field where specifying ALL system status IDs" => [
            "update" => ["state" => "open"],
            "where" => ["statusID" => RecordStatusModel::$systemDefinedIDs],
        ];
        foreach (RecordStatusModel::$systemDefinedIDs as $systemDefinedID) {
            yield "update one field where specifying system status ID {$systemDefinedID}" => [
                "update" => ["state" => "open"],
                "where" => ["statusID" => $systemDefinedID],
            ];
        }
        yield "update one field where isDefault = true [insufficiently constrained]" => [
            "update" => ["state" => "open"],
            "where" => ["isDefault" => true],
        ];
        yield "update one field where state = open [insufficiently constrained]" => [
            "update" => ["isDefault" => true],
            "where" => ["state" => "open"],
        ];
    }

    /**
     * Test update record status
     *
     * @param array $update
     * @param array|null $where
     * @dataProvider updateDataProvider
     */
    public function testUpdate(array $update, ?array $where): void
    {
        $insertID = $this->insertNewStatus("discussion");
        if (!isset($where)) {
            $where = ["statusID" => $insertID];
        }
        $isUpdated = $this->recordStatusModel->update($update, $where);
        $this->assertTrue($isUpdated);
    }

    /**
     * @return iterable
     */
    public function updateDataProvider(): iterable
    {
        yield "update one field, single record" => [
            "update" => ["state" => "open"],
            "where" => null,
        ];
        yield "update isDefault = true, single record" => [
            "update" => ["isDefault" => true],
            "where" => null,
        ];
        yield "update isDefault = false, single record" => [
            "update" => ["isDefault" => false],
            "where" => null,
        ];
        yield "update one field, where isDefault = true and isSystem = false" => [
            "update" => ["state" => "open"],
            "where" => ["isDefault" => true, "isSystem" => false],
        ];
        yield "update isDefault = false, where state = open and isSystem = false" => [
            "update" => ["state" => "open"],
            "where" => ["isDefault" => true, "isSystem" => false],
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
    public function testDeleteThrowsClientException(array $where)
    {
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $this->expectException(ClientException::class);
        $_ = $recordStatusModel->delete($where);
    }

    /**
     * Data Provider for deleteThrowsClientException test
     *
     * @return iterable
     */
    public function deleteThrowsClientExceptionDataProvider(): iterable
    {
        foreach (RecordStatusModel::$systemDefinedIDs as $systemDefinedID) {
            yield "Specifying system status ID {$systemDefinedID}" => [
                "where" => ["statusID" => $systemDefinedID],
            ];
        }
        yield "delete where specifying ALL system status IDs" => [
            "where" => ["statusID" => RecordStatusModel::$systemDefinedIDs],
        ];
        yield "delete where state = open [insufficiently constrained]" => [
            "where" => ["state" => "open"],
        ];
        yield "delete where isDefault = false [insufficiently constrained]" => [
            "where" => ["isDefault" => false],
        ];
        yield "delete where isDefault = true [insufficiently constrained]" => [
            "where" => ["isDefault" => true],
        ];
    }

    /**
     * Test that deleting a default status throws a ClientException.
     */
    public function testDeleteDefaultThrowsClientException()
    {
        $insertID = $this->insertNewStatus("discussion", true);
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $recordStatus = $recordStatusModel->selectSingle(["statusID" => $insertID]);
        $this->assertTrue($recordStatus["isDefault"], "Inserted record status is not default status");

        $this->expectException(ClientException::class);
        $_ = $recordStatusModel->delete(["statusID" => $insertID]);
    }

    /**
     * Test deleting a record status
     */
    public function testDelete()
    {
        $insertID = $this->insertNewStatus("discussion");
        $recordStatusModel = static::container()->get(RecordStatusModel::class);
        $isDeleted = $recordStatusModel->delete(["statusID" => $insertID]);
        $this->assertTrue($isDeleted);
        $this->expectException(NoResultsException::class);
        $_ = $recordStatusModel->selectSingle(["statusID" => $insertID]);
    }

    /**
     * test validateStatuses check.
     *
     * @depends testInsert
     */
    public function testValidateStatuses()
    {
        $statusID = $this->recordStatusModel->selectSingle(["name" => "Open Status 5"])["statusID"];
        $statusID2 = $this->recordStatusModel->selectSingle(["name" => "Closed Status 6"])["statusID"];
        $internalStatusID = $this->recordStatusModel->selectSingle(["name" => "Active Internal Status 9"])["statusID"];
        $this->recordStatusModel->validateStatusesAreActive([$statusID, $statusID2], false);
        $this->expectException(NotFoundException::class);
        $this->recordStatusModel->validateStatusesAreActive([1, 2, 3, 99, 89, $statusID, $statusID2], false);
        $this->recordStatusModel->validateStatusesAreActive(
            [RecordStatusModel::DISCUSSION_INTERNAL_STATUS_NONE, $internalStatusID],
            true
        );
    }

    /**
     * @throws ClientException
     *
     * @depends testInsert
     * @dataProvider getStatusesDataProvider
     */
    public function testGetStatuses(?bool $isActive, ?bool $isInternal)
    {
        $allStatuses = $this->recordStatusModel->select();
        $statusIDs = [];
        foreach ($allStatuses as $status) {
            if (
                ($isActive === null || $status["isActive"] === $isActive) &&
                ($isInternal === null || $status["isInternal"] === $isInternal)
            ) {
                $statusIDs[] = $status["statusID"];
            }
        }
        sort($statusIDs);
        $statuses = array_keys($this->recordStatusModel->getStatuses($isActive, $isInternal));
        sort($statuses);
        $this->assertSame($statusIDs, $statuses);
    }

    /**
     * Data Provider for testGetStatuses method.
     *
     * @return array
     */
    public function getStatusesDataProvider(): array
    {
        return [
            "get All statuses, isActive = null, is Internal = null" => [null, null],
            "get All Active statuses, isActive = true, is Internal = null" => [true, null],
            "get All Inactive statuses, isActive = false, is Internal = null" => [false, null],
            "get All Active internal statuses, isActive = true, is Internal = true" => [true, true],
            "get All Active external statuses, isActive = true, is Internal = false" => [true, false],
            "get All Inactive internal statuses, isActive = false, is Internal = true" => [false, true],
            "get All Inactive external statuses, isActive = false, is Internal = false" => [false, false],
            "get All Internal statuses, isActive = null, is Internal = true" => [null, true],
            "get All External statuses, isActive = null, is Internal = false" => [null, false],
        ];
    }
}
