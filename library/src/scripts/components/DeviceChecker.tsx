/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import debounce from "lodash/debounce";

export enum Devices {
    MOBILE = "mobile",
    TABLET = "tablet",
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
            }
            return device;
        } else {
            throw new Error("deviceChecker does not exist");
        }
    }

    public componentDidMount() {
        window.addEventListener("resize", e => {
            debounce(
                () => {
                    window.requestAnimationFrame(data => {
                        this.props.doUpdate();
                    });
                },
                100,
                {
                    leading: true,
                },
            )();
        });
    }
}
