/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import React from "react";

interface IProps {
    isVisible: boolean;
    onClose: () => void;
    footerActions: React.ReactNode;
    form: React.ReactNode;
}

export function FormTreeRowEditModal(props: IProps) {
    return (
        <Modal isVisible={props.isVisible} size={ModalSizes.MEDIUM} exitHandler={props.onClose}>
            <Frame
                header={<FrameHeader closeFrame={props.onClose} title={t("Edit")} />}
                body={<FrameBody hasVerticalPadding>{props.form}</FrameBody>}
                footer={<FrameFooter justifyRight>{props.footerActions}</FrameFooter>}
            />
        </Modal>
    );
}
