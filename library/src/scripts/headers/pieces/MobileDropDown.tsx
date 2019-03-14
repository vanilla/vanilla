/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Heading from "@library/layout/Heading";
import { chevronUp, downTriangle } from "@library/icons/common";
import CloseButton from "@library/navigation/CloseButton";
import { Panel } from "@library/layout/PanelLayout";
import { frameHeaderClasses } from "@library/layout/frame/frameStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import FlexSpacer from "@library/layout/FlexSpacer";
import SmartAlign from "@library/layout/SmartAlign";
import { mobileDropDownClasses } from "@library/headers/pieces/mobileDropDownStyles";

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
        const classesFrameHeader = frameHeaderClasses();
        const { className, children, title, buttonClass } = this.props;
        return children ? (
            <div className={classNames(classes.root, className)}>
                <Button
                    title={this.props.title}
                    className={classNames(classes.toggleButton, buttonClass)}
                    onClick={this.open}
                    buttonRef={this.buttonRef}
                    baseClass={ButtonTypes.CUSTOM}
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
                                        <FlexSpacer
                                            className={classNames(
                                                "frameHeader-leftSpacer",
                                                classesFrameHeader.leftSpacer,
                                            )}
                                        />
                                        <Heading
                                            title={title}
                                            className={classNames(
                                                "frameHeader-heading",
                                                "frameHeader-centred",
                                                classesFrameHeader.centred,
                                                classesFrameHeader.heading,
                                            )}
                                        >
                                            <SmartAlign>{title}</SmartAlign>
                                        </Heading>
                                        <div
                                            className={classNames(
                                                "frameHeader-closePosition",
                                                classesFrameHeader.closePosition,
                                                classesFrameHeader.action,
                                            )}
                                        >
                                            <CloseButton
                                                className="frameHeader-close"
                                                onClick={this.close}
                                                baseClass={ButtonTypes.CUSTOM}
                                            />
                                        </div>
                                    </header>
                                    <FrameBody className={this.props.frameBodyClassName}>{children}</FrameBody>
                                    <FrameFooter>
                                        <Button
                                            onClick={this.close}
                                            baseClass={ButtonTypes.CUSTOM}
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
