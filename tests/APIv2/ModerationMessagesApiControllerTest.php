<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the moderation-messages endpoints.
 */
class ModerationMessagesApiControllerTest extends AbstractResourceTest
{
    use NoGetEditTestTrait, CommunityApiTestTrait, UsersAndRolesApiTestTrait;

    protected $baseUrl = "/moderation-messages";

    protected $pk = "moderationMessageID";

    protected $patchFields = [
        "body",
        "layoutViewType",
        "format",
        "isEnabled",
        "isDismissible",
        "type",
        "viewLocation",
        "includeDescendants",
    ];

    protected $editFields = [];

    protected $testPagingOnIndex = false;

    protected $record = [
        "body" => "testModPost",
        "format" => "text",
        "isDismissible" => false,
        "isEnabled" => true,
        "type" => "alert",
        "viewLocation" => "content",
        "layoutViewType" => "discussionList",
    ];

    /**
     * Test assigning a message to a specific category.
     */
    public function testPostingToCategory()
    {
        $category = $this->createCategory();
        $categorySpecificMessage = $this->testPost($this->record(), [
            "recordType" => "category",
            "recordID" => $category["categoryID"],
        ]);

        // Verify that the categoryID is saved with the message.
        $this->assertSame($category["categoryID"], $categorySpecificMessage["recordID"]);

        // Verify that the message actually shows up on the category page.
        $member = $this->createUser();
        $this->getSession()->start($member["userID"]);
        $html = $this->bessy()
            ->getHtml("categories/" . $category["urlcode"], [], ["deliveryType" => DELIVERY_TYPE_ALL])
            ->getInnerHtml();
        $this->assertStringContainsString($categorySpecificMessage["body"], $html);
    }

    /**
     * Test posting to a category that doesn't exist.
     */
    public function testPostingToNonExistentCategory()
    {
        $this->expectException(NotFoundException::class);
        $this->testPost($this->record(), [
            "recordType" => "category",
            "recordID" => 9999,
        ]);
    }

    /**
     * Test posting a recordID without posting a recordType.
     */
    public function testRecordIDWithoutType()
    {
        $this->expectExceptionMessage("recordType is required when saving a recordID.");
        $this->testPost($this->record(), [
            "recordID" => 2,
        ]);
    }

    /**
     * Test posting a recordType without posting a recordID.
     */
    public function testRecordTypeWithoutID()
    {
        $this->expectExceptionMessage("recordID is required when saving a recordType.");
        $this->testPost($this->record(), [
            "recordType" => "category",
        ]);
    }

    /**
     * Test that a message appears in the child of the category it's associated with when choosing to include Subcategories.
     */
    public function testMessageAppearsInSubcategory()
    {
        $parentCategory = $this->createCategory();
        $childCategory = $this->createCategory();
        $record = $this->testPost($this->record(), [
            "recordType" => "category",
            "recordID" => $parentCategory["categoryID"],
            "includeDescendants" => true,
        ]);

        $member = $this->createUser();
        $this->getSession()->start($member["userID"]);
        $html = $this->bessy()
            ->getHtml("categories/" . $childCategory["urlcode"], [], ["deliveryType" => DELIVERY_TYPE_ALL])
            ->getInnerHtml();
        $this->assertStringContainsString($record["body"], $html);
    }

    /**
     * Test dismissing a message through the "/dismiss" endpoint.
     */
    public function testMessageDismiss()
    {
        // Post a dismissible message.
        $record = $this->testPost([
            "body" => "testDisabled",
            "format" => "text",
            "isDismissible" => true,
            "isEnabled" => true,
            "type" => "alert",
            "viewLocation" => "content",
            "layoutViewType" => "all",
        ]);

        // Verify that it appears for a user.
        $member = $this->createUser();
        $this->getSession()->start($member["userID"]);
        $html = $this->bessy()
            ->getHtml("discussions", [], ["deliveryType" => DELIVERY_TYPE_ALL])
            ->getInnerHtml();
        $this->assertStringContainsString($record["body"], $html);

        // Have the user dismiss it.
        $this->api()->put($this->baseUrl . "/{$record["moderationMessageID"]}/dismiss");

        // Verify that it's gone.
        $html = $this->bessy()
            ->getHtml("discussions", [], ["deliveryType" => DELIVERY_TYPE_ALL])
            ->getInnerHtml();
        $this->assertStringNotContainsString($record["body"], $html);
    }

    /**
     * Test that disabled messages are sent from the index endpoint for mods only.
     */
    public function testViewingDisabledMessagesPermissionCheck()
    {
        $this->testPost();
        $this->testPost([
            "body" => "testDisabled",
            "format" => "text",
            "isDismissible" => false,
            "isEnabled" => false,
            "type" => "alert",
            "viewLocation" => "content",
            "layoutViewType" => "newDiscussion",
        ]);

        $enabledMessagesCount = count(
            $this->api()
                ->get($this->baseUrl, ["isEnabled" => true])
                ->getBody()
        );
        $disabledMessagesCount = count(
            $this->api()
                ->get($this->baseUrl, ["isEnabled" => false])
                ->getBody()
        );

        // Admin should see all messages.
        $allMessagesForAdmin = $this->api()
            ->get($this->baseUrl)
            ->getBody();
        $this->assertSame(count($allMessagesForAdmin), $enabledMessagesCount + $disabledMessagesCount);

        // User should see only enabled messages.
        $member = $this->createUser();
        $this->getSession()->start($member["userID"]);
        $messagesForMember = $this->api()
            ->get($this->baseUrl)
            ->getBody();
        $this->assertCount($enabledMessagesCount, $messagesForMember);
    }

    /**
     * Test that an exception is thrown when a user tries to get a message in a category they don't have view
     * permission for.
     */
    public function testMessageCategoryPermissionsRespected(): void
    {
        $category = $this->createPermissionedCategory();
        $message = $this->testPost($this->record(), [
            "recordType" => "category",
            "recordID" => $category["categoryID"],
        ]);

        $user = $this->createUser();
        $this->api()->setUserID($user["userID"]);
        $this->expectException(ForbiddenException::class);
        $this->api()->get($this->baseUrl . "/" . $message["moderationMessageID"]);
    }

    /**
     * Skip test. There's no "/:id/edit" endpoint.
     *
     * @param string $editSuffix
     */
    public function testEditFormatCompat(string $editSuffix = "/edit")
    {
        TestCase::markTestSkipped("This resource doesn't have GET /:id/edit.");
    }

    /**
     * We don't need the image.
     */
    public function testMainImageField()
    {
        $this->markTestSkipped();
    }
}
