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
import { extractSchemaDefaults } from "@vanilla/json-schema-forms";
import { ClearThemeOverrideContext } from "@library/theming/ThemeOverrideContext";

interface IProps {
    path: ILayoutEditorDestinationPath;
}

export function LayoutEditorAddWidget(props: IProps) {
    const classes = layoutEditorClasses.useAsHook();
    const { editorContents, editorSelection, layoutViewType } = useLayoutEditor();
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const catalog = useLayoutCatalog(layoutViewType);
    const [widgetSelectionModalOpen, setWidgetSelectionModalOpen] = useState(false);
    const [widgetSettingsModalOpen, setWidgetSettingsModalOpen] = useState(false);
    const [selectedWidgetID, setSelectedWidgetID] = useState<string | undefined>(undefined);

    const isSelected =
        LayoutEditorPath.areWidgetPathsEqual(props.path, editorSelection.getPath()) &&
        editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET;
    useFocusOnActivate(buttonRef, isSelected);

    const currentSectionID = editorContents.getSection(props.path)?.$hydrate ?? "";
    const currentSectionAllowedItems = catalog?.sections?.[currentSectionID].allowedWidgetIDs ?? null;
    // Breadcrumbs are special in this iteration. They are added to a specific section region and have no config.
    const allowedWidgetIDs = currentSectionAllowedItems?.filter((id) => id !== "react.breadcrumbs") ?? [];
    let allowedWidgetCatalog = catalog?.widgets ?? {};
    if (currentSectionAllowedItems != null) {
        allowedWidgetCatalog = Object.fromEntries(
            Object.entries(allowedWidgetCatalog).filter(([widgetID, widget]) => {
                if (allowedWidgetIDs.includes(widgetID)) {
                    return true;
                } else {
                    return false;
                }
            }),
        );
    }

    // Add non-required assets as well
    for (const [assetID, asset] of Object.entries(catalog?.assets ?? {})) {
        if (!asset.isRequired) {
            allowedWidgetCatalog[assetID] = asset;
        }
    }

    const widgetSchema = selectedWidgetID ? allowedWidgetCatalog[selectedWidgetID]?.schema : undefined;
    const widgetName = selectedWidgetID ? allowedWidgetCatalog[selectedWidgetID]?.name : undefined;

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
                    <Icon icon={"add"} />
                </div>
            </Button>
            <ClearThemeOverrideContext>
                <LayoutThumbnailsModal
                    title="Choose your Widget"
                    exitHandler={() => {
                        setWidgetSettingsModalOpen(false);
                        setWidgetSelectionModalOpen(false);
                        editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
                    }}
                    sections={allowedWidgetCatalog}
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
                        initialValues={extractSchemaDefaults(widgetSchema)}
                        widgetID={selectedWidgetID!}
                        layoutCatalog={catalog}
                    />
                )}
            </ClearThemeOverrideContext>
        </>
    );
}
