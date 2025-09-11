<?php

namespace VanillaTests\APIv2;

use Gdn;
use VanillaTests\Http\TestHttpClient;

/**
 * Trait used to test private conversations.
 *
 * @method TestHttpClient api()
 */
trait ConversationApiTestTrait
{
    protected int|null $lastConversationID = null;

    protected int|null $lastMessageID = null;

    /**
     * Start a new conversation.
     *
     * @param array $overrides
     * @return array
     */
    protected function createConversation(array $overrides = []): array
    {
        $body = $overrides + [
            "participantUserIDs" => [Gdn::session()->UserID],
            "initialBody" => "Hello **world**",
            "initialFormat" => "markdown",
        ];

        $result = $this->api()
            ->post("/conversations", $body)
            ->getBody();

        $this->lastConversationID = $result["conversationID"];

        return $result;
    }

    /**
     * Create a new message.
     *
     * @param array $overrides
     * @return array
     */
    protected function createMessage(array $overrides = []): array
    {
        $body = $overrides + [
            "conversationID" => $this->lastConversationID,
            "body" => "Hello **world**",
            "format" => "markdown",
        ];

        $result = $this->api()
            ->post("/messages", $body)
            ->getBody();

        $this->lastMessageID = $result["messageID"];

        return $result;
    }
}
