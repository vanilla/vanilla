/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import CommentOptionsChangeStatus from "@QnA/components/CommentOptionsChangeStatus";
import { QnAStatus } from "@dashboard/@types/api/comment";
import { MetaTag } from "@library/metas/Metas";
import { TagPreset } from "@library/metas/Tags.variables";
import { registerLoadableWidgets } from "@library/utility/componentRegistry";
import { addCommentOption } from "@vanilla/addon-vanilla/thread/CommentOptionsMenu";
import { useThreadItemContext } from "@vanilla/addon-vanilla/thread/ThreadItemContext";
import { ThreadItemHeader } from "@vanilla/addon-vanilla/thread/ThreadItemHeader";
import { t } from "@vanilla/i18n";

registerLoadableWidgets({
    TabbedCommentListAsset: () =>
        import(/* webpackChunkName: "widgets/TabbedCommentListAsset" */ "@QnA/asset/TabbedCommentListAsset"),
});

addCommentOption({
    shouldRender: (comment, hasPermission) => {
        return !!(
            [QnAStatus.ACCEPTED, QnAStatus.REJECTED].includes(comment.attributes?.answer?.status) &&
            hasPermission("curation.manage")
        );
    },
    component: CommentOptionsChangeStatus,
});

function QnaStatusTag() {
    const { attributes } = useThreadItemContext();

    const qnaStatus: QnAStatus = attributes?.answer?.status;
    switch (qnaStatus) {
        case QnAStatus.ACCEPTED:
            return <MetaTag tagPreset={TagPreset.SUCCESS}>{t("Accepted Answer")}</MetaTag>;
        case QnAStatus.REJECTED:
            return <MetaTag>{t("Rejected Answer")}</MetaTag>;
        default:
            return null;
    }
}

ThreadItemHeader.registerMetaItem(
    QnaStatusTag,
    function (context) {
        return context.recordType === "comment" && !!context.attributes?.answer?.status;
    },
    {
        placement: "metadata",
    },
);
