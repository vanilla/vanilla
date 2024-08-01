<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\StaticCacheConfigTrait;
use Psr\Log\LoggerInterface;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Logger;

/**
 * Utilities for working with audit logs.
 */
class AuditLogger
{
    use StaticCacheConfigTrait;
    public const CONF_ENABLED = "auditLog.enabled";
    public const CONF_RETENTION = "auditLog.retentionTime";

    /**
     * Log and audit log event.
     * Logs to central logging and the GDN_auditLog table.
     *
     * @param AuditLogEventInterface $event
     *
     * @return string
     */
    public static function log(AuditLogEventInterface $event): string
    {
        if (self::c("auditLog.enabled", false) === false) {
            return "";
        }
        $logLevel = Logger::INFO;
        $context = $event->getAuditContext();
        $eventType = $event->getAuditEventType();
        $message = $event::formatAuditMessage($eventType, $context, $event->getAuditMeta());
        if ($event instanceof LoggableEventInterface) {
            $logLevel = $event->getLogEntry()->getLevel();
        }

        // Log to the central logging system.
        self::getLogger()->log(
            $logLevel,
            $message,
            array_merge($context, [
                Logger::FIELD_EVENT => $event->getAuditEventType(),
                Logger::FIELD_CHANNEL => Logger::CHANNEL_AUDIT,
                Logger::FIELD_USERID => $event->getSessionUserID(),
                Logger::FIELD_USERNAME => $event->getSessionUsername(),
            ])
        );

        $model = self::getModel();
        try {
            // If we have a parent event, make sure it exists.
            if ($event instanceof AuditEventWithParentInterface) {
                $parentEvent = $event->getParentAuditEvent();

                try {
                    $existingParentEvent = $model->selectSingle([
                        "auditLogID" => $event->getParentAuditEvent()->getAuditLogID(),
                    ]);
                } catch (NoResultsException $e) {
                    // That's ok, we'll create it.
                    // This is potentially racy and could cause the event to get logged twice. No big deal.
                    $model->add($parentEvent);
                    // Now make sure we log it since it's the first time.
                    self::log($parentEvent);
                }

                // Add as a sub event to the parent event.
                $model->pushChildEvent($parentEvent->getAuditLogID(), $event);
                return $parentEvent->getAuditLogID();
            } else {
                return $model->add($event);
            }

            // Insert into the table.
        } catch (\Throwable $e) {
            // Totally possible that something will try to log before we've created the table.
            ErrorLogger::warning($e, ["auditLog"]);
            return "";
        }
    }

    /**
     * Format an audit log event as an array.
     *
     * @param AuditLogEventInterface $auditLogEvent
     *
     * @return array
     */
    public static function auditLogEventToArray(AuditLogEventInterface $auditLogEvent): array
    {
        $request = $auditLogEvent->getAuditRequest();
        $requestMethod = $request->getMethod();
        $requestPath = $request->getUri()->getPath();

        $insert = [
            "auditLogID" => $auditLogEvent->getAuditLogID(),
            "eventType" => $auditLogEvent->getAuditEventType(),
            "requestMethod" => $requestMethod,
            "requestPath" => substr($requestPath, 0, 200),
            "requestQuery" => $request->getQuery(),
            "context" => $auditLogEvent->getAuditContext(),
            "meta" => $auditLogEvent->getAuditMeta(),
        ];
        return $insert;
    }

    ///
    /// Utilities
    ///

    /**
     * @return AuditLogModel
     */
    private static function getModel(): AuditLogModel
    {
        return \Gdn::getContainer()->get(AuditLogModel::class);
    }

    /**
     * @return LoggerInterface
     */
    private static function getLogger(): LoggerInterface
    {
        return \Gdn::getContainer()->get(LoggerInterface::class);
    }
}
