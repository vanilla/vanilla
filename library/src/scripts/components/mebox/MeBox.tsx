/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import FlexSpacer from "../flexSpacer";
import HeaderLogo, { IHeaderLogo } from "./pieces/HeaderLogo";
import CompactMenu from "./pieces/CompactMenu";
import HeaderNavigation, { IHeaderNavigationProps } from "./pieces/HeaderNavigation";
import CompactSearch from "./pieces/CompactSearch";
import NotificationsDropdown, { INotificationsDropDownProps } from "./pieces/NotificationsDropdown";
import MessagesDropDown, { IMessagesDropDownProps } from "./pieces/MessagesDropdown";
import UserDropdown, { IUserDropDownProps } from "./pieces/UserDropdown";
import Container from "@knowledge/layouts/components/Container";
import { PanelWidgetHorizontalPadding } from "@knowledge/layouts/PanelLayout";
import LanguagesDropDown, { ILanguageDropDownProps } from "@knowledge/modules/article/components/LanguagesDropDown";
import { dummyNavigationData } from "./state/dummyNavigationData";

export interface IHeaderStyles {
    bgColor?: string;
    fgColor?: string;
    notificationColor?: string;
}

export interface IMeBoxProps extends IDeviceProps {
    homePage: boolean;
    className?: string;
    logoData: IHeaderLogo;
    navigationData: IHeaderNavigationProps;
    languagesData: ILanguageDropDownProps;
    notificationsData: INotificationsDropDownProps;
    messagesData: IMessagesDropDownProps;
    userDropDownData: IUserDropDownProps;
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
        const hideNonSearchElements = this.state.openSearch && isMobile;
        const styles = {
            fg: this.props.headerStyles && this.props.headerStyles.fgColor ? this.props.headerStyles.fgColor : "#fff",
            bg:
                this.props.headerStyles && this.props.headerStyles.bgColor
                    ? this.props.headerStyles.bgColor
                    : "#0291DB",
        };
        let content;
        if (isMobile) {
            content = (
                <React.Fragment>
                    <div className={classNames("vanillaHeader-homeTop")}>
                        <FlexSpacer className="vanillaHeader-flexSpacer" />
                        <HeaderLogo {...this.props.logoData} logoClassName="vanillaHeader-logo" />
                        <CompactMenu {...this.props} />
                    </div>
                    <div className={classNames("vanillaHeader-homeBottom")}>
                        <HeaderNavigation data={dummyNavigationData} />
                    </div>
                </React.Fragment>
            );
        } else {
            content = (
                <React.Fragment>
                    <div className="vanillaHeader-bar">
                        {!hideNonSearchElements && (
                            <React.Fragment>
                                <HeaderLogo
                                    {...this.props.logoData}
                                    className="vanillaHeader-headerLogo hasRightMargin"
                                    logoClassName="vanillaHeader-logo"
                                    color={styles.fg}
                                />
                                <HeaderNavigation {...this.props.navigationData} />
                                <LanguagesDropDown {...this.props.languagesData} />
                            </React.Fragment>
                        )}
                        <CompactSearch onClick={this.toggleSearch} open={this.state.openSearch} />
                        {!hideNonSearchElements && (
                            <React.Fragment>
                                <NotificationsDropdown {...this.props.notificationsData} />
                                <MessagesDropDown {...this.props.messagesData} />
                                <UserDropdown {...this.props.userDropDownData} />
                            </React.Fragment>
                        )}
                    </div>
                </React.Fragment>
            );
        }
        return (
            <header
                className={classNames("vanillaHeader", this.props.className)}
                style={{
                    backgroundColor: styles.bg,
                    color: styles.fg,
                }}
            >
                <Container>
                    <PanelWidgetHorizontalPadding>{content}</PanelWidgetHorizontalPadding>
                </Container>
            </header>
        );
    }

    public toggleSearch = () => {
        this.setState({
            openSearch: !this.state.openSearch,
        });
    };
}

export default withDevice(MeBox);
