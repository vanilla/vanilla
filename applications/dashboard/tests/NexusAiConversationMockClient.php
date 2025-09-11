<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Tests;

use Garden\Container\ContainerException;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Http\Mocks\MockHttpClient;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\NexusAiConversationClient;
use Vanilla\Forum\Models\CategorySuggestionModel;
use Vanilla\Forum\Models\TagSuggestionModel;
use Vanilla\Site\OwnSiteProvider;

/**
 * Mock client used to test the AiConversationClient.
 */
class NexusAiConversationMockClient extends NexusAiConversationClient
{
    private string $conversationSessionID;
    private string $searchConversationID;
    private string $lastMessageID;

    /**
     * D.I.
     *
     * @param ConfigurationInterface $config
     * @param OwnSiteProvider $siteProvider
     * @throws ContainerException
     */
    public function __construct(
        protected ConfigurationInterface $config,
        OwnSiteProvider $siteProvider,
        CategorySuggestionModel $categorySuggestionModel,
        TagSuggestionModel $tagSuggestionModel
    ) {
        $config->saveToConfig([
            NexusAiConversationClient::NEXUS_AI_CONVERSATION_URL_CONFIG_KEY => "http://nexus-rag-mock-url",
            NexusAiConversationClient::NEXUS_AUTH_URL_CONFIG_KEY => "http://nexus-rag-mock-auth-url",
            NexusAiConversationClient::NEXUS_AI_CONVERSATION_CLIENT_ID_CONFIG_KEY => "test-client-id",
            NexusAiConversationClient::NEXUS_AI_CONVERSATION_CLIENT_SECRET_CONFIG_KEY => "test-client-secret",
            NexusAiConversationClient::NEXUS_AI_CONVERSATION_MODEL_CONFIG_KEY => "test-recipe-code",
        ]);

        $httpClient = Gdn::getContainer()->get(MockHttpClient::class);
        parent::__construct($httpClient, $this->config, $siteProvider, $categorySuggestionModel, $tagSuggestionModel);
        $this->setMockResponses();
    }

    /**
     * Mock generating a token from Nexus. We can't mock this request since the token must be unique.
     *
     * @return string|null
     */
    public function createToken(): ?string
    {
        $token = MD5(microtime());
        $this->config->saveToConfig(self::NEXUS_AI_CONVERSATION_TOKEN_CONFIG_KEY, $token);
        $this->config->saveToConfig(self::NEXUS_AI_CONVERSATION_TOKEN_EXPIRATION_CONFIG_KEY, time() + 3600);
        return $token;
    }

