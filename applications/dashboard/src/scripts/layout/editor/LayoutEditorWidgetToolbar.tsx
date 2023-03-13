/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorDirection, LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { WidgetSettingsModal } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutEditorWidgetPath, IWidgetCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { cx } from "@emotion/css";
import { EmbedMenu } from "@library/editor/pieces/EmbedMenu";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React, { useMemo, useState } from "react";

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
    const { $hydrate, ...widgetProps } = widgetSpec ?? {};
    const [isWidgetSettingsModalOpen, setWidgetSettingsModalOpen] = useState(false);
    const isAsset = $hydrate?.includes("asset");

    const trashButton = (
        <EmbedButton
            disabled={isAsset}
            onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                editorContents.deleteWidget(props.path);
                editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
            }}
        >
            <Icon icon={"data-trash"} />
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
                        setWidgetSettingsModalOpen(true);
                    }}
                >
                    <Icon icon={"data-pencil"} />
                </EmbedButton>
                <ConditionalWrap
                    component={ToolTip}
                    condition={!!isAsset}
                    componentProps={{ label: t("You cannot delete this required widget") }}
                >
                    {isAsset ? <span>{trashButton}</span> : trashButton}
                </ConditionalWrap>
            </EmbedMenu>
            <WidgetSettingsModal
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
                initialValue={widgetProps}
                widgetCatalog={catalog?.widgets ?? {}}
                middlewaresCatalog={catalog?.middlewares ?? {}}
                widgetID={$hydrate ?? ""}
                assetCatalog={catalog?.assets}
            />
        </>
    );
}
