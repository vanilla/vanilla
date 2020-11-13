/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState } from "react";
import DropDownContents, { DropDownContentSize } from "@library/flyouts/DropDownContents";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { useUniqueID } from "@library/utility/idUtils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import FlyoutToggle from "@library/flyouts/FlyoutToggle";
import classNames from "classnames";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { DropDownMenuIcon } from "@library/icons/common";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { FrameHeaderMinimal } from "@library/layout/frame/FrameHeaderMinimal";
import { useMeasure } from "@vanilla/react-utils";

import ConditionalWrap from "@library/layout/ConditionalWrap";

export enum DropDownOpenDirection {
    ABOVE_LEFT = "aboveLeft",
    ABOVE_RIGHT = "aboveRight",
    ABOVE_CENTER = "aboveCenter",
    BELOW_LEFT = "belowLeft",
    BELOW_RIGHT = "belowRight",
    BELOW_CENTER = "belowCenter",
    AUTO = "auto",
    HIDDEN = "hidden",
}

// We can add more as we need, but for now, only this one is supported
export enum DropDownPreferredOpenDirections {
    ABOVE_CENTER = "aboveCenter",
}

interface IOpenDirectionProps {
    openDirection?: DropDownOpenDirection;
    preferredDirection?: DropDownPreferredOpenDirections;
    renderAbove?: boolean; // @deprecated
    renderLeft?: boolean; // @deprecated
}

