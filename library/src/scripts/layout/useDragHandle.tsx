/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";
import { useCallback, useLayoutEffect, useRef, useState } from "react";

export function useDragHandle(params: {
    initialWidthPercentage: number;
    onWidthPercentageChange: (newWidth: number) => void;
}) {
    const { initialWidthPercentage, onWidthPercentageChange } = params;
    // Initialize the files width.
    const mainPanelRef = useRef<HTMLDivElement>(null);
    useLayoutEffect(() => {
        if (mainPanelRef.current) {
            mainPanelRef.current.style.width = `${initialWidthPercentage}%`;
        }
    }, [mainPanelRef.current]);

    const [isDragging, setIsDragging] = useState(false);
    const handleResizeStart = useCallback((e: React.MouseEvent | React.TouchEvent) => {
        e.preventDefault();
        setIsDragging(true);

        const filesElement = mainPanelRef.current;
        if (!filesElement) return;

        const parentElement = filesElement.parentElement;
        if (!parentElement) return;

        // Get initial positions
        const initialX = "touches" in e ? e.touches[0].clientX : e.clientX;
        const initialWidth = filesElement.offsetWidth;

        function handleMove(e: MouseEvent | TouchEvent) {
            const filesElement = mainPanelRef.current;
            if (!filesElement) return;

            const parentElement = filesElement.parentElement;
            if (!parentElement) return;

            // Get current X position
            const currentX = "touches" in e ? e.touches[0].clientX : e.clientX;

            // Calculate the distance moved
            const deltaX = currentX - initialX;

            // Calculate new width as a percentage of parent
            const newWidth = initialWidth + deltaX;
            const parentWidth = parentElement.offsetWidth;
            const newPercentage = (newWidth / parentWidth) * 100;

            // Constrain between 20% and 80%
            const constrainedPercentage = Math.min(Math.max(newPercentage, 20), 80);

            // Apply the new width
            filesElement.style.width = `${constrainedPercentage}%`;
        }

        function handleEnd() {
            setIsDragging(false);
            document.removeEventListener("mousemove", handleMove);
            document.removeEventListener("mouseup", handleEnd);
            document.removeEventListener("touchmove", handleMove);
            document.removeEventListener("touchend", handleEnd);

            // Save the final width to settings
            if (mainPanelRef.current) {
                const finalWidth = parseFloat(mainPanelRef.current.style.width);
                onWidthPercentageChange(finalWidth);
            }
        }

        document.addEventListener("mousemove", handleMove);
        document.addEventListener("mouseup", handleEnd);
        document.addEventListener("touchmove", handleMove);
        document.addEventListener("touchend", handleEnd);
    }, []);

    const dragHandle = (
        <div
            className={classes.resizeHandle}
            onMouseDown={handleResizeStart}
            onTouchStart={handleResizeStart}
            data-dragging={isDragging ? "true" : undefined}
        >
            {isDragging && <div style={{ height: "100vh", width: "100vw" }} />}
        </div>
    );

    return {
        dragHandle,
        mainPanelRef,
        isDragging,
    };
}
const classes = {
    resizeHandle: css({
        height: "100%",
        width: 1,
        backgroundColor: ColorsUtils.var(ColorVar.InputBorder),
        cursor: "col-resize",
        position: "relative",

        "&:hover": {
            backgroundColor: ColorsUtils.var(ColorVar.InputBorderActive),
        },
        "&:active": {
            backgroundColor: ColorsUtils.var(ColorVar.Primary),
            boxShadow: `0 0 0 2px ${ColorsUtils.var(ColorVar.Primary)}`,
        },

        "&:after": {
            content: `""`,
            display: "block",
            position: "absolute",
            top: 0,
            left: -12,
            right: -12,
            bottom: 0,
            zIndex: 100,
        },
        "&[data-dragging]": {
            zIndex: 9999,
        },
        "&[data-dragging]:after": {
            position: "fixed",
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
        },
    }),
};
