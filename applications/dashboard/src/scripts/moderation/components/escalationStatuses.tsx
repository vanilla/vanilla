/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Notice from "@library/metas/Notice";
import { getMeta } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n";

type IEscalationStatuses = Record<string, string>;

export function getEscalationStatuses(): IEscalationStatuses {
    const statuses: IEscalationStatuses = getMeta("escalation.statuses", {
        open: "Open",
        "in-progress": "In Progress",
        "on-hold": "On Hold",
        done: "Done",
    });

    for (const [id, labelCode] of Object.entries(statuses)) {
        statuses[id] = t(labelCode);
    }
    return statuses;
}

export function EscalationStatus(props: { status: string }) {
    const label = getEscalationStatuses()[props.status] ?? props.status;
    return <Notice>{label}</Notice>;
}
