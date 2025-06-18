/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { LayoutEditorToolbar } from "@dashboard/layout/editor/LayoutEditorToolbar";
import { LayoutEditorWidgetMeta } from "@dashboard/layout/editor/LayoutEditorWidgetMeta";
import { LayoutSectionInfos } from "@dashboard/layout/editor/LayoutSectionInfos";
import {
    IEditableLayoutWidget,
    ILayoutEditorPath,
    type ILayoutEditorSectionPath,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React from "react";

interface IProps {
    path: ILayoutEditorSectionPath;
    positionRelativeTo?: HTMLElement | null;
    allowColumnInvert?: boolean;
}

export function LayoutEditorSectionToolbar(props: IProps) {
    const { editorContents, editorSelection } = useLayoutEditor();
    const isFirstSection = props.path.sectionIndex === 0;
    const isLastSection = props.path.sectionIndex === editorContents.getSectionCount() - 1;
    const globalVars = globalVariables();
    let offsetLeft = (document.body.clientWidth - globalVars.contentWidth - globalVars.gutter.size * 2) / 2;
    offsetLeft = Math.max(globalVars.gutter.size, offsetLeft);

    const section = editorContents.getSection(props.path);
    const hasAnyChildren = section
        ? LayoutSectionInfos[section.$hydrate].regionNames.some((region) => {
              return section[region] && Array.isArray(section[region]) && section[region].length > 0;
          })
        : false;

    let trashButton = (
        <Button
            buttonType={ButtonTypes.ICON}
            onClick={() => {
                editorContents.deleteSection(props.path.sectionIndex);
            }}
            disabled={hasAnyChildren}
        >
            <Icon icon={"delete"} />
        </Button>
    );

    if (hasAnyChildren) {
        trashButton = (
            <ToolTip
                label={t(
                    "This section contains a widget and cannot be deleted. The widget may be hidden by your role preview settings.",
                )}
            >
                <span>{trashButton}</span>
            </ToolTip>
        );
    }

    const hasBreadcrumb = editorContents.hasBreadcrumb(props.path);

    const toggleBreadcrumbOnSection = () => {
        const breadcrumbPath = {
            ...props.path,
            sectionRegion: editorContents.isSectionOneColumn(props.path) ? "children" : "breadcrumbs",
            sectionRegionIndex: 0,
        };
        if (!hasBreadcrumb) {
            editorContents.insertWidget(breadcrumbPath, {
                $hydrate: "react.breadcrumbs",
            });
        } else {
            editorContents.deleteWidget(breadcrumbPath);
        }
    };

    const breadcrumbButton = !editorContents.isSectionFullWidth(props.path) && (
        <ToolTip label={hasBreadcrumb ? t("Hide breadcrumbs on this section") : t("Show breadcrumbs on this section")}>
            <span>
                <Button
                    buttonType={ButtonTypes.ICON}
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        toggleBreadcrumbOnSection();
                    }}
                >
                    {hasBreadcrumb ? (
                        <Icon icon={"navigation-breadcrumb-active"} />
                    ) : (
                        <Icon icon={"navigation-breadcrumb-inactive"} />
                    )}
                </Button>
            </span>
        </ToolTip>
    );

    return (
        <LayoutEditorToolbar positionRelativeTo={props.positionRelativeTo}>
            <LayoutEditorWidgetMeta widgetPath={props.path} />
            <Button
                buttonType={ButtonTypes.ICON}
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
                <Icon icon={"move-up"} />
            </Button>
            <Button
                buttonType={ButtonTypes.ICON}
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
                <Icon icon={"move-down"} />
            </Button>
            {props.allowColumnInvert && (
                <Button
                    buttonType={ButtonTypes.ICON}
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
                    <Icon icon={"swap"} />
                </Button>
            )}
            {breadcrumbButton}
            {trashButton}
        </LayoutEditorToolbar>
    );
}
