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
import { IHydratedEditableWidgetProps } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { WidgetContextProvider } from "@library/layout/Widget";
import { pointerEventsClass } from "@library/styles/styleHelpersFeedback";
import { useFocusOnActivate } from "@vanilla/react-utils";
import React, { useLayoutEffect, useRef } from "react";

interface IProps extends IHydratedEditableWidgetProps {
    children?: React.ReactNode;
}

export function LayoutEditorWidgetWrapper(props: IProps) {
    const { $editorPath, children } = props;
    const { editorSelection } = useLayoutEditor();
    const widgetRef = useRef<HTMLElement | null>(null);
    const isActive =
        editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET &&
        LayoutEditorPath.areWidgetPathsEqual($editorPath, editorSelection.getPath());
    useFocusOnActivate(widgetRef, isActive);
    const classes = layoutEditorClasses();

    useLayoutEffect(() => {
        widgetRef.current?.setAttribute("data-layout-editor-focusable", "");
    }, [widgetRef.current]);

    return (
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
            extraClasses={classes.widget}
            extraContent={
                isActive && (
                    <>
                        <div className={classes.widgetBorder}></div>
                        {LayoutEditorPath.isWidgetPath($editorPath) && <LayoutEditorWidgetToolbar path={$editorPath} />}
                    </>
                )
            }
            childrenWrapperClassName={pointerEventsClass()}
        >
            {children}
        </WidgetContextProvider>
    );
}
