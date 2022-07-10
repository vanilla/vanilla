/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import IndependentSearch from "@library/features/search/IndependentSearch";
import { ButtonPreset } from "@library/forms/ButtonPreset";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Container from "@library/layout/components/Container";
import FlexSpacer from "@library/layout/FlexSpacer";
import Heading from "@library/layout/Heading";
import { useBannerContainerDivRef, useBannerContext } from "@library/banner/BannerContext";
import { bannerVariables, IBannerOptions } from "@library/banner/Banner.variables";
import { bannerClasses } from "@library/banner/Banner.styles";
import { assetUrl, t } from "@library/utility/appUtils";
import classNames from "classnames";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { DefaultBannerBg } from "@library/banner/DefaultBannerBg";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { contentBannerClasses, contentBannerVariables } from "@library/banner/contentBannerStyles";
import { useComponentDebug } from "@vanilla/react-utils";
import { useSection } from "@library/layout/LayoutContext";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { styleUnit } from "@library/styles/styleUnit";
import { ISearchScopeNoCompact } from "@library/features/search/SearchScopeContext";
import SmartLink from "@library/routing/links/SmartLink";

export interface IBannerProps {
    action?: React.ReactNode;
    title?: string; // Often the message to display isn't the real H1
    description?: string;
    className?: string;
    backgroundImage?: string;
    contentImage?: string;
    logoImage?: string;
    iconImage?: string;
    searchBarNoTopMargin?: boolean;
    forceSearchOpen?: boolean;
    isContentBanner?: boolean;
    scope?: ISearchScopeNoCompact;
    initialQuery?: string; // prepopulate text input
    hideSearch?: boolean;
    hideIcon?: boolean;
    options?: Partial<IBannerOptions>;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export default function Banner(props: IBannerProps) {
    const { isCompact, mediaQueries } = useSection();
    const bannerContextRef = useBannerContainerDivRef();
    const { setOverlayTitleBar } = useBannerContext();
    const { action, className, isContentBanner } = props;
    const varsTitleBar = titleBarVariables();
    const classesTitleBar = titleBarClasses();
    const classes = isContentBanner ? contentBannerClasses() : bannerClasses();
    const vars = isContentBanner ? contentBannerVariables(props.options) : bannerVariables(props.options);
    const { options } = vars;
    const device = useDevice();

    const { title = vars.title.text } = props;

    useComponentDebug({ vars });
    useEffect(() => {
        setOverlayTitleBar(options.overlayTitleBar);
    }, [options.overlayTitleBar]);

    if (!options.enabled) {
        return null;
    }

    const description = props.description ?? vars.description.text;

    // Image element (right)
    let rightImageSrc = props.contentImage || vars.rightImage.image || null;
    rightImageSrc = rightImageSrc ? assetUrl(rightImageSrc) : null;

    // Logo (Image in middle)
    let logoImageSrc = props.logoImage || vars.logo.image || null;
    logoImageSrc = logoImageSrc ? assetUrl(logoImageSrc) : null;

    let iconImageSrc: string | null = null;

    if (isContentBanner && (!options.hideIcon || !!props.hideIcon)) {
        iconImageSrc = props.iconImage || vars.icon.image || null;
        iconImageSrc = iconImageSrc ? assetUrl(iconImageSrc) : null;
    }

    const hideSearchOnMobile = isCompact && options.hideSearchOnMobile;

    // Search placement
    const showBottomSearch =
        options.searchPlacement === "bottom" && !options.hideSearch && !props.hideSearch && !hideSearchOnMobile;
    const showMiddleSearch =
        options.searchPlacement === "middle" && !options.hideSearch && !props.hideSearch && !hideSearchOnMobile;
    const searchAloneInContainer =
        showBottomSearch || (showMiddleSearch && options.hideDescription && options.hideTitle);

    const hideButton = isCompact || vars.presets.button.preset === ButtonPreset.HIDE || !!props.scope;

    const searchComponent = (
        <div
            className={classNames(classes.searchContainer, {
                [classes.noTopMargin]: searchAloneInContainer,
            })}
        >
            <IndependentSearch
                forceMenuOpen={props.forceSearchOpen}
                buttonClass={classes.searchButton}
                buttonType={ButtonTypes.PRIMARY}
                isLarge={true}
                placeholder={t("SearchBoxPlaceHolder", "Search")}
                hideSearchButton={hideButton}
                contentClass={classes.content}
                resultsAsModalClasses={classes.resultsAsModal}
                scope={props.scope}
                initialQuery={props.initialQuery}
                overwriteSearchBar={{
                    borderRadius: styleUnit(vars.searchBar.border.radius),
                    preset: vars.presets.input.preset,
                    compact: !!rightImageSrc || device === Devices.MOBILE || device === Devices.XS,
                }}
            />
        </div>
    );

    const headingTitleLarge = (
        <Heading className={classes.title} depth={1} isLarge>
            {title}
        </Heading>
    );

    return (
        <div
            ref={bannerContextRef}
            className={classNames(className, classes.root, {
                [classesTitleBar.negativeSpacer]: varsTitleBar.fullBleed.enabled && options.overlayTitleBar,
            })}
        >
            {/* First container holds:
                - Background.
                - Right image if there is one.
                - This container has overflow: "hidden".
                - Spacer elements for all the main content, but no actual content.
                - Overflow hidden can't be applied to the main content, because it has a search box that can't be cut off.
            */}
            <div className={classes.bannerContainer}>
                <div className={classes.overflowRightImageContainer}>
                    <div
                        className={classNames(classes.middleContainer, {
                            [classesTitleBar.bannerPadding]: varsTitleBar.fullBleed.enabled,
                        })}
                    >
                        <div className={classNames(classes.outerBackground(props.backgroundImage || undefined))}>
                            {!props.backgroundImage &&
                                !vars.outerBackground.image &&
                                !vars.outerBackground.unsetBackground && (
                                    <DefaultBannerBg isContentBanner={isContentBanner} />
                                )}
                        </div>
                        {vars.backgrounds.useOverlay && <div className={classes.backgroundOverlay} />}
                        <Container className={classes.fullHeight}>
                            <div className={classes.imagePositioner}>
                                {/*For SEO & accessibility*/}
                                {options.hideTitle && (
                                    <>
                                        <Heading className={visibility().visuallyHidden} depth={1}>
                                            {title}
                                        </Heading>
                                    </>
                                )}
                                <ConditionalWrap
                                    className={classes.contentContainer(!rightImageSrc)}
                                    condition={
                                        showMiddleSearch ||
                                        !options.hideTitle ||
                                        !options.hideDescription ||
                                        !!logoImageSrc
                                    }
                                />
                                {rightImageSrc && (
                                    <div className={classes.imageElementContainer}>
                                        {/*We rely on the title for screen readers as we don't yet have alt text hooked up to image*/}
                                        <img className={classes.rightImage} src={rightImageSrc} aria-hidden={true} />
                                    </div>
                                )}
                            </div>
                        </Container>
                        {showBottomSearch && <div className={classes.searchStrip} style={{ background: "none" }} />}
                    </div>
                </div>
                {/* Main Content Area
                - Note that background is up in the previous grouping.
                - Overflow hidden CAN NEVER BE APPLIED HERE.
            */}
                <div
                    className={classNames(classes.middleContainer, {
                        [classesTitleBar.bannerPadding]: varsTitleBar.fullBleed.enabled,
                    })}
                >
                    <Container fullGutter>
                        <div className={classes.imagePositioner}>
                            {/*For SEO & accessibility*/}
                            {options.hideTitle && (
                                <Heading className={visibility().visuallyHidden} depth={1}>
                                    {title}
                                </Heading>
                            )}
                            <ConditionalWrap
                                className={classes.contentContainer(!rightImageSrc)}
                                condition={
                                    showMiddleSearch ||
                                    !options.hideTitle ||
                                    !options.hideDescription ||
                                    !!logoImageSrc ||
                                    !!iconImageSrc
                                }
                            >
                                {!!logoImageSrc && (
                                    <div className={classes.logoSpacer}>
                                        <div className={classes.logoContainer}>
                                            {/*We rely on the title for screen readers as we don't yet have alt text hooked up to image*/}
                                            <img className={classes.logo} src={logoImageSrc} aria-hidden={true} />
                                        </div>
                                    </div>
                                )}

                                <div className={classes.iconTextAndSearchContainer}>
                                    {isContentBanner && iconImageSrc && (
                                        <div className={classes.iconContainer}>
                                            <img className={classes.icon} src={iconImageSrc} aria-hidden={true} />
                                        </div>
                                    )}
                                    {Banner.extraBeforeSearchBarComponents.map((ComponentName, index) => {
                                        return <ComponentName {...props} key={index} />;
                                    })}
                                    <div className={classes.textAndSearchContainer}>
                                        {!options.hideTitle && (
                                            <div className={classes.titleWrap}>
                                                <FlexSpacer className={classes.titleFlexSpacer} />
                                                {title && (
                                                    <>
                                                        {options.url ? (
                                                            <SmartLink
                                                                to={options.url}
                                                                className={classes.titleUrlWrap}
                                                            >
                                                                {headingTitleLarge}
                                                            </SmartLink>
                                                        ) : (
                                                            <>{headingTitleLarge}</>
                                                        )}
                                                    </>
                                                )}
                                                <div className={classNames(classes.text, classes.titleFlexSpacer)}>
                                                    {action}
                                                </div>
                                            </div>
                                        )}
                                        {!options.hideDescription && description && (
                                            <div className={classes.descriptionWrap}>
                                                <p
                                                    className={classNames(classes.description, classes.text)}
                                                    dangerouslySetInnerHTML={{ __html: description }}
                                                />
                                            </div>
                                        )}
                                        {showMiddleSearch && searchComponent}
                                        {showMiddleSearch &&
                                            Banner.extraAfterSearchBarComponents.map((ComponentName, index) => {
                                                return <ComponentName {...props} key={index} />;
                                            })}
                                    </div>
                                </div>
                            </ConditionalWrap>
                            {rightImageSrc && <div className={classes.imageElementContainer} />}
                        </div>
                    </Container>
                </div>
            </div>
            {showBottomSearch && (
                <>
                    <div className={classes.searchStrip}>
                        <Container fullGutter>{searchComponent}</Container>
                    </div>
                    {Banner.extraAfterSearchBarComponents.map((ComponentName, index) => {
                        return <ComponentName {...props} key={index} />;
                    })}
                </>
            )}
        </div>
    );
}

/** Hold the extra after search bar text components before rendering. */
Banner.extraAfterSearchBarComponents = [] as Array<React.ComponentType<IBannerProps>>;

/** Hold the extra before search bar text components before rendering. */
Banner.extraBeforeSearchBarComponents = [] as Array<React.ComponentType<IBannerProps>>;

/**
 * Register an extra component to be rendered after the search bar.
 *
 * @param component The component class to be render.
 */
Banner.registerAfterSearchBar = (component: React.ComponentType<IBannerProps>) => {
    Banner.extraAfterSearchBarComponents.pop();
    Banner.extraAfterSearchBarComponents.push(component);
};

/**
 * Register an extra component to be rendered before the search bar.
 *
 * @param component The component class to be render.
 */
Banner.registerBeforeSearchBar = (component: React.ComponentType<IBannerProps>) => {
    Banner.extraBeforeSearchBarComponents.pop();
    Banner.extraBeforeSearchBarComponents.push(component);
};
