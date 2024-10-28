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

class PostFieldsTest extends AbstractResourceTest
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    protected $baseUrl = "/post-fields";

    protected $pk = "postFieldID";

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
     * @inheritDoc
     */
    public function record(): array
    {
        $salt = round(microtime(true) * 1000) . rand(1, 1000);
        return [
            "apiName" => "apiName-$salt",
            "postTypeID" => 1,
            "dataType" => "text",
            "label" => "field label",
            "description" => "field description",
            "formType" => "text",
            "visibility" => "public",
            "dropdownOptions" => ["option1"],
            "isRequired" => false,
            "isActive" => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function testGetEdit($record = null)
    {
        $this->markTestSkipped("This resource doesn't have a GET /post-fields/{id}/edit endpoint");
    }

    /**
     * Test creating a post field with a duplicate apiName results in an exception.
     *
     * @return void
     */
    public function testPostWithDuplicateApiName()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("This post field API name is already in use. Use a unique API name.");
        $this->testPost(["apiName" => "duplicateName"] + $this->record());
        $this->testPost(["apiName" => "duplicateName"] + $this->record());
    }

    /**
     * Test creating a post field with a duplicate apiName for a different post type. No exception thrown.
     * @return void
     */
    public function testPostWithDuplicateApiNameDifferentPostType()
    {
        $this->testPost(["apiName" => "duplicateName"] + $this->record());
        $this->testPost(["apiName" => "duplicateName", "postTypeID" => 2] + $this->record());
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
                "apiName" => $newPostField["apiName"],
                "label" => $newPostField["label"],
                "dataType" => $newPostField["dataType"],
                "formType" => $newPostField["formType"],
                "visibility" => $newPostField["visibility"],
                "isRequired" => $newPostField["isRequired"],
                "isActive" => $newPostField["isActive"],
            ],
            [
                "apiName" => [$newPostField["apiName"]],
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
        $postField = $this->testPost();

        $payload = [
            "label" => $postField["label"] . "updated",
            "description" => $postField["description"] . "updated",
            "formType" => "dropdown",
            "visibility" => "private",
            "dropdownOptions" => ["option2"],
            "isRequired" => false,
            "isActive" => true,
        ];
        $postTypeUpdated = $this->api()
            ->patch($this->baseUrl . "/" . $postField["postFieldID"], $payload)
            ->getBody();
        $this->assertDataLike($payload, $postTypeUpdated);
    }

    /**
     * Tests that the PUT /post-fields/sorts endpoint updates sort values and the order is reflected in the index endpoint.
     *
     * @return void
     */
    public function testSort()
    {
        $one = $this->testPost(["apiName" => "one"] + $this->record());
        $two = $this->testPost(["apiName" => "two"] + $this->record());
        $three = $this->testPost(["apiName" => "three"] + $this->record());

        $this->assertApiResults($this->baseUrl, [], ["apiName" => ["one", "two", "three"]]);

        $this->api()->put("$this->baseUrl/sorts", [
            $one["postFieldID"] => 3,
            $two["postFieldID"] => 2,
            $three["postFieldID"] => 1,
        ]);

        $this->assertApiResults($this->baseUrl, [], ["apiName" => ["three", "two", "one"]]);
    }
}
