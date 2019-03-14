/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import VanillaHeader from "@library/headers/VanillaHeader";
import VanillaMobileHomeHeader from "@library/headers/pieces/VanillaMobileHomeHeader";
import { withDevice, IDeviceProps, Devices } from "@library/layout/DeviceContext";

interface IProps extends IDeviceProps {}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 */
export class VanillaHomeHeader extends React.Component<IProps> {
    public render() {
        const isMobile = this.props.device === Devices.MOBILE;
        return isMobile ? <VanillaMobileHomeHeader /> : <VanillaHeader />;
    }
}

export default withDevice(VanillaHomeHeader);
