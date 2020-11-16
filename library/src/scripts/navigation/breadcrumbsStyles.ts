/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { px, em } from "csx";
import {
    colorOut,
    margins,
    singleLineEllipsis,
    unit,
    userSelect,
    EMPTY_FONTS,
    fonts,
    ensureColorHelper,
} from "@library/styles/styleHelpers";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";

export const breadcrumbsVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeVariables = variableFactory("breadcrumbs");

    const sizing = makeVariables("sizing", {
        minHeight: 16,
    });

    const linkColors = clickableItemStates();
    const link = makeVariables("link", {
        textTransform: "uppercase",
        font: {
            ...EMPTY_FONTS,
            color: ensureColorHelper(linkColors.color as string),
            size: globalVars.fonts.size.small,
            lineHeight: globalVars.lineHeights.condensed,
        },
    });

    return { sizing, link };
});

export const breadcrumbsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = breadcrumbsVariables();
    const style = styleFactory("breadcrumbs");
    const linkColors = clickableItemStates();
    const link = style("link", {
        ...singleLineEllipsis(),
        display: "inline-flex",
        ...fonts(vars.link.font),
        textTransform: vars.link.textTransform as any,
        $nest: linkColors.$nest,
    });

    const root = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        $nest: {
            [`& a.${link}`]: {
                textDecoration: "underline",
            },
        },
    });

    const crumb = style("crumb", {
        ...singleLineEllipsis(),
        display: "inline-flex",
        fontSize: unit(globalVars.fonts.size.small),
        lineHeight: globalVars.lineHeights.condensed,
        overflow: "hidden",
    });

    const list = style("list", {
        display: "flex",
        alignItems: "center",
        flexDirection: "row",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        minHeight: unit(vars.sizing.minHeight),
    });

    const separator = style("separator", {
        ...userSelect(),
        ...margins({
            vertical: 0,
            horizontal: em(0.2),
        }),
    });

    const separatorIcon = style("separatorIcon", {
        display: "inline-flex",
        justifyContent: "center",
        alignItems: "center",
        fontFamily: "arial, sans-serif !important",
        width: `1ex`,
        opacity: 0.5,
        position: "relative",
        color: "inherit",
    });

    const breadcrumb = style("breadcrumb", {
        display: "inline-flex",
        lineHeight: 1,
        minHeight: unit(vars.sizing.minHeight),
    });

    const current = style("current", {
        color: "inherit",
        opacity: 1,
    });

    return {
        root,
        list,
        separator,
        separatorIcon,
        breadcrumb,
        link,
        current,
        crumb,
    };
});
