<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

/**
 * Interface for an audit event with a parent.
 *
 * The logger will ensure the parent event is created and this is a child of it.
 */
interface AuditEventWithParentInterface extends AuditLogEventInterface
{
    /**
     * @return AuditLogEventInterface
     */
    public function getParentAuditEvent(): AuditLogEventInterface;
}
