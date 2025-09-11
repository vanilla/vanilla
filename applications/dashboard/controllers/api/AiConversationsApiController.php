<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Controllers\Api;

use AbstractApiController;
use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Events\AiConversationEvent;
use Vanilla\Dashboard\Models\AiConversationMessageModel;
use Vanilla\Dashboard\Models\AiConversationModel;
use Vanilla\Dashboard\Models\NexusAiConversationClient;
use Vanilla\Exception\PermissionException;
use Vanilla\Models\Model;

/**
 * API controller used to for AI conversation. Currently, only the Nexus API is supported.
 */
class AiConversationsApiController extends AbstractApiController
{
    const AI_CONVERSATION_LIMIT = 30;

    /**
     * D.I.
     *
     * @param AiConversationModel $aiConversationModel
     * @param ConfigurationInterface $config
     * @throws Exception
     */
    public function __construct(
        private AiConversationModel $aiConversationModel,
        private AiConversationMessageModel $aiConversationMessageModel,
        ConfigurationInterface $config
    ) {
        if (!$config->get(AiConversationModel::AI_CONVERSATION_FEATURE_CONFIG)) {
            throw new Exception("AI conversation feature is not enabled.");
        }
    }

    /**
     * List Ai conversations.
     *
     * [GET] `/api/v2/ai-conversations`
     *
     * @param array $query
     * @return Data
     * @throws ValidationException
     * @throws HttpException
     * @throws PermissionException
     */
    public function index(array $query): Data
    {
        $where = [];
        $this->permission(AiConversationModel::PERMISSION_KEY);

        if (!Gdn::session()->isValid()) {
            // Guest can only access conversation they started this session.
            $where["sessionID"] = Gdn::session()->SessionID;
        } else {
            $this->permission("Garden.SignIn.Allow");
        }

        $in = Schema::parse([
            "limit:i?" => ["minimum" => 1, "maximum" => self::AI_CONVERSATION_LIMIT, "default" => 30],
            "offset:i?" => ["minimum" => 0, "default" => 0],
            "insertUserID:i?",
            "source:s?" => [
                "enum" => [NexusAiConversationClient::MODEL_NAME],
                "default" => NexusAiConversationClient::MODEL_NAME,
            ],
        ]);
        $query = $in->validate($query);
        $userID = $query["insertUserID"] ?? Gdn::session()->UserID;

        if ($userID !== Gdn::session()->UserID) {
            $this->permission("Garden.Moderation.Manage");
        }

        $where["c.insertUserID"] = $userID;
        $conversations = $this->aiConversationModel->getConversations(
            $where,
            $query["limit"],
            $query["offset"],
            "dateInserted",
            "desc"
        );
        return new Data($conversations);
    }

