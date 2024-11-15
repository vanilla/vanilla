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

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
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
            1
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

        $payload = [
            "name" => $postType["name"] . "updated",
            "isActive" => false,
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
}
