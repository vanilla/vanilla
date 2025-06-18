/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ColorPicker } from "@dashboard/components/ColorPicker";
import { LayoutEditorContents } from "@dashboard/layout/editor/LayoutEditorContents";
import { resolveFieldParams } from "@dashboard/layout/editor/widgetSettings/resolveFieldParams";
import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import { IWidgetConfigurationComponentProps } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import { type ILayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { FauxWidget, fetchOverviewComponent } from "@dashboard/layout/overview/LayoutOverview";
import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import { LayoutLookupContext, LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { NestedSelect } from "@library/forms/nestedSelect";
import { HtmlWidgetCodeEditor } from "@library/htmlWidget/HtmlWidgetEditor";
import { WidgetContextProvider } from "@library/layout/LayoutWidget";
import { Row } from "@library/layout/Row";
import WidgetPreviewNoPointerEventsWrapper from "@library/layout/WidgetPreviewNoPointerEventsWrapper";
import { LinkContext } from "@library/routing/links/LinkContextProvider";
import SmartLink from "@library/routing/links/SmartLink";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { EditorThemePreviewOverrides, useEditorThemePreview } from "@library/theming/EditorThemePreviewContext";
import { t } from "@vanilla/i18n";
import debounce from "lodash-es/debounce";
import React, { memo, useEffect, useRef, useState } from "react";
import { MemoryRouter } from "react-router-dom";

interface IWidgetSettingsPreviewProps {
    layoutCatalog: ILayoutCatalog;
    widgetID: string;
    config?: object;
    schema: IWidgetConfigurationComponentProps["schema"];
    value: IWidgetConfigurationComponentProps["value"];
    onChange: IWidgetConfigurationComponentProps["onChange"];
}

type IRendererConfig = React.ComponentProps<typeof LayoutRenderer>;

export function WidgetSettingsPreview(props: IWidgetSettingsPreviewProps) {
    const schemaIncludesHtml = props.schema.properties && "html" in props.schema.properties;

    const { layoutCatalog, widgetID, config } = props;

    const [rendererConfig, setRendererConfig] = useState<IRendererConfig>(
        new LayoutEditorContents(
            {
                titleBar: {} as any,
                layoutViewType: "home",
                layout: [
                    {
                        $hydrate: widgetID,
                        ...resolveFieldParams(config),
                    },
                ],
            },
            layoutCatalog,
        ).hydrate(),
    );

    const throttledSetRendererConfig = useRef(debounce(setRendererConfig, 250, { leading: true }));

    useEffect(() => {
        throttledSetRendererConfig.current(
            new LayoutEditorContents(
                {
                    titleBar: {} as any,
                    layoutViewType: "home",
                    layout: [
                        {
                            $hydrate: widgetID,
                            ...config,
                        },
                    ],
                },
                layoutCatalog,
            ).hydrate(),
        );
    }, [config, widgetID]);

    const classes = widgetSettingsClasses();

    const previewTheme = useEditorThemePreview();
    const [scalePercent, setScalePercent] = useState("100%");
    const [previewBgColor, setPreviewBgColor] = useState(
        ColorsUtils.ensureColorHelper(
            previewTheme.previewedThemeQuery?.data?.assets?.variables?.data?.global?.mainColors.bg ?? "#fff",
        ).toHexString(),
    );
    const transform = (() => {
        switch (scalePercent) {
            case "150%":
                return "scale(0.6666666667)";
            case "200%":
                return "scale(0.5)";
            default:
                return undefined;
        }
    })();
    const previewStyle: React.CSSProperties = {
        width: scalePercent,
        transform,
        transformOrigin: "top left",
        backgroundColor: previewBgColor,
    };

    const widgetClassName = cx(`${widgetID.split(".")[1]}`);

    return (
        <div className={classes.preview}>
            <div className={classes.previewHeader}>
                <Translate
                    source="Add or edit your widget here. You can choose your widget options by selecting a layout option, title and description if applicable.
                    Set your widget conditions to specify where the widget will appear along with who the widget will be visible to.
                    Styles will be inherited from <1>Style Guide. </1>Find out more in the <2>documentation.</2>"
                    c1={(text) => <SmartLink to="https://success.vanillaforums.com/kb/articles/279">{text}</SmartLink>}
                    c2={(text) => <SmartLink to="https://success.vanillaforums.com/kb/articles/547">{text}</SmartLink>}
                />
            </div>

            {schemaIncludesHtml ? (
                <HtmlWidgetCodeEditor
                    value={props.value}
                    onChange={(newValue) => props.onChange({ ...props.value, ...newValue })}
                />
            ) : (
                <>
                    <Row gap={16} align={"center"} className={classes.previewContainer}>
                        <NestedSelect
                            inline={true}
                            compact={true}
                            label={t("Scale") + ":"}
                            value={scalePercent}
                            onChange={(value: string) => setScalePercent(value)}
                            options={[
                                { label: "200%", value: "200%" },
                                { label: "150%", value: "150%" },
                                { label: "100%", value: "100%" },
                                { label: "75%", value: "75%" },
                                { label: "50%", value: "50%" },
                                { label: "25%", value: "25%" },
                            ]}
                        />
                        <label className={classes.previewColorLabel}>
                            <strong>{t("Preview Background")}:</strong>
                            <ColorPicker
                                swatchClassName={classes.previewColorSwatch}
                                noInput={true}
                                value={previewBgColor}
                                onChange={setPreviewBgColor}
                            ></ColorPicker>
                        </label>
                    </Row>
                    <div className={classes.previewContainer}>
                        <EditorThemePreviewOverrides>
                            <WidgetSettingsPreviewImpl
                                key={scalePercent} // Need to re-render on scale change so overflow detection and measures are reset.
                                style={previewStyle}
                                widgetClassName={widgetClassName}
                                {...rendererConfig}
                            />
                        </EditorThemePreviewOverrides>
                    </div>
                </>
            )}
        </div>
    );
}

const WidgetSettingsPreviewImpl = memo(function WidgetSettingsPreview(
    props: React.ComponentProps<typeof LayoutRenderer> & { style?: React.CSSProperties; widgetClassName: string },
) {
    const { widgetClassName, style, ...rendererConfig } = props;
    const classes = widgetSettingsClasses.useAsHook();

    return (
        <div style={style} className={cx(classes.previewBody, "widgetSettingsPreview", widgetClassName)}>
            <WidgetContextProvider
                // To override outer context
                widgetRef={{ current: null }}
                inert={true}
                extraClasses={classes.previewContent}
            >
                <WidgetPreviewNoPointerEventsWrapper>
                    <MemoryRouter>
                        <LinkContext.Provider
                            value={{
                                linkContexts: [""],
                                isDynamicNavigation: () => {
                                    return true;
                                },
                                pushSmartLocation: () => {},
                                makeHref: () => {
                                    return "";
                                },
                                areLinksDisabled: false,
                            }}
                        >
                            <LayoutLookupContext.Provider
                                value={{
                                    componentFetcher: fetchOverviewComponent,
                                    fallbackWidget: FauxWidget,
                                }}
                            >
                                <LayoutRenderer {...rendererConfig} />
                            </LayoutLookupContext.Provider>
                        </LinkContext.Provider>
                    </MemoryRouter>
                </WidgetPreviewNoPointerEventsWrapper>
            </WidgetContextProvider>
        </div>
    );
});
