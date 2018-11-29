/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import ReactDOM from "react-dom";
import MeBox from "@library/components/mebox/MeBox";
import { dummyLogoData } from "@library/components/mebox/state/dummyLogoData";
import { dummyNotificationsData } from "@library/components/mebox/state/dummyNotificationsData";
import { dummyMessagesData } from "@library/components/mebox/state/dummyMessagesData";
import { dummyNavigationData } from "@library/components/mebox/state/dummyNavigationData";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import { dummyUserDropDownData } from "@library/components/mebox/state/dummyUserDropDownData";
import classNames from "classnames";
import Container from "@library/components/layouts/components/Container";
import { PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import HeaderLogo from "@library/components/mebox/pieces/HeaderLogo";
import VanillaHeaderNav from "@library/components/mebox/pieces/VanillaHeaderNav";
import CompactSearch from "@library/components/mebox/pieces/CompactSearch";
import CompactMeBox from "@library/components/mebox/pieces/CompactMeBox";
import { connect } from "react-redux";
import { INotificationsProps } from "@library/components/mebox/pieces/NotificationsContents";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import MobileDropDown from "@library/components/headers/pieces/MobileDropDown";
import ConditionalWrap from "@library/components/ConditionalWrap";
import FlexSpacer from "@library/components/FlexSpacer";
import BackLink from "@library/components/navigation/BackLink";
import { signIn } from "@library/components/icons";
import VanillaHeaderNavItem from "@library/components/mebox/pieces/VanillaHeaderNavItem";

interface IProps extends IDeviceProps, IInjectableUserState {
    container?: Element; // Element containing header. Should be the default most if not all of the time.
    className?: string;
    title?: string; // Needed for mobile dropdown
    mobileDropDownContent?: React.ReactNode; // Needed for mobile dropdown
    backUrl?: string;
    onSearchIconClick?: () => void;
}

interface IState {
    openSearch: boolean;
    showingSuggestions: boolean;
}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 * That means the exact location in the page is not that important, since it will
 * render in a specific div in the default-master.
 */
export class VanillaHeader extends React.Component<IProps, IState> {
    public state = {
        openSearch: false,
        showingSuggestions: false,
    };
    public render() {
        const currentUser = this.props.currentUser.data;
        const isMobile = this.props.device === Devices.MOBILE;
        const isGuest = currentUser && UsersModel && currentUser.userID === UsersModel.GUEST_ID;
        const countClass = "vanillaHeader-count";
        const buttonClass = "vanillaHeader-button";
        const showMobileDropDown = isMobile && !this.state.openSearch && this.props.title;

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

        const leftEl = !!this.props.backUrl ? (
            <FlexSpacer className="pageHeading-leftSpacer" />
        ) : (
            <BackLink url={this.props.backUrl} className="pageHeading-backLink" />
        );

        const onSearchClick = this.props.onSearchIconClick ? this.props.onSearchIconClick : this.openSearch;

        return ReactDOM.createPortal(
            <header className={classNames("vanillaHeader", this.props.className)}>
                <Container>
                    <PanelWidgetHorizontalPadding>
                        <div className="vanillaHeader-bar">
                            {!this.state.openSearch && isMobile ? (
                                leftEl
                            ) : (
                                <HeaderLogo
                                    {...dummyLogoData}
                                    className="vanillaHeader-logoContainer"
                                    logoClassName="vanillaHeader-logo"
                                />
                            )}
                            {!this.state.openSearch &&
                                !isMobile && (
                                    <VanillaHeaderNav
                                        {...dummyNavigationData}
                                        className="vanillaHeader-nav"
                                        linkClassName="vanillaHeader-navLink"
                                        linkContentClassName="vanillaHeader-navLinkContent"
                                    />
                                )}
                            {showMobileDropDown && (
                                <MobileDropDown title={this.props.title!} buttonClass="vanillaHeader-mobileDropDown">
                                    {this.props.mobileDropDownContent}
                                </MobileDropDown>
                            )}

                            <ConditionalWrap className="vanillaHeader-rightFlexBasis" condition={!!showMobileDropDown}>
                                <CompactSearch
                                    className={classNames("vanillaHeader-compactSearch", {
                                        isCentered: this.state.openSearch,
                                    })}
                                    open={this.state.openSearch}
                                    onSearchButtonClick={onSearchClick}
                                    onCloseSearch={this.closeSearch}
                                    cancelButtonClassName="vanillaHeader-searchCancel"
                                    buttonClass="vanillaHeader-button"
                                    showingSuggestions={this.state.showingSuggestions}
                                    onOpenSuggestions={this.setOpenSuggestions}
                                    onCloseSuggestions={this.setCloseSuggestions}
                                />
                                {isGuest ? (
                                    (!this.state.openSearch || !isMobile) && (
                                        <VanillaHeaderNav
                                            linkClassName="vanillaHeader-navLink"
                                            linkContentClassName="vanillaHeader-navLinkContent"
                                            className="vanillaHeader-nav vanillaHeader-guestNav"
                                        >
                                            <VanillaHeaderNavItem
                                                to={`/entry/signin?target=${window.location.pathname}`}
                                            >
                                                {signIn("vanillaHeader-signInIcon")}
                                            </VanillaHeaderNavItem>
                                        </VanillaHeaderNav>
                                    )
                                ) : (
                                    <React.Fragment>
                                        {!isMobile && (
                                            <MeBox
                                                className="vanillaHeader-meBox"
                                                notificationsProps={notificationProps}
                                                messagesProps={messagesProps as any}
                                                counts={dummyUserDropDownData}
                                                buttonClassName="vanillaHeader-button"
                                                contentClassName="vanillaHeader-dropDownContents"
                                            />
                                        )}
                                        {isMobile &&
                                            !this.state.openSearch && (
                                                <CompactMeBox
                                                    counts={dummyUserDropDownData}
                                                    buttonClass="vanillaHeader-button"
                                                    userPhotoClass="headerDropDown-user"
                                                />
                                            )}
                                    </React.Fragment>
                                )}
                            </ConditionalWrap>
                        </div>
                    </PanelWidgetHorizontalPadding>
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
export default withRedux(withDevice<IProps>(VanillaHeader));
