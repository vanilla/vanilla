/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import ReactDOM from "react-dom";
import classNames from "classnames";
import { connect } from "react-redux";
import VanillaHeaderNavItem from "@library/components/mebox/pieces/VanillaHeaderNavItem";
import { signIn } from "@library/components/icons";
import VanillaHeaderListItem from "@library/components/mebox/pieces/VanillaHeaderListItem";
import { dummyNavigationData } from "@library/components/mebox/state/dummyNavigationData";
import vanillaHeaderClasses, { vanillaHeaderHomeClasses } from "@library/styles/vanillaHeaderStyles";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import Container from "@library/components/layouts/components/Container";
import FlexSpacer from "@library/components/FlexSpacer";
import HeaderLogo from "@library/components/mebox/pieces/HeaderLogo";
import { PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import { dummyLogoData } from "@library/components/mebox/state/dummyLogoData";
import VanillaHeaderNav from "@library/components/mebox/pieces/VanillaHeaderNav";
import CompactMeBox from "@library/components/mebox/pieces/CompactMeBox";
import LanguagesDropDown from "@library/components/LanguagesDropDown";
import { dummyOtherLanguagesData } from "@library/state/dummyOtherLanguages";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { withDevice } from "@library/contexts/DeviceContext";

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
        const headerClasses = vanillaHeaderClasses();
        const classes = vanillaHeaderHomeClasses();

        return ReactDOM.createPortal(
            <header className={classNames(headerClasses.root, classes.root, this.props.className)}>
                <Container className="vanillaHeaderHome-top">
                    <PanelWidgetHorizontalPadding>
                        <div className={classNames(headerClasses.bar, "isHome")}>
                            <FlexSpacer className={classes.left} />
                            <HeaderLogo
                                {...dummyLogoData}
                                className="vanillaHeader-logoContainer"
                                logoClassName="vanillaHeader-logo isCentred"
                            />
                            {isGuest ? (
                                <VanillaHeaderNav className={classNames(headerClasses.nav, "vanillaHeader-guest")}>
                                    <VanillaHeaderNavItem to={`/entry/signin?target=${window.location.pathname}`}>
                                        {signIn("vanillaHeader-signInIcon")}
                                    </VanillaHeaderNavItem>
                                </VanillaHeaderNav>
                            ) : (
                                <CompactMeBox
                                    buttonClass={headerClasses.button}
                                    userPhotoClass="headerDropDown-user"
                                    currentUser={this.props.currentUser}
                                />
                            )}
                        </div>
                    </PanelWidgetHorizontalPadding>
                </Container>
                <div className={classes.bottom}>
                    <div className="vanillaHeader-horizontalScroll">
                        <VanillaHeaderNav
                            {...dummyNavigationData}
                            linkClassName="vanillaHeader-navLink"
                            linkContentClassName="vanillaHeader-navLinkContent"
                            className={classNames(headerClasses.nav, "isScrolled")}
                        >
                            <VanillaHeaderListItem>
                                <LanguagesDropDown
                                    {...dummyOtherLanguagesData}
                                    renderLeft={true}
                                    className="vanillaHeader-locales"
                                    buttonClassName="vanillaHeader-localeToggle"
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
