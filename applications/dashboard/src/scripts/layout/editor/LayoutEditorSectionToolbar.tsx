/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { IEditableLayoutWidget, ILayoutEditorPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { globalVariables } from "@library/styles/globalStyleVars";
import { EmbedMenu } from "@rich-editor/editor/pieces/EmbedMenu";
import { Icon } from "@vanilla/icons";
import React from "react";

interface IProps {
    path: ILayoutEditorPath;
    offset?: number;
    allowColumnInvert?: boolean;
}

export function LayoutEditorSectionToolbar(props: IProps) {
    const { editorContents, editorSelection } = useLayoutEditor();
    const classes = layoutEditorClasses();
    const isFirstSection = props.path.sectionIndex === 0;
    const isLastSection = props.path.sectionIndex === editorContents.getSectionCount() - 1;
    const globalVars = globalVariables();
    let offsetLeft = (document.body.clientWidth - globalVars.contentWidth - globalVars.gutter.size * 2) / 2;
    offsetLeft = Math.max(globalVars.gutter.size, offsetLeft);
    return (
        <EmbedMenu
            className={classes.toolbarOffset(offsetLeft)}
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
            {props.allowColumnInvert && (
                <EmbedButton
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const section = editorContents.getSection(props.path);
                        const isInverted = section?.isInverted ? !section.isInverted : true;
                        const newSpec = { ...section, isInverted: isInverted } as IEditableLayoutWidget;
                        editorContents.modifySection(props.path.sectionIndex, newSpec);
                    }}
                    ariaLabel={"Invert the secondary column alignment between left and right."}
                >
                    <Icon icon={"data-swap"} />
                </EmbedButton>
            )}
            <EmbedButton
                onClick={() => {
                    editorContents.deleteSection(props.path.sectionIndex);
                }}
            >
                <Icon icon={"data-trash"} />
            </EmbedButton>
        </EmbedMenu>
    );
}
