<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Web\RequestInterface;
use Vanilla\Logging\AuditEventWithParentInterface;
use Vanilla\Logging\AuditLogEventInterface;
use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event for when a user accesses the dashboard.
 */
class DashboardApiAccessEvent extends BasicAuditLogEvent implements AuditEventWithParentInterface
{
    private DashboardAccessEvent $parentEvent;

    /**
     * Constructor.
     */
    public function __construct(DashboardAccessEvent $parentEvent)
    {
        parent::__construct();
        $this->parentEvent = $parentEvent;
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "dashboard_api_access";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "Dashboard Access (API)";
    }

    /**
     * @inheritDoc
     */
    public function getParentAuditEvent(): AuditLogEventInterface
    {
        return $this->parentEvent;
    }

    /**
     * Try to construct a dashboard API access event from headers.
     * This only occurs if a parent request is generated.
     *
     * @param RequestInterface $request
     *
     * @return DashboardApiAccessEvent |null
     */
    public static function tryFromHeaders(RequestInterface $request): ?DashboardApiAccessEvent
    {
        $auditLogID = $request->getHeader("X-Parent-Audit-Log-Id");
        $requestPath = $request->getHeader("X-Parent-Audit-Log-Request-Path");
        $requestMethod = $request->getHeader("X-Parent-Audit-Log-Request-Method");
        $requestQuery = $request->getHeader("X-Parent-Audit-Log-Request-Query");

        if ($auditLogID === "" || $requestPath === "" || $requestMethod === "") {
            return null;
        }

        $query = empty($requestQuery) ? new \ArrayObject() : json_decode($requestQuery, true);

        $request = new \Gdn_Request();
        $request->setMethod($requestMethod);
        $request->setPath($requestPath);
        $request->setQuery($query);

        $parentEvent = new DashboardAccessEvent();
        $parentEvent->setAuditLogID($auditLogID);
        $parentEvent->overrideRequest($request);

        $event = new DashboardApiAccessEvent($parentEvent);

        return $event;
    }
}
