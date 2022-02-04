<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\Dashboard\Models\RecordStatusModel;

/**
 * Test the /api/v2/discussions/statuses endpoints.
 */
class DiscussionsStatusesTest extends AbstractResourceTest {

    use NoGetEditTestTrait;

    /** @var string[] */
    public static $addons = ["ideation"];

    /** @var string */
    protected $baseUrl = "/discussions/statuses";

    /** @var string[] */
    protected $patchFields = ["isDefault", "name", "state", "recordSubtype"];

    /** @var string */
    protected $pk = "statusID";

    /** @var array */
    protected $record = [
        "isDefault" => false,
        "name" => "foo",
        "state" => "open",
        "recordSubtype" => "test",
    ];

    /** @var bool */
    protected $testPagingOnIndex = false;

    /**
     * @inheritDoc
     */
    public function record() {
        static $inc = 1;
        $record = $this->record;
        $record["name"] .= " " . $inc++;
        return $record;
    }

    /**
     * Test toggling the isDefault flag for a discussion status.
     */
    public function testToggleIsDefault(): void {
        // Create a couple statuses. Ensure one of them is set to be the default.
        $primary = $this->api()->post($this->baseUrl, [
            "name" => __FUNCTION__ . " A",
            "isDefault" => true,
        ])->getBody();
        $this->assertSame(true, $primary["isDefault"]);

        $secondary = $this->api()->post($this->baseUrl, ["name" => __FUNCTION__ . " B"])->getBody();
        $this->assertSame(false, $secondary["isDefault"]);

        // Switch the other status to be the new default.
        $this->api()->patch(
            $this->baseUrl . "/" . $secondary["statusID"],
            ["isDefault" => true]
        );

        // The original default status should no longer have a truthy isDefault flag. The new default status should.
        $primaryUpdated = $this->api()->get($this->baseUrl . "/" . $primary["statusID"])->getBody();
        $this->assertSame(false, $primaryUpdated["isDefault"]);

        $secondaryUpdated = $this->api()->get($this->baseUrl . "/" . $secondary["statusID"])->getBody();
        $this->assertSame(true, $secondaryUpdated["isDefault"]);
    }

    /**
     * Test that attempting to delete a system status throws a client exception
     *
     * @param int $statusID
     * @dataProvider deleteSystemStatusThrowsClientExceptionDataProvider
     */
    public function testDeleteSystemStatusThrowsClientException(int $statusID): void {
        $this->expectException(ClientException::class);
        $_ = $this->api()->delete("{$this->baseUrl}/{$statusID}");
    }

    /**
     * Data Provider for deleteSystemStatusThrowsClientException test
     *
     * @return iterable
     */
    public function deleteSystemStatusThrowsClientExceptionDataProvider(): iterable {
        foreach (RecordStatusModel::$systemDefinedIDs as $systemDefinedID) {
            yield "System Defined ID: {$systemDefinedID}" => [$systemDefinedID];
        }
    }

    /**
     * Test that attempting to delete a default status throws a ClientException
     */
    public function testDeleteDefaultStatusThrowsClientException(): void {
        // Create a default status
        $postBody = ["name" => __FUNCTION__.time(), 'recordSubType' => 'blah', 'isDefault' => true];
        $response = $this->api()->post($this->baseUrl, $postBody);
        $responseBody = $response->getBody();
        $this->assertArrayHasKey('isDefault', $responseBody);
        $this->assertTrue($responseBody['isDefault']);
        $this->assertArrayHasKey('statusID', $responseBody);
        $insertID = $responseBody['statusID'];

        //Attempt to delete it
        $this->expectException(ClientException::class);
        $_ = $this->api->delete("{$this->baseUrl}/{$insertID}");
    }

    /**
     * Test that DELETE /discussions/statuses/:id also deletes the corresponding ideation-specific status
     */
    public function testDeleteIdeationStatusSync(): void {
        $postBody = $this->getDiscussionIdeationStatusToPost();
        $response = $this->api()->post($this->baseUrl, $postBody);
        $statusToDelete = $response->getBody();

        $recordStatusID = intval($statusToDelete['statusID']);
        /** @var \StatusModel $statusModel */
        $statusModel = static::container()->get(\StatusModel::class);
        $rows = $statusModel->getWhere(['recordStatusID' => $recordStatusID])->resultArray();
        $this->assertCount(1, $rows, "Ideation Status not found");

        $this->api->delete("{$this->baseUrl}/{$recordStatusID}");

        $rows = $statusModel->getWhere(['recordStatusID' => $recordStatusID])->resultArray();
        $this->assertEmpty($rows, "Ideation Status to delete still exists");
    }

    /**
     * Test PATCH completes successfully but does not update system defined status when included in request.
     *
     * @param array $patchBody
     * @dataProvider patchDoesNotUpdateSystemStatusDataProvider
     */
    public function testPatchDoesNotUpdateSystemStatus(array $patchBody): void {
        $postBody = $this->testPost($this->record());
        $response = $this->api()->patch("{$this->baseUrl}/{$postBody['statusID']}", $patchBody);
        $responseBody = $response->getBody();
        $this->assertArrayHasKey('isSystem', $responseBody);
        $this->assertFalse($responseBody['isSystem']);
    }

