/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { ILayoutEditorDestinationPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { cx } from "@library/styles/styleShim";
import { Icon } from "@vanilla/icons";
import { useFocusOnActivate, useLastValue } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { useEffect, useLayoutEffect, useRef } from "react";

interface IProps {
    path: ILayoutEditorDestinationPath;
}

export function LayoutEditorAddWidget(props: IProps) {
    const classes = layoutEditorClasses();
    const { editorContents, editorSelection } = useLayoutEditor();
    const buttonRef = useRef<HTMLButtonElement | null>(null);

    const isSelected =
        LayoutEditorPath.areWidgetPathsEqual(props.path, editorSelection.getPath()) &&
        editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET;
    useFocusOnActivate(buttonRef, isSelected);

    // Temp hack
    const isFullWidth = editorContents.isSectionFullWidth(props.path);
    return (
        <Button
            data-layout-editor-focusable
            buttonRef={buttonRef}
            tabIndex={-1}
            buttonType={ButtonTypes.CUSTOM}
            onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                editorContents.insertWidget(
                    props.path,
                    isFullWidth
                        ? {
                              $hydrate: "react.app-banner",
                              isFullWidth: isFullWidth,
                          }
                        : {
                              $hydrate: "react.html",
                          },
                );
                editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
            }}
            className={classes.addWidget}
        >
            <div className={cx(classes.buttonCircle, "buttonCircle")}>
                <Icon icon={"data-add"} />
            </div>
        </Button>
    );
}
