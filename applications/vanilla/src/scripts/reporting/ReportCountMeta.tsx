/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import SmartLink from "@library/routing/links/SmartLink";
import { commentThreadClasses } from "@vanilla/addon-vanilla/comments/CommentThread.classes";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { RecordID } from "@vanilla/utils";

export function ReportCountMeta(props: {
    countReports?: number;
    recordType: string;
    recordID: RecordID;
    classNames?: string;
}) {
    const { countReports, recordType, recordID, classNames } = props;

    const classesCommentThread = commentThreadClasses.useAsHook();
    if (countReports == null || countReports === 0) {
        return <></>;
    }
    return (
        <SmartLink
            target={"_blank"}
            className={cx(classesCommentThread.reportsTag, classNames)}
            to={`/dashboard/content/reports?statuses=none&recordType=${recordType}&recordID=${recordID}`}
        >
            <Tag className={classesCommentThread.reportsTag} preset={TagPreset.COLORED}>
                {countReports > 1 ? <Translate source={"<0/> Reports"} c0={countReports} /> : <>{t("1 Report")}</>}
                <Icon icon="meta-external-compact" />
            </Tag>
        </SmartLink>
    );
}
