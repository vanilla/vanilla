/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import throttle from "lodash/throttle";
import React, { useCallback, useContext, useEffect, useState } from "react";
import { twoColumnLayoutVariables } from "@library/layout/twoColumnLayoutStyles";

export enum TwoColumnDevices {
    XS = "xs",
    MOBILE = "mobile",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
    DESKTOP = "desktop",
}

export interface ITwoColumnLayoutDeviceProps {
    device: TwoColumnDevices;
}

const TwoColumnLayoutDeviceContext = React.createContext<TwoColumnDevices>(TwoColumnDevices.DESKTOP);
export default TwoColumnLayoutDeviceContext;

export function useTwoColumnLayoutDevice() {
    const device = useContext(TwoColumnLayoutDeviceContext);
    return device;
}

interface IProps {
    children: React.ReactNode;
}

export function TwoColumnLayoutDeviceProvider(props: IProps) {
    const calculateDevice = useCallback(() => {
        const width = document.body.clientWidth;
        const breakpoints = twoColumnLayoutVariables().panelLayoutBreakPoints;
        if (width <= breakpoints.xs) {
            return TwoColumnDevices.XS;
        } else if (width <= breakpoints.oneColumn && width < breakpoints.noBleed) {
            return TwoColumnDevices.MOBILE;
        } else if (width <= breakpoints.noBleed) {
            return TwoColumnDevices.NO_BLEED;
        } else {
            return TwoColumnDevices.DESKTOP;
        }
    }, []);
    const [device, setDevice] = useState<TwoColumnDevices>(calculateDevice());

    useEffect(() => {
        const throttledUpdate = throttle(
            () => {
                setDevice(calculateDevice);
            },
            100,
            {
                leading: true,
                trailing: true,
            },
        );
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [calculateDevice, setDevice]);

    return (
        <TwoColumnLayoutDeviceContext.Provider value={device}>{props.children}</TwoColumnLayoutDeviceContext.Provider>
    );
}

/**
 * HOC to inject DeviceContext as props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withTwoColumnDevice<T extends ITwoColumnLayoutDeviceProps = ITwoColumnLayoutDeviceProps>(
    WrappedComponent: React.ComponentType<T>,
) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    const ComponentWithDevice = (props: Optionalize<T, ITwoColumnLayoutDeviceProps>) => {
        return (
            <TwoColumnLayoutDeviceContext.Consumer>
                {context => {
                    // https://github.com/Microsoft/TypeScript/issues/28938
                    return <WrappedComponent device={context} {...(props as T)} />;
                }}
            </TwoColumnLayoutDeviceContext.Consumer>
        );
    };
    ComponentWithDevice.displayName = `withTwoColumnDevice(${displayName})`;
    return ComponentWithDevice;
}
