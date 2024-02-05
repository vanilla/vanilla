/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { IComment, QnAStatus } from "@dashboard/@types/api/comment";
import DateTime from "@library/content/DateTime";
import { MetaLink, MetaTag } from "@library/metas/Metas";
import { t } from "@vanilla/i18n";
import { TagPreset } from "@library/metas/Tags.variables";

interface IProps {
    comment: IComment;
}

export default function CommentMeta(props: IProps) {
    const { comment } = props;

    let qnaTag: ReactNode | undefined = undefined;
    const qnaStatus: QnAStatus | undefined = comment.attributes?.answer?.status;

    switch (qnaStatus) {
        case QnAStatus.ACCEPTED:
            qnaTag = <MetaTag tagPreset={TagPreset.SUCCESS}>{t("Accepted Answer")}</MetaTag>;
            break;
        case QnAStatus.REJECTED:
            qnaTag = <MetaTag>{t("Rejected Answer")}</MetaTag>;
            break;
        default:
            qnaTag = undefined;
    }

    const permaLink = (
        <MetaLink to={comment.url}>
            <DateTime timestamp={comment.dateInserted} />
        </MetaLink>
    );

    return (
        <>
            {qnaTag}
            {permaLink}
        </>
    );
}
