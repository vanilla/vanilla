<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\Web\RequestInterface;

/**
 * Interface to implement for an audit loggable event.
 */
interface AuditLogEventInterface
{
    /**
     * Given an event name and a context, determine if we can format a message for the event.
     *
     * @param string $eventType
     * @param array $context
     * @param array $meta
     *
     * @return bool
     */
    public static function canFormatAuditMessage(string $eventType, array $context, array $meta): bool;

    /**
     * Format a human-readable message for the event.
     *
     * @param string $eventType
     * @param array $context
     * @param array $meta
     *
     * @return string
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string;

    /**
     * Get a unique ID for this audit log.
     *
     * @return string
     */
    public function getAuditLogID(): string;

    /**
     * @return string
     */
    public function getAuditEventType(): string;

    /**
     * @return array
     */
    public function getAuditContext(): array;

    /**
     * @return RequestInterface
     */
    public function getAuditRequest(): RequestInterface;

    /**
     * @return array
     */
    public function getAuditMeta(): array;

    /**
     * Return the userID of the user who performed the action.
     *
     * @return int
     */
    public function getSessionUserID(): int;

    /**
     * Get the username of the user performing the event.
     *
     * @return string
     */
    public function getSessionUsername(): string;
}
