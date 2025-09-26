<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use DateTimeImmutable;
use Exception;
use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use Garden\Http\HttpResponseException;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Gdn;
use SpoofMiddleware;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Site\OwnSiteProvider;

/**
 * A client for the Nexus RAG search service.
 */
class NexusRagSearchClient
{
    // e.g.https://api.nexus-mc-dev-us-1.apps.hl-nexus.com/api/v0.1/AIAssistant/chat/
    const NEXUS_RAG_URL_CONFIG_KEY = "nexus.rag.url";

    // e.g. https://auth.nexus-shared-dev-us-1.nexus-shared.hl-nexus.com
    const NEXUS_AUTH_URL_CONFIG_KEY = "nexus.rag.authUrl";
    const NEXUS_RAG_CLIENT_ID_CONFIG_KEY = "nexus.rag.clientID";
    const NEXUS_RAG_CLIENT_SECRET_CONFIG_KEY = "nexus.rag.clientSecret";
    const NEXUS_RAG_MODEL_CONFIG_KEY = "nexus.rag.model";
    const NEXUS_RAG_TOKEN_CONFIG_KEY = "nexus.rag.token";
    const NEXUS_RAG_TOKEN_EXPIRATION_CONFIG_KEY = "nexus.rag.tokenExpiration";
    const AI_CONVERSATION_ASK_COMMUNITY_CATEGORY_CONFIG_KEY = "nexus.rag.ask.categoryID";
    const AI_CONVERSATION_ASK_COMMUNITY_POST_TYPE_CONFIG_KEY = "nexus.rag.ask.postType";
    const ASSISTANT_NAME_CONFIG_KEY = "nexus.rag.AssistantName";
    const MODEL_NAME = "nexus";
    private string $siteID;

    /**
     * D.I.
     */
    public function __construct(
        protected HttpClient $client,
        protected ConfigurationInterface $config,
        OwnSiteProvider $siteProvider,
        protected AiConversationModel $aiConversationModel
    ) {
        $this->siteID = $siteProvider->getOwnSite()->getSiteID();

        $baseUrl = $this->config->get(self::NEXUS_RAG_URL_CONFIG_KEY);
        $this->client->setBaseUrl(rtrim($baseUrl, "/"));

        $token = $this->getToken();
        $this->client->setThrowExceptions(true);
        $this->client->setDefaultHeaders([
            "Content-Type" => "application/json",
            "Authorization" => "Bearer $token",
        ]);
    }

    /**
     * Get a token from Nexus.
     *
     * @return string|null
     */
    private function getToken(): ?string
    {
        // Check if the current token is still valid.
        $expiration = $this->config->get(self::NEXUS_RAG_TOKEN_EXPIRATION_CONFIG_KEY);
        $currentToken = $this->config->get(self::NEXUS_RAG_TOKEN_CONFIG_KEY);

        if ($expiration && $expiration > time() && $currentToken) {
            return $currentToken;
        }

        return $this->createToken();
    }

    /**
     * Generate a new token.
     *
     * @return string|null
     */
    public function createToken(): ?string
    {
        $clientID = $this->config->get(self::NEXUS_RAG_CLIENT_ID_CONFIG_KEY);
        $clientSecret = $this->config->get(self::NEXUS_RAG_CLIENT_SECRET_CONFIG_KEY);
        $authUrl = $this->config->get(self::NEXUS_AUTH_URL_CONFIG_KEY);

        if (!$authUrl || !$clientID || !$clientSecret) {
            ErrorLogger::warning("Missing client ID or client secret for Nexus.", ["nexus"]);
            return null;
        }
        try {
            $response = $this->client->post(
                $authUrl . "/connect/token",
                http_build_query([
                    "grant_type" => "client_credentials",
                    "scope" => "api",
                    "client_id" => $clientID,
                    "client_secret" => $clientSecret,
                ]),
                headers: ["Content-Type" => "application/x-www-form-urlencoded"]
            );

            if ($response->isSuccessful()) {
                $body = $response->getBody();
                $expiration = time() + $body["expires_in"];
                $this->config->saveToConfig(self::NEXUS_RAG_TOKEN_CONFIG_KEY, $body["access_token"]);
                $this->config->saveToConfig(self::NEXUS_RAG_TOKEN_EXPIRATION_CONFIG_KEY, $expiration);
                return $body["access_token"];
            } else {
                ErrorLogger::warning("Failed to get a token from Nexus.", ["nexus"], ["response" => $response]);
                return null;
            }
        } catch (HttpResponseException $e) {
            // Log and move on.
            ErrorLogger::warning("Failed to get a token from Nexus.", ["nexus"], ["exception" => $e]);
            return null;
        }
    }

