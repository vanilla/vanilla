/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import classNames from "classnames";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { chevronUp, downTriangle } from "@library/components/icons/header";
import { Panel } from "@library/components/layouts/PanelLayout";
import { Frame, FrameBody, FrameFooter, FrameHeader, FramePanel } from "@library/components/frame";
import SmartAlign from "@library/components/SmartAlign";
import Heading from "@library/components/Heading";
import CloseButton from "@library/components/CloseButton";
import { BackLink } from "@library/components/navigation/BackLink";
import FlexSpacer from "@library/components/FlexSpacer";

export interface IProps {
    className?: string;
    buttonClass?: string;
    title: string;
    children: React.ReactNode;
}

interface IState {
    open: boolean;
}

/**
 * Implements Mobile Drop Down, (like a hamburger menu with the page title as the toggle)
 */
export default class MobileDropDown extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public state = {
        open: false,
    };

    public render() {
        const { className, children, title, buttonClass } = this.props;
        return children ? (
            <div className={classNames("mobileDropDown", className)}>
                <Button
                    title={this.props.title}
                    className={classNames("mobileDropDown-toggleButton", buttonClass)}
                    onClick={this.open}
                    buttonRef={this.buttonRef}
                    baseClass={ButtonBaseClass.CUSTOM}
                >
                    <span className="mobileDropDown-buttonContents">
                        <span className="mobileDropDown-title">{this.props.title}</span>
                        <span className="mobileDropDown-icon">{downTriangle("mobileDropDown-downTriangle")}</span>
                    </span>
                </Button>
                {this.state.open && (
                    <Modal
                        size={ModalSizes.MODAL_AS_DROP_DOWN}
                        label={t("Menu")}
                        elementToFocusOnExit={this.buttonRef.current!}
                        className="mobileDropDown-modal"
                        exitHandler={this.close}
                    >
                        <div className="mobileDropDown-content">
                            <Panel className="mobileDropDown-panel">
                                <Frame>
                                    <header className="frameHeader mobileDropDown-header">
                                        <FlexSpacer className="frameHeader-leftSpacer" />
                                        <Heading title={title} className="frameHeader-heading frameHeader-centred">
                                            <SmartAlign>{title}</SmartAlign>
                                        </Heading>
                                        <div className="frameHeader-closePosition">
                                            <CloseButton className="frameHeader-close" onClick={this.close} />
                                        </div>
                                    </header>
                                    <FrameBody>{children}</FrameBody>
                                    <FrameFooter className="isCompact">
                                        <Button
                                            onClick={this.close}
                                            baseClass={ButtonBaseClass.CUSTOM}
                                            className="mobileDropDown-closeModal"
                                        >
                                            {chevronUp("mobileDropDown-closeModalIcon")}
                                        </Button>
                                    </FrameFooter>
                                </Frame>
                            </Panel>
                        </div>
                    </Modal>
                )}
            </div>
        ) : (
            <span className="mobileDropDown-title">{this.props.title}</span>
        );
    }

    private open = () => {
        this.setState({
            open: true,
        });
    };
    private close = () => {
        this.setState({
            open: false,
        });
    };
}
