/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { FunctionComponent, ReactNode, useState } from "react";
import { t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { StackingContextProvider } from "@vanilla/react-utils";
import { IComment } from "@dashboard/@types/api/comment";
import ChangeQnaStatusForm from "@QnA/components/ChangeQnaStatusForm";

const CommentOptionsChangeStatus: FunctionComponent<{ comment: IComment; onSuccess?: () => Promise<void> }> = ({
    comment,
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
            <DropDownItemButton onClick={open}>{t("Change Status")}</DropDownItemButton>
            <StackingContextProvider>
                <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={close}>
                    <ChangeQnaStatusForm comment={comment} onSuccess={handleSuccess} onCancel={close} />
                </Modal>
            </StackingContextProvider>
        </>
    );
};

export default CommentOptionsChangeStatus;
