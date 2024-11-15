/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { FunctionComponent, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import LazyMoveDiscussionForm from "@library/features/discussions/forms/LazyMoveDiscussionForm";
import { StackingContextProvider } from "@vanilla/react-utils";

const DiscussionOptionsMove: FunctionComponent<{
    discussion: IDiscussion;
    onSuccess?: () => Promise<void>;
}> = ({ discussion, onSuccess }) => {
    const [isVisible, setIsVisible] = useState(false);
    const open = () => setIsVisible(true);
    const close = () => setIsVisible(false);

    async function handleSuccess() {
        !!onSuccess && (await onSuccess());
        close();
    }

    return (
        <>
            <DropDownItemButton onClick={open}>{t("Move")}</DropDownItemButton>
            <StackingContextProvider>
                <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={close}>
                    <LazyMoveDiscussionForm discussion={discussion} onSuccess={handleSuccess} onCancel={close} />
                </Modal>
            </StackingContextProvider>
        </>
    );
};

export default DiscussionOptionsMove;
