/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import HeaderLogo, { IHeaderLogo } from "./pieces/HeaderLogo";
import VanillaHeaderNav, { IVanillaHeaderNavProps } from "./pieces/VanillaHeaderNav";
import CompactSearch from "./pieces/CompactSearch";
import NotificationsDropdown from "./pieces/NotificationsDropDown";
import MessagesDropDown from "./pieces/MessagesDropDown";
import Container from "@library/components/layouts/components/Container";
import { ILanguageDropDownProps } from "@library/components/LanguagesDropDown";
import { PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";

import UserDropdown from "./pieces/UserDropdown";
import { IInjectableUserState } from "@library/users/UsersModel";
import UsersModel from "@library/users/UsersModel";
import { connect } from "react-redux";
import get from "lodash/get";
import CompactMeBox from "@library/components/mebox/pieces/CompactMeBox";
import { INotificationsProps } from "@library/components/mebox/pieces/NotificationsContents";
import { IMessagesContentsProps } from "@library/components/mebox/pieces/MessagesContents";

export interface IHeaderStyles {
    bgColor?: string;
    fgColor?: string;
    notificationColor?: string;
}

export interface IMeBoxProps extends IDeviceProps, IInjectableUserState {
    homePage: boolean;
    className?: string;
    logoProps: IHeaderLogo;
    navigationProps: IVanillaHeaderNavProps;
    guestNavigationProps: IVanillaHeaderNavProps;
    languagesProps: ILanguageDropDownProps;
    notificationsProps: INotificationsProps;
    messagesProps: IMessagesContentsProps;
    counts: any;
    headerStyles: IHeaderStyles;
}

interface IState {
    openSearch: boolean;
}

/**
 * Implements MeBox component. Note that this component handles all the logic of what components to display, but does not contain the content its self
 */
export class MeBox extends React.Component<IMeBoxProps, IState> {
    public state = {
        openSearch: false,
    };

    public render() {
        const isMobile = this.props.device === Devices.MOBILE;
        const currentUser = get(this.props, "currentUser.data", {
            name: null,
            userID: null,
            photoUrl: null,
        });
        const isGuest = currentUser && UsersModel && currentUser.userID === UsersModel.GUEST_ID;
        const styles = {
            fg: this.props.headerStyles && this.props.headerStyles.fgColor ? this.props.headerStyles.fgColor : "#fff",
            bg:
                this.props.headerStyles && this.props.headerStyles.bgColor
                    ? this.props.headerStyles.bgColor
                    : "#0291DB",
        };

        return (
            <header
                className={classNames("vanillaHeader", this.props.className)}
                style={{
                    backgroundColor: styles.bg,
                    color: styles.fg,
                }}
            >
                <Container>
                    <PanelWidgetHorizontalPadding>
                        <div className="vanillaHeader-bar">
                            <React.Fragment>
                                <HeaderLogo
                                    {...this.props.logoProps}
                                    className="vanillaHeader-headerLogo hasRightMargin"
                                    logoClassName="vanillaHeader-logo"
                                    color={styles.fg}
                                />
                                {!this.state.openSearch &&
                                    !isMobile && (
                                        <VanillaHeaderNav
                                            {...this.props.navigationProps}
                                            linkClassName="meBox-navLink"
                                            linkContentClassName="meBox-navLinkContent"
                                        />
                                    )}
                            </React.Fragment>
                            <CompactSearch
                                className="vanillaHeader-compactSearch"
                                open={this.state.openSearch}
                                onOpenSearch={this.openSearch}
                                onCloseSearch={this.closeSearch}
                                cancelButtonClassName="meBox-searchCancel"
                            />
                            {!isGuest && (
                                <React.Fragment>
                                    {!isMobile && (
                                        <React.Fragment>
                                            <NotificationsDropdown
                                                {...this.props.notificationsProps}
                                                countClass="meBox-count"
                                            />
                                            <MessagesDropDown {...this.props.messagesProps} countClass="meBox-count" />
                                            <UserDropdown counts={this.props.counts} className="meBox-userDropdown" />
                                        </React.Fragment>
                                    )}
                                    {isMobile && (
                                        <CompactMeBox
                                            notifcationsProps={this.props.notificationsProps}
                                            messagesProps={this.props.messagesProps}
                                            counts={this.props.counts}
                                            buttonClass="vanillaHeader-account"
                                            userPhotoClass="headerDropDown-user"
                                        />
                                    )}
                                </React.Fragment>
                            )}
                            {isGuest && (
                                <VanillaHeaderNav
                                    {...this.props.guestNavigationProps}
                                    linkClassName="meBox-navLink"
                                    linkContentClassName="meBox-navLinkContent"
                                />
                            )}
                        </div>
                    </PanelWidgetHorizontalPadding>
                </Container>
            </header>
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
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(MeBox);
