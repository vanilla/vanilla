/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useBannerContext } from "@library/banner/BannerContext";
import { isUserGuest, useUsersState } from "@library/features/users/userModel";
import Hamburger from "@library/flyouts/Hamburger";
import { ButtonTypes } from "@library/forms/buttonStyles";
import MeBox from "@library/headers/mebox/MeBox";
import CompactMeBox from "@library/headers/mebox/pieces/CompactMeBox";
import CompactSearch from "@library/headers/mebox/pieces/CompactSearch";
import HeaderLogo from "@library/headers/mebox/pieces/HeaderLogo";
import { meBoxClasses } from "@library/headers/mebox/pieces/meBoxStyles";
import TitleBarNav from "@library/headers/mebox/pieces/TitleBarNav";
import TitleBarNavItem from "@library/headers/mebox/pieces/TitleBarNavItem";
import { titleBarClasses, titleBarLogoClasses, titleBarVariables } from "@library/headers/titleBarStyles";
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
import React, { useDebugValue, useEffect, useRef, useState } from "react";
import ReactDOM from "react-dom";
import { animated, useSpring } from "react-spring";

interface IProps {
    container?: HTMLElement; // Element containing header. Should be the default most if not all of the time.
    className?: string;
    title?: string; // Needed for mobile flyouts
    mobileDropDownContent?: React.ReactNode; // Needed for mobile flyouts, does NOT work with hamburger
    isFixed?: boolean;
    useMobileBackButton?: boolean;
    hamburger?: React.ReactNode; // Not to be used with mobileDropDownContent
    logoUrl?: string;
    hasSubNav?: boolean;
    backgroundColorForMobileDropdown?: boolean; // If the left panel has a background color, we also need it here when the mobile menu's open.
    extraBurgerNavigation?: React.ReactNode;
}

export enum LogoAlignment {
    LEFT = "left",
    CENTER = "center",
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

    const { bgProps, bg2Props, logoProps } = useScrollTransition();

    const { pages } = usePageContext();
    const device = useTitleBarDevice();
    const [isSearchOpen, setIsSearchOpen] = useState(false);
    const [isShowingSuggestions, setIsShowingSuggestions] = useState(false);
    const { hamburger } = props;
    const isCompact = device === TitleBarDevices.COMPACT;
    const showMobileDropDown = isCompact && !isSearchOpen && !!props.title;
    const classesMeBox = meBoxClasses();
    const { currentUser } = useUsersState();
    const isGuest = isUserGuest(currentUser.data);
    const vars = titleBarVariables();
    const classes = titleBarClasses();
    const logoClasses = titleBarLogoClasses();
    const showSubNav = device === TitleBarDevices.COMPACT && props.hasSubNav;
    const meBox = isCompact ? !isSearchOpen && <MobileMeBox /> : <DesktopMeBox />;

