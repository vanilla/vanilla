/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import throttle from "lodash/throttle";

export enum Devices {
    MOBILE = "mobile",
    TABLET = "tablet",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
    DESKTOP = "desktop",
}

export interface IDeviceProps {
    device: Devices;
}

interface IDeviceCheckerProps {
    doUpdate: () => void;
}
/**
 * Component returns device, based on media queries set in CSS to avoid duplicate break points.
 * Added DeviceContext in DeviceContext.tsx to pass data down to components
 * Force rerender when needed by calling doUpdate.
 */

export default class DeviceChecker extends React.Component<IDeviceCheckerProps> {
    public deviceChecker: React.RefObject<HTMLDivElement> = React.createRef();

    public render() {
        return <div ref={this.deviceChecker} className="deviceChecker" />;
    }

    /**
     * Query div in page to get device based on media query from CSS
     */
    public get device() {
        if (this.deviceChecker.current) {
            let device = Devices.DESKTOP;
            switch (`${this.deviceChecker.current.offsetWidth}`) {
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
     * There's a bug in webpack and there's no way to know the styles have loaded from webpack. In debug mode,
     */
    public componentDidMount() {
        window.addEventListener("resize", this.throttledUpdateOnResize);
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
     * Call the props update function when the window resizes.
     */
    private updateOnResize = () => {
        window.requestAnimationFrame(data => {
            this.props.doUpdate();
        });
    };

    /**
     * A throttled version of updateOnResize.
     */
    private throttledUpdateOnResize = throttle(this.updateOnResize, 100, {
        leading: true,
    });
}
