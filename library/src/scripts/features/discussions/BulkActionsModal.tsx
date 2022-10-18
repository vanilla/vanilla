/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import BulkDeleteDiscussions from "@library/features/discussions/forms/BulkDeleteDiscussionsForm";
import { BulkMergeDiscussionsForm } from "@library/features/discussions/forms/BulkMergeDiscussionsForm";
import BulkMoveDiscussions from "@library/features/discussions/forms/BulkMoveDiscussionsForm";
import BulkCloseDiscussions from "@library/features/discussions/forms/BulkCloseDiscussionsForm";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import React, { useMemo } from "react";

export enum BulkActionTypes {
    DELETE = "delete",
    MERGE = "merge",
    MOVE = "move",
    CLOSE = "close",
}

export interface IBulkActionForm {
    onCancel(): void;
}

interface IProps {
    /** The externally controlled visibility of the modal */
    visibility: boolean;
    /** Function to update parent component of visibility state changes */
    onVisibilityChange(visibility: boolean): void;
    /** The kind of bulk action that needs to be performed */
    bulkActionType: BulkActionTypes | null;
}

/**
 * This is a dynamic modal which will render all the bulk action form
 */
export function BulkActionsModal(props: IProps) {
    const { visibility, onVisibilityChange, bulkActionType } = props;
    const Content = useMemo(() => {
        if (bulkActionType) {
            return (
                {
                    delete: BulkDeleteDiscussions,
                    move: BulkMoveDiscussions,
                    merge: BulkMergeDiscussionsForm,
                    close: BulkCloseDiscussions,
                }[bulkActionType] ?? null
            );
        }
        return null;
    }, [bulkActionType]);

    const close = () => onVisibilityChange(false);

    return (
        <Modal isVisible={visibility} size={ModalSizes.MEDIUM} exitHandler={close}>
            {Content && <Content onCancel={close} />}
        </Modal>
    );
}
