/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import { layoutClasses, layoutVariables } from "@library/layout/layoutStyles";
import throttle from "lodash/throttle";
import React, { useCallback, useContext, useEffect, useState } from "react";
import {
    IThreeColumnLayoutMediaQueries,
    threeColumnLayout,
    ThreeColumnLayoutDevices,
} from "@library/layout/types/threeColumn";
import { IOneColumnLayoutMediaQueries, oneColumnLayout, OneColumnLayoutDevices } from "@library/layout/types/oneColumn";
import {
    IOneColumnNarrowLayoutMediaQueries,
    oneColumnNarrowLayout,
    OneColumnNarrowLayoutDevices,
} from "@library/layout/types/oneColumnNarrow";
import { NestedCSSProperties } from "typestyle/lib/types";

export enum LayoutTypes {
    THREE_COLUMNS = "three columns", // Dynamic layout with up to 3 columns that adjusts to its contents. This is the default
    ONE_COLUMN = "one column",
    NARROW = "one column narrow", // Single column, but narrower than normal
    // TWO_COLUMNS = "two columns", // Two column layout
    // LEGACY = "legacy", // Legacy forum layout
}

export interface IAllLayoutMediaQueries {
    [LayoutTypes.THREE_COLUMNS]?: IThreeColumnLayoutMediaQueries;
    [LayoutTypes.ONE_COLUMN]?: IOneColumnLayoutMediaQueries;
    [LayoutTypes.NARROW]?: IOneColumnNarrowLayoutMediaQueries;
    // [LayoutTypes.LEGACY]?: ILegacyLayoutMediaQueries;
}

export interface ILayoutProps {
    type?: LayoutTypes;
    currentDevice?: string;
    Devices?: any;
    isCompact?: boolean; // Usually mobile and/or xs, but named this way to be more generic and not be confused with the actual mobile media query
    isFullWidth?: boolean; // Usually desktop and no bleed, but named this way to be more generic and just to mean it's the full size
    layoutClasses?: any;
    currentLayoutVariables?: any;
    mediaQueries?: (styles: IAllLayoutMediaQueries) => NestedCSSProperties;
    contentWidth?: () => number;
    calculateDevice?: () => OneColumnLayoutDevices | OneColumnNarrowLayoutDevices | ThreeColumnLayoutDevices;
    layoutSpecificStyles?: (style) => any | undefined;
}

const filterQueriesByType = mediaQueriesByType => {
    return (mediaQueriesByLayout: IAllLayoutMediaQueries) => {
        const { type = LayoutTypes.THREE_COLUMNS } = useLayout();
        // console.log("");
        // console.log("mediaQueriesByLayout: ", mediaQueriesByLayout);

        Object.keys(mediaQueriesByLayout).forEach(layoutName => {
            // console.log("layoutName: ", layoutName);
            if (layoutName === type) {
                // Check if we're in the correct layout before applying
                const mediaQueriesForLayout = mediaQueriesByLayout[layoutName];
                const stylesForLayout = mediaQueriesByLayout[layoutName];
                // console.log("mediaQueriesForLayout: ", mediaQueriesForLayout);
                // console.log("stylesForLayout: ", stylesForLayout);

                if (mediaQueriesForLayout) {
                    Object.keys(mediaQueriesForLayout).forEach(queryName => {
                        mediaQueriesForLayout[queryName] = stylesForLayout;
                        // console.log("mediaQueriesForLayout[queryName]: ", mediaQueriesForLayout[queryName]);
                        return mediaQueriesForLayout[queryName];
                    });
                }
            }
        });
        return {};
    };
};

export const allLayoutVariables = () => {
    const mediaQueriesByType = {};
    const types = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayout(),
        [LayoutTypes.ONE_COLUMN]: oneColumnLayout(),
        [LayoutTypes.NARROW]: oneColumnNarrowLayout(),
        // [LayoutTypes.LEGACY]: legacyLayout(),
    };

    // console.log("");
    // console.log("types: ", types);

    Object.keys(LayoutTypes).forEach(layoutName => {
        const enumKey = LayoutTypes[layoutName];
        const layoutData = types[enumKey];
        // console.log("layoutData: ", layouries();
        mediaQueriesByType[enumKey] = layoutData.mediaQueries();
        // console.log("mediaQueriesByType: ", mediaQueriesByType);
    });

    // console.log("mediaQueriesByType: ", mediaQueriesByType);

    return {
        mediaQueries: filterQueriesByType(mediaQueriesByType),
        types,
    };
};

export const layoutVarsForCurrentLayout = (props: { type: LayoutTypes }) => {
    const { type = LayoutTypes.THREE_COLUMNS } = props;

    if (layoutVariables().layouts.types[type]) {
        return layoutVariables().layouts.types[type];
    } else {
        return layoutVariables().layouts.types[LayoutTypes.THREE_COLUMNS];
    }
};

const LayoutContext = React.createContext<ILayoutProps>({});

export default LayoutContext;

export function useLayout() {
    return useContext(LayoutContext);
}

export function LayoutProvider(props: { type?: LayoutTypes; children: React.ReactNode }) {
    const { type = LayoutTypes.THREE_COLUMNS, children } = props;
    const currentLayoutVars = layoutVarsForCurrentLayout({ type });

    const calculateDevice = useCallback(() => {
        return currentLayoutVars.calculateDevice();
    }, []);

    const [deviceInfo, setDeviceInfo] = useState<ILayoutProps>({} as ILayoutProps);

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            const currentDevice = calculateDevice();
            setDeviceInfo({
                type,
                currentDevice: currentDevice,
                Devices: currentLayoutVars.Devices,
                isCompact: currentLayoutVars.isCompact(currentDevice),
                isFullWidth: currentLayoutVars.isFullWidth(currentDevice),
                layoutClasses: layoutClasses({ type }),
                currentLayoutVariables: currentLayoutVars,
                mediaQueries: currentLayoutVars.mediaQueries as any,
                contentWidth: currentLayoutVars.contentWidth,
                calculateDevice: currentLayoutVars.calculateDevice,
                layoutSpecificStyles: currentLayoutVars["layoutSpecificStyles"] ?? undefined,
            });
        }, 100);
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [calculateDevice, setDeviceInfo]);

    return <LayoutContext.Provider value={deviceInfo}>{children}</LayoutContext.Provider>;
}

/**
 * HOC to inject DeviceContext as props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withLayout<T extends ILayoutProps = ILayoutProps>(WrappedComponent: React.ComponentType<T>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    const ComponentWithDevice = (props: Optionalize<T, ILayoutProps>) => {
        return (
            <LayoutContext.Consumer>
                {context => {
                    // https://github.com/Microsoft/TypeScript/issues/28938
                    return <WrappedComponent device={context} {...(props as T)} />;
                }}
            </LayoutContext.Consumer>
        );
    };
    ComponentWithDevice.displayName = `withLayout(${displayName})`;
    return ComponentWithDevice;
}
