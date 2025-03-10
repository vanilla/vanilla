/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorDirection, LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { WidgetSettingsModal } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutEditorWidgetPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { cx } from "@emotion/css";
import { EmbedMenu } from "@library/editor/pieces/EmbedMenu";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { extractSchemaDefaults } from "@library/json-schema-forms";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useState } from "react";
import isEmpty from "lodash/isEmpty";
import { mergeAndReplaceArrays } from "@vanilla/utils";

interface IProps {
    path: ILayoutEditorWidgetPath;
}

export function LayoutEditorWidgetToolbar(props: IProps) {
    const { editorContents, editorSelection, layoutViewType } = useLayoutEditor();
    const pathRight = editorSelection.getWidgetPathInDirection(props.path, LayoutEditorDirection.RIGHT);
    const pathLeft = editorSelection.getWidgetPathInDirection(props.path, LayoutEditorDirection.LEFT);
    const pathDown = editorSelection.getWidgetPathInDirection(props.path, LayoutEditorDirection.DOWN);
    const pathUp = editorSelection.getWidgetPathInDirection(props.path, LayoutEditorDirection.UP);

    const catalog = useLayoutCatalog(layoutViewType);
    const widgetSpec = editorContents.getWidget(props.path);
    const { $hydrate, ...rest } = widgetSpec ?? {};
    const [isWidgetSettingsModalOpen, setWidgetSettingsModalOpen] = useState(false);
    const widget = $hydrate ? catalog?.assets[$hydrate] ?? catalog?.widgets[$hydrate] : undefined;
    const isRequired = widget?.isRequired ?? false;
    const widgetProps = isEmpty(rest) ? undefined : rest;

    const trashButton = (
        <EmbedButton
            disabled={isRequired}
            onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                editorContents.deleteWidget(props.path);
                editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
            }}
        >
            <Icon icon={"delete"} />
        </EmbedButton>
    );

    return (
        <>
            <EmbedMenu
                onClick={(e) => {
                    // Prevent this click from bubbling up.
                    e.preventDefault();
                    e.stopPropagation();
                }}
                className={cx(layoutEditorClasses().toolbarMenu, "layoutEditorToolbarMenu")}
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
                    <Icon icon={"move-left"} />
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
                    <Icon icon={"move-up"} />
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
                    <Icon icon={"move-down"} />
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
                    <Icon icon={"move-right"} />
                </EmbedButton>
                <EmbedButton
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        setWidgetSettingsModalOpen(true);
                    }}
                >
                    <Icon icon={"edit"} />
                </EmbedButton>
                <ConditionalWrap
                    component={ToolTip}
                    condition={!!isRequired}
                    componentProps={{ label: t("You cannot delete this required widget") }}
                >
                    {isRequired ? <span>{trashButton}</span> : trashButton}
                </ConditionalWrap>
            </EmbedMenu>
            {!!catalog && !!$hydrate && !!widget && (
                <WidgetSettingsModal
                    key={$hydrate}
                    exitHandler={() => {
                        setWidgetSettingsModalOpen(false);
                    }}
                    onSave={(settings) => {
                        editorContents.modifyWidget(props.path, {
                            $hydrate: $hydrate,
                            ...settings,
                        });
                        editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
                        setWidgetSettingsModalOpen(false);
                    }}
                    isVisible={isWidgetSettingsModalOpen}
                    schema={widget.schema}
                    name={widget.name}
                    initialValues={mergeAndReplaceArrays(extractSchemaDefaults(widget.schema), widgetProps ?? {})}
                    widgetCatalog={catalog.widgets}
                    middlewaresCatalog={catalog.middlewares ?? {}}
                    widgetID={$hydrate}
                    assetCatalog={catalog.assets}
                />
            )}
        </>
    );
}
