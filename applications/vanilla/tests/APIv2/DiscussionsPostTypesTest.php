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
use Vanilla\Logging\AuditLogModel;
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
     * @inheritdoc
     */
    public static function getAddons(): array
    {
        $addons = parent::getAddons();
        $addons[] = "qna";
        return $addons;
    }

    /**
     * @inheritdoc
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

        $exclusivePostType = $this->createPostType();
        $this->createCategory([
            "hasRestrictedPostTypes" => true,
            "allowedPostTypeIDs" => [$exclusivePostType["postTypeID"]],
        ]);
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
    public function testPostWithPostMetaAndNoPostTypeSupplied()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessageMatches("/value requires postTypeID to be present/");

        $postField = $this->createPostField(["postTypeID" => "discussion", "isRequired" => false]);
        $this->createDiscussion(["postMeta" => [$postField["postFieldID"] => "abcd"]]);
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
        $this->expectExceptionMessage("postMeta.{$postField["postFieldID"]} is required");

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
            "postMeta" => [$postField["postFieldID"] => "abcd"],
        ]);

        return [$discussion, $postField];
    }

    /**
     * Test updating post fields with the patch discussion endpoint.
     *
     * @return void
     * @depends testPostWithPostTypeAndRequiredFieldsAreIncluded
     */
    public function testPatchWithPostMeta(array $dependencies)
    {
        $this->expectNotToPerformAssertions();
        [$discussion, $postField] = $dependencies;

        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "postMeta" => [$postField["postFieldID"] => "efghi"],
        ]);
    }

    /**
     * Test the post fields expander on the `GET /discussions/{discussionID}` and the get_edit endpoint.
     *
     * @return void
     */
    public function testGetDiscussionWithPostMeta()
    {
        $postType = $this->createPostType();
        $postField = $this->createPostField(["postTypeID" => $postType["postTypeID"], "isRequired" => true]);

        $discussion = $this->createDiscussion([
            "postTypeID" => $postType["postTypeID"],
            "postMeta" => [$postField["postFieldID"] => "abcdef"],
        ]);

        $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postMeta"])
            ->assertSuccess()
            ->assertJsonObjectLike([
                "postTypeID" => $postType["postTypeID"],
                "type" => "discussion",
                "postMeta.{$postField["postFieldID"]}" => "abcdef",
            ]);

        // Make sure it works on get_edit
        $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->assertSuccess()
            ->assertJsonObjectLike([
                "postTypeID" => $postType["postTypeID"],
                "postMeta.{$postField["postFieldID"]}" => "abcdef",
            ]);
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
    public function testValidationOfPostMeta($dataType, $formType, $value, $responseValue)
    {
        $postType = $this->createPostType();
        $postField = $this->createPostField([
            "postTypeID" => $postType["postTypeID"],
            "dataType" => $dataType,
            "formType" => $formType,
        ]);

        $discussion = $this->createDiscussion([
            "postTypeID" => $postType["postTypeID"],
            "postMeta" => [$postField["postFieldID"] => $value],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postMeta"])
            ->getBody();
        $this->assertArrayHasKey("postMeta", $discussion);
        $this->assertArrayHasKey($postField["postFieldID"], $discussion["postMeta"]);
        $this->assertEquals($responseValue, $discussion["postMeta"][$postField["postFieldID"]]);
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
            ->put("/discussions/{$discussion["discussionID"]}/type", [
                "postTypeID" => $postType["postTypeID"],
            ])
            ->assertJsonObjectLike([
                "type" => $postType["parentPostTypeID"],
                "postTypeID" => $postType["postTypeID"],
            ])
            ->getBody();

        $discussion = $this->runWithConfig(
            [FeatureFlagHelper::featureConfigKey(PostTypeModel::FEATURE_POST_TYPES) => false],
            fn() => $this->api()
                ->get("/discussions/{$convertedDiscussion["discussionID"]}")
                ->getBody()
        );
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
        $this->setConfig("auditLog.enabled", true);
        $commonPostField = $this->createPostField(["isRequired" => true]);
        $originOnlyPostField = $this->createPostField(["visibility" => "private"]);
        $fromPostType = $this->createPostType([
            "postFieldIDs" => [$commonPostField["postFieldID"], $originOnlyPostField["postFieldID"]],
        ]);

        $discussion = $this->createDiscussion([
            "postTypeID" => $fromPostType["postTypeID"],
            "postMeta" => [
                $commonPostField["postFieldID"] => "my custom post field",
                $originOnlyPostField["postFieldID"] => "my private data",
            ],
        ]);

        $toPostType = $this->createPostType([
            "postFieldIDs" => [$commonPostField["postFieldID"]],
        ]);

        $this->api()->put("/discussions/{$discussion["discussionID"]}/type", [
            "postTypeID" => $toPostType["postTypeID"],
            "postMeta" => [
                $commonPostField["postFieldID"] => "my custom post field",
            ],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postMeta"])
            ->assertJsonObjectLike([
                "postTypeID" => $toPostType["postTypeID"],
                "type" => $toPostType["parentPostTypeID"],
                "postMeta.{$commonPostField["postFieldID"]}" => "my custom post field",
            ])
            ->getBody();

        $this->assertLogMessage(
            "Discussion post type changed from `{$fromPostType["postTypeID"]}` to `{$toPostType["postTypeID"]}`"
        );

        $auditLogModel = $this->container()->get(AuditLogModel::class);
        $logs = $auditLogModel->select(["eventType" => "discussion_post_type_change"]);
        $this->assertCount(1, $logs);
        $context = $logs[0]["context"];
        $this->assertEquals($fromPostType["postTypeID"], $context["previousPostTypeID"]);
        $this->assertEquals($toPostType["postTypeID"], $context["postTypeID"]);
        $this->assertEquals($discussion["discussionID"], $context["discussionID"]);
        $this->setConfig("auditLog.enabled", false);
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

        $category = $this->createCategory([
            "name" => "Restricted Cat",
            "hasRestrictedPostTypes" => true,
            "allowedPostTypeIDs" => ["question"],
        ]);
        $discussion = $this->createDiscussion(["postTypeID" => "question"]);

        $discussionPostType = $this->createPostType(["parentPostType" => "discussion"]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(
            "Category 'Restricted Cat' does not allow for '{$discussionPostType["postTypeID"]}' type records."
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
        $this->setConfig("auditLog.enabled", true);
        $this->resetTable("auditLog");
        $commonPostField = $this->createPostField(["isRequired" => true]);
        $originOnlyPostField = $this->createPostField(["visibility" => "internal"]);
        $fromPostType = $this->createPostType([
            "postFieldIDs" => [$commonPostField["postFieldID"], $originOnlyPostField["postFieldID"]],
        ]);

        $originalCategory = $this->createCategory([
            "hasRestrictedPostTypes" => true,
            "allowedPostTypeIDs" => [$fromPostType["postTypeID"]],
        ]);

        $discussion = $this->createDiscussion([
            "postTypeID" => $fromPostType["postTypeID"],
            "postMeta" => [
                $commonPostField["postFieldID"] => "my custom post field",
                $originOnlyPostField["postFieldID"] => "my internal data",
            ],
        ]);

        $toPostType = $this->createPostType([
            "postFieldIDs" => [$commonPostField["postFieldID"]],
        ]);

        $targetCategory = $this->createCategory([
            "hasRestrictedPostTypes" => true,
            "allowedPostTypeIDs" => [$toPostType["postTypeID"]],
        ]);

        $this->api()->patch("/discussions/move", [
            "discussionIDs" => [$discussion["discussionID"]],
            "categoryID" => $targetCategory["categoryID"],
            "postTypeID" => $toPostType["postTypeID"],
            "postMeta" => [$commonPostField["postFieldID"] => "test"],
        ]);

        $commonExpected = [
            "categoryID" => $targetCategory["categoryID"],
            "postTypeID" => $toPostType["postTypeID"],
            "postMeta.{$commonPostField["postFieldID"]}" => "test",
        ];

        $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postMeta"])
            ->assertJsonObjectLike(
                [
                    "type" => $toPostType["parentPostTypeID"],
                ] + $commonExpected
            )
            ->getBody();

        $this->api()
            ->get("/discussions/{$discussion["discussionID"]}/edit")
            ->assertJsonObjectLike($commonExpected)
            ->getBody();

        $this->assertLogMessage(
            "Discussion post type changed from `{$fromPostType["postTypeID"]}` to `{$toPostType["postTypeID"]}`"
        );

        $auditLogModel = $this->container()->get(AuditLogModel::class);
        $logs = $auditLogModel->select(["eventType" => "discussion_post_type_change"]);
        $this->assertCount(1, $logs);
        $context = $logs[0]["context"];
        $this->assertEquals($fromPostType["postTypeID"], $context["previousPostTypeID"]);
        $this->assertEquals($toPostType["postTypeID"], $context["postTypeID"]);
        $this->assertEquals($discussion["discussionID"], $context["discussionID"]);
        $this->setConfig("auditLog.enabled", false);
    }

    /**
     * Test moving a legacy post to a category with type conversion.
     *
     * @return void
     */
    public function testMoveFromLegacyPost()
    {
        // Simulate a legacy discussion that only has `Type` column.
        $category = $this->createCategory();
        $discussionID = $this->discussionModel->save([
            "Name" => "Test Post",
            "CategoryID" => $category["categoryID"],
            "Type" => "Idea",
            "Body" => "Test post",
            "Format" => "Html",
        ]);

        $targetCategory = $this->createCategory();
        $this->api()
            ->patch("/discussions/move", [
                "discussionIDs" => [$discussionID],
                "categoryID" => $targetCategory["categoryID"],
                "postTypeID" => "question",
            ])
            ->assertSuccess()
            ->assertJsonObjectLike(["status" => "success"]);

        $this->api()
            ->get("/discussions/{$discussionID}")
            ->assertJsonObjectLike(["postTypeID" => "question", "categoryID" => $targetCategory["categoryID"]]);
    }
}
