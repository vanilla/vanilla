<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event for when a user accesses the churn export.
 */
class ExportAccessEvent extends BasicAuditLogEvent
{
    /**
     * @param int $siteID The site ID.
     * @param int $backupId The backup ID.
     * @param string $userName The user's name.
     * @param string $exportUrl The export URL.
     */
    public function __construct(int $siteID, int $backupId, string $exportUrl)
    {
        parent::__construct([
            "siteID" => $siteID,
            "backupID" => $backupId,
            "exportUrl" => $exportUrl,
        ]);
    }
    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "export_access";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        $last5Chars = substr($context["exportUrl"], -5);
        return "User generated a URL link for data export ending in - `{$last5Chars}`";
    }
}
