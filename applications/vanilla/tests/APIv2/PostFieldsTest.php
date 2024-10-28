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

class PostFieldsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;

    protected $baseUrl = "/post-fields";

    protected $postTypeOne;

    protected $postTypeTwo;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
        \Gdn::sql()->truncate("postField");

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
     * Test creating a post field with a duplicate apiName for a different post type. No exception thrown.
     *
     * @return void
     */
    public function testPostWithDuplicatePostFieldDifferentPostType()
    {
        $this->testPost(["postFieldID" => "duplicate-name"]);
        $this->testPost(["postFieldID" => "duplicate-name", "postTypeID" => $this->postTypeTwo]);
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
            ->get($this->baseUrl . "/" . $postField["postTypeID"] . "/" . $postField["postFieldID"])
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
            ->patch($this->baseUrl . "/" . $postField["postTypeID"] . "/" . $postField["postFieldID"], $payload)
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

        $response = $this->api()->delete($this->baseUrl . "/" . $row["postTypeID"] . "/" . $row["postFieldID"]);
        $this->assertSame(204, $response->getStatusCode());

        $this->runWithExpectedException(NotFoundException::class, function () use ($row) {
            $this->api()->get($this->baseUrl . "/" . $row["postTypeID"] . "/" . $row["postFieldID"]);
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

        $this->assertApiResults($this->baseUrl, [], ["postFieldID" => ["one", "two", "three"]]);

        $this->api()->put("$this->baseUrl/sorts/{$this->postTypeOne}", [
            $one["postFieldID"] => 3,
            $two["postFieldID"] => 2,
            $three["postFieldID"] => 1,
        ]);

        $this->assertApiResults($this->baseUrl, [], ["postFieldID" => ["three", "two", "one"]]);
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
        $this->expectExceptionMessageMatches("/^The following values are not allowed:/");
        $this->testPost(["postFieldID" => "password"]);
    }
}
