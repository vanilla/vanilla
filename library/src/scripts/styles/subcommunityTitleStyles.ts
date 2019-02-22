/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    componentThemeVariables,
    debugHelper,
    defaultTransition,
    unit,
} from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { FontSizeProperty, HeightProperty, MarginProperty, PaddingProperty, WidthProperty } from "csstype";
import { TLength } from "typestyle/lib/types";
import { ColorHelper, percent } from "csx";
import { shadowHelper } from "@library/styles/shadowHelpers";

export function subcommunityTileVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "subcommunityTile");

    const spacing = {
        default: 24 as PaddingProperty<TLength>,
        color: globalVars.mainColors.primary as ColorHelper,
        ...themeVars.subComponentStyles("spacing"),
    };

    const frame = {
        height: 90 as PaddingProperty<TLength>,
        width: 90 as PaddingProperty<TLength>,
        bottomMargin: 16 as MarginProperty<TLength>,
        ...themeVars.subComponentStyles("frame"),
    };

    const title = {
        fontSize: globalVars.fonts.size.large as FontSizeProperty<TLength>,
        lineHeight: globalVars.lineHeights.condensed,
        marginBottom: 6,
        ...themeVars.subComponentStyles("title"),
    };

    const description = {
        fontSize: globalVars.fonts.size.medium as FontSizeProperty<TLength>,
        marginTop: 6,
        lineHeight: globalVars.lineHeights.excerpt,
        ...themeVars.subComponentStyles("description"),
    };

    const link = {
        topPadding: 38 as PaddingProperty<TLength>,
        bottomPadding: 24 as PaddingProperty<TLength>,
        leftPadding: 24 as PaddingProperty<TLength>,
        rightPadding: 24 as PaddingProperty<TLength>,
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        ...themeVars.subComponentStyles("link"),
    };

    const fallBackIcon = {
        width: 90 as WidthProperty<TLength>,
        height: 90 as HeightProperty<TLength>,
        fg: globalVars.mainColors.primary,
        ...themeVars.subComponentStyles("fallBackIcon"),
    };

    return { spacing, frame, title, description, link, fallBackIcon };
}

export function subcommunityTileClasses(theme?: object) {
    const vars = subcommunityTileVariables(theme);
    const debug = debugHelper("subcommunityTile");
    const shadow = shadowHelper(theme);

    const root = style({
        display: "flex",
        flexDirection: "column",
        alignItems: "stretch",
        width: percent(100),
        padding: unit(vars.spacing.default),
        userSelect: "none",
        flexGrow: 1,
        ...debug.name(),
    });

    const link = style({
        ...shadow.embed(),
        ...defaultTransition("box-shadow"),
        display: "block",
        position: "relative",
        cursor: "pointer",
        flexGrow: 1,
        color: vars.link.fg.toString(),
        backgroundColor: vars.link.bg.toString(),
        paddingTop: unit(vars.link.topPadding),
        paddingRight: unit(vars.link.rightPadding),
        paddingBottom: unit(vars.link.bottomPadding),
        paddingLeft: unit(vars.link.leftPadding),
        $nest: {
            "&:hover": {
                ...shadow.embedHover(),
            },
        },
        ...debug.name("link"),
    });

    const main = style({
        position: "relative",
        ...debug.name("main"),
    });

    const frame = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        position: "relative",
        height: unit(vars.frame.height),
        width: unit(vars.frame.width),
        marginTop: "auto",
        marginRight: "auto",
        marginLeft: "auto",
        marginBottom: unit(vars.frame.bottomMargin),
        ...debug.name("iconFrame"),
    });

    const icon = style({
        display: "block",
        position: "absolute",
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
        margin: "auto",
        height: "auto",
        maxWidth: percent(100),
        maxHeight: percent(100),
        ...debug.name("icon"),
    });

    const title = style({
        fontSize: unit(vars.title.fontSize),
        lineHeight: vars.title.lineHeight,
        textAlign: "center",
        marginBottom: unit(vars.title.marginBottom),
        ...debug.name("title"),
    });

    const description = style({
        position: "relative",
        marginTop: unit(vars.description.marginTop),
        fontSize: unit(vars.description.fontSize),
        lineHeight: vars.description.lineHeight,
        textAlign: "center",
        ...debug.name("description"),
    });

    const fallBackIcon = style({
        ...absolutePosition.middleOfParent(),
        width: unit(vars.fallBackIcon.width),
        height: unit(vars.fallBackIcon.height),
        color: vars.fallBackIcon.fg.toString(),
        ...debug.name("fallbackicon"),
    });

    return { root, link, frame, icon, main, title, description, fallBackIcon };
}
