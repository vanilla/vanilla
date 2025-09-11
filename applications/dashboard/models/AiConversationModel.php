<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Exception;
use Garden\EventManager;
use Garden\Web\Exception\ClientException;
use Gdn;
use Gdn_DatabaseStructure;
use Garden\Schema\Schema;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Events\AiConversationEvent;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Model used to interact with the `GDN_AiConversation` table.
 */
class AiConversationModel extends PipelineModel
{
    const AI_CONVERSATION_RETENTION = "ai.conversation.retention";
    const AI_CONVERSATION_FEATURE_FLAG = "aiConversation";
    const AI_CONVERSATION_FEATURE_CONFIG = "Feature.aiConversation.Enabled";
    const PERMISSION_KEY = "Garden.aiAssistedSearch.View";
    const AI_CONVERSATION_MEDIUM = "ai-conversation";
    const AI_SEARCH_MEDIUM = "ai-search";

    /**
     * D.I.
     *
     * @param ConfigurationInterface $config
     * @param NexusAiConversationClient $client
     */
    public function __construct(
        ConfigurationInterface $config,
        private NexusAiConversationClient $client,
        private AiConversationMessageModel $aiConversationMessageModel,
        private EventManager $eventManager
    ) {
        parent::__construct("aiConversation");
        $this->addPipelineProcessor(
            new PruneProcessor("dateLastMessage", $config->get(self::AI_CONVERSATION_RETENTION, "60 days"))
        );
    }

    /**
     * Get a conversation by its ID. This will only fetch the DB record.
     *
     * @param int $conversationID
     * @return array|false
     * @throws Exception
     */
    public function getConversationByID(int $conversationID): array|false
    {
        $conversation = $this->getConversations(["c.conversationID" => $conversationID], 1)[0] ?? [];
        return $conversation;
    }

    /**
     * Get a conversation by its ID. This will only fetch the DB record.
     *
     * @param array $where
     * @return array|false
     * @throws Exception
     */
    public function getConversations(
        array $where,
        int $limit = 30,
        int $offset = 0,
        array|string $orderFields = "",
        string $orderDirection = "asc"
    ): array|false {
        $sql = $this->database->createSql();
        $conversations = $sql
            ->select("c.*")
            ->select("cm.body as lastMessageBody, cm.references")
            ->from("aiConversation c")
            ->leftJoin("aiConversationMessage cm", "cm.conversationID=c.conversationID && c.lastMessageID=cm.messageID")
            ->where($where)
            ->limit($limit)
            ->offset($offset)
            ->orderBy($orderFields, $orderDirection)
            ->get()
            ->resultArray();

        foreach ($conversations as &$conversation) {
            if (isset($conversation["references"])) {
                $conversation["references"] = json_decode($conversation["references"], true);
            }
        }

        return $conversations;
    }

    /**
     * Get a conversation by its ID. This function will make a call to the AI conversation API to fetch the full conversation
     *
     * @param int $conversationID
     * @return array|false
     * @throws Exception
     */
    public function getRagConversation(int $conversationID): array|false
    {
        $conversation = $this->getConversationByID($conversationID);

        $response = $this->client->getConversation($conversation["foreignID"]);
        $result = $response->getBody();
        $result = $this->client->normalizeOutput($result, self::AI_CONVERSATION_MEDIUM);

        $data = array_merge($result, $conversation);
        return $data;
    }

    /**
     * Call the AI conversation API to start a new conversation.
     *
     * @return array
     * @throws Exception
     */
    public function startConversation(): array
    {
        $response = $this->client->startConversation();
        $result = $response->getBody();
        $result = $this->client->normalizeOutput($result, self::AI_CONVERSATION_MEDIUM);

        // Insert the conversation into the database.
        $insert = [
            "foreignID" => $result["foreignID"],
            "source" => $result["source"],
            "insertUserID" => Gdn::session()->UserID,
            "sessionID" => Gdn::session()->SessionID,
            "dateInserted" => CurrentTimeStamp::getDateTime(),
            "dateLastMessage" => CurrentTimeStamp::getDateTime(),
            "lastMessageBody" => null,
        ];
        $conversationID = $this->insert($insert);
        $conversation = $this->getConversationByID($conversationID);

        $event = new AiConversationEvent(AiConversationEvent::ACTION_CONVERSATION_STARTED, [
            "aiConversation" => $conversation,
            "source" => AiConversationEvent::SOURCE_CHAT,
        ]);
        $this->eventManager->dispatch($event);

        $data = array_merge($result, $conversation);
        return $data;
    }

