/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { assetUrl } from "@library/application";
import { globalVariables } from "@library/styles/globalStyleVars";
import { centeredBackgroundProps, getColorDependantOnLightness } from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { percent, px, url } from "csx";

export const splashVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("splash");
    const globalVars = globalVariables();
    const elementaryColor = globalVars.elementaryColors;

    const fullBackground = makeThemeVars("fullBackground", {
        bg: globalVars.mainColors.primary,
        image: assetUrl("/resources/design/fallbackSplashBackground.svg"),
    });

    // Optional textShadow available
    const title = makeThemeVars("title", {
        fg: elementaryColor.white,
        fontSize: globalVars.fonts.size.title,
        textAlign: "center",
        fontWeight: globalVars.fonts.weights.semiBold,
        textShadow: `0 1px 25px ${elementaryColor.black.fade(0.5).toString()}`,
        marginTop: 28,
        marginBottom: 40,
    });

    const spacing = makeThemeVars("spacing", {
        top: 48,
        bottom: 48,
    });

    const border = makeThemeVars("border", {
        color: globalVars.mainColors.fg,
    });

    const searchContainer = {
        width: 670,
    };

    return { fullBackground, title, spacing, border, searchContainer };
});

export const splashStyles = useThemeCache(() => {
    const vars = splashVariables();
    const style = styleFactory("splash");

    const bg = vars.fullBackground.image;

    const root = style({
        backgroundColor: vars.fullBackground.bg.toString(),
        position: "relative",
    });

    const backgroundImage = bg ? url(bg) : undefined;
    const opacity = bg ? 0.4 : undefined; // only for default bg
    const fullBackground = style({
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
        fontSize: px(vars.title.fontSize),
        textAlign: "center",
        fontWeight: vars.title.fontWeight,
        color: vars.title.fg.toString(),
        paddingTop: px(vars.title.marginTop),
        marginBottom: px(vars.title.marginBottom),
        textShadow: `0 1px 25px ${getColorDependantOnLightness(vars.title.fg, vars.title.fg, 0.9).fade(0.4)}`,
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

    return { root, container, innerContainer, title, search, fullBackground, searchContainer };
});
