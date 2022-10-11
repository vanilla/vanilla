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

class CollectionApiControllerTest extends AbstractResourceTest
{
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;
    use CommunityApiTestTrait;

    protected $baseUrl = "/collections";

    protected $pk = "collectionID";

    /** @var int */
    protected static $counter = 0;

    /** @var null */
    protected $record = null;

    /** @var bool */
    protected $testPagingOnIndex = false;

    protected $patchFields = ["name", "records"];

    /**
     * @param array $collectionRecord
     * @param array $expected
     * @return void
     * @dataProvider postValidationDataProvider
     */
    public function testPostValidation(array $collectionRecord, array $expected)
    {
        $this->expectExceptionCode($expected[0]);
        $this->expectExceptionMessage($expected[1]);
        $this->api()->post($this->baseUrl, $collectionRecord);
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
                [422, "The recordType not-a-record-type is not a valid record type for collection."],
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
        $collectionRecord = [
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
        $collectionRecord["records"] = $records;

        return $collectionRecord;
    }

    /**
     * Test collection post request
     *
     * @return void
     */
    public function testCollectionPost()
    {
        $collectionRecord = $this->getRecord();
        // Test permission error (403)
        $this->runWithUser(function () use ($collectionRecord) {
            $this->runWithExpectedExceptionCode(403, function () use ($collectionRecord) {
                $this->api()->post($this->baseUrl, $collectionRecord);
            });
        }, \UserModel::GUEST_USER_ID);

        parent::testPost($collectionRecord);
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
     * Test collection get method
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
    public function testCollectionReturnsResultSorted()
    {
        $collectionRecord = $this->getrecord("desc");
        $result = $this->api()->post($this->baseUrl, $collectionRecord);
        $this->assertSame($collectionRecord["name"], $result["name"]);
        $this->assertEquals($collectionRecord["records"][1], $result["records"][0]);
        $this->assertEquals($collectionRecord["records"][0], $result["records"][1]);
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
        $collectionRecord = [
            "name" => "Collection Record - $cnt",
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

        return $collectionRecord;
    }

    /**
     * Test collection patch
     *
     * @return void
     */
    public function testCollectionPatch()
    {
        $collectionRecord = $this->getrecord();
        $result = $this->api()->post($this->baseUrl, $collectionRecord);
        $record = end($collectionRecord["records"]);
        $record["sort"] = 10;
        $updatedRecord = [
            "name" => "Updated collections",
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
     * Test collection record content endpoint.
     * @return void
     */
    public function testGetCollectionContent()
    {
        $collection = $this->getrecord();
        $locale = \Gdn::locale()->current() ?: "en";
        $collectionRecord = $this->api()
            ->post($this->baseUrl, $collection)
            ->getBody();
        $url = $this->baseUrl . "/content/{$collectionRecord[$this->pk]}/$locale";
        $response = $this->api()->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $collectionRecordContent = $response->getBody();
        $this->assertArrayHasKey("records", $collectionRecordContent);
        $records = $collectionRecordContent["records"];

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
        $collectionRecordContent = $response->getBody();
        $this->assertCount(count($records) - 1, $collectionRecordContent["records"]);
        $records = $collectionRecordContent["records"];
        $this->assertEquals("category", $records[0]["recordType"]);
        $this->assertEquals($categoryID, $records[0]["recordID"]);

        //Test that updating an existing collection record invalidates the cache and gives back updated records
        $this->testCollectionRecordOnUpdate($collection, $collectionRecordContent[$this->pk]);

        //test on delete collection gives back error
        $this->api()->delete($this->baseUrl . "/{$collectionRecordContent[$this->pk]}");
        $this->expectExceptionCode(404);
        $response = $this->api()->get($url);
    }

    /**
     * Test that a collection record update flushes the cache and returns the proper result
     * @param array $collectionRecord
     * @param int $collectionID
     * @return void
     */
    private function testCollectionRecordOnUpdate(array $collectionRecord, int $collectionID)
    {
        $collectionRecord["name"] = "Updated collection";
        $collectionRecord["records"][0]["sort"] = 3;
        unset($collectionRecord["records"][1]);
        $category = $this->createCategory(["name" => "CG Category for patch "]);
        $discussion = $this->createDiscussion(["name" => "Discussion for patch category"]);
        $collectionRecord["records"][] = [
            "recordID" => $category["categoryID"],
            "recordType" => "category",
            "sort" => "2",
        ];
        $collectionRecord["records"][] = [
            "recordID" => $discussion["discussionID"],
            "recordType" => "discussion",
            "sort" => "1",
        ];
        $response = $this->api()->patch($this->baseUrl . "/$collectionID", $collectionRecord);
        $this->assertEquals(200, $response->getStatusCode());
        $locale = \Gdn::locale()->current() ?: "en";
        $url = $this->baseUrl . "/content/$collectionID/$locale";
        $response = $this->api()->get($url);
        $updatedCollectionRecord = $response->getBody();
        $this->assertEquals($updatedCollectionRecord["name"], $collectionRecord["name"]);
        $records = $updatedCollectionRecord["records"];
        $this->assertCount(count($collectionRecord["records"]), $records);

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
        $this->markTestSkipped("This resource doesn't have a GET /collections/{id}/edit endpoint");
    }
}
