/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import CompactMeBox from "@library/headers/mebox/pieces/CompactMeBox";
import VanillaHeaderNavItem from "@library/headers/mebox/pieces/VanillaHeaderNavItem";
import { withDevice, IDeviceProps, Devices } from "@library/layout/DeviceContext";
import { IInjectableUserState, mapUsersStoreState, isUserGuest } from "@library/features/users/userModel";
import { vanillaHeaderClasses, vanillaHeaderHomeClasses } from "@library/headers/vanillaHeaderStyles";
import { LogoType } from "@library/theming/ThemeLogo";
import VanillaHeaderNav from "@library/headers/mebox/pieces/VanillaHeaderNav";
import HeaderLogo from "@library/headers/mebox/pieces/HeaderLogo";
import FlexSpacer from "@library/layout/FlexSpacer";
import { PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import { dummyNavigationData } from "@library/headers/mebox/state/dummyNavigationData";
import { signIn } from "@library/icons/common";
import ReactDOM from "react-dom";
import classNames from "classnames";
import { connect } from "react-redux";
import Container from "@library/layout/components/Container";

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
    public render() {
        const currentUser = this.props.currentUser.data;
        const isGuest = isUserGuest(currentUser);
        const headerClasses = vanillaHeaderClasses();
        const classes = vanillaHeaderHomeClasses();

        return ReactDOM.createPortal(
            <header className={classNames(headerClasses.root, classes.root, this.props.className)}>
                <Container className="vanillaHeaderHome-top">
                    <PanelWidgetHorizontalPadding>
                        <div className={classNames(headerClasses.bar, "isHome")}>
                            <FlexSpacer className={classes.left} />
                            <HeaderLogo
                                className={classNames("vanillaHeader-logoContainer", headerClasses.logoContainer)}
                                logoClassName="vanillaHeader-logo isCentred"
                                logoType={LogoType.MOBILE}
                            />
                            {isGuest ? (
                                <VanillaHeaderNav className={classNames(headerClasses.nav, "vanillaHeader-guest")}>
                                    <VanillaHeaderNavItem to={`/entry/signin?target=${window.location.pathname}`}>
                                        {signIn("vanillaHeader-signInIcon")}
                                    </VanillaHeaderNavItem>
                                </VanillaHeaderNav>
                            ) : (
                                <CompactMeBox currentUser={this.props.currentUser} />
                            )}
                        </div>
                    </PanelWidgetHorizontalPadding>
                </Container>
                <div className={classes.bottom}>
                    <div className={headerClasses.scroll}>
                        <VanillaHeaderNav
                            {...dummyNavigationData}
                            className={classNames("vanillaHeader-nav", headerClasses.nav)}
                            linkClassName={classNames("vanillaHeader-navLink", headerClasses.topElement)}
                            linkContentClassName="vanillaHeader-navLinkContent"
                        />
                    </div>
                </div>
            </header>,
            this.props.container || document.getElementById("vanillaHeader")!,
        );
    }
}

const withRedux = connect(mapUsersStoreState);
export default withRedux(withDevice(VanillaMobileHomeHeader));
