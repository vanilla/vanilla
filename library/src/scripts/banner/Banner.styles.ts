/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { containerVariables } from "@library/layout/components/containerStyles";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { unitIfDefined } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, important, percent, px, quote, translateX, translateY, viewWidth } from "csx";
import { media } from "@library/styles/styleShim";
import { css } from "@emotion/css";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { Mixins } from "@library/styles/Mixins";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { SearchBarPresets } from "./SearchBarPresets";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { BannerAlignment, bannerVariables, IBannerOptions } from "@library/banner/Banner.variables";
import { IBackground } from "@library/styles/cssUtilsTypes";
import { twoColumnVariables } from "@library/layout/types/layout.twoColumns";

export const bannerClasses = useThemeCache(
    (alternativeVariables?: ReturnType<typeof bannerVariables>, optionOverrides?: Partial<IBannerOptions>) => {
        const vars = alternativeVariables ?? bannerVariables(optionOverrides);
        const formElementVars = formElementsVariables();
        const globalVars = globalVariables();
        const isCentered = vars.options.alignment === "center";
        const borderRadius =
            vars.searchBar.border.radius !== undefined ? vars.searchBar.border.radius : vars.border.radius;
        const isUnifiedBorder = vars.presets.input.preset === SearchBarPresets.UNIFIED_BORDER;

        const searchButton = css(Mixins.button(vars.searchButton));
        const mediaQueries = twoColumnVariables().mediaQueries();

        const outerBackground = useThemeCache(() => {
            return css({
                position: "absolute",
                top: 0,
                left: 0,
                width: percent(100),
                height: calc(`100% + 2px`),
                transform: translateY(`-1px`), // Depending on how the browser rounds the pixels, there is sometimes a 1px gap above the banner
                display: "block",
                backgroundColor: ColorsUtils.colorOut(vars.outerBackground.color),
            });
        });

        const defaultBannerSVG = css({
            ...Mixins.absolute.fullSizeOfParent(),
        });

        const backgroundImage = css({
            ...Mixins.absolute.fullSizeOfParent(),
            objectFit: "cover",
        });

        const backgroundOverlay = css({
            display: "block",
            position: "absolute",
            top: px(0),
            left: px(0),
            width: percent(100),
            height: calc(`100% + 2px`),
            background: ColorsUtils.colorOut(vars.backgrounds.overlayColor),
        });

        const contentContainer = (hasFullWidth = false) => {
            return css(
                {
                    display: "flex",
                    flexDirection: "column",
                    justifyContent: "center",
                    alignItems: vars.options.alignment === BannerAlignment.LEFT ? "flex-start" : "center",
                    ...Mixins.padding(vars.contentContainer.padding),
                    ...Mixins.background(vars.innerBackground),
                    minWidth: styleUnit(vars.contentContainer.minWidth),
                    maxWidth: vars.rightImage.image ? styleUnit(vars.contentContainer.minWidth) : undefined,
                    minHeight: styleUnit(vars.dimensions.minHeight),
                    maxHeight: unitIfDefined(vars.dimensions.maxHeight),
                    flexGrow: 0,
                    width: hasFullWidth || vars.options.alignment === BannerAlignment.LEFT ? percent(100) : undefined,
                },
                media(
                    {
                        maxWidth: vars.contentContainer.minWidth + containerVariables().spacing.padding * 2 * 2,
                    },
                    {
                        right: "initial",
                        left: 0,
                        minWidth: percent(100),
                        maxWidth: percent(100),
                        minHeight: unitIfDefined(vars.dimensions.mobile.minHeight),
                        maxHeight: unitIfDefined(vars.dimensions.mobile.maxHeight ?? vars.dimensions.maxHeight),
                        ...(vars.options.mobileAlignment
                            ? {
                                  alignItems:
                                      vars.options.mobileAlignment === BannerAlignment.LEFT ? "flex-start" : "center",
                              }
                            : {}),
                        ...Mixins.padding(vars.contentContainer.mobile.padding),
                    },
                ),
            );
        };

        const text = css({
            color: ColorsUtils.colorOut(vars.colors.primaryContrast),
        });

        const noTopMargin = css({});

        const conditionalUnifiedBorder = isUnifiedBorder
            ? {
                  borderRadius,
                  boxShadow: `0 0 0 ${styleUnit(vars.unifiedBorder.width)} ${ColorsUtils.colorOut(
                      vars.unifiedBorder.color,
                  )}`,
              }
            : {};

        const searchContainer = css({
            position: "relative",
            width: percent(100),
            maxWidth: styleUnit(vars.searchBar.sizing.maxWidth),
            height: styleUnit(vars.searchBar.sizing.height),
            margin: isCentered ? "auto" : undefined,
            ...Mixins.margin(vars.searchBar.margin),
            ...conditionalUnifiedBorder,
            ...{
                // FIXME: get rid of hard-coded class name
                ".search-results": {
                    width: percent(100),
                    maxWidth: styleUnit(vars.searchBar.sizing.maxWidth),
                    margin: "auto",
                    zIndex: 2,
                },
                [`&.${noTopMargin}`]: {
                    marginTop: 0,
                },
                ...mediaQueries.oneColumnDown({
                    ...Mixins.margin(vars.searchBar.marginMobile),
                    [`.${noTopMargin}`]: {
                        marginTop: 0,
                    },
                }),
            },
        });

        const iconContainer = css({
            ...lineHeightAdjustment(),
            ...Mixins.margin(vars.icon.margins),
        });

        const icon = css({
            width: styleUnit(vars.icon.width),
            maxWidth: styleUnit(vars.icon.width),
            height: styleUnit(vars.icon.height),
            maxHeight: styleUnit(vars.icon.height),
            borderRadius: vars.icon.borderRadius,

            ...mediaQueries.oneColumnDown({
                width: styleUnit(vars.icon.mobile.width),
                maxWidth: styleUnit(vars.icon.mobile.width),
                height: styleUnit(vars.icon.mobile.height),
                maxHeight: styleUnit(vars.icon.mobile.height),
                borderRadius: vars.icon.mobile.borderRadius,
            }),
        });

        const title = css({
            display: "block",
            ...Mixins.font(vars.title.font),
            flexGrow: 1,
            ...mediaQueries.oneColumnDown(Mixins.font(vars.title.fontMobile)),
        });

        const iconTextAndSearchContainer = css({
            display: "flex",
            flexDirection: "row",
            flexWrap: "wrap",
            alignItems: "center",
            width: percent(100),
        });

        const textAndSearchContainer = css({
            display: "flex",
            flexDirection: "column",
            width: percent(100),
            flexBasis: styleUnit(vars.textAndSearchContainer.maxWidth),
            flexGrow: 0,
            marginLeft: isCentered ? "auto" : undefined,
            marginRight: isCentered ? "auto" : undefined,
        });

        const titleWrap = css({
            ...Mixins.margin(vars.title.margins),
            display: "flex",
            flexWrap: "nowrap",
            alignItems: "center",
        });

        const titleUrlWrap = css({
            marginLeft: isCentered ? "auto" : undefined,
            marginRight: isCentered ? "auto" : undefined,
        });

        const titleFlexSpacer = css({
            display: isCentered ? "block" : "none",
            position: "relative",
            height: styleUnit(formElementVars.sizing.height),
            width: styleUnit(formElementVars.sizing.height),
            flexBasis: styleUnit(formElementVars.sizing.height),
            transform: translateX(px((formElementVars.sizing.height - globalVars.icon.sizes.default) / 2 - 1)), // The "2" is to offset the pencil that visually doesn't look aligned without a cheat.
            ...{
                // FIXME: get rid of hard-coded class name
                ".searchBar-actionButton:after": {
                    content: quote(""),
                    ...Mixins.absolute.middleOfParent(),
                    width: px(20),
                    height: px(20),
                    backgroundColor: ColorsUtils.colorOut(vars.buttonShadow.background),
                    boxShadow: vars.buttonShadow.full,
                },
                // FIXME: get rid of hard-coded class name
                ".searchBar-actionButton": {
                    color: important("inherit"),
                    "&:not(.focus-visible)": {
                        outline: 0,
                    },
                },
                ".icon-compose": {
                    zIndex: 1,
                },
            },
        });

        const descriptionWrap = css({
            ...Mixins.margin(vars.description.margins),
            display: "flex",
            flexWrap: "nowrap",
            alignItems: "center",
        });

        const description = css({
            display: "block",
            ...Mixins.font(vars.description.font),
            flexGrow: 1,
        });

        const content = css({
            boxSizing: "border-box",
            flexGrow: 1,
            zIndex: 1,
            boxShadow: vars.searchBar.shadow.show ? vars.searchBar.shadow.style : undefined,
            minHeight: styleUnit(vars.searchBar.sizing.height),
        });

        const imagePositioner = css({
            display: "flex",
            flexDirection: "row",
            flexWrap: "nowrap",
            alignItems: "center",
            maxWidth: percent(100),
            height: percent(100),
        });

        const makeImageMinWidth = (rootUnit, padding) => {
            const negative =
                vars.contentContainer.minWidth + (vars.contentContainer.padding.horizontal as number) + padding;

            return calc(`${styleUnit(rootUnit)} - ${styleUnit(negative)}`);
        };

        const imageElementContainer = css(
            {
                alignSelf: "stretch",
                maxWidth: makeImageMinWidth(
                    oneColumnVariables().contentWidth,
                    containerVariables().spacing.padding * 2 * 2,
                ),
                flexGrow: 1,
                position: "relative",
                overflow: "hidden",
            },
            media(
                { maxWidth: oneColumnVariables().contentWidth },
                {
                    minWidth: makeImageMinWidth("100vw", containerVariables().spacing.padding * 2),
                },
            ),
            mediaQueries.oneColumnDown({
                minWidth: makeImageMinWidth("100vw", containerVariables().spacing.mobile.padding * 2),
            }),
            media(
                { maxWidth: 500 },
                {
                    display: "none",
                },
            ),
        );

        const logoContainer = css({
            display: "flex",
            width: percent(100),
            height: styleUnit(vars.logo.height),
            maxWidth: percent(100),
            minHeight: styleUnit(vars.logo.height),
            alignItems: "center",
            justifyContent: "center",
            position: "relative",
            overflow: "hidden",
            ...mediaQueries.oneColumnDown({
                height: unitIfDefined(vars.logo.mobile.height),
                minHeight: unitIfDefined(vars.logo.mobile.height),
            }),
        });

        const logoSpacer = css({
            ...Mixins.padding(vars.logo.padding),
        });

        const logo = css({
            height: styleUnit(vars.logo.height),
            width: styleUnit(vars.logo.width),
            maxHeight: percent(100),
            maxWidth: percent(100),
            ...mediaQueries.oneColumnDown({
                height: unitIfDefined(vars.logo.mobile.height),
                width: unitIfDefined(vars.logo.mobile.width),
            }),
        });

        const rightImage = css(
            {
                ...Mixins.absolute.fullSizeOfParent(),
                minWidth: styleUnit(vars.rightImage.minWidth),
                objectPosition: "100% 50%",
                objectFit: "contain",
                marginLeft: "auto",
                ...Mixins.padding(vars.rightImage.padding),
            },
            media(
                { maxWidth: vars.contentContainer.minWidth + vars.rightImage.minWidth },
                {
                    paddingRight: 0,
                },
            ),
        );

        const titleBarVars = titleBarVariables();

        // NOTE FOR FUTURE
        // Do no apply overflow hidden here.
        // It will cut off the search box in the banner.
        const root = css(
            {
                position: "relative",
                zIndex: 1, // To make sure it sites on top of panel layout overflow indicators.
                maxWidth: percent(100),
                backgroundColor: ColorsUtils.colorOut(vars.outerBackground.color),
                ".searchBar": {
                    height: styleUnit(vars.searchBar.sizing.height),
                },

                // FIXME: why is this even here?
                // Kludge for the layout editor
                ".layoutEditorToolbarMenu": {
                    transform: "translate(-50%, 50%)",
                },
            },
            titleBarVars.swoop.amount > 0
                ? {
                      marginTop: -titleBarVars.swoop.swoopOffset,
                      paddingTop: titleBarVars.swoop.swoopOffset,
                  }
                : {},
        );

        const bannerContainer = css({
            position: "relative",
        });

        // Use this for cutting of the right image with overflow hidden.
        const overflowRightImageContainer = css({
            ...Mixins.absolute.fullSizeOfParent(),
            overflow: "hidden",
        });

        const fullHeight = css({
            height: percent(100),
        });

        const resultsAsModal = css({
            top: styleUnit(vars.searchBar.sizing.height + 2),
            ...mediaQueries.xs({
                width: viewWidth(100),
                left: `50%`,
                transform: translateX("-50%"),
                borderTopRightRadius: 0,
                borderTopLeftRadius: 0,
                ".suggestedTextInput-option, .suggestedTextInput-groupHeading": {
                    ...Mixins.padding({
                        horizontal: 21,
                    }),
                },
            }),
        });

        const middleContainer = css({
            height: percent(100),
            position: "relative",
            minHeight: styleUnit(vars.dimensions.minHeight),
            ...mediaQueries.oneColumnDown({
                minHeight: unitIfDefined(vars.dimensions.mobile.minHeight),
            }),
        });

        const searchStrip = css({
            position: "relative",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 1,
            background: ColorsUtils.colorOut(vars.searchStrip.bg),
            ...Mixins.padding(vars.searchStrip.padding),
            minHeight: unitIfDefined(vars.searchStrip.minHeight),
            marginTop: unitIfDefined(vars.searchStrip.offset),
            ...mediaQueries.oneColumnDown({
                background: vars.searchStrip.mobile.bg ? ColorsUtils.colorOut(vars.searchStrip.mobile.bg) : undefined,
                ...Mixins.padding(vars.searchStrip.mobile.padding),
                minHeight: unitIfDefined(vars.searchStrip.mobile.minHeight),
                marginTop: unitIfDefined(vars.searchStrip.mobile.offset),
            }),
        });

        return {
            root,
            bannerContainer,
            overflowRightImageContainer,
            fullHeight,
            outerBackground,
            contentContainer,
            text,
            defaultBannerSVG,
            backgroundImage,
            searchContainer,
            searchButton,
            iconTextAndSearchContainer,
            textAndSearchContainer,
            title,
            titleFlexSpacer,
            titleWrap,
            titleUrlWrap,
            description,
            descriptionWrap,
            content,
            iconContainer,
            icon,
            resultsAsModal,
            backgroundOverlay,
            imageElementContainer,
            rightImage,
            middleContainer,
            imagePositioner,
            searchStrip,
            noTopMargin,
            logoContainer,
            logoSpacer,
            logo,
        };
    },
);
