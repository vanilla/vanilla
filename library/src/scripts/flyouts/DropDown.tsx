/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { frameHeaderClasses } from "@library/layout/frame/frameHeaderStyles";
import Heading from "@library/layout/Heading";
import DropDownContents from "@library/flyouts/DropDownContents";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { getRequiredID } from "@library/utility/idUtils";
import FlexSpacer from "@library/layout/FlexSpacer";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import SmartAlign from "@library/layout/SmartAlign";
import CloseButton from "@library/navigation/CloseButton";
import FlyoutToggle from "@library/flyouts/FlyoutToggle";
import classNames from "classnames";
import { dropDownMenu } from "@library/icons/common";
import { IDeviceProps, withDevice, Devices } from "@library/layout/DeviceContext";

export interface IProps extends IDeviceProps {
    id?: string;
    name?: string;
    children: React.ReactNode;
    className?: string;
    renderAbove?: boolean; // Adjusts the flyout position vertically
    renderLeft?: boolean; // Adjusts the flyout position horizontally
    describedBy?: string;
    contentsClassName?: string;
    buttonContents?: React.ReactNode;
    buttonClassName?: string;
    buttonBaseClass?: ButtonTypes;
    disabled?: boolean;
    toggleButtonClassName?: string;
    initialFocusElement?: HTMLElement | null;
    buttonRef?: React.RefObject<HTMLButtonElement>;
    onVisibilityChange?: (isVisible: boolean) => void;
    openAsModal?: boolean;
    title?: string;
    selfPadded?: boolean;
    flyoutSize?: FlyoutSizes;
}

export enum FlyoutSizes {
    DEFAULT = "default",
    MEDIUM = "medium",
}

export interface IState {
    selectedText: string;
}

/**
 * Creates a drop down menu
 */
class DropDown extends React.Component<IProps, IState> {
    private id;
    public constructor(props) {
        super(props);
        this.id = getRequiredID(props, "dropDown");
        this.state = {
            selectedText: "",
        };
    }

    public setSelectedText(selectedText) {
        this.setState({
            selectedText,
        });
    }

    public get selectedText(): string {
        return this.state.selectedText;
    }

    public render() {
        const { title } = this.props;
        const classesDropDown = dropDownClasses();
        const classesFrameHeader = frameHeaderClasses();
        const classes = dropDownClasses();

        const openAsModal =
            this.props.openAsModal || this.props.device === Devices.MOBILE || this.props.device === Devices.XS;
        return (
            <FlyoutToggle
                id={this.id}
                className={classNames(this.props.className)}
                buttonBaseClass={this.props.buttonBaseClass || ButtonTypes.ICON}
                name={this.props.name}
                buttonContents={this.props.buttonContents || dropDownMenu()}
                buttonClassName={this.props.buttonClassName}
                selectedItemLabel={this.selectedText}
                disabled={this.props.disabled}
                buttonRef={this.props.buttonRef}
                toggleButtonClassName={this.props.toggleButtonClassName}
                onVisibilityChange={this.props.onVisibilityChange}
                openAsModal={openAsModal}
                initialFocusElement={this.props.initialFocusElement}
            >
                {params => {
                    return (
                        <DropDownContents
                            {...params}
                            id={this.id + "-handle"}
                            parentID={this.id}
                            className={classNames(this.props.contentsClassName)}
                            onClick={this.doNothing}
                            renderLeft={!!this.props.renderLeft}
                            renderAbove={!!this.props.renderAbove}
                            openAsModal={openAsModal}
                            selfPadded={this.props.selfPadded}
                            flyoutSize={this.props.flyoutSize}
                        >
                            {title ? (
                                <header className={classNames("frameHeader", classesFrameHeader.root)}>
                                    {openAsModal && (
                                        <FlexSpacer
                                            className={classNames(
                                                "frameHeader-leftSpacer",
                                                classesFrameHeader.leftSpacer,
                                            )}
                                        />
                                    )}
                                    {openAsModal && (
                                        <SmartAlign>
                                            <Heading
                                                title={title}
                                                className={classNames(
                                                    "dropDown-title",
                                                    classesDropDown.title,
                                                    classes.title,
                                                )}
                                            />
                                        </SmartAlign>
                                    )}

                                    {!openAsModal && (
                                        <Heading
                                            title={title}
                                            className={classNames(
                                                "dropDown-title",
                                                classesDropDown.title,
                                                classes.title,
                                            )}
                                        />
                                    )}

                                    <CloseButton
                                        className={classesFrameHeader.action}
                                        onClick={params.closeMenuHandler}
                                    />
                                </header>
                            ) : null}
                            <ul className={classNames("dropDownItems", classes.items)}>{this.props.children}</ul>
                        </DropDownContents>
                    );
                }}
            </FlyoutToggle>
        );
    }

    private doNothing = e => {
        e.stopPropagation();
        e.preventDefault();
    };
}

export default withDevice(DropDown);
