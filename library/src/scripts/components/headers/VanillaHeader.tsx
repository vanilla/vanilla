/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/application";
import ConditionalWrap from "@library/components/ConditionalWrap";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import FlexSpacer from "@library/components/FlexSpacer";
import MobileDropDown from "@library/components/headers/pieces/MobileDropDown";
import Container from "@library/components/layouts/components/Container";
import { Panel, PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import MeBox from "@library/components/mebox/MeBox";
import CompactMeBox from "@library/components/mebox/pieces/CompactMeBox";
import CompactSearch from "@library/components/mebox/pieces/CompactSearch";
import HeaderLogo from "@library/components/mebox/pieces/HeaderLogo";
import VanillaHeaderNav from "@library/components/mebox/pieces/VanillaHeaderNav";
import VanillaHeaderNavItem from "@library/components/mebox/pieces/VanillaHeaderNavItem";
import { dummyLogoData } from "@library/components/mebox/state/dummyLogoData";
import { dummyMessagesData } from "@library/components/mebox/state/dummyMessagesData";
import { dummyNavigationData } from "@library/components/mebox/state/dummyNavigationData";
import { dummyUserDropDownData } from "@library/components/mebox/state/dummyUserDropDownData";
import BackLink from "@library/components/navigation/BackLink";
import { withDevice } from "@library/contexts/DeviceContext";
import { IWithPagesProps, withPages } from "@library/contexts/PagesContext";
import { ScrollOffsetContext } from "@library/contexts/ScrollOffsetContext";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import classNames from "classnames";
import * as React from "react";
import ReactDOM from "react-dom";
import { connect } from "react-redux";

interface IProps extends IDeviceProps, IInjectableUserState, IWithPagesProps {
    container?: Element; // Element containing header. Should be the default most if not all of the time.
    className?: string;
    title?: string; // Needed for mobile dropdown
    mobileDropDownContent?: React.ReactNode; // Needed for mobile dropdown
    showSearchIcon?: boolean;
    isFixed?: boolean;
}

interface IState {
    openSearch: boolean;
    showingSuggestions: boolean;
    isScrolledOff: boolean;
}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 * That means the exact location in the page is not that important, since it will
 * render in a specific div in the default-master.
 */
export class VanillaHeader extends React.Component<IProps, IState> {
    public static contextType = ScrollOffsetContext;
    public context!: React.ContextType<typeof ScrollOffsetContext>;

    public static defaultProps: Partial<IProps> = {
        showSearchIcon: true,
        mobileDropDownContent: null,
        isFixed: true,
    };

    public state = {
        openSearch: false,
        showingSuggestions: false,
        isScrolledOff: false,
    };
    public render() {
        const { isFixed } = this.props;
        const { isScrolledOff } = this.state;
        const currentUser = this.props.currentUser.data;
        const isMobile = this.props.device === Devices.MOBILE;
        const isGuest = currentUser && UsersModel && currentUser.userID === UsersModel.GUEST_ID;
        const countClass = "vanillaHeader-count";
        const buttonClass = "vanillaHeader-button";
        const showMobileDropDown = isMobile && !this.state.openSearch && this.props.title;

        const messagesProps = {
            ...dummyMessagesData,
            buttonClass,
            countClass: classNames(countClass, "vanillaHeader-messagesCount"),
        };

        return ReactDOM.createPortal(
            <>
                {isFixed && <div className={classNames("vanillaHeader-spacer")} />}
                <header
                    className={classNames("vanillaHeader", this.props.className, { isFixed }, this.context.offsetClass)}
                >
                    <Container>
                        <Panel className="panelLayout-fullWidth">
                            <PanelWidgetHorizontalPadding>
                                <div className="vanillaHeader-bar">
                                    {!this.state.openSearch &&
                                        isMobile && (
                                            <BackLink
                                                className="vanillaHeader-leftFlexBasis vanillaHeader-backLink"
                                                linkClassName="vanillaHeader-button"
                                                fallbackElement={<FlexSpacer className="pageHeading-leftSpacer" />}
                                            />
                                        )}

                                    {!isMobile && (
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
                                        <MobileDropDown
                                            title={this.props.title!}
                                            buttonClass="vanillaHeader-mobileDropDown"
                                        >
                                            {this.props.mobileDropDownContent}
                                        </MobileDropDown>
                                    )}

                                    <ConditionalWrap
                                        className="vanillaHeader-rightFlexBasis"
                                        condition={!!showMobileDropDown}
                                    >
                                        {this.props.showSearchIcon ? (
                                            <CompactSearch
                                                className={classNames("vanillaHeader-compactSearch", {
                                                    isCentered: this.state.openSearch,
                                                })}
                                                focusOnMount
                                                open={this.state.openSearch}
                                                onSearchButtonClick={this.openSearch}
                                                onCloseSearch={this.closeSearch}
                                                cancelButtonClassName="vanillaHeader-searchCancel"
                                                cancelContentClassName="meBox-contentHover"
                                                buttonClass="vanillaHeader-button"
                                                showingSuggestions={this.state.showingSuggestions}
                                                onOpenSuggestions={this.setOpenSuggestions}
                                                onCloseSuggestions={this.setCloseSuggestions}
                                                buttonContentClass="meBox-buttonContent"
                                            />
                                        ) : (
                                            <FlexSpacer className="compactSearch vanillaHeader-compactSearch" />
                                        )}
                                        {isGuest ? (
                                            (!this.state.openSearch || !isMobile) && (
                                                <VanillaHeaderNav className="vanillaHeader-nav vanillaHeader-guestNav">
                                                    <VanillaHeaderNavItem
                                                        linkClassName="button vanillaHeader-guestButton vanillaHeader-signIn"
                                                        to={`/entry/signin?target=${window.location.pathname}`}
                                                    >
                                                        {t("Sign in")}
                                                    </VanillaHeaderNavItem>
                                                    <VanillaHeaderNavItem
                                                        linkClassName="button vanillaHeader-guestButton vanillaHeader-register"
                                                        to={`/entry/register?target=${window.location.pathname}`}
                                                    >
                                                        {t("Register")}
                                                    </VanillaHeaderNavItem>
                                                </VanillaHeaderNav>
                                            )
                                        ) : (
                                            <React.Fragment>
                                                {!isMobile && (
                                                    <MeBox
                                                        className="vanillaHeader-meBox"
                                                        messagesProps={messagesProps as any}
                                                        counts={dummyUserDropDownData}
                                                        buttonClassName="vanillaHeader-button"
                                                        contentClassName="vanillaHeader-dropDownContents"
                                                        currentUser={this.props.currentUser}
                                                    />
                                                )}
                                                {isMobile &&
                                                    !this.state.openSearch && (
                                                        <CompactMeBox
                                                            className={"vanillaHeader-button"}
                                                            counts={dummyUserDropDownData}
                                                            buttonClass="vanillaHeader-tabButton"
                                                            userPhotoClass="headerDropDown-user"
                                                        />
                                                    )}
                                            </React.Fragment>
                                        )}
                                    </ConditionalWrap>
                                </div>
                            </PanelWidgetHorizontalPadding>
                        </Panel>
                    </Container>
                </header>
            </>,
            this.props.container || document.getElementById("vanillaHeader")!,
        );
    }

    public componentDidMount() {
        this.context.setScrollOffset(48);
    }

    public componentWillUnmount() {
        this.context.resetScrollOffset();
    }

    public openSearch = () => {
        const { pages } = this.props;
        if (pages.search) {
            pages.search.preload();
        }
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
export default withPages(withRedux(withDevice<IProps>(VanillaHeader)));
