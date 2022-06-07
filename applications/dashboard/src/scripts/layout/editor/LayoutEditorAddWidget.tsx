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
import React, { useRef, useState } from "react";

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
    const [selectedWidget, setSelectedWidget] = useState<IWidgetCatalog | {}>({});

    const isSelected =
        LayoutEditorPath.areWidgetPathsEqual(props.path, editorSelection.getPath()) &&
        editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET;
    useFocusOnActivate(buttonRef, isSelected);

    // Temp hack
    const isFullWidth = editorContents.isSectionFullWidth(props.path);
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
                sections={catalog?.widgets ?? {}}
                onAddSection={(widgetID) => {
                    setWidgetSettingsModalOpen(true);
                    setSelectedWidgetID(widgetID);
                    setSelectedWidget(catalog?.widgets[widgetID] ?? {});
                }}
                isVisible={isModalOpen}
                itemType="widgets"
            />
            <WidgetSettingsModal
                exitHandler={() => {
                    setWidgetSettingsModalOpen(false);
                }}
                onSave={(settings) => {
                    editorContents.insertWidget(
                        props.path,
                        isFullWidth
                            ? {
                                  $hydrate: "react.app-banner",
                                  isFullWidth: isFullWidth,
                              }
                            : {
                                  $hydrate: selectedWidgetID,
                                  ...settings,
                              },
                    );
                    editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
                    setWidgetSettingsModalOpen(false);
                    setIsModalOpen(false);
                }}
                isVisible={isWidgetSettingsModalOpen}
                initialValue={{}}
                widgetID={selectedWidgetID}
                widgetCatalog={catalog?.widgets ?? {}}
            />
        </>
    );
}
