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
use VanillaTests\UsersAndRolesApiTestTrait;

class CategoriesPostTypesTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * @inheritdoc
     */
    public static function getAddons(): array
    {
        $addons = parent::getAddons();
        $addons[] = "QnA";
        return $addons;
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);

        // This is to simulate separate requests since the post/patch schema is cached in a local property.
        $this->container()->setInstance(\CategoriesApiController::class, null);
    }

    /**
     * @inheritdoc
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

        // When filtering by $postType1 we should get $category1 because we have an explicit association there,
        // and $category2 because $category2 does not have any explicit associations (allows any).
        $this->api()
            ->get("categories", [
                "postTypeID" => $postType1["postTypeID"],
                "categoryID" => "{$category1["categoryID"]},{$category2["categoryID"]},{$category3["categoryID"]}",
            ])
            ->assertJsonArrayValues(["categoryID" => [$category1["categoryID"], $category2["categoryID"]]]);
    }

    /**
     * Test that updating a category's custom post types also updates the legacy AllowedDiscussionTypes.
     *
     * @return void
     */
    public function testUpdatePostTypesAlsoUpdatesAllowedDiscussionTypes()
    {
        $category = $this->createCategory(["allowedPostTypeIDs" => ["discussion", "question"]]);
        $this->api()
            ->get("categories/{$category["categoryID"]}")
            ->assertJsonObjectLike(["allowedDiscussionTypes" => ["discussion", "question"]]);

        $this->api()->patch("categories/{$category["categoryID"]}", ["allowedPostTypeIDs" => ["discussion"]]);
        $this->api()
            ->get("categories/{$category["categoryID"]}")
            ->assertJsonObjectLike(["allowedDiscussionTypes" => ["discussion"]]);
    }

    /**
     * Test that updating a category's legacy AllowedDiscussionTypes also updates custom post types.
     *
     * @return void
     */
    public function testUpdateAllowedDiscussionTypesAlsoUpdatesPostTypes()
    {
        $category = $this->createCategory([
            "allowedDiscussionTypes" => ["Discussion", "Question"],
        ]);
        $this->api()
            ->get("categories/{$category["categoryID"]}")
            ->assertJsonObjectLike(["allowedPostTypeIDs" => ["discussion", "question"]]);

        $this->api()->patch("categories/{$category["categoryID"]}", ["allowedDiscussionTypes" => ["Discussion"]]);
        $this->api()
            ->get("categories/{$category["categoryID"]}")
            ->assertJsonObjectLike(["allowedPostTypeIDs" => ["discussion"]]);
    }

    /**
     * Smoke test to make sure category counts returned by the post type endpoint match categories endpoint with a post type filter.
     *
     * @return void
     */
    public function testCategoryCountsPerPostTypes()
    {
        $this->createCategory();
        $this->createCategory();

        $postType = $this->createPostType();

        $categories = $this->api()
            ->get("/categories", ["outputFormat" => "flat", "postTypeID" => $postType["postTypeID"]])
            ->getBody();

        $this->api()
            ->get("/post-types/{$postType["postTypeID"]}")
            ->assertJsonObjectLike(["countCategories" => count($categories)]);
    }

    /**
     * Test that invalid categories are not included in a post type's API response.
     *
     * @return void
     */
    public function testInvalidCategoriesExcluded()
    {
        $postType = $this->createPostType();

        $category = $this->createCategory();

        $catSpecialPerms = $this->createCategory();
        $user = $this->createUserWithCategoryPermissions($catSpecialPerms, [
            "discussions.view" => true,
            "discussions.add" => false,
        ]);

        $this->runWithUser(function () use ($postType, $category, $catSpecialPerms) {
            $responseBody = $this->api()
                ->get("/post-types/{$postType["postTypeID"]}")
                ->getBody();

            $responseBody = $this->api()
                ->get("/categories", [
                    "postTypeID" => $postType["postTypeID"],
                ])
                ->getBody();
            $categoryIDs = array_column($responseBody, "categoryID");

            $this->assertContains($category["categoryID"], $categoryIDs);
            $this->assertNotContains($catSpecialPerms["categoryID"], $categoryIDs);
        }, $user);
    }

    /**
     * Make sure we are excluding post types when crawling.
     *
     * @return void
     */
    public function testPostTypeExcludedWhenCrawl(): void
    {
        $postType = $this->createPostType(["postTypeID" => strtolower(__FUNCTION__)]);
        $this->createCategory([
            "hasRestrictedPostTypes" => true,
            "parentCategoryID" => null,
            "allowedPostTypeIDs" => [$postType["postTypeID"]],
        ]);

        $results = $this->api()
            ->get("categories")
            ->getBody();
        foreach ($results as $result) {
            $this->assertArrayHasKey(
                "allowedPostTypeOptions",
                $result,
                "allowedPostTypeOptions be present in the response for categories."
            );
        }

        $results = $this->api()
            ->get("categories", ["expand" => ["crawl"]])
            ->getBody();
        foreach ($results as $result) {
            $this->assertArrayNotHasKey(
                "allowedPostTypeOptions",
                $result,
                "allowedPostTypeOptions should not be present in the response for categories when crawling."
            );
        }
    }
}
