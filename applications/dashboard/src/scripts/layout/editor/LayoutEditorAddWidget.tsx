/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { WidgetSettingsModal } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import { LayoutThumbnailsModal } from "@dashboard/layout/editor/thumbnails/LayoutThumbnailsModal";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutEditorDestinationPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { cx } from "@library/styles/styleShim";
import { Icon } from "@vanilla/icons";
import { useFocusOnActivate } from "@vanilla/react-utils";
import React, { useRef, useState } from "react";
import { extractDataByKeyLookup } from "@vanilla/json-schema-forms";

interface IProps {
    path: ILayoutEditorDestinationPath;
}

export function LayoutEditorAddWidget(props: IProps) {
    const classes = layoutEditorClasses();
    const { editorContents, editorSelection, layoutViewType } = useLayoutEditor();
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const catalog = useLayoutCatalog(layoutViewType);
    const [widgetSelectionModalOpen, setWidgetSelectionModalOpen] = useState(false);
    const [widgetSettingsModalOpen, setWidgetSettingsModalOpen] = useState(false);
    const [selectedWidgetID, setSelectedWidgetID] = useState<string | undefined>(undefined);

    const widgetSchema = selectedWidgetID ? catalog?.widgets[selectedWidgetID]?.schema : undefined;
    const widgetName = selectedWidgetID ? catalog?.widgets[selectedWidgetID]?.name : undefined;

    const isSelected =
        LayoutEditorPath.areWidgetPathsEqual(props.path, editorSelection.getPath()) &&
        editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET;
    useFocusOnActivate(buttonRef, isSelected);

    const currentSectionID = editorContents.getSection(props.path)?.$hydrate ?? "";
    const currentSectionAllowedItems = catalog?.sections?.[currentSectionID].allowedWidgetIDs ?? null;
    // Breadcrumbs are special in this iteration. They are added to a specific section region and have no config.
    const allowedWidgetIDs = currentSectionAllowedItems?.filter((id) => id !== "react.breadcrumbs") ?? [];
    let allowedWidgets = catalog?.widgets ?? {};
    if (currentSectionAllowedItems != null) {
        allowedWidgets = Object.fromEntries(
            Object.entries(allowedWidgets).filter(([widgetID, widget]) => {
                if (allowedWidgetIDs.includes(widgetID)) {
                    return true;
                } else {
                    return false;
                }
            }),
        );
    }
    return (
        <>
            <Button
                data-layout-editor-focusable
                buttonRef={buttonRef}
                tabIndex={isSelected ? 0 : -1}
                buttonType={ButtonTypes.CUSTOM}
                ariaLabel={"Add widget"}
                onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
                    setWidgetSelectionModalOpen(true);
                }}
                className={classes.addWidget}
            >
                <div className={cx(classes.buttonCircle, "buttonCircle")}>
                    <Icon icon={"data-add"} />
                </div>
            </Button>
            <LayoutThumbnailsModal
                title="Choose your Widget"
                exitHandler={() => {
                    if (!widgetSettingsModalOpen) {
                        setWidgetSelectionModalOpen(false);
                        editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
                    }
                }}
                sections={allowedWidgets}
                onAddSection={(widgetID) => {
                    setWidgetSettingsModalOpen(true);
                    setSelectedWidgetID(widgetID);
                }}
                isVisible={widgetSelectionModalOpen}
                itemType="widgets"
            />
            {!!widgetSchema && (
                <WidgetSettingsModal
                    key={selectedWidgetID!}
                    exitHandler={() => {
                        setWidgetSettingsModalOpen(false);
                    }}
                    onSave={(settings) => {
                        editorContents.insertWidget(props.path, {
                            $hydrate: selectedWidgetID,
                            ...settings,
                        });
                        editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
                        setWidgetSelectionModalOpen(false);
                        setSelectedWidgetID(undefined);
                    }}
                    isVisible={widgetSettingsModalOpen}
                    schema={widgetSchema}
                    name={widgetName!}
                    initialValues={extractDataByKeyLookup(widgetSchema, "default")}
                    widgetID={selectedWidgetID!}
                    widgetCatalog={catalog?.widgets ?? {}}
                    middlewaresCatalog={catalog?.middlewares ?? {}}
                />
            )}
        </>
    );
}
