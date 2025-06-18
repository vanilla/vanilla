<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\CurrentTimeStamp;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\PostFieldModel;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

class PostFieldsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;

    protected $baseUrl = "/post-fields";

    protected $postTypeOne;

    protected $postTypeTwo;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
        \Gdn::sql()->truncate("postField");
        \Gdn::sql()->truncate("postTypePostFieldJunction");

        if (!isset($this->postTypeOne, $this->postTypeTwo)) {
            // Create post type fixtures.
            ["postTypeID" => $this->postTypeOne] = $this->createPostType();
            ["postTypeID" => $this->postTypeTwo] = $this->createPostType();
        }
    }

    /**
     * Return a valid post field payload.
     *
     * @return array
     */
    private function record(): array
    {
        $salt = round(microtime(true) * 1000) . rand(1, 1000);
        return [
            "postFieldID" => "postfieldid-$salt",
            "postTypeID" => $this->postTypeOne,
            "dataType" => "text",
            "label" => "field label",
            "description" => "field description",
            "formType" => "text",
            "visibility" => "public",
            "dropdownOptions" => null,
            "isRequired" => false,
            "isActive" => true,
        ];
    }

    /**
     * Test creating a post field with a duplicate post field ID results in an exception.
     *
     * @return void
     */
    public function testPostWithDuplicatePostField()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("This identifier is already in use. Use a unique identifier.");
        $this->testPost(["postFieldID" => "duplicate-name"]);
        $this->testPost(["postFieldID" => "duplicate-name"]);
    }

    /**
     * Tests the index endpoint with various filters applied.
     *
     * @return void
     */
    public function testIndexWithFilters()
    {
        $newPostField = $this->testPost();

        $this->assertApiResults(
            $this->baseUrl,
            [
                "label" => $newPostField["label"],
                "dataType" => $newPostField["dataType"],
                "formType" => $newPostField["formType"],
                "visibility" => $newPostField["visibility"],
                "isRequired" => $newPostField["isRequired"],
                "isActive" => $newPostField["isActive"],
            ],
            [
                "label" => [$newPostField["label"]],
            ],
            1
        );
    }

    /**
     * Test get.
     *
     * @return void
     */
    public function testGet()
    {
        $postField = $this->testPost();

        $result = $this->api()
            ->get($this->baseUrl . "/" . $postField["postFieldID"])
            ->getBody();

        $this->assertDataLike(
            [
                "label" => $postField["label"],
            ],
            $result
        );
    }

    /**
     * Test get with invalid path parameter.
     *
     * @return void
     */
    public function testGetWithInvalidPath()
    {
        $this->expectException(NotFoundException::class);
        $this->api()->get($this->baseUrl . "/abc");
    }

    /**
     * Test get when record does not exist.
     *
     * @return void
     */
    public function testGetWithValidPathButNoResult()
    {
        $this->expectException(NotFoundException::class);
        $this->api()->get($this->baseUrl . "/abc/def");
    }

    /**
     * Test patch.
     *
     * @return void
     */
    public function testPatch()
    {
        $postField = $this->testPost();

        $payload = [
            "label" => $postField["label"] . "updated",
            "description" => $postField["description"] . "updated",
            "visibility" => "private",
            "isRequired" => false,
            "isActive" => true,
        ];
        $postFieldUpdated = $this->api()
            ->patch($this->baseUrl . "/" . $postField["postFieldID"], $payload)
            ->getBody();
        $this->assertDataLike($payload, $postFieldUpdated);
    }

    /**
     * Test delete.
     *
     * @return void
     */
    public function testDelete()
    {
        $row = $this->testPost();

        $response = $this->api()->delete($this->baseUrl . "/" . $row["postFieldID"]);
        $this->assertSame(204, $response->getStatusCode());

        $this->runWithExpectedException(NotFoundException::class, function () use ($row) {
            $this->api()->get($this->baseUrl . "/" . $row["postFieldID"]);
        });
    }

    /**
     * Tests that the PUT /post-fields/sorts endpoint updates sort values and the order is reflected in the index endpoint.
     *
     * @return void
     */
    public function testSort()
    {
        $one = $this->testPost(["postFieldID" => "one"] + $this->record());
        $two = $this->testPost(["postFieldID" => "two"] + $this->record());
        $three = $this->testPost(["postFieldID" => "three"] + $this->record());

        $body = $this->api()
            ->get("/post-types", ["postTypeID" => $this->postTypeOne, "expand" => "postFields"])
            ->assertCount(1)
            ->getBody();

        $this->assertRowsLike(["postFieldID" => ["one", "two", "three"]], $body[0]["postFields"]);

        $this->api()->put("$this->baseUrl/sorts/{$this->postTypeOne}", [
            $one["postFieldID"] => 3,
            $two["postFieldID"] => 2,
            $three["postFieldID"] => 1,
        ]);

        $body = $this->api()
            ->get("/post-types", ["postTypeID" => $this->postTypeOne, "expand" => "postFields"])
            ->assertCount(1)
            ->getBody();

        $this->assertRowsLike(["postFieldID" => ["three", "two", "one"]], $body[0]["postFields"]);
    }

    /**
     * Test post.
     *
     * @param array $overrides
     * @return array
     */
    public function testPost(array $overrides = []): array
    {
        $record = $overrides + $this->record();
        $result = $this->api()->post($this->baseUrl, $record);

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        unset($record["postTypeID"], $body["postTypeID"]);
        $this->assertRowsEqual($record, $body);

        return $body;
    }

    /**
     * Test that isRequired can only be set for public or private post fields.
     *
     * @return void
     */
    public function testValidateIsRequired()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("To designate a field as required, visibility must be public or private.");
        $this->testPost(["isRequired" => true, "visibility" => "internal"]);
    }

    /**
     * Test creating a post field with incompatible dataType and formType properties.
     *
     * @return void
     */
    public function testValidateIncompatibleDataTypeAndFormType()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(
            "The dataType `number` is not compatible with `checkbox`. Valid formType values are: dropdown|number"
        );
        $this->testPost(["dataType" => "number", "formType" => "checkbox"]);
    }

    /**
     * Test creating a post field with invalid post field ID.
     *
     * @return void
     */
    public function testValidateInvalidPostFieldID()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Whitespace, slashes, periods and uppercase letters are not allowed");
        $this->testPost(["postFieldID" => "post field with spaces"]);
    }

    /**
     * Test creating a post field with post field IDs used by API filter middleware.
     *
     * @return void
     */
    public function testValidatePostFieldIDUsedInAPIFilterMiddleware()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("The following values are not allowed:");
        $this->testPost(["postFieldID" => "password"]);
    }

    /**
     * Test migration of post fields from old table structure.
     *
     * @return void
     */
    public function testMigrateFromOldPostFieldsStructure()
    {
        // Recreate table with old table structure.
        \Gdn::sql()->query("drop table GDN_postField;");
        \Gdn::sql()->query("drop table GDN_postTypePostFieldJunction;");
        \Gdn::sql()->query(
            <<<SQL
            create table `GDN_postField` (
            `postFieldID` varchar(100) not null,
            `postTypeID` varchar(100) not null,
            `label` varchar(100) not null,
            `description` varchar(500) null,
            `dataType` enum('text','boolean','date','number','string[]','number[]') not null,
            `formType` enum('text','text-multiline','dropdown','tokens','checkbox','date','number') not null,
            `visibility` enum('public','private','internal') not null,
            `displayOptions` json null,
            `dropdownOptions` json null,
            `isRequired` tinyint not null default 0,
            `isActive` tinyint not null default 0,
            `sort` tinyint not null default 0,
            `dateInserted` datetime not null,
            `dateUpdated` datetime null,
            `insertUserID` int not null,
            `updateUserID` int null,
            primary key (`postFieldID`, `postTypeID`)
            ) engine=innodb default character set utf8mb4 collate utf8mb4_unicode_ci;
SQL
        );

        // Create three records for testing. Two with the same post field ID.
        \Gdn::sql()->insert("postField", [
            "postFieldID" => "my-post-field",
            "postTypeID" => "discussion",
            "label" => "my post field",
            "dataType" => "text",
            "formType" => "text",
            "visibility" => "public",
            "dateInserted" => CurrentTimeStamp::getMySQL(),
            "insertUserID" => \Gdn::session()->UserID,
        ]);
        \Gdn::sql()->insert("postField", [
            "postFieldID" => "my-post-field",
            "postTypeID" => "question",
            "label" => "my post field",
            "dataType" => "text",
            "formType" => "text",
            "visibility" => "public",
            "dateInserted" => CurrentTimeStamp::getMySQL(),
            "insertUserID" => \Gdn::session()->UserID,
        ]);
        \Gdn::sql()->insert("postField", [
            "postFieldID" => "my-post-field2",
            "postTypeID" => "question",
            "label" => "my post field",
            "dataType" => "text",
            "formType" => "text",
            "visibility" => "public",
            "dateInserted" => CurrentTimeStamp::getMySQL(),
            "insertUserID" => \Gdn::session()->UserID,
        ]);

        // Run utility update to update table schemas and run the migration.
        \Gdn::getContainer()
            ->get(\UpdateModel::class)
            ->runStructure();

        // Test that three rows were inserted in the junction table.
        $results = \Gdn::sql()
            ->get("postTypePostFieldJunction")
            ->resultArray();
        $this->assertRowsLike(
            [
                "postTypeID" => ["discussion", "question", "question"],
            ],
            $results,
            false,
            3
        );
    }

    /**
     * Test that the users are able to view post fields based on their permissions.
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function testAvailableViewFieldsForCurrentSessionUser()
    {
        $this->createUserFixtures();
        $record = $this->record();
        $postFields = [];
        $postFields["privatePostField"] =
            [
                "postFieldID" => "text-field-private",
                "label" => "Discussion text field",
                "description" => "test txt field",
                "visibility" => "private",
            ] + $record;

        $postFields["publicPostField"] =
            [
                "postFieldID" => "text-meta-public",
                "label" => "Discussion text field 2",
                "description" => "test txt field",
                "visibility" => "public",
            ] + $record;
        $postFields["internalPostField"] =
            [
                "postFieldID" => "text-meta-internal",
                "label" => "Discussion text field 3",
                "description" => "test txt field",
                "visibility" => "internal",
            ] + $record;

        foreach ($postFields as $postField) {
            $this->createPostField($postField);
        }

        $postFieldIDs = array_column($postFields, "postFieldID");
        $this->runWithAdminUser(function () use ($postFieldIDs) {
            $availableFields = PostFieldModel::getAvailableViewFieldsForCurrentSessionUser();
            $availablePostFieldID = array_column($availableFields, "postFieldID");
            $this->assertEqualsCanonicalizing($postFieldIDs, $availablePostFieldID);
        });
        $this->runWithUser(function () {
            $availableFields = PostFieldModel::getAvailableViewFieldsForCurrentSessionUser();
            $availablePostFieldID = array_column($availableFields, "postFieldID");
            // Moderators have personal info view permission
            $this->assertContains("Garden.PersonalInfo.View", $this->getSession()->getPermissionsArray());
            $this->assertEqualsCanonicalizing(["text-meta-public", "text-field-private"], $availablePostFieldID);
        }, $this->moderatorID);

        $this->runWithUser(function () use ($postFieldIDs) {
            $availableFields = PostFieldModel::getAvailableViewFieldsForCurrentSessionUser();
            $availablePostFieldID = array_column($availableFields, "postFieldID");
            // Members get only public fields
            $this->assertEqualsCanonicalizing(["text-meta-public"], $availablePostFieldID);

            // Give member the permissions to view all fields
            $this->getSession()->setPermission([
                "Garden.PersonalInfo.View" => true,
                "Garden.InternalInfo.View" => true,
            ]);
            $availableFields = PostFieldModel::getAvailableViewFieldsForCurrentSessionUser();
            $availablePostFieldID = array_column($availableFields, "postFieldID");
            $this->assertEqualsCanonicalizing($postFieldIDs, $availablePostFieldID);
        }, $this->memberID);
    }

    /**
     * Test that we can filter post fields by one or more post types.
     *
     * @return void
     * @throws \Exception
     */
    public function testFilterByPostTypes()
    {
        // Orphan post field.
        $this->createPostField();

        $postTypeOnePostField = $this->createPostField();
        $postTypeOneAndTwoPostField = $this->createPostField();

        $this->api()->patch("/post-types/{$this->postTypeOne}", [
            "postFieldIDs" => [$postTypeOnePostField["postFieldID"], $postTypeOneAndTwoPostField["postFieldID"]],
        ]);

        $this->api()->patch("/post-types/{$this->postTypeTwo}", [
            "postFieldIDs" => [$postTypeOneAndTwoPostField["postFieldID"]],
        ]);

        // Should only get the two post fields assigned to the post types.
        $this->assertApiResults(
            "/post-fields",
            ["postTypeID" => "{$this->postTypeOne},{$this->postTypeTwo}"],
            ["postFieldID" => [$postTypeOnePostField["postFieldID"], $postTypeOneAndTwoPostField["postFieldID"]]],
            count: 2
        );
    }
}
