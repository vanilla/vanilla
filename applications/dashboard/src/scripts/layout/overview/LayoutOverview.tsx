/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditorContents } from "@dashboard/layout/editor/LayoutEditorContents";
import {
    useCatalogForLayout,
    useLayoutCatalog,
    useLayoutJson,
} from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
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
import { Widget } from "@library/layout/Widget";
import { LinkContext } from "@library/routing/links/LinkContextProvider";
import { IRegisteredComponent } from "@library/utility/componentRegistry";
import React, { useMemo } from "react";
import { MemoryRouter } from "react-router-dom";

interface IProps {
    layoutID: ILayoutDetails["layoutID"];
}

const _overviewComponents: Record<string, IRegisteredComponent> = {};

export function registerWidgetOverviews(widgets: Record<string, React.ComponentType<any>>) {
    for (const [widgetName, widget] of Object.entries(widgets)) {
        _overviewComponents[widgetName.toLowerCase()] = {
            Component: widget,
        };
    }
}

export const fetchOverviewComponent: IComponentFetcher = (componentName) => {
    return _overviewComponents[componentName.toLowerCase()] ?? null;
};

// These need to be replaced with the widget previews

export const FauxWidget = (props: React.ComponentProps<FallbackLayoutWidget> & { isFullWidth?: boolean }) => {
    const classes = layoutOverviewClasses();
    return (
        <Widget
            withContainer={props.isFullWidth}
            tabIndex={-1}
            className={props.isFullWidth ? classes.fauxWidgetFullWidth : classes.fauxWidget}
        >
            <div className={classes.fauxWidgetContent}>
                <p>{props.$reactComponent}</p>
            </div>
        </Widget>
    );
};

export function LayoutOverview(props: IProps) {
    const { layoutID } = props;
    const jsonLoadable = useLayoutJson(layoutID);

    const catalog = useCatalogForLayout(layoutID);

    // Effect to load the initial
    const contents = useMemo(() => {
        if (!catalog || !jsonLoadable.data) {
            return null;
        }

        return new LayoutEditorContents(jsonLoadable.data, catalog);
    }, [catalog, jsonLoadable]);

    if (jsonLoadable.status === LoadStatus.LOADING || !contents) {
        return <LayoutOverviewSkeleton />;
    }

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
                                }}
                            >
                                <LayoutRenderer layout={contents.hydrate().layout} />
                            </LayoutLookupContext.Provider>
                        </LinkContext.Provider>
                    </MemoryRouter>
                </SectionBehaviourContext.Provider>
            </ContainerContextReset>
        </DeviceProvider>
    );
}
