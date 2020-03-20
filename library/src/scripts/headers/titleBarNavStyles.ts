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
    negative,
    paddings,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { LogoAlignment } from "@library/headers/TitleBar";

export const titleBarNavigationVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("titleBarNavigation");
    const globalVars = globalVariables();
    const varsFormElements = formElementsVariables();
    const titleBarVars = titleBarVariables();

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
        bg: titleBarVars.colors.fg,
        bottomSpace: 1,
    });

    const navLinks = makeThemeVars("navLinks", {
        fontSize: 14,
        padding: {
            left: 8,
            right: 8,
        },
    });

    const navPadding = makeThemeVars("navPadding", {
        padding: {
            bottom: 4,
        },
    });

    return {
        border,
        item,
        linkActive,
        padding,
        navLinks,
        navPadding,
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

    const navigation = style(
        "navigation",
        titleBarVars.logo.doubleLogoStrategy === "hidden" || titleBarVars.logo.justifyContent === LogoAlignment.CENTER
            ? {
                  marginLeft: unit(-(vars.padding.horizontal + vars.navLinks.padding.left)),
              }
            : {},
    );

    const navigationCentered = style("navigationCentered", {
        ...absolutePosition.middleOfParent(true),
        display: "inline-flex",
    });

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
        paddingLeft: unit(vars.navLinks.padding.left),
        paddingRight: unit(vars.navLinks.padding.right),
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
        fontSize: unit(vars.navLinks.fontSize),
        fontWeight: globalVars.fonts.weights.normal,
        position: "relative",
        display: "inline-flex",
        alignItems: "center",
        alignSelf: "center",
        height: 0, // IE11 Fix.
    });

    const firstItem = style("lastItem", {
        zIndex: 2,
    });

    const lastItem = style("lastItem", {
        zIndex: 2,
    });
    const navContiner = style("navContiner", {
        paddingBottom: unit(vars.navPadding.padding.bottom),
    });
    const navLinks = style("navLinks", {});

    return {
        root,
        navigation,
        navigationCentered,
        items,
        link,
        linkActive,
        linkContent,
        lastItem,
        firstItem,
        navLinks,
        navContiner,
    };
});

export default titleBarNavClasses;
