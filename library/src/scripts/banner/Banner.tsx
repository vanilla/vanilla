/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import IndependentSearch from "@library/features/search/IndependentSearch";
import { ButtonPreset } from "@library/forms/buttonStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Container from "@library/layout/components/Container";
import FlexSpacer from "@library/layout/FlexSpacer";
import Heading from "@library/layout/Heading";
import { useBannerContainerDivRef, useBannerContext } from "@library/banner/BannerContext";
import { bannerClasses, bannerVariables } from "@library/banner/bannerStyles";
import { assetUrl, t } from "@library/utility/appUtils";
import classNames from "classnames";
import { titleBarClasses, titleBarVariables } from "@library/headers/titleBarStyles";
import { DefaultBannerBg } from "@library/banner/DefaultBannerBg";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { contentBannerClasses, contentBannerVariables } from "@library/banner/contentBannerStyles";
import { useComponentDebug } from "@vanilla/react-utils";
import { useLayout } from "@library/layout/LayoutContext";

interface IProps {
    action?: React.ReactNode;
    title?: string; // Often the message to display isn't the real H1
    description?: React.ReactNode;
    className?: string;
    backgroundImage?: string;
    contentImage?: string;
    logoImage?: string;
    searchBarNoTopMargin?: boolean;
    forceSearchOpen?: boolean;
    isContentBanner?: boolean;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export default function Banner(props: IProps) {
    const { isCompact, mediaQueries } = useLayout();
    const bannerContextRef = useBannerContainerDivRef();
    const { setOverlayTitleBar, setRenderedH1 } = useBannerContext();
    const { action, className, isContentBanner } = props;
    const varsTitleBar = titleBarVariables();
    const classesTitleBar = titleBarClasses();
    const classes = isContentBanner ? contentBannerClasses(mediaQueries) : bannerClasses(mediaQueries);
    const vars = isContentBanner ? contentBannerVariables() : bannerVariables();
    const { options } = vars;

    const { title = vars.title.text } = props;

    useComponentDebug({ vars });

    useEffect(() => {
        setOverlayTitleBar(!!options.overlayTitleBar);
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

    // Search placement
    const showBottomSearch = options.searchPlacement === "bottom" && !options.hideSearch;
    const showMiddleSearch = options.searchPlacement === "middle" && !options.hideSearch;
    const searchAloneInContainer =
        showBottomSearch || (showMiddleSearch && options.hideDescription && options.hideTitle);

    const hideButton = isCompact || bannerVariables().presets.button.preset === ButtonPreset.HIDE;

    const searchComponent = (
        <div className={classNames(classes.searchContainer, { [classes.noTopMargin]: searchAloneInContainer })}>
            <IndependentSearch
                forceMenuOpen={props.forceSearchOpen}
                buttonClass={classes.searchButton}
                buttonBaseClass={ButtonTypes.CUSTOM}
                isLarge={true}
                placeholder={t("SearchBoxPlaceHolder", "Search")}
                inputClass={classes.input}
                iconClass={classes.icon}
                buttonLoaderClassName={classes.buttonLoader}
                hideSearchButton={hideButton}
                contentClass={classes.content}
                valueContainerClasses={classes.valueContainer(hideButton)}
                iconContainerClasses={classes.iconContainer}
                resultsAsModalClasses={classes.resultsAsModal}
            />
        </div>
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
                                        {setRenderedH1(true)}
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
                                <>
                                    <Heading className={visibility().visuallyHidden} depth={1}>
                                        {title}
                                    </Heading>
                                    {setRenderedH1(true)}
                                </>
                            )}
                            <ConditionalWrap
                                className={classes.contentContainer(!rightImageSrc)}
                                condition={
                                    showMiddleSearch || !options.hideTitle || !options.hideDescription || !!logoImageSrc
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
                                {!options.hideTitle && (
                                    <div className={classes.titleWrap}>
                                        <FlexSpacer className={classes.titleFlexSpacer} />
                                        {title && (
                                            <>
                                                <Heading className={classes.title} depth={1} isLarge>
                                                    {title}
                                                </Heading>
                                                {setRenderedH1(true)}
                                            </>
                                        )}
                                        <div className={classNames(classes.text, classes.titleFlexSpacer)}>
                                            {action}
                                        </div>
                                    </div>
                                )}
                                {!options.hideDescription && description && (
                                    <div className={classes.descriptionWrap}>
                                        <p className={classNames(classes.description, classes.text)}>{description}</p>
                                    </div>
                                )}
                                {showMiddleSearch && searchComponent}
                            </ConditionalWrap>
                            {rightImageSrc && <div className={classes.imageElementContainer} />}
                        </div>
                    </Container>
                </div>
            </div>
            {showBottomSearch && (
                <div className={classes.searchStrip}>
                    <Container fullGutter>{searchComponent}</Container>
                </div>
            )}
        </div>
    );
}
