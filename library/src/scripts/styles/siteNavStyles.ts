/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper, unit } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { layoutVariables } from "@library/styles/layoutStyles";
import { calc, percent, px } from "csx";

export function siteNavVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const themeVars = componentThemeVariables(theme, "siteNav");

    const node = {
        fontSize: globalVars.fonts.size.medium,
        fg: globalVars.mainColors.fg,
        lineHeight: globalVars.lineHeights.condensed,
        borderWidth: 1,
        padding: 4,
        active: {
            fg: globalVars.links.colors.default,
            fontWeight: globalVars.fonts.weights.bold,
        },
        ...themeVars.subComponentStyles("node"),
    };

    const title = {
        fontSize: globalVars.fonts.size.large,
        fontWeight: globalVars.fonts.weights.bold,
    };

    const nodeToggle = {
        height: node.fontSize * node.lineHeight,
        width: globalVars.gutter.size,
        iconWidth: 7,
        ...themeVars.subComponentStyles("nodeToggle"),
    };

    const spacer = {
        default: 7,
    };

    return { node, title, nodeToggle, spacer };
}

export function siteNavClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = siteNavVariables(theme);
    const mediaQueries = layoutVariables().mediaQueries();

    const debug = debugHelper("siteNav");

    const root = style(
        {
            position: "relative",
            display: "block",
            zIndex: 1,
            marginTop: unit(vars.nodeToggle.height / 2 - vars.node.fontSize / 2),
            ...debug.name(),
        },
        mediaQueries.noBleed({
            marginLeft: unit(vars.nodeToggle.width - vars.nodeToggle.iconWidth / 2 - vars.spacer.default),
        }),
    );

    const title = style({
        fontSize: unit(globalVars.fonts.size.large),
        fontWeight: globalVars.fonts.weights.bold,
        ...debug.name("title"),
    });

    const children = style({
        position: "relative",
        display: "block",
        $nest: {
            "& + .siteNavAdminLinks": {
                margin: `25px 0 0`,
            },
        },
        ...debug.name("children"),
    });

    return { root, title, children };
}

export function siteNavNodeClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = siteNavVariables(theme);
    const mediaQueries = layoutVariables().mediaQueries();

    const debug = debugHelper("siteNavNode");

    const root = style({
        position: "relative",
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        flexWrap: "nowrap",
        fontSize: unit(vars.node.fontSize),
        color: vars.node.fg.toString(),
        $nest: {
            "&.isCurrent": {
                color: vars.node.active.fg.toString(),
                fontWeight: vars.node.active.fontWeight,
            },
        },
        ...debug.name(),
    });

    const children = style({
        marginLeft: unit(vars.spacer.default),
        ...debug.name("children"),
    });

    const contents = style({
        display: "block",
        width: percent(100),
        $nest: {
            ".siteNavNode-buttonOffset": {
                top: unit(15.5),
            },
        },
        ...debug.name("contents"),
    });

    const link = style({
        display: "block",
        flexGrow: 1,
        color: "inherit",
        lineHeight: vars.node.lineHeight,
        minHeight: px(30),
        outline: 0,
        padding: 0,
        $nest: {
            "&:active, &:focus": {
                outline: 0,
            },
            "&:hover": {
                color: globalVars.links.colors.default.toString(),
            },
            "&.hasChildren": {
                fontWeight: globalVars.fonts.weights.semiBold,
                $nest: {
                    "&.isFirstLevel": {
                        fontSize: unit(globalVars.fonts.size.large),
                        fontWeight: globalVars.fonts.weights.bold,
                    },
                },
            },
        },
        ...debug.name("link"),
    });

    const label = style(
        {
            position: "relative",
            display: "block",
            width: calc(`100% + ${unit(vars.nodeToggle.width)}`),
            marginLeft: unit(-vars.nodeToggle.width),
            textAlign: "left",
            border: `solid transparent ${unit(vars.node.borderWidth)}`,
            paddingTop: unit(vars.node.padding + vars.node.borderWidth),
            paddingRight: unit(vars.node.padding),
            paddingBottom: unit(vars.node.padding + vars.node.borderWidth),
            paddingLeft: unit(vars.nodeToggle.width - vars.node.borderWidth),
            ...debug.name("label"),
        },
        mediaQueries.oneColumn({
            fontSize: unit(globalVars.fonts.size.large),
        }),
    );

    const spacer = style({
        display: "block",
        height: unit(vars.nodeToggle.height),
        width: unit(vars.spacer.default),
        margin: `6px 0`,
        ...debug.name("spacer"),
    });

    const toggle = style({
        margin: `6px 0`,
        padding: 0,
        zIndex: 1,
        display: "block",
        alignItems: "center",
        justifyContent: "center",
        outline: 0,
        height: unit(vars.nodeToggle.height),
        width: unit(vars.nodeToggle.width),
        ...debug.name("toggle"),
    });

    const buttonOffset = style({
        position: "relative",
        display: "flex",
        justifyContent: "flex-end",
        width: unit(vars.nodeToggle.width),
        marginLeft: unit(-vars.nodeToggle.width),
        top: px(16),
        transform: `translateY(-50%)`,
        ...debug.name("buttonOffset"),
    });

    return { root, children, contents, link, label, spacer, toggle, buttonOffset };
}
