<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

class PostTypesTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;

    protected $baseUrl = "/post-types";

    private $role;

    private $category;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);

        $this->role = $this->createRole();
        $this->category = $this->createCategory();
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->disableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
    }

    /**
     * Return a valid post type payload.
     *
     * @return array
     */
    public function record(): array
    {
        $salt = round(microtime(true) * 1000) . rand(1, 1000);
        return [
            "postTypeID" => "posttypeid-$salt",
            "parentPostTypeID" => "discussion",
            "name" => "posttypename-$salt",
            "isActive" => true,
            "isDeleted" => false,
            "roleIDs" => [$this->role["roleID"]],
            "categoryIDs" => [$this->category["categoryID"]],
        ];
    }

    /**
     * Test creating a post type with a duplicate post type ID results in an exception.
     *
     * @return void
     */
    public function testPostWithDuplicatePostType()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("This identifier is already used. Use a unique identifier.");
        $this->testPost(["postTypeID" => "duplicate-name"] + $this->record());
        $this->testPost(["postTypeID" => "duplicate-name"] + $this->record());
    }

    /**
     * Test that we get an exception when trying to create a post type when the feature is disabled.
     *
     * @return void
     */
    public function testPostWithFeatureNotEnabled()
    {
        $this->runWithConfig(
            [FeatureFlagHelper::featureConfigKey(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS) => false],
            function () {
                $this->expectExceptionCode(400);
                $this->expectExceptionMessage("Post Types & Post Fields is not enabled.");
                $this->testPost();
            }
        );
    }

    /**
     * Tests the index endpoint with various filters applied.
     *
     * @return void
     */
    public function testIndexWithFilters()
    {
        \Gdn::sql()->delete("postType", ["isOriginal" => false]);
        $newPostType = $this->testPost();

        $this->assertApiResults(
            "/post-types",
            [
                "isOriginal" => $newPostType["isOriginal"],
                "isActive" => $newPostType["isActive"],
                "isDeleted" => $newPostType["isDeleted"],
            ],
            [
                "name" => [$newPostType["name"]],
            ],
            count: 1
        );
    }

    /**
     * Basic patch test.
     *
     * @return void
     */
    public function testPatch()
    {
        $postType = $this->testPost();
        $category = $this->createCategory();

        $payload = [
            "name" => $postType["name"] . "updated",
            "isActive" => false,
            "categoryIDs" => [$category["categoryID"]],
        ];
        $postTypeUpdated = $this->api()
            ->patch($this->baseUrl . "/" . $postType["postTypeID"], $payload)
            ->getBody();
        $this->assertDataLike($payload, $postTypeUpdated);
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
        unset($record["postFieldIDs"]);
        $this->assertRowsEqual($record, $body);

        return $body;
    }

    /**
     * Test delete.
     *
     * @return array
     */
    public function testDelete()
    {
        $row = $this->testPost();

        $response = $this->api()->delete($this->baseUrl . "/" . $row["postTypeID"]);
        $this->assertSame(204, $response->getStatusCode());

        $this->runWithExpectedException(NotFoundException::class, function () use ($row) {
            $this->api()->get($this->baseUrl . "/" . $row["postTypeID"]);
        });

        return $row;
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
            ->get($this->baseUrl . "/" . $postField["postTypeID"])
            ->getBody();

        $this->assertDataLike(
            [
                "name" => $postField["name"],
            ],
            $result
        );
    }

    /**
     * Test that the index endpoint returns deleted records when supplied the includeDeleted filter.
     *
     * @return void
     * @depends testDelete
     */
    public function testIndexWithDeletedIncluded(array $postType)
    {
        $rows = $this->api()
            ->get($this->baseUrl, ["postTypeID" => $postType["postTypeID"], "includeDeleted" => true])
            ->getBody();
        $this->assertCount(1, $rows);
    }

    /**
     * Test validating that a parent post type exists.
     *
     * @return void
     */
    public function testParentPostTypeValidation()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("The selected parent post type does not exist");
        $this->testPost(["parentPostTypeID" => "doesntexist"] + $this->record());
    }

    /**
     * Test role ID validation.
     *
     * @return void
     */
    public function testPostWithInvalidRole()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Invalid role id: 9999");
        $this->testPost(["roleIDs" => [9999]]);
    }

    /**
     * Test category ID validation.
     *
     * @return void
     */
    public function testPostWithInvalidCategory()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("The category 9999 is not a valid category.");
        $this->testPost(["categoryIDs" => [9999]]);
    }

    /**
     * Test post field ID validation.
     *
     * @return void
     */
    public function testPostWithInvalidPostFields()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("The following post fields are invalid: not-a-valid-post-field");
        $this->testPost(["postFieldIDs" => ["not-a-valid-post-field"]]);
    }

    /**
     * Test associating post fields with a post type.
     *
     * @return void
     * @throws \Exception
     */
    public function testPostWithValidPostFields()
    {
        $postField1 = $this->createPostField();
        $postField2 = $this->createPostField();
        $postType = $this->testPost(["postFieldIDs" => [$postField1["postFieldID"], $postField2["postFieldID"]]]);

        $this->assertApiResults(
            "/post-fields",
            ["postTypeID" => $postType["postTypeID"]],
            ["postFieldID" => [$postField1["postFieldID"], $postField2["postFieldID"]]]
        );
    }

    /**
     * Test expand post fields on the post types api endpoint.
     *
     * @return void
     * @throws \Exception
     */
    public function testExpandPostFields()
    {
        $postField1 = $this->createPostField();
        $postField2 = $this->createPostField();
        $postType = $this->createPostType(["postFieldIDs" => [$postField1["postFieldID"], $postField2["postFieldID"]]]);

        $result = $this->api()
            ->get("/post-types/{$postType["postTypeID"]}", ["expand" => "postFields"])
            ->getBody();
        $this->assertArrayHasKey("postFields", $result);
        $this->assertRowsLike(
            ["postFieldID" => [$postField1["postFieldID"], $postField2["postFieldID"]]],
            $result["postFields"],
            true,
            2
        );
    }
}