    /**
     * Make search request to the Nexus RAG service.
     *
     * @param string $query
     * @return HttpResponse
     * @throws ValidationException|Exception
     */
    public function search(string $query): HttpResponse
    {
        $body = [
            "message" => $query,
            "originSystemId" => "VN",
            "originTenantId" => $this->siteID,
            "originUserId" => strval(Gdn::session()->UserID),
            "searchHeaders" => [SpoofMiddleware::SPOOF_HEADER => Gdn::session()->UserID],
        ];

        $response = $this->client->post("/search/" . $this->getModel(), body: $body);
        if ($response->isSuccessful()) {
            $this->trackSession($response);
        } else {
            ErrorLogger::warning("Failed to start a search request with Nexus.", ["nexus"], ["response" => $response]);
        }
        return $response;
    }

    /**
     * Start a new conversation with the Nexus RAG service.
     *
     *
     * Note: A conversation will automatically be started when a search request is made.
     *
     * @return HttpResponse
     * @throws Exception
     */
    public function startConversation(): HttpResponse
    {
        $body = [
            "originSystemId" => "VN",
            "originTenantId" => $this->siteID,
            "originUserId" => strval(Gdn::session()->UserID),
        ];

        $response = $this->client->post("/session/" . $this->getModel(), $body);
        if ($response->isSuccessful()) {
            $this->trackSession($response);
        } else {
            ErrorLogger::warning("Failed to start conversation with Nexus.", ["nexus"], ["response" => $response]);
        }

        return $response;
    }

    /**
     * Get an existing session.
     *
     * @param string $foreignID (sessionId in Nexus)
     * @return HttpResponse
     * @throws Exception
     */
    public function getConversation(string $foreignID): HttpResponse
    {
        $response = $this->client->get("/session/" . $this->getModel() . "/$foreignID");

        if ($response->isSuccessful()) {
            $this->trackSession($response);
        } else {
            ErrorLogger::warning(
                "Failed to get a conversation from Nexus.",
                ["nexus"],
                ["response" => $response, "chatSessionID" => $foreignID]
            );
        }

        return $response;
    }

    /**
     * Continue an existing conversation.
     *
     * @param string $foreignID (sessionId in Nexus) (sessionId in Nexus)
     * @param string $query
     * @return HttpResponse
     * @throws ValidationException
     */
    public function continueConversation(string $foreignID, string $query): HttpResponse
    {
        $in = Schema::parse(["message:s"]);
        $body = $in->validate([
            "message" => $query,
            "searchHeaders" => [SpoofMiddleware::SPOOF_HEADER => Gdn::session()->UserID],
        ]);

        $response = $this->client->post("/session/" . $this->getModel() . "/$foreignID", $body);

        if ($response->isSuccessful()) {
            $this->trackSession($response);
        } else {
            ErrorLogger::warning(
                "Failed to continue a conversation.",
                ["nexus"],
                ["response" => $response, "chatSessionID" => $foreignID]
            );
        }

        return $response;
    }

    /**
     * Notify Nexus when a message has been copied.
     *
     * @param string $foreignID (sessionId in Nexus)
     * @param string $messageID
     * @return HttpResponse
     * @throws Exception
     */
    public function recordMessageCopied(string $foreignID, string $messageID): HttpResponse
    {
        $response = $this->client->post("/messageCopy/$foreignID/$messageID");

        if ($response->isSuccessful()) {
            $this->trackSession($response);
        } else {
            ErrorLogger::warning(
                "Failed to notify Nexus a that message has been copied.",
                ["nexus"],
                ["response" => $response, "chatSessionID" => $foreignID, "messageID" => $messageID]
            );
        }

        return $response;
    }

