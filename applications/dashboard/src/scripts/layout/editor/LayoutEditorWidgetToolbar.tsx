/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { LayoutEditorToolbar } from "@dashboard/layout/editor/LayoutEditorToolbar";
import { LayoutEditorWidgetMeta } from "@dashboard/layout/editor/LayoutEditorWidgetMeta";
import { WidgetSettingsModal } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutEditorWidgetPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { extractSchemaDefaults } from "@library/json-schema-forms";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { useWidgetContext } from "@library/layout/LayoutWidget";
import { ClearThemeOverrideContext } from "@library/theming/ThemeOverrideContext";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { mergeAndReplaceArrays } from "@vanilla/utils";
import isEmpty from "lodash-es/isEmpty";
import { forwardRef, useState } from "react";

interface IProps {
    path: ILayoutEditorWidgetPath;
    positionRelativeTo?: HTMLElement | null;
    positionAbsolute?: boolean;
    noDrag?: boolean;
    noDelete?: boolean;
    dragAttributes?: React.HTMLAttributes<any>;
}

export const LayoutEditorWidgetToolbar = forwardRef(function LayoutEditorWidgetToolbar(
    props: IProps,
    ref: React.Ref<HTMLDivElement>,
) {
    const { editorContents, editorSelection, layoutViewType } = useLayoutEditor();

    const catalog = useLayoutCatalog(layoutViewType);
    const widgetSpec = editorContents.getWidget(props.path);
    const { $hydrate, ...rest } = widgetSpec ?? {};
    const [isWidgetSettingsModalOpen, setWidgetSettingsModalOpen] = useState(false);
    const widget = $hydrate ? catalog?.assets[$hydrate] ?? catalog?.widgets[$hydrate] : undefined;
    const isRequired = widget?.isRequired ?? false;
    const widgetProps = isEmpty(rest) ? undefined : rest;

    const trashButton = (
        <Button
            buttonType={ButtonTypes.ICON}
            disabled={isRequired}
            onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                editorContents.deleteWidget(props.path);
                editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.WIDGET);
            }}
        >
            <Icon icon={"delete"} />
        </Button>
    );

    const widgetContext = useWidgetContext();

    return (
        <>
            <LayoutEditorToolbar
                positionAbsolute={props.positionAbsolute}
                positionRelativeTo={widgetContext.widgetRef.current}
            >
                <LayoutEditorWidgetMeta widgetPath={props.path} />
                {!props.noDrag && (
                    <span data-draggable="button" {...props.dragAttributes}>
                        <ToolTip label={t("Drag Widget")}>
                            <span>
                                <Button buttonType={ButtonTypes.ICON} style={{ cursor: "grab" }}>
                                    <Icon icon={"move-drag"} />
                                </Button>
                            </span>
                        </ToolTip>
                    </span>
                )}
                <Button
                    buttonType={ButtonTypes.ICON}
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        setWidgetSettingsModalOpen(true);
                    }}
                >
                    <Icon icon={"edit"} />
                </Button>
                {!props.noDelete && (
                    <ConditionalWrap
                        component={ToolTip}
                        condition={!!isRequired}
                        componentProps={{ label: t("You cannot delete this required widget") }}
                    >
                        {isRequired ? <span>{trashButton}</span> : trashButton}
                    </ConditionalWrap>
                )}
            </LayoutEditorToolbar>
            {!!catalog && !!$hydrate && !!widget && (
                <ClearThemeOverrideContext>
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
                        layoutCatalog={catalog}
                        widgetID={$hydrate}
                    />
                </ClearThemeOverrideContext>
            )}
        </>
    );
});
