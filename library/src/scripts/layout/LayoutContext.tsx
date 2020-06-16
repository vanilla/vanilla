/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import { layoutClasses } from "@library/layout/layoutStyles";
import throttle from "lodash/throttle";
import React, { useCallback, useContext, useEffect, useState } from "react";
import { threeColumnLayout, ThreeColumnLayoutDevices } from "@library/layout/types/threeColumn";

import {
    LayoutTypes,
    layoutVarsForCurrentLayout,
    ILayoutMediaQueryFunction,
    IAllLayoutDevices,
} from "@library/layout/types/LayoutUtils";

export interface ILayoutProps {
    type: LayoutTypes;
    currentDevice: string;
    Devices: any;
    isCompact: boolean; // Usually mobile and/or xs, but named this way to be more generic and not be confused with the actual mobile media query
    isFullWidth: boolean; // Usually desktop and no bleed, but named this way to be more generic and just to mean it's the full size
    layoutClasses: any;
    currentLayoutVariables: any;
    mediaQueries: ILayoutMediaQueryFunction;
    contentWidth: () => number;
    calculateDevice: () => IAllLayoutDevices;
    layoutSpecificStyles: (style) => any | undefined;
}

const defaultLayoutVars = layoutVarsForCurrentLayout({ type: LayoutTypes.THREE_COLUMNS });
const LayoutContext = React.createContext<ILayoutProps>({
    type: LayoutTypes.THREE_COLUMNS,
    currentDevice: ThreeColumnLayoutDevices.DESKTOP,
    Devices: defaultLayoutVars.Devices,
    isCompact: defaultLayoutVars.isCompact(ThreeColumnLayoutDevices.DESKTOP),
    isFullWidth: defaultLayoutVars.isFullWidth(ThreeColumnLayoutDevices.DESKTOP),
    layoutClasses: layoutClasses({ type: LayoutTypes.THREE_COLUMNS }),
    currentLayoutVariables: defaultLayoutVars,
    mediaQueries: defaultLayoutVars.mediaQueries as ILayoutMediaQueryFunction,
    contentWidth: defaultLayoutVars.contentWidth,
    calculateDevice: defaultLayoutVars.calculateDevice,
    layoutSpecificStyles: defaultLayoutVars["layoutSpecificStyles"] ?? undefined,
});

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

    const defaultLayoutVars = threeColumnLayout();
    const [deviceInfo, setDeviceInfo] = useState<ILayoutProps>({
        type: LayoutTypes.THREE_COLUMNS,
        currentDevice: ThreeColumnLayoutDevices.DESKTOP,
        Devices: defaultLayoutVars.Devices,
        isCompact: defaultLayoutVars.isCompact(ThreeColumnLayoutDevices.DESKTOP),
        isFullWidth: defaultLayoutVars.isFullWidth(ThreeColumnLayoutDevices.DESKTOP),
        layoutClasses: layoutClasses({ type: LayoutTypes.THREE_COLUMNS }),
        currentLayoutVariables: defaultLayoutVars,
        mediaQueries: defaultLayoutVars.mediaQueries,
        contentWidth: defaultLayoutVars.contentWidth,
        calculateDevice: defaultLayoutVars.calculateDevice,
        layoutSpecificStyles: defaultLayoutVars["layoutSpecificStyles"] ?? undefined,
    } as ILayoutProps); // Can't get variables here

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            const currentDevice = calculateDevice();
            setDeviceInfo({
                type: currentLayoutVars.type,
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
