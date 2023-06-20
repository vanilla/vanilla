/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useLayoutEffect, useState } from "react";
import { RangeStatic } from "quill/core";
import ToolbarPositioner from "@rich-editor/toolbars/pieces/ToolbarPositioner";

export default function MentionSuggestionListPositioner(
    props: React.PropsWithChildren<{
        isVisible: boolean;
        flyoutRef: React.RefObject<HTMLElement>;
        mentionSelection: RangeStatic | null;
    }>,
) {
    const { mentionSelection, isVisible, flyoutRef, children } = props;

    const [flyoutWidth, setFlyoutWidth] = useState<number | null>(null);
    const [flyoutHeight, setFlyoutHeight] = useState<number | null>(null);

    useLayoutEffect(() => {
        setFlyoutWidth(flyoutRef.current ? flyoutRef.current.offsetWidth : null);
        setFlyoutHeight(flyoutRef.current ? flyoutRef.current.offsetHeight : null);
    }, [flyoutRef]);

    return (
        <ToolbarPositioner
            horizontalAlignment="start"
            verticalAlignment="below"
            flyoutWidth={flyoutWidth}
            flyoutHeight={flyoutHeight}
            isActive={isVisible}
            selectionIndex={mentionSelection ? mentionSelection.index : 0}
            selectionLength={mentionSelection ? mentionSelection.length : 0}
        >
            {({ x, y }) => {
                let style: React.CSSProperties = {
                    visibility: "hidden",
                    position: "absolute",
                    zIndex: -1,
                };

                if (x && y) {
                    style = {
                        position: "absolute",
                        top: y.position,
                        left: x.position,
                        zIndex: 10,
                        visibility: "visible",
                    };
                }

                return <div style={style}>{children}</div>;
            }}
        </ToolbarPositioner>
    );
}
