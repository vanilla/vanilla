/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { Optionalize } from "@library/@types/utils";
import throttle from "lodash/throttle";
import { deviceCheckerClasses } from "@library/layout/deviceCheckerStyles";
import { forceRenderStyles } from "typestyle";

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

interface IState {
    device: Devices;
}
export class DeviceProvider extends React.Component<IProps, IState> {
    public state: IState = {
        device: Devices.DESKTOP,
    };
    private deviceChecker: React.RefObject<HTMLDivElement> = React.createRef();

    public render() {
        const classes = deviceCheckerClasses();
        forceRenderStyles();
        const children = (
            <DeviceContext.Provider value={this.state.device}>{this.props.children}</DeviceContext.Provider>
        );
        return (
            <>
                <div ref={this.deviceChecker} className={classes.root} />
                {this.deviceChecker.current && children}
            </>
        );
    }

    /**
     * Query div in page to get device based on media query from CSS
     */
    private get device() {
        if (this.deviceChecker.current) {
            let device = Devices.DESKTOP;
            switch (`${this.deviceChecker.current.offsetWidth}`) {
                case "0":
                    device = Devices.XS;
                    break;
                case "1":
                    device = Devices.MOBILE;
                    break;
                case "2":
                    device = Devices.TABLET;
                    break;
                case "3":
                    device = Devices.NO_BLEED;
                    break;
                default:
                    device = Devices.DESKTOP;
            }
            return device;
        } else {
            throw new Error("deviceChecker does not exist");
        }
    }

    /**
     * @inheritdoc
     */
    public componentDidMount() {
        // Force at least one setting of the device.
        this.setState({ device: this.device });

        // Add a listener to update the device when window size changes.
        window.addEventListener("resize", this.throttledUpdateOnResize);

        // When the webpack hot reload is on, styles are mounted after the javascript.
        // As a result the measurement here is incorrect and there is no event fired when the CSS finishes.
        // Here we fake it with a delayed fake resize event.
        if (module.hot) {
            setTimeout(() => {
                window.dispatchEvent(new Event("resize"));
            }, 1000);
        }
    }

    /**
     * @inheritDoc
     */
    public componentWillUnmount() {
        window.removeEventListener("resize", this.throttledUpdateOnResize);
    }

    /**
     * A throttled version of updateOnResize.
     */
    private throttledUpdateOnResize = throttle(() => {
        this.setState({ device: this.device });
    }, 100);
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
