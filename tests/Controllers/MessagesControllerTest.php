<?php
/**
 * @author Dani Stark <dani.stark@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use VanillaTests\SiteTestCase;

/**
 * Tests for the `MessagesController`
 */
class MessagesControllerTest extends SiteTestCase {

    /** @var object  ConversationModel */
    private $conversationModel;

    /** @var string */
    private static $testMember;

    /** @var array */
    private static $conversation;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->conversationModel = self::container()->get(\ConversationModel::class);
    }

    /**
     * Test MessagesController::Index() with invalid user.
     * @depends testProvideData
     */
    public function testPostConversationInvalidUser(): void {
        $countBefore = $this->conversationModel->getRecipients(['ConversationID' => self::$conversation['ConversationID']])->numRows();
        // Add an invalid user to the conversation.
        try {
            $this->bessy()->post("/messages/".self::$conversation['ConversationID'], [
                'AddPeople' => 'invalid-user'
            ], ['DeliveryMethod' => DELIVERY_METHOD_JSON]);
        } catch (\Gdn_UserException $e) {
            $exception = $e->getMessage();
            $this->assertEquals('Invalid recipient.', $exception);
        }
        $countAfter = $this->conversationModel->getRecipients(['ConversationID' => self::$conversation['ConversationID']])->numRows();
        $this->assertEquals($countBefore, $countAfter);
    }

    /**
     * Provide a conversation object.
     */
    public function testProvideData(): void {
        self::$testMember = $this->createUserFixture(self::ROLE_MEMBER);
        $conversation = [
            'Format' => 'Text',
            'Body' => 'Test conversation',
            'InsertUserID' => 1,
            'RecipientUserID' => [2]
        ];
        $conversationID = $this->conversationModel->save($conversation);
        self::$conversation = $this->conversationModel->getID($conversationID, DATASET_TYPE_ARRAY);
        $this->assertTrue(true);
    }

    /**
     * Test MessagesController::Index() with valid user.
     * @depends testProvideData
     */
    public function testPostConversationValidUser(): void {
        $countBefore = $this->conversationModel->getRecipients(['ConversationID' => self::$conversation['ConversationID']])->numRows();
        // Add an valid user to the conversation.
        $result = $this->bessy()->post("/messages/".self::$conversation['ConversationID'], [
            'AddPeople' => self::$testMember
        ], ['DeliveryMethod' => DELIVERY_METHOD_JSON]);
        $actualMessage = array_column($result->getInformMessages(), 'Message');
        $this->assertEquals('Your changes were saved.', $actualMessage[0]);
        $countAfter = $this->conversationModel->getRecipients(['ConversationID' => self::$conversation['ConversationID']])->numRows();
        $this->assertEquals($countBefore + 1, $countAfter);
    }
}
