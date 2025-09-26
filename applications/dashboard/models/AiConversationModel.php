<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Exception;
use Gdn;
use Gdn_DatabaseStructure;
use Garden\Schema\Schema;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Model used to interact with the `GDN_AiConversation` table.
 */
class AiConversationModel extends PipelineModel
{
    const AI_CONVERSATION_RETENTION = "ai.conversation.retention";
    const RAG_SEARCH_FEATURE_FLAG = "ragSearch";
    const RAG_SEARCH_FEATURE_CONFIG = "Feature.ragSearch.Enabled";
    const PERMISSION_KEY = "Garden.RagSearch.View";

    /**
     * D.I.
     *
     * @param ConfigurationInterface $config
     * @throws Exception
     */
    public function __construct(ConfigurationInterface $config)
    {
        parent::__construct("aiConversation");
        $this->addPipelineProcessor(
            new PruneProcessor("dateLastMessage", $config->get(self::AI_CONVERSATION_RETENTION, "60 days"))
        );
    }

    /**
     * Get a conversation by it's ID.
     *
     * @param int $conversationID
     * @return array|false
     * @throws Exception
     */
    public function getConversationByID(int $conversationID): array|false
    {
        $sql = $this->database->createSql();
        $result = $sql
            ->select()
            ->from("aiConversation")
            ->where(["conversationID" => $conversationID])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        return $result;
    }

    /**
     * Get a conversation by its foreignID.
     *
     * @param string $foreignID, string $source
     * @throws Exception
     */
    public function getConversationByForeignID(string $foreignID, string $source): array|false
    {
        $sql = $this->database->createSql();
        $result = $sql
            ->select()
            ->from("aiConversation")
            ->where(["foreignID" => $foreignID, "source" => $source])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        return $result;
    }

    /**
     * Update an existing conversation or create one if none exists.
     *
     * @param string $foreignID
     * @param string $source
     * @param array $messages
     * @return array|false
     * @throws Exception
     */
    public function trackConversation(string $foreignID, string $source, array $messages = []): array|false
    {
        $currentConversation = $this->getConversationByForeignID($foreignID, $source);
        $lastMessage = end($messages);

        // Update the session if it exists.
        if ($currentConversation) {
            $set = ["dateLastMessage" => CurrentTimeStamp::getDateTime()];

            if (!empty($lastMessage)) {
                $set += [
                    "lastMessageID" => $lastMessage["messageId"],
                    "lastMessageBody" => $lastMessage["message"],
                ];
            }

            $this->createSql()
                ->update("aiConversation")
                ->set($set)
                ->where("foreignID", $foreignID)
                ->put();
        } else {
            $insert = [
                "foreignID" => $foreignID,
                "source" => $source,
                "insertUserID" => Gdn::session()->UserID,
                "sessionID" => Gdn::session()->SessionID,
                "dateInserted" => CurrentTimeStamp::getDateTime(),
                "lastMessageID" => $lastMessage["messageId"] ?? null,
                "lastMessageBody" => $lastMessage["message"] ?? null,
                "dateLastMessage" => CurrentTimeStamp::getDateTime(),
            ];
            $this->insert($insert);
        }

        return $this->getConversationByForeignID($foreignID, $source);
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
            "lastMessageID:s",
            "lastMessageBody:s",
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
            ->column("lastMessageID", "varchar(255)", true)
            ->column("lastMessageBody", "text", true)
            ->column("dateLastMessage", "datetime", keyType: "index")
            ->set();

        Gdn::permissionModel()->define([AiConversationModel::PERMISSION_KEY => 0]);
    }
}
