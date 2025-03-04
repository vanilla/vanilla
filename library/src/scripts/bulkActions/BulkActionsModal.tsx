/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import BulkDeleteDiscussions from "@library/features/discussions/forms/BulkDeleteDiscussionsForm";
import { BulkMergeDiscussionsForm } from "@library/features/discussions/forms/BulkMergeDiscussionsForm";
import BulkMoveDiscussions from "@library/features/discussions/forms/BulkMoveDiscussionsForm";
import BulkCloseDiscussions from "@library/features/discussions/forms/BulkCloseDiscussionsForm";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useMemo } from "react";
import { BulkAction, IAdditionalBulkAction } from "@library/bulkActions/BulkActions.types";
import CommentsSplitBulkAction from "@vanilla/addon-vanilla/comments/bulkActions/CommentsSplitBulkAction";
import CommentsDeleteBulkAction from "@vanilla/addon-vanilla/comments/bulkActions/CommentsDeleteBulkAction";

interface IProps {
    /** The externally controlled visibility of the modal */
    visibility: boolean;
    /** Function to update parent component of visibility state changes */
    onVisibilityChange(visibility: boolean): void;
    /** The kind of bulk action that needs to be performed */
    bulkActionType: BulkAction | null;
    /** Either discussion or comment */
    recordType: "discussion" | "comment";
    /** Additional bulk actions */
    additionalBulkActions?: IAdditionalBulkAction[];
}

/**
 * This is a dynamic modal which will render all the bulk action form
 */
export function BulkActionsModal(props: IProps) {
    const { visibility, onVisibilityChange, bulkActionType, recordType, additionalBulkActions } = props;
    const Content = useMemo(() => {
        if (bulkActionType) {
            const defaultBulkActions = {
                delete: recordType === "comment" ? CommentsDeleteBulkAction : BulkDeleteDiscussions,
                move: BulkMoveDiscussions,
                merge: BulkMergeDiscussionsForm,
                close: BulkCloseDiscussions,
                split: CommentsSplitBulkAction,
            };
            const matchingAdditionalBulkAction = additionalBulkActions?.find(
                (bulkAction) => bulkAction.action === bulkActionType,
            );

            return defaultBulkActions[bulkActionType]
                ? defaultBulkActions[bulkActionType]
                : matchingAdditionalBulkAction
                ? matchingAdditionalBulkAction.contentRenderer
                : null;
        }
        return null;
    }, [bulkActionType]);

    const close = () => onVisibilityChange(false);

    return (
        <Modal isVisible={visibility} size={ModalSizes.MEDIUM} exitHandler={close}>
            {Content && <Content onCancel={close} onSuccess={close} />}
        </Modal>
    );
}
