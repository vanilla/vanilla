/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import ReactDOM from "react-dom";
import { dummyLogoData } from "../../mebox/state/dummyLogoData";
import { dummyNotificationsData } from "../../mebox/state/dummyNotificationsData";
import { dummyMessagesData } from "../../mebox/state/dummyMessagesData";
import { dummyGuestNavigationData, dummyNavigationData } from "../../mebox/state/dummyNavigationData";
import { Devices, IDeviceProps } from "../../DeviceChecker";
import { withDevice } from "../../../contexts/DeviceContext";
import { dummyUserDropDownData } from "../../mebox/state/dummyUserDropDownData";
import UsersModel, { IInjectableUserState } from "../../../users/UsersModel";
import classNames from "classnames";
import Container from "../../layouts/components/Container";
import { PanelWidgetHorizontalPadding } from "../../layouts/PanelLayout";
import HeaderLogo from "../../mebox/pieces/HeaderLogo";
import VanillaHeaderNav from "../../mebox/pieces/VanillaHeaderNav";
import CompactMeBox from "../../mebox/pieces/CompactMeBox";
import { connect } from "react-redux";
import { INotificationsProps } from "../../mebox/pieces/NotificationsContents";
import LanguagesDropDown from "../../LanguagesDropDown";
import { dummyOtherLanguagesData } from "../../../state/dummyOtherLanguages";
import { ButtonBaseClass } from "../../forms/Button";
import FlexSpacer from "../../FlexSpacer";

interface IProps extends IDeviceProps, IInjectableUserState {
    container?: Element; // Element containing header. Should be the default most if not all of the time.
    className?: string;
}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 * That means the exact location in the page is not that important, since it will
 * render in a specific div in the default-master.
 */
export class VanillaMobileHomeHeader extends React.Component<IProps> {
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

        const navData = isGuest ? dummyGuestNavigationData : dummyNavigationData;

        return ReactDOM.createPortal(
            <header className={classNames("vanillaHeader", "vanillaHeaderHome", this.props.className)}>
                <Container className="vanillaHeaderHome-top">
                    <PanelWidgetHorizontalPadding>
                        <div className="vanillaHeader-bar isHome">
                            <FlexSpacer className="vanillaHeaderHome-left" />
                            <HeaderLogo
                                {...dummyLogoData}
                                className="vanillaHeader-logoContainer"
                                logoClassName="vanillaHeader-logo isCentred"
                            />
                            <CompactMeBox
                                notificationsProps={notificationProps as INotificationsProps}
                                messagesProps={messagesProps as any}
                                counts={dummyUserDropDownData}
                                buttonClass="vanillaHeader-button"
                                userPhotoClass="headerDropDown-user"
                            />
                        </div>
                    </PanelWidgetHorizontalPadding>
                </Container>
                <div className="vanillaHeaderHome-bottom">
                    <div className="vanillaHeader-horizontalScroll">
                        <Container>
                            <PanelWidgetHorizontalPadding className="vanillaHeaderHome-nav">
                                <VanillaHeaderNav
                                    {...navData}
                                    linkClassName="vanillaHeader-navLink"
                                    linkContentClassName="vanillaHeader-navLinkContent"
                                    className={classNames("vanillaHeader-nav", { "vanillaHeader-guestNav": isGuest })}
                                    listClassName="isScrolled"
                                >
                                    <li className="vanillaHeaderNav-item">
                                        <LanguagesDropDown
                                            {...dummyOtherLanguagesData}
                                            renderLeft={true}
                                            className="vanillaHeader-locales"
                                            buttonClassName="vanillaHeader-localeToggle"
                                            buttonBaseClass={ButtonBaseClass.CUSTOM}
                                            widthOfParent={false}
                                            openAsModal={isMobile}
                                        />
                                    </li>
                                </VanillaHeaderNav>
                            </PanelWidgetHorizontalPadding>
                        </Container>
                    </div>
                </div>
            </header>,
            this.props.container || document.getElementById("vanillaHeader")!,
        );
    }
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(withDevice(VanillaMobileHomeHeader));
