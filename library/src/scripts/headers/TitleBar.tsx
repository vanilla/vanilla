/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ISearchScopeNoCompact } from "@library/features/search/SearchScopeContext";
import { useCurrentUserSignedIn } from "@library/features/users/userHooks";
import Hamburger from "@library/flyouts/Hamburger";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { LogoAlignment } from "@library/headers/LogoAlignment";
import { MeBoxDesktop } from "@library/headers/mebox/MeBoxDesktop";
import { MeBoxMobile } from "@library/headers/mebox/MeBoxMobile";
import CompactSearch from "@library/headers/mebox/pieces/CompactSearch";
import Logo from "@library/headers/mebox/pieces/Logo";
import { meBoxClasses } from "@library/headers/mebox/pieces/meBoxStyles";
import TitleBarNav from "@library/headers/mebox/pieces/TitleBarNav";
import { navigationVariables } from "@library/headers/navigationVariables";
import { titleBarClasses, titleBarLogoClasses } from "@library/headers/TitleBar.classes";
import { TitleBarLayoutWidget } from "@library/headers/TitleBar.LayoutWidget";
import {
    TitleBarParamContext,
    TitleBarParamContextProvider,
    useTitleBarParams,
    useTitleBarParamVarOverrides,
    type ITitleBarParams,
} from "@library/headers/TitleBar.ParamContext";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import titleBarNavClasses, { titleBarNavigationVariables } from "@library/headers/titleBarNavStyles";
import Container from "@library/layout/components/Container";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import FlexSpacer from "@library/layout/FlexSpacer";
import { HashOffsetReporter, useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { useTitleBarDevice } from "@library/layout/TitleBarContext";
import { SearchPageRoute } from "@library/search/SearchPageRoute";
import { defaultFontFamily } from "@library/styles/globalStyleVars";
import { useThemeForcedVariables } from "@library/theming/Theme.context";
import { LogoType } from "@library/theming/ThemeLogo";
import { t } from "@library/utility/appUtils";
import { useThemeFragmentImpl } from "@library/utility/FragmentImplContext";
import { SkipNavContent, SkipNavLink } from "@reach/skip-nav";
import type TitleBarFragmentInjectable from "@vanilla/injectables/TitleBarFragment";
import { useIsOverflowing, useMeasure } from "@vanilla/react-utils";
import { measureText } from "@vanilla/utils";
import classNames from "classnames";
import React, { useDebugValue, useEffect, useRef, useState } from "react";
import ReactDOM from "react-dom";
import { animated, useSpring } from "react-spring";

interface IImplProps {
    // Only used in storybook
    overwriteLogo?: string; // overwrite logo, used for storybook
    forceVisibility?: boolean; // For storybook, as it will disable closing the search
    forceMenuOpen?: boolean; // For storybook, will force nested menu open
    scope?: ISearchScopeNoCompact; // For storybook.
    isFixed?: boolean; // For storybook, will force the titlebar to not be fixed to the top of the page.

    // Display only the logo. Useful for if you just want a basic TitleBar with no mebox or navigation.
    // Bypasses custom fragments.
    onlyLogo?: boolean;

    wrapperComponent?: React.ComponentType<{ children: React.ReactNode }>;
    className?: string;
}

interface IProps extends IImplProps, Partial<ITitleBarParams> {
    container?: HTMLElement | null; // Element containing header. Should be the default most if not all of the time.
}

export default function TitleBar(props: IProps) {
    const CustomImpl = useThemeFragmentImpl<TitleBarFragmentInjectable.Props>("TitleBarFragment");

    let result: React.ReactNode;
    if (CustomImpl && !props.onlyLogo) {
        result = (
            <TitleBarParamContext.Consumer>
                {(params) => {
                    return <CustomImpl {...params} />;
                }}
            </TitleBarParamContext.Consumer>
        );
    } else {
        result = <TitleBarImpl {...props} />;
    }

    if (props.wrapperComponent) {
        result = <props.wrapperComponent>{result}</props.wrapperComponent>;
    }
    result = <TitleBarParamContextProvider {...props}>{result}</TitleBarParamContextProvider>;
    const containerElement = props.container !== null ? props.container || document.getElementById("titleBar") : null;

    if (containerElement) {
        return ReactDOM.createPortal(result, containerElement);
    } else {
        return <>{result}</>;
    }
}

/**
 * Implements Vanilla Header component. Note that this component uses a react portal.
 * That means the exact location in the page is not that important, since it will
 * render in a specific div in the default-master.
 */
function TitleBarImpl(_props: IImplProps) {
    const props = {
        isFixed: true,
        forceVisibility: false,
        ..._props,
    };

    const params = useTitleBarParams();
    const varOverrides = useTitleBarParamVarOverrides();

    const vars = titleBarVariables.useAsHook(varOverrides);
    const classes = titleBarClasses.useAsHook(varOverrides);
    const containerOptions = vars.titleBarContainer;
    const meboxVars = vars.meBox;

    const { bgProps, logoProps } = useScrollTransition();

    const device = useTitleBarDevice();
    const [isSearchOpen, setIsSearchOpen] = useState(props.forceVisibility);
    const [isShowingSuggestions, setIsShowingSuggestions] = useState(false);

    const classesMeBox = meBoxClasses.useAsHook();
    const currentUserIsSignedIn = useCurrentUserSignedIn();
    const isGuest = !currentUserIsSignedIn;
    const logoClasses = titleBarLogoClasses.useAsHook();
    const isMobileLogoCentered = params.logo.alignmentMobile === LogoAlignment.CENTER;
    const isDesktopLogoCentered = params.logo.alignment === LogoAlignment.CENTER;
    const isNavbarCentered = params.navigation.alignment === "center";

    const navOverflow = useIsOverflowing();
    const rootSizeRef = useRef<HTMLElement>(null);
    const rootMeasure = useMeasure(rootSizeRef);
    const measuredWidth = rootMeasure.clientWidth === 0 ? window.innerWidth : rootMeasure.clientWidth;
    const _isCompact = measuredWidth <= vars.breakpoints.compact;

    // we'll manage wether its compact or not in state
    const isCompact = navOverflow.isOverflowing || _isCompact;

    const meBox = isCompact ? !isSearchOpen && <MeBoxMobile /> : <MeBoxDesktop />;

    // When previewing and updating the colors live, there can be flickering of some components.
    // As a result we want to hide them on first render for these cases.
    const isPreviewing = !!useThemeForcedVariables();
    const [isPreviewFirstRender, setIsPreviewFirstRender] = useState(isPreviewing);
    useEffect(() => {
        if (isPreviewFirstRender) {
            setIsPreviewFirstRender(false);
        }
    }, [isPreviewFirstRender]);

    let headerContent = (
        <>
            <HashOffsetReporter className={classes.container}>
                <div className={classes.bgContainer}>
                    <animated.div
                        {...bgProps}
                        className={classNames(classes.bg1, { [classes.swoop]: vars.swoop.amount > 0 })}
                    >
                        {!isPreviewFirstRender && (
                            <>
                                {/* Cannot be a background image there will be flickering. */}
                                {vars.colors.bgImage && (
                                    <img
                                        src={vars.colors.bgImage}
                                        className={classes.bgImage}
                                        alt={"titleBarImage"}
                                        aria-hidden={true}
                                    />
                                )}
                                {vars.overlay && <div className={classes.overlay} />}
                            </>
                        )}
                    </animated.div>
                </div>
                <Container
                    fullGutter
                    gutterSpacing={containerOptions.gutterSpacing}
                    maxWidth={containerOptions.maxWidth}
                >
                    <div className={classes.titleBarContainer}>
                        <div className={classes.bar}>
                            {props.onlyLogo ? (
                                <>
                                    {isCompact ? (
                                        <>
                                            {isMobileLogoCentered && <FlexSpacer actualSpacer />}
                                            <div
                                                className={classNames(
                                                    isMobileLogoCentered && classes.logoCenterer,
                                                    logoClasses.mobileLogo,
                                                )}
                                            >
                                                <animated.span
                                                    className={classes.logoAnimationWrap}
                                                    {...(logoProps as any)}
                                                >
                                                    <Logo
                                                        className={classes.logoContainer}
                                                        logoClassName="titleBar-logo"
                                                        logoType={LogoType.MOBILE}
                                                        overwriteLogo={props.overwriteLogo}
                                                    />
                                                </animated.span>
                                            </div>
                                        </>
                                    ) : (
                                        <animated.div
                                            className={classNames(classes.logoAnimationWrap)}
                                            {...(logoProps as any)}
                                        >
                                            <span
                                                className={classNames("logoAlignment", {
                                                    [classes.logoCenterer]: isDesktopLogoCentered,
                                                    [classes.logoLeftAligned]: !isDesktopLogoCentered,
                                                })}
                                            >
                                                <>
                                                    <SkipNavLink className={classes.skipNav}>
                                                        {t("Skip to content")}
                                                    </SkipNavLink>

                                                    <Logo
                                                        className={classNames(
                                                            "titleBar-logoContainer",
                                                            classes.logoContainer,
                                                        )}
                                                        logoClassName="titleBar-logo"
                                                        logoType={LogoType.DESKTOP}
                                                        overwriteLogo={props.overwriteLogo}
                                                    />
                                                </>
                                            </span>
                                        </animated.div>
                                    )}
                                </>
                            ) : (
                                <>
                                    {isCompact && <FlexSpacer className="pageHeading-leftSpacer" />}
                                    {!isCompact && (isDesktopLogoCentered ? !isSearchOpen : true) && (
                                        <animated.div
                                            className={classNames(classes.logoAnimationWrap)}
                                            {...(logoProps as any)}
                                        >
                                            <span
                                                className={classNames("logoAlignment", {
                                                    [classes.logoCenterer]: isDesktopLogoCentered,
                                                    [classes.logoLeftAligned]: !isDesktopLogoCentered,
                                                })}
                                            >
                                                <>
                                                    <SkipNavLink className={classes.skipNav}>
                                                        {t("Skip to content")}
                                                    </SkipNavLink>

                                                    <Logo
                                                        className={classNames(
                                                            "titleBar-logoContainer",
                                                            classes.logoContainer,
                                                        )}
                                                        logoClassName="titleBar-logo"
                                                        logoType={LogoType.DESKTOP}
                                                        overwriteLogo={props.overwriteLogo}
                                                    />
                                                </>
                                            </span>
                                        </animated.div>
                                    )}
                                    {!isSearchOpen && !isCompact && (
                                        <TitleBarNav
                                            forceOpen={props.forceMenuOpen}
                                            isCentered={isNavbarCentered}
                                            containerRef={navOverflow.ref}
                                            className={classes.nav}
                                            navigationItems={params.navigation.items}
                                            linkClassName={classes.topElement}
                                        />
                                    )}
                                    {isCompact && (
                                        <>
                                            {!isSearchOpen && (
                                                <>
                                                    <Hamburger className={classes.hamburger} showCloseIcon={false} />
                                                    {isMobileLogoCentered && <FlexSpacer actualSpacer />}
                                                    <div
                                                        className={classNames(
                                                            isMobileLogoCentered && classes.logoCenterer,
                                                            logoClasses.mobileLogo,
                                                        )}
                                                    >
                                                        <animated.span
                                                            className={classes.logoAnimationWrap}
                                                            {...(logoProps as any)}
                                                        >
                                                            <Logo
                                                                className={classes.logoContainer}
                                                                logoClassName="titleBar-logo"
                                                                logoType={LogoType.MOBILE}
                                                                overwriteLogo={props.overwriteLogo}
                                                            />
                                                        </animated.span>
                                                    </div>
                                                </>
                                            )}
                                        </>
                                    )}
                                    <ConditionalWrap
                                        className={classes.desktopMeBoxSectionWrapper(isSearchOpen)}
                                        condition={!isCompact && isNavbarCentered}
                                    >
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
                                            placeholder={t("Search")}
                                            open={isSearchOpen}
                                            onSearchButtonClick={() => {
                                                SearchPageRoute.preload();
                                                setIsSearchOpen(true);
                                            }}
                                            onCloseSearch={() => {
                                                setIsSearchOpen(props.forceVisibility); // will be false if not used
                                            }}
                                            cancelButtonClassName={classNames(
                                                classes.topElement,
                                                classes.searchCancel,
                                                titleBarNavClasses().link,
                                            )}
                                            cancelContentClassName="meBox-buttonContent"
                                            buttonClass={classNames(classes.button, {
                                                [classes.buttonOffset]: !isCompact && isGuest,
                                            })}
                                            showingSuggestions={isShowingSuggestions}
                                            onOpenSuggestions={() => setIsShowingSuggestions(true)}
                                            onCloseSuggestions={() => setIsShowingSuggestions(false)}
                                            buttonContentClassName={classNames(
                                                classesMeBox.buttonContent,
                                                "meBox-buttonContent",
                                            )}
                                            clearButtonClass={classes.clearButtonClass}
                                            scope={
                                                props.scope
                                                    ? {
                                                          ...props.scope,
                                                      }
                                                    : undefined
                                            }
                                            searchCloseOverwrites={{
                                                source: "fromTitleBar",
                                                ...vars.stateColors,
                                            }}
                                            overwriteSearchBar={{
                                                compact: isCompact,
                                            }}
                                            withLabel={meboxVars.withLabel}
                                        />
                                        {meBox}
                                    </ConditionalWrap>
                                </>
                            )}
                        </div>
                    </div>
                </Container>
            </HashOffsetReporter>
            <SkipNavContent />
        </>
    );

    if (props.wrapperComponent) {
        headerContent = <props.wrapperComponent>{headerContent}</props.wrapperComponent>;
    }

    const containerClasses = classNames("titleBar", classes.root, props.className);

    return (
        <TitleBarLayoutWidget ref={rootSizeRef} className={containerClasses}>
            {headerContent}
        </TitleBarLayoutWidget>
    );
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
    const params = useTitleBarParams();
    const bgRef = useRef<HTMLDivElement | null>(null);
    const logoRef = useRef<HTMLDivElement | null>(null);
    const [scrollPos, setScrollPos] = useState(0);
    const titleVars = titleBarVariables.useAsHook();
    const positioningOptions = titleVars.positioning;

    const { doubleLogoStrategy } = titleVars.logo;
    const shouldOverlay = params.positioning === "StickyTransparent";

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
    if (shouldOverlay && bgRef.current) {
        const titleBarHeight = bgRef.current.getBoundingClientRect().height;
        bgStart = topOffset + titleBarHeight;
        bgEnd = bgStart + titleBarHeight;
    }

    const clientHeaderStart = topOffset === 0 ? -1 : 0; // Fix to ensure an empty topOffset starts us at 100% opacity.
    const clientHeaderEnd = topOffset;

    const { bgSpring, clientHeaderSpring } = useSpring({
        bgSpring: Math.max(bgStart, Math.min(bgEnd, scrollPos)),
        clientHeaderSpring: Math.max(clientHeaderStart, Math.min(clientHeaderEnd, scrollPos)),
        tension: 100,
    });

    // Fades in first.
    const bgOpacity = bgSpring.interpolate({
        range: [bgStart, bgEnd],
        output: [positioningOptions.startingOpacity, 1],
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

    const actualOpacity = (logoOpacity as any).payload?.[0]?.value ?? 0;
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
        logoProps,
    });
    return {
        bgProps,
        logoProps,
    };
}

// For backwards compatibility
export { TitleBar };

/**
 * Calculation of titlebar navbar with based on navLinks labels and their styling.
 */
export const getMinimalSpaceForDesktopNavBar = (isGuest?: boolean) => {
    const navigationItemsLabels = navigationVariables().navigationItems.map((item) => item.name);
    const titleBarVars = titleBarVariables();
    const navVars = titleBarNavigationVariables();
    const formVars = formElementsVariables();

    try {
        const navBarWidthFromNavItems = navigationItemsLabels.reduce((acc, label) => {
            const labelWidth =
                measureText(
                    label,
                    navVars.navLinks.font.size ?? 14,
                    navVars.navLinks.font.family?.[0] ?? defaultFontFamily,
                ) +
                navVars.navLinks.padding.left +
                navVars.navLinks.padding.right +
                titleBarVars.sizing.spacer;
            return acc + labelWidth;
        }, 0);

        // max width of the mebox section with subcommunity chooser and search included, we opt for maximal possible size here
        const meBoxSectionWidth = isGuest
            ? formVars.sizing.height * 2 +
              16 +
              titleBarVars.button.guest.minWidth * 2 +
              titleBarVars.guest.spacer * 2 +
              16
            : formVars.sizing.height * 5 + 16;

        // with 20 extra as a buffer
        return navBarWidthFromNavItems + 20 + meBoxSectionWidth * 2;
    } catch (e) {
        return null;
    }
};

/** Hold the extra mebox components before rendering. */
TitleBar.extraMeBoxComponents = [] as React.ComponentType[];

/**
 * Register an extra component to be rendered before the mebox.
 * This will only affect larger screen sizes.
 *
 * @param component The component class to be render.
 */
TitleBar.registerBeforeMeBox = (component: React.ComponentType) => {
    // if (!TitleBar.extraMeBoxComponents.some((comp) => comp === component)) {
    TitleBar.extraMeBoxComponents.push(component);
    // }
};
