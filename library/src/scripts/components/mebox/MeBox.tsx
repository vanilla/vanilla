/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import HeaderLogo, { IHeaderLogo } from "./pieces/HeaderLogo";
import CompactMenu from "./pieces/CompactMenu";
import VanillaHeaderNav, { IVanillaHeaderNavProps } from "./pieces/VanillaHeaderNav";
import CompactSearch from "./pieces/CompactSearch";
import NotificationsDropdown, { INotificationsDropDownProps } from "./pieces/NotificationsDropDown";
import MessagesDropDown, { IMessagesDropDownProps } from "./pieces/MessagesDropDown";
import Container from "@library/components/layouts/components/Container";
import { dummyNavigationData } from "./state/dummyNavigationData";
import LanguagesDropDown, { ILanguageDropDownProps } from "@library/components/LanguagesDropDown";
import { PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import FlexSpacer from "@library/components/FlexSpacer";
import { ButtonBaseClass } from "@library/components/forms/Button";
import UserDropdown, { UserDropDown } from "./pieces/UserDropdown";
import { IInjectableUserState } from "@library/users/UsersModel";
import UsersModel from "@library/users/UsersModel";
import { connect } from "react-redux";
import get from "lodash/get";
import Condition from "@library/components/Condition";

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
    notificationsProps: INotificationsDropDownProps;
    messagesProps: IMessagesDropDownProps;
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
    public constructor(props) {
        super(props);
        this.state = {
            openSearch: false,
        };
    }
    public render() {
        const isMobile = this.props.device === Devices.MOBILE;
        const showNonSearchItems = !this.state.openSearch && !isMobile;
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
                            {showNonSearchItems && (
                                <React.Fragment>
                                    <HeaderLogo
                                        {...this.props.logoProps}
                                        className="vanillaHeader-headerLogo hasRightMargin"
                                        logoClassName="vanillaHeader-logo"
                                        color={styles.fg}
                                    />
                                    <VanillaHeaderNav
                                        {...this.props.navigationProps}
                                        linkClassName="meBox-navLink"
                                        linkContentClassName="meBox-navLinkContent"
                                    />
                                    <LanguagesDropDown
                                        {...this.props.languagesProps}
                                        renderLeft={true}
                                        className="meBox-locale"
                                        buttonClassName="meBox-localeToggle"
                                        buttonBaseClass={ButtonBaseClass.CUSTOM}
                                        widthOfParent={false}
                                    />
                                </React.Fragment>
                            )}
                            <CompactSearch
                                className="vanillaHeader-search"
                                open={this.state.openSearch}
                                onOpenSearch={this.openSearch}
                                onCloseSearch={this.closeSearch}
                                cancelButtonClassName="meBox-searchCancel"
                            />
                            <Condition if={showNonSearchItems}>
                                <Condition if={!isGuest}>
                                    <Condition if={!isMobile}>
                                        <NotificationsDropdown
                                            {...this.props.notificationsProps}
                                            countClass="meBox-count"
                                        />
                                        <MessagesDropDown {...this.props.messagesProps} countClass="meBox-count" />
                                        <UserDropdown counts={this.props.counts} className="meBox-userDropdown" />
                                    </Condition>
                                    <Condition if={isMobile}>
                                        <
                                    </Condition>
                                </Condition>
                                <Condition if={isGuest}>
                                    <VanillaHeaderNav
                                        {...this.props.guestNavigationProps}
                                        linkClassName="meBox-navLink"
                                        linkContentClassName="meBox-navLinkContent"
                                    />
                                </Condition>
                            </Condition>
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

    private doSearch = () => {
        alert("todo !");
    };
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(MeBox);
