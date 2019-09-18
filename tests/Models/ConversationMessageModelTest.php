<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Test {@link ConversationMessageModel}.
 */
class ConversationMessageModelTest extends SharedBootstrapTestCase {
    use SiteTestTrait {
        setupBeforeClass as baseSetupBeforeClass;
    }
    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        self::baseSetupBeforeClass();
    }

    /**
     *  Test ConversationMessageModel validate function.
     */
    public function testInvalidConversationMessageModelValidate() {
        $conversation = $this->provideConversation();
        $conversationMessagesModel = new ConversationMessageModel();
        $conversationMessagesModel->validate($conversation);
        $results = $conversationMessagesModel->Validation->resultsText();
        $this->assertEquals('Invalid conversation.', $results);
    }

    /**
     *  Test ConversationMessageModel validate function.
     */
    public function testValidConversationMessageModelValidate() {
        $conversation = $this->provideConversation();
        $conversationModel = new ConversationModel();
        $conversationModel->save($conversation);
        $conversationMessagesModel = new ConversationMessageModel();
        $conversationMessagesModel->validate($conversation);
        $results = $conversationMessagesModel->Validation->resultsText();
        $this->assertEquals('', $results);
    }

    /**
     *  Provide a conversation array.
     * @return array
     */
    public function provideConversation() {
        return $conversation = [
            'ConversationID' => '1',
            'Format' => 'Text',
            'Body' => 'Creating conversation',
            'InsertUserID' => 1,
            'RecipientUserID' => [2]
        ];
    }
}
