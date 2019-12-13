/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px, calc, quote } from "csx";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import {
    absolutePosition,
    colorOut,
    flexHelper,
    margins,
    negative,
    paddings,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const titleBarNavigationVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("titleBarNavigation");
    const globalVars = globalVariables();
    const varsFormElements = formElementsVariables();

    const border = makeThemeVars("border", {
        verticalWidth: 3,
    });

    const item = makeThemeVars("item", {
        size: varsFormElements.sizing.height,
    });

    const padding = makeThemeVars("padding", {
        horizontal: globalVars.gutter.half,
    });

    const linkActive = makeThemeVars("linkActive", {
        offset: 2,
        height: 3,
        bg: globalVars.mainColors.primary,
        bottomSpace: 1,
    });

    const navLinks = makeThemeVars("navLinks", {
        fontSize: 14,
        fontWeight: 400,
        padding: {
            top: 0,
            bottom: 0,
            left: 8,
            right: 8,
        },
    });

    return {
        border,
        item,
        linkActive,
        padding,
        navLinks,
    };
});

const titleBarNavClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const titleBarVars = titleBarVariables();
    const vars = titleBarNavigationVariables();
    const mediaQueries = titleBarVars.mediaQueries();
    const flex = flexHelper();
    const style = styleFactory("titleBarNav");

    const root = style(
        {
            ...flex.middleLeft(),
            position: "relative",
            height: unit(titleBarVars.sizing.height),
        },
        mediaQueries.compact({
            height: unit(titleBarVars.sizing.mobile.height),
        }),
    );

    const navigation = style("navigation", {});

    const items = style(
        "items",
        {
            ...flex.middleLeft(),
            height: unit(titleBarVars.sizing.height),
            ...paddings(vars.padding),
        },
        mediaQueries.compact({
            height: px(titleBarVars.sizing.mobile.height),
            justifyContent: "center",
            width: percent(100),
        }),
    );

    const link = style("link", {
        ...userSelect(),
        color: colorOut(titleBarVars.colors.fg),
        whiteSpace: "nowrap",
        lineHeight: globalVars.lineHeights.condensed,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        minHeight: unit(vars.item.size),
        textDecoration: "none",
        $nest: {
            "&.focus-visible": {
                color: colorOut(titleBarVars.colors.fg),
                backgroundColor: colorOut(titleBarVars.buttonContents.state.bg),
            },
            "&:focus": {
                color: colorOut(titleBarVars.colors.fg),
                backgroundColor: colorOut(titleBarVars.buttonContents.state.bg),
            },
            "&:hover": {
                color: colorOut(titleBarVars.colors.fg),
                backgroundColor: colorOut(titleBarVars.buttonContents.state.bg),
            },
        },
    });

    const linkActive = style("linkActive", {
        $nest: {
            "&:after": {
                ...absolutePosition.topLeft(
                    `calc(50% - ${unit(vars.linkActive.height + vars.linkActive.bottomSpace)})`,
                ),
                content: quote(""),
                height: unit(vars.linkActive.height),
                marginLeft: unit(negative(vars.linkActive.offset)),
                width: calc(`100% + ${unit(vars.linkActive.offset * 2)}`),
                backgroundColor: colorOut(vars.linkActive.bg),
                transform: `translateY(${unit(titleBarVars.sizing.height / 2)})`,
            },
        },
    });

    const linkContent = style("linkContent", {
        position: "relative",
        display: "flex",
        alignItems: "center",
        minHeight: unit(vars.item.size),
        height: 0, // IE11 Fix.
    });

    const firstItem = style("lastItem", {
        zIndex: 2,
    });

    const lastItem = style("lastItem", {
        zIndex: 2,
    });

    const navLinks = style("navLink", {
        $nest: {
            "&&": {
                fontSize: unit(vars.navLinks.fontSize),
                fontWeight: globalVars.fonts.weights.normal,
                paddingLeft: unit(vars.navLinks.padding.left),
                paddingRight: unit(vars.navLinks.padding.right),
            },
        },
    });

    return {
        root,
        navigation,
        items,
        link,
        linkActive,
        linkContent,
        lastItem,
        firstItem,
        navLinks,
    };
});

export default titleBarNavClasses;
