<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Gdn;
use VanillaTests\SiteTestCase;
use ConversationModel;

/**
 * Test {@link ConversationModel}.
 */
class ConversationModelTest extends SiteTestCase
{
    /** @var ConversationModel */
    protected $conversationModel;

    /**
     * Instantiate conversationModel & ConversationMessageModel.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->conversationModel = Gdn::getContainer()->get(ConversationModel::class);
    }

    /**
     * Test countParticipants for /dba/counts
     */
    public function testParticipantCount(): void
    {
        $conversationID = $this->createConversation(1, 2);
        $this->conversationModel->setField($conversationID, "CountParticipants", 0);
        $conversation = $this->conversationModel->getID($conversationID, DATASET_TYPE_ARRAY);

        // Make sure CountParticipants was reset properly.
        $this->assertEquals(0, $conversation["CountParticipants"]);

        $this->conversationModel->counts("CountParticipants");
        $conversation = $this->conversationModel->getID($conversationID, DATASET_TYPE_ARRAY);
        $this->assertEquals(2, $conversation["CountParticipants"]);
    }

    /**
     * Create a conversation and return it's ID.
     *
     * @param int $insertUserID
     * @param int $recipientUserID
     * @return int
     */
    public function createConversation(int $insertUserID, int $recipientUserID): int
    {
        $conversation = [
            "Format" => "Text",
            "Body" => "Creating conversation",
            "InsertUserID" => $insertUserID,
            "RecipientUserID" => [$recipientUserID],
        ];
        $conversationID = $this->conversationModel->save($conversation);
        return $conversationID;
    }
}
