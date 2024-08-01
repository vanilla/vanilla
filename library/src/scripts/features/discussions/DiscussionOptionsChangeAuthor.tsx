/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import ChangeAuthorForm from "@library/features/discussions/forms/ChangeAuthorForm";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { useState } from "react";

interface IProps {
    discussion: IDiscussion;
    onSuccess?: () => Promise<void>;
}

export function DiscussionOptionsChangeAuthor(props: IProps) {
    const [isVisible, setIsVisible] = useState(false);
    const open = () => setIsVisible(true);
    const close = () => setIsVisible(false);

    async function handleSuccess() {
        props.onSuccess && (await props.onSuccess());
        close();
    }

    return (
        <>
            <DropDownItemButton onClick={open}>{t("Change Author")}</DropDownItemButton>
            <Modal
                isVisible={isVisible}
                size={ModalSizes.MEDIUM}
                exitHandler={() => close()}
                className={css({ overflow: "visible!important" })} // Fighting with modal styles here to make dropdown suggestions visible
            >
                <ChangeAuthorForm discussion={props.discussion} onCancel={close} onSuccess={handleSuccess} />
            </Modal>
        </>
    );
}
