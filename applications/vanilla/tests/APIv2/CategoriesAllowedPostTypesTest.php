<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

class CategoriesAllowedPostTypesTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);

        // This is to simulate separate requests since the post/patch schema is cached in a local property.
        $this->container()->setInstance(\CategoriesApiController::class, null);
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
     * Test saving allowed post types when creating a category.
     *
     * @return array
     */
    public function testPostWithPostTypes()
    {
        $postType = $this->createPostType();
        $category = $this->createCategory([
            "hasRestrictedPostTypes" => true,
            "allowedPostTypeIDs" => [$postType["postTypeID"]],
        ]);
        $this->assertEquals([$postType["postTypeID"]], $category["allowedPostTypeIDs"]);
        return $category;
    }

    /**
     * Test creating a category with inactive post types results in an exception.
     *
     * @return void
     */
    public function testPostWithInactivePostTypes()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("allowedPostTypeIDs[0] must be one of: ");
        $postType = $this->createPostType(["isActive" => false]);
        $this->createCategory(["hasRestrictedPostTypes" => true, "allowedPostTypeIDs" => [$postType["postTypeID"]]]);
    }

    /**
     * Test creating a category with inactive post types results in an exception.
     *
     * @return void
     */
    public function testPostWithDeletedPostTypes()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("allowedPostTypeIDs[0] must be one of: ");
        $postType = $this->createPostType(["isDeleted" => true]);
        $this->createCategory(["hasRestrictedPostTypes" => true, "allowedPostTypeIDs" => [$postType["postTypeID"]]]);
    }

    /**
     * Test saving allowed post types when updating a category.
     *
     * @param array $category
     * @return void
     * @depends testPostWithPostTypes
     */
    public function testPatchAllowedPostTypes(array $category): void
    {
        $postType = $this->createPostType();
        $result = $this->api()
            ->patch("categories/{$category["categoryID"]}", [
                "hasRestrictedPostTypes" => true,
                "allowedPostTypeIDs" => [$postType["postTypeID"], $postType["postTypeID"]],
            ])
            ->getBody();
        $actual = $result["allowedPostTypeIDs"];
        $this->assertEquals([$postType["postTypeID"]], $actual);
    }

    /**
     * Test that allowedDiscussionTypes cannot be used with allowedPostTypeIDs.
     *
     * @return void
     */
    public function testAllowedDiscussionTypesWithPostTypes()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Only one of allowedDiscussionTypes, allowedPostTypeIDs are allowed");
        $category = $this->createCategory();
        $this->api()
            ->patch("categories/{$category["categoryID"]}", [
                "hasRestrictedPostTypes" => true,
                "allowedPostTypeIDs" => ["discussion"],
                "allowedDiscussionTypes" => ["Discussion"],
            ])
            ->getBody();
    }

    /**
     * Test filtering categories by allowed post type IDs.
     *
     * @return void
     */
    public function testIndexFilterByPostType()
    {
        $postType1 = $this->createPostType(["postTypeID" => "abcd1"]);
        $postType2 = $this->createPostType(["postTypeID" => "efgh2"]);

        $category1 = $this->createCategory([
            "hasRestrictedPostTypes" => true,
            "parentCategoryID" => null,
            "allowedPostTypeIDs" => [$postType1["postTypeID"]],
        ]);
        $category2 = $this->createCategory(["parentCategoryID" => null]);
        $category3 = $this->createCategory([
            "hasRestrictedPostTypes" => true,
            "parentCategoryID" => null,
            "allowedPostTypeIDs" => [$postType2["postTypeID"]],
        ]);

        // When filtering by $postType1
        // We should only get $category1 because we have an explicit association there.
        $categories = $this->api()
            ->get("categories", [
                "postTypeID" => $postType1["postTypeID"],
                "categoryID" => "{$category1["categoryID"]},{$category2["categoryID"]},{$category3["categoryID"]}",
            ])
            ->getBody();

        $this->assertRowsLike(["categoryID" => [$category1["categoryID"]]], $categories, false, 1);
    }
}
