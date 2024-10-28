<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

class PostTypesTest extends AbstractResourceTest
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    protected $baseUrl = "/post-types";

    protected $pk = "postTypeID";

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
        \Gdn::sql()->truncate("postType");
    }

    /**
     * @inheritDoc
     */
    public function record(): array
    {
        $salt = round(microtime(true) * 1000) . rand(1, 1000);
        return [
            "apiName" => "apiname-$salt",
            "name" => "name",
            "baseType" => "discussion",
            "isActive" => true,
            "isDeleted" => false,
        ];
    }

    /**
     * @inheritDoc
     */
    public function testGetEdit($record = null)
    {
        $this->markTestSkipped("This resource doesn't have a GET /post-types/{id}/edit endpoint");
    }

    /**
     * Test creating a post type with a duplicate apiName results in an exception.
     *
     * @return void
     */
    public function testPostWithDuplicateApiName()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("This post type API name is already in use. Use a unique API name.");
        $this->testPost(["apiName" => "duplicateName"] + $this->record());
        $this->testPost(["apiName" => "duplicateName"] + $this->record());
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
        $newPostType = $this->testPost();

        $this->assertApiResults(
            "/post-types",
            [
                "apiName" => $newPostType["apiName"],
                "name" => $newPostType["name"],
                "baseType" => $newPostType["baseType"],
                "isOriginal" => $newPostType["isOriginal"],
                "isActive" => $newPostType["isActive"],
                "isDeleted" => $newPostType["isDeleted"],
            ],
            [
                "apiName" => [$newPostType["apiName"]],
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
}
