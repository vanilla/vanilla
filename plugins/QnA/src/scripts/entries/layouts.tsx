/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import CommentOptionsChangeStatus from "@QnA/components/CommentOptionsChangeStatus";
import { QnAStatus } from "@dashboard/@types/api/comment";
import { PermissionMode } from "@library/features/users/Permission";
import { MetaTag } from "@library/metas/Metas";
import { TagPreset } from "@library/metas/Tags.variables";
import { registerLoadableWidgets } from "@library/utility/componentRegistry";
import { addCommentOption } from "@vanilla/addon-vanilla/comments/CommentOptionsMenu";
import { CommentsBulkActionsProvider } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActionsContext";
import { useContentItemContext } from "@vanilla/addon-vanilla/contentItem/ContentItemContext";
import { ContentItemHeader } from "@vanilla/addon-vanilla/contentItem/ContentItemHeader";
import { t } from "@vanilla/i18n";

registerLoadableWidgets({
    AnswerThreadAsset: () => import("@QnA/asset/AnswerThreadAsset"),
});

addCommentOption({
    shouldRender: (comment, hasPermission) => {
        return !!(
            [QnAStatus.ACCEPTED, QnAStatus.REJECTED].includes(comment.attributes?.answer?.status) &&
            (hasPermission("curation.manage") ||
                hasPermission("discussions.edit", {
                    resourceType: "category",
                    resourceID: comment.categoryID,
                    mode: PermissionMode.RESOURCE_IF_JUNCTION,
                }))
        );
    },
    component: CommentOptionsChangeStatus,
});

function QnaStatusTag() {
    const { attributes } = useContentItemContext();

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

ContentItemHeader.registerMetaItem(
    QnaStatusTag,
    function (context) {
        return context.recordType === "comment" && !!context.attributes?.answer?.status;
    },
    {
        placement: "metadata",
    },
);

CommentsBulkActionsProvider.registerPostType({ value: "question", label: "Question" });