    /**
     * Send feedback to Nexus about a specific message.
     *
     * @param string $foreignID (sessionId in Nexus)
     * @param string $messageID
     * @param string $feedback
     * @return HttpResponse
     * @throws ValidationException
     */
    public function feedback(string $foreignID, string $messageID, string $feedback): HttpResponse
    {
        $in = Schema::parse(["feedback:s"]);

        $body = $in->validate([
            "feedback" => $feedback,
        ]);

        $response = $this->client->post("/feedback/$foreignID/$messageID", $body);

        if ($response->isSuccessful()) {
            $this->trackSession($response);
        } else {
            ErrorLogger::warning(
                "Failed to send feedback to Nexus.",
                ["nexus"],
                ["response" => $response, "chatSessionID" => $foreignID, "messageID" => $messageID]
            );
        }

        return $response;
    }

    /**
     * React to a message in a chat session.
     *
     * @param string $foreignID (sessionId in Nexus)
     * @param string $messageID
     * @param string $reaction 'like' or 'dislike'
     * @return HttpResponse
     * @throws Exception
     */
    public function reactToMessage(string $foreignID, string $messageID, string $reaction): HttpResponse
    {
        $like = $reaction === "like" ? "true" : "false";
        $response = $this->client->post("/like/$foreignID/$messageID/$like");

        if ($response->isSuccessful()) {
            $this->trackSession($response);
        } else {
            ErrorLogger::warning(
                "Failed to notify Nexus a reaction has been made.",
                ["nexus"],
                [
                    "response" => $response,
                    "chatSessionID" => $foreignID,
                    "messageID" => $messageID,
                    "reaction" => $reaction,
                ]
            );
        }

        return $response;
    }

    /**
     * Notify Nexus when a reaction has been removed on a message.
     *
     * @param string $foreignID (sessionId in Nexus)
     * @param string $messageID
     * @return HttpResponse
     * @throws Exception
     */
    public function removeMessageReaction(string $foreignID, string $messageID): HttpResponse
    {
        $response = $this->client->delete("/like/$foreignID/$messageID");

        if ($response->isSuccessful()) {
            $this->trackSession($response);
        } else {
            ErrorLogger::warning(
                "Failed to notify Nexus a reaction has been removed.",
                ["nexus"],
                [
                    "response" => $response,
                    "chatSessionID" => $foreignID,
                    "messageID" => $messageID,
                ]
            );
        }

        return $response;
    }

    /**
     * Ask Nexus to write a summary of the conversation as a question for the community.
     *
     * @param string $foreignID
     * @return HttpResponse
     * @throws Exception
     */
    public function askCommunity(string $foreignID): HttpResponse
    {
        // TODO: Remove the body kludge to make the request work when the endpoint has been fixed.
        $response = $this->client->post("/session/" . $this->getModel() . "/$foreignID/AskTheHumansPost", ["" => ""]);

        if ($response->isSuccessful()) {
            $this->trackSession($response);
        } else {
            ErrorLogger::warning(
                "Failed to generate a question summary of the conversation.",
                ["nexus"],
                [
                    "response" => $response,
                    "chatSessionID" => $foreignID,
                ]
            );
        }

        return $response;
    }

    /**
     * Track a Nexus session.
     *
     * @param HttpResponse $response
     * @return void
     * @throws Exception
     */
    private function trackSession(HttpResponse $response): void
    {
        $body = $response->getBody();
        if (isset($body["sessionId"])) {
            $this->aiConversationModel->trackConversation(
                $body["sessionId"],
                $this->getModel(),
                $body["messages"] ?? []
            );
        } else {
            ErrorLogger::warning(
                "There are no sessionId assigned to this search request made to Nexus.",
                ["nexus"],
                ["response" => $response]
            );
        }
    }

    /**
     * Normalize the output of the conversation.
     *
     * @param array $rows
     * @return array
     * @throws Exception
     */
    public function normalizeOutput(array $rows): array
    {
        // Session has a totally different meaning in Vanilla so we will use nexusID instead.
        $data = [
            "foreignID" => $rows["sessionId"],
            "source" => $this->getModel(),
        ];

        // Normalize the messages.
        if (isset($rows["messages"])) {
            $data["messages"] = $this->normalizeMessages($rows["messages"]);
        }

        // Add the conversation data we are tracking.
        $conversation = $this->aiConversationModel->getConversationByForeignID($data["foreignID"], $this->getModel());
        $data = array_merge($data, $conversation);

        if (isset($rows["diagnostics"])) {
            unset($conversation["lastMessageBody"]);
            $this->logDiagnostics($rows, ["conversation" => $conversation]);
        }

        return $data;
    }