    const headerContent = (
        <HashOffsetReporter>
            <animated.div {...bgProps} className={classes.bg1}></animated.div>
            <animated.div {...bg2Props} className={classes.bg2}>
                {/* Cannot be a background image there will be flickering. */}
                {vars.colors.bgImage && (
                    <img
                        src={vars.colors.bgImage}
                        className={classes.bgImage}
                        alt={"titleBarImage"}
                        aria-hidden={true}
                    />
                )}
            </animated.div>
            <Container>
                <PanelWidgetHorizontalPadding>
                    <div className={classNames(classes.bar, { isHome: showSubNav })}>
                        {isCompact &&
                            (props.useMobileBackButton ? (
                                <BackLink className={classes.leftFlexBasis} linkClassName={classes.button} />
                            ) : (
                                hamburger && <FlexSpacer className="pageHeading-leftSpacer" />
                            ))}
                        {!isCompact && (
                            <animated.div className={classes.logoAnimationWrap} {...logoProps}>
                                <HeaderLogo
                                    className={classNames("titleBar-logoContainer", classes.logoContainer)}
                                    logoClassName="titleBar-logo"
                                    logoType={LogoType.DESKTOP}
                                />
                            </animated.div>
                        )}
                        {!isSearchOpen && !isCompact && (
                            <TitleBarNav
                                className={classes.nav}
                                linkClassName={classes.topElement}
                                linkContentClassName="titleBar-navLinkContent"
                            />
                        )}
                        {isCompact && (
                            <>
                                <Hamburger
                                    className={classes.hamburger}
                                    extraBurgerNavigation={props.extraBurgerNavigation}
                                />
                                {!isSearchOpen && (
                                    <div className={classNames(classes.logoCenterer, logoClasses.mobileLogo)}>
                                        <animated.span {...logoProps}>
                                            <HeaderLogo
                                                className={classes.logoContainer}
                                                logoClassName="titleBar-logo"
                                                logoType={LogoType.MOBILE}
                                            />
                                        </animated.span>
                                    </div>
                                )}
                            </>
                        )}
                        <ConditionalWrap className={classes.rightFlexBasis} condition={!!showMobileDropDown}>
                            {!isSearchOpen && (
                                <div className={classes.extraMeBoxIcons}>
                                    {TitleBar.extraMeBoxComponents.map((ComponentName, index) => {
                                        return <ComponentName key={index} />;
                                    })}
                                </div>
                            )}
                            <CompactSearch
                                className={classNames(classes.compactSearch, {
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
                                cancelButtonClassName={classNames(classes.topElement, classes.searchCancel)}
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
                            {meBox}
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
        { [classes.isSticky]: props.isFixed },
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

/**
 * Hook for the scroll transition of the titleBar.
 *
 * The following should happen on scroll if
 * - There is a splash.
 * - We are configured to overlay the splash.
 *
 * - Starts at transparent.
 * - Transitions the background color in over the height of the splash.
 * - Once we pass the splash, transition in the bg image of the splash.
 */
function useScrollTransition() {
    const bgRef = useRef<HTMLDivElement | null>(null);
    const bg2Ref = useRef<HTMLDivElement | null>(null);
    const logoRef = useRef<HTMLDivElement | null>(null);
    const { bannerExists, bannerRect } = useBannerContext();
    const [scrollPos, setScrollPos] = useState(0);
    const fullBleedOptions = titleBarVariables().fullBleed;

    const { doubleLogoStrategy } = titleBarVariables().logo;
    const shouldOverlay = fullBleedOptions.enabled && bannerExists;
    const { topOffset } = useScrollOffset();

    // Scroll handler to pass to the form element.
    useEffect(() => {
        const handler = () => {
            requestAnimationFrame(() => {
                setScrollPos(Math.max(0, window.scrollY));
            });
        };
        if (shouldOverlay || doubleLogoStrategy === "fade-in") {
            window.addEventListener("scroll", handler);
            return () => {
                window.removeEventListener("scroll", handler);
            };
        }
    }, [doubleLogoStrategy, setScrollPos, shouldOverlay]);

    // Calculate some dimensions.
    let bgStart = 0;
    let bgEnd = 0;
    let bg2Start = 0;
    let bg2End = 0;
    if (bannerExists && bannerRect && bg2Ref.current) {
        const bannerEnd = bannerRect.bottom;
        const titleBarHeight = bg2Ref.current.getBoundingClientRect().height;
        bgStart = bannerRect.top;
        bgEnd = bgStart + titleBarHeight;
        bg2Start = bannerEnd - titleBarHeight * 2;
        bg2End = bannerEnd - titleBarHeight;
    }

    const clientHeaderStart = topOffset === 0 ? -1 : 0; // Fix to ensure an empty topOffset starts us at 100% opacity.
    const clientHeaderEnd = topOffset;

    const { bgSpring, bg2Spring, clientHeaderSpring } = useSpring({
        bgSpring: Math.max(bgStart, Math.min(bgEnd, scrollPos)),
        bg2Spring: Math.max(bg2Start, Math.min(bg2End, scrollPos)),
        clientHeaderSpring: Math.max(clientHeaderStart, Math.min(clientHeaderEnd, scrollPos)),
        tension: 100,
    });

    // Fades in first.
    const bgOpacity = bgSpring.interpolate({
        range: [bgStart, bgEnd],
        output: [fullBleedOptions.startingOpacity, fullBleedOptions.endingOpacity],
    });

    // Fades in second.
    const bg2Opacity = bg2Spring.interpolate({
        range: [bg2Start, bg2End],
        output: [0, 1],
    });

    const logoOpacity = clientHeaderSpring.interpolate({
        range: [clientHeaderStart, clientHeaderEnd],
        output: [0, 1],
    });

    const bgProps = shouldOverlay
        ? {
              style: { opacity: bgOpacity },
              ref: bgRef,
          }
        : {};

    const bg2Props = shouldOverlay
        ? {
              style: { opacity: bg2Opacity },
              ref: bg2Ref,
          }
        : {};

    const actualOpacity = logoOpacity.payload?.[0]?.value ?? 0;
    const logoProps =
        doubleLogoStrategy === "fade-in"
            ? {
                  style: {
                      opacity: logoOpacity,
                      pointerEvents: actualOpacity <= 0.15 ? "none" : "initial",
                  },
                  ref: logoRef,
              }
            : {};

    useDebugValue({
        bgProps,
        bg2Props,
        logoProps,
    });
    return {
        bgProps,
        bg2Props,
        logoProps,
    };
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
