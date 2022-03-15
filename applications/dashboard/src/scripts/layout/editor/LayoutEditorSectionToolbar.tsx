/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { ILayoutEditorPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { globalVariables } from "@library/styles/globalStyleVars";
import { EmbedMenu } from "@rich-editor/editor/pieces/EmbedMenu";
import { Icon } from "@vanilla/icons";
import React from "react";

interface IProps {
    path: ILayoutEditorPath;
    offset?: number;
}

export function LayoutEditorSectionToolbar(props: IProps) {
    const { editorContents, editorSelection } = useLayoutEditor();
    const classes = layoutEditorClasses();
    const isFirstSection = props.path.sectionIndex === 0;
    const isLastSection = props.path.sectionIndex === editorContents.getSectionCount() - 1;
    const globalVars = globalVariables();
    let leftOffset = (document.body.clientWidth - globalVars.contentWidth - globalVars.gutter.size * 2) / 2;
    leftOffset = Math.max(globalVars.gutter.size, leftOffset);
    return (
        <div
            className={
                // Uncomment in case we want the vertical toolbar.
                // ""
                classes.sectionToolbar
            }
        >
            <EmbedMenu
                className={classes.toolbarOffset}
                // Uncomment in case we want the vertical toolbar.
                // style={{
                //     position: "absolute",
                //     flexDirection: "column",
                //     left: leftOffset,
                //     top: "50%",
                //     transform: "translateY(-50%)",
                //     right: "initial",
                //     bottom: "initial",
                // }}
                onClick={(e) => {
                    // Prevent this click from bubbling up.
                    e.preventDefault();
                    e.stopPropagation();
                }}
            >
                <EmbedButton
                    disabled={isFirstSection}
                    onClick={() => {
                        const newPath: ILayoutEditorPath = {
                            ...props.path,
                            sectionIndex: props.path.sectionIndex - 1,
                        };
                        editorContents.moveSection(props.path, newPath);
                        editorSelection.moveSelectionTo(newPath, LayoutEditorSelectionMode.SECTION);
                    }}
                >
                    <Icon icon={"data-up"} />
                </EmbedButton>
                <EmbedButton
                    disabled={isLastSection}
                    onClick={() => {
                        const newPath: ILayoutEditorPath = {
                            ...props.path,
                            sectionIndex: props.path.sectionIndex + 1,
                        };
                        editorContents.moveSection(props.path, newPath);
                        editorSelection.moveSelectionTo(newPath, LayoutEditorSelectionMode.SECTION);
                    }}
                >
                    <Icon icon={"data-down"} />
                </EmbedButton>
                <EmbedButton
                    onClick={() => {
                        editorContents.deleteSection(props.path.sectionIndex);
                    }}
                >
                    <Icon icon={"data-trash"} />
                </EmbedButton>
            </EmbedMenu>
        </div>
    );
}
