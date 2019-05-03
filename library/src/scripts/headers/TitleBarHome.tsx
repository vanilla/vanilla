/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import TitleBar from "@library/headers/TitleBar";
import TitleBarMobileHome from "@library/headers/pieces/TitleBarMobileHome";
import { withDevice, IDeviceProps, Devices } from "@library/layout/DeviceContext";

interface IProps extends IDeviceProps {}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 */
export class TitleBarHome extends React.Component<IProps> {
    public render() {
        const isMobile = this.props.device === Devices.MOBILE || this.props.device === Devices.XS;
        return isMobile ? <TitleBarMobileHome /> : <TitleBar />;
    }
}

export default withDevice(TitleBarHome);
