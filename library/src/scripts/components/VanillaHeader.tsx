/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import ReactDOM from "react-dom";
import { MeBox } from "@library/components/mebox/MeBox";
import { dummyOtherLanguagesData } from "@knowledge/modules/categories/state/dummyOtherLanguages";
import { dummyLogoData } from "./mebox/state/dummyLogoData";
import { dummyNotificationsData } from "@library/components/mebox/state/dummyNotificationsData";
import { dummyMessagesData } from "@library/components/mebox/state/dummyMessagesData";
import { dummyUserDropDownData } from "@library/components/mebox/state/dummyUserDropDownData";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { dummyNavigationData } from "./mebox/state/dummyNavigationData";

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
                className={this.props.className}
                logoData={dummyLogoData}
                notificationsData={dummyNotificationsData}
                navigationData={{ data: dummyNavigationData }}
                languagesData={dummyOtherLanguagesData}
                messagesData={dummyMessagesData}
                userDropDownData={dummyUserDropDownData}
                device={this.props.device}
                homePage={!!this.props.homePage}
                headerStyles={{}} // Defaults for now
            />,
            this.props.container || document.getElementById("vanillaHeader")!,
        );
    }

    /*<header className={classNames("vanillaHeader")}>*/
    /*{!this.props.homePage && (*/
    /*<div className="vanillaHeader-top">*/
    /*{this.props.logoUrl && <img src={this.props.logoUrl} alt={this.props.logoAlt} />}*/
    /*{!this.props.logoUrl && vanillaLogo("vanillaHeader-logo")}*/
    /*</div>*/
    /*)}*/
    /*<div className="vanillaHeader-main" />*/
    /*</header>*/
}

export default withDevice(VanillaHeader);
