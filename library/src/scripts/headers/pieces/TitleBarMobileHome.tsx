/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import CompactMeBox from "@library/headers/mebox/pieces/CompactMeBox";
import { withDevice, IDeviceProps, Devices } from "@library/layout/DeviceContext";
import { IInjectableUserState, mapUsersStoreState, isUserGuest } from "@library/features/users/userModel";
import { titleBarClasses, titleBarHomeClasses } from "@library/headers/titleBarStyles";
import { LogoType } from "@library/theming/ThemeLogo";
import TitleBarNav from "@library/headers/mebox/pieces/TitleBarNav";
import HeaderLogo from "@library/headers/mebox/pieces/HeaderLogo";
import FlexSpacer from "@library/layout/FlexSpacer";
import { PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import { defaultNavigationData } from "@library/headers/mebox/state/defaultNavigationData";
import ReactDOM from "react-dom";
import classNames from "classnames";
import { connect } from "react-redux";
import Container from "@library/layout/components/Container";
import TitleBarNavItem from "@library/headers/mebox/pieces/TitleBarNavItem";
import { SignInIcon } from "@library/icons/common";
import { navigationVariables } from "../navigationVariables";
import { getCurrentLocale } from "@vanilla/i18n";
import { dummyStorybookNavigationData } from "../dummyStorybookNavigationData";

interface IProps extends IDeviceProps, IInjectableUserState {
    container?: Element; // Element containing header. Should be the default most if not all of the time.
    className?: string;
    navigationLinks?: boolean;
}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 * That means the exact location in the page is not that important, since it will
 * render in a specific div in the default-master.
 */
export class TitleBarMobileHome extends React.Component<IProps> {
    public render() {
        const currentUser = this.props.currentUser.data;
        const isGuest = isUserGuest(currentUser);
        const titleBarVars = titleBarClasses();
        const classes = titleBarHomeClasses();
        const navigationData = Object.keys(navigationVariables()[`${getCurrentLocale()}`]).includes("data")
            ? navigationVariables()[`${getCurrentLocale()}`].data
            : defaultNavigationData().data;

        const navLinks = this.props.navigationLinks
            ? defaultNavigationData().data.concat(dummyStorybookNavigationData().data)
            : navigationData;

        return ReactDOM.createPortal(
            <header className={classNames(titleBarVars.root, classes.root, this.props.className)}>
                <Container className="titleBarHome-top">
                    <PanelWidgetHorizontalPadding>
                        <div className={classNames(titleBarVars.bar, "isHome")}>
                            <FlexSpacer className={classes.left} />
                            <HeaderLogo
                                className={classNames("titleBar-logoContainer", titleBarVars.logoContainer)}
                                logoClassName="titleBar-logo isCentred"
                                logoType={LogoType.MOBILE}
                            />
                            {isGuest ? (
                                <TitleBarNav
                                    className={classNames(titleBarVars.nav, "titleBar-guest")}
                                    excludeExtraNavItems={true}
                                >
                                    <TitleBarNavItem to={`/entry/signin?target=${window.location.pathname}`}>
                                        <SignInIcon className={"titleBar-signInIcon"} />
                                    </TitleBarNavItem>
                                </TitleBarNav>
                            ) : (
                                <CompactMeBox currentUser={this.props.currentUser} />
                            )}
                        </div>
                    </PanelWidgetHorizontalPadding>
                </Container>
                <div className={classes.bottom}>
                    <div className={titleBarVars.scroll}>
                        <TitleBarNav
                            data={navLinks}
                            className={classNames("titleBar-nav", titleBarVars.nav)}
                            linkClassName={classNames("titleBar-navLink", titleBarVars.topElement)}
                            linkContentClassName="titleBar-navLinkContent"
                        />
                    </div>
                </div>
            </header>,
            this.props.container || document.getElementById("titleBar")!,
        );
    }
}

const withRedux = connect(mapUsersStoreState);
export default withRedux(withDevice(TitleBarMobileHome));
