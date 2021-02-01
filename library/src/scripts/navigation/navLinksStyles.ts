/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { extendItemContainer, singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";
import { media } from "@library/styles/styleShim";
import { CSSObject } from "@emotion/css";
import { containerVariables } from "@library/layout/components/containerStyles";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

export const navLinksVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("navLinks");
    const globalVars = globalVariables();

    const item = makeThemeVars("item", {
        fontSize: globalVars.fonts.size.large,
        padding: Variables.spacing({
            vertical: globalVars.spacer.size * 2,
            horizontal: containerVariables().spacing.padding * 2,
        }),
        paddingMobile: Variables.spacing({
            horizontal: 0,
        }),
    });

    const linksWithHeadings = makeThemeVars("linksWithHeadings", {
        paddings: {
            horizontal: (item.padding.horizontal as number) / 2,
        },
    });

    const title = makeThemeVars("title", {
        font: Variables.font({
            size: globalVars.fonts.size.title,
            weight: globalVars.fonts.weights.bold,
            lineHeight: globalVars.lineHeights.condensed,
            color: undefined,
        }),
        maxWidth: percent(100),
        margins: {
            bottom: globalVars.gutter.size,
        },
        mobile: {
            font: Variables.font({
                size: globalVars.fonts.size.large,
                weight: globalVars.fonts.weights.bold,
            }),
        },
    });

    const link = makeThemeVars("link", {
        fg: globalVars.mainColors.fg,
        fontWeight: globalVars.fonts.weights.normal,
        lineHeight: globalVars.lineHeights.condensed,
        width: 203,
        maxWidth: percent(100),
        fontSize: 16,
    });

    const viewAllLinkColors = Mixins.clickable.itemState();

    const viewAll = makeThemeVars("viewAll", {
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: globalVars.fonts.size.medium,
        margins: {
            top: "auto",
        },
        paddings: {
            top: globalVars.gutter.size,
        },
        icon: false,
        ...viewAllLinkColors,
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
        hidden: false,
    });

    const breakPoints = makeThemeVars("breakPoints", {
        oneColumn: 750,
    });

    const mediaQueries = () => {
        const oneColumn = (styles) => {
            return media({ maxWidth: breakPoints.oneColumn }, styles);
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
            ...Mixins.padding({
                ...vars.item.padding,
                horizontal: (vars.item.padding.horizontal as number) / 2,
            }),
            display: "flex",
            flexDirection: "column",
            maxWidth: percent(100),
            width: percent(100 / vars.columns.desktop),
        },
        mediaQueries.oneColumn({
            width: percent(100),
            ...Mixins.padding({
                ...vars.item.paddingMobile,
                horizontal: (vars.item.paddingMobile.horizontal as number) / 2,
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
        fontSize: styleUnit(vars.item.fontSize),
        marginTop: styleUnit(vars.spacing.margin),
        marginBottom: styleUnit(vars.spacing.margin),
    });

    const title = style("title", {
        ...{
            "&&": {
                display: "block",
                ...Mixins.font(vars.title.font),
                maxWidth: percent(100),
                ...Mixins.margin(vars.title.margins),
                ...mediaQueries.oneColumn(Mixins.font(vars.title.mobile.font)),
            },
        },
    });

    const topTitle = style("topTitle", {
        ...{
            "&&": {
                ...Mixins.margin({
                    vertical: vars.item.padding.vertical,
                    top: vars.item.padding.top,
                    bottom: 0,
                }),
                ...Mixins.padding({ horizontal: (vars.item.padding.horizontal as number) / 2 }),
                width: "100%",
            },
        },
    });

    const linkColors = Mixins.clickable.itemState({
        default: vars.link.fg,
    });

    const link = style("link", {
        display: "block",
        ...Mixins.font({
            size: vars.link.fontSize,
            lineHeight: vars.link.lineHeight,
            weight: vars.link.fontWeight,
            // @ts-ignore
            color: linkColors.color,
        }),
        ...linkColors,
    });

    const viewAllItem = style("viewAllItem", {
        display: "block",
        fontSize: styleUnit(vars.item.fontSize),
        ...Mixins.margin(vars.viewAll.margins),
        ...Mixins.padding(vars.viewAll.paddings),
    });

    const viewAllLinkColors = Mixins.clickable.itemState({
        default: globalVars.mainColors.primary,
    });

    const noItemLink = style("noItemLink", { ...Mixins.clickable.itemState(), marginTop: globalVars.gutter.quarter });

    const viewAll = style("viewAll", {
        display: "block",
        ...Mixins.font({
            weight: vars.viewAll.fontWeight as number,
            size: vars.viewAll.fontSize as number,
            // @ts-ignore
            color: vars.viewAll.color,
        }),
        ...{
            ...viewAllLinkColors,
            span: {
                marginRight: vars.viewAll.icon ? 10 : undefined,
            },
        },
    });

    const linksWithHeadings = style(
        "linksWithHeadings",
        {
            ...Mixins.padding(vars.linksWithHeadings.paddings),
            ...extendItemContainer(
                (vars.item.padding.horizontal as number) + vars.linksWithHeadings.paddings.horizontal,
            ),
            display: "flex",
            flexWrap: "wrap",
            alignItems: "stretch",
            justifyContent: "space-between",
        },
        mediaQueries.oneColumn({
            ...extendItemContainer(
                (vars.item.paddingMobile.horizontal as number) + vars.linksWithHeadings.paddings.horizontal,
            ),
        }),
    );

    const separator = style(
        "separator",
        {
            display: vars.separator.hidden ? "none" : "block",
            width: percent(100),

            // Has to be a border and not a BG, because sometimes chrome rounds it's height to 0.99px and it disappears.
            borderLeft: "none",
            borderRight: "none",
            borderTop: "none",
            borderBottom: singleBorder({ color: vars.separator.bg, width: vars.separator.height }),
            ...{
                "&:last-child": {
                    display: "none",
                },
            },
        },
        mediaQueries.oneColumn(Mixins.margin({ horizontal: vars.item.paddingMobile.horizontal })),
    );

    const separatorIndependant = style(
        "separatorIndependant",
        {
            display: "block",
            ...extendItemContainer(vars.item.padding.horizontal as number),

            // Has to be a border and not a BG, because sometimes chrome rounds it's height to 0.99px and it disappears.
            borderBottom: singleBorder({ color: vars.separator.bg, width: vars.separator.height }),
        },
        mediaQueries.oneColumn(Mixins.margin({ horizontal: vars.item.paddingMobile.horizontal })),
        mediaQueries.oneColumn({
            ...extendItemContainer(vars.item.paddingMobile.horizontal as number),
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
