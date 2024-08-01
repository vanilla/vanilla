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
 * Event for when a user accesses the dashboard.
 */
class DashboardAccessEvent extends BasicAuditLogEvent
{
    /** @var RequestInterface|null */
    private ?RequestInterface $overrideRequest = null;

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "dashboard_access";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "User accessed the dashboard.";
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
