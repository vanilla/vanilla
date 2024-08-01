<?php
/**
 * @author Dani Stark <dani.stark@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use Vanilla\Utility\ArrayUtils;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the `MessagesController`
 */
class MessagesControllerTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /** @var object  ConversationModel */
    private $conversationModel;

    /** @var string */
    private static $testMember;

    /** @var array */
    private static $conversation;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->conversationModel = self::container()->get(\ConversationModel::class);
    }

    /**
     * Test MessagesController::Index() with invalid user.
     * @depends testProvideData
     */
    public function testPostConversationInvalidUser(): void
    {
        $countBefore = $this->conversationModel
            ->getRecipients(["ConversationID" => self::$conversation["ConversationID"]])
            ->numRows();
        // Add an invalid user to the conversation.
        try {
            $this->bessy()->post(
                "/messages/" . self::$conversation["ConversationID"],
                [
                    "AddPeople" => "invalid-user",
                ],
                ["DeliveryMethod" => DELIVERY_METHOD_JSON]
            );
        } catch (\Gdn_UserException $e) {
            $exception = $e->getMessage();
            $this->assertEquals("Invalid recipient.", $exception);
        }
        $countAfter = $this->conversationModel
            ->getRecipients(["ConversationID" => self::$conversation["ConversationID"]])
            ->numRows();
        $this->assertEquals($countBefore, $countAfter);
    }

    /**
     * Test MessagesController::Add() with a deleted user.
     * @depends testProvideData
     */
    public function testPostConversationDeletedUser(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        /** @var \UserModel $userModel */
        $userModel = self::container()->get(\UserModel::class);
        $userModel->deleteID($userB["userID"]);
        $to = "{$userA["userID"]},{$userB["userID"]}";
        // Add a deleted user to the conversation.
        $this->bessy()->post(
            "/messages/add",
            [
                "To" => $to,
                "Body" => "test-conversation",
                "Format" => "text",
            ],
            ["DeliveryMethod" => DELIVERY_METHOD_JSON]
        );
        $userBConversations = $this->conversationModel->get2($userB["userID"])->resultArray();
        $this->assertEquals(0, count($userBConversations));
    }

    /**
     * Provide a conversation object.
     */
    public function testProvideData(): void
    {
        self::$testMember = $this->createUserFixture(self::ROLE_MEMBER);
        $conversation = [
            "Format" => "Text",
            "Body" => "Test conversation",
            "InsertUserID" => 1,
            "RecipientUserID" => [2],
        ];
        $conversationID = $this->conversationModel->save($conversation);
        self::$conversation = $this->conversationModel->getID($conversationID, DATASET_TYPE_ARRAY);
        $this->assertTrue(true);
    }

    /**
     * Test MessagesController::Index() with valid user.
     * @depends testProvideData
     */
    public function testPostConversationValidUser(): void
    {
        $countBefore = $this->conversationModel
            ->getRecipients(["ConversationID" => self::$conversation["ConversationID"]])
            ->numRows();
        // Add an valid user to the conversation.
        $result = $this->bessy()->post(
            "/messages/" . self::$conversation["ConversationID"],
            [
                "AddPeople" => self::$testMember,
            ],
            ["DeliveryMethod" => DELIVERY_METHOD_JSON]
        );
        $actualMessage = array_column($result->getInformMessages(), "Message");
        $this->assertEquals("Your changes were saved.", $actualMessage[0]);
        $countAfter = $this->conversationModel
            ->getRecipients(["ConversationID" => self::$conversation["ConversationID"]])
            ->numRows();
        $this->assertEquals($countBefore + 1, $countAfter);
    }

    /**
     * Reaching /messages/add should redirect if there is a redirection config for it.
     */
    public function testMessageAddRedirect(): void
    {
        $redirectUrl = "https://example.com/{name}?id={userID}";

        $this->runWithConfig(["Garden.Messages.Add.RedirectUrl" => $redirectUrl], function () use ($redirectUrl) {
            $user = $this->createUser();
            $this->runWithUser(function () use ($user, $redirectUrl) {
                $user = ArrayUtils::camelCase($user);

                /** @var \ProfileController $r */
                $r = $this->bessy()->get("/messages/add");
                $actualRedirect = $r->addDefinition("RedirectTo");
                $this->assertNotEmpty($actualRedirect);
                $expected = formatString($redirectUrl, $user);
                $this->assertSame($expected, $actualRedirect);
            }, $user);
        });
    }

    /**
     * Reaching /messages/add does not redirect the user if the configured redirection URL uses tags that the user
     * has no value for it.
     */
    public function testMessageFailsRedirect(): void
    {
        $redirectUrl = "https://example.com/{name}?ssoid={ssoID}";

        $this->runWithConfig(["Garden.Messages.Add.RedirectUrl" => $redirectUrl], function () use ($redirectUrl) {
            $user = $this->createUser();
            $this->runWithUser(function () use ($user, $redirectUrl) {
                $user = ArrayUtils::camelCase($user);

                // We verify that the user does not have an SsoID
                $userSsoID = $this->userModel->getDefaultSSOIDs([$user["userID"]])[$user["userID"]] ?? false;
                $this->assertFalse($userSsoID);

                // We direct the user to /messages/add
                /** @var \ProfileController $r */
                $r = $this->bessy()->get("/messages/add");
                // We verify that no redirection happened
                $actualRedirect = $r->addDefinition("RedirectTo");
                $this->assertEmpty($actualRedirect);
                // We verify that the displayed page shows 'New Message" as the page's title.
                $this->bessy()
                    ->getLastHtml()
                    ->assertContainsString("New Message</h1>");
            }, $user);
        });
    }
}
