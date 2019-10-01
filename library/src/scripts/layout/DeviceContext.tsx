/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import throttle from "lodash/throttle";
import React, { useCallback, useContext, useEffect, useState } from "react";

export enum Devices {
    XS = "xs",
    MOBILE = "mobile",
    TABLET = "tablet",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
    DESKTOP = "desktop",
}

export interface IDeviceProps {
    device: Devices;
}

const DeviceContext = React.createContext<Devices>(Devices.DESKTOP);
export default DeviceContext;

export function useDevice() {
    const device = useContext(DeviceContext);
    return device;
}

interface IProps {
    children: React.ReactNode;
}

export function DeviceProvider(props: IProps) {
    const calculateDevice = useCallback(() => {
        const breakpoints = layoutVariables().panelLayoutBreakPoints;
        const width = document.body.clientWidth;
        if (width <= breakpoints.xs) {
            return Devices.XS;
        } else if (width <= breakpoints.oneColumn) {
            return Devices.MOBILE;
        } else if (width <= breakpoints.twoColumn) {
            return Devices.TABLET;
        } else if (width <= breakpoints.noBleed) {
            return Devices.NO_BLEED;
        } else {
            return Devices.DESKTOP;
        }
    }, []);
    const [device, setDevice] = useState<Devices>(calculateDevice());

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            setDevice(calculateDevice);
        }, 100);
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [calculateDevice, setDevice]);

    return <DeviceContext.Provider value={device}>{props.children}</DeviceContext.Provider>;
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
                    return <WrappedComponent device={context} {...props as T} />;
                }}
            </DeviceContext.Consumer>
        );
    };
    ComponentWithDevice.displayName = `withDevice(${displayName})`;
    return ComponentWithDevice;
}
