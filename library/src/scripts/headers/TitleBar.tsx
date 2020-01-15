/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { isUserGuest, useUsersState } from "@library/features/users/userModel";
import Hamburger from "@library/flyouts/Hamburger";
import { hamburgerClasses } from "@library/flyouts/hamburgerStyles";
import { ButtonTypes } from "@library/forms/buttonStyles";
import MeBox from "@library/headers/mebox/MeBox";
import CompactMeBox from "@library/headers/mebox/pieces/CompactMeBox";
import CompactSearch from "@library/headers/mebox/pieces/CompactSearch";
import HeaderLogo from "@library/headers/mebox/pieces/HeaderLogo";
import { meBoxClasses } from "@library/headers/mebox/pieces/meBoxStyles";
import TitleBarNav from "@library/headers/mebox/pieces/TitleBarNav";
import TitleBarNavItem from "@library/headers/mebox/pieces/TitleBarNavItem";
import MobileDropDown from "@library/headers/pieces/MobileDropDown";
import { titleBarClasses, titleBarVariables } from "@library/headers/titleBarStyles";
import { SignInIcon } from "@library/icons/common";
import Container from "@library/layout/components/Container";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import FlexSpacer from "@library/layout/FlexSpacer";
import { PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import { HashOffsetReporter, useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import BackLink from "@library/routing/links/BackLink";
import SmartLink from "@library/routing/links/SmartLink";
import { usePageContext } from "@library/routing/PagesContext";
import { LogoType } from "@library/theming/ThemeLogo";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { useEffect, useState } from "react";
import ReactDOM from "react-dom";

interface IProps {
    container?: HTMLElement; // Element containing header. Should be the default most if not all of the time.
    className?: string;
    title?: string; // Needed for mobile flyouts
    mobileDropDownContent?: React.ReactNode; // Needed for mobile flyouts, does NOT work with hamburger
    isFixed?: boolean;
    useMobileBackButton?: boolean;
    hamburger?: React.ReactNode; // Not to be used with mobileDropDownContent
    logoUrl?: string;
    backgroundColorForMobileDropdown?: boolean; // If the left panel has a background color, we also need it here when the mobile menu's open.
}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 * That means the exact location in the page is not that important, since it will
 * render in a specific div in the default-master.
 */
export default function TitleBar(_props: IProps) {
    const props = {
        mobileDropDownContent: null,
        isFixed: true,
        useMobileBackButton: true,
        hamburger: false,
        ..._props,
    };

    const { pages } = usePageContext();
    const device = useTitleBarDevice();
    const [isSearchOpen, setIsSearchOpen] = useState(false);
    const [isShowingSuggestions, setIsShowingSuggestions] = useState(false);
    const { hamburger } = props;
    const isCompact = device === TitleBarDevices.COMPACT;
    const classes = titleBarClasses();
    const showMobileDropDown = isCompact && !isSearchOpen && !!props.title;
    const showHamburger = isCompact && !isSearchOpen && !!hamburger;
    const classesMeBox = meBoxClasses();
    const { currentUser } = useUsersState();
    const isGuest = isUserGuest(currentUser.data);

    const headerContent = (
        <HashOffsetReporter>
            <Container>
                <PanelWidgetHorizontalPadding>
                    <div className={classNames("titleBar-bar", classes.bar)}>
                        {!isSearchOpen &&
                            isCompact &&
                            (props.useMobileBackButton ? (
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
                        {!isCompact && (
                            <HeaderLogo
                                className={classNames("titleBar-logoContainer", classes.logoContainer)}
                                logoClassName="titleBar-logo"
                                logoType={LogoType.DESKTOP}
                            />
                        )}
                        {!isSearchOpen && !isCompact && (
                            <TitleBarNav
                                className={classNames("titleBar-nav", classes.nav)}
                                linkClassName={classNames("titleBar-navLink", classes.topElement)}
                                linkContentClassName="titleBar-navLinkContent"
                            />
                        )}
                        {showMobileDropDown && !showHamburger && (
                            <MobileDropDown
                                title={props.title!}
                                buttonClass={classNames("titleBar-mobileDropDown")}
                                hasBackgroundColor={props.backgroundColorForMobileDropdown}
                            >
                                {props.mobileDropDownContent}
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
                            {!isSearchOpen && (
                                <div className={classes.extraMeBoxIcons}>
                                    {TitleBar.extraMeBoxComponents.map((ComponentName, index) => {
                                        return <ComponentName key={index} />;
                                    })}
                                </div>
                            )}
                            <CompactSearch
                                className={classNames("titleBar-compactSearch", classes.compactSearch, {
                                    isCentered: isSearchOpen,
                                })}
                                focusOnMount
                                open={isSearchOpen}
                                onSearchButtonClick={() => {
                                    if (pages.search) {
                                        pages.search.preload();
                                    }
                                    setIsSearchOpen(true);
                                }}
                                onCloseSearch={() => {
                                    setIsSearchOpen(false);
                                }}
                                cancelButtonClassName={classNames(
                                    "titleBar-searchCancel",
                                    classes.topElement,
                                    classes.searchCancel,
                                )}
                                cancelContentClassName="meBox-buttonContent"
                                buttonClass={classNames(classes.button, {
                                    [classes.buttonOffset]: !isCompact && isGuest,
                                })}
                                showingSuggestions={isShowingSuggestions}
                                onOpenSuggestions={() => setIsShowingSuggestions(true)}
                                onCloseSuggestions={() => setIsShowingSuggestions(false)}
                                buttonContentClassName={classNames(classesMeBox.buttonContent, "meBox-buttonContent")}
                                clearButtonClass={classes.clearButtonClass}
                            />
                            {isCompact ? isSearchOpen && <MobileMeBox /> : <DesktopMeBox />}
                        </ConditionalWrap>
                    </div>
                </PanelWidgetHorizontalPadding>
            </Container>
        </HashOffsetReporter>
    );

    const { resetScrollOffset, setScrollOffset, offsetClass } = useScrollOffset();
    const containerElement = props.container || document.getElementById("titleBar");

    const containerClasses = classNames(
        "titleBar",
        classes.root,
        props.className,
        { [classes.isFixed]: props.isFixed },
        offsetClass,
    );
    useEffect(() => {
        setScrollOffset(titleBarVariables().sizing.height);
        containerElement?.setAttribute("class", containerClasses);

        return () => {
            resetScrollOffset();
        };
    }, [setScrollOffset, resetScrollOffset, containerElement, containerClasses]);

    if (containerElement) {
        return ReactDOM.createPortal(headerContent, containerElement);
    } else {
        return <header className={containerClasses}>{headerContent}</header>;
    }
}

function DesktopMeBox() {
    const classes = titleBarClasses();
    const { currentUser } = useUsersState();
    const isGuest = isUserGuest(currentUser.data);
    if (isGuest) {
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
                currentUser={currentUser}
                className={classNames("titleBar-meBox", classes.meBox)}
                buttonClassName={classes.button}
                contentClassName={classNames("titleBar-dropDownContents", classes.dropDownContents)}
            />
        );
    }
}

// For backwards compatibility
export { TitleBar };

function MobileMeBox() {
    // if (isSearchOpen) {
    //     // We don't display when search is open.
    //     return null;
    // }
    const { currentUser } = useUsersState();
    const isGuest = isUserGuest(currentUser.data);
    const classes = titleBarClasses();
    if (isGuest) {
        return (
            <SmartLink
                className={classNames(classes.centeredButtonClass, classes.button)}
                to={`/entry/signin?target=${window.location.pathname}`}
            >
                <SignInIcon className={"titleBar-signInIcon"} />
            </SmartLink>
        );
    } else {
        return <CompactMeBox className={classNames("titleBar-button", classes.button)} currentUser={currentUser} />;
    }
}

/** Hold the extra mebox components before rendering. */
TitleBar.extraMeBoxComponents = [] as React.ComponentType[];

/**
 * Register an extra component to be rendered before the mebox.
 * This will only affect larger screen sizes.
 *
 * @param component The component class to be render.
 */
TitleBar.registerBeforeMeBox = (component: React.ComponentType) => {
    TitleBar.extraMeBoxComponents.pop();
    TitleBar.extraMeBoxComponents.push(component);
};
