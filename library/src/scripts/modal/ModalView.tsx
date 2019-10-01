/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ModalSizes from "@library/modal/ModalSizes";
import { modalClasses } from "@library/modal/modalStyles";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { useMemo, useRef } from "react";
import ScrollLock, { TouchScrollable } from "react-scrolllock";
import { forceRenderStyles } from "typestyle";

interface IProps {
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
}

/**
 * Render the contents into a portal.
 */
export function ModalView(props: IProps) {
    const domID = useMemo(() => uniqueIDFromPrefix("modal"), []);
    const descriptionID = domID + "-description";

    const ownRef = useRef<HTMLDivElement>(null);
    const modalRef = props.modalRef || ownRef;

    const { titleID, label, size } = props;
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

    // We HAVE to render force the styles to render before componentDidMount
    // And our various focusing tricks or the page will jump.
    forceRenderStyles();
    return (
        <ScrollLock>
            <div className={classes.overlay} onClick={props.onOverlayClick}>
                <div
                    role="dialog"
                    aria-modal={true}
                    className={classNames(
                        classes.root,
                        {
                            isFullScreen: size === ModalSizes.FULL_SCREEN || size === ModalSizes.MODAL_AS_SIDE_PANEL,
                            isSidePanel: size === ModalSizes.MODAL_AS_SIDE_PANEL,
                            isDropDown: size === ModalSizes.MODAL_AS_DROP_DOWN,
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
                </div>
            </div>
        </ScrollLock>
    );
}
