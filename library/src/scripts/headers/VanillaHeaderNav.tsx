/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px, calc } from "csx";
import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import { layoutVariables } from "@library/styles/layoutStyles";
import { flexHelper, unit } from "@library/styles/styleHelpers";
import { styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";

export function vanillaHeaderNavigation() {
    const globalVars = globalVariables();
    const varsFormElements = formElementsVariables();

    const border = {
        verticalWidth: 3,
        active: {
            border: {
                color: globalVars.mainColors.bg.fade(0.9),
            },
        },
    };

    const active = {
        bottomOffset: 8,
    };

    const item = {
        size: varsFormElements.sizing.height,
    };

    return {
        border,
        active,
        item,
    };
}

export default function vanillaHeaderNavClasses() {
    const headerVars = vanillaHeaderVariables();
    const vars = vanillaHeaderNavigation();
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
            $nest: {
                "&.isCurrent": {
                    $nest: {
                        "&.vanillaHeaderNav-linkContent": {
                            $nest: {
                                "&:after": {
                                    marginLeft: px(-2),
                                    width: calc(`100% + 4px`),
                                    borderBottomColor: vars.border.active.border.color.toString(),
                                },
                            },
                        },
                    },
                },
            },
        },
        mediaQueries.oneColumn({
            height: px(headerVars.sizing.mobile.height),
        }),
    );

    const link = style("link", {
        display: "flex",
        justifyContent: "center",
        alignItems: "stretch",
        height: unit(vars.item.size),
        $nest: {
            "&.focus-visible": {
                backgroundColor: headerVars.buttonContents.hover.bg.toString(),
            },
            "&:hover": {
                backgroundColor: headerVars.buttonContents.hover.bg.toString(),
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
        linkContent,
    };
}
