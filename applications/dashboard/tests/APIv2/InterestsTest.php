<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Dashboard\Models\InterestModel;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

class InterestsTest extends AbstractResourceTest
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    protected $baseUrl = "/interests";

    protected $pk = "interestID";

    private $category;

    private $tag;

    private $profileField;

    private static int $counter = 0;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature(InterestModel::SUGGESTED_CONTENT_FEATURE_FLAG);
        $this->api()->put("/interests/toggle-suggested-content", ["enabled" => true]);

        $this->category = $this->createCategory();
        $this->profileField = $this->createProfileField([
            "apiName" => "interestsTestProfileField" . self::$counter++,
            "formType" => ProfileFieldModel::FORM_TYPE_CHECKBOX,
            "dataType" => ProfileFieldModel::DATA_TYPE_BOOL,
        ]);
        $this->tag = $this->createTag();
        \Gdn::sql()->truncate("interest");
    }

    /**
     * @inheritDoc
     */
    public function record(): array
    {
        $salt = round(microtime(true) * 1000) . rand(1, 1000);
        return [
            "apiName" => "apiname-$salt",
            "name" => "name-$salt",
            "categoryIDs" => isset($this->category) ? [$this->category["categoryID"]] : [],
            "tagIDs" => isset($this->tag) ? [$this->tag["tagID"]] : [],
            "profileFieldMapping" => isset($this->profileField)
                ? [
                    $this->profileField["apiName"] => true,
                ]
                : [],
            "isDefault" => false,
        ];
    }

    /**
     * @inheritDoc
     */
    public function testGetEdit($record = null)
    {
        $this->markTestSkipped("This resource doesn't have a GET /interests/{id}/edit endpoint");
    }

    /**
     * Test creating an interest with a duplicate apiName results in an exception.
     *
     * @return void
     */
    public function testPostWithDuplicateApiName()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("This interest API name is already in use. Use a unique API name.");
        $this->testPost(["apiName" => "duplicateName"] + $this->record());
        $this->testPost(["apiName" => "duplicateName"] + $this->record());
    }

    /**
     * Test creating an interest with a duplicate name results in an exception.
     *
     * @return void
     */
    public function testPostWithDuplicateName()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("This interest name is already in use. Use a unique name.");
        $this->testPost(["name" => "duplicateName"] + $this->record());
        $this->testPost(["name" => "duplicateName"] + $this->record());
    }

    /**
     * Test creating an interest with non-existent categories results in an exception.
     *
     * @return void
     */
    public function testPostWithInvalidCategories()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("Categories not found: 999999");
        $this->testPost(["categoryIDs" => [999999]] + $this->record());
    }

    /**
     * Test creating an interest with non-existent tags results in an exception.
     *
     * @return void
     */
    public function testPostWithInvalidTags()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("Tags not found: 999999");
        $this->testPost(["tagIDs" => [999999]] + $this->record());
    }

    /**
     * Test creating an interest with non-existent profile fields results in an exception.
     *
     * @return void
     */
    public function testPostWithInvalidProfileFields()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("Profile fields not found: doesNotExist");
        $this->testPost(["profileFieldMapping" => ["doesNotExist" => true]] + $this->record());
    }

    /**
     * Test that we can an exception when trying to create an interest when the feature is disabled.
     *
     * @return void
     */
    public function testPostWithFeatureNotEnabled()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("Suggested Content is not enabled");
        $this->api()->put("/interests/toggle-suggested-content", ["enabled" => false]);
        $this->testPost();
    }

    /**
     * Tests the index endpoint with various filters applied.
     *
     * @return void
     */
    public function testIndexWithFilters()
    {
        $profileField = $this->createProfileField();
        $category = $this->createCategory();
        $tag = $this->createTag();
        $newInterest = $this->testPost(
            [
                "profileFieldMapping" => [$profileField["apiName"] => true],
                "categoryIDs" => [$category["categoryID"]],
                "tagIDs" => [$tag["tagID"]],
                "isDefault" => true,
            ] + $this->record()
        );

        $indexBody = $this->api()
            ->get($this->baseUrl, [
                "apiName" => $newInterest["apiName"],
                "name" => $newInterest["name"],
                "profileFields" => [$profileField["apiName"]],
                "categoryIDs" => [$category["categoryID"]],
                "tagIDs" => [$tag["tagID"]],
                "isDefault" => true,
            ])
            ->getBody();

        $this->assertCount(1, $indexBody);
    }

    /**
     * Basic patch test.
     *
     * @return void
     */
    public function testPatch()
    {
        $profileField = $this->createProfileField([
            "formType" => ProfileFieldModel::FORM_TYPE_CHECKBOX,
            "dataType" => ProfileFieldModel::DATA_TYPE_BOOL,
        ]);
        $category = $this->createCategory();
        $tag = $this->createTag();
        $interest = $this->testPost();

        $payload = [
            "name" => $interest["name"] . "updated",
            "profileFieldMapping" => [$profileField["apiName"] => true],
            "categoryIDs" => [$category["categoryID"]],
            "tagIDs" => [$tag["tagID"]],
            "isDefault" => true,
        ];
        $interestUpdated = $this->api()
            ->patch($this->baseUrl . "/" . $interest["interestID"], $payload)
            ->getBody();
        $this->assertDataLike($payload, $interestUpdated);
    }

    /**
     * Tests the `/api/v2/interests/suggested-content` endpoint
     *
     * @return void
     */
    public function testGetSuggestedContent()
    {
        $user = $this->createUser();
        $profileField = $this->createProfileField(["dataType" => "boolean", "formType" => "checkbox"]);
        $this->api()->patch("/users/{$user["userID"]}/profile-fields", [
            $profileField["apiName"] => true,
        ]);

        $category = $this->createCategory();
        $discussion = $this->createDiscussion();

        $tag = $this->createTag();
        $this->api()->post("/discussions/{$discussion["discussionID"]}/tags", [
            "tagIDs" => [$tag["tagID"]],
        ]);

        // Create interest associated with profile fields.
        $this->createInterest([
            "profileFieldMapping" => [
                $profileField["apiName"] => true,
            ],
            "tagIDs" => [$tag["tagID"]],
        ]);

        $this->runWithUser(function () use ($category, $discussion) {
            $suggested = $this->api()
                ->get($this->baseUrl . "/suggested-content", [
                    "suggestedFollowsLimit" => 3,
                    "suggestedContentLimit" => 3,
                ])
                ->getBody();

            // Should have 1 suggested category and 1 suggested discussion
            $this->assertArrayHasKey("categories", $suggested);
            $this->assertArrayHasKey("discussions", $suggested);
            $this->assertCount(1, $suggested["categories"]);
            $this->assertCount(1, $suggested["discussions"]);
            $this->assertEquals($category["categoryID"], $suggested["categories"][0]["categoryID"]);
            $this->assertEquals($discussion["discussionID"], $suggested["discussions"][0]["discussionID"]);
        }, $user);
    }
}
