/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import throttle from "lodash/throttle";
import React, { useCallback, useContext, useEffect, useState } from "react";
import { titleBarVariables } from "@library/headers/titleBarStyles";

export enum TitleBarDevices {
    COMPACT = "compact",
    FULL = "full",
}

export interface ITitleBarDeviceProps {
    device: TitleBarDevices;
}

const TitleBarDeviceContext = React.createContext<TitleBarDevices>(TitleBarDevices.FULL);
export default TitleBarDeviceContext;

export function useDevice() {
    const device = useContext(TitleBarDeviceContext);
    return device;
}

interface IProps {
    children: React.ReactNode;
}

export function TitleBarDeviceProvider(props: IProps) {
    const calculateDevice = useCallback(() => {
        // const breakpoints = layoutVariables().panelLayoutBreakPoints;
        const breakpoints = titleBarVariables().breakpoints;
        const width = document.body.clientWidth;
        if (width <= breakpoints.compact) {
            return TitleBarDevices.COMPACT;
        } else {
            return TitleBarDevices.FULL;
        }
    }, []);
    const [device, setDevice] = useState<TitleBarDevices>(calculateDevice());

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            setDevice(calculateDevice);
        }, 100);
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [calculateDevice, setDevice]);

    return <TitleBarDeviceContext.Provider value={device}>{props.children}</TitleBarDeviceContext.Provider>;
}

/**
 * HOC to inject DeviceContext as props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withTitleBarDevice<T extends ITitleBarDeviceProps = ITitleBarDeviceProps>(
    WrappedComponent: React.ComponentType<T>,
) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    const ComponentWithDevice = (props: Optionalize<T, ITitleBarDeviceProps>) => {
        return (
            <TitleBarDeviceContext.Consumer>
                {context => {
                    // https://github.com/Microsoft/TypeScript/issues/28938
                    return <WrappedComponent device={context} {...(props as T)} />;
                }}
            </TitleBarDeviceContext.Consumer>
        );
    };
    ComponentWithDevice.displayName = `withDevice(${displayName})`;
    return ComponentWithDevice;
}
