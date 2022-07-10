/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { LayoutEditorDirection, LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { ILayoutEditorWidgetPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { EmbedMenu } from "@rich-editor/editor/pieces/EmbedMenu";
import { Icon } from "@vanilla/icons";
import React from "react";

interface IProps {
    path: ILayoutEditorWidgetPath;
}

export function LayoutEditorWidgetToolbar(props: IProps) {
    const { editorContents, editorSelection } = useLayoutEditor();
    const pathRight = editorSelection.getWidgetPathInDirection(props.path, LayoutEditorDirection.RIGHT);
    const pathLeft = editorSelection.getWidgetPathInDirection(props.path, LayoutEditorDirection.LEFT);
    const pathDown = editorSelection.getWidgetPathInDirection(props.path, LayoutEditorDirection.DOWN);
    const pathUp = editorSelection.getWidgetPathInDirection(props.path, LayoutEditorDirection.UP);

    return (
        <EmbedMenu
            onClick={(e) => {
                // Prevent this click from bubbling up.
                e.preventDefault();
                e.stopPropagation();
            }}
        >
            <EmbedButton
                disabled={!pathLeft}
                onClick={() => {
                    if (!pathLeft) {
                        return;
                    }
                    editorContents.moveWidget(props.path, pathLeft);
                    editorSelection.moveSelectionTo(pathLeft, LayoutEditorSelectionMode.WIDGET);
                }}
            >
                <Icon icon={"data-left"} />
            </EmbedButton>
            <EmbedButton
                disabled={!pathUp}
                onClick={() => {
                    if (!pathUp) {
                        return;
                    }
                    editorContents.moveWidget(props.path, pathUp);
                    editorSelection.moveSelectionTo(pathUp, LayoutEditorSelectionMode.WIDGET);
                }}
            >
                <Icon icon={"data-up"} />
            </EmbedButton>
            <EmbedButton
                disabled={!pathDown}
                onClick={() => {
                    if (!pathDown) {
                        return;
                    }
                    editorContents.moveWidget(props.path, pathDown);
                    editorSelection.moveSelectionTo(pathDown, LayoutEditorSelectionMode.WIDGET);
                }}
            >
                <Icon icon={"data-down"} />
            </EmbedButton>
            <EmbedButton
                disabled={!pathRight}
                onClick={() => {
                    if (!pathRight) {
                        return;
                    }
                    editorContents.moveWidget(props.path, pathRight);
                    editorSelection.moveSelectionTo(pathRight, LayoutEditorSelectionMode.WIDGET);
                }}
            >
                <Icon icon={"data-right"} />
            </EmbedButton>
            <EmbedButton
                onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    editorContents.deleteWidget(props.path);
                    editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
                }}
            >
                <Icon icon={"data-trash"} />
            </EmbedButton>
        </EmbedMenu>
    );
}
