<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Tests\Events;

use Garden\Http\HttpClient;
use Garden\Http\Mocks\MockHttpClient;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Events\AiConversationEvent;
use Vanilla\Dashboard\Models\AiConversationModel;
use Vanilla\Dashboard\Models\NexusAiConversationClient;
use Vanilla\Dashboard\Tests\NexusAiConversationMockClient;
use Vanilla\OpenAI\OpenAIClient;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\OpenAI\MockOpenAIClient;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for the AiConversationEvent resource event.
 */
class AiConversationEventTest extends SiteTestCase
{
    use CommunityApiTestTrait, EventSpyTestTrait, UsersAndRolesApiTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $mockOpenAIClient = $this->container()->get(MockOpenAIClient::class);
        $this->container()->setInstance(OpenAIClient::class, $mockOpenAIClient);

        // Set up mock client for testing
        $httpClient = $this->container()->get(MockHttpClient::class);
        $this->container()->setInstance(HttpClient::class, $httpClient);

        $client = $this->container()->get(NexusAiConversationMockClient::class);
        $this->container()->setInstance(NexusAiConversationClient::class, $client);

        $config = $this->container()->get(ConfigurationInterface::class);
        $config->saveToConfig([
            AiConversationModel::AI_CONVERSATION_FEATURE_CONFIG => true,
        ]);

        // Reset the table for clean test runs
        $this->resetTable("aiConversation");
        $this->resetTable("aiConversationMessage");
    }

    /**
     * Test the conversation started event when starting a new conversation.
     *
     * @return void
     */
    public function testConversationStartedEvent(): void
    {
        $conversation = $this->api()
            ->post("/ai-conversations")
            ->getBody();

        $expected = self::getConversationContent($conversation, AiConversationEvent::SOURCE_CHAT);
        $this->assertEventDispatched(
            new AiConversationEvent(AiConversationEvent::ACTION_CONVERSATION_STARTED, $expected)
        );
    }

    /**
     * Test the search summary generated event when performing a search.
     *
     * @return void
     */
    public function testSearchSummaryGeneratedEvent(): void
    {
        $conversation = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();

        $expected = self::getConversationContent($conversation, AiConversationEvent::SOURCE_SEARCH);
        $this->assertEventDispatched(
            new AiConversationEvent(AiConversationEvent::ACTION_SEARCH_SUMMARY_GENERATED, $expected)
        );
    }

    /**
     * Test the user message sent event when replying to a conversation.
     *
     * @return void
     */
    public function testUserMessageSentEvent(): void
    {
        $conversation = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();

        $replyResult = $this->api()
            ->post("/ai-conversations/{$conversation["conversationID"]}/reply", [
                "body" => "What is the meaning of life?",
            ])
            ->getBody();

        $expected = self::getConversationContent($conversation, AiConversationEvent::SOURCE_SEARCH);

        foreach ($replyResult["messages"] as $message) {
            $expected["message"] = $message;
            $expected["source"] = AiConversationEvent::SOURCE_CHAT;
            $expected["aiResponse"] = $message["aiResponse"];
            $this->assertEventDispatched(
                new AiConversationEvent(AiConversationEvent::ACTION_USER_MESSAGE_SENT, $expected)
            );
        }

        $this->assertEventDispatched(
            new AiConversationEvent(AiConversationEvent::ACTION_SEARCH_SUMMARY_GENERATED, $expected)
        );
    }

    /**
     * Test the AI response rated event when reacting to a message.
     *
     * @return void
     */
    public function testAiResponseRatedEvent(): void
    {
        $conversation = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();
        $messageID = $conversation["messages"][1]["messageID"];
        $this->api()->put("/ai-conversations/{$conversation["conversationID"]}/react", [
            "messageID" => $messageID,
            "reaction" => "like",
        ]);

        // Get the updated conversation to assert the event
        $conversation = $this->api()
            ->get("/ai-conversations/{$conversation["conversationID"]}")
            ->getBody();

        $expected = self::getConversationContent($conversation, AiConversationEvent::SOURCE_CHAT);
        $expected["messageID"] = $messageID;
        $expected["reaction"] = "like";
        ksort($expected);

        $this->assertEventDispatched(new AiConversationEvent(AiConversationEvent::ACTION_AI_RESPONSE_RATED, $expected));
    }

    /**
     * Test the user feedback event when providing feedback on a message.
     *
     * @return void
     */
    public function testUserFeedbackEvent(): void
    {
        $conversation = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();
        $messageID = $conversation["messages"][1]["messageID"];
        $this->api()->put("/ai-conversations/{$conversation["conversationID"]}/feedback", [
            "messageID" => $messageID,
            "body" => "Good bot!",
        ]);

        // Get the updated conversation to assert the event
        $conversation = $this->api()
            ->get("/ai-conversations/{$conversation["conversationID"]}")
            ->getBody();

        $expected = self::getConversationContent($conversation, AiConversationEvent::SOURCE_CHAT);
        $expected["messageID"] = $messageID;

        $this->assertEventDispatched(new AiConversationEvent(AiConversationEvent::ACTION_USER_FEEDBACK, $expected));
    }

    /**
     * Test the user copied event when a message is copied.
     *
     * @return void
     */
    public function testUserCopiedEvent(): void
    {
        $conversation = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();

        $messageID = $conversation["messages"][1]["messageID"];
        $this->api()->post("/ai-conversations/{$conversation["conversationID"]}/copied", [
            "messageID" => $messageID,
        ]);

        // Get the updated conversation to assert the event
        $conversation = $this->api()
            ->get("/ai-conversations/{$conversation["conversationID"]}")
            ->getBody();

        $expected = self::getConversationContent($conversation, AiConversationEvent::SOURCE_CHAT);
        $expected["messageID"] = $messageID;

        $this->assertEventDispatched(new AiConversationEvent(AiConversationEvent::ACTION_USER_COPIED, $expected));
    }

    /**
     * Test the search summary generated event when asking the community.
     *
     * @return void
     */
    public function testAskCommunityEvent(): void
    {
        // Set up required data for ask community
        $this->createCategory();
        $this->createPostType();
        $conversation = $this->api()
            ->post("/ai-conversations", ["body" => "What is the meaning of life?"])
            ->getBody();
        $this->api()->post("/ai-conversations/{$conversation["conversationID"]}/ask-community", [
            "source" => AiConversationEvent::SOURCE_CHAT,
        ]);

        // Get the updated conversation to assert the event
        $conversation = $this->api()
            ->get("/ai-conversations/{$conversation["conversationID"]}")
            ->getBody();

        $expected = self::getConversationContent($conversation, AiConversationEvent::SOURCE_CHAT);
        $this->assertEventDispatched(new AiConversationEvent(AiConversationEvent::ACTION_AI_ASK_COMMUNITY, $expected));
    }

    /**
     * Normalizes the conversation content for event assertions.
     *
     * @param array $conversation
     * @param string $source
     * @return array
     */
    private static function getConversationContent(array $conversation, string $source): array
    {
        return [
            "aiConversation" => [
                "conversationID" => $conversation["conversationID"],
                "foreignID" => $conversation["foreignID"],
                "source" => $conversation["source"],
                "insertUserID" => $conversation["insertUserID"],
                "sessionID" => $conversation["sessionID"],
                "dateInserted" => $conversation["dateInserted"],
                "lastMessageID" => $conversation["lastMessageID"],
                "dateLastMessage" => $conversation["dateLastMessage"],
            ],
            "source" => $source,
        ];
    }
}
