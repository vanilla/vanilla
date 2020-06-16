/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import { layoutClassesForCurrentLayout, layoutVariables } from "@library/layout/layoutStyles";
import throttle from "lodash/throttle";
import React, { useCallback, useContext, useEffect, useState } from "react";
import { IThreeColumnLayoutMediaQueries, ThreeColumnLayoutDevices } from "@library/layout/types/threeColumn";
import { ILegacyLayoutMediaQueries } from "@library/layout/types/legacy";
import { IOneColumnLayoutMediaQueries } from "@library/layout/types/oneColumn";
import { IOneColumnNarrowLayoutMediaQueries } from "@library/layout/types/oneColumnNarrow";
import { NestedCSSProperties } from "typestyle/lib/types";
import { globalVariables } from "@library/styles/globalStyleVars";

// export type IAnyMediaQuery =
//     | ILegacyLayoutMediaQueries
//     | IOneColumnLayoutMediaQueries
//     | IOneColumnNarrowLayoutMediaQueries
//     | IThreeColumnLayoutMediaQueries
//     | undefined;

export enum LayoutTypes {
    THREE_COLUMNS = "three columns", // Dynamic layout with up to 3 columns that adjusts to its contents. This is the default
    ONE_COLUMN = "one column",
    ONE_COLUMN_NARROW = "one column narrow", // Single column, but narrower than normal
    TWO_COLUMNS = "two columns", // Two column layout
    LEGACY = "legacy", // Legacy forum layout
}

export interface IAllLayoutMediaQueries {
    [LayoutTypes.THREE_COLUMNS]?: IThreeColumnLayoutMediaQueries;
    [LayoutTypes.ONE_COLUMN]?: IOneColumnLayoutMediaQueries;
    [LayoutTypes.ONE_COLUMN_NARROW]?: IOneColumnNarrowLayoutMediaQueries;
    [LayoutTypes.LEGACY]?: ILegacyLayoutMediaQueries;
}

export interface ILayoutProps {
    type: LayoutTypes;
    currentDevice: string;
    Devices: any;
    isCompact: boolean; // Usually mobile and/or xs, but named this way to be more generic and not be confused with the actual mobile media query
    isFullWidth: boolean; // Usually desktop and no bleed, but named this way to be more generic and just to mean it's the full size
    layoutClasses: any;
    currentLayoutVariables: any;
    mediaQueries: (styles: IAllLayoutMediaQueries) => NestedCSSProperties;
}

const LayoutContext = React.createContext<ILayoutProps>({
    type: LayoutTypes.THREE_COLUMNS,
    currentDevice: ThreeColumnLayoutDevices.DESKTOP,
    Devices: ThreeColumnLayoutDevices,
    isCompact: false,
    isFullWidth: false,
    layoutClasses: {},
    currentLayoutVariables: {},
    mediaQueries: (styles: IAllLayoutMediaQueries) => {
        return {} as NestedCSSProperties;
    },
});

export default LayoutContext;

export function useLayout() {
    return useContext(LayoutContext);
}

export function LayoutProvider(props: { type?: LayoutTypes; children: React.ReactNode }) {
    const { type = LayoutTypes.THREE_COLUMNS, children } = props;
    const layoutVars = layoutVariables();
    const currentLayoutVars = layoutVars.layouts.types[type];

    const calculateDevice = useCallback(() => {
        return currentLayoutVars.calculateDevice();
    }, []);

    const currentDevice = calculateDevice();
    const [deviceInfo, setDeviceInfo] = useState<ILayoutProps>({
        type,
        currentDevice: currentDevice,
        Devices: currentLayoutVars.Devices,
        isCompact: currentLayoutVars.isCompact(currentDevice),
        isFullWidth: currentLayoutVars.isFullWidth(currentDevice),
        layoutClasses: layoutClassesForCurrentLayout({ type }),
        currentLayoutVariables: currentLayoutVars,
        mediaQueries: currentLayoutVars.mediaQueries(),
    });

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            const currentDevice = calculateDevice();
            setDeviceInfo({
                type,
                currentDevice: currentDevice,
                Devices: currentLayoutVars.Devices,
                isCompact: currentLayoutVars.isCompact(currentDevice),
                isFullWidth: currentLayoutVars.isFullWidth(currentDevice),
                layoutClasses: layoutClassesForCurrentLayout({ type }),
                currentLayoutVariables: currentLayoutVars,
                mediaQueries: currentLayoutVars.mediaQueries(),
            });
        }, 100);
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [calculateDevice, setDeviceInfo]);

    return <LayoutContext.Provider value={deviceInfo}>{props.children}</LayoutContext.Provider>;
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
