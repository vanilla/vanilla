<?php

use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\SiteTestTrait;


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
     *  Test ConversationMessageModel validate function
     */
    public function testConversationMessageModelValidate() {
        $conversation = [
            'ConversationID' => 1,
            'Format' => 'Text',
            'Body' => 'Creating conversation',
            'InsertUserID' => 1,
            'RecipientUserID' => [2]
        ];
        $conversationMessagesModel = new ConversationMessageModel();
        $result = $conversationMessagesModel->validate($conversation);
        $this->assertEquals(false, $result);
    }
}
