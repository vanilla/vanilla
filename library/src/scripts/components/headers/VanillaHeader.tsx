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
import { dummyNavigationData } from "@library/components/mebox/state/dummyNavigationData";
import BackLink from "@library/components/navigation/BackLink";
import { withDevice } from "@library/contexts/DeviceContext";
import { IWithPagesProps, withPages } from "@library/contexts/PagesContext";
import { ScrollOffsetContext } from "@library/contexts/ScrollOffsetContext";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import classNames from "classnames";
import * as React from "react";
import ReactDOM from "react-dom";
import { connect } from "react-redux";
import vanillaHeaderClasses from "@library/styles/vanillaHeaderStyles";
import { meBoxClasses } from "@library/styles/meBoxStyles";

interface IProps extends IDeviceProps, IInjectableUserState, IWithPagesProps {
    container?: Element; // Element containing header. Should be the default most if not all of the time.
    className?: string;
    title?: string; // Needed for mobile dropdown
    mobileDropDownContent?: React.ReactNode; // Needed for mobile dropdown
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
        const currentUser = this.props.currentUser.data;
        const isMobile = this.props.device === Devices.MOBILE;
        const isGuest = currentUser && UsersModel && currentUser.userID === UsersModel.GUEST_ID;
        const classes = vanillaHeaderClasses();
        const showMobileDropDown = isMobile && !this.state.openSearch && this.props.title;
        const classesMeBox = meBoxClasses();

        return ReactDOM.createPortal(
            <>
                {isFixed && <div className={classNames("vanillaHeader-spacer", classes.spacer)} />}
                <header
                    className={classNames(
                        "vanillaHeader",
                        classes.root,
                        this.props.className,
                        { isFixed },
                        this.context.offsetClass,
                    )}
                >
                    <Container>
                        <Panel className="panelLayout-fullWidth">
                            <PanelWidgetHorizontalPadding>
                                <div className={classNames("vanillaHeader-bar", classes.bar)}>
                                    {!this.state.openSearch &&
                                        isMobile && (
                                            <BackLink
                                                className={classNames(
                                                    "vanillaHeader-leftFlexBasis",
                                                    "vanillaHeader-backLink",
                                                    classes.leftFlexBasis,
                                                )}
                                                linkClassName={classes.button}
                                                fallbackElement={<FlexSpacer className="pageHeading-leftSpacer" />}
                                            />
                                        )}

                                    {!isMobile && (
                                        <HeaderLogo
                                            {...dummyLogoData}
                                            className={classNames("vanillaHeader-logoContainer", classes.logoContainer)}
                                            logoClassName="vanillaHeader-logo"
                                        />
                                    )}
                                    {!this.state.openSearch &&
                                        !isMobile && (
                                            <VanillaHeaderNav
                                                {...dummyNavigationData}
                                                className={classNames("vanillaHeader-nav", classes.nav)}
                                                linkClassName={classNames("vanillaHeader-navLink", classes.topElement)}
                                                linkContentClassName="vanillaHeader-navLinkContent"
                                            />
                                        )}
                                    {showMobileDropDown && (
                                        <MobileDropDown
                                            title={this.props.title!}
                                            buttonClass={classNames("vanillaHeader-mobileDropDown", classes.topElement)}
                                        >
                                            {this.props.mobileDropDownContent}
                                        </MobileDropDown>
                                    )}

                                    <ConditionalWrap
                                        className={classNames("vanillaHeader-rightFlexBasis", classes.rightFlexBasis)}
                                        condition={!!showMobileDropDown}
                                    >
                                        <CompactSearch
                                            className={classNames(
                                                "vanillaHeader-compactSearch",
                                                classes.compactSearch,
                                                {
                                                    isCentered: this.state.openSearch,
                                                },
                                            )}
                                            focusOnMount
                                            open={this.state.openSearch}
                                            onSearchButtonClick={this.openSearch}
                                            onCloseSearch={this.closeSearch}
                                            cancelButtonClassName={classNames(
                                                "vanillaHeader-searchCancel",
                                                classes.topElement,
                                                classes.searchCancel,
                                            )}
                                            cancelContentClassName="meBox-contentHover"
                                            buttonClass={classes.button}
                                            showingSuggestions={this.state.showingSuggestions}
                                            onOpenSuggestions={this.setOpenSuggestions}
                                            onCloseSuggestions={this.setCloseSuggestions}
                                            buttonContentClass={classNames(classesMeBox.buttonContent)}
                                            clearButtonClass={classes.clearButtonClass}
                                        />

                                        {isGuest ? (
                                            (!this.state.openSearch || !isMobile) && (
                                                <VanillaHeaderNav
                                                    className={classNames(
                                                        "vanillaHeader-nav vanillaHeader-guestNav",
                                                        classes.nav,
                                                    )}
                                                >
                                                    <VanillaHeaderNavItem
                                                        linkClassName={classNames(
                                                            "button",
                                                            classes.signIn,
                                                            classes.guestButton,
                                                        )}
                                                        to={`/entry/signin?target=${window.location.pathname}`}
                                                    >
                                                        {t("Sign in")}
                                                    </VanillaHeaderNavItem>
                                                    <VanillaHeaderNavItem
                                                        linkClassName={classNames(
                                                            "button",
                                                            classes.register,
                                                            classes.guestButton,
                                                        )}
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
                                                        currentUser={this.props.currentUser}
                                                        className={classNames("vanillaHeader-meBox", classes.meBox)}
                                                        buttonClassName={classes.button}
                                                        contentClassName={classNames(
                                                            "vanillaHeader-dropDownContents",
                                                            classes.dropDownContents,
                                                        )}
                                                    />
                                                )}
                                                {isMobile &&
                                                    !this.state.openSearch && (
                                                        <CompactMeBox
                                                            className={classNames(
                                                                "vanillaHeader-button",
                                                                classes.button,
                                                            )}
                                                            buttonClass={classNames(
                                                                classes.centeredButtonClass,
                                                                classes.button,
                                                            )}
                                                            userPhotoClass="headerDropDown-user"
                                                            currentUser={this.props.currentUser}
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
