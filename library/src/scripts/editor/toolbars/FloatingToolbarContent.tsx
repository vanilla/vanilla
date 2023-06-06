import { css, cx } from "@emotion/css";
import { inlineToolbarClasses, nubClasses } from "@library/editor/toolbars/FloatingToolbar.classes";
import React, { PropsWithChildren } from "react";

export interface IXCoordinates {
    position: number;
    nubPosition?: number;
}

export interface IYCoordinates {
    position: number;
    nubPosition?: number;
    nubPointsDown?: boolean;
}

export interface IParameters {
    x: IXCoordinates | null;
    y: IYCoordinates | null;
    offsetX?: IXCoordinates | null;
}

type IFloatingToolbarContentProps = PropsWithChildren<
    IParameters & {
        isVisible: boolean;
    }
>;

export default React.forwardRef<HTMLDivElement, IFloatingToolbarContentProps>(function FloatingToolbarContent(
    props,
    ref,
) {
    const { x, y, isVisible, children } = props;

    let toolbarStyles: React.CSSProperties = {
        visibility: "hidden",
        position: "absolute",
        top: 0,
    };
    let nubStyles = {};

    if (x && y && isVisible) {
        toolbarStyles = {
            position: "absolute",
            top: y ? y.position : 0,
            left: x ? x.position : 0,
            zIndex: 5,
            visibility: "visible",
        };

        nubStyles = {
            left: x && x.nubPosition ? x.nubPosition : 0,
            top: y && y.nubPosition ? y.nubPosition : 0,
        };
    }

    const isAbove = !!(y && y.nubPointsDown);

    const classesNub = nubClasses(isAbove ? "above" : "below");
    const { above, below } = inlineToolbarClasses();

    return (
        <div className={isAbove ? above : below} style={toolbarStyles} ref={ref}>
            {children}
            <div style={nubStyles} className={cx(classesNub.position, css({ transform: "translateX(-50%)" }))}>
                <div className={classesNub.root} />
            </div>
        </div>
    );
});