export interface IDropDownProps extends IOpenDirectionProps {
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
    contentRef?: React.RefObject<HTMLDivElement>;
    isVisible?: boolean;
    onVisibilityChange?: (isVisible: boolean) => void;
    openAsModal?: boolean;
    title?: string;
    mobileTitle?: string;
    flyoutType: FlyoutType;
    selfPadded?: boolean;
    isSmall?: boolean;
    handleID?: string;
    contentID?: string;
    horizontalOffset?: boolean;
    tag?: string;
    accessibleLabel?: string;
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
export default function DropDown(props: IDropDownProps) {
    const device = useDevice();
    const { title, preferredDirection } = props;
    const mobileTitle = props.mobileTitle ?? title;
    const classes = dropDownClasses();
    const ContentTag = props.flyoutType === FlyoutType.FRAME ? "div" : "ul";
    const openAsModal = props.openAsModal || device === Devices.MOBILE || device === Devices.XS;
    const buttonRef = useRef<HTMLButtonElement>(null);
    const contentRef = useRef<HTMLDivElement>(null);
    const ownButtonRef = props.buttonRef ?? buttonRef;
    const ownContentRef = props.contentRef ?? contentRef;
    const ID = useUniqueID("flyout");

    const [ownIsVisible, setOwnIsVisible] = useState(false);
    const isVisible = props.isVisible ?? (preferredDirection ? ownIsVisible : undefined);
    const onVisibilityChange = props.onVisibilityChange ?? (preferredDirection ? setOwnIsVisible : undefined);

    const buttonRect = useMeasure(ownButtonRef); // Dimensions of button
    const contentRect = useMeasure(ownContentRef); // Dimensions of content

    const openDirection = resolveOpenDirection({
        props,
        buttonRect,
        contentRect,
        dropDownID: ID,
    });

    const positionHidden = openDirection === DropDownOpenDirection.HIDDEN;

    const handleID = props.handleID ?? ID + "-handle";
    const contentID = props.contentID ?? ID + "-contents";

    return (
        <FlyoutToggle
            id={handleID}
            className={classNames(props.className)}
            buttonBaseClass={props.buttonBaseClass || ButtonTypes.ICON}
            name={props.name!}
            buttonContents={props.buttonContents || <DropDownMenuIcon />}
            buttonClassName={props.buttonClassName}
            disabled={props.disabled}
            buttonRef={ownButtonRef}
            toggleButtonClassName={props.toggleButtonClassName}
            isVisible={isVisible}
            onVisibilityChange={onVisibilityChange}
            openAsModal={openAsModal}
            initialFocusElement={props.initialFocusElement}
            tag={props.tag}
            contentID={contentID}
        >
            {(params) => {
                return (
                    <ConditionalWrap
                        condition={(!isVisible || positionHidden) && !openAsModal && !!preferredDirection}
                        className={classes.positioning}
                    >
                        <DropDownContents
                            {...params}
                            contentRef={ownContentRef}
                            id={contentID}
                            className={classNames(props.contentsClassName)}
                            renderCenter={[
                                DropDownOpenDirection.ABOVE_CENTER,
                                DropDownOpenDirection.BELOW_CENTER,
                            ].includes(openDirection)}
                            renderLeft={[DropDownOpenDirection.ABOVE_LEFT, DropDownOpenDirection.BELOW_LEFT].includes(
                                openDirection,
                            )}
                            renderAbove={[
                                DropDownOpenDirection.ABOVE_RIGHT,
                                DropDownOpenDirection.ABOVE_LEFT,
                                DropDownOpenDirection.ABOVE_CENTER,
                            ].includes(openDirection)}
                            openAsModal={openAsModal}
                            selfPadded={
                                props.selfPadded !== undefined
                                    ? props.selfPadded
                                    : props.flyoutType === FlyoutType.FRAME
                            }
                            size={
                                props.flyoutType === FlyoutType.FRAME && !props.isSmall
                                    ? DropDownContentSize.MEDIUM
                                    : DropDownContentSize.SMALL
                            }
                            horizontalOffset={props.horizontalOffset}
                        >
                            {!openAsModal && title && (
                                <FrameHeader title={title} closeFrame={params.closeMenuHandler} />
                            )}
                            {openAsModal && mobileTitle && (
                                <FrameHeaderMinimal onClose={params.closeMenuHandler}>
                                    {mobileTitle ?? title}
                                </FrameHeaderMinimal>
                            )}
                            {openAsModal && props.flyoutType === FlyoutType.FRAME ? (
                                props.children
                            ) : (
                                <ContentTag className={classNames("dropDownItems", classes.items)}>
                                    {props.children}
                                </ContentTag>
                            )}
                        </DropDownContents>
                    </ConditionalWrap>
                );
            }}
        </FlyoutToggle>
    );
}

interface IResolveDirectionProps {
    props: IOpenDirectionProps;
    buttonRect: DOMRect;
    contentRect: DOMRect;
    setFlyoutHasPlacement?: (positioned: boolean) => void;
    dropDownID: string;
}

const resolveOpenDirection = (data: IResolveDirectionProps): DropDownOpenDirection => {
    const { props, buttonRect, contentRect } = data;
    let { renderAbove, renderLeft, preferredDirection, openDirection } = props;

    if (preferredDirection && (contentRect.height === 0 || contentRect.width === 0)) {
        return DropDownOpenDirection.HIDDEN;
    }

    // @deprecated, do not use these props anymore
    if ((openDirection && renderAbove) || (openDirection && renderLeft)) {
        throw new Error("`renderAbove` & `renderLeft` may not be used with `openDirection` in <DropDown />");
    }

    // check for preferred positioning. We currently only support 1, but more can be added
    if (preferredDirection && buttonRect.width !== 0 && contentRect.width !== 0) {
        switch (preferredDirection) {
            case DropDownPreferredOpenDirections.ABOVE_CENTER:
                const topClearance = buttonRect.top;
                const leftClearance = buttonRect.left;
                const rightClearance = window.innerWidth - buttonRect.right;

                const contentHalfWidth = contentRect.width / 2;
                const contentHeight = contentRect.height;

                renderAbove = topClearance >= contentHeight;

                const renderCentered = contentHalfWidth <= leftClearance && contentHalfWidth <= rightClearance;

                if (renderCentered) {
                    if (renderAbove) {
                        return DropDownOpenDirection.ABOVE_CENTER;
                    } else {
                        return DropDownOpenDirection.BELOW_CENTER;
                    }
                } // Else, we've just determined one direction. The left or right position is calculated below
                break;
        }
    }

    // Early bailout if we aren't auto.
    if (props.openDirection === DropDownOpenDirection.AUTO || !renderAbove || !renderLeft) {
        if (!renderAbove) {
            const documentHeight = document.body.clientHeight;
            const centerY = (buttonRect.top + buttonRect.bottom) / 2; // center Y position of button
            renderAbove = centerY > documentHeight / 2;
        }
        if (!renderLeft) {
            const documentWidth = document.body.clientWidth;
            const centerX = (buttonRect.left + buttonRect.right) / 2; // center X position of button
            renderLeft = centerX > documentWidth / 2;
        }
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
};
