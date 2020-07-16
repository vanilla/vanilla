/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    margins,
    paddings,
    unit,
    fonts,
    extendItemContainer,
    EMPTY_FONTS,
    singleBorder,
    EMPTY_SPACING,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, px } from "csx";
import { media } from "typestyle";
import { NestedCSSProperties } from "typestyle/lib/types";
import { containerVariables } from "@library/layout/components/containerStyles";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";

export const navLinksVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("navLinks");
    const globalVars = globalVariables();

    const item = makeThemeVars("item", {
        fontSize: globalVars.fonts.size.large,
        padding: {
            ...EMPTY_SPACING,
            vertical: globalVars.spacer.size * 2,
            horizontal: containerVariables().spacing.padding * 2,
        },
        paddingMobile: {
            ...EMPTY_SPACING,
            horizontal: 0,
        },
    });

    const linksWithHeadings = makeThemeVars("linksWithHeadings", {
        paddings: {
            horizontal: item.padding.horizontal / 2,
        },
    });

    const title = makeThemeVars("title", {
        font: {
            ...EMPTY_FONTS,
            size: globalVars.fonts.size.title,
            weight: globalVars.fonts.weights.bold,
            lineHeight: globalVars.lineHeights.condensed,
        },
        maxWidth: percent(100),
        margins: {
            bottom: globalVars.gutter.size,
        },
        mobile: {
            font: {
                ...EMPTY_FONTS,
                fontSize: globalVars.fonts.size.large,
                fontWeight: globalVars.fonts.weights.bold,
            },
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

    const viewAllLinkColors = clickableItemStates();
    const viewAll = makeThemeVars("viewAll", {
        color: viewAllLinkColors.color,
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: globalVars.fonts.size.medium,
        margins: {
            top: "auto",
        },
        paddings: {
            top: globalVars.gutter.size,
        },
        $nest: viewAllLinkColors.$nest,
    });

    const spacing = makeThemeVars("spacing", {
        margin: 6,
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
            ...paddings({
                ...vars.item.padding,
                horizontal: vars.item.padding.horizontal / 2,
            }),
            display: "flex",
            flexDirection: "column",
            maxWidth: percent(100),
            width: percent(100 / vars.columns.desktop),
        },
        mediaQueries.oneColumn({
            width: percent(100),
            ...paddings({
                ...vars.item.paddingMobile,
                horizontal: vars.item.paddingMobile.horizontal / 2,
            }),
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

    const title = style("title", {
        $nest: {
            "&&": {
                display: "block",
                ...fonts(vars.title.font),
                maxWidth: percent(100),
                ...margins(vars.title.margins),
                ...mediaQueries.oneColumn(fonts(vars.title.mobile.font)),
            },
        },
    });

    const topTitle = style("topTitle", {
        $nest: {
            "&&": {
                ...margins({
                    vertical: vars.item.padding.vertical,
                    top: vars.item.padding.top,
                    bottom: 0,
                }),
                ...paddings({ horizontal: vars.item.padding.horizontal / 2 }),
                width: "100%",
            },
        },
    });

    const linkColors = clickableItemStates({
        default: globalVars.mainColors.fg,
    });

    const link = style("link", {
        display: "block",
        ...fonts({
            size: vars.link.fontSize,
            lineHeight: vars.link.lineHeight,
            // @ts-ignore
            color: linkColors.color,
        }),
        $nest: linkColors.$nest as NestedCSSProperties,
    } as NestedCSSProperties);

    const viewAllItem = style("viewAllItem", {
        display: "block",
        fontSize: unit(vars.item.fontSize),
        ...margins(vars.viewAll.margins),
        ...paddings(vars.viewAll.paddings),
    });

    const viewAllLinkColors = clickableItemStates({
        default: globalVars.mainColors.primary,
    });

    const noItemLink = style("noItemLink", { ...clickableItemStates(), marginTop: globalVars.gutter.quarter });

    const viewAll = style("viewAll", {
        display: "block",
        ...fonts({
            weight: vars.viewAll.fontWeight,
            size: vars.viewAll.fontSize,
            // @ts-ignore
            color: vars.viewAll.color,
        }),
        $nest: viewAllLinkColors.$nest,
    });

    const linksWithHeadings = style(
        "linksWithHeadings",
        {
            ...paddings(vars.linksWithHeadings.paddings),
            ...extendItemContainer(vars.item.padding.horizontal + vars.linksWithHeadings.paddings.horizontal),
            display: "flex",
            flexWrap: "wrap",
            alignItems: "stretch",
            justifyContent: "space-between",
        },
        mediaQueries.oneColumn({
            ...extendItemContainer(vars.item.paddingMobile.horizontal + vars.linksWithHeadings.paddings.horizontal),
        }),
    );

    const separator = style(
        "separator",
        {
            display: "block",
            width: percent(100),

            // Has to be a border and not a BG, because sometimes chrome rounds it's height to 0.99px and it disappears.
            borderBottom: singleBorder({ color: vars.separator.bg, width: vars.separator.height }),
        },
        mediaQueries.oneColumn(margins({ horizontal: vars.item.paddingMobile.horizontal })),
    );

    const separatorIndependant = style(
        "separatorIndependant",
        {
            display: "block",
            ...extendItemContainer(vars.item.padding.horizontal),

            // Has to be a border and not a BG, because sometimes chrome rounds it's height to 0.99px and it disappears.
            borderBottom: singleBorder({ color: vars.separator.bg, width: vars.separator.height }),
        },
        mediaQueries.oneColumn(margins({ horizontal: vars.item.paddingMobile.horizontal })),
        mediaQueries.oneColumn({
            ...extendItemContainer(vars.item.paddingMobile.horizontal),
        }),
    );

    const separatorOdd = style(
        "separatorOdd",
        {
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
        noItemLink,
        title,
        topTitle,
        link,
        viewAllItem,
        viewAll,
        linksWithHeadings,
        separator,
        separatorOdd,
        separatorIndependant,
    };
});
