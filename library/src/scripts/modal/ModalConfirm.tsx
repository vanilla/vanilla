/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import SmartAlign from "@library/layout/SmartAlign";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal, { MODAL_CONTAINER_ID } from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@library/utility/appUtils";
import { getRequiredID, uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";
import React from "react";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";

interface IProps {
    title: React.ReactNode; // required for accessibility
    srOnlyTitle?: boolean;
    className?: string;
    onCancel?: (e: Event) => void;
    onConfirm: (e: Event) => void;
    confirmTitle?: string;
    children: React.ReactNode;
    isConfirmLoading?: boolean;
    elementToFocusOnExit?: HTMLElement;
    size?: ModalSizes;
    isVisible: boolean;
}

/**
 * Basic confirm dialogue.
 */
export default class ModalConfirm extends React.Component<IProps> {
    private cancelRef = React.createRef<HTMLButtonElement>();
    private id = uniqueIDFromPrefix("confirmModal");

    public render() {
        const { onConfirm, srOnlyTitle, isConfirmLoading, title, children, size } = this.props;
        const onCancel = this.handleCancel;
        const classesFrameBody = frameBodyClasses();
        const classFrameFooter = frameFooterClasses();
        return (
            <Modal
                isVisible={this.props.isVisible}
                size={size ? size : ModalSizes.SMALL}
                elementToFocus={this.cancelRef.current as HTMLElement}
                exitHandler={onCancel}
                titleID={this.titleID}
                elementToFocusOnExit={this.props.elementToFocusOnExit}
            >
                <Frame
                    header={
                        <FrameHeader
                            titleID={this.titleID}
                            closeFrame={onCancel}
                            srOnlyTitle={srOnlyTitle!}
                            title={title}
                        />
                    }
                    body={
                        <FrameBody>
                            <SmartAlign className={classNames("frameBody-contents", classesFrameBody.contents)}>
                                {children}
                            </SmartAlign>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight={true}>
                            <Button
                                className={classFrameFooter.actionButton}
                                baseClass={ButtonTypes.TEXT}
                                buttonRef={this.cancelRef}
                                onClick={onCancel}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                className={classFrameFooter.actionButton}
                                onClick={onConfirm}
                                baseClass={ButtonTypes.TEXT_PRIMARY}
                                disabled={isConfirmLoading}
                            >
                                {isConfirmLoading ? <ButtonLoader /> : this.props.confirmTitle || t("OK")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </Modal>
        );
    }

    private handleCancel = e => {
        this.props.onCancel && this.props.onCancel(e);
    };

    public get titleID() {
        return this.id + "-title";
    }

    public componentDidMount() {
        this.forceUpdate();
    }
}
