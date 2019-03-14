/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import LanguagesDropDown from "@library/layout/LanguagesDropDown";
import CompactMeBox from "@library/headers/mebox/pieces/CompactMeBox";
import { VanillaHeaderNavItem } from "@library/headers/mebox/pieces/VanillaHeaderNavItem";
import { withDevice } from "@library/layout/DeviceContext";
import UsersModel, { IInjectableUserState } from "@library/features/users/UsersModel";
import { Devices, IDeviceProps } from "@library/layout/DeviceChecker";
import { dummyOtherLanguagesData } from "@library/redux/dummyOtherLanguages";
import { vanillaHeaderClasses, vanillaHeaderHomeClasses } from "@library/headers/vanillaHeaderStyles";
import { LogoType } from "@library/theming/ThemeLogo";
import VanillaHeaderNav from "@library/headers/mebox/pieces/VanillaHeaderNav";
import HeaderLogo from "@library/headers/mebox/pieces/HeaderLogo";
import FlexSpacer from "@library/layout/FlexSpacer";
import VanillaHeaderListItem from "@library/headers/mebox/pieces/VanillaHeaderListItem";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import { dummyNavigationData } from "@library/headers/mebox/state/dummyNavigationData";
import { signIn } from "@library/icons/common";

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
                                className="vanillaHeader-logoContainer"
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
                                    buttonBaseClass={ButtonTypes.CUSTOM}
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
