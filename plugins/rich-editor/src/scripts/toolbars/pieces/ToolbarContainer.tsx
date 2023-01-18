/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { PropsWithChildren, useEffect, useState } from "react";
import { RangeStatic } from "quill/core";
import ToolbarPositioner from "@rich-editor/toolbars/pieces/ToolbarPositioner";
import FloatingToolbarContent from "@library/editor/toolbars/FloatingToolbarContent";
import floatingToolbarVariables from "@library/editor/toolbars/FloatingToolbar.variables";

type IProps = PropsWithChildren<{
    selection: RangeStatic;
    isVisible: boolean;
}>;

export default React.forwardRef<HTMLDivElement, IProps>(function ToolbarContainer(props, ref) {
    const { isVisible, selection, children } = props;

    const {
        nub: { width: nubWidth },
    } = floatingToolbarVariables();
    const nubHeight = nubWidth || 12;

    const flyoutRef = React.createRef<HTMLDivElement>();

    const [flyoutWidth, setFlyoutWidth] = useState<number | null>(
        flyoutRef.current ? flyoutRef.current.offsetWidth : null,
    );
    const [flyoutHeight, setFlyoutHeight] = useState<number | null>(
        flyoutRef.current ? flyoutRef.current.offsetHeight : null,
    );

    useEffect(() => {
        setFlyoutWidth(flyoutRef.current ? flyoutRef.current.offsetWidth : null);
        setFlyoutHeight(flyoutRef.current ? flyoutRef.current.offsetHeight : null);
    }, [flyoutRef]);

    return (
        <ToolbarPositioner
            {...{
                flyoutWidth,
                flyoutHeight,
                nubHeight,
            }}
            selectionIndex={selection!.index}
            selectionLength={selection!.length}
            isActive={isVisible}
        >
            {({ x, y }) => {
                return (
                    <FloatingToolbarContent {...{ x, y, isVisible }} ref={flyoutRef}>
                        {children}
                    </FloatingToolbarContent>
                );
            }}
        </ToolbarPositioner>
    );
});
