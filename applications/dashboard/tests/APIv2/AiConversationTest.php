<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace APIv2;

use Exception;
use Garden\Container\ContainerException;
use Garden\Http\HttpClient;
use Garden\Http\Mocks\MockHttpClient;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\ServerException;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Events\AiConversationEvent;
use Vanilla\Dashboard\Models\NexusAiConversationClient;
use Vanilla\Dashboard\Models\AiConversationModel;
use Vanilla\Dashboard\Models\AiConversationMessageModel;
use Vanilla\Dashboard\Tests\NexusAiConversationMockClient;
use Vanilla\Logging\ErrorLogger;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\Site\OwnSite;
use VanillaTests\Fixtures\OpenAI\MockOpenAIClient;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for `/api/v2/ai-conversations` endpoints.
 */
class AiConversationTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;
    private AiConversationModel $aiConversationModel;
    private AiConversationMessageModel $aiConversationMessageModel;
    private bool $mockRun = false;
    private string $model;

    /**
     * Determine if we are running against Nexus or against a MockClient.
     *
     * To set this up, simply configure the following environment variables in your phpunit.xml.
     *
     * @return void
     * @throws ContainerException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->config = $this->container()->get(ConfigurationInterface::class);
        $this->config->saveToConfig([
            ErrorLogger::CONF_LOG_NOTICES => true,
            AiConversationModel::AI_CONVERSATION_FEATURE_CONFIG => true,
        ]);

        $this->setUpOpenAI();
        $this->setUpNexus();
        $this->aiConversationModel = $this->container()->get(AiConversationModel::class);
        $this->aiConversationMessageModel = $this->container()->get(AiConversationMessageModel::class);
    }

    /**
     * Test creating and refreshing a token.
     *
     * @return void
     */
    public function testCreateAndRefreshToken(): void
    {
        $client = $this->container()->get(NexusAiConversationClient::class);
        $oldToken = $client->createToken();
        // Force the token to refresh should be refreshed when running utility update.
        $this->bessy()->get("/utility/update");

        $currentToken = $this->config->get(NexusAiConversationClient::NEXUS_AI_CONVERSATION_TOKEN_CONFIG_KEY);
        $this->assertNotEquals($oldToken, $currentToken);
    }

    /**
     * Test trying to access the endpoint with the feature disabled.
     *
     * @return void
     * @throws ContainerException
     */
    public function testFeatureDisabled(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("AI conversation feature is not enabled.");
        $config = $this->container()->get(ConfigurationInterface::class);
        $config->saveToConfig([
            AiConversationModel::AI_CONVERSATION_FEATURE_CONFIG => false,
        ]);

        $this->api()->get("/ai-conversations");
    }

    /**
     * Test [POST] `/api/v2/ai-conversations` without a message.
     *
     * @return void
     * @throws Exception
     */
    public function testPostStartConversation(): void
    {
        $result = $this->api()
            ->post("/ai-conversations")
            ->getBody();

        $this->assertSessionTracked($result["conversationID"]);
        $this->assertErrorLog([
            "level" => "notice",
            "message" => "Nexus diagnostics",
            "tags" => ["nexus", "ai-conversation"],
        ]);

        $conversation = $this->aiConversationModel->getConversationByID($result["conversationID"]);
        $this->assertNull($conversation["lastMessageID"]);
        $this->assertEquals(Gdn::session()->UserID, $conversation["insertUserID"]);
    }

    /**
     * Test [POST] `/api/v2/ai-conversations` with an initial message.
     *
     * @return void
     * @throws Exception
     */
    public function testPostSearch(): void
    {
        $result = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();

        $this->assertSessionTracked($result["conversationID"]);
        $this->assertNotEmpty($result["messages"]);
        $this->assertErrorLog([
            "level" => "notice",
            "message" => "Nexus diagnostics",
            "tags" => ["nexus", "ai-conversation"],
        ]);

        // We don't know for sure if Nexus will add references or not.
        if (isset($result["messages"][1]["references"])) {
            foreach ($result["messages"][1]["references"] as $reference) {
                $this->assertEquals(
                    "utm_source=community-search&utm_medium=ai-search",
                    parse_url($reference["url"], PHP_URL_QUERY)
                );
            }
        }

        // Make sure the messages are attributed to the right user.
        $this->assertEquals($result["messages"][0]["user"], Gdn::session()->User->Name);
        $this->assertEquals($result["messages"][1]["user"], "Assistant");
        $conversation = $this->aiConversationModel->getConversationByID($result["conversationID"]);
        $this->assertNotNull($conversation["lastMessageID"]);
        $this->assertEquals(Gdn::session()->UserID, $conversation["insertUserID"]);

        $userMessage = $this->aiConversationMessageModel->selectSingle([
            "conversationID" => $conversation["conversationID"],
            "aiResponse" => false,
        ]);
        $this->assertEquals($userMessage["body"], $result["messages"][0]["body"]);
        $this->assertEquals($userMessage["foreignID"], $result["messages"][0]["messageID"]);

        $aiMessage = $this->aiConversationMessageModel->selectSingle([
            "conversationID" => $conversation["conversationID"],
            "aiResponse" => true,
        ]);
        $this->assertEquals($aiMessage["body"], $result["messages"][1]["body"]);
        $this->assertEquals($aiMessage["foreignID"], $result["messages"][1]["messageID"]);
    }

    /**
     * Test getting multiple conversations with [GET] `/api/v2/ai-conversations`.
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->resetTable("aiConversation");

        CurrentTimeStamp::mockTime("2023-04-01");
        $conversation1 = $this->api()
            ->post("/ai-conversations")
            ->getBody();

        CurrentTimeStamp::mockTime("2023-04-02");
        $conversation2 = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();

        $result = $this->api()
            ->get("/ai-conversations")
            ->getBody();
        $this->assertCount(2, $result);

        // We expect the conversation to be sorted by dateLastMessage in descending order.
        $this->assertEquals($conversation1["foreignID"], $result[1]["foreignID"]);
        $this->assertEquals($conversation2["foreignID"], $result[0]["foreignID"]);
        CurrentTimeStamp::clearMockTime();
    }

    /**
     * Test getting multiple conversations with [GET] `/api/v2/ai-conversations` when not logged in.
     *
     * @return void
     */
    public function testIndexNotLoggedIn(): void
    {
        $guest = $this->createUser(["roleID" => [\RoleModel::GUEST_ID]]);
        $this->runWithUser(function () {
            $this->expectException(ForbiddenException::class);
            $this->expectExceptionMessage("Permission Problem");
            $this->api()->get("/ai-conversations");
        }, $guest);
    }

    /**
     * Test getting the conversations from another user without the proper permissions by calling [GET] `/api/v2/ai-conversations`.
     *
     * @return void
     */
    public function testGettingAnotherUserConversationsIndex(): void
    {
        $role = $this->createRole(
            [],
            [
                "aiAssistedSearch.view" => true,
            ]
        );

        $attacker = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID, $role["roleID"]]]);
        $victim = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID, $role["roleID"]]]);

        // Make sure the user can check themselves.
        $conversation = $this->runWithUser(function () use ($attacker) {
            return $this->api()->post("/ai-conversations");
        }, $victim);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");

        $this->runWithUser(function () use ($conversation) {
            $this->api()->get("/ai-conversations/{$conversation["conversationID"]}");
        }, $attacker);
    }

    /**
     * Test getting the conversations from another user without the proper permissions by calling [GET] `/api/v2/ai-conversations/{id}`.
     *
     * @return void
     */
    public function testGettingAnotherUserConversations(): void
    {
        $role = $this->createRole(
            [],
            [
                "aiAssistedSearch.view" => true,
            ]
        );

        $attacker = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID, $role["roleID"]]]);
        $victim = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID]]);

        // Make sure the user can check themselves.
        $this->runWithUser(function () use ($attacker) {
            $this->api()->get("/ai-conversations", ["insertUserID" => $attacker["userID"]]);
        }, $attacker);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");

        $this->runWithUser(function () use ($victim) {
            $this->api()->get("/ai-conversations", ["insertUserID" => $victim["userID"]]);
        }, $attacker);
    }

    /**
     * Test [GET] `/api/v2/ai-conversations/{id}`.
     *
     * @return void
     */
    public function testGetConversation(): void
    {
        // Start a conversation.
        $conversation = $this->api()
            ->post("/ai-conversations")
            ->getBody();

        // Fetch that same conversation.
        $getResponse = $this->api()
            ->get("/ai-conversations/{$conversation["conversationID"]}")
            ->getBody();

        $this->assertEquals($conversation, $getResponse);
    }

    /**
     * Test [POST] `/api/v2/ai-conversations/{id}/reply`.
     *
     * @return void
     * @throws Exception
     */
    public function testReplyConversation(): void
    {
        // Start a conversation.
        $searchRequest = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();
        $this->assertCount(2, $searchRequest["messages"]);

        $conversationID = $searchRequest["conversationID"];

        // Reply on the conversation.
        $replyRequest = $this->api()
            ->post("/ai-conversations/$conversationID/reply", [
                "body" => "What is the meaning of life?",
            ])
            ->getBody();
        $this->assertErrorLog([
            "level" => "notice",
            "message" => "Nexus diagnostics",
            "tags" => ["nexus", "ai-conversation"],
        ]);

        // We expect two more message, one from the user and one from the assistant.
        $this->assertCount(4, $replyRequest["messages"]);
        $this->assertSessionTracked($conversationID);

        // We don't know for sure if Nexus will add references or not.
        if (isset($replyRequest["messages"][3]["references"])) {
            foreach ($replyRequest["messages"][3]["references"] as $reference) {
                $this->assertEquals(
                    "utm_source=community-search&utm_medium=ai-conversation",
                    parse_url($reference["url"], PHP_URL_QUERY)
                );
            }
        }

        $messages = $this->aiConversationMessageModel->select(
            [
                "conversationID" => $conversationID,
            ],
            [
                "orderFields" => "messageID",
                "orderDirection" => "asc",
            ]
        );

        foreach ($messages as $key => $message) {
            $this->assertEquals($messages[$key]["body"], $replyRequest["messages"][$key]["body"]);
            $this->assertEquals($messages[$key]["foreignID"], $replyRequest["messages"][$key]["messageID"]);
        }
    }

    /**
     * Test liking a message using [PUT] `/api/v2/ai-conversations/{id}/react`
     *
     * @return void
     */
    public function testPutLikeReact(): void
    {
        $searchRequest = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();

        // We want to take a message from the assistant.
        $conversationID = $searchRequest["conversationID"];
        $messageID = $searchRequest["messages"][1]["messageID"];

        // This is a good answer. Let's like it!
        $this->api()
            ->put("/ai-conversations/$conversationID/react", [
                "messageID" => $messageID,
                "reaction" => "like",
            ])
            ->assertSuccess();
        $this->assertAiMessageReaction("like", $searchRequest["conversationID"], $messageID);

        // Never mind, this is too much pressure. I'll just remove my reaction.
        $this->api()
            ->put("/ai-conversations/$conversationID/react", [
                "messageID" => $messageID,
                "reaction" => null,
            ])
            ->assertSuccess();
        $this->assertAiMessageReaction(null, $searchRequest["conversationID"], $messageID);
    }

    /**
     * Test disliking a message using [PUT] `/api/v2/ai-conversations/{id}react`
     *
     * @return void
     */
    public function testPutDislikeReact(): void
    {
        $searchRequest = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();

        // We want to take a message from the assistant.
        $conversationID = $searchRequest["conversationID"];
        $messageID = $searchRequest["messages"][1]["messageID"];

        // I don't like it!
        $this->api()
            ->put("/ai-conversations/$conversationID/react", [
                "messageID" => $messageID,
                "reaction" => "dislike",
            ])
            ->assertSuccess();
        $this->assertAiMessageReaction("dislike", $searchRequest["conversationID"], $messageID);

        // Never mind, this is too much pressure. I'll just remove my reaction.
        $this->api()
            ->put("/ai-conversations/$conversationID/react", [
                "messageID" => $messageID,
            ])
            ->assertSuccess();
        $this->assertAiMessageReaction(null, $searchRequest["conversationID"], $messageID);
    }

    /**
     * Test that we can't react to a message with an invalid reaction.
     *
     * @return void
     */
    public function testPutInvalidReaction(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("reaction must be one of: like, dislike, null.");

        $conversation = $this->api()->post("/ai-conversations", ["body" => "Who is Rob Wenger?"]);
        $this->api()->put("/ai-conversations/{$conversation["conversationID"]}/react", [
            "messageID" => $conversation["messageID"],
            "reaction" => "invalid",
        ]);
    }

    /**
     * Test [PUT] `/api/v2/ai-conversations/{conversationID}/feedback`.
     *
     * @return void
     */
    public function testPutFeedback(): void
    {
        $searchRequest = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();

        // We want to take a message from the assistant.
        $conversationID = $searchRequest["conversationID"];
        $messageID = $searchRequest["messages"][1]["messageID"];

        $this->api()
            ->put("/ai-conversations/$conversationID/feedback", [
                "messageID" => $messageID,
                "body" => "Good bot!",
            ])
            ->assertSuccess();

        $conversation = $this->api()
            ->get("/ai-conversations/{$conversationID}")
            ->getBody();
        $this->assertEquals("Good bot!", $conversation["messages"][1]["feedback"]);
    }

    /**
     * Test notifying that a message has been copied using [POST] `/api/v2/ai-conversations/{id}/copied`.
     *
     * @return void
     */
    public function testMessageCopied(): void
    {
        $searchRequest = $this->api()
            ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
            ->getBody();
        $conversationID = $searchRequest["conversationID"];
        $messageID = $searchRequest["messages"][1]["messageID"];

        $response = $this->api()
            ->post("/ai-conversations/$conversationID/copied", [
                "messageID" => $messageID,
            ])
            ->assertSuccess();
    }

    /**
     * Test calling the `/api/v2/ai-conversations` endpoints as an unauthorized user.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return void
     * @dataProvider provideConversationEndpoints
     * @dataProvider provideConversationActionEndpoints
     */
    public function testUnauthorizedUser(string $method, string $endpoint, array $body): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");

        $conversation = $this->api()->post("/ai-conversations", ["body" => "Who is Rob Wenger?"]);
        $endpoint = str_replace("{id}", $conversation["conversationID"], $endpoint);

        $user = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID]]);
        $this->runWithUser(function () use ($method, $endpoint, $body) {
            $this->api()->request($method, $endpoint, $body);
        }, $user);
    }

    /**
     * Test calling the `/api/v2/ai-conversations` endpoints on a conversation started by another user.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return void
     * @dataProvider provideConversationActionEndpoints
     */
    public function testActionOnDifferentUserConversation(string $method, string $endpoint, array $body): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("You are not allowed to interact with this conversation.");

        $role = $this->createRole(
            [],
            [
                "aiAssistedSearch.view" => true,
            ]
        );

        $conversation = $this->api()->post("/ai-conversations", ["body" => "Who is Rob Wenger?"]);
        $endpoint = str_replace("{id}", $conversation["conversationID"], $endpoint);

        $user = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID, $role["roleID"]]]);
        $this->runWithUser(function () use ($method, $endpoint, $body) {
            $this->api()->request($method, $endpoint, $body);
        }, $user);
    }

    /**
     * Make sure Guest can access the endpoints.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return void
     * @dataProvider provideConversationEndpoints
     * @dataProvider provideConversationActionEndpoints
     */
    public function testEndpointsAsGuest(string $method, string $endpoint, array $body): void
    {
        $roleID = \RoleModel::GUEST_ID;
        $this->api()->patch("/roles/$roleID/permissions", [
            [
                "permissions" => ["aiAssistedSearch.view" => true],
                "type" => "global",
            ],
        ]);

        $this->runWithUser(function () use ($method, $endpoint, $body) {
            $conversation = $this->api()
                ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
                ->getBody();
            $endpoint = str_replace("{id}", $conversation["conversationID"], $endpoint);

            if (isset($body["messageID"])) {
                $body["messageID"] = $conversation["messages"][1]["messageID"];
            }

            $response = $this->api()
                ->request($method, $endpoint, $body)
                ->assertSuccess();
        }, \UserModel::GUEST_USER_ID);
    }

    /**
     * Make sure that other guest conversations are not accessible.
     *
     * @return void
     */
    public function testAccessingAnotherGuestConversation(): void
    {
        $roleID = \RoleModel::GUEST_ID;
        $this->api()->patch("/roles/$roleID/permissions", [
            [
                "permissions" => ["aiAssistedSearch.view" => true],
                "type" => "global",
            ],
        ]);

        $conversation = $this->runWithUser(function () {
            return $this->api()
                ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
                ->getBody();
        }, \UserModel::GUEST_USER_ID);
        Gdn::session()->end();

        $conversationID = $conversation["conversationID"];

        $this->runWithUser(function () use ($conversationID) {
            // We don't have any ongoing conversation listed.
            $result = $this->api()
                ->get("/ai-conversations")
                ->getBody();
            $this->assertEmpty($result);

            // We can't access the conversation.
            $this->expectException(ClientException::class);
            $this->expectExceptionMessage("You are not allowed to interact with this conversation.");
            $this->api()->get("/ai-conversations/$conversationID");
        }, \UserModel::GUEST_USER_ID);
    }

    /**
     * Test that a guest is not allowed to access another guest conversation.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @return void
     * @dataProvider provideConversationActionEndpoints
     */
    public function testActingOnAnotherGuestConversation(string $method, string $endpoint, array $body): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("You are not allowed to interact with this conversation.");

        $roleID = \RoleModel::GUEST_ID;
        $this->api()->patch("/roles/$roleID/permissions", [
            [
                "permissions" => ["aiAssistedSearch.view" => true],
                "type" => "global",
            ],
        ]);

        $conversation = $this->runWithUser(function () {
            return $this->api()
                ->post("/ai-conversations", ["body" => "Who is Rob Wenger?"])
                ->getBody();
        }, \UserModel::GUEST_USER_ID);

        if (isset($body["messageID"])) {
            $body["messageID"] = $conversation["messages"][1]["messageID"];
        }

        Gdn::session()->end();

        $endpoint = str_replace("{id}", $conversation["conversationID"], $endpoint);
        $this->runWithUser(function () use ($method, $endpoint, $body) {
            $this->api()->request($method, $endpoint, $body);
        }, \UserModel::GUEST_USER_ID);
    }

    /**
     * Provide every endpoint for the test.
     *
     * ["method", "endpoint", "body"]
     *
     * @return array
     */
    public static function provideConversationEndpoints(): array
    {
        return [
            "[GET] /api/v2/ai-conversation" => ["GET", "/ai-conversations", []],
            "[POST] /api/v2/ai-conversations" => ["POST", "/ai-conversations", []],
            "[GET] /api/v2/ai-conversations/id" => ["GET", "/ai-conversations/{id}", []],
        ];
    }

    /**
     * List of endpoints that apply an action on an ongoing conversation.
     *
     * @return array
     */
    public static function provideConversationActionEndpoints(): array
    {
        return [
            "[POST] /api/v2/ai-conversations/id/reply" => [
                "POST",
                "/ai-conversations/{id}/reply",
                ["body" => "What is the meaning of life?"],
            ],
            "[PUT] /api/v2/ai-conversations/id/react" => [
                "PUT",
                "/ai-conversations/{id}/react",
                ["messageID" => "123456789", "reaction" => "like"],
            ],
            "[PUT] /api/v2/ai-conversations/id/feedback" => [
                "PUT",
                "/ai-conversations/{id}/feedback",
                ["messageID" => "123456789", "body" => "Good bot!"],
            ],
            "[POST] /api/v2/ai-conversations/id/copied" => [
                "POST",
                "/ai-conversations/{id}/copied",
                ["messageID" => "123456789"],
                [],
            ],
        ];
    }

    /**
     * Test generating a post from the AI conversation by calling [POST] `/api/v2/ai-conversations/{id}/ask-community`.
     *
     * @return void
     */
    public function testAskCommunity(): void
    {
        $searchRequest = $this->api()
            ->post("/ai-conversations", [
                "body" => "What is the meaning of life?",
            ])
            ->getBody();
        $conversationID = $searchRequest["conversationID"];

        $this->api()
            ->post("/ai-conversations/$conversationID/ask-community", [
                "calledFrom" => AiConversationEvent::SOURCE_CHAT,
            ])
            ->assertSuccess()
            ->assertJsonObjectHasKeys(["name", "body", "format", "summary", "categoryID", "postType"])
            ->assertJsonObjectLike([
                "categoryID" => 1,
                "postType" => "discussion",
                "tags" => [["id" => $this->lastInsertedTagID, "label" => "tag", "confidence" => "0.95"]],
            ]);
    }

    /**
     * Test that guest are not allowed to use the ask community endpoint.
     *
     * @return void
     */
    public function testAskCommunityAsGuest(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Permission Problem");
        $searchRequest = $this->api()
            ->post("/ai-conversations", [
                "body" => "What is the meaning of life?",
            ])
            ->getBody();

        $conversationID = $searchRequest["conversationID"];
        $this->runWithUser(function () use ($conversationID) {
            $this->api()->post("/ai-conversations/$conversationID/ask-community", [
                "calledFrom" => AiConversationEvent::SOURCE_SEARCH,
            ]);
        }, \UserModel::GUEST_USER_ID);
    }

    /**
     * Test calling the ask community endpoint with an invalid calledFrom.
     *
     * @return void
     */
    public function testAskCommunityInvalidCalledFrom(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("calledFrom must be one of: search, chat");

        $searchRequest = $this->api()
            ->post("/ai-conversations", ["body" => "What is the meaning of life?"])
            ->getBody();
        $conversationID = $searchRequest["conversationID"];
        $this->api()->post("/ai-conversations/$conversationID/ask-community", [
            "calledFrom" => "invalid_source",
        ]);
    }

    /**
     * Test calling the ask community endpoint without calledFrom.
     *
     * @return void
     */
    public function testAskCommunityNoCalledFrom(): void
    {
        $searchRequest = $this->api()
            ->post("/ai-conversations", ["body" => "What is the meaning of life?"])
            ->getBody();

        $conversationID = $searchRequest["conversationID"];
        $this->api()
            ->post("/ai-conversations/$conversationID/ask-community")
            ->assertSuccess()
            ->assertJsonObjectHasKeys(["name", "body", "format", "summary", "categoryID", "postType"])
            ->assertJsonObjectLike([
                "categoryID" => 1,
                "postType" => "discussion",
            ]);
    }

    /**
     * Assert that we are properly tracking the session.
     *
     * @param int $conversationID
     * @return void
     * @throws Exception
     */
    private function assertSessionTracked(int $conversationID): void
    {
        $session = $this->aiConversationModel->getRagConversation($conversationID);
        $this->assertNotFalse($session);
    }

    /**
     * Assert that a message has the expected reaction.
     *
     * Note: This check can be flaky. It's best to run it with a breakpoint just before we call the API.
     *
     * @param string|null $expected
     * @param int $conversationID
     * @param string $messageID
     * @return void
     */
    private function assertAiMessageReaction(?string $expected, int $conversationID, string $messageID): void
    {
        if ($this->mockRun) {
            // No need to check the reaction in the mock client.
            return;
        }

        // Give Nexus some time to process the reaction.
        sleep(5);

        $conversation = $this->api()
            ->get("/ai-conversations/{$conversationID}")
            ->getBody();

        foreach ($conversation["messages"] as $message) {
            if ($message["messageID"] === $messageID) {
                $this->assertEquals($expected, $message["reaction"]);
                return;
            }
        }

        $this->assertTrue(false, "Message not found in conversation.");
    }

    /**
     * Configure OpenAI to test AiConversation.
     *
     * @return void
     */
    private function setUpOpenAI(): void
    {
        $this->resetTable("Tag");
        $mockOpenAIClient = Gdn::getContainer()->get(MockOpenAIClient::class);
        $mockOpenAIClient->addMockResponse(
            "#Classify the input text by finding the best category and type based on a provided list.#",
            [
                "category" => [
                    [
                        "id" => 1,
                        "confidence" => 0.7,
                        "type" => "discussion",
                    ],
                    [
                        "id" => 2,
                        "confidence" => 0.6,
                        "type" => "question",
                    ],
                ],
            ]
        );
        $this->createTag([
            "name" => "tag",
            "fullName" => "tag",
        ]);

        $mockOpenAIClient->addMockResponse(
            "#Classify the input text by finding the best label based on a provided list.#",
            [
                "labels" => [
                    [
                        "id" => $this->lastInsertedTagID,
                        "label" => "tag",
                        "confidence" => 0.95,
                    ],
                ],
            ]
        );

        Gdn::getContainer()->setInstance(OpenAIClient::class, $mockOpenAIClient);
    }

    /**
     * Set up Nexus for the test.
     *
     * @return void
     */
    private function setUpNexus(): void
    {
        $url = getenv("NEXUS_AI_CONVERSATION_URL");
        $authUrl = getenv("NEXUS_AI_CONVERSATION_AUTH_URL");
        $clientID = getenv("NEXUS_CLIENT_ID");
        $clientSecret = getenv("NEXUS_CLIENT_SECRET");
        $model = getenv("NEXUS_MODEL");
        $siteID = getenv("NEXUS_SITE_ID");

        if ($url && $authUrl && $clientID && $clientSecret && $model && $siteID) {
            $ownSite = Gdn::getContainer()->getArgs(OwnSite::class);
            $ownSite->setSiteID($siteID);
            $this->model = $model;
            $this->config->saveToConfig([
                NexusAiConversationClient::NEXUS_AI_CONVERSATION_URL_CONFIG_KEY => $url,
                NexusAiConversationClient::NEXUS_AUTH_URL_CONFIG_KEY => $authUrl,
                NexusAiConversationClient::NEXUS_AI_CONVERSATION_CLIENT_ID_CONFIG_KEY => $clientID,
                NexusAiConversationClient::NEXUS_AI_CONVERSATION_CLIENT_SECRET_CONFIG_KEY => $clientSecret,
                NexusAiConversationClient::NEXUS_AI_CONVERSATION_MODEL_CONFIG_KEY => $model,
            ]);
        } else {
            $httpClient = $this->container()->get(MockHttpClient::class);
            $this->container()->setInstance(HttpClient::class, $httpClient);

            $client = $this->container()->get(NexusAiConversationMockClient::class);
            $this->container()->setInstance(NexusAiConversationClient::class, $client);
            $this->mockRun = true;
            $this->model = $client->getModel();

            // We re-use the same ID for the mock client so we need to reset the table.
            $this->resetTable("aiConversation");
            $this->resetTable("aiConversationMessage");
        }
    }
}
