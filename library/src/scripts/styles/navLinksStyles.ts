/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper, setAllLinkColors, unit } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { percent, px } from "csx";
import { layoutVariables } from "@library/styles/layoutStyles";

export function navLinksVariables(theme?: object) {
    const themeVars = componentThemeVariables(theme, "navLinks");
    const globalVars = globalVariables(theme);

    const linksWithHeadings = {
        padding: 16,
        ...themeVars.subComponentStyles("linksWithHeadings"),
    };

    const item = {
        fontSize: globalVars.fonts.size.large,
        ...themeVars.subComponentStyles("item"),
    };

    const title = {
        fontSize: 20,
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: globalVars.lineHeights.condensed,
        width: 203,
        maxWidth: percent(100),
        ...themeVars.subComponentStyles("link"),
    };

    const link = {
        color: globalVars.mainColors.fg,
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: globalVars.lineHeights.condensed,
        width: 203,
        maxWidth: percent(100),
        fontSize: 16,
        ...themeVars.subComponentStyles("link"),
    };

    const viewAll = {
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: globalVars.fonts.size.medium,
        marginTop: "auto",
        ...setAllLinkColors(),
        ...themeVars.subComponentStyles("viewAll"),
    };

    const spacing = {
        padding: 24,
        margin: 6,
    };

    const sizing = {
        width: 250,
    };

    return { linksWithHeadings, item, title, sizing, link, viewAll, spacing };
}

export function navLinksClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = navLinksVariables(theme);
    const debug = debugHelper("navLinks");
    const mediaQueries = layoutVariables(theme).mediaQueries();

    const root = style(
        {
            ...debug.name(),
            display: "flex",
            flexDirection: "column",
            padding: unit(vars.spacing.padding),
            width: unit(vars.sizing.width),
        },
        mediaQueries.xs({
            width: percent(100),
        }),
    );

    const items = style({
        ...debug.name("items"),
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
    });

    const item = style({
        ...debug.name("item"),
        display: "block",
        fontSize: unit(vars.item.fontSize),
        marginTop: unit(vars.spacing.margin),
        marginBottom: unit(vars.spacing.margin),
        $nest: {
            "&.isViewAll": {
                marginTop: "auto",
            },
        },
    });

    const title = style({
        ...debug.name("title"),
        display: "block",
        fontSize: unit(vars.title.fontSize),
        lineHeight: globalVars.lineHeights.condensed,
        fontWeight: globalVars.fonts.weights.semiBold,
        width: unit(vars.title.width),
        maxWidth: percent(100),
        marginBottom: unit(vars.spacing.margin),
    });

    const link = style({
        ...debug.name("link"),
        display: "block",
        fontSize: unit(vars.link.fontSize),
        lineHeight: vars.link.lineHeight,
        ...setAllLinkColors(),
    });

    const viewAll = style({
        ...debug.name("viewAll"),
        fontWeight: vars.viewAll.fontWeight,
        fontSize: vars.viewAll.fontSize,
        ...setAllLinkColors({
            default: {
                color: globalVars.mainColors.primary.toString(),
            },
        }),
    });

    const linksWithHeadings = style(
        {
            padding: unit(vars.linksWithHeadings.padding),
            display: "flex",
            flexWrap: "wrap",
            alignItems: "stretch",
            justifyContent: "space-between",
            ...debug.name("linksWithHeadings"),
        },
        mediaQueries.xs({
            padding: 0,
        }),
    );

    return { root, items, item, title, link, viewAll, linksWithHeadings };
}
