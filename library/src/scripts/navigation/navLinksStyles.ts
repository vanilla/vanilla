/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, margins, paddings, setAllLinkColors, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, px } from "csx";
import { media } from "typestyle";

export const navLinksVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("navLinks");
    const globalVars = globalVariables();

    const linksWithHeadings = makeThemeVars("linksWithHeadings", {
        paddings: {
            all: 20,
        },
        mobile: {
            paddings: {
                all: 0,
            },
        },
    });

    const item = makeThemeVars("item", {
        fontSize: globalVars.fonts.size.large,
    });

    const title = makeThemeVars("title", {
        fontSize: globalVars.fonts.size.title,
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: globalVars.lineHeights.condensed,
        maxWidth: percent(100),
        margins: {
            bottom: 8,
        },
        mobile: {
            fontSize: globalVars.fonts.size.large,
            fontWeight: globalVars.fonts.weights.bold,
        },
    });

    const link = makeThemeVars("link", {
        fg: globalVars.mainColors.fg,
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: globalVars.lineHeights.condensed,
        width: 203,
        maxWidth: percent(100),
        fontSize: 16,
    });

    const viewAllLinkColors = setAllLinkColors();
    const viewAll = makeThemeVars("viewAll", {
        color: viewAllLinkColors.color,
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: globalVars.fonts.size.medium,
        margins: {
            top: "auto",
        },
        paddings: {
            top: 20,
        },
        mobile: {
            paddings: {
                top: 8,
            },
        },
        $nest: viewAllLinkColors.nested,
    });

    const spacing = makeThemeVars("spacing", {
        paddings: {
            vertical: 34,
            horizontal: 40,
        },
        margin: 6,
        mobile: {
            paddings: {
                vertical: 22,
                horizontal: 8,
            },
        },
    });

    const columns = makeThemeVars("columns", {
        desktop: 2,
    });

    const separator = makeThemeVars("separator", {
        height: 1,
        bg: globalVars.mixBgAndFg(0.3),
    });

    const breakPoints = makeThemeVars("breakPoints", {
        oneColumn: 750,
    });

    const mediaQueries = () => {
        const oneColumn = styles => {
            return media({ maxWidth: px(breakPoints.oneColumn) }, styles);
        };

        return { oneColumn };
    };

    return {
        linksWithHeadings,
        item,
        title,
        columns,
        link,
        viewAll,
        spacing,
        separator,
        mediaQueries,
    };
});

export const navLinksClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = navLinksVariables();
    const style = styleFactory("navLinks");
    const mediaQueries = vars.mediaQueries();

    const root = style(
        {
            ...paddings(vars.spacing.paddings),
            display: "flex",
            flexDirection: "column",
            maxWidth: percent(100),
            width: percent(100 / vars.columns.desktop),
        },
        mediaQueries.oneColumn({
            width: percent(100),
            ...paddings(vars.spacing.mobile.paddings),
        }),
    );

    const items = style("items", {
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
    });

    const item = style("item", {
        display: "block",
        fontSize: unit(vars.item.fontSize),
        marginTop: unit(vars.spacing.margin),
        marginBottom: unit(vars.spacing.margin),
    });

    const title = style(
        "title",
        {
            display: "block",
            fontSize: unit(vars.title.fontSize),
            lineHeight: globalVars.lineHeights.condensed,
            fontWeight: globalVars.fonts.weights.semiBold,
            maxWidth: percent(100),
            ...margins(vars.title.margins),
        },
        mediaQueries.oneColumn({
            fontSize: unit(vars.title.mobile.fontSize),
            fontWeight: vars.title.mobile.fontWeight,
        }),
    );

    const linkColors = setAllLinkColors({
        default: globalVars.mainColors.fg,
    });

    const link = style("link", {
        display: "block",
        fontSize: unit(vars.link.fontSize),
        lineHeight: vars.link.lineHeight,
        color: linkColors.color,
        $nest: linkColors.nested,
    });

    const viewAllItem = style(
        "viewAllItem",
        {
            display: "block",
            fontSize: unit(vars.item.fontSize),
            ...margins(vars.viewAll.margins),
            ...paddings(vars.viewAll.paddings),
        },
        mediaQueries.oneColumn({
            ...paddings(vars.viewAll.mobile.paddings),
        }),
    );

    const viewAllLinkColors = setAllLinkColors({
        default: globalVars.mainColors.primary,
    });

    const viewAll = style("viewAll", {
        display: "block",
        fontWeight: vars.viewAll.fontWeight,
        fontSize: vars.viewAll.fontSize,
        color: viewAllLinkColors.color,
        $nest: viewAllLinkColors.nested,
    });

    const linksWithHeadings = style(
        "linksWithHeadings",
        {
            ...paddings(vars.linksWithHeadings.paddings),
            display: "flex",
            flexWrap: "wrap",
            alignItems: "stretch",
            justifyContent: "space-between",
        },
        mediaQueries.oneColumn({
            ...paddings(vars.linksWithHeadings.mobile.paddings),
        }),
    );

    const separator = style("separator", {
        display: "block",
        width: percent(100),
        height: unit(vars.separator.height),
        backgroundColor: colorOut(vars.separator.bg),
    });

    const separatorOdd = style(
        "separatorOdd",
        {
            $unique: true,
            display: "none",
        },
        mediaQueries.oneColumn({
            display: "block",
        }),
    );

    return {
        root,
        items,
        item,
        title,
        link,
        viewAllItem,
        viewAll,
        linksWithHeadings,
        separator,
        separatorOdd,
    };
});
