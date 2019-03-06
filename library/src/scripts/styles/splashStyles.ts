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
    toStringColor,
    unit,
} from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { percent, px, url } from "csx";
import { widgetVariables } from "@library/styles/widgetStyleVars";
import { AlignItemsProperty, TextAlignLastProperty, TextShadowProperty } from "csstype";
import { formElementsVariables } from "@library/components/forms/formElementStyles";

export const splashVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("splash");
    const globalVars = globalVariables();
    const widgetVars = widgetVariables();
    const formElVars = formElementsVariables();

    const topPadding = 69;
    const spacing = makeThemeVars("spacing", {
        padding: {
            top: topPadding,
            bottom: topPadding * 0.8,
            right: unit(widgetVars.spacing.inner.horizontalPadding + globalVars.gutter.quarter),
            left: unit(widgetVars.spacing.inner.horizontalPadding + globalVars.gutter.quarter),
        },
    });

    const outerBackground = makeThemeVars("outerBackground", {
        bg: globalVars.mainColors.primary,
        image: assetUrl("/resources/design/fallbackSplashBackground.svg"),
        fallbackImage: assetUrl("/resources/design/fallbackSplashBackground.svg"),
    });

    const innerBackground = makeThemeVars("innerBackground", {
        bg: undefined,
        padding: spacing.padding,
    });

    const colors = makeThemeVars("color", {
        fg: globalVars.mainColors.fg,
        shadowColor: globalVars.elementaryColors.black.fade(0.5),
    });

    const text = makeThemeVars("text", {
        align: "center",
        maxWidth: 700,
    });

    const title = makeThemeVars("title", {
        font: {
            color: globalVars.elementaryColors.white,
            size: globalVars.fonts.size.title,
            weight: globalVars.fonts.weights.semiBold,
            align: "center" as TextAlignLastProperty,
            shadow: `0 1px 25px ${colors.shadowColor}` as TextShadowProperty,
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
            align: title.font.align,
        },
    });

    const search = makeThemeVars("search", {
        margin: 30,
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
            size: formElVars.giantInput.fontSize,
        },
        button: {
            minWidth: 130,
            border: {
                width: formElVars.border.width,
                radius: formElVars.border.radius,
                color: formElVars.border.color,
            },
            font: {
                size: globalVars.fonts.size.medium,
            },
        },
        icon: {
            color: globalVars.mixBgAndFg(0.4),
        },
        input: {
            font: {
                size: globalVars.fonts.size.subTitle,
            },
        },
    });

    return {
        outerBackground,
        title,
        spacing,
        border,
        searchContainer,
        innerBackground,
        colors,
        text,
        paragraph,
        search,
        searchDrawer,
        searchBar,
        button,
    };
});

export const splashStyles = useThemeCache(() => {
    const vars = splashVariables();
    const style = styleFactory("splash");

    const root = style({
        position: "relative",
        backgroundColor: toStringColor(vars.outerBackground.bg),
    });

    const main = style("main", {});

    const fallbackImg = vars.outerBackground.fallbackImage;
    let backgroundImage = vars.outerBackground.image;
    let opacity = 1;

    if (backgroundImage.charAt(0) === "~") {
        backgroundImage = themeAsset(backgroundImage.substr(1, backgroundImage.length - 1));
    } else if (isAllowedUrl(backgroundImage)) {
        backgroundImage = fallbackImg;
        opacity = 0.4; // only for default bg
    }

    const outerBackground = style({
        ...centeredBackgroundProps(),
        display: "block",
        position: "absolute",
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
        backgroundSize: "cover",
        backgroundImage,
        opacity,
    });

    const container = style({});

    const innerContainer = style({
        paddingTop: vars.spacing.top,
        paddingBottom: vars.spacing.bottom,
    });

    const title = style({
        textAlign: "center",
        ...font(vars.title.font),
        paddingTop: px(vars.title.marginTop),
        marginBottom: px(vars.title.marginBottom),
        // textShadow: `0 1px 25px ${getColorDependantOnLightness(vars.title.fg, vars.title.fg, 0.9).fade(0.4)}`,
    });

    const search = style({});

    const searchContainer = style({
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

    return { root, container, innerContainer, title, search, outerBackground, searchContainer };
});
