/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import throttle from "lodash/throttle";
import React, { useContext, useEffect, useState } from "react";
import { IPanelLayoutClasses } from "@library/layout/panelLayoutStyles";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import { mediaQueryFactory } from "@library/layout/types/mediaQueryFactory";
import { IAllLayoutDevices, ILayoutMediaQueryFunction } from "@library/layout/types/interface.panelLayout";
import { twoColumnLayoutClasses, twoColumnLayoutVariables } from "@library/layout/types/layout.twoColumns";
import { threeColumnLayoutClasses, threeColumnLayoutVariables } from "@library/layout/types/layout.threeColumns";

export interface ILayoutProps {
    type: LayoutTypes;
    currentDevice: string;
    Devices: IAllLayoutDevices;
    isCompact: boolean; // Usually mobile and/or xs, but named this way to be more generic and not be confused with the actual mobile media query
    isFullWidth: boolean; // Usually desktop and no bleed, but named this way to be more generic and just to mean it's the full size
    classes: IPanelLayoutClasses;
    currentLayoutVariables: any;
    mediaQueries: ILayoutMediaQueryFunction;
    contentWidth: number;
    calculateDevice: () => any;
    layoutSpecificStyles: (style) => any | undefined;
    rightPanelCondition: (currentDevice: string, shouldRenderRightPanel: boolean) => boolean;
}

const layoutDataByType = (type: LayoutTypes): ILayoutProps => {
    const layout = {
        variables: type === LayoutTypes.TWO_COLUMNS ? twoColumnLayoutVariables() : threeColumnLayoutVariables(),
        classes: type === LayoutTypes.TWO_COLUMNS ? twoColumnLayoutClasses() : threeColumnLayoutClasses(),
    };

    const currentDevice = layout.variables.calculateDevice().toString();

    return {
        type,
        currentDevice,
        Devices: layout.variables.Devices as any,
        isCompact: layout.variables.isCompact(currentDevice),
        isFullWidth: layout.variables.isFullWidth(currentDevice),
        classes: layout.classes as IPanelLayoutClasses,
        currentLayoutVariables: layout.variables,
        mediaQueries: mediaQueryFactory(layout.variables.mediaQueries, type),
        contentWidth: layout.variables.contentWidth,
        calculateDevice: layout.variables.calculateDevice,
        layoutSpecificStyles: layout.variables["layoutSpecificStyles"] ?? undefined,
        rightPanelCondition:
            layout.variables["rightPanelCondition"] !== undefined
                ? layout.variables["rightPanelCondition"]
                : () => {
                      return false;
                  },
    };
};

const LayoutContext = React.createContext<ILayoutProps>(layoutDataByType(LayoutTypes.THREE_COLUMNS));

export default LayoutContext;

export function useLayout() {
    return useContext(LayoutContext);
}

export function LayoutProvider(props: { type?: LayoutTypes; children: React.ReactNode }) {
    const { type = LayoutTypes.THREE_COLUMNS, children } = props;

    const [deviceInfo, setDeviceInfo] = useState<ILayoutProps>(layoutDataByType(type));

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            setDeviceInfo(layoutDataByType(type));
        }, 100);
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [type, setDeviceInfo]);

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
