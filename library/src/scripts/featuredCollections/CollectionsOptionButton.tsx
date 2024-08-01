/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { StackingContextProvider } from "@vanilla/react-utils";
import { CollectionsForm } from "@library/featuredCollections/CollectionsForm";
import { ICollectionResource } from "@library/featuredCollections/Collections.variables";

interface IProps extends ICollectionResource {
    modalOnly?: boolean;
    initialVisibility?: boolean;
}

export function CollectionsOptionButton(props: IProps) {
    const [isVisible, setIsVisible] = useState(props.initialVisibility || false);
    const open = () => setIsVisible(true);
    const close = () => setIsVisible(false);

    return (
        <>
            {!props.modalOnly && <DropDownItemButton onClick={open}>{t("Add to Collection")}</DropDownItemButton>}
            <StackingContextProvider>
                <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={close}>
                    <CollectionsForm {...props} onSuccess={close} onCancel={close} />
                </Modal>
            </StackingContextProvider>
        </>
    );
}
