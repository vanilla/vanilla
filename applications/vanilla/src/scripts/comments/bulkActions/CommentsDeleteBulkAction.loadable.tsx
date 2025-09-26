/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CommentDeleteMethod } from "@dashboard/@types/api/comment";
import { IBulkActionForm } from "@library/bulkActions/BulkActions.types";
import { useCommentsBulkActionsContext } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActionsContext";
import DeleteCommentsForm from "@vanilla/addon-vanilla/comments/DeleteCommentsForm";

export default function CommentsDeleteBulkAction(props: IBulkActionForm) {
    const { checkedCommentIDs, handleMutateSuccess } = useCommentsBulkActionsContext();

    return (
        <DeleteCommentsForm
            close={props.onCancel}
            onMutateSuccess={async (deleteMethod?: CommentDeleteMethod) => {
                props.onSuccess();
                await handleMutateSuccess(deleteMethod);
            }}
            commentIDs={checkedCommentIDs}
        />
    );
}
