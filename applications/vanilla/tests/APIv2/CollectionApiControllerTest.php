<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\ExpectExceptionTrait;
use VanillaTests\TestLoggerTrait;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

class CollectionApiControllerTest extends AbstractResourceTest
{
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;
    use CommunityApiTestTrait;
    use TestLoggerTrait;

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
     * @return array
     */
    public function testCollectionPost(): array
    {
        $collectionRecord = $this->getRecord();
        // Test permission error (403)
        $this->runWithUser(function () use ($collectionRecord) {
            $this->runWithExpectedExceptionCode(403, function () use ($collectionRecord) {
                $this->api()->post($this->baseUrl, $collectionRecord);
            });
        }, \UserModel::GUEST_USER_ID);

        return parent::testPost($collectionRecord);
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
     * Test the GET /collections/by-resource endpoint with a valid requestBody.
     */
    public function testGetByResource(): void
    {
        $collection = $this->testCollectionPost();
        $record = $collection["records"][0];
        $collectionsByResource = $this->api()
            ->get($this->baseUrl . "/by-resource", [
                "recordID" => $record["recordID"],
                "recordType" => $record["recordType"],
            ])
            ->getBody();
        $this->assertCount(1, $collectionsByResource);
        $this->assertSame($collectionsByResource[0]["collectionID"], $collection["collectionID"]);
    }

    /**
     * Test GET /collections/by-resource with a resource that doesn't exist.
     */
    public function testGetByResourceWithNonExistentRecord(): void
    {
        $badRecord = [
            "recordID" => 9876543,
            "recordType" => "discussion",
        ];
        $collectionsByResource = $this->api()
            ->get($this->baseUrl . "/by-resource", [
                "recordID" => $badRecord["recordID"],
                "recordType" => $badRecord["recordType"],
            ])
            ->getBody();

        // Since the record doesn't exist, we should get back an empty array.
        $this->assertEmpty($collectionsByResource);
    }

    /**
     * Test adding a resource to a group of collections using the PUT /collections/by-resource endpoint.
     */
    public function testPutByResource(): void
    {
        $collectionOne = $this->testCollectionPost();
        $collectionTwo = $this->testCollectionPost();
        $record = $this->getRecord();
        $singleRecord = $record["records"][0];

        // Add the record to two collections.
        $this->api()->put($this->baseUrl . "/by-resource", [
            "collectionIDs" => [$collectionOne["collectionID"], $collectionTwo["collectionID"]],
            "record" => [
                "recordID" => $singleRecord["recordID"],
                "recordType" => $singleRecord["recordType"],
                "sort" => $singleRecord["sort"],
            ],
        ]);

        // We should get the two collections back when we call the GET /collections/by-resource endpoint.
        $returnedCollections = $this->api()
            ->get($this->baseUrl . "/by-resource", [
                "recordID" => $singleRecord["recordID"],
                "recordType" => $singleRecord["recordType"],
            ])
            ->getBody();
        $this->assertCount(2, $returnedCollections);

        // When we get the full record of each collection, the record we added should be there.
        foreach ($returnedCollections as $collection) {
            $fetchedCollection = $this->api()
                ->get($this->baseUrl . "/{$collection["collectionID"]}")
                ->getBody();
            $records = array_map(function ($record) {
                return "{$record["recordType"]}_{$record["recordID"]}";
            }, $fetchedCollection["records"]);
            $this->assertContains("{$singleRecord["recordType"]}_{$singleRecord["recordID"]}", $records);
        }
    }

    /**
     * Test adding a record to a group of collections (one of which doesn't exist) using the PUT /collections/by-resource endpoint.
     */
    public function testPutByResourceWithNonExistentCollection(): void
    {
        $collectionOne = $this->testCollectionPost();
        $collectionTwo = $this->testCollectionPost();
        $phantomCollectionID = 999999;
        $record = $this->getRecord();
        $singleRecord = $record["records"][0];

        // Add the record to the two real and one phantom collections.
        $this->api()->put($this->baseUrl . "/by-resource", [
            "collectionIDs" => [$collectionOne["collectionID"], $collectionTwo["collectionID"], $phantomCollectionID],
            "record" => [
                "recordID" => $singleRecord["recordID"],
                "recordType" => $singleRecord["recordType"],
                "sort" => $singleRecord["sort"],
            ],
        ]);

        // Verify that we logged an error message for the phantom collection.
        $this->getTestLogger()->hasMessage(
            "Failed to add record {$singleRecord["recordType"]}_{$singleRecord["recordID"]} to collection {$phantomCollectionID}"
        );

        // But the records should still have been added to the other two collections.
        $returnedCollections = $this->api()
            ->get($this->baseUrl . "/by-resource", [
                "recordID" => $singleRecord["recordID"],
                "recordType" => $singleRecord["recordType"],
            ])
            ->getBody();
        $this->assertCount(2, $returnedCollections);
        foreach ($returnedCollections as $collection) {
            $fetchedCollection = $this->api()
                ->get($this->baseUrl . "/{$collection["collectionID"]}")
                ->getBody();
            $records = array_map(function ($record) {
                return "{$record["recordType"]}_{$record["recordID"]}";
            }, $fetchedCollection["records"]);
            $this->assertContains("{$singleRecord["recordType"]}_{$singleRecord["recordID"]}", $records);
        }
    }

    /** Test adding and removing records from collections also resets the cache data */
    public function testPutByResourceClearsCache(): void
    {
        //create a collections with records
        $collectionOne = $this->testCollectionPost();

        //Verify that the collections show up when the get the full collection results based on locale
        $locale = \Gdn::locale()->current() ?: "en";
        $url = $this->baseUrl . "/content/{$collectionOne[$this->pk]}/$locale";
        $response = $this->api()->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $collectionFullContent = $response->getBody();

        $this->assertEquals($collectionOne["collectionID"], $collectionFullContent["collectionID"]);
        $this->assertCount(count($collectionOne["records"]), $collectionFullContent["records"]);

        $this->assertEqualsCanonicalizing(
            array_column($collectionOne["records"], "recordID"),
            array_column($collectionFullContent["records"], "recordID")
        );

        //Add a new record to the collection

        $record = $this->getRecord()["records"][0];
        $response = $this->api()->put($this->baseUrl . "/by-resource", [
            "collectionIDs" => [$collectionOne["collectionID"]],
            "record" => $record,
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        //Check if the new records shows in the collection
        $response = $this->api()->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $collectionFullContent = $response->getBody();
        $this->assertEquals($collectionOne["collectionID"], $collectionFullContent["collectionID"]);
        $this->assertCount(count($collectionOne["records"]) + 1, $collectionFullContent["records"]);
        $this->assertContains($record["recordID"], array_column($collectionFullContent["records"], "recordID"));

        //create a new collection and add the same record to the collection and see if it gets removed from the existing collection

        $collectionTwo = $this->testCollectionPost();
        $response = $this->api()->put($this->baseUrl . "/by-resource", [
            "collectionIDs" => [$collectionTwo["collectionID"]],
            "record" => $record,
        ]);

        //Now test that the collection doesn't have the record anymore
        $response = $this->api()->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $collectionFullContent = $response->getBody();
        $this->assertEquals($collectionOne["collectionID"], $collectionFullContent["collectionID"]);
        $this->assertCount(count($collectionOne["records"]), $collectionFullContent["records"]);
        $this->assertNotContains($record["recordID"], array_column($collectionFullContent["records"], "recordID"));
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
