/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { Omit } from "@library/@types/utils";
const DeviceContext = React.createContext<Devices>(Devices.DESKTOP);
export default DeviceContext;

/**
 * HOC to inject DeviceContext as props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withDevice<T extends IDeviceProps = IDeviceProps>(WrappedComponent: React.ComponentType<T>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    class ComponentWithDevice extends React.Component<Omit<T, keyof IDeviceProps>> {
        public static displayName = `withDevice(${displayName})`;
        public render() {
            return (
                <DeviceContext.Consumer>
                    {context => {
                        return <WrappedComponent device={context} {...this.props} />;
                    }}
                </DeviceContext.Consumer>
            );
        }
    }
    return ComponentWithDevice;
}
