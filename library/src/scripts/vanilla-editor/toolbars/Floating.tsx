/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { css, cx } from "@emotion/css";
import { inlineToolbarClasses, nubClasses } from "@library/editor/toolbars/FloatingToolbar.classes";
import {
    flip,
    getSelectionBoundingClientRect,
    UseVirtualFloatingOptions,
    UseVirtualFloatingReturn,
} from "@udecode/plate-headless";
import FloatingToolbarVariables from "@library/editor/toolbars/FloatingToolbar.variables";

export const defaultFloatingOptions: UseVirtualFloatingOptions = {
    middleware: [
        flip({
            padding: 96,
        }),
    ],
    placement: "bottom",
    strategy: "absolute",
    getBoundingClientRect: getSelectionBoundingClientRect,
};

export default React.forwardRef<
    HTMLDivElement | null,
    React.PropsWithChildren<UseVirtualFloatingReturn & { nubWidth?: number; nubHeight?: number }>
>(function Floating(props, arrowRef) {
    const { children, nubWidth = FloatingToolbarVariables().nub.width, ...floatingResult } = props;

    const nubHeight = props.nubHeight ?? nubWidth;

    const { placement } = floatingResult;

    const arrowData = floatingResult.middlewareData.arrow;

    const arrowX = arrowData?.x || 0;

    const arrowY = floatingResult.refs.floating.current
        ? placement === "top"
            ? floatingResult.refs.floating.current.offsetHeight - 1
            : 1 - nubHeight * 2
        : null;

    const arrowStyle = {
        left: arrowX != null ? `${arrowX}px` : "",
        top: arrowY != null ? `${arrowY}px` : "",
    };

    const classesNub = nubClasses(placement === "top" ? "above" : "below");
    const { above, below } = inlineToolbarClasses();

    return (
        <div
            className={cx(css({ position: "absolute", top: 0, zIndex: 5 }), placement === "top" ? above : below)}
            style={floatingResult.style}
            ref={floatingResult.floating}
        >
            {props.children}

            {arrowRef ? (
                <div ref={arrowRef} className={classesNub.position} style={arrowStyle}>
                    <div className={classesNub.root} />
                </div>
            ) : null}
        </div>
    );
});
