/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { FunctionComponent, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { getMeta, t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import MoveDiscussionForm from "@library/features/discussions/forms/MoveDiscussionForm.loadable";
import { StackingContextProvider } from "@vanilla/react-utils";
import MovePostForm from "@library/features/discussions/forms/MovePostForm";

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

    const POST_TYPES_ENABLED = getMeta("featureFlags.customLayout.createPost.Enabled", false);

    return (
        <>
            <DropDownItemButton onClick={open}>{t("Move")}</DropDownItemButton>
            <StackingContextProvider>
                <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={close}>
                    {POST_TYPES_ENABLED ? (
                        <MovePostForm discussion={discussion} onSuccess={handleSuccess} onCancel={close} />
                    ) : (
                        <MoveDiscussionForm discussion={discussion} onSuccess={handleSuccess} onCancel={close} />
                    )}
                </Modal>
            </StackingContextProvider>
        </>
    );
};

export default DiscussionOptionsMove;
