/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import UsersModel, { IInjectableUserState } from "@library/features/users/UsersModel";
import MeBox from "@library/headers/mebox/MeBox";
import CompactMeBox from "@library/headers/mebox/pieces/CompactMeBox";
import CompactSearch from "@library/headers/mebox/pieces/CompactSearch";
import HeaderLogo from "@library/headers/mebox/pieces/HeaderLogo";
import VanillaHeaderNav from "@library/headers/mebox/pieces/VanillaHeaderNav";
import VanillaHeaderNavItem from "@library/headers/mebox/pieces/VanillaHeaderNavItem";
import { dummyNavigationData } from "@library/headers/mebox/state/dummyNavigationData";
import MobileDropDown from "@library/headers/pieces/MobileDropDown";
import { vanillaHeaderClasses, vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import Container from "@library/layout/components/Container";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { withDevice, IDeviceProps, Devices } from "@library/layout/DeviceContext";
import FlexSpacer from "@library/layout/FlexSpacer";
import { ScrollOffsetContext } from "@library/layout/ScrollOffsetContext";
import BackLink from "@library/routing/links/BackLink";
import { IWithPagesProps, withPages } from "@library/routing/PagesContext";
import { sticky } from "@library/styles/styleHelpers";
import { LogoType } from "@library/theming/ThemeLogo";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import * as React from "react";
import ReactDOM from "react-dom";
import { connect } from "react-redux";
import { style } from "typestyle";
import { Panel, PanelWidgetHorizontalPadding } from "../layout/PanelLayout";
import { meBoxClasses } from "@library/headers/mebox/pieces/meBoxStyles";
import { ButtonTypes } from "@library/forms/buttonStyles";

interface IProps extends IDeviceProps, IInjectableUserState, IWithPagesProps {
    container?: Element; // Element containing header. Should be the default most if not all of the time.
    className?: string;
    title?: string; // Needed for mobile flyouts
    mobileDropDownContent?: React.ReactNode; // Needed for mobile flyouts
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

        const fixedClass = style({
            ...sticky(),
            $debugName: "isFixed",
            top: 0,
            zIndex: 1,
        });

        const outerCssClasses = classNames(
            "vanillaHeader",
            classes.root,
            this.props.className,
            { [fixedClass]: isFixed },
            this.context.offsetClass,
        );

        const containerElement = this.props.container || document.getElementById("vanillaHeader")!;
        containerElement.classList.value = outerCssClasses;

        return ReactDOM.createPortal(
            <Container>
                <Panel className="panelLayout-fullWidth">
                    <PanelWidgetHorizontalPadding>
                        <div className={classNames("vanillaHeader-bar", classes.bar)}>
                            {!this.state.openSearch && isMobile && (
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
                                    className={classNames("vanillaHeader-logoContainer", classes.logoContainer)}
                                    logoClassName="vanillaHeader-logo"
                                    logoType={LogoType.DESKTOP}
                                />
                            )}
                            {!this.state.openSearch && !isMobile && (
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
                                    className={classNames("vanillaHeader-compactSearch", classes.compactSearch, {
                                        isCentered: this.state.openSearch,
                                    })}
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
                                    buttonContentClassName={classNames(classesMeBox.buttonContent)}
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
                                                buttonType={ButtonTypes.TRANSLUCID}
                                                linkClassName={classNames(classes.signIn, classes.guestButton)}
                                                to={`/entry/signin?target=${window.location.pathname}`}
                                            >
                                                {t("Sign in")}
                                            </VanillaHeaderNavItem>
                                            <VanillaHeaderNavItem
                                                buttonType={ButtonTypes.INVERTED}
                                                linkClassName={classNames(classes.register, classes.guestButton)}
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
                                        {isMobile && !this.state.openSearch && (
                                            <CompactMeBox
                                                className={classNames("vanillaHeader-button", classes.button)}
                                                buttonClass={classNames(classes.centeredButtonClass, classes.button)}
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
            </Container>,
            containerElement,
        );
    }

    public componentDidMount() {
        const headerVars = vanillaHeaderVariables();
        this.context.setScrollOffset(headerVars.sizing.height);
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
export default withRedux(withPages(withDevice(VanillaHeader)));
