/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { t } from "@vanilla/i18n";

export enum ReportStatus {
    NEW = "new",
    DISMISSED = "dismissed",
    ESCALATED = "escalated",
    REJECTED = "rejected",
}

const ReportStatusLabels = {
    [ReportStatus.NEW]: "New",
    [ReportStatus.DISMISSED]: "Report Dismissed",
    [ReportStatus.ESCALATED]: "Escalated",
    [ReportStatus.REJECTED]: "Post Rejected",
};

export function reportStatusLabel(status: string): string {
    return t(ReportStatusLabels[status as ReportStatus] ?? status);
}
