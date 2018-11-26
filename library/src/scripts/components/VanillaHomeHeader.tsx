/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import ReactDOM from "react-dom";
import MeBox from "@library/components/mebox/MeBox";
import { dummyLogoData } from "./mebox/state/dummyLogoData";
import { dummyNotificationsData } from "@library/components/mebox/state/dummyNotificationsData";
import { dummyMessagesData } from "@library/components/mebox/state/dummyMessagesData";
import { dummyGuestNavigationData, dummyNavigationData } from "./mebox/state/dummyNavigationData";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import { dummyUserDropDownData } from "@library/components/mebox/state/dummyUserDropDownData";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import classNames from "classnames";
import Container from "@library/components/layouts/components/Container";
import { PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import HeaderLogo from "@library/components/mebox/pieces/HeaderLogo";
import VanillaHeaderNav from "@library/components/mebox/pieces/VanillaHeaderNav";
import CompactSearch from "@library/components/mebox/pieces/CompactSearch";
import CompactMeBox from "@library/components/mebox/pieces/CompactMeBox";
import { connect } from "react-redux";
import { INotificationsProps } from "@library/components/mebox/pieces/NotificationsContents";
import LanguagesDropDown from "@library/components/LanguagesDropDown";
import { dummyOtherLanguagesData } from "@library/state/dummyOtherLanguages";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { t } from "@library/application";

interface IProps extends IDeviceProps, IInjectableUserState {
    container?: Element; // Element containing header. Should be the default most if not all of the time.
    className?: string;
}

/**
 * Implements Vanilla Hoe Header component. Note that this component uses a react portal.
 * That means the exact location in the page is not that important, since it will
 * render in a specific div in the default-master.
 */
export class VanillaHomeHeader extends React.Component<IProps> {
    private resultsRef: React.RefObject<HTMLDivElement> = React.createRef();
    public render() {
        const currentUser = this.props.currentUser.data;
        const isMobile = this.props.device === Devices.MOBILE;
        const isGuest = currentUser && UsersModel && currentUser.userID === UsersModel.GUEST_ID;
        const countClass = "vanillaHeader-count";
        const buttonClass = "vanillaHeader-button";

        const notificationProps = {
            data: dummyNotificationsData.data,
            userSlug: currentUser!.name,
            count: 108,
            countClass: classNames(countClass, "vanillaHeader-notificationsCount"),
        };

        const messagesProps = {
            ...dummyMessagesData,
            buttonClass,
            countClass: classNames(countClass, "vanillaHeader-messagesCount"),
        };

        return ReactDOM.createPortal(
            <header className={classNames("vanillaHeader", "vanillaHeaderHome", this.props.className)}>
                <Container className={"vanillaHeaderHome-top"}>
                    <PanelWidgetHorizontalPadding>
                        <div className="vanillaHeader-bar">{t("toto")}</div>
                    </PanelWidgetHorizontalPadding>
                </Container>
                <Container className={"vanillaHeaderHome-bottom"}>
                    <div className="vanillaHeader-horizontalScroll">
                        <PanelWidgetHorizontalPadding>
                            <VanillaHeaderNav
                                {...dummyGuestNavigationData}
                                linkClassName="vanillaHeader-navLink"
                                linkContentClassName="vanillaHeader-navLinkContent"
                                className="vanillaHeader-nav vanillaHeader-guestNav"
                            />
                            <LanguagesDropDown
                                {...dummyOtherLanguagesData}
                                renderLeft={true}
                                className="vanillaHeader-locales"
                                buttonClassName="vanillaHeader-localeToggle"
                                buttonBaseClass={ButtonBaseClass.CUSTOM}
                                widthOfParent={false}
                                openAsModal={isMobile}
                            />
                        </PanelWidgetHorizontalPadding>
                    </div>
                </Container>
            </header>,
            this.props.container || document.getElementById("vanillaHeader")!,
        );
    }

    public openSearch = () => {
        this.setState({
            openSearch: true,
        });
    };

    public closeSearch = () => {
        this.setState({
            openSearch: false,
        });
    };

    /**
     * Keep track of visibility of suggestions
     */
    public setOpenSuggestions = () => {
        this.setState({
            showingSuggestions: true,
        });
    };

    /**
     * Keep track of visibility of suggestions
     */
    public setCloseSuggestions = () => {
        this.setState({
            showingSuggestions: false,
        });
    };
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(withDevice(VanillaHomeHeader));
