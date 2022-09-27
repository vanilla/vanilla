/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import LazyAnnounceDiscussionForm from "@library/features/discussions/forms/LazyAnnounceDiscussionForm";
import { StackingContextProvider } from "@vanilla/react-utils";

const DiscussionOptionsAnnounce: FunctionComponent<{ discussion: IDiscussion }> = ({ discussion }) => {
    const [isVisible, setIsVisible] = useState(false);
    const open = () => setIsVisible(true);
    const close = () => setIsVisible(false);

    return (
        <>
            <DropDownItemButton onClick={open}>{t("Announce")}</DropDownItemButton>
            <StackingContextProvider>
                <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={close}>
                    <LazyAnnounceDiscussionForm discussion={discussion} onSuccess={close} onCancel={close} />
                </Modal>
            </StackingContextProvider>
        </>
    );
};

export default DiscussionOptionsAnnounce;
