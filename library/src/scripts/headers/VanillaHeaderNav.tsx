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
    flexHelper,
    modifyColorBasedOnLightness,
    negative,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { layoutVariables } from "@library/layout/layoutStyles";
import { userContentClasses } from "@library/content/userContentStyles";

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
    };
});

export default function vanillaHeaderNavClasses() {
    const headerVars = vanillaHeaderVariables();
    const vars = vanillaHeaderNavigationVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const flex = flexHelper();
    const style = styleFactory("vanillaHeaderNav");

    const root = style({
        position: "relative",
    });

    const navigation = style({
        ...flex.middle(),
        height: percent(100),
        color: "inherit",
        $nest: {
            "&.isScrolled": {
                alignItems: "center",
                justifyContent: "center",
                flexWrap: "nowrap",
                minWidth: percent(100),
            },
        },
    });

    const items = style(
        "items",
        {
            ...flex.middle(),
            height: unit(vars.item.size),
        },
        mediaQueries.oneColumn({
            height: px(headerVars.sizing.mobile.height),
        }),
    );

    const link = style("link", {
        ...userSelect(),
        display: "flex",
        justifyContent: "center",
        alignItems: "stretch",
        height: unit(vars.item.size),
        $nest: {
            "&.focus-visible": {
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
        ...flex.middleLeft(),
        position: "relative",
    });

    return {
        root,
        navigation,
        items,
        link,
        linkActive,
        linkContent,
    };
}
