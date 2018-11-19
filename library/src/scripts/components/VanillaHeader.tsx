/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import ReactDOM from "react-dom";
import { MeBox } from "@library/components/mebox/MeBox";
import { dummyLogoData } from "./mebox/state/dummyLogoData";
import { dummyNotificationsData } from "@library/components/mebox/state/dummyNotificationsData";
import { dummyMessagesData } from "@library/components/mebox/state/dummyMessagesData";
import { dummyNavigationData } from "./mebox/state/dummyNavigationData";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import { dummyOtherLanguagesData } from "@library/state/dummyOtherLanguages";
import { dummyUserDropDownData } from "@library/components/mebox/state/dummyUserDropDownData";

interface IProps extends IDeviceProps {
    container?: Element; // Element containing header. Should be the default most if not all of the time.
    className?: string;
    homePage?: boolean;
}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 * That means the exact location in the page is not that important, since it will
 * render in a specific div in the default-master.
 */
export class VanillaHeader extends React.Component<IProps> {
    public render() {
        return ReactDOM.createPortal(
            <MeBox
                homePage={!!this.props.homePage}
                className={this.props.className}
                logoProps={dummyLogoData}
                notificationsProps={dummyNotificationsData as any}
                navigationProps={{ children: dummyNavigationData.children, className: "vanillaHeader-nav" }}
                languagesProps={{
                    ...dummyOtherLanguagesData,
                    className: "vanillaHeader-locales",
                    buttonClassName: "vanillaHeader-localesToggle",
                }}
                messagesProps={dummyMessagesData as any}
                counts={dummyUserDropDownData}
                device={this.props.device}
                headerStyles={{}} // Defaults for now
            />,
            this.props.container || document.getElementById("vanillaHeader")!,
        );
    }
}

export default withDevice(VanillaHeader);
