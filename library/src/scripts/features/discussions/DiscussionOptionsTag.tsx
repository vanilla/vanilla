/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { FunctionComponent, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import TagDiscussionForm from "@library/features/discussions/forms/TagDiscussionForm";
import { cx, css } from "@emotion/css";

export const DiscussionOptionsTag: FunctionComponent<{ discussion: IDiscussion }> = ({ discussion }) => {
    const [isVisible, setIsVisible] = useState(false);
    const open = () => setIsVisible(true);
    const close = () => setIsVisible(false);
    const visibleOverrideForSuggestions = css(`
        overflow: visible!important
    `);

    return (
        <>
            <DropDownItemButton onClick={open}>{t("Tag")}</DropDownItemButton>
            <Modal
                isVisible={isVisible}
                size={ModalSizes.MEDIUM}
                exitHandler={close}
                className={visibleOverrideForSuggestions}
            >
                <TagDiscussionForm discussion={discussion} onSuccess={close} onCancel={close} />
            </Modal>
        </>
    );
};
