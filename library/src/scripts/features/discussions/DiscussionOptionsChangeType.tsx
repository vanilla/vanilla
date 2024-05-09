/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent, ReactNode, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import ChangeTypeDiscussionForm from "@library/features/discussions/forms/ChangeTypeDiscussionForm";
import { StackingContextProvider } from "@vanilla/react-utils";

const DiscussionOptionsChangeType: FunctionComponent<{ discussion: IDiscussion; onSuccess?: () => Promise<void> }> = ({
    discussion,
    onSuccess,
}) => {
    const [isVisible, setIsVisible] = useState(false);
    const open = () => setIsVisible(true);
    const close = () => setIsVisible(false);

    async function handleSuccess() {
        !!onSuccess && (await onSuccess());
        close();
    }

    return (
        <>
            <DropDownItemButton onClick={open}>{t("Change Type")}</DropDownItemButton>
            <StackingContextProvider>
                <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={close}>
                    <ChangeTypeDiscussionForm discussion={discussion} onSuccess={handleSuccess} onCancel={close} />
                </Modal>
            </StackingContextProvider>
        </>
    );
};

export default DiscussionOptionsChangeType;
