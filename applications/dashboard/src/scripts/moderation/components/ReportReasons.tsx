/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IReason } from "@dashboard/moderation/CommunityManagementTypes";
import { MetaTag } from "@library/metas/Metas";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import { ToolTip } from "@library/toolTip/ToolTip";

interface IProps {
    reasons: IReason[];
}
export function ReportReasons(props: IProps) {
    return (
        <>
            {props?.reasons?.map((reason) => (
                <ToolTip key={`${reason.reportID}-${reason.reportReasonID}`} label={reason.description}>
                    <MetaTag tagPreset={TagPreset.STANDARD}>{reason.name}</MetaTag>
                </ToolTip>
            ))}
        </>
    );
}
