/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { assetUrl, isAllowedUrl, themeAsset } from "@library/application";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    centeredBackgroundProps,
    font,
    getColorDependantOnLightness,
    IFont,
    paddings,
    toStringColor,
    unit,
} from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { percent, px, url } from "csx";
import { widgetVariables } from "@library/styles/widgetStyleVars";
import { FontWeightProperty, PaddingProperty, TextAlignLastProperty, TextShadowProperty } from "csstype";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { TLength } from "typestyle/lib/types";
import get from "lodash/get";

export const splashVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("splash");
    const globalVars = globalVariables();
    const widgetVars = widgetVariables();
    const formElVars = formElementsVariables();

    const topPadding = 69;
    const spacing = makeThemeVars("spacing", {
        padding: {
            top: topPadding as PaddingProperty<TLength>,
            bottom: (topPadding * 0.8) as PaddingProperty<TLength>,
            right: unit(widgetVars.spacing.inner.horizontalPadding + globalVars.gutter.quarter) as PaddingProperty<
                TLength
            >,
            left: unit(widgetVars.spacing.inner.horizontalPadding + globalVars.gutter.quarter) as PaddingProperty<
                TLength
            >,
        },
    });

    const outerBackground = makeThemeVars("outerBackground", {
        bg: globalVars.mainColors.primary,
        backgroundPosition: "50% 50%",
        backgroundSize: "cover",
        image: assetUrl("/resources/design/fallbackSplashBackground.svg"),
        fallbackImage: assetUrl("/resources/design/fallbackSplashBackground.svg"),
    });

    const innerBackground = makeThemeVars("innerBackground", {
        bg: undefined,
        padding: {
            top: spacing.padding,
            right: spacing.padding,
            bottom: spacing.padding,
            left: spacing.padding,
        },
    });

    const text = makeThemeVars("text", {
        fg: globalVars.elementaryColors.white,
        align: "center",
        shadowMix: 1,
        shadowOpacity: 1,
    });

    const title = makeThemeVars("title", {
        align: "center",
        maxWidth: 700,
        font: {
            color: text.fg,
            size: globalVars.fonts.size.title,
            weight: globalVars.fonts.weights.semiBold as FontWeightProperty,
            align: text.align as TextAlignLastProperty,
            shadow: `0 1px 15px ${getColorDependantOnLightness(text.fg, text.fg, text.shadowMix).fade(
                text.shadowOpacity,
            )}` as TextShadowProperty,
        },
        marginTop: 28,
        marginBottom: 40,
    });

    const border = makeThemeVars("border", {
        color: globalVars.mainColors.fg,
    });

    const searchContainer = makeThemeVars("searchContainer", {
        width: 670,
    });

    const paragraph = makeThemeVars("paragraph", {
        margin: ".4em",
        text: {
            size: 24,
            weight: 300,
        },
    });

    const search = makeThemeVars("search", {
        margin: 30,
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
    });

    const searchDrawer = makeThemeVars("searchDrawer", {
        bg: globalVars.mainColors.bg,
    });

    const searchBar = makeThemeVars("searchBar", {
        sizing: {
            height: formElVars.giantInput.height,
            width: 705,
        },
        font: {
            color: globalVars.elementaryColors.white,
            size: formElVars.giantInput.fontSize,
        },
        button: {
            minWidth: 130,
            font: {
                size: globalVars.fonts.size.medium,
            },
            icon: {
                color: globalVars.mixBgAndFg(0.4),
            },
            input: {
                font: {
                    size: globalVars.fonts.size.subTitle,
                },
            },
        },
    });

    return {
        outerBackground,
        spacing,
        border,
        searchContainer,
        innerBackground,
        text,
        title,
        paragraph,
        search,
        searchDrawer,
        searchBar,
    };
});

export const splashStyles = useThemeCache(() => {
    const vars = splashVariables();
    const style = styleFactory("splash");

    const root = style({
        position: "relative",
        backgroundColor: toStringColor(vars.outerBackground.bg),
    });

    let backgroundImage = vars.outerBackground.image;
    let opacity;

    if (backgroundImage.charAt(0) === "~") {
        backgroundImage = themeAsset(backgroundImage.substr(1, backgroundImage.length - 1));
    } else if (!isAllowedUrl(backgroundImage)) {
        backgroundImage = vars.outerBackground.fallbackImage;
        opacity = 0.4; // only for default bg
    }

    const outerBackground = style("outerBackground", {
        ...centeredBackgroundProps(),
        display: "block",
        position: "absolute",
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
        backgroundSize: "cover",
        backgroundImage: url(backgroundImage),
        opacity,
    });

    const innerContainer = style("innerContainer", {
        ...paddings(vars.spacing.padding),
        backgroundColor: vars.innerBackground.bg,
    });

    const title = style("title", {
        display: "block",
        ...font(vars.title.font as IFont),
        ...paddings({
            top: unit(vars.title.marginTop),
            bottom: unit(vars.title.marginBottom),
        }),
    });

    const text = style("text", {
        display: "block",
        color: toStringColor(vars.text.fg),
        width: unit(vars.title.maxWidth),
        maxWidth: percent(100),
        margin: `auto auto 0`,
        textAlign: "center",
        $nest: {
            "& + .splash-p": {
                marginTop: unit(vars.search.margin),
            },
        },
    });

    const buttonBorderColor = get(vars, "searchBar.button.borderColor", false);
    const buttonBg = get(vars, "searchBar.button.bg", false);
    const buttonFg = get(vars, "searchBar.button.fg", false);
    let hoverBg = get(vars, "searchBar.button.hoverBg", false);
    if (!hoverBg || buttonBg === "transparent") {
        hoverBg = buttonFg ? buttonFg.fade(0.2) : buttonBorderColor ? buttonBorderColor.fade(0.2) : undefined;
    }

    const searchButton = style("searchButton", {
        $nest: {
            "&&&&": {
                backgroundColor: buttonBg ? toStringColor(buttonBg) : undefined,
                borderColor: buttonBorderColor ? toStringColor(buttonBorderColor) : undefined,
                color: buttonFg ? toStringColor(buttonFg) : undefined,

                $nest: {
                    "&:hover, &:focus, &:active, &.focus-visible": {
                        backgroundColor: toStringColor(hoverBg),
                    },
                },
            },
        },
    });

    const searchContainer = style("searchContainer", {
        position: "relative",
        maxWidth: percent(100),
        width: px(vars.searchContainer.width),
        margin: "auto",
        $nest: {
            ".search-results": {
                maxWidth: percent(100),
                width: px(vars.searchContainer.width),
                margin: "auto",
            },
        },
    });

    const input = style("input", {});

    const buttonLoader = style("buttonLoader", {});

    return {
        root,
        outerBackground,
        innerContainer,
        title,
        text,
        searchButton,
        searchContainer,
        input,
        buttonLoader,
    };
});
