<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\ExpectExceptionTrait;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

class ContentGroupsApiControllerTest extends AbstractResourceTest
{
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;
    use CommunityApiTestTrait;

    protected $baseUrl = "/content-groups";

    protected $pk = "contentGroupID";

    /** @var int */
    protected static $counter = 0;

    /** @var null */
    protected $record = null;

    /** @var bool */
    protected $testPagingOnIndex = false;

    protected $patchFields = ["name", "records"];

    /**
     * @param array $contentRecord
     * @param array $expected
     * @return void
     * @dataProvider postValidationDataProvider
     */
    public function testPostValidation(array $contentRecord, array $expected)
    {
        $this->expectExceptionCode($expected[0]);
        $this->expectExceptionMessage($expected[1]);
        $this->api()->post($this->baseUrl, $contentRecord);
    }

    /**
     * Data provider for testPostValidation
     *
     * @return array
     */
    public function postValidationDataProvider(): array
    {
        return [
            [[], [400, "name is required. records is required."]],
            [$this->getDummyRecords("test-A", "", 1), [422, "records[0].recordType is required."]],
            [
                $this->getDummyRecords("test-A", ["discussion", "category"], 32),
                [422, "records must contain no more than 30 items."],
            ],
            [
                $this->getDummyRecords("test-A", "not-a-record-type", 1),
                [422, "The recordType not-a-record-type is not a valid record type for content groups."],
            ],
            [
                [
                    "name" => "bad-recordID",
                    "records" => [
                        [
                            "recordType" => "discussion",
                            "recordID" => 5000000,
                        ],
                    ],
                ],
                [404, "The record 5000000 with recordType discussion does not exist."],
            ],
        ];
    }

    /**
     * provide dummy records for validation tests
     *
     * @param string $name
     * @param mixed string|array $recordType
     * @param int $noOfRecords
     * @return array
     */
    public function getDummyRecords(string $name, $recordType, int $noOfRecords): array
    {
        $contentRecord = [
            "name" => $name,
        ];
        $records = [];
        for ($i = 0; $i < $noOfRecords; $i++) {
            $records[] = [
                "recordID" => $i + 1,
                "recordType" => is_string($recordType) ? $recordType : $recordType[array_rand($recordType, 1)],
                "sort" => rand(1, 30),
            ];
        }
        $contentRecord["records"] = $records;

        return $contentRecord;
    }

    /**
     * Test content-groups post request
     *
     * @return void
     */
    public function testContentGroupPost()
    {
        $contentGroupRecord = $this->getRecord();
        // Test permission error (403)
        $this->runWithUser(function () use ($contentGroupRecord) {
            $this->runWithExpectedExceptionCode(403, function () use ($contentGroupRecord) {
                $this->api()->post($this->baseUrl, $contentGroupRecord);
            });
        }, \UserModel::GUEST_USER_ID);

        parent::testPost($contentGroupRecord);
    }

    /**
     * Test post endpoint
     *
     * @param null $record
     * @param array $extra
     * @return array
     */
    public function testPost($record = null, array $extra = []): array
    {
        $this->record = $this->getrecord();

        return parent::testPost($this->record);
    }

    /**
     * Test content group get method
     *
     * @return void
     */
    public function testGet()
    {
        $result = parent::testGet();
        $this->assertSame($this->record["name"], $result["name"]);
        $this->assertArrayHasKey("dateInserted", $result);
        $this->assertIsString($result["dateInserted"]);
        $this->assertArrayHasKey("dateUpdated", $result);
        $this->assertIsString($result["dateUpdated"]);
        $this->assertIsInt($result["insertUserID"]);
        $this->assertEquals(\Gdn::session()->UserID, $result["insertUserID"]);
        $this->assertIsInt($result["updateUserID"]);
        $this->assertEquals(\Gdn::session()->UserID, $result["updateUserID"]);

        $this->assertEquals($this->record["records"], $result["records"]);

        // Test permission error (403)
        $this->runWithUser(function () use ($result) {
            $this->runWithExpectedExceptionCode(403, function () use ($result) {
                $this->api()->get("{$this->baseUrl}/{$result[$this->pk]}");
            });
        }, \UserModel::GUEST_USER_ID);
    }

    /**
     * Test the results are returned based on sort values
     *
     * @return void
     */
    public function testContentGroupReturnsResultSorted()
    {
        $contentGroupRecord = $this->getrecord("desc");
        $result = $this->api()->post($this->baseUrl, $contentGroupRecord);
        $this->assertSame($contentGroupRecord["name"], $result["name"]);
        $this->assertEquals($contentGroupRecord["records"][1], $result["records"][0]);
        $this->assertEquals($contentGroupRecord["records"][0], $result["records"][1]);
    }

    /**
     * provide records for test
     *
     * @param string $sort
     * @return array
     */
    public function getRecord(string $sort = "asc"): array
    {
        $cnt = self::$counter++;
        $category = $this->createCategory(["name" => "CG Category - $cnt"]);
        $discussion = $this->createDiscussion(["name" => "CG Discussion - $cnt"]);
        $contentGroupRecord = [
            "name" => "Content Group Record - $cnt",
            "records" => [
                [
                    "recordID" => $category["categoryID"],
                    "recordType" => "category",
                    "sort" => $sort == "asc" ? 1 : 2,
                ],
                [
                    "recordID" => $discussion["discussionID"],
                    "recordType" => "discussion",
                    "sort" => $sort == "asc" ? 2 : 1,
                ],
            ],
        ];

        return $contentGroupRecord;
    }

    /**
     * Test content group patch
     *
     * @return void
     */
    public function testContentGroupPatch()
    {
        $contentGroupRecord = $this->getrecord();
        $result = $this->api()->post($this->baseUrl, $contentGroupRecord);
        $record = end($contentGroupRecord["records"]);
        $record["sort"] = 10;
        $updatedRecord = [
            "name" => "Updated Content Group",
            "records" => [$record],
        ];

        $patchedResult = $this->api()->patch($this->baseUrl . "/{$result[$this->pk]}", $updatedRecord);
        $this->assertSame($updatedRecord["name"], $patchedResult["name"]);
        $this->assertCount(1, $patchedResult["records"]);
        $this->assertEquals($updatedRecord["records"], $patchedResult["records"]);

        $this->assertArrayHasKey("dateInserted", $patchedResult);
        $this->assertIsString($patchedResult["dateInserted"]);
        $this->assertArrayHasKey("dateUpdated", $patchedResult);
        $this->assertIsString($patchedResult["dateUpdated"]);
        $this->assertIsInt($result["updateUserID"]);
        $this->assertEquals(\Gdn::session()->UserID, $result["updateUserID"]);
    }

    /***
     * Test delete path
     */

    //skip all non required tests

    /**
     * @inheritDoc
     */
    public function testMainImageField()
    {
        $this->markTestSkipped("This resource doen't have endpoints with a format");
    }

    /**
     * @inheritDoc
     */
    public function testPostBadFormat(): void
    {
        $this->markTestSkipped("This resource doen't have endpoints with a format");
    }

    /**
     * @inheritDoc
     */
    public function testGetEdit($record = null)
    {
        $this->markTestSkipped("This resource doesn't have a GET /content-groups/{id}/edit endpoint");
    }
}
