/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    borderStyles,
    componentThemeVariables,
    debugHelper,
    allLinkStates,
    margins,
    absolutePosition,
    unit,
} from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { percent, px } from "csx";

export function attachmentVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const themeVars = componentThemeVariables(theme, "attachment");

    const border = {
        color: globalVars.mixBgAndFg(0.2),
        style: "solid",
        width: formElementVars.border.width,
        radius: 0,
        ...themeVars.subComponentStyles("border"),
    };

    const sizing = {
        width: globalVars.embed.sizing.width,
        maxWidth: percent(100),
        ...themeVars.subComponentStyles("sizing"),
    };

    const padding = {
        default: 12,
        ...themeVars.subComponentStyles("padding"),
    };

    const shadows = {
        default: `0 1px 3px 0 ${globalVars.mainColors.fg.fade(0.3).toString()}`,
        ...themeVars.subComponentStyles("shadows"),
    };

    const text = {
        fontSize: globalVars.fonts.size.medium,
        ...themeVars.subComponentStyles("text"),
    };

    const title = {
        color: globalVars.mixBgAndFg(0.9),
        ...themeVars.subComponentStyles("title"),
    };

    const loading = {
        opacity: 0.5,
    };

    return { border, padding, shadows, text, title, loading, sizing };
}

export function attachmentClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const vars = attachmentVariables(theme);
    const debug = debugHelper("attachment");

    const root = style({
        display: "block",
        position: "relative",
        textDecoration: "none",
        color: "inherit",
        boxShadow: vars.shadows.default,
        width: px(globalVars.embed.sizing.width),
        maxWidth: percent(100),
        margin: "auto",
        overflow: "hidden",
        userSelect: "none",
        ...borderStyles(vars.border),
        $nest: {
            "&.isLoading, &.hasError": {
                cursor: "pointer",
                $nest: {
                    "&:hover": {
                        boxShadow: `0 0 0 ${px(
                            globalVars.embed.select.borderWidth,
                        )} ${globalVars.embed.focus.color.fade(0.5)} inset`,
                    },
                    "&:focus": {
                        boxShadow: `0 0 0 ${px(
                            globalVars.embed.select.borderWidth,
                        )} ${globalVars.embed.focus.color.toString()} inset`,
                    },
                },
            },
        },
        ...debug.name(),
    });

    const link = style({
        ...allLinkStates({
            textDecoration: "none",
        }),
        ...debug.name("link"),
    });

    const box = style({
        position: "relative",
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
        padding: px(vars.padding.default),
        width: percent(100),
        ...borderStyles({
            color: "transparent",
            width: 2,
            radius: 0,
        }),
        ...debug.name("box"),
    });

    const format = style({
        flexBasis: px(globalVars.icon.sizes.small + vars.padding.default),
        height: unit(globalVars.icon.sizes.small),
        paddingRight: unit(vars.padding.default),
        flexShrink: 1,
        ...debug.name("format"),
    });

    const main = style({
        display: "flex",
        flexDirection: "column",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        flexGrow: 1,
        ...debug.name("main"),
    });

    const title = style({
        fontSize: px(vars.text.fontSize),
        color: vars.title.color.toString(),
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: px(globalVars.icon.sizes.small),
        ...debug.name("title"),
    });

    const metas = style({
        marginBottom: px(0),
        lineHeight: globalVars.lineHeights.condensed,
        ...debug.name("metas"),
    });

    const close = style({
        ...margins({
            top: px(-((formElementVars.sizing.height - globalVars.icon.sizes.default) / 2)),
            right: px(-((formElementVars.sizing.height - globalVars.icon.sizes.default) / 2)),
        }),
        pointerEvents: "all",
        ...debug.name("close"),
    });

    const loadingProgress = style({
        ...absolutePosition.bottomLeft(),
        transition: `width ease-out .2s`,
        height: px(3),
        marginBottom: px(-1),
        width: 0,
        maxWidth: percent(100),
        backgroundColor: globalVars.mainColors.primary.toString(),
        ...debug.name("loadingProgress"),
    });

    const loadingContent = style({
        $nest: {
            ".attachment-format": {
                opacity: vars.loading.opacity,
            },
            ".attachment-main": {
                opacity: vars.loading.opacity,
            },
        },
        ...debug.name("loadingContent"),
    });

    return { root, link, box, format, main, title, metas, close, loadingProgress, loadingContent };
}
