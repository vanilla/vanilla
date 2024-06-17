<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

/**
 * Abstract audit log event adding
 */
abstract class BasicAuditLogEvent implements AuditLogEventInterface
{
    use BasicAuditLogTrait;
}