    /**
     * Call the AI conversation API to start a search.
     *
     * @param string $query
     * @return array|false
     * @throws Exception
     */
    public function search(string $query): array|false
    {
        $response = $this->client->search($query);
        $result = $response->getBody();
        $result = $this->client->normalizeOutput($result, self::AI_SEARCH_MEDIUM);

        // Insert the conversation into the database.
        $insert = [
            "foreignID" => $result["foreignID"],
            "source" => $result["source"],
            "insertUserID" => Gdn::session()->UserID,
            "sessionID" => Gdn::session()->SessionID,
            "dateInserted" => CurrentTimeStamp::getDateTime(),
            "dateLastMessage" => CurrentTimeStamp::getDateTime(),
        ];
        $conversationID = $this->insert($insert);
        $conversation = $this->getConversationByID($conversationID);

        $aiReferenceCount = 0;

        $newMessages = $this->aiConversationMessageModel->saveInBulk($conversation, $result["messages"]);
        foreach ($newMessages as $newMessage) {
            // Count the references from ALL AI messages.
            if ($newMessage["aiResponse"]) {
                $aiReferenceCount += $newMessage["referencesCount"] ?? 0;
            }
        }

        $conversation["lastMessageBody"] = $newMessage["body"] ?? "";
        $conversation["references"] = $newMessage["references"] ?? [];

        $event = new AiConversationEvent(AiConversationEvent::ACTION_SEARCH_SUMMARY_GENERATED, [
            "aiConversation" => $conversation,
            "source" => AiConversationEvent::SOURCE_SEARCH,
            "query" => $query,
            "referenceCount" => $aiReferenceCount,
        ]);
        $this->eventManager->dispatch($event);

        $data = array_merge($result, $conversation);
        return $data;
    }

    /**
     * Ask the AI conversation service to write a message for the community regarding the conversation.
     *
     * @param array $conversation
     * @param string|null $source
     * @return array
     * @throws Exception
     */
    public function askCommunity(array $conversation, ?string $source): array
    {
        $response = $this->client->askCommunity($conversation["foreignID"]);
        $body = $response->getBody();
        $result = $this->client->normalizeAskCommunity($body);

        $event = new AiConversationEvent(AiConversationEvent::ACTION_AI_ASK_COMMUNITY, [
            "aiConversation" => $conversation,
            "source" => $source,
        ]);
        $this->eventManager->dispatch($event);

        $data = array_merge($result, $conversation);
        return $data;
    }

    /**
     * @return Schema
     */
    public static function fragmentSchema(): Schema
    {
        return Schema::parse([
            "conversationID:s",
            "foreignID:s",
            "source:s",
            "insertUserID:i",
            "dateInserted:s",
            "lastMessageID:i",
            "dateLastMessage:s",
        ]);
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
            ->table("aiConversation")
            ->primaryKey("conversationID")
            ->column("foreignID", "varchar(255)", keyType: "key")
            ->column("source", "varchar(255)", keyType: "key")
            ->column("insertUserID", "int")
            ->column("sessionID", "varchar(32)")
            ->column("dateInserted", "datetime")
            ->column("lastMessageID", "int", true)
            ->column("dateLastMessage", "datetime", keyType: "index")
            ->set();

        Gdn::permissionModel()->define([AiConversationModel::PERMISSION_KEY => 0]);
    }

    /**
     * Validate the ownership of a conversation.
     *
     * @param array $conversation
     * @return void
     * @throws ClientException
     */
    public function validateConversation(array $conversation): void
    {
        // If the user is a guest, we will validate based on the sessionID.
        if (!Gdn::session()->isValid() && $conversation["sessionID"] !== Gdn::session()->SessionID) {
            throw new ClientException("You are not allowed to interact with this conversation.");
        }

        if ($conversation["insertUserID"] !== Gdn::session()->UserID) {
            throw new ClientException("You are not allowed to interact with this conversation.");
        }

        if ($conversation["source"] !== $this->client->getModel()) {
            throw new ClientException(
                "The model used to start this conversation is not the same as the one configured."
            );
        }
    }
}
