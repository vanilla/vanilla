/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import ModalSizes from "@library/modal/ModalSizes";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import ModalConfirm from "@library/modal/ModalConfirm";
import { useSelector } from "react-redux";
import { IDiscussionsStoreState } from "./discussionsReducer";
import { useDiscussionActions } from "./DiscussionActions";
import { LoadStatus } from "@library/@types/api/core";
import { StackingContextProvider } from "@vanilla/react-utils";

const DiscussionOptionsDelete: FunctionComponent<{ discussion: IDiscussion }> = ({ discussion }) => {
    const [isVisible, setIsVisible] = useState(false);
    const open = () => setIsVisible(true);
    const close = () => setIsVisible(false);
    const actions = useDiscussionActions();

    const deleteStatus = useSelector((storeState: IDiscussionsStoreState) => {
        return (
            storeState.discussions.deleteStatusesByID[discussion.discussionID] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    const { status } = deleteStatus;

    const handleDeleteConfirm = () => {
        actions.deleteDiscussion({ discussionID: discussion.discussionID });
        close();
    };

    return (
        <>
            <DropDownItemButton name={t("Delete")} onClick={open}>
                {t("Delete")}
            </DropDownItemButton>
            <StackingContextProvider>
                <ModalConfirm
                    isVisible={isVisible}
                    size={ModalSizes.MEDIUM}
                    title={t("Delete Discussion")}
                    onCancel={close}
                    onConfirm={handleDeleteConfirm}
                    isConfirmLoading={status === LoadStatus.LOADING}
                    elementToFocusOnExit={document.activeElement as HTMLElement}
                >
                    {t("Are you sure you want to delete this discussion?")}
                </ModalConfirm>
            </StackingContextProvider>
        </>
    );
};

export default DiscussionOptionsDelete;