    /**
     * Get a conversation based on its ID.
     *
     * [GET] `/api/v2/ai-conversations/{conversationID}`
     *
     * @param int $conversationID
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get(int $conversationID): Data
    {
        $this->permission(AiConversationModel::PERMISSION_KEY);
        $conversation = $this->aiConversationModel->getRagConversation($conversationID);

        if ($conversation["insertUserID"] !== Gdn::session()->UserID) {
            $this->permission("Garden.Moderation.Manage");
        }

        if (!Gdn::session()->isValid() && $conversation["sessionID"] !== Gdn::session()->SessionID) {
            throw new ClientException("You are not allowed to interact with this conversation.");
        }

        $out = self::outSchema();
        $out->validate($conversation);
        return new Data($conversation);
    }

    /**
     * Start a new conversation.
     *
     * [POST] `/api/v2/ai-conversations`
     *
     * @param array $body
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function post(array $body): Data
    {
        $this->permission(AiConversationModel::PERMISSION_KEY);

        $in = Schema::parse(["body:s?"]);
        $body = $in->validate($body);

        if (isset($body["body"])) {
            $conversation = $this->aiConversationModel->search($body["body"]);
        } else {
            $conversation = $this->aiConversationModel->startConversation();
        }

        $out = self::outSchema();
        $out->validate($conversation);
        return new Data($conversation);
    }

    /**
     * Continue an ai conversation.
     *
     * [POST] `/api/v2/ai-conversations/{conversationID}/reply`
     *
     * @param int $conversationID
     * @param array $body
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function post_reply(int $conversationID, array $body): Data
    {
        $this->permission(AiConversationModel::PERMISSION_KEY);

        $in = Schema::parse(["body:s"]);
        $body = $in->validate($body);

        $conversation = $this->aiConversationModel->getConversationByID($conversationID);
        $this->aiConversationModel->validateConversation($conversation);

        $result = $this->aiConversationMessageModel->reply($conversation, $body["body"]);

        // Fetch the updated conversation.
        $conversation = $this->aiConversationModel->getConversationByID($conversationID);

        $data = array_merge($result, $conversation);

        $out = self::outSchema();
        $out->validate($data);
        return new Data($data);
    }

    /**
     * React to a message in a conversation.
     *
     * [PUT] `/api/v2/ai-conversations/{conversationID}react`
     *
     * @param int $conversationID
     * @param array $body
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function put_react(int $conversationID, array $body): Data
    {
        $this->permission(AiConversationModel::PERMISSION_KEY);

        $in = Schema::parse(["messageID:s", "reaction:s?" => ["enum" => ["like", "dislike", null]]]);
        $body = $in->validate($body);

        $conversation = $this->aiConversationModel->getConversationByID($conversationID);
        $this->aiConversationModel->validateConversation($conversation);

        $success = $this->aiConversationMessageModel->react(
            $conversation,
            $body["messageID"],
            $body["reaction"] ?? null
        );
        $result = ["success" => $success];
        return new Data($result);
    }

    /**
     * Send feedback about a message in a conversation.
     *
     * [PUT] `/api/v2/ai-conversations/{conversationID}/feedback`
     *
     * @param int $conversationID
     * @param array $body
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function put_feedback(int $conversationID, array $body): Data
    {
        $this->permission(AiConversationModel::PERMISSION_KEY);

        $in = Schema::parse(["messageID:s", "body:s"]);
        $body = $in->validate($body);

        $conversation = $this->aiConversationModel->getConversationByID($conversationID);
        $this->aiConversationModel->validateConversation($conversation);

        $success = $this->aiConversationMessageModel->addFeedback($conversation, $body["messageID"], $body["body"]);
        $result = ["success" => $success];
        return new Data($result);
    }

    /**
     * Notify that a message has been copied.
     *
     * [POST] `/api/v2/ai-conversations/{conversationID}/copied`
     *
     * @param int $conversationID
     * @param array $body
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function post_copied(int $conversationID, array $body): Data
    {
        $this->permission(AiConversationModel::PERMISSION_KEY);

        $in = Schema::parse(["messageID:s"]);
        $body = $in->validate($body);

        $conversation = $this->aiConversationModel->getConversationByID($conversationID);
        $this->aiConversationModel->validateConversation($conversation);

        $success = $this->aiConversationMessageModel->copied($conversation, $body["messageID"]);
        $result = ["success" => $success];
        return new Data($result);
    }

    /**
     * Send a request to Nexus to generate a community post requesting assistance based on the content of a conversation.
     *
     * [POST] `/api/v2/ai-conversations/{conversationID}/ask-community`
     *
     * @param int $conversationID
     * @param array $body
     * @return Data
     * @throws ClientException
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function post_askCommunity(int $conversationID, array $body): Data
    {
        $this->permission(AiConversationModel::PERMISSION_KEY);
        $this->permission("Garden.SignIn.Allow");

        $in = Schema::parse([
            "calledFrom:s?" => ["enum" => [AiConversationEvent::SOURCE_SEARCH, AiConversationEvent::SOURCE_CHAT]],
        ]);
        $body = $in->validate($body);

        $conversation = $this->aiConversationModel->getConversationByID($conversationID);
        $this->aiConversationModel->validateConversation($conversation);

        $result = $this->aiConversationModel->askCommunity($conversation, $body["calledFrom"] ?? null);
        $out = Schema::parse(["name:s", "body:s", "format:s", "summary:s", "categoryID:i?", "postType:s?", "tags:a"]);
        $out->validate($result);

        return new Data($result);
    }

    /**
     * The return schema for the API.
     *
     * @return Schema
     */
    public static function outSchema(): Schema
    {
        return Schema::parse([
            "conversationID:i",
            "foreignID:s",
            "source:s",
            "insertUserID:i",
            "dateInserted:dt",
            "lastMessageID:s?",
            "lastMessageBody:s?",
            "dateLastMessage:dt?",
            "messages:a?" => [
                "messageID:s",
                "dateInserted:dt",
                "body:s",
                "feedback:s?",
                "confidence:s?",
                "user:s",
                "reaction:s?" => ["like", "dislike"],
                "references:a?" => ["items" => ["recordID:i", "recordType:s", "name:s", "url:s"]],
            ],
        ]);
    }
}
