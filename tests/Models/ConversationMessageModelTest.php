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
     * @var ConversationModel
     */
    protected $conversationModel;
    /**
     * @var ConversationMessageModel
     */
    protected $conversationMessageModel;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        self::baseSetupBeforeClass();
    }

    /**
     * Instantiate conversationModel & ConversationMessageModel.
     */
    protected function setup() {
        $this->conversationModel = new ConversationModel();
        $this->conversationMessageModel = new ConversationMessageModel();
    }

    /**
     * Test ConversationMessageModel validate an invalid conversation message.
     */
    public function testInvalidConversationMessageModelValidate() {
        $conversationMessage = [
            'ConversationID' => '9999',
            'Format' => 'Text',
            'Body' => 'This is a test message'
        ];

        $this->conversationMessageModel->validate($conversationMessage);
        $results = $this->conversationMessageModel->Validation->resultsText();
        $this->assertEquals('Invalid conversation.', $results);
    }

    /**
     * Test ConversationMessageModel validate a valid conversation message.
     */
    public function testValidConversationMessageModelValidate() {
        $conversation = $this->provideConversation();
        $conversationMessage = [
            'ConversationID' => $conversation['ConversationID'],
            'Format' => 'Text',
            'Body' => 'This is a test message'
        ];
        $this->conversationMessageModel->validate($conversationMessage);
        $results = $this->conversationMessageModel->Validation->resultsArray();
        $this->assertEmpty($results);
    }

    /**
     * Provide a conversation object.
     *
     * @return object
     */
    private function provideConversation(): array {
        $conversation = [
            'Format' => 'Text',
            'Body' => 'Creating conversation',
            'InsertUserID' => 1,
            'RecipientUserID' => [2]
        ];
        $conversationID = $this->conversationModel->save($conversation);
        $conversation = $this->conversationModel->getID($conversationID, DATASET_TYPE_ARRAY);
        return $conversation;
    }
}
