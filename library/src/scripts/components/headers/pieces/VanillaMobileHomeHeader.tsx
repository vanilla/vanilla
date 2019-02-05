/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import ReactDOM from "react-dom";
import { dummyLogoData } from "../../mebox/state/dummyLogoData";
import { dummyMessagesData } from "../../mebox/state/dummyMessagesData";
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
import VanillaHeaderNavItem from "@library/components/mebox/pieces/VanillaHeaderNavItem";
import { signIn } from "@library/components/icons";
import VanillaHeaderListItem from "@library/components/mebox/pieces/VanillaHeaderListItem";
import { dummyNavigationData } from "@library/components/mebox/state/dummyNavigationData";
import { style } from "typestyle";
import vanillaHeaderClasses, { vanillaHeaderHomeClasses } from "@library/components/headers/vanillaHeaderStyles";

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
        const headerHomeClasses = vanillaHeaderHomeClasses();
        const currentUser = this.props.currentUser.data;
        const isMobile = this.props.device === Devices.MOBILE;
        const isGuest = currentUser && UsersModel && currentUser.userID === UsersModel.GUEST_ID;
        const countClass = "vanillaHeader-count";
        const buttonClass = "vanillaHeader-button";

        const notificationProps: INotificationsProps = {
            data: [],
            userSlug: currentUser!.name,
            countClass: classNames(countClass, "vanillaHeader-notificationsCount"),
        };

        const messagesProps = {
            ...dummyMessagesData,
            buttonClass,
            countClass: classNames(countClass, "vanillaHeader-messagesCount"),
        };

        const classes = vanillaHeaderClasses();
        const classesHome = vanillaHeaderHomeClasses();

        return ReactDOM.createPortal(
            <header
                className={classNames(
                    "vanillaHeader",
                    "vanillaHeaderHome",
                    classes.root,
                    classesHome.root,
                    this.props.className,
                )}
            >
                <Container className="vanillaHeaderHome-top">
                    <PanelWidgetHorizontalPadding>
                        <div className={classNames("vanillaHeader-bar", "isHome", classes.bar)}>
                            <FlexSpacer className={classNames("vanillaHeaderHome-left", classesHome.left)} />
                            <HeaderLogo
                                {...dummyLogoData}
                                className={classNames("vanillaHeader-logoContainer", classes.logoContainer)}
                                logoClassName="vanillaHeader-logo isCentred"
                            />
                            {isGuest ? (
                                <VanillaHeaderNav
                                    className={classNames("vanillaHeader-nav vanillaHeader-guestNav", classes.nav)}
                                >
                                    <VanillaHeaderNavItem to={`/entry/signin?target=${window.location.pathname}`}>
                                        {signIn("vanillaHeader-signInIcon")}
                                    </VanillaHeaderNavItem>
                                </VanillaHeaderNav>
                            ) : (
                                <CompactMeBox
                                    counts={dummyUserDropDownData}
                                    buttonClass={classNames("vanillaHeader-button", classes.button)}
                                    userPhotoClass="headerDropDown-user"
                                />
                            )}
                        </div>
                    </PanelWidgetHorizontalPadding>
                </Container>
                <div className={classNames("vanillaHeaderHome-bottom", headerHomeClasses.bottom)}>
                    <div className={classNames("vanillaHeader-horizontalScroll", classes.horizontalScroll)}>
                        <VanillaHeaderNav
                            {...dummyNavigationData}
                            linkClassName={classNames("vanillaHeader-navLink", classes.topElement)}
                            linkContentClassName="vanillaHeader-navLinkContent"
                            className={classNames("vanillaHeader-nav", "isScrolled", classes.nav)}
                        >
                            <VanillaHeaderListItem>
                                <LanguagesDropDown
                                    {...dummyOtherLanguagesData}
                                    renderLeft={true}
                                    className={classNames("vanillaHeader-locales", classes.locales)}
                                    buttonClassName={classNames(
                                        "vanillaHeader-localeToggle",
                                        classes.topElement,
                                        classes.localeToggle,
                                    )}
                                    buttonBaseClass={ButtonBaseClass.CUSTOM}
                                    widthOfParent={false}
                                    openAsModal={isMobile}
                                />
                            </VanillaHeaderListItem>
                        </VanillaHeaderNav>
                    </div>
                </div>
            </header>,
            this.props.container || document.getElementById("vanillaHeader")!,
        );
    }
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(withDevice(VanillaMobileHomeHeader));
