<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

class DiscussionsPostFieldsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
        \Gdn::sql()->truncate("postField");
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

        $postField = $this->createPostField(["postTypeID" => "discussion", "isRequired" => true]);

        $discussion = $this->createDiscussion([
            "postTypeID" => "discussion",
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
        $postField = $this->createPostField(["postTypeID" => "discussion", "isRequired" => true]);

        $discussion = $this->createDiscussion([
            "postTypeID" => "discussion",
            "postFields" => [$postField["postFieldID"] => "abcdef"],
        ]);
        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postFields"])
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
        $postField = $this->createPostField([
            "postTypeID" => "discussion",
            "dataType" => $dataType,
            "formType" => $formType,
        ]);

        $discussion = $this->createDiscussion([
            "postTypeID" => "discussion",
            "postFields" => [$postField["postFieldID"] => $value],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "postFields"])
            ->getBody();
        $this->assertArrayHasKey("postFields", $discussion);
        $this->assertArrayHasKey($postField["postFieldID"], $discussion["postFields"]);
        $this->assertEquals($responseValue, $discussion["postFields"][$postField["postFieldID"]]);
    }
}
