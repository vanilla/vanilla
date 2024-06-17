<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Web\RequestInterface;
use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event for when a user accesses the AI Suggestions.
 */
class AiSuggestionAccessEvent extends BasicAuditLogEvent
{
    /** @var RequestInterface|null */
    private ?RequestInterface $overrideRequest = null;

    private string $eventType;
    /**
     * @param string $eventType event type
     * @param array $requestData request data
     */
    public function __construct(string $eventType, array $requestData)
    {
        parent::__construct($requestData);
        $this->eventType = $eventType;
    }

    /**
     * @inheritDoc
     */
    public function getAuditEventType(): string
    {
        return static::eventType() . $this->eventType;
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "aiSuggestion_";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "User used the ai Suggestions.";
    }

    /**
     * @inheritDoc
     */
    public function getAuditContext(): array
    {
        return $this->context + [];
    }

    /**
     * @param RequestInterface $overrideRequest
     * @return void
     */
    public function overrideRequest(RequestInterface $overrideRequest): void
    {
        $this->overrideRequest = $overrideRequest;
    }

    /**
     * @return RequestInterface
     */
    public function getAuditRequest(): RequestInterface
    {
        return $this->overrideRequest ?? \Gdn::request();
    }
}
