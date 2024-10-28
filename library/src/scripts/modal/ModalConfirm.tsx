/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import SmartAlign from "@library/layout/SmartAlign";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import React from "react";

interface IProps {
    title: React.ReactNode; // required for accessibility
    srOnlyTitle?: boolean;
    className?: string;
    onCancel?: (e: React.SyntheticEvent) => void;
    onConfirm?: (e: React.SyntheticEvent) => void;
    confirmLinkTo?: string;
    confirmTitle?: React.ReactNode;
    cancelTitle?: string;
    children: React.ReactNode;
    isConfirmLoading?: boolean;
    isConfirmDisabled?: boolean;
    elementToFocusOnExit?: HTMLElement;
    size?: ModalSizes;
    isVisible: boolean;
    fullWidthContent?: boolean;
    bodyClassName?: string;
    confirmClasses?: string;
}

/**
 * Basic confirm dialogue.
 */
export default class ModalConfirm extends React.Component<IProps> {
    private cancelRef = React.createRef<HTMLButtonElement>();
    private id = uniqueIDFromPrefix("confirmModal");

    public render() {
        const {
            onConfirm,
            confirmLinkTo,
            srOnlyTitle,
            isConfirmLoading,
            isConfirmDisabled,
            title,
            children,
            size,
            fullWidthContent,
        } = this.props;
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
                            {fullWidthContent ? (
                                <div className={cx(classesFrameBody.contents, this.props.bodyClassName)}>
                                    {children}
                                </div>
                            ) : (
                                <SmartAlign className={cx(classesFrameBody.contents, this.props.bodyClassName)}>
                                    {children}
                                </SmartAlign>
                            )}
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight={true}>
                            <Button
                                className={classFrameFooter.actionButton}
                                buttonType={ButtonTypes.TEXT}
                                buttonRef={this.cancelRef}
                                onClick={onCancel}
                            >
                                {this.props.cancelTitle ?? t("Cancel")}
                            </Button>
                            {!!onConfirm && (
                                <Button
                                    className={cx(classFrameFooter.actionButton, this.props.confirmClasses)}
                                    onClick={onConfirm}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                    disabled={isConfirmLoading || isConfirmDisabled}
                                >
                                    {isConfirmLoading ? <ButtonLoader /> : this.props.confirmTitle || t("OK")}
                                </Button>
                            )}
                            {!!confirmLinkTo && (
                                <LinkAsButton
                                    className={cx(classFrameFooter.actionButton, this.props.confirmClasses)}
                                    to={confirmLinkTo}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                    disabled={isConfirmLoading || isConfirmDisabled}
                                >
                                    {this.props.confirmTitle || t("OK")}
                                </LinkAsButton>
                            )}
                        </FrameFooter>
                    }
                />
            </Modal>
        );
    }

    private handleCancel = (e) => {
        this.props.onCancel && this.props.onCancel(e);
    };

    public get titleID() {
        return this.id + "-title";
    }

    public componentDidMount() {
        this.forceUpdate();
    }
}
