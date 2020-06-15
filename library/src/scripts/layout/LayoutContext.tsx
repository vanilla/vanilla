/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import { layoutClasses, LayoutTypes, layoutVariables } from "@library/layout/layoutStyles";
import throttle from "lodash/throttle";
import React, { useCallback, useContext, useEffect, useState } from "react";
import { ThreeColumnLayoutDevices } from "@library/layout/types/threeColumn";

type IAllDevices = ThreeColumnLayoutDevices;

export interface ILayoutProps {
    type: LayoutTypes;
    currentDevice: IAllDevices;
    Devices: any; // Enum
    isCompact: boolean;
    isFullWidth: boolean;
    layoutClasses: object;
}

const LayoutContext = React.createContext<ILayoutProps>({
    type: LayoutTypes.THREE_COLUMNS,
    currentDevice: ThreeColumnLayoutDevices.DESKTOP,
    Devices: ThreeColumnLayoutDevices,
    isCompact: false,
    isFullWidth: false,
    layoutClasses: {},
});

export default LayoutContext;

export function useLayout() {
    return useContext(LayoutContext);
}

export function LayoutProvider(props: { type?: LayoutTypes; children: React.ReactNode }) {
    const { type = LayoutTypes.THREE_COLUMNS, children } = props;
    const layoutVars = layoutVariables();
    const currentLayoutVars = layoutVars.types[type];

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
        layoutClasses: layoutClasses({ type }),
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
                layoutClasses: layoutClasses({ type }),
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
