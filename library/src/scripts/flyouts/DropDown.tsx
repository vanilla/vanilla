/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import { frameHeaderClasses } from "@library/layout/frame/frameHeaderStyles";
import Heading from "@library/layout/Heading";
import DropDownContents, { DropDownContentSize } from "@library/flyouts/DropDownContents";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { useUniqueID } from "@library/utility/idUtils";
import FlexSpacer from "@library/layout/FlexSpacer";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import SmartAlign from "@library/layout/SmartAlign";
import CloseButton from "@library/navigation/CloseButton";
import FlyoutToggle from "@library/flyouts/FlyoutToggle";
import classNames from "classnames";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { DropDownMenuIcon } from "@library/icons/common";
import { props } from "bluebird";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { FrameHeaderMinimal } from "@library/layout/frame/FrameHeaderMinimal";

export enum DropDownOpenDirection {
    ABOVE_LEFT = "aboveLeft",
    ABOVE_RIGHT = "aboveRight",
    BELOW_LEFT = "belowLeft",
    BELOW_RIGHT = "belowRight",
    AUTO = "auto",
}

interface IOpenDirectionProps {
    openDirection?: DropDownOpenDirection;
    renderAbove?: boolean; // @deprecated
    renderLeft?: boolean; // @deprecated
}

export interface IProps extends IOpenDirectionProps {
    name?: string;
    children: React.ReactNode;
    className?: string;
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
    mobileTitle?: string;
    flyoutType: FlyoutType;
    selfPadded?: boolean;
    isSmall?: boolean;
    id?: string;
    horizontalOffset?: boolean;
    tag?: string;
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
export default function DropDown(props: IProps) {
    const ownID = useUniqueID("dropDown");
    const id = props.id || ownID;
    const device = useDevice();

    const { title } = props;
    const mobileTitle = props.mobileTitle ?? title;
    const classesDropDown = dropDownClasses();
    const classesFrameHeader = frameHeaderClasses();
    const classes = dropDownClasses();
    const ContentTag = props.flyoutType === FlyoutType.FRAME ? "div" : "ul";
    const openAsModal = props.openAsModal || device === Devices.MOBILE || device === Devices.XS;
    const ownButtonRef = useRef<HTMLButtonElement>(null);
    const openDirection = resolveOpenDirection(props, props.buttonRef || ownButtonRef);

    return (
        <FlyoutToggle
            id={id}
            className={classNames(props.className)}
            buttonBaseClass={props.buttonBaseClass || ButtonTypes.ICON}
            name={props.name!}
            buttonContents={props.buttonContents || <DropDownMenuIcon />}
            buttonClassName={props.buttonClassName}
            disabled={props.disabled}
            buttonRef={props.buttonRef || ownButtonRef}
            toggleButtonClassName={props.toggleButtonClassName}
            isVisible={props.isVisible}
            onVisibilityChange={props.onVisibilityChange}
            openAsModal={openAsModal}
            initialFocusElement={props.initialFocusElement}
            tag={props.tag}
        >
            {params => {
                return (
                    <DropDownContents
                        {...params}
                        id={id + "-handle"}
                        parentID={id}
                        className={classNames(props.contentsClassName)}
                        renderLeft={[DropDownOpenDirection.ABOVE_LEFT, DropDownOpenDirection.BELOW_LEFT].includes(
                            openDirection,
                        )}
                        renderAbove={[DropDownOpenDirection.ABOVE_RIGHT, DropDownOpenDirection.ABOVE_LEFT].includes(
                            openDirection,
                        )}
                        openAsModal={openAsModal}
                        selfPadded={
                            props.selfPadded !== undefined ? props.selfPadded : props.flyoutType === FlyoutType.FRAME
                        }
                        size={
                            props.flyoutType === FlyoutType.FRAME && !props.isSmall
                                ? DropDownContentSize.MEDIUM
                                : DropDownContentSize.SMALL
                        }
                        horizontalOffset={props.horizontalOffset}
                    >
                        {!openAsModal && title && <FrameHeader title={title} closeFrame={params.closeMenuHandler} />}
                        {openAsModal && mobileTitle && (
                            <FrameHeaderMinimal onClose={params.closeMenuHandler}>
                                {mobileTitle ?? title}
                            </FrameHeaderMinimal>
                        )}
                        <ContentTag className={classNames("dropDownItems", classes.items)}>{props.children}</ContentTag>
                    </DropDownContents>
                );
            }}
        </FlyoutToggle>
    );
}

function resolveOpenDirection(props: IOpenDirectionProps, ref: React.RefObject<HTMLElement>): DropDownOpenDirection {
    let { renderAbove, renderLeft } = props;
    if ((props.openDirection && renderAbove) || (props.openDirection && renderLeft)) {
        throw new Error("`renderAbove` & `renderLeft` may not be used with `openDirection` in <DropDown />");
    }

    // Early bailout if we aren't auto.
    if (ref.current && (props.openDirection === DropDownOpenDirection.AUTO || (!renderAbove && !renderLeft))) {
        const documentWidth = document.body.clientWidth;
        const documentHeight = document.body.clientHeight;

        const rect = ref.current.getBoundingClientRect() as ClientRect;
        const centerX = (rect.left + rect.right) / 2;
        const centerY = (rect.top + rect.bottom) / 2;
        renderAbove = centerY > documentHeight / 2;
        renderLeft = centerX > documentWidth / 2;
    } else if (props.openDirection) {
        return props.openDirection;
    }

    if (renderAbove && renderLeft) {
        return DropDownOpenDirection.ABOVE_LEFT;
    } else if (renderAbove && !renderLeft) {
        return DropDownOpenDirection.ABOVE_RIGHT;
    } else if (!renderAbove && renderLeft) {
        return DropDownOpenDirection.BELOW_LEFT;
    } else if (!renderAbove && !renderLeft) {
        return DropDownOpenDirection.BELOW_RIGHT;
    }

    // DEFAULT
    return DropDownOpenDirection.BELOW_RIGHT;
}
