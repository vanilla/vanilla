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
    use SiteTestTrait, \VanillaTests\SetupTraitsTrait;

    /**
     * @var ConversationModel
     */
    protected $conversationModel;

    /**
     * @var ConversationMessageModel
     */
    protected $conversationMessageModel;

    /**
     * @var array
     */
    private $conversation;

    /**
     * Instantiate conversationModel & ConversationMessageModel.
     */
    public function setUp(): void {
        parent::setUp();

        $this->container()->call(function (
            ConversationModel $conversationModel
        ) {
            $this->conversationModel = $conversationModel;
            $this->conversationMessageModel = new ConversationMessageModel($conversationModel);
        });
        $this->createUserFixtures();

        $id = $this->conversationModel->save([
            'RecipientUserID' => [$this->memberID, $this->moderatorID],
        ], [ConversationModel::OPT_CONVERSATION_ONLY => true]);
        $this->conversation = $this->conversationModel->getID($id, DATASET_TYPE_ARRAY);
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

    /**
     * Test the basic saving of a message.
     */
    public function testSaveMessage(): void {
        $row = [
            'ConversationID' => $this->conversation['ConversationID'],
            'Body' => __FUNCTION__,
            'Format' => 'Text',
        ];
        $id = $this->conversationMessageModel->save($row);
        $message = $this->conversationMessageModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertArraySubsetRecursive($row, $message);
    }

    /**
     * Test adding a method with the deprecated signature.
     */
    public function testSaveMessageDeprecated(): void {
        $row = [
            'ConversationID' => $this->conversation['ConversationID'],
            'Body' => __FUNCTION__,
            'Format' => 'Text',
        ];

        $id = @$this->conversationMessageModel->save($row, $this->conversation);
        $message = $this->conversationMessageModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertArraySubsetRecursive($row, $message);
    }

    /**
     * Test adding a method with the deprecated signature.
     */
    public function testSaveMessageDeprecatedNullConversation(): void {
        $row = [
            'ConversationID' => $this->conversation['ConversationID'],
            'Body' => __FUNCTION__,
            'Format' => 'Text',
        ];

        $id = @$this->conversationMessageModel->save($row, null, ['NewConversation' => true]);
        $message = $this->conversationMessageModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertArraySubsetRecursive($row, $message);
    }
}
