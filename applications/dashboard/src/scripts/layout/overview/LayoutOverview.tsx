/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEditorSchemaDefaultsEnhancer } from "@dashboard/layout/editor/LayoutEditor.overviews";
import { LayoutEditorContents } from "@dashboard/layout/editor/LayoutEditorContents";
import { useCatalogForLayout, useLayoutJson } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutDetails } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { layoutOverviewClasses } from "@dashboard/layout/overview/LayoutOverview.classes";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { LoadStatus } from "@library/@types/api/core";
import {
    FallbackLayoutWidget,
    IComponentFetcher,
    LayoutLookupContext,
    LayoutRenderer,
} from "@library/features/Layout/LayoutRenderer";
import { ContainerContextReset } from "@library/layout/components/Container";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { SectionBehaviourContext } from "@library/layout/SectionBehaviourContext";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { LinkContext } from "@library/routing/links/LinkContextProvider";
import { IRegisteredComponent } from "@library/utility/componentRegistry";
import React, { Suspense, useMemo } from "react";
import { MemoryRouter } from "react-router-dom";
import { QueryLoader } from "@library/loaders/QueryLoader";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";

interface IProps {
    layoutID: ILayoutDetails["layoutID"];
}

declare global {
    interface Window {
        _layout_editor_overviews_: Record<string, IRegisteredComponent>;
    }
}

window._layout_editor_overviews_ = window._layout_editor_overviews_ ?? {};

export function registerWidgetOverviews(widgets: Record<string, React.ComponentType<any>>) {
    for (const [widgetName, widget] of Object.entries(widgets)) {
        window._layout_editor_overviews_[widgetName.toLowerCase()] = {
            Component: widget,
        };
    }
}

export const fetchOverviewComponent: IComponentFetcher = (componentName) => {
    return window._layout_editor_overviews_[componentName.toLowerCase()] ?? null;
};

// These need to be replaced with the widget previews

export const FauxWidget = (props: React.ComponentProps<FallbackLayoutWidget> & { isFullWidth?: boolean }) => {
    const classes = layoutOverviewClasses();
    return (
        <LayoutWidget
            interWidgetSpacing={props.isFullWidth ? "none" : "standard"}
            tabIndex={-1}
            className={props.isFullWidth ? classes.fauxWidgetFullWidth : classes.fauxWidget}
        >
            <div className={classes.fauxWidgetContent}>
                <p>{props.$reactComponent}</p>
            </div>
        </LayoutWidget>
    );
};

export function LayoutOverview(props: IProps) {
    const { layoutID } = props;
    const jsonLoadable = useLayoutJson(layoutID);

    const catalog = useCatalogForLayout(layoutID);

    const propEnhancer = useEditorSchemaDefaultsEnhancer(catalog);

    // Effect to load the initial
    const contents = useMemo(() => {
        if (!catalog || !jsonLoadable.data) {
            return null;
        }

        return new LayoutEditorContents(jsonLoadable.data, catalog);
    }, [catalog, jsonLoadable]);

    const classes = layoutOverviewClasses.useAsHook();

    if (jsonLoadable.status === LoadStatus.ERROR) {
        return <CoreErrorMessages error={jsonLoadable.error} />;
    }

    if (jsonLoadable.status === LoadStatus.LOADING || !contents) {
        return <LayoutOverviewSkeleton />;
    }

    const hydratedContent = contents.hydrate();

    return (
        <DeviceProvider>
            <ContainerContextReset>
                <SectionBehaviourContext.Provider value={{ isSticky: true, autoWrap: true, useMinHeight: false }}>
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
                                    fallbackWidget: FauxWidget,
                                    componentFetcher: fetchOverviewComponent,
                                    propEnhancer,
                                }}
                            >
                                <div className={classes.root}>
                                    <Suspense fallback={<LayoutOverviewSkeleton />}>
                                        <LayoutRenderer
                                            noSuspense={true}
                                            allowInternalProps
                                            layout={[
                                                {
                                                    $reactComponent: "TitleBar",
                                                    $fragmentImpls: hydratedContent.titleBar.$fragmentImpls as any,
                                                    $reactProps: {
                                                        ...hydratedContent.titleBar,
                                                        $editorPath: "TitleBar",
                                                    },
                                                },
                                            ]}
                                        />
                                        <LayoutRenderer noSuspense={true} layout={hydratedContent.layout} />
                                    </Suspense>
                                </div>
                            </LayoutLookupContext.Provider>
                        </LinkContext.Provider>
                    </MemoryRouter>
                </SectionBehaviourContext.Provider>
            </ContainerContextReset>
        </DeviceProvider>
    );
}
