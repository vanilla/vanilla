/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IInjectableUserState, mapUsersStoreState, isUserGuest } from "@library/features/users/userModel";
import MeBox from "@library/headers/mebox/MeBox";
import CompactMeBox from "@library/headers/mebox/pieces/CompactMeBox";
import CompactSearch from "@library/headers/mebox/pieces/CompactSearch";
import HeaderLogo from "@library/headers/mebox/pieces/HeaderLogo";
import TitleBarNav from "@library/headers/mebox/pieces/TitleBarNav";
import TitleBarNavItem from "@library/headers/mebox/pieces/TitleBarNavItem";
import { dummyNavigationData } from "@library/headers/mebox/state/dummyNavigationData";
import MobileDropDown from "@library/headers/pieces/MobileDropDown";
import { titleBarClasses, titleBarVariables } from "@library/headers/titleBarStyles";
import Container from "@library/layout/components/Container";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { withDevice, IDeviceProps, Devices } from "@library/layout/DeviceContext";
import FlexSpacer from "@library/layout/FlexSpacer";
import { ScrollOffsetContext, HashOffsetReporter } from "@library/layout/ScrollOffsetContext";
import BackLink from "@library/routing/links/BackLink";
import { IWithPagesProps, withPages } from "@library/routing/PagesContext";
import { sticky } from "@library/styles/styleHelpers";
import { LogoType } from "@library/theming/ThemeLogo";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import * as React from "react";
import ReactDOM from "react-dom";
import { connect } from "react-redux";
import { PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import { meBoxClasses } from "@library/headers/mebox/pieces/meBoxStyles";
import { ButtonTypes } from "@library/forms/buttonStyles";
import SmartLink from "@library/routing/links/SmartLink";
import { SignInIcon } from "@library/icons/common";
import DropDown from "@library/flyouts/DropDown";
import Hamburger from "@library/flyouts/Hamburger";
import { hamburgerClasses } from "@library/flyouts/hamburgerStyles";
import { styleFactory } from "@library/styles/styleUtils";

interface IProps extends IDeviceProps, IInjectableUserState, IWithPagesProps {
    container?: Element; // Element containing header. Should be the default most if not all of the time.
    className?: string;
    title?: string; // Needed for mobile flyouts
    mobileDropDownContent?: React.ReactNode; // Needed for mobile flyouts, does NOT work with hamburger
    isFixed?: boolean;
    useMobileBackButton?: boolean;
    hamburger?: React.ReactNode; // Not to be used with mobileDropDownContent
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
export class TitleBar extends React.Component<IProps, IState> {
    /** Hold the extra mebox components before rendering. */
    private static extraMeBoxComponents: React.ComponentType[] = [];

    /**
     * Register an extra component to be rendered before the mebox.
     * This will only affect larger screen sizes.
     *
     * @param component The component class to be render.
     */
    public static registerBeforeMeBox(component: React.ComponentType) {
        TitleBar.extraMeBoxComponents.push(component);
    }
    public static contextType = ScrollOffsetContext;
    public context!: React.ContextType<typeof ScrollOffsetContext>;

    public static defaultProps: Partial<IProps> = {
        mobileDropDownContent: null,
        isFixed: true,
        useMobileBackButton: true,
        hamburger: false,
    };

    public state = {
        openSearch: false,
        showingSuggestions: false,
        isScrolledOff: false,
    };
    public render() {
        const { isFixed, hamburger } = this.props;
        const isMobile = this.props.device === Devices.MOBILE || this.props.device === Devices.XS;
        const classes = titleBarClasses();
        const showMobileDropDown = isMobile && !this.state.openSearch && this.props.title;
        const showHamburger = isMobile && !this.state.openSearch && !!hamburger;
        const classesMeBox = meBoxClasses();

        const containerElement = this.props.container || document.getElementById("titleBar")!;

        return ReactDOM.createPortal(
            <HashOffsetReporter>
                <Container>
                    <PanelWidgetHorizontalPadding>
                        <div className={classNames("titleBar-bar", classes.bar)}>
                            {!this.state.openSearch &&
                                isMobile &&
                                (this.props.useMobileBackButton ? (
                                    <BackLink
                                        className={classNames(
                                            "titleBar-leftFlexBasis",
                                            "titleBar-backLink",
                                            classes.leftFlexBasis,
                                        )}
                                        linkClassName={classes.button}
                                    />
                                ) : (
                                    !hamburger && <FlexSpacer className="pageHeading-leftSpacer" />
                                ))}
                            {!isMobile && (
                                <HeaderLogo
                                    className={classNames("titleBar-logoContainer", classes.logoContainer)}
                                    logoClassName="titleBar-logo"
                                    logoType={LogoType.DESKTOP}
                                />
                            )}
                            {!this.state.openSearch && !isMobile && (
                                <TitleBarNav
                                    {...dummyNavigationData()}
                                    className={classNames("titleBar-nav", classes.nav)}
                                    linkClassName={classNames("titleBar-navLink", classes.topElement)}
                                    linkContentClassName="titleBar-navLinkContent"
                                />
                            )}
                            {showMobileDropDown && !showHamburger && (
                                <MobileDropDown
                                    title={this.props.title!}
                                    buttonClass={classNames("titleBar-mobileDropDown")}
                                >
                                    {this.props.mobileDropDownContent}
                                </MobileDropDown>
                            )}

                            {showHamburger && (
                                <>
                                    <Hamburger buttonClassName={classes.hamburger} contents={hamburger} />
                                    <FlexSpacer
                                        className={hamburgerClasses().spacer(1 + TitleBar.extraMeBoxComponents.length)}
                                    />
                                    <div className={classes.logoCenterer}>
                                        <HeaderLogo
                                            className={classNames("titleBar-logoContainer", classes.logoContainer)}
                                            logoClassName="titleBar-logo"
                                            logoType={LogoType.MOBILE}
                                        />
                                    </div>
                                </>
                            )}
                            <ConditionalWrap
                                className={classNames("titleBar-rightFlexBasis", classes.rightFlexBasis)}
                                condition={!!showMobileDropDown}
                            >
                                {!this.state.openSearch && (
                                    <div className={classes.extraMeBoxIcons}>
                                        {TitleBar.extraMeBoxComponents.map((ComponentName, index) => {
                                            return <ComponentName key={index} />;
                                        })}
                                    </div>
                                )}
                                <CompactSearch
                                    className={classNames("titleBar-compactSearch", classes.compactSearch, {
                                        isCentered: this.state.openSearch,
                                    })}
                                    focusOnMount
                                    open={this.state.openSearch}
                                    onSearchButtonClick={this.openSearch}
                                    onCloseSearch={this.closeSearch}
                                    cancelButtonClassName={classNames(
                                        "titleBar-searchCancel",
                                        classes.topElement,
                                        classes.searchCancel,
                                    )}
                                    cancelContentClassName="meBox-buttonContent"
                                    buttonClass={classNames(classes.button, {
                                        [classes.buttonOffset]: !isMobile && this.isGuest,
                                    })}
                                    showingSuggestions={this.state.showingSuggestions}
                                    onOpenSuggestions={this.setOpenSuggestions}
                                    onCloseSuggestions={this.setCloseSuggestions}
                                    buttonContentClassName={classNames(
                                        classesMeBox.buttonContent,
                                        "meBox-buttonContent",
                                    )}
                                    clearButtonClass={classes.clearButtonClass}
                                />
                                {isMobile ? this.renderMobileMeBox() : this.renderDesktopMeBox()}
                            </ConditionalWrap>
                        </div>
                    </PanelWidgetHorizontalPadding>
                </Container>
            </HashOffsetReporter>,
            containerElement,
        );
    }

    public componentDidMount() {
        const titleBarVars = titleBarVariables();
        this.context.setScrollOffset(titleBarVars.sizing.height);
        const { isFixed } = this.props;
        const classes = titleBarClasses();

        const containerElement = this.props.container || document.getElementById("titleBar")!;
        containerElement.className = classNames(
            "titleBar",
            classes.root,
            this.props.className,
            { [classes.isFixed]: isFixed },
            this.context.offsetClass,
            containerElement.className,
        );
    }

    public componentWillUnmount() {
        this.context.resetScrollOffset();
    }

    private renderMobileMeBox() {
        if (this.state.openSearch) {
            // We don't display when search is open.
            return null;
        }
        const classes = titleBarClasses();
        if (this.isGuest) {
            return (
                <SmartLink
                    className={classNames(classes.centeredButtonClass, classes.button)}
                    to={`/entry/signin?target=${window.location.pathname}`}
                >
                    <SignInIcon className={"titleBar-signInIcon"} />
                </SmartLink>
            );
        } else {
            return (
                <CompactMeBox
                    className={classNames("titleBar-button", classes.button)}
                    currentUser={this.props.currentUser}
                />
            );
        }
    }

    private renderDesktopMeBox() {
        const classes = titleBarClasses();
        if (this.isGuest) {
            return (
                <TitleBarNav className={classNames("titleBar-nav titleBar-guestNav", classes.nav)}>
                    <TitleBarNavItem
                        buttonType={ButtonTypes.TRANSPARENT}
                        linkClassName={classNames(classes.signIn, classes.guestButton)}
                        to={`/entry/signin?target=${window.location.pathname}`}
                    >
                        {t("Sign In")}
                    </TitleBarNavItem>
                    <TitleBarNavItem
                        buttonType={ButtonTypes.TRANSLUCID}
                        linkClassName={classNames(classes.register, classes.guestButton)}
                        to={`/entry/register?target=${window.location.pathname}`}
                    >
                        {t("Register")}
                    </TitleBarNavItem>
                </TitleBarNav>
            );
        } else {
            return (
                <MeBox
                    currentUser={this.props.currentUser}
                    className={classNames("titleBar-meBox", classes.meBox)}
                    buttonClassName={classes.button}
                    contentClassName={classNames("titleBar-dropDownContents", classes.dropDownContents)}
                />
            );
        }
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

    private get isGuest(): boolean {
        const currentUser = this.props.currentUser.data;
        return !!isUserGuest(currentUser);
    }
}

const withRedux = connect(mapUsersStoreState);
export default withRedux(withPages(withDevice(TitleBar)));
