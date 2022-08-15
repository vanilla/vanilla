/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { WidgetSettingsModal } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import { LayoutThumbnailsModal } from "@dashboard/layout/editor/thumbnails/LayoutThumbnailsModal";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutEditorDestinationPath, IWidgetCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { cx } from "@library/styles/styleShim";
import { Icon } from "@vanilla/icons";
import { useFocusOnActivate } from "@vanilla/react-utils";
import React, { useMemo, useRef, useState } from "react";
import { JsonSchema } from "@vanilla/json-schema-forms";
import merge from "lodash/merge";

interface IProps {
    path: ILayoutEditorDestinationPath;
}

export function LayoutEditorAddWidget(props: IProps) {
    const classes = layoutEditorClasses();
    const { editorContents, editorSelection, layoutViewType } = useLayoutEditor();
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const catalog = useLayoutCatalog(layoutViewType);
    const [isModalOpen, setIsModalOpen] = useState(false);

    //widgetSettingsModal
    const [isWidgetSettingsModalOpen, setWidgetSettingsModalOpen] = useState(false);
    const [selectedWidgetID, setSelectedWidgetID] = useState("");

    const widgetSchema = catalog?.widgets[selectedWidgetID]?.schema ?? {};

    const isSelected =
        LayoutEditorPath.areWidgetPathsEqual(props.path, editorSelection.getPath()) &&
        editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET;
    useFocusOnActivate(buttonRef, isSelected);

    const currentSectionID = editorContents.getSection(props.path)?.$hydrate ?? "";
    const currentSectionAllowedItems = catalog?.sections?.[currentSectionID].allowedWidgetIDs ?? null;
    let allowedWidgets = catalog?.widgets ?? {};
    if (currentSectionAllowedItems != null) {
        allowedWidgets = Object.fromEntries(
            Object.entries(allowedWidgets).filter(([widgetID, widget]) => {
                if (currentSectionAllowedItems.includes(widgetID)) {
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
                    setIsModalOpen(true);
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
                    setIsModalOpen(false);
                    editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
                }}
                sections={allowedWidgets}
                onAddSection={(widgetID) => {
                    setWidgetSettingsModalOpen(true);
                    setSelectedWidgetID(widgetID);
                }}
                isVisible={isModalOpen}
                itemType="widgets"
            />
            <WidgetSettingsModal
                exitHandler={() => {
                    setWidgetSettingsModalOpen(false);
                }}
                onSave={(settings) => {
                    editorContents.insertWidget(props.path, {
                        $hydrate: selectedWidgetID,
                        ...settings,
                    });
                    editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
                    setWidgetSettingsModalOpen(false);
                    setIsModalOpen(false);
                }}
                isVisible={isWidgetSettingsModalOpen}
                initialValue={extractDataByKeyLookup(widgetSchema, "default")}
                widgetID={selectedWidgetID}
                widgetCatalog={catalog?.widgets ?? {}}
                middlewaresCatalog={catalog?.middlewares ?? {}}
            />
        </>
    );
}

//get schema object withdefault values only as props for a widget in order to set them in widget previews
export function extractDataByKeyLookup(schema: JsonSchema, keyToLookup: string, path?: string, currentData?: object) {
    let generatedData = currentData ?? {};
    if (schema && schema.type === "object") {
        Object.entries(schema.properties).map(([key, value]: [string, JsonSchema]) => {
            if (value.type === "object") {
                extractDataByKeyLookup(value, keyToLookup, path ? `${path}.${key}` : key, generatedData);
            } else if (value[keyToLookup]) {
                //we have a path, value is nested somewhere in the object
                if (path) {
                    let keys = [...path.split("."), key],
                        newObjectFromCurrentPath = {};

                    //new object creation logic from path
                    let node = keys.slice(0, -1).reduce(function (memo, current) {
                        return (memo[current] = {});
                    }, newObjectFromCurrentPath);

                    //last key where we'll assign our value
                    node[key] = value[keyToLookup];
                    generatedData = merge(generatedData, newObjectFromCurrentPath);
                } else {
                    //its first level value, we just assign it to our object
                    generatedData[key] = value[keyToLookup];
                }
            }
        });
    }
    return generatedData;
}
