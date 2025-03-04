<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;
use Psr\Log\LogLevel;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;
use Vanilla\Utility\ArrayUtils;

/**
 * Represent a subscription resource event.
 */
class SubscriptionChangeEvent extends ResourceEvent implements LoggableEventInterface, TrackingEventInterface
{
    const COLLECTION_NAME = "subscriptionChange";
    const ACTION_FOLLOW = "category_follow";
    const ACTION_UNFOLLOW = "category_unfollow";
    const ACTION_DIGEST_ENABLED = "digest_subscribe";
    const ACTION_DIGEST_DISABLED = "digest_unsubscribe";

    const ACTION_DIGEST_AUTO_SUBSCRIBE = "digest_auto_subscribe";

    /**
     * DiscussionEvent constructor.
     *
     * @param string $action
     * @param array $payload
     * @param array|object|null $sender
     */
    public function __construct(string $action, array $payload, $sender = null)
    {
        parent::__construct($action, $payload, $sender);
    }

    /**
     * Get the name of the collection this resource event belongs to.
     *
     * @return string
     */
    public function getCollectionName(): string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getPayload(): array
    {
        //We need to override the current payload with trackable payload
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context[self::COLLECTION_NAME] = ArrayUtils::pluck($this->payload[self::COLLECTION_NAME] ?? [], [
            "type",
            "category",
            "user",
            "preferences",
        ]);

        return new LogEntry(LogLevel::INFO, LoggerUtils::resourceEventLogMessage($this), $context);
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * Get event data needed for tracking.
     *
     * @param TrackableCommunityModel $trackableCommunity
     *
     * @return array
     */
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunity): array
    {
        if (
            in_array($this->action, [
                self::ACTION_DIGEST_ENABLED,
                self::ACTION_DIGEST_DISABLED,
                self::ACTION_DIGEST_AUTO_SUBSCRIBE,
            ])
        ) {
            return $this->payload[self::COLLECTION_NAME];
        }
        return $trackableCommunity->getTrackableCategorySubscription($this->payload[self::COLLECTION_NAME]);
    }
}