    /**
     * Generate the mock responses coming from Nexus.
     *
     * @return void
     */
    public function setMockResponses(): void
    {
        $this->conversationSessionID = MD5(mt_rand(1, 1000000));
        $this->searchConversationID = MD5(mt_rand(1, 1000000));

        $messages = [
            $this->createMockMessage(),
            $this->createMockMessage([
                "role" => "assistant",
                "message" => "Your Boss dawg!",
                "feedback" => "Good bot!",
            ]),
        ];

        // Start Conversation / Search
        $this->createMockResponse(
            "POST",
            "http://nexus-rag-mock-url/session/test-recipe-code",
            $this->conversationSessionID
        );
        $this->createMockResponse(
            "POST",
            "http://nexus-rag-mock-url/search/test-recipe-code",
            $this->searchConversationID,
            $messages
        );

        // Get Conversation
        $this->createMockResponse(
            "GET",
            "http://nexus-rag-mock-url/session/test-recipe-code/$this->conversationSessionID",
            $this->conversationSessionID
        );

        // Like a message
        $this->createMockResponse(
            "POST",
            "http://nexus-rag-mock-url/like/$this->searchConversationID/$this->lastMessageID/true",
            $this->searchConversationID
        );

        $this->createMockResponse(
            "POST",
            "http://nexus-rag-mock-url/like/$this->searchConversationID/$this->lastMessageID/false",
            $this->searchConversationID
        );

        $this->createMockResponse(
            "DELETE",
            "http://nexus-rag-mock-url/like/$this->searchConversationID/$this->lastMessageID",
            $this->searchConversationID
        );

        // Feedback
        $this->createMockResponse(
            "POST",
            "http://nexus-rag-mock-url/feedback/$this->searchConversationID/$this->lastMessageID",
            $this->searchConversationID
        );

        // Copied
        $this->createMockResponse(
            "POST",
            "http://nexus-rag-mock-url/messageCopy/$this->searchConversationID/$this->lastMessageID",
            $this->searchConversationID
        );

        // Ask Community
        $this->createMockResponse(
            "POST",
            "http://nexus-rag-mock-url/session/test-recipe-code/$this->searchConversationID/AskTheHumansPost",
            $this->searchConversationID,
            data: "{\"Subject\":\"Seeking Insights on the Meaning of Life\",\"Message\":\"<div>Hi everyone,<br><br>I've been reflecting on a profound question: What is the meaning of life? Despite various discussions, I find that I'm still searching for a clear understanding. If anyone has thoughts, insights, or philosophical perspectives on this topic, I would greatly appreciate your input. Your experiences and viewpoints could really help broaden my understanding.<br><br>Thank you in advance for your contributions!</div>\",\"ChatSummary\":\"The user inquired about the meaning of life, indicating a deep interest in this philosophical topic but has not received any answers.\"}"
        );

        // Continue Conversation
        $messages = array_merge($messages, [
            $this->createMockMessage(["role" => "User", "message" => "What is the meaning of life?"]),
            $this->createMockMessage([
                "role" => "assistant",
                "message" => "42",
                "messageReferences" => [
                    [
                        "itemKey" => "0",
                        "title" => "The Hitchhiker's Guide to the Galaxy",
                        "url" => "https://en.wikipedia.org/wiki/The_Hitchhiker%27s_Guide_to_the_Galaxy",
                        "itemType" => "article",
                    ],
                ],
            ]),
        ]);
        $this->createMockResponse(
            "POST",
            "http://nexus-rag-mock-url/session/test-recipe-code/$this->searchConversationID",
            $this->searchConversationID,
            $messages
        );

        $this->createMockResponse(
            "GET",
            "http://nexus-rag-mock-url/session/test-recipe-code/$this->searchConversationID",
            $this->searchConversationID,
            $messages
        );
    }

    /**
     * Create a mock response from Nexus and register it.
     *
     * @param string $method
     * @param string $url
     * @param string $messageID
     * @param array $messages
     * @param array|string $data
     * @return void
     */
    private function createMockResponse(
        string $method,
        string $url,
        string $messageID,
        array $messages = [],
        array|string $data = []
    ): void {
        $body = [
            "sessionId" => $messageID,
            "diagnostics" => [
                [
                    "key" => "test",
                    "value" => "42ms",
                ],
            ],
        ];

        if (!empty($messages)) {
            $body["messages"] = $messages;
        }

        if (!empty($data)) {
            $body["data"] = $data;
        }

        $request = new HttpRequest($method, $url);
        $response = new HttpResponse(200);
        $response->setBody($body);
        $this->client->addMockRequest($request, $response);
    }

    /**
     * Mock a message coming from Nexus.
     *
     * @param array $overrides
     * @return array
     */
    private function createMockMessage(array $overrides = []): array
    {
        $this->lastMessageID = MD5(mt_rand(1, 1000000));
        $message = $overrides + [
            "role" => "User",
            "message" => "Who is Rob Wenger?",
            "messageId" => $this->lastMessageID,
            "actionStates" => [
                "Liked" => null,
            ],
            "confidence" => null,
            "feedback" => null,
            "messageTime" => "2025-04-16T20:12:21.3665999Z",
            "messageReferences" => [
                [
                    "itemKey" => "0",
                    "title" => "Vanilla Forums",
                    "url" => "https://success.vanillaforums.com/discussions",
                    "itemType" => "category",
                ],
            ],
        ];

        return $message;
    }
}
