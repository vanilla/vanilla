/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Translate from "@library/content/Translate";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import SmartLink from "@library/routing/links/SmartLink";
import { discussionThreadClasses } from "@vanilla/addon-vanilla/thread/DiscussionThread.classes";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { RecordID } from "@vanilla/utils";

export function ReportCountMeta(props: { countReports?: number; recordType: string; recordID: RecordID }) {
    const { countReports, recordType, recordID } = props;

    if (countReports == null || countReports === 0) {
        return <></>;
    }
    return (
        <SmartLink className={discussionThreadClasses().reportsTag} to={`/dashboard/content/triage/${recordID}`}>
            <Tag className={discussionThreadClasses().reportsTag} preset={TagPreset.COLORED}>
                {countReports > 1 ? <Translate source={"<0/> Reports"} c0={countReports} /> : <>{t("1 Report")}</>}
                <Icon icon="meta-external-compact" />
            </Tag>
        </SmartLink>
    );
}
