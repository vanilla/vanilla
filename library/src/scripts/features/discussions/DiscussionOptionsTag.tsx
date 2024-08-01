/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { FunctionComponent, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import TagDiscussionForm from "@library/features/discussions/forms/TagDiscussionForm";
import { css } from "@emotion/css";

export const DiscussionOptionsTag: FunctionComponent<{ discussion: IDiscussion; onSuccess?: () => Promise<void> }> = ({
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
    const visibleOverrideForSuggestions = css({
        "&&": {
            overflow: "visible",
        },
    });

    return (
        <>
            <DropDownItemButton onClick={open}>{t("Tag")}</DropDownItemButton>
            <Modal
                isVisible={isVisible}
                size={ModalSizes.MEDIUM}
                exitHandler={close}
                className={visibleOverrideForSuggestions}
            >
                <TagDiscussionForm discussion={discussion} onSuccess={handleSuccess} onCancel={close} />
            </Modal>
        </>
    );
};
