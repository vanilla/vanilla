/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { LayoutEditorWidgetToolbar } from "@dashboard/layout/editor/LayoutEditorWidgetToolbar";
import {
    IHydratedEditableWidgetProps,
    type ILayoutEditorSpecialWidgetPath,
    type ILayoutEditorWidgetPath,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { useDraggable } from "@dnd-kit/core";
import { WidgetContextProvider } from "@library/layout/LayoutWidget";
import { useStickyContext } from "@library/modal/StickyContext";
import { pointerEventsClass } from "@library/styles/styleHelpersFeedback";
import { ClearThemeOverrideContext } from "@library/theming/ThemeOverrideContext";
import { useFocusOnActivate } from "@vanilla/react-utils";
import type { CSSProperties } from "react";
import React, { useEffect, useLayoutEffect, useRef } from "react";

interface IProps extends Omit<IHydratedEditableWidgetProps, "$editorPath"> {
    children?: React.ReactNode;
    $editorPath: ILayoutEditorWidgetPath;
}

export function LayoutEditorWidgetWrapper(props: IProps) {
    if (LayoutEditorPath.isSpecialWidgetPath(props.$editorPath)) {
        return <LayoutEditorSpecialWidgetWrapper {...props} />;
    } else {
        return <LayoutEditorRegularWidgetWrapper {...props} />;
    }
}

function LayoutEditorSpecialWidgetWrapper(props: IProps) {
    const { $editorPath, children } = props;
    const { editorSelection } = useLayoutEditor();
    const widgetRef = useRef<HTMLElement | null>(null);
    const isActive =
        editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET &&
        LayoutEditorPath.areWidgetPathsEqual($editorPath, editorSelection.getPath());
    useFocusOnActivate(widgetRef, isActive);
    const classes = layoutEditorClasses.useAsHook();
    return (
        <WidgetContextProvider
            widgetRef={widgetRef}
            onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                editorSelection.moveSelectionTo(
                    "TitleBar",
                    // Toggle between the two.
                    LayoutEditorSelectionMode.WIDGET,
                );
            }}
            extraContent={
                isActive && (
                    <>
                        <div className={classes.widgetBorder}></div>
                        <ClearThemeOverrideContext>
                            <LayoutEditorWidgetToolbar noDrag noDelete positionAbsolute={true} path={$editorPath} />
                        </ClearThemeOverrideContext>
                    </>
                )
            }
        >
            {props.children}
        </WidgetContextProvider>
    );
}

function LayoutEditorRegularWidgetWrapper(props: IProps) {
    const { $editorPath, children } = props;
    const { editorSelection } = useLayoutEditor();
    const widgetRef = useRef<HTMLElement | null>(null);
    const isActive =
        editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET &&
        LayoutEditorPath.areWidgetPathsEqual($editorPath, editorSelection.getPath());
    useFocusOnActivate(widgetRef, isActive);
    const classes = layoutEditorClasses.useAsHook();

    const isWidgetSelected = LayoutEditorPath.isWidgetPath($editorPath);
    useLayoutEffect(() => {
        widgetRef.current?.setAttribute("data-layout-editor-focusable", "");
    }, [widgetRef.current]);

    const draggable = useDraggable({
        id: LayoutEditorPath.draggableID($editorPath),
        data: {
            $editorPath,
            isActiveWidget: isActive,
        },
    });

    const draggableStyle: CSSProperties = draggable.isDragging
        ? {
              opacity: 0.5,
          }
        : {};

    const stickyContext = useStickyContext();

    return (
        <>
            <WidgetContextProvider
                onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    editorSelection.moveSelectionTo(
                        props.$editorPath,
                        // Toggle between the two.
                        isActive ? LayoutEditorSelectionMode.SECTION : LayoutEditorSelectionMode.WIDGET,
                    );
                }}
                tabIndex={isActive ? 0 : -1}
                widgetRef={widgetRef}
                widgetRefFn={draggable.setNodeRef}
                extraClasses={classes.widget}
                extraProps={{
                    ...draggable.attributes,
                    ...draggable.listeners,
                    style: draggableStyle,
                    ...{
                        "data-draggable": "widget",
                    },
                }}
                extraContent={
                    isActive && (
                        <>
                            <div className={classes.widgetBorder}></div>
                        </>
                    )
                }
                childrenWrapperClassName={pointerEventsClass()}
                inert={true}
            >
                {children}
                {isActive &&
                    isWidgetSelected &&
                    !draggable.isDragging &&
                    stickyContext.mountStickyPortal(
                        <ClearThemeOverrideContext>
                            <LayoutEditorWidgetToolbar
                                dragAttributes={{ ...draggable.listeners, ...draggable.listeners }}
                                positionRelativeTo={widgetRef.current}
                                path={$editorPath}
                                ref={draggable.setActivatorNodeRef}
                            />
                        </ClearThemeOverrideContext>,
                    )}
            </WidgetContextProvider>
        </>
    );
}
