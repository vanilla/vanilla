<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Vanilla\Controllers\Api\CollectionsApiController;
use Vanilla\CurrentTimeStamp;
use Vanilla\Models\CollectionModel;
use Vanilla\Models\CollectionRecordModel;
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

    private CollectionRecordModel $collectionRecordModel;
    private CollectionsApiController $collectionsApiController;

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
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->collectionRecordModel = $this->container()->get(CollectionRecordModel::class);
        $this->collectionsApiController = $this->container()->get(CollectionsApiController::class);
    }

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

        $response = $this->api()->post($this->baseUrl, $collectionRecord);
        $this->assertEquals(201, $response->getStatusCode());
        $collection = $response->getBody();
        $this->assertArraySubsetRecursive($collectionRecord, $collection);
        return $collection;
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
        $record = $record ?? $this->getrecord();

        $post = $record + $extra;
        $result = $this->api()->post($this->baseUrl, $post);

        $this->assertEquals(201, $result->getStatusCode());
        $body = $result->getBody();
        $this->assertArraySubsetRecursive($record, $body);
        $this->record = $body;
        return $body;
    }

    /**
     * Test duplicate collection name throws errors
     *
     * @param array $collection
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @depends testPost
     */
    public function testDuplicatedCollectionNameThrowErrors(array $collection): void
    {
        $collectionRecord = $this->getRecord();
        $collectionRecord["name"] = $collection["name"];
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("A collection with the name {$collectionRecord["name"]} already exists.");
        $this->api()->post($this->baseUrl, $collectionRecord);
    }

    /**
     * Test post endpoint, test for duplication prevention
     *
     */
    public function testPostNoDuplication()
    {
        $collectionRecord = $this->getRecord();
        $collectionRecord["name"] = "Marvel Collection";
        $collectionModel = $this->container()->get(CollectionModel::class);
        $this->testPost($collectionRecord);
        $updatedCollectionRecord = $collectionRecord;
        $category = $this->createCategory(["name" => "CG Category - new"]);
        $updatedCollectionRecord["records"] = [
            [
                "recordID" => $category["categoryID"],
                "recordType" => "category",
                "sort" => 1,
            ],
        ];
        $this->runWithExpectedExceptionMessage(
            "A collection with the name Marvel Collection already exists.",
            function () use ($updatedCollectionRecord) {
                $this->api()->post($this->baseUrl, $updatedCollectionRecord);
            }
        );
        // Making sure only 1 collection is created
        $collections = $collectionModel->select(["name" => $collectionRecord["name"]]);
        $this->assertCount(1, $collections);

        $newCollectionRecord = $this->getRecord();
        $this->testPost($newCollectionRecord);

        // New collection is created
        $collections = $collectionModel->select(["name" => $newCollectionRecord["name"]]);
        $this->assertCount(1, $collections);
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
        $this->assertArraySubsetRecursive($collectionRecord["records"][1], $result["records"][0]);
        $this->assertArraySubsetRecursive($collectionRecord["records"][0], $result["records"][1]);
    }

    /**
     * provide records for test
     *
     * @param string $sort
     * @return array
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
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
        $this->assertArraySubsetRecursive($updatedRecord["records"], $patchedResult["records"]);

        $this->assertArrayHasKey("dateInserted", $patchedResult);
        $this->assertIsString($patchedResult["dateInserted"]);
        $this->assertArrayHasKey("dateUpdated", $patchedResult);
        $this->assertIsString($patchedResult["dateUpdated"]);
        $this->assertIsInt($result["updateUserID"]);
        $this->assertEquals(\Gdn::session()->UserID, $result["updateUserID"]);
    }

    /**
     * Test that while patching a collection the users can't select same name for record if it already exists
     *
     * @return void
     */
    public function testCollectionPatchDuplicateNameValidation()
    {
        $collectionRecord = $this->getrecord();
        $collectionRecord["name"] = "Marvel Series Collection";
        $result = $this->api()->post($this->baseUrl, $collectionRecord);
        $collection1 = $result->getBody();
        $collectionRecord["name"] = "DC Series Collection";
        $result = $this->api()->post($this->baseUrl, $collectionRecord);
        $collection2 = $result->getBody();
        $newDiscussionRecord = $this->createDiscussion(["name" => "Avengers Epic"]);
        $newRecord = [
            "recordID" => $newDiscussionRecord["discussionID"],
            "recordType" => "discussion",
            "sort" => 1,
        ];
        $updatedRecord = [
            "name" => "DC Series Collection",
            "records" => [$newRecord],
        ];
        $this->runWithExpectedExceptionMessage(
            "A collection with the name DC Series Collection already exists.",
            function () use ($collection1, $updatedRecord) {
                $this->api()->patch($this->baseUrl . "/{$collection1["collectionID"]}", $updatedRecord);
            }
        );
        // We should be able to update the collection with the same name if it's the same collection
        $updatedRecord = [
            "name" => "Marvel Series Collection",
            "records" => [$newRecord],
        ];
        $result = $this->api()->patch($this->baseUrl . "/{$collection1["collectionID"]}", $updatedRecord);
        $this->assertEquals(200, $result->getStatusCode());
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
        $this->api()->get($url);
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
        // Create a collections with records
        $collectionOne = $this->testCollectionPost();

        // Verify that the collections show up when the get the full collection results based on locale
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

        // Add a new record to the collection
        $record = $this->getRecord()["records"][0];
        $response = $this->api()->put($this->baseUrl . "/by-resource", [
            "collectionIDs" => [$collectionOne["collectionID"]],
            "record" => $record,
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        // Check if the new records shows in the collection
        $response = $this->api()->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $collectionFullContent = $response->getBody();
        $this->assertEquals($collectionOne["collectionID"], $collectionFullContent["collectionID"]);
        $this->assertCount(count($collectionOne["records"]) + 1, $collectionFullContent["records"]);
        $this->assertContains($record["recordID"], array_column($collectionFullContent["records"], "recordID"));

        // Create a new collection and add the same record to the collection and see if it gets removed from the existing collection
        $collectionTwo = $this->testCollectionPost();
        $response = $this->api()->put($this->baseUrl . "/by-resource", [
            "collectionIDs" => [$collectionTwo["collectionID"]],
            "record" => $record,
        ]);

        // Now test that the collection doesn't have the record anymore
        $response = $this->api()->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $collectionFullContent = $response->getBody();
        $this->assertEquals($collectionOne["collectionID"], $collectionFullContent["collectionID"]);
        $this->assertCount(count($collectionOne["records"]), $collectionFullContent["records"]);
        $this->assertNotContains($record["recordID"], array_column($collectionFullContent["records"], "recordID"));
    }

    /**
     * Test GET /collections returns empty collections.
     *
     */
    public function testGetCollectionReturnsEmptyCollections()
    {
        // Create a collection with records.
        $collectionData = $this->testCollectionPost();

        // Remove records from the collection.
        $this->collectionRecordModel->delete(["collectionID" => $collectionData["collectionID"]]);

        $collections = $this->api()
            ->get($this->baseUrl)
            ->getBody();

        $found = false;
        foreach ($collections as $collection) {
            if ($collection["collectionID"] == $collectionData["collectionID"]) {
                $this->assertEquals([], $collection["records"]);
                $found = true;
            }
        }

        $this->assertTrue($found, "The collection was not found in the list of collections.");
    }

    /**
     * Test the results are filterable by `dateUpdated`.
     *
     * @return void
     */
    public function testCollectionFilterByDateUpdated()
    {
        $this->resetTable("collection");
        $this->resetTable("collectionRecord");

        $collectionRecords = [];

        $discussions[] = $this->createDiscussion();
        $discussions[] = $this->createDiscussion();

        foreach ($discussions as $discussion) {
            $collectionRecords[] = ["recordID" => $discussion["discussionID"], "recordType" => "discussion"];
        }

        // Create collection dated `2020-01-01 10:00:00`
        CurrentTimeStamp::mockTime("2020-01-01 10:00:00");
        $this->createCollection($collectionRecords);
        // Create collection dated `2021-01-01 10:00:00`
        CurrentTimeStamp::mockTime("2021-01-01 10:00:00");
        $this->createCollection($collectionRecords);
        // Create collection dated `2022-01-01 10:00:00`
        CurrentTimeStamp::mockTime("2022-01-01 10:00:00");
        $this->createCollection($collectionRecords);

        CurrentTimeStamp::mockTime("now");

        $results = $this->api()
            ->get($this->baseUrl, ["dateUpdated" => ">2019-01-01"])
            ->getBody();
        $this->assertCount(3, $results);

        $results = $this->api()
            ->get($this->baseUrl, ["dateUpdated" => ">2021-02-02"])
            ->getBody();
        $this->assertCount(1, $results);

        $results = $this->api()
            ->get($this->baseUrl, ["dateUpdated" => "<2025-01-01"])
            ->getBody();
        $this->assertCount(3, $results);
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

    /**
     * Test collections/Contents endpoint throws exception if provided with invalid locale
     *
     * @return void
     */
    public function testCollectionContentsThrowsClintExceptionOnInvalidLocale()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Invalid locale provided.");
        $this->api()->get($this->baseUrl . "/contents/ar");
    }

    /**
     * Test for /collections/contents endpoint
     *
     * @return array
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testCollectionContents(): array
    {
        $data = [];
        $this->resetTable("collection");
        $this->resetTable("collectionRecord");

        // Test that if there are no collections, the endpoint returns an empty array
        $response = $this->api()->get($this->baseUrl . "/contents/en");
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEmpty($body);

        $oldestDate = CurrentTimeStamp::mockTime(strtotime("-10 days"));

        // Create a category
        $this->createCategory(["name" => "Collection Test Category"]);
        //create 3 discussions
        $discussions = $this->createDiscussionSet(3);

        // Create two collections with the discussions
        $recordSet1 = [
            ["recordID" => $discussions[0]["discussionID"], "recordType" => "discussion", "sort" => 1],
            ["recordID" => $discussions[1]["discussionID"], "recordType" => "discussion", "sort" => 2],
        ];
        $recordSet2 = [
            ["recordID" => $discussions[1]["discussionID"], "recordType" => "discussion", "sort" => 1],
            ["recordID" => $discussions[2]["discussionID"], "recordType" => "discussion", "sort" => 2],
        ];

        $collection1 = $this->createCollection($recordSet1, ["name" => "Collection 1"]);
        $collection2 = $this->createCollection($recordSet2, ["name" => "Collection 2"]);

        // Add a new discussion to the existing collections
        $midDate = CurrentTimeStamp::mockTime(strtotime("-5 days"));
        $discussions = $this->createDiscussionSet(2);
        $recordSet1[] = ["recordID" => $discussions[0]["discussionID"], "recordType" => "discussion", "sort" => 3];
        $recordSet2[] = ["recordID" => $discussions[1]["discussionID"], "recordType" => "discussion", "sort" => 3];

        $recordset = end($recordSet1);
        // update the collections with new Data
        $this->addCollectionRecord($recordset, $collection1["collectionID"]);
        $recordset = end($recordSet2);
        $this->addCollectionRecord($recordset, $collection2["collectionID"]);

        // update the collections with new Data
        CurrentTimeStamp::clearMockTime();

        $discussions = $this->createDiscussionSet(2);
        $recordSet1[] = ["recordID" => $discussions[0]["discussionID"], "recordType" => "discussion", "sort" => 4];
        $recordSet2[] = ["recordID" => $discussions[1]["discussionID"], "recordType" => "discussion", "sort" => 4];

        $recordset = end($recordSet1);
        $this->addCollectionRecord($recordset, $collection1["collectionID"]);
        $recordset = end($recordSet2);
        $this->addCollectionRecord($recordset, $collection2["collectionID"]);

        $updatedCollection1 = $this->api()
            ->get($this->baseUrl . "/{$collection1["collectionID"]}")
            ->getBody();
        $updatedCollection2 = $this->api()
            ->get($this->baseUrl . "/{$collection2["collectionID"]}")
            ->getBody();

        // Test that we receive all the records when we add no filters
        $response = $this->api()->get($this->baseUrl . "/contents/en");
        $result = $response->getBody();
        $totalRecords = count($recordSet1) + count($recordSet2);
        $this->assertCount($totalRecords, $result);
        $record = $result[0];
        $keys = ["collectionID", "recordType", "recordID", "dateAddedToCollection", "sort", "record"];
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $record);
        }

        $head = $response->getHeaders();
        $this->assertStringContainsString("/api/v2/collections/contents/en?page=1", $head["X-App-Page-First-Url"][0]);
        $this->assertStringContainsString("/api/v2/collections/contents/en?page=1", $head["X-App-Page-Last-Url"][0]);
        return $data = [
            "records" => [$recordSet1, $recordSet2],
            "collections" => [$updatedCollection1, $updatedCollection2],
            "dates" => [$oldestDate, $midDate],
        ];
    }

    /**
     * Test for /collections/contents endpoint, filter by collectionID
     *
     * @param array $data
     * @return void
     * @depends testCollectionContents
     */
    public function testCollectionContentsFilterByCollectionID(array $data)
    {
        $collection1 = $data["collections"][0];
        $response = $this->api()->get($this->baseUrl . "/contents/en", [
            "collectionID" => $collection1["collectionID"],
        ]);
        $result = $response->getBody();
        $this->assertCount(count($collection1["records"]), $result);
        $recordIDs = array_column($result, "recordID");
        $this->assertEquals(array_column($collection1["records"], "recordID"), $recordIDs);
    }

    /**
     * Test for /collections/contents endpoint, filter by date
     *
     * @param array $data
     * @return void
     * @depends testCollectionContents
     */
    public function testCollectionContentsFilterByDateRange(array $data)
    {
        $expectedCount = count($data["records"][0]) + count($data["records"][1]) - 2; // eliminate the recently added records
        $oldestDate = $data["dates"][0];
        $midDate = $data["dates"][1];
        $startRange = $oldestDate->sub(new \DateInterval("P1D"))->format("Y-m-d");
        $endRange = $midDate->add(new \DateInterval("P1D"))->format("Y-m-d");
        $response = $this->api()->get($this->baseUrl . "/contents/en", [
            "dateAddedToCollection" => "[$startRange, $endRange]",
        ]);
        $result = $response->getBody();
        $this->assertCount($expectedCount, $result);
        $collectionRecords1 = array_column($data["collections"][0]["records"], "recordID");
        $collectionRecords2 = array_column($data["collections"][1]["records"], "recordID");
        array_pop($collectionRecords1);
        array_pop($collectionRecords2);
        $recordIDs = array_column($result, "recordID");
        $this->assertEquals(array_merge($collectionRecords1, $collectionRecords2), $recordIDs);
    }

    /**
     * Generate a set of dummy discussions for testing
     *
     * @param int $cnt
     * @return array
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    private function createDiscussionSet(int $cnt)
    {
        $discussions = [];
        for ($i = 0; $i < $cnt; $i++) {
            $discussions[] = $this->createDiscussion([
                "name" => "Collection Discussion -" . uniqid(),
                "categoryID" => $this->lastInsertedCategoryID,
            ]);
        }
        return $discussions;
    }

    /**
     * Add a new collection record to existing collection
     *
     * @param array $collectionRecord
     * @param int $collectionID
     * @return array
     */
    private function addCollectionRecord(array $collectionRecord, int $collectionID): array
    {
        $response = $this->api()->put($this->baseUrl . "/by-resource", [
            "record" => $collectionRecord,
            "collectionIDs" => [$collectionID],
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        return $response->getBody();
    }
}
