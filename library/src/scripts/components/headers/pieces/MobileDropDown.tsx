/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import classNames from "classnames";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { chevronUp, downTriangle } from "@library/components/icons/common";
import { Panel } from "@library/components/layouts/PanelLayout";
import { Frame, FrameBody, FrameFooter } from "@library/components/frame";
import SmartAlign from "@library/components/SmartAlign";
import Heading from "@library/components/Heading";
import CloseButton from "@library/components/CloseButton";
import FlexSpacer from "@library/components/FlexSpacer";
import { mobileDropDownClasses } from "@library/styles/mobileDropDownStyles";

export interface IProps {
    className?: string;
    buttonClass?: string;
    title: string;
    children?: React.ReactNode;
    frameClassName?: string;
    frameBodyClassName?: string;
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
        const classes = mobileDropDownClasses();
        const { className, children, title, buttonClass } = this.props;
        return children ? (
            <div className={classNames(classes.root, className)}>
                <Button
                    title={this.props.title}
                    className={classNames(classes.toggleButton, buttonClass)}
                    onClick={this.open}
                    buttonRef={this.buttonRef}
                    baseClass={ButtonBaseClass.CUSTOM}
                >
                    <span className={classNames(classes.buttonContents)}>
                        <span className={classes.title}>{this.props.title}</span>
                        <span className={classes.icon}>{downTriangle("mobileDropDown-downTriangle")}</span>
                    </span>
                </Button>
                {this.state.open && (
                    <Modal
                        size={ModalSizes.MODAL_AS_DROP_DOWN}
                        label={t("Menu")}
                        elementToFocusOnExit={this.buttonRef.current!}
                        className={classes.modal}
                        exitHandler={this.close}
                    >
                        <div className={classes.content}>
                            <Panel className={classes.panel}>
                                <Frame className={this.props.frameClassName}>
                                    <header className={classes.header}>
                                        <FlexSpacer className="frameHeader-leftSpacer" />
                                        <Heading title={title} className="frameHeader-heading frameHeader-centred">
                                            <SmartAlign>{title}</SmartAlign>
                                        </Heading>
                                        <div className="frameHeader-closePosition">
                                            <CloseButton
                                                className="frameHeader-close"
                                                onClick={this.close}
                                                baseClass={ButtonBaseClass.CUSTOM}
                                            />
                                        </div>
                                    </header>
                                    <FrameBody className={this.props.frameBodyClassName}>{children}</FrameBody>
                                    <FrameFooter>
                                        <Button
                                            onClick={this.close}
                                            baseClass={ButtonBaseClass.CUSTOM}
                                            className={classes.closeModal}
                                        >
                                            {chevronUp(classes.closeModalIcon)}
                                        </Button>
                                    </FrameFooter>
                                </Frame>
                            </Panel>
                        </div>
                    </Modal>
                )}
            </div>
        ) : (
            <div className={classes.toggleButton}>
                <span className={classes.title}>{this.props.title}</span>
            </div>
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
