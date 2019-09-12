/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { frameHeaderClasses } from "@library/layout/frame/frameHeaderStyles";
import Heading from "@library/layout/Heading";
import DropDownContents, { DropDownContentSize } from "@library/flyouts/DropDownContents";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { getRequiredID } from "@library/utility/idUtils";
import FlexSpacer from "@library/layout/FlexSpacer";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import SmartAlign from "@library/layout/SmartAlign";
import CloseButton from "@library/navigation/CloseButton";
import FlyoutToggle from "@library/flyouts/FlyoutToggle";
import classNames from "classnames";
import { IDeviceProps, withDevice, Devices } from "@library/layout/DeviceContext";
import { DropDownMenuIcon } from "@library/icons/common";

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
    isVisible?: boolean;
    onVisibilityChange?: (isVisible: boolean) => void;
    openAsModal?: boolean;
    title?: string;
    flyoutType: FlyoutType;
    selfPadded?: boolean;
    isSmall?: boolean;
}

export enum FlyoutType {
    LIST = "list",
    FRAME = "frame",
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
        const ContentTag = this.props.flyoutType === FlyoutType.FRAME ? "div" : "ul";

        const openAsModal =
            this.props.openAsModal || this.props.device === Devices.MOBILE || this.props.device === Devices.XS;
        return (
            <FlyoutToggle
                id={this.id}
                className={classNames(this.props.className)}
                buttonBaseClass={this.props.buttonBaseClass || ButtonTypes.ICON}
                name={this.props.name}
                buttonContents={this.props.buttonContents || <DropDownMenuIcon />}
                buttonClassName={this.props.buttonClassName}
                selectedItemLabel={this.selectedText}
                disabled={this.props.disabled}
                buttonRef={this.props.buttonRef}
                toggleButtonClassName={this.props.toggleButtonClassName}
                isVisible={this.props.isVisible}
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
                            renderLeft={!!this.props.renderLeft}
                            renderAbove={!!this.props.renderAbove}
                            openAsModal={openAsModal}
                            selfPadded={
                                this.props.selfPadded !== undefined
                                    ? this.props.selfPadded
                                    : this.props.flyoutType === FlyoutType.FRAME
                            }
                            size={
                                this.props.flyoutType === FlyoutType.FRAME && !this.props.isSmall
                                    ? DropDownContentSize.MEDIUM
                                    : DropDownContentSize.SMALL
                            }
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
                                            (
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
                                        className={classNames(
                                            classesFrameHeader.action,
                                            classesFrameHeader.categoryIcon,
                                        )}
                                        onClick={params.closeMenuHandler}
                                    />
                                </header>
                            ) : null}
                            <ContentTag className={classNames("dropDownItems", classes.items)}>
                                {this.props.children}
                            </ContentTag>
                        </DropDownContents>
                    );
                }}
            </FlyoutToggle>
        );
    }
}

export default withDevice<IProps>(DropDown);
