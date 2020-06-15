/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import { LayoutTypes, layoutVariables } from "@library/layout/layoutStyles";
import throttle from "lodash/throttle";
import React, { useCallback, useContext, useEffect, useState } from "react";
import { ThreeColumnLayoutDevices } from "@library/layout/types/threeColumn";
import { calc } from "csx";

export const Devices = ThreeColumnLayoutDevices; // temp

export interface IDeviceProps {
    device: ThreeColumnLayoutDevices;
    isMobile: boolean;
}

const DeviceContext = React.createContext<IDeviceProps>({
    device: ThreeColumnLayoutDevices.DESKTOP,
    isMobile: false,
});

export default DeviceContext;

export function useDevice() {
    const device = useContext(DeviceContext);
    return device;
}

interface IProps {
    children: React.ReactNode;
}

export function DeviceProvider(props: IProps) {
    const layoutVars = layoutVariables().types.threeColumns; // hard coded for compatibility

    const calculateDevice = useCallback(() => {
        const breakpoints = layoutVars.breakPoints;
        const width = document.body.clientWidth;
        if (width <= breakpoints.xs) {
            return layoutVars.Devices.XS;
        } else if (width <= breakpoints.oneColumn) {
            return layoutVars.Devices.MOBILE;
        } else if (width <= breakpoints.twoColumn) {
            return layoutVars.Devices.TABLET;
        } else if (width <= breakpoints.noBleed) {
            return layoutVars.Devices.NO_BLEED;
        } else {
            return layoutVars.Devices.DESKTOP;
        }
    }, []);

    const currentDevice = calculateDevice();
    const [deviceInfo, setDeviceInfo] = useState<IDeviceProps>({
        device: currentDevice,
        isMobile: currentDevice === Devices.MOBILE || currentDevice === Devices.XS,
    });

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            const currentDevice = calculateDevice();
            setDeviceInfo({
                device: currentDevice,
                isMobile: currentDevice === Devices.MOBILE || currentDevice === Devices.XS,
            });
        }, 100);
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [calculateDevice, setDeviceInfo]);

    return <DeviceContext.Provider value={deviceInfo}>{props.children}</DeviceContext.Provider>;
}

/**
 * HOC to inject DeviceContext as props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withDevice<T extends IDeviceProps = IDeviceProps>(WrappedComponent: React.ComponentType<T>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    const ComponentWithDevice = (props: Optionalize<T, IDeviceProps>) => {
        return (
            <DeviceContext.Consumer>
                {context => {
                    // https://github.com/Microsoft/TypeScript/issues/28938
                    return <WrappedComponent device={context} {...(props as T)} />;
                }}
            </DeviceContext.Consumer>
        );
    };
    ComponentWithDevice.displayName = `withDevice(${displayName})`;
    return ComponentWithDevice;
}