    /**
     * Data Provider for patchDoesNotUpdateSystemStatus test
     *
     * @return iterable
     */
    public function patchDoesNotUpdateSystemStatusDataProvider(): iterable {
        yield 'isSystem = true' => [
            'patchBody' => ['isSystem' => true],
        ];
        yield 'isSystem = 1' => [
            'patchBody' => ['isSystem' => 1],
        ];
        yield 'isSystem = true [with other updates]' => [
            'patchBody' => ["name" => "fizz", 'isDefault' => true, 'isSystem' => true],
        ];
        yield 'isSystem = 1 [with other updates]' => [
            'patchBody' => ["name" => "fuzz", 'recordSubType' => 'fish', 'isSystem' => 1],
        ];
    }

    /**
     * Test that PATCH throws ClientException when statusID provided is system defined status
     *
     * @param int $statusID
     * @param array $patchBody
     * @dataProvider patchThrowsClientExceptionWhenSystemStatusIdIsSpecifiedDataProvider
     */
    public function testPatchThrowsClientExceptionWhenSystemStatusIdIsSpecified(int $statusID, array $patchBody): void {
        $this->expectException(ClientException::class);
        $_ = $this->api()->patch("{$this->baseUrl}/{$statusID}", $patchBody);
    }

    /**
     * Data Provider for patchThrowsClientExceptionWhenSystemStatusIdIsSpecified test
     *
     * @return iterable
     */
    public function patchThrowsClientExceptionWhenSystemStatusIdIsSpecifiedDataProvider(): iterable {
        foreach (RecordStatusModel::$systemDefinedIDs as $systemDefinedID) {
            yield "patching system status ID {$systemDefinedID}" => [
                'statusID' => $systemDefinedID,
                'patchBody' => ['name' => 'flip', 'recordSubType' => 'flop']
            ];
        }
    }

    /**
     * Test that a PATCH /api/v2/discussions/statuses/:id that specifies a discussion/ideation type status
     * auto-updates the corresponding ideation-specific status entry.
     */
    public function testPatchIdeationStatusSync(): void {
        $postBody = $this->getDiscussionIdeationStatusToPost();
        $response = $this->api()->post($this->baseUrl, $postBody);
        $statusToPatch = $response->getBody();

        $statusID = $statusToPatch[$this->pk];
        $newName = str_rot13($statusToPatch['name']);
        $newState = 'closed';

        $response = $this->api()->patch("{$this->baseUrl}/{$statusID}", ['name' => $newName, 'state' => $newState]);
        $responseBody = $response->getBody();
        $this->assertEquals($newName, $responseBody['name']);

        /** @var \StatusModel $statusModel */
        $statusModel = static::container()->get(\StatusModel::class);
        $rows = $statusModel->getWhere(['recordStatusID' => intval($statusID)])->resultArray();
        $this->assertCount(1, $rows);
        $row = array_shift($rows);
        $this->assertEquals($newName, $row['Name']);
        $this->assertEquals('Closed', $row['State']);
    }

    /**
     * Test POST completes successfully but does not update system defined status when included in request.
     */
    public function testPostDoesNotUpdateSystemStatus(): void {
        $postBody = ["name" => "flap", 'recordSubType' => 'fish', 'isSystem' => 1];
        $response = $this->api()->post($this->baseUrl, $postBody);
        $responseBody = $response->getBody();
        $this->assertArrayHasKey('isSystem', $responseBody);
        $this->assertFalse($responseBody['isSystem']);
    }

    /**
     * Test that a POST /api/v2/discussions/statuses that specifies a discussion/ideation type status
     * auto-creates the corresponding ideation-specific status entry.
     *
     * @throws \Garden\Container\ContainerException Not Applicable.
     * @throws \Garden\Container\NotFoundException Not Applicable.
     */
    public function testPostIdeationStatusSync(): void {
        $postBody = $this->getDiscussionIdeationStatusToPost();
        $response = $this->api()->post($this->baseUrl, $postBody);
        $responseBody = $response->getBody();
        $recordStatusID = $responseBody['statusID'];

        /** @var \StatusModel $statusModel */
        $statusModel = static::container()->get(\StatusModel::class);
        $rows = $statusModel->getWhere(['recordStatusID' => intval($recordStatusID)])->resultArray();
        $this->assertCount(1, $rows);
        $row = array_shift($rows);
        $this->assertEquals($postBody['name'], $row['Name']);
        $this->assertEqualsIgnoringCase($postBody['state'], $row['State']);
        $this->assertEquals($recordStatusID, $row['recordStatusID']);
        $this->assertEmpty($row['IsDefault']);
    }

    /**
     * Get an ideation-specific discussion status
     *
     * @param bool $isDefault
     * @return array
     */
    private function getDiscussionIdeationStatusToPost(bool $isDefault = false): array {
        static $count = 1;
        return [
            'name' => __FUNCTION__.$count++,
            'recordType' => 'discussion',
            'recordSubtype' => 'ideation',
            'state' => 'open',
            'isDefault' => $isDefault
        ];
    }
}
