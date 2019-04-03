/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {colorOut, debugHelper, margins, setAllLinkColors, unit} from "@library/styles/styleHelpers";
import {styleFactory, useThemeCache, variableFactory} from "@library/styles/styleUtils";
import {percent, px} from "csx";
import {layoutVariables} from "@library/layout/layoutStyles";
import {media} from "typestyle";

export const navLinksVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("navLinks");
    const globalVars = globalVariables();

    const linksWithHeadings = makeThemeVars("linksWithHeadings", {
        padding: 16,
    });

    const item = makeThemeVars("item", {
        fontSize: globalVars.fonts.size.large,
    });

    const title = makeThemeVars("title", {
        fontSize: 20,
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: globalVars.lineHeights.condensed,
        width: 203,
        maxWidth: percent(100),
    });

    const link = makeThemeVars("link", {
        color: globalVars.mainColors.fg,
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: globalVars.lineHeights.condensed,
        width: 203,
        maxWidth: percent(100),
        fontSize: 16,
    });

    const viewAll = makeThemeVars("viewAll", {
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: globalVars.fonts.size.medium,
        margins: {
            top: globalVars.gutter.size,
            bottom: 10,
        },
        ...setAllLinkColors(),
    });

    const spacing = makeThemeVars("spacing", {
        padding: 24,
        margin: 6,
    });

    const columns = makeThemeVars("columns", {
        desktop: 2,
    });

    const separator = makeThemeVars("separator", {
        height: 1,
        bg: globalVars.mixBgAndFg(.5),
    });

    const breakPoints = makeThemeVars("breakPoints",{
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
            display: "flex",
            flexDirection: "column",
            padding: unit(vars.spacing.padding),
            maxWidth: percent(100),
            width: percent(100/vars.columns.desktop),
        },
        mediaQueries.oneColumn({
            width: percent(100),
        }),
    );

    const items = style("items",{
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
    });

    const item = style("item",{
        display: "block",
        fontSize: unit(vars.item.fontSize),
        marginTop: unit(vars.spacing.margin),
        marginBottom: unit(vars.spacing.margin),
    });

    const title = style("title",{
        display: "block",
        fontSize: unit(vars.title.fontSize),
        lineHeight: globalVars.lineHeights.condensed,
        fontWeight: globalVars.fonts.weights.semiBold,
        width: unit(vars.title.width),
        maxWidth: percent(100),
        marginBottom: unit(vars.spacing.margin),
    });

    const link = style("link",{
        display: "block",
        fontSize: unit(vars.link.fontSize),
        lineHeight: vars.link.lineHeight,
        ...setAllLinkColors(),
    });

    const viewAllitem = style("viewAllItem", {
        display: "block",
        fontSize: unit(vars.item.fontSize),
        ...margins(vars.viewAll.margins),
    });

    const viewAll = style("viewAll", {
        fontWeight: vars.viewAll.fontWeight,
        fontSize: vars.viewAll.fontSize,
        ...setAllLinkColors({
            default: {
                color: colorOut(globalVars.mainColors.primary),
            },
        }),
    });

    const linksWithHeadings = style("linksWithHeadings",
        {
            padding: unit(vars.linksWithHeadings.padding),
            display: "flex",
            flexWrap: "wrap",
            alignItems: "stretch",
            justifyContent: "space-between",
        },
        mediaQueries.oneColumn({
            padding: 0,
        }),
    );

    const separator = style("separator", {
        display: "block",
        width: percent(100),
        height: unit(vars.separator.height),
        backgroundColor: colorOut(vars.separator.bg),
    });


    const separatorOdd = style("separatorOdd", {
        display: "none",
    }, mediaQueries.oneColumn({
        display: "block",
    }));

    return {
        root,
        items,
        item,
        title,
        link,
        viewAllitem,
        viewAll,
        linksWithHeadings,
        separator,
        separatorOdd,
    };
});
