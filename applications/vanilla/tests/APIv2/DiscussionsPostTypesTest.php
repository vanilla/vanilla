<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\PostFieldModel;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Various tests related to post types and post fields.
 */
class DiscussionsPostTypesTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /** @var \DiscussionModel */
    private $discussionModel;

    /**
     * @inheritDoc
     */
    public static function getAddons(): array
    {
        $addons = parent::getAddons();
        $addons[] = "qna";
        return $addons;
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
        $this->discussionModel = \Gdn::getContainer()->get(\DiscussionModel::class);

        $this->container()->setInstance(\CategoriesApiController::class, null);
    }

    /**
     * Test role validation on posting discussions with a restricted post type.
     *
     * @return void
     */
    public function testPostTypeRoleValidation()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("not a valid post type");

        $role = $this->createRole();
        $postType = $this->createPostType(["roleIDs" => [$role["roleID"]]]);

        // Create a user that has the member role required to post discussions but not the role required for the post type.
        $user = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID]]);

        $this->runWithUser(function () use ($postType) {
            $this->createDiscussion(["postTypeID" => $postType["postTypeID"]]);
        }, $user);
    }

    /**
     * Test category validation when posting in a category that has restricted post types.
     *
     * @return void
     */
    public function testPostTypeCategoryValidation()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("not a valid post type");

        $this->createCategory(["hasRestrictedPostTypes" => true, "allowedPostTypeIDs" => ["discussion"]]);
        $postType = $this->createPostType();
        $this->createDiscussion(["postTypeID" => $postType["postTypeID"]]);
    }

    /**
     * Test creating a discussion with a post type that has no required fields.
     *
     * @return void
     */
    public function testPostWithPostTypeAndNoRequiredFields()
    {
        $this->createPostField(["postTypeID" => "discussion", "isRequired" => false]);
        $discussion = $this->createDiscussion(["postTypeID" => "discussion"]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertArrayHasKey("postTypeID", $discussion);
        $this->assertEquals("discussion", $discussion["postTypeID"]);
    }

    /**
     * Test creating a discussion with post fields but the post type is not sent.
     *
     * @return void
     */
    public function testPostWithPostFieldsAndNoPostTypeSupplied()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessageMatches("/value requires postTypeID to be present/");

        $postField = $this->createPostField(["postTypeID" => "discussion", "isRequired" => false]);
        $this->createDiscussion(["postFields" => [$postField["postFieldID"] => "abcd"]]);
    }

    /**
     * Test creating a discussion with a post type that has required fields, but they are not sent in the request.
     *
     * @return void
     */
    public function testPostWithPostTypeAndRequiredFieldsAreMissing()
    {
        $postField = $this->createPostField(["postTypeID" => "discussion", "isRequired" => true]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("postFields.{$postField["postFieldID"]} is required");

        $this->createDiscussion(["postTypeID" => "discussion"]);
    }

    /**
     * Test creating a discussion with a post type that has required fields, and they are sent in the request.
     *
     * @return array
     */
    public function testPostWithPostTypeAndRequiredFieldsAreIncluded(): array
    {
        $this->expectNotToPerformAssertions();

        $postType = $this->createPostType();
        $postField = $this->createPostField(["postTypeID" => $postType["postTypeID"], "isRequired" => true]);

        $discussion = $this->createDiscussion([
            "postTypeID" => $postType["postTypeID"],
            "postFields" => [$postField["postFieldID"] => "abcd"],
        ]);

        return [$discussion, $postField];
    }

    /**
     * Test updating post fields with the patch discussion endpoint.
     *
     * @return void
     * @depends testPostWithPostTypeAndRequiredFieldsAreIncluded
     */
    public function testPatchWithPostFields(array $dependencies)
    {
        $this->expectNotToPerformAssertions();
        [$discussion, $postField] = $dependencies;

        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "postFields" => [$postField["postFieldID"] => "efghi"],
        ]);
    }

    /**
     * Test the post fields expander on the `GET /discussions/{discussionID}` endpoint.
     *
     * @return void
     */
    public function testGetDiscussionWithPostFieldsExpand()
    {
        $postType = $this->createPostType();
        $postField = $this->createPostField(["postTypeID" => $postType["postTypeID"], "isRequired" => true]);

        $discussion = $this->createDiscussion([
            "postTypeID" => $postType["postTypeID"],
            "postFields" => [$postField["postFieldID"] => "abcdef"],
        ]);
        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postMeta"])
            ->getBody();
        $this->assertArrayHasKey("postFields", $discussion);
        $this->assertIsArray($discussion["postFields"]);
        $this->assertArrayHasKey($postField["postFieldID"], $discussion["postFields"]);
        $this->assertEquals("abcdef", $discussion["postFields"][$postField["postFieldID"]]);
    }

    /**
     * Provide test post field data.
     *
     * @return \Generator
     */
    public function provideValidPostFieldValues(): \Generator
    {
        yield "text" => ["text", "text", "abc", "abc"];
        yield "number" => ["number", "number", 100, 100];
        yield "boolean" => ["boolean", "checkbox", "true", true];
        yield "date" => ["date", "date", "2022-09-01T05:54:26.990Z", "2022-09-01T05:54:26+00:00"];
        yield "string[]" => ["string[]", "dropdown", ["hey", "ho"], ["hey", "ho"]];
        yield "number[]" => ["number[]", "dropdown", [4, 5], [4, 5]];
    }

    /**
     * Test validation works when calling the discussion post endpoint with different post field types.
     *
     * @param $dataType
     * @param $formType
     * @param $value
     * @param $responseValue
     * @return void
     * @dataProvider provideValidPostFieldValues
     */
    public function testValidationOfPostFields($dataType, $formType, $value, $responseValue)
    {
        $postType = $this->createPostType();
        $postField = $this->createPostField([
            "postTypeID" => $postType["postTypeID"],
            "dataType" => $dataType,
            "formType" => $formType,
        ]);

        $discussion = $this->createDiscussion([
            "postTypeID" => $postType["postTypeID"],
            "postFields" => [$postField["postFieldID"] => $value],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postMeta"])
            ->getBody();
        $this->assertArrayHasKey("postFields", $discussion);
        $this->assertArrayHasKey($postField["postFieldID"], $discussion["postFields"]);
        $this->assertEquals($responseValue, $discussion["postFields"][$postField["postFieldID"]]);
    }

    /**
     * Test validation that `postTypeID` cannot be used with `type`.
     *
     * @return void
     */
    public function testPutDiscussionTypeWithTypeAndPostType()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Only one of type, postTypeID are allowed");
        $discussion = $this->createDiscussion();
        $this->api()
            ->put("/discussions/{$discussion["discussionID"]}/type", [
                "postTypeID" => "question",
                "type" => "discussion",
            ])
            ->getBody();
    }

    /**
     * Test converting a post that was created before custom post types.
     *
     * @return mixed|string
     */
    public function testPutDiscussionTypeFromLegacy()
    {
        $discussion = $this->runWithConfig(
            [FeatureFlagHelper::featureConfigKey(PostTypeModel::FEATURE_POST_TYPES) => false],
            fn() => $this->createDiscussion(["type" => "question"])
        );

        $postType = $this->createPostType();

        $convertedDiscussion = $this->api()
            ->put("/discussions/{$discussion["discussionID"]}/type", ["postTypeID" => $postType["postTypeID"]])
            ->getBody();
        $this->assertEquals($postType["postTypeID"], $convertedDiscussion["postTypeID"]);

        $this->assertEquals($postType["name"], $convertedDiscussion["type"]);

        $discussion = $this->runWithConfig(
            [FeatureFlagHelper::featureConfigKey(PostTypeModel::FEATURE_POST_TYPES) => false],
            fn() => $this->api()
                ->get("/discussions/{$convertedDiscussion["discussionID"]}")
                ->getBody()
        );
        // To retain backward compatibility, the type property is being set as the parent post type.
        $this->assertEquals("discussion", $discussion["type"]);
        return $convertedDiscussion;
    }

    /**
     * If something directly updates the type of the discussion, postTypeID is removed.
     *
     * @return void
     * @depends testPutDiscussionTypeFromLegacy
     */
    public function testLegacySetTypeNullsPostType(array $discussion)
    {
        $this->discussionModel->setField($discussion["discussionID"], "Type", "poll");
        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertEquals("poll", $discussion["type"]);

        // Post type ID was cleared out.
        $this->assertArrayNotHasKey("postTypeID", $discussion);
    }

    /**
     * Test successfully converting from one post type to another with the `PUT /api/v2/discussions/{id}/type` endpoint.
     *
     * @return void
     */
    public function testConvertPostType()
    {
        $commonPostField = $this->createPostField(["isRequired" => true]);
        $originOnlyPostField = $this->createPostField(["visibility" => "private"]);
        $fromPostType = $this->createPostType([
            "postFieldIDs" => [$commonPostField["postFieldID"], $originOnlyPostField["postFieldID"]],
        ]);

        $discussion = $this->createDiscussion([
            "postTypeID" => $fromPostType["postTypeID"],
            "postFields" => [
                $commonPostField["postFieldID"] => "my custom post field",
                $originOnlyPostField["postFieldID"] => "my private data",
            ],
        ]);

        $toPostType = $this->createPostType([
            "postFieldIDs" => [$commonPostField["postFieldID"]],
        ]);

        $this->api()->put("/discussions/{$discussion["discussionID"]}/type", [
            "postTypeID" => $toPostType["postTypeID"],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postMeta"])
            ->getBody();
        $this->assertEquals($toPostType["postTypeID"], $discussion["postTypeID"]);
        $this->assertEquals($toPostType["name"], $discussion["type"]);
        $this->assertArrayHasKey("postFields", $discussion);
        $this->assertArrayHasKey($commonPostField["postFieldID"], $discussion["postFields"]);
        $this->assertArrayHasKey(PostFieldModel::PRIVATE_DATA_FIELD_ID, $discussion["postFields"]);

        $this->assertEquals("my custom post field", $discussion["postFields"][$commonPostField["postFieldID"]]);
        $this->assertEquals(
            "{$originOnlyPostField["label"]}: my private data",
            $discussion["postFields"][PostFieldModel::PRIVATE_DATA_FIELD_ID]
        );
    }

    /**
     * Test that we cannot convert to a postTypeID that is not allowed in the category.
     *
     * @return void
     */
    public function testConvertPostTypeInRestrictedCategory()
    {
        // Make sure question post type is active.
        $this->api()->patch("/post-types/question", ["isActive" => true]);

        $category = $this->createCategory(["hasRestrictedPostTypes" => true, "allowedPostTypeIDs" => ["question"]]);
        $discussion = $this->createDiscussion(["postTypeID" => "question"]);

        $discussionPostType = $this->createPostType(["parentPostType" => "discussion"]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(
            "Category #{$category["categoryID"]} doesn't allow for {$discussionPostType["postTypeID"]} type records"
        );

        $this->api()->put("/discussions/{$discussion["discussionID"]}/type", [
            "postTypeID" => $discussionPostType["postTypeID"],
        ]);
    }

    /**
     * Similar test as `testConvertPostType()`, but using the `PATCH /api/v2/discussions/move` endpoint.
     *
     * @return void
     */
    public function testMoveWithPostType()
    {
        $commonPostField = $this->createPostField(["isRequired" => true]);
        $originOnlyPostField = $this->createPostField(["visibility" => "internal"]);
        $fromPostType = $this->createPostType([
            "postFieldIDs" => [$commonPostField["postFieldID"], $originOnlyPostField["postFieldID"]],
        ]);

        $category = $this->createCategory();

        $discussion = $this->createDiscussion([
            "postTypeID" => $fromPostType["postTypeID"],
            "postFields" => [
                $commonPostField["postFieldID"] => "my custom post field",
                $originOnlyPostField["postFieldID"] => "my internal data",
            ],
        ]);

        $toPostType = $this->createPostType([
            "postFieldIDs" => [$commonPostField["postFieldID"]],
        ]);

        $this->api()->patch("/discussions/move", [
            "discussionIDs" => [$discussion["discussionID"]],
            "categoryID" => $category["categoryID"],
            "postTypeID" => $toPostType["postTypeID"],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postMeta"])
            ->getBody();
        $this->assertEquals($category["categoryID"], $discussion["categoryID"]);
        $this->assertEquals($toPostType["postTypeID"], $discussion["postTypeID"]);
        $this->assertEquals($toPostType["name"], $discussion["type"]);
        $this->assertArrayHasKey("postFields", $discussion);
        $this->assertArrayHasKey($commonPostField["postFieldID"], $discussion["postFields"]);
        $this->assertArrayHasKey(PostFieldModel::INTERNAL_DATA_FIELD_ID, $discussion["postFields"]);

        $this->assertEquals("my custom post field", $discussion["postFields"][$commonPostField["postFieldID"]]);
        $this->assertEquals(
            "{$originOnlyPostField["label"]}: my internal data",
            $discussion["postFields"][PostFieldModel::INTERNAL_DATA_FIELD_ID]
        );
    }
}
