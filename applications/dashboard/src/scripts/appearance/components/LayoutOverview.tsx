/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { layoutOverviewClasses } from "@dashboard/appearance/components/LayoutOverview.classes";
import { hydrateLayoutFromCatalog } from "@dashboard/appearance/utils";
import { useCatalogForLayout, useLayoutJson } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayout } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { FallbackLayoutWidget, IComponentFetcher, Layout } from "@library/features/Layout/Layout";
import Container, { ContainerContextReset } from "@library/layout/components/Container";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { WidgetLayoutWidget } from "@library/layout/WidgetLayoutWidget";
import Loader from "@library/loaders/Loader";
import { IRegisteredComponent } from "@library/utility/componentRegistry";
import React, { useMemo } from "react";

interface IProps {
    layoutID: ILayout["layoutID"];
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

export const FauxWidget = (props: React.ComponentProps<FallbackLayoutWidget> & { depth?: number }) => {
    const classes = layoutOverviewClasses();
    return (
        <WidgetLayoutWidget>
            <Container fullGutter>
                <div className={classes.fauxWidget}>
                    <p>{props.$reactComponent}</p>
                    <small style={{ position: "absolute", top: 16, right: 16 }}>{props.depth}</small>
                </div>
            </Container>
        </WidgetLayoutWidget>
    );
};

export function LayoutOverview(props: IProps) {
    const { layoutID } = props;
    const rawLayoutLoadable = useLayoutJson(layoutID);
    const catalog = useCatalogForLayout(layoutID);

    const hydratedLayout = useMemo(
        () => rawLayoutLoadable.data && catalog && hydrateLayoutFromCatalog(rawLayoutLoadable.data, catalog),
        [rawLayoutLoadable, catalog],
    );

    return hydratedLayout ? (
        <DeviceProvider>
            <ContainerContextReset>
                <Layout
                    layout={hydratedLayout.layout}
                    fallbackWidget={FauxWidget}
                    componentFetcher={fetchOverviewComponent}
                />
            </ContainerContextReset>
        </DeviceProvider>
    ) : (
        <Loader />
    );
}
