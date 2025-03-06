/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { t } from "@vanilla/i18n";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import DeleteCommentsForm from "@vanilla/addon-vanilla/comments/DeleteCommentsForm";

interface IProps {
    isVisible: boolean;
    commentID: IComment["commentID"];
    onCancel: () => void;
    onMutateSuccess?: () => Promise<void>;
}

export function DeleteCommentModal(props: IProps) {
    const { isVisible, commentID, onMutateSuccess, onCancel } = props;

    return (
        <Modal isVisible={isVisible} exitHandler={onCancel} size={ModalSizes.MEDIUM} titleID={"delete_comment_Modal"}>
            <DeleteCommentsForm
                title={t("Delete Comment")}
                description={t("Are you sure you want to delete this comment?")}
                close={onCancel}
                onMutateSuccess={onMutateSuccess}
                commentIDs={[commentID]}
                successMessage={t("Comment Deleted")}
            />
        </Modal>
    );
}
