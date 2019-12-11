/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import TitleBar from "@library/headers/TitleBar";
import TitleBarMobileHome from "@library/headers/pieces/TitleBarMobileHome";
import { ITitleBarDeviceProps, TitleBarDevices, withTitleBarDevice } from "@library/layout/TitleBarContext";

interface IProps extends ITitleBarDeviceProps {}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 */
export class TitleBarHome extends React.Component<IProps> {
    public render() {
        const isCompact = this.props.device === TitleBarDevices.COMPACT;
        return isCompact ? <TitleBarMobileHome /> : <TitleBar useMobileBackButton={false} />;
    }
}

export default withTitleBarDevice(TitleBarHome);
