/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { em } from "csx";
import { singleLineEllipsis, userSelect, ensureColorHelper } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

export const breadcrumbsVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeVariables = variableFactory("breadcrumbs");

    const sizing = makeVariables("sizing", {
        minHeight: 16,
    });

    const link = makeVariables("link", {
        font: Variables.font({
            color: ensureColorHelper(globalVars.links.colors.default),
            size: globalVars.fonts.size.small,
            lineHeight: globalVars.lineHeights.condensed,
            transform: "uppercase",
        }),
    });

    return { sizing, link };
});

export const breadcrumbsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = breadcrumbsVariables();
    const style = styleFactory("breadcrumbs");
    const linkColors = Mixins.clickable.itemState();
    const link = style("link", {
        ...singleLineEllipsis(),
        display: "inline-flex",
        ...Mixins.font(vars.link.font),
        ...linkColors,
    });

    const root = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        ...{
            [`& a.${link}`]: {
                textDecoration: "underline",
            },
        },
    });

    const crumb = style("crumb", {
        ...singleLineEllipsis(),
        display: "inline-flex",
        fontSize: styleUnit(globalVars.fonts.size.small),
        lineHeight: globalVars.lineHeights.condensed,
        overflow: "hidden",
    });

    const list = style("list", {
        display: "flex",
        alignItems: "center",
        flexDirection: "row",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        minHeight: styleUnit(vars.sizing.minHeight),
    });

    const separator = style("separator", {
        ...userSelect(),
        ...Mixins.margin({
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
        minHeight: styleUnit(vars.sizing.minHeight),
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
