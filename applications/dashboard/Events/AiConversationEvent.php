<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Psr\Log\LogLevel;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Analytics\TrackableUserModel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggerUtils;
use Vanilla\Utility\ArrayUtils;

/**
 * Class representing an AI conversation event.
 */
class AiConversationEvent extends ResourceEvent implements TrackingEventInterface
{
    const COLLECTION_NAME = "aiConversation";

    // AI Conversation specific actions
    const ACTION_SEARCH_SUMMARY_GENERATED = "searchSummaryGenerated";
    const ACTION_CONVERSATION_STARTED = "conversationStarted";
    const ACTION_USER_MESSAGE_SENT = "userMessageSent";
    const ACTION_AI_RESPONSE_GENERATED = "aiResponseGenerated";
    const ACTION_AI_RESPONSE_RATED = "aiResponseRated";
    const ACTION_USER_FEEDBACK = "userFeedback";
    const ACTION_USER_COPIED = "userCopied";
    const ACTION_AI_ASK_COMMUNITY = "aiAskCommunity";
    const ACTION_SOURCE_CLICKED = "sourceClicked";
    const SOURCE_CHAT = "chat";
    const SOURCE_SEARCH = "search";

    /**
     * D.I.
     *
     * @param string $action
     * @param array $payload
     * @param $sender
     */
    public function __construct(string $action, array $payload, $sender = null)
    {
        // Remove the body.
        if (isset($payload["message"])) {
            $payload["message"]["body"] = null;
        }

        parent::__construct($action, $payload, $sender);
    }

    /**
     * @inheritdoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getTrackablePayload(TrackableUserModel $trackableUserModel): array
    {
        $payload = $this->payload;
        $payload["user"] = $trackableUserModel->getTrackableUser($payload["aiConversation"]["insertUserID"]);
        return $payload;
    }
}
