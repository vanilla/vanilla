/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { mobileDropDownClasses } from "@library/headers/pieces/mobileDropDownStyles";
import Container from "@library/layout/components/Container";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import LazyModal from "@library/modal/LazyModal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import * as React from "react";
import { ChevronUpIcon, DownTriangleIcon, NBSP, UpTriangleIcon } from "@library/icons/common";
import { panelBackgroundClasses } from "@library/layout/panelBackgroundStyles";
import { EntranceAnimation, FromDirection } from "@library/animation/EntranceAnimation";
import PanelWidgetHorizontalPadding from "@library/layout/components/PanelWidgetHorizontalPadding";

export interface IProps {
    className?: string;
    buttonClass?: string;
    title: string;
    children?: React.ReactNode;
    frameClassName?: string;
    frameBodyClassName?: string;
    hasBackgroundColor?: boolean;
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

        const TitleButton = (props: { icon: React.ReactNode; onClick: React.MouseEventHandler }) => {
            return (
                <Button
                    title={this.props.title}
                    className={classNames(classes.toggleButton, buttonClass)}
                    onClick={props.onClick}
                    buttonRef={this.buttonRef}
                    buttonType={ButtonTypes.CUSTOM}
                >
                    <span className={classNames(classes.buttonContents)}>
                        <span className={classes.title}>{this.props.title}</span>
                        {NBSP}
                        <span className={classes.icon}>{props.icon}</span>
                    </span>
                </Button>
            );
        };

        return children ? (
            <div className={classNames(classes.root, className)}>
                <TitleButton
                    icon={<DownTriangleIcon className={"mobileDropDown-downTriangle"} />}
                    onClick={this.open}
                />
                <LazyModal
                    isVisible={this.state.open}
                    size={ModalSizes.MODAL_AS_DROP_DOWN}
                    label={t("Menu")}
                    elementToFocusOnExit={this.buttonRef.current as HTMLElement}
                    className={classes.modal}
                    exitHandler={this.close}
                    afterContent={
                        <EntranceAnimation
                            delay={50}
                            fromDirection={FromDirection.TOP}
                            asElement="header"
                            isEntered={this.state.open}
                            className={classes.header}
                        >
                            <Container>
                                <PanelWidgetHorizontalPadding>
                                    <div className={classes.headerContent}>
                                        <TitleButton onClick={this.close} icon={<UpTriangleIcon />} />
                                    </div>
                                </PanelWidgetHorizontalPadding>
                            </Container>
                        </EntranceAnimation>
                    }
                >
                    <Frame
                        bodyWrapClass={classNames({
                            [panelBackgroundClasses().backgroundColor]: this.props.hasBackgroundColor, // Note that it will take the config from the component to decide if it renders the color or not.
                        })}
                        header={<div className={classes.headerSpacer}></div>}
                        body={<FrameBody className={this.props.frameBodyClassName}>{children}</FrameBody>}
                        footer={
                            <FrameFooter>
                                <Button
                                    onClick={this.close}
                                    buttonType={ButtonTypes.CUSTOM}
                                    className={classes.closeModal}
                                >
                                    <ChevronUpIcon className={classes.closeModalIcon} />
                                </Button>
                            </FrameFooter>
                        }
                    />
                </LazyModal>
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
