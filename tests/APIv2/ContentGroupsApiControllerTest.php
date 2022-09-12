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

    /**
     * Test Content group content endpoint.
     * @return void
     */
    public function testGetContentGroupContent()
    {
        $contentGroupRecord = $this->getrecord();
        $locale = \Gdn::locale()->current() ?: "en";
        $contentRecord = $this->api()
            ->post($this->baseUrl, $contentGroupRecord)
            ->getBody();
        $url = $this->baseUrl . "/content/{$contentRecord[$this->pk]}/$locale";
        $response = $this->api()->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $contentRecordContent = $response->getBody();
        $this->assertArrayHasKey("records", $contentRecordContent);
        $records = $contentRecordContent["records"];

        //Verify content record matches its corresponding schema definition
        foreach ($records as $record) {
            $this->assertArrayHasKey("record", $record);
            $this->assertIsArray($record["record"]);
            if ($record["recordType"] == "category") {
                $this->verifyCategoryData($record["record"]);
            } elseif ($record["recordType"] == "discussion") {
                $this->verifyDiscussionData($record["record"]);
            }
        }

        //The content no longer exists and should be ignored
        $this->api()->delete("/discussions" . "/{$records[1]["recordID"]}");
        $categoryID = $records[0]["recordID"];
        $response = $this->api()->get($url);
        $contentRecordContent = $response->getBody();
        $this->assertCount(count($records) - 1, $contentRecordContent["records"]);
        $records = $contentRecordContent["records"];
        $this->assertEquals("category", $records[0]["recordType"]);
        $this->assertEquals($categoryID, $records[0]["recordID"]);

        //Test that updating an existing content record invalidates the cache and gives back updated records
        $this->testContentGroupContentOnUpdate($contentGroupRecord, $contentRecordContent[$this->pk]);

        //test on delete content group gives back error
        $this->api()->delete($this->baseUrl . "/{$contentRecordContent[$this->pk]}");
        $this->expectExceptionCode(404);
        $response = $this->api()->get($url);
    }

    /**
     * Test that a content group record update flushes the cache and returns the proper result
     * @param array $contentGroupRecord
     * @param int $contentGroupID
     * @return void
     */
    private function testContentGroupContentOnUpdate(array $contentGroupRecord, int $contentGroupID)
    {
        $contentGroupRecord["name"] = "Updated ContentRecord";
        $contentGroupRecord["records"][0]["sort"] = 3;
        unset($contentGroupRecord["records"][1]);
        $category = $this->createCategory(["name" => "CG Category for patch "]);
        $discussion = $this->createDiscussion(["name" => "Discussion for patch category"]);
        $contentGroupRecord["records"][] = [
            "recordID" => $category["categoryID"],
            "recordType" => "category",
            "sort" => "2",
        ];
        $contentGroupRecord["records"][] = [
            "recordID" => $discussion["discussionID"],
            "recordType" => "discussion",
            "sort" => "1",
        ];
        $response = $this->api()->patch($this->baseUrl . "/$contentGroupID", $contentGroupRecord);
        $this->assertEquals(200, $response->getStatusCode());
        $locale = \Gdn::locale()->current() ?: "en";
        $url = $this->baseUrl . "/content/$contentGroupID/$locale";
        $response = $this->api()->get($url);
        $updatedContentGroupRecord = $response->getBody();
        $this->assertEquals($updatedContentGroupRecord["name"], $contentGroupRecord["name"]);
        $records = $updatedContentGroupRecord["records"];
        $this->assertCount(count($contentGroupRecord["records"]), $records);

        //check the data is sorted
        $this->assertEquals($discussion["discussionID"], $records[0]["recordID"]);
        $this->assertEquals("discussion", $records[0]["recordType"]);
        $this->assertIsArray($records[0]["record"]);

        $this->assertEquals($category["categoryID"], $records[1]["recordID"]);
        $this->assertEquals("category", $records[1]["recordType"]);
    }

    /**
     * Verify the required category data are returned
     * @param array $record
     * @return void
     */
    private function verifyCategoryData(array $record)
    {
        $keys = [
            "categoryID",
            "name",
            "description",
            "parentCategoryID",
            "customPermissions",
            "isArchived",
            "urlcode",
            "url",
            "displayAs",
            "countCategories",
            "countDiscussions",
            "countComments",
            "countAllDiscussions",
            "countAllComments",
            "allowedDiscussionTypes",
            "depth",
            "sort",
        ];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $record);
        }
        $this->assertArrayNotHasKey("followed", $record);
    }

    /**
     * verify discussion expanded fields are part of the record data
     * @param array $record
     * @return void
     */
    private function verifyDiscussionData(array $record)
    {
        $this->assertArrayNotHasKey("body", $record);
        $this->assertArrayHasKey("excerpt", $record);

        //Check to see if the record has expanded fields
        $expandedKeys = ["category", "insertUser", "lastUser", "lastPost", "tags", "breadcrumbs", "status"];
        foreach ($expandedKeys as $key) {
            $this->assertArrayHasKey($key, $record);
            $this->assertIsArray($record[$key]);
        }
    }

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
