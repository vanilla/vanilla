/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { cx } from "@emotion/css";
import { EmbedMenu } from "@library/editor/pieces/EmbedMenu";
import { useMeasure } from "@vanilla/react-utils";
import { useEffect, useState } from "react";

export function LayoutEditorToolbar(props: {
    positionAbsolute?: boolean;
    positionRelativeTo?: HTMLElement | null;
    children?: React.ReactNode;
}) {
    const { positionRelativeTo, children } = props;
    const [measureableRef, setMeasureableRef] = useState<React.RefObject<HTMLElement>>({
        current: null,
    });
    const { editorSelection } = useLayoutEditor();

    // We need an extra set state on mount to ensure we get a ref into our state

    const [mountCount, setMountCount] = useState(0);
    useEffect(() => {
        setMountCount((prev) => prev + 1);
    }, [editorSelection.getPath()]);

    useEffect(() => {
        if (positionRelativeTo) {
            setMeasureableRef({ current: positionRelativeTo });
        }
    }, [positionRelativeTo, mountCount]);

    const widgetMeasure = useMeasure(measureableRef, { watchScroll: true, watchRef: true });

    const titleBarGap = 56;
    const toolbarHeight = 68;
    const placementAbove = widgetMeasure.top - toolbarHeight; // Subtrack the height of the toolbar

    const placementStickyUntilGone =
        widgetMeasure.top + widgetMeasure.height < toolbarHeight
            ? widgetMeasure.bottom
            : Math.max(titleBarGap, placementAbove);

    const widgetXCenter = widgetMeasure.left + widgetMeasure.width / 2;
    const classes = layoutEditorClasses.useAsHook();

    return (
        <div
            onKeyDown={(e) => {
                if (e.key === " " || e.key === "Enter") {
                    e.stopPropagation();
                }
            }}
        >
            <EmbedMenu
                data-layout-editor-focus-container
                onClick={(e) => {
                    // Prevent this click from bubbling up.
                    e.preventDefault();
                    e.stopPropagation();
                }}
                className={cx(classes.toolbarMenu)}
                style={
                    props.positionAbsolute
                        ? { position: "absolute", top: "16px", left: "50%", transform: "translate(-50%, 0)" }
                        : {
                              visibility: widgetMeasure.width === 0 ? "hidden" : "visible",
                              position: "fixed",
                              top: 0,
                              left: widgetXCenter,
                              zIndex: 1051, // Just over the Vanilla TitleBar but under the admin titlebar.
                              transform: `translate(-50%, ${placementStickyUntilGone}px)`,
                          }
                }
            >
                {children}
            </EmbedMenu>
        </div>
    );
}
