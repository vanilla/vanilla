/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ModalSizes from "@library/modal/ModalSizes";
import { modalClasses } from "@library/modal/modalStyles";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { useMemo, useRef, useState, useLayoutEffect, useCallback } from "react";
import ScrollLock, { TouchScrollable } from "react-scrolllock";
import { forceRenderStyles } from "typestyle";
import { IModalTransitioner, ModalTransition } from "@library/modal/ModalTransition";
import { EntranceAnimation, ITargetTransform, FromDirection } from "@library/animation/EntranceAnimation";
import { useLastValue } from "@vanilla/react-utils";

interface IProps extends IModalTransitioner {
    onOverlayClick: React.MouseEventHandler;
    onModalClick: React.MouseEventHandler;
    onKeyDown: React.KeyboardEventHandler;
    description?: string;
    titleID?: string;
    label?: string;
    className?: boolean;
    scrollable?: boolean;
    size: ModalSizes;
    modalRef?: React.RefObject<HTMLDivElement>;
    children?: React.ReactNode;
    isVisible: boolean;
    onDestroyed: () => void;
}

/**
 * Render the contents into a portal.
 */
export function ModalView(props: IProps) {
    const { titleID, label, size, isVisible, onDestroyed } = props;
    const [isAnimatingOut, setIsAnimatingOut] = useState(false);

    const lastVisible = useLastValue(isVisible);
    useLayoutEffect(() => {
        if (lastVisible && !isVisible) {
            setIsAnimatingOut(true);
        }
    }, [isVisible, lastVisible]);

    const handleDestroy = useCallback(() => {
        setIsAnimatingOut(false);
        onDestroyed();
    }, [onDestroyed]);

    const domID = useMemo(() => uniqueIDFromPrefix("modal"), []);
    const descriptionID = domID + "-description";

    const ownRef = useRef<HTMLDivElement>(null);
    const modalRef = props.modalRef || ownRef;

    const classes = modalClasses();

    let contents = (
        <>
            {props.description && (
                <div id={descriptionID} className="sr-only">
                    {props.description}
                </div>
            )}
            {props.children}
        </>
    );

    if (props.scrollable) {
        contents = (
            <TouchScrollable>
                <div className={classes.scroll}>{contents}</div>
            </TouchScrollable>
        );
    }

    const targetTransform: Partial<ITargetTransform> | undefined = useMemo(() => {
        switch (size) {
            case ModalSizes.SMALL:
            case ModalSizes.MEDIUM:
            case ModalSizes.LARGE:
            case ModalSizes.XL:
                return {
                    xPercent: -50,
                    yPercent: -50,
                };
            default:
                return undefined;
        }
    }, [size]);

    const contentTransition = (() => {
        switch (props.size) {
            case ModalSizes.SMALL:
            case ModalSizes.MEDIUM:
            case ModalSizes.LARGE:
                return {
                    fade: true,
                    fromDirection: FromDirection.BOTTOM,
                    halfDirection: true,
                };
            case ModalSizes.XL:
                return {
                    fade: true,
                };
            case ModalSizes.FULL_SCREEN:
                return {
                    fade: false,
                };
            case ModalSizes.MODAL_AS_DROP_DOWN:
                return {
                    fade: false,
                    fromDirection: FromDirection.TOP,
                };
            case ModalSizes.MODAL_AS_SIDE_PANEL_RIGHT:
                return {
                    fade: false,
                    fromDirection: FromDirection.RIGHT,
                };
            case ModalSizes.MODAL_AS_SIDE_PANEL_LEFT:
                return {
                    fade: false,
                    fromDirection: FromDirection.LEFT,
                };
        }
    })();

    // We HAVE to render force the styles to render before componentDidMount
    // And our various focusing tricks or the page will jump.
    forceRenderStyles();
    return (
        <div>
            <EntranceAnimation
                fade
                isEntered={props.isVisible}
                className={classes.overlayScrim}
                onDestroyed={handleDestroy}
            ></EntranceAnimation>
            <ScrollLock isActive={props.isVisible || lastVisible || isAnimatingOut}>
                <div
                    className={classes.overlayContent}
                    onClick={props.onOverlayClick}
                    style={{ pointerEvents: props.isVisible ? "initial" : "none" }}
                >
                    <EntranceAnimation
                        {...contentTransition}
                        targetTransform={targetTransform}
                        isEntered={props.isVisible}
                        role="dialog"
                        aria-modal={true}
                        className={classNames(
                            classes.root,
                            {
                                isFullScreen:
                                    size === ModalSizes.FULL_SCREEN ||
                                    size === ModalSizes.MODAL_AS_SIDE_PANEL_RIGHT ||
                                    size === ModalSizes.MODAL_AS_SIDE_PANEL_LEFT,
                                isSidePanelRight: size === ModalSizes.MODAL_AS_SIDE_PANEL_RIGHT,
                                isSidePanelLeft: size === ModalSizes.MODAL_AS_SIDE_PANEL_LEFT,
                                isDropDown: size === ModalSizes.MODAL_AS_DROP_DOWN,
                                isXL: size === ModalSizes.XL,
                                isLarge: size === ModalSizes.LARGE,
                                isMedium: size === ModalSizes.MEDIUM,
                                isSmall: size === ModalSizes.SMALL,
                                isShadowed: size === ModalSizes.LARGE || ModalSizes.MEDIUM || ModalSizes.SMALL,
                            },
                            props.className,
                        )}
                        ref={modalRef}
                        onKeyDown={props.onKeyDown}
                        onClick={props.onModalClick}
                        aria-label={label}
                        aria-labelledby={titleID}
                        aria-describedby={props.description ? descriptionID : undefined}
                    >
                        {contents}
                    </EntranceAnimation>
                </div>
            </ScrollLock>
        </div>
    );
}
