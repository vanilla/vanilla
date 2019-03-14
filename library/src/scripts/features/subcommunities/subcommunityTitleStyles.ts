/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    absolutePosition,
    borders,
    colorOut,
    debugHelper,
    defaultTransition,
    paddings,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { TLength } from "typestyle/lib/types";
import { componentThemeVariables, useThemeCache } from "@library/styles/styleUtils";
import { ColorHelper, percent } from "csx";
import { FontSizeProperty, HeightProperty, MarginProperty, PaddingProperty, WidthProperty } from "csstype";
import { style } from "typestyle";

export const subcommunityTileVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = componentThemeVariables("subcommunityTile");

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
        padding: {
            top: 38,
            bottom: 24,
            left: 24,
            right: 24,
        },
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        minHeight: 280,
        ...themeVars.subComponentStyles("link"),
    };

    const fallBackIcon = {
        width: 90 as WidthProperty<TLength>,
        height: 90 as HeightProperty<TLength>,
        fg: globalVars.mainColors.primary,
        ...themeVars.subComponentStyles("fallBackIcon"),
    };

    return { spacing, frame, title, description, link, fallBackIcon };
});

export const subcommunityTileClasses = useThemeCache(() => {
    const vars = subcommunityTileVariables();
    const globalVars = globalVariables();
    const debug = debugHelper("subcommunityTile");
    const shadow = shadowHelper();

    const root = style({
        display: "flex",
        flexDirection: "column",
        alignItems: "stretch",
        width: percent(100),
        padding: unit(vars.spacing.default),
        ...userSelect(),
        flexGrow: 1,
        ...debug.name(),
    });

    const link = style({
        ...defaultTransition("box-shadow", "border"),
        ...paddings(vars.link.padding),
        display: "block",
        position: "relative",
        cursor: "pointer",
        flexGrow: 1,
        color: vars.link.fg.toString(),
        backgroundColor: colorOut(vars.link.bg),
        borderRadius: unit(2),
        minHeight: unit(vars.link.minHeight),
        ...shadowOrBorderBasedOnLightness(
            globalVars.body.backgroundImage.color,
            borders({
                color: vars.link.fg.fade(0.3),
            }),
            shadow.embed(),
        ),
        $nest: {
            "&:hover": {
                ...shadowOrBorderBasedOnLightness(
                    globalVars.body.backgroundImage.color,
                    borders({
                        color: vars.link.fg.fade(0.5),
                    }),
                    shadow.embedHover(),
                ),
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
});
