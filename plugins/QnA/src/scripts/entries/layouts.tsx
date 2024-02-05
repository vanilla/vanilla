/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import CommentOptionsChangeStatus from "@QnA/components/CommentOptionsChangeStatus";
import { QnAStatus } from "@dashboard/@types/api/comment";
import { registerLoadableWidgets } from "@library/utility/componentRegistry";
import { addCommentOption } from "@vanilla/addon-vanilla/thread/CommentOptionsMenu";

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
