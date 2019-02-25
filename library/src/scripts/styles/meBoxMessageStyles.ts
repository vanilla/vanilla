/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper, toStringColor, unit } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { calc, percent, quote } from "csx";
import { objectFitWithFallback } from "@library/styles/styleHelpers";
import { absolutePosition } from "@library/styles/styleHelpers";
import vanillaHeaderStyles, { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";

export function meBoxMessageVariables(theme?: object) {
    const themeVars = componentThemeVariables(theme, "meBoxMessage");
    const spacing = {
        padding: 8,
        ...themeVars.subComponentStyles("spacing"),
    };

    const imageContainer = {
        width: 40,
        ...themeVars.subComponentStyles("imageContainer"),
    };

    const unreadDot = {
        width: 12,
        ...themeVars.subComponentStyles("unreadDot"),
    };

    return { spacing, imageContainer, unreadDot };
}

export function meBoxMessageClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = meBoxMessageVariables(theme);
    const headerVars = vanillaHeaderVariables(theme);
    const debug = debugHelper("meBoxMessage");

    const root = style({
        ...debug.name(),
        display: "block",
        $nest: {
            "& + &": {
                borderTop: `solid 1px ${globalVars.border.color.toString()}`,
            },
        },
    });

    const link = style({
        display: "flex",
        flexWrap: "nowrap",
        padding: unit(vars.spacing.padding),
        color: "inherit",
        userSelect: "none",
        $nest: {
            "&:active, &:focus, &:hover, &.focus-visible": {
                backgroundColor: toStringColor(globalVars.states.active.color),
                textDecoration: "none",
                color: headerVars.colors.fg.toString(),
            },
            "&:active .meta, &:focus .meta, &:hover .meta, &.focus-visible .meta": {
                color: headerVars.colors.fg.toString(),
                opacity: 0.75,
            },
        },
        ...debug.name("link"),
    });

    const imageContainer = style({
        position: "relative",
        width: unit(vars.imageContainer.width),
        height: unit(vars.imageContainer.width),
        flexBasis: unit(vars.imageContainer.width),
        borderRadius: percent(50),
        overflow: "hidden",
        border: `solid 1px ${globalVars.border.color.toString()}`,
        ...debug.name("imageContainer"),
    });

    const image = style({
        ...objectFitWithFallback(),
        ...debug.name("image"),
    });

    const status = style({
        position: "relative",
        width: unit(vars.unreadDot.width),
        flexBasis: unit(vars.unreadDot.width),
        ...debug.name("status"),
        $nest: {
            "&.isUnread": {
                $nest: {
                    "&:after": {
                        ...absolutePosition.middleRightOfParent(),
                        content: quote(""),
                        height: unit(vars.unreadDot.width),
                        width: unit(vars.unreadDot.width),
                        backgroundColor: globalVars.mainColors.primary.toString(),
                        borderRadius: percent(50),
                    },
                },
            },
        },
    });

    const contents = style({
        flexGrow: 1,
        paddingLeft: vars.spacing.padding,
        paddingRight: vars.spacing.padding,
        maxWidth: calc(`100% - ${unit(vars.unreadDot.width + vars.imageContainer.width)}`),
        ...debug.name("contents"),
    });

    const message = style({
        lineHeight: globalVars.lineHeights.excerpt,
        ...debug.name("message"),
    });

    return { root, link, imageContainer, image, status, contents, message };
}
