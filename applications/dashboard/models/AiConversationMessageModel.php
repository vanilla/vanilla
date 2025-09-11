<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Exception;
use Garden\EventManager;
use Garden\Schema\ValidationException;
use Gdn;
use Gdn_DatabaseStructure;
use Gdn_Format;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Events\AiConversationEvent;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Formatting\Html\HtmlSanitizer;
use Vanilla\Models\PipelineModel;

/**
 * Manages AI conversation messages.
 */
class AiConversationMessageModel extends PipelineModel
{
    const AI_USER_ID = -1;

    /**
     * D.I.
     *
     * @param ConfigurationInterface $config
     * @param NexusAiConversationClient $client
     * @param EventManager $eventManager
     */
    public function __construct(
        ConfigurationInterface $config,
        private NexusAiConversationClient $client,
        private EventManager $eventManager,
        private HtmlSanitizer $htmlSanitizer
    ) {
        parent::__construct("aiConversationMessage");
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);
        $this->addPipelineProcessor(
            new PruneProcessor("dateInserted", $config->get(AiConversationModel::AI_CONVERSATION_RETENTION, "60 days"))
        );
    }

    /**
     * Reply to a conversation with a message and return the full conversation.
     *
     * @param array $conversation
     * @param string $message
     * @return array
     * @throws ValidationException
     */
    public function reply(array $conversation, string $message): array
    {
        $response = $this->client->continueConversation($conversation["foreignID"], $message);
        $result = $response->getBody();
        $result = $this->client->normalizeOutput($result, AiConversationModel::AI_CONVERSATION_MEDIUM);
        $this->saveInBulk($conversation, $result["messages"]);
        return $result;
    }

    /**
     * React to a message in a conversation. If the reaction is null, it will remove the reaction.
     *
     * @param array $conversation
     * @param string|int $messageID
     * @param string|null $reaction
     * @return bool
     * @throws Exception
     */
    public function react(array $conversation, string|int $messageID, ?string $reaction = null): bool
    {
        if (is_numeric($messageID)) {
            $messageID = $this->select(["messageID" => $messageID])["foreignID"];
        }

        if ($reaction === null) {
            $response = $this->client->removeMessageReaction($conversation["foreignID"], $messageID);
        } else {
            $response = $this->client->reactToMessage($conversation["foreignID"], $messageID, $reaction);
        }

        $event = new AiConversationEvent(AiConversationEvent::ACTION_AI_RESPONSE_RATED, [
            "aiConversation" => $conversation,
            "messageID" => $messageID,
            "reaction" => $reaction,
            "source" => AiConversationEvent::SOURCE_CHAT,
        ]);
        $this->eventManager->dispatch($event);

        return $response->isSuccessful();
    }

    /**
     * Provide feedback on a message in a conversation.
     *
     * @param array $conversation
     * @param string|int $messageID
     * @param string $feedback
     * @return bool
     * @throws ValidationException
     */
    public function addFeedback(array $conversation, string|int $messageID, string $feedback): bool
    {
        if (is_numeric($messageID)) {
            $messageID = $this->select(["messageID" => $messageID])["foreignID"];
        }

        $response = $this->client->feedback($conversation["foreignID"], $messageID, $feedback);

        $event = new AiConversationEvent(AiConversationEvent::ACTION_USER_FEEDBACK, [
            "aiConversation" => $conversation,
            "messageID" => $messageID,
            "source" => AiConversationEvent::SOURCE_CHAT,
        ]);
        $this->eventManager->dispatch($event);

        return $response->isSuccessful();
    }

    /**
     * Notify the AI conversation service that a message has been copied to the clipboard.
     *
     * @param array $conversation
     * @param string|int $messageID
     * @return bool
     * @throws Exception
     */
    public function copied(array $conversation, string|int $messageID): bool
    {
        if (is_numeric($messageID)) {
            $messageID = $this->select(["messageID" => $messageID])["foreignID"];
        }

        $response = $this->client->recordMessageCopied($conversation["foreignID"], $messageID);

        $event = new AiConversationEvent(AiConversationEvent::ACTION_USER_COPIED, [
            "aiConversation" => $conversation,
            "messageID" => $messageID,
            "source" => AiConversationEvent::SOURCE_CHAT,
        ]);
        $this->eventManager->dispatch($event);

        return $response->isSuccessful();
    }

    /**
     * Save multiple messages in bulk for a conversation.
     *
     * @param array $conversation
     * @param array $messages
     * @return array
     * @throws Exception
     */
    public function saveInBulk(array $conversation, array $messages): array
    {
        if (empty($messages)) {
            return [];
        }

        $conversationID = $conversation["conversationID"];
        $toInsert = [];
        $foreignIDs = array_column($messages, "messageID");
        $currentMessages = $this->select(["foreignID" => $foreignIDs, "conversationID" => $conversationID]);
        $currentIDs = array_column($currentMessages, "foreignID");
        $events = [];

        foreach ($messages as $key => $message) {
            if (!in_array($message["messageID"], $currentIDs)) {
                $userID = !$message["aiResponse"] ? Gdn::session()->UserID : self::AI_USER_ID;
                $references = $message["references"] ?? [];
                $this->normalizeReferences($references);
                $message = [
                    "foreignID" => $message["messageID"],
                    "conversationID" => $conversationID,
                    "insertUserID" => $userID,
                    "body" => $this->htmlSanitizer->filter($message["body"]),
                    "feedback" => $message["feedback"] ?? null,
                    "references" => json_encode($references),
                    "referenceCount" => count($references),
                    "rating" => $message["rating"] ?? null,
                    "aiResponse" => $message["aiResponse"] ? 1 : 0,
                ];
                $this->insert($message);
                $action = $message["aiResponse"]
                    ? AiConversationEvent::ACTION_AI_RESPONSE_GENERATED
                    : AiConversationEvent::ACTION_USER_MESSAGE_SENT;
                $events[] = new AiConversationEvent($action, [
                    "aiConversation" => $conversation,
                    "message" => $toInsert,
                    "source" => AiConversationEvent::SOURCE_CHAT,
                    "aiResponse" => $message["aiResponse"],
                ]);
            } else {
                // We already inserted this message.
                unset($messages[$key]);
            }
        }

        // We dispatch the events after the insert has been completed just in case something went wrong.
        foreach ($events as $event) {
            $this->eventManager->dispatch($event);
        }

        $this->updateLastConversationMessage($conversationID);

        return $messages;
    }

    /**
     * Normalize the references.
     *
     * @param array $references
     * @return void
     */
    private function normalizeReferences(array &$references): void
    {
        foreach ($references as &$reference) {
            $reference["name"] = $this->htmlSanitizer->filter($reference["name"]);
        }
    }

    /**
     * Update the last message in a conversation.
     *
     * @param int $conversationID
     * @return void
     * @throws Exception
     */
    private function updateLastConversationMessage(int $conversationID): void
    {
        $lastMessage = $this->createSql()
            ->select(["messageID", "dateInserted"])
            ->from("aiConversationMessage")
            ->where(["conversationID" => $conversationID, "aiResponse" => true])
            ->orderBy("dateInserted,messageID", "desc")
            ->limit(1)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        $this->createSql()
            ->update("aiConversation c")
            ->set(["c.lastMessageID" => $lastMessage["messageID"], "c.dateLastMessage" => $lastMessage["dateInserted"]])
            ->where("conversationID", $conversationID)
            ->put();
    }

    /**
     * Create the `GDN_aiConversation` table used to keep track of the chat sessions.
     *
     * @param Gdn_DatabaseStructure $structure
     * @return void
     * @throws Exception
     */
    public static function structure(Gdn_DatabaseStructure $structure): void
    {
        $structure
            ->table("aiConversationMessage")
            ->primaryKey("messageID")
            ->column("foreignID", "varchar(255)", keyType: "key")
            ->column("conversationID", "int", keyType: "key")
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("body", "text")
            ->column("feedback", "text", true)
            ->column("references", "text", true)
            ->column("referenceCount", "int", true)
            ->column("rating", "varchar(32)", true)
            ->column("aiResponse", "tinyint", true)
            ->set();
    }
}
