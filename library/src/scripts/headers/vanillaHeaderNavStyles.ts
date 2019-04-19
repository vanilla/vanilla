/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px, calc, quote } from "csx";
import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import {
    absolutePosition,
    colorOut,
    flexHelper, margins,
    negative, paddings,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { layoutVariables } from "@library/layout/layoutStyles";

export const vanillaHeaderNavigationVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("vanillaHeaderNavigation");
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
        bg: globalVars.mainColors.bg.fade(0.9),
        bottomSpace: 1,
    });

    return {
        border,
        item,
        linkActive,
        padding,
    };
});

export default function vanillaHeaderNavClasses() {
    const globalVars = globalVariables();
    const headerVars = vanillaHeaderVariables();
    const vars = vanillaHeaderNavigationVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const flex = flexHelper();
    const style = styleFactory("vanillaHeaderNav");

    const root = style({
        ...flex.middleLeft(),
        position: "relative",
        height: unit(headerVars.sizing.height),
    }, mediaQueries.oneColumn({
        height: unit(headerVars.sizing.mobile.height),
    }));

    const navigation = style("navigation",{
        // height: unit(vars.item.size),
    }, mediaQueries.oneColumn({
        // height: unit(headerVars.sizing.mobile.height),
    }));

    const items = style(
        "items",
        {
            ...flex.middleLeft(),
            height: unit(headerVars.sizing.height),
            ...paddings(vars.padding),
        },
        mediaQueries.oneColumn({
            height: px(headerVars.sizing.mobile.height),
        }),
    );

    const link = style("link", {
        ...userSelect(),
        color: colorOut(headerVars.colors.fg),
        whiteSpace: "nowrap",
        lineHeight: globalVars.lineHeights.condensed,
        display: "flex",
        alignItems: 'center',
        justifyContent: "center",
        height: unit(vars.item.size),
        textDecoration: "none",
        $nest: {
            "&.focus-visible": {
                backgroundColor: colorOut(headerVars.buttonContents.state.bg),
            },
            "&:focus": {
                backgroundColor: colorOut(headerVars.buttonContents.state.bg),
            },
            "&:hover": {
                backgroundColor: colorOut(headerVars.buttonContents.state.bg),
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
                transform: `translateY(${unit(headerVars.sizing.height / 2)})`,
            },
        },
    });

    const linkContent = style("linkContent", {
        position: "relative",
    });

    const firstItem = style("lastItem", {
        zIndex: 2,
    });

    const lastItem = style("lastItem", {
        zIndex: 2,
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
    };
}
