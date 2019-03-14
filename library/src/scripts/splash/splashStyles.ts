/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { assetUrl, isAllowedUrl, themeAsset } from "../dom/appUtils";
import { globalVariables } from "../styles/globalStyleVars";
import {
    background,
    centeredBackgroundProps,
    getBackgroundImage,
    modifyColorBasedOnLightness,
    IFont,
    paddings,
    colorOut,
    unit,
    IBackground,
    fonts,
} from "../styles/styleHelpers";
import { useThemeCache, styleFactory, variableFactory } from "../styles/styleUtils";
import { percent, px, url } from "csx";
import { widgetVariables } from "../styles/widgetStyleVars";
import { FontWeightProperty, PaddingProperty, TextAlignLastProperty, TextShadowProperty } from "csstype";
import { formElementsVariables } from "../forms/formElementStyles";
import { TLength } from "typestyle/lib/types";
import get from "lodash/get";
import { transparentColor } from "@library/styles/buttonStyles";

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

    const outerBackground: IBackground = makeThemeVars("outerBackground", {
        color: globalVars.mainColors.primary,
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
            shadow: `0 1px 15px ${modifyColorBasedOnLightness(text.fg, text.fg, text.shadowMix).fade(
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
        backgroundColor: colorOut(vars.outerBackground.color),
    });

    const image = getBackgroundImage(vars.outerBackground.image, vars.outerBackground.fallbackImage);
    const outerBackground = style("outerBackground", {
        ...centeredBackgroundProps(),
        display: "block",
        position: "absolute",
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
        ...background(vars.outerBackground),
        opacity: vars.outerBackground.fallbackImage && image === vars.outerBackground.fallbackImage ? 0.4 : undefined,
    });

    const innerContainer = style("innerContainer", {
        ...paddings(vars.spacing.padding),
        backgroundColor: vars.innerBackground.bg,
    });

    const title = style("title", {
        display: "block",
        ...fonts(vars.title.font as IFont),
        ...paddings({
            top: unit(vars.title.marginTop),
            bottom: unit(vars.title.marginBottom),
        }),
    });

    const text = style("text", {
        display: "block",
        color: colorOut(vars.text.fg),
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
    if (!hoverBg || buttonBg === transparentColor) {
        hoverBg = buttonFg ? buttonFg.fade(0.2) : buttonBorderColor ? buttonBorderColor.fade(0.2) : undefined;
    }

    const searchButton = style("splashSearchButton", {
        $nest: {
            "&&&&": {
                backgroundColor: buttonBg ? colorOut(buttonBg) : undefined,
                borderColor: buttonBorderColor ? colorOut(buttonBorderColor) : undefined,
                color: buttonFg ? colorOut(buttonFg) : undefined,

                $nest: {
                    "&:hover, &:focus, &:active, &.focus-visible": {
                        backgroundColor: colorOut(hoverBg),
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

    const icon = style("icon", {});
    const input = style("input", {});

    const buttonLoader = style("buttonLoader", {});

    return {
        root,
        outerBackground,
        innerContainer,
        title,
        text,
        icon,
        searchButton,
        searchContainer,
        input,
        buttonLoader,
    };
});
