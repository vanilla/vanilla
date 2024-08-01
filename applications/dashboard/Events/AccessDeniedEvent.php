<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Exception\PermissionException;
use Vanilla\Logging\AuditLogger;
use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Audit log event for when a user doesn't have permission to access something.
 */
class AccessDeniedEvent extends BasicAuditLogEvent
{
    /**
     * @param string|string[] $permission
     * @throws \Exception
     */
    public function __construct($permission)
    {
        parent::__construct([
            "missingPermissions" => (array) $permission,
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "access_denied";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "User denied access because they lack one or more permissions";
    }

    /**
     * Try to log an exception as an access denied.
     *
     * @param \Throwable $throwable
     * @return void
     * @throws \Exception
     */
    public static function tryLog(\Throwable $throwable): void
    {
        $auditEx = $throwable;
        if ($auditEx instanceof \Gdn_UserException && $auditEx->getPrevious() instanceof PermissionException) {
            $auditEx = $auditEx->getPrevious();
        }

        if (
            $auditEx instanceof PermissionException &&
            // Only log permission exceptions if the user is logged in.
            $auditEx->getPermission() !== "Garden.SignIn.Allow" &&
            \Gdn::session()->isValid()
        ) {
            $event = new AccessDeniedEvent($auditEx->getPermission());
            AuditLogger::log($event);
        }
    }
}