    /**
     * Normalize the response coming from Nexus.
     *
     * @param array $rows
     * @return array
     */
    private function normalizeMessages(array $rows): array
    {
        $messages = [];
        foreach ($rows as $row) {
            $message["messageID"] = $row["messageId"];
            $message["body"] = $row["message"];
            $message["feedback"] = $row["feedback"];
            $message["confidence"] = $row["confidence"];
            $message["dateInserted"] = new DateTimeImmutable($row["messageTime"]);

            // Nexus is not 100% consistent with the capitalization.
            $sender = strtolower($row["role"]);

            if ($sender == "assistant") {
                $message["user"] = $this->config->get(self::ASSISTANT_NAME_CONFIG_KEY, "Assistant");
            } else {
                $message["user"] = Gdn::session()->User->Name ?? t("Me");
            }

            if (isset($row["messageReferences"])) {
                $message["references"] = $this->normalizeReferences($row["messageReferences"]);
            }

            $reaction = $row["actionStates"]["Liked"] ?? false;
            if ($reaction === "true") {
                $message["reaction"] = "like";
            } elseif ($reaction === "false") {
                $message["reaction"] = "dislike";
            } else {
                $message["reaction"] = null;
            }

            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Normalize the references.
     *
     * @param array $rows
     * @return array
     */
    private function normalizeReferences(array $rows): array
    {
        $references = [];

        foreach ($rows as $row) {
            $reference["recordID"] = $row["itemKey"];
            $reference["recordType"] = $row["itemType"];
            $reference["name"] = $row["title"];
            $reference["url"] = $row["url"];
            $references[] = $reference;
        }
        return $references;
    }

    /**
     * Log the diagnostics metrics coming from Nexus.
     *
     * @param array $rows
     * @param array $context
     * @return void
     */
    private function logDiagnostics(array $rows, array $context = [])
    {
        if (!isset($rows["diagnostics"])) {
            return;
        }

        $diagnostics = $this->normalizeDiagnostic($rows["diagnostics"]);
        $context += [
            "diagnostics" => $diagnostics,
            "sessionId" => $rows["sessionId"],
            "model" => $this->getModel(),
        ];

        ErrorLogger::notice("Nexus diagnostics", ["nexus", "rag", "ai-conversation"], $context);
    }

    /**
     * Format the diagnostics coming from Nexus.
     *
     * @param array $rows
     * @return array
     */
    private function normalizeDiagnostic(array $rows): array
    {
        $diagnostic = [];
        foreach ($rows as $row) {
            if (isset($row["key"], $row["value"])) {
                $diagnostic[$row["key"]] = $row["value"];
            }
        }

        return $diagnostic;
    }

    /**
     * Normalize the response coming from Nexus when asking the community.
     *
     * @param array $rows
     * @return array
     */
    public function normalizeAskCommunity(array $rows): array
    {
        $this->logDiagnostics($rows);
        $data = json_decode($rows["data"], true);

        $categoryID = $this->config->get(self::AI_CONVERSATION_ASK_COMMUNITY_CATEGORY_CONFIG_KEY);
        $postType = $this->config->get(self::AI_CONVERSATION_ASK_COMMUNITY_POST_TYPE_CONFIG_KEY, "discussion");
        $body = $this->formatAskBody($data["Message"]);

        return [
            "name" => $data["Subject"],
            "body" => $body,
            "format" => "html",
            "summary" => $data["ChatSummary"],
            "categoryID" => $categoryID,
            "postType" => $postType,
        ];
    }

    /**
     * Replace the macros coming from Nexus with their values.
     *
     * @param string $body
     * @return string
     */
    private function formatAskBody(string $body): string
    {
        $body = str_replace(["[Your Name]"], Gdn::session()->User->Name, $body);
        return $body;
    }

    /**
     * Get the LLM model used to generate the response.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->config->get(self::NEXUS_RAG_MODEL_CONFIG_KEY);
    }
}
