/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px, quote, calc } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, unit } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";
import { flexHelper } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/styles/layoutStyles";
import { formElementsVariables } from "@library/components/forms/formElementStyles";

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
    const debug = debugHelper("vanillaHeaderNav");

    const root = style({
        position: "relative",
    });

    const navigation = style({
        ...debug.name(),
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
        {
            ...debug.name("items"),
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

    const link = style({
        ...debug.name("link"),
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

    const linkContent = style({
        ...debug.name("linkContent"),
        ...flex.middleLeft(),
        position: "relative",
        $nest: {
            "&:after": {
                content: quote(""),
                position: "absolute",
                top: 0,
                right: 0,
                bottom: 0,
                left: 0,
                width: percent(100),
                marginBottom: -vars.active.bottomOffset,
                borderStyle: "solid",
                borderColor: "transparent",
                borderTopWidth: vars.border.verticalWidth,
                borderRightWidth: 0,
                borderBottomWidth: vars.border.verticalWidth,
                borderLeftWidth: 0,
            },
        },
    });

    return {
        root,
        navigation,
        items,
        link,
        linkContent,
    };
}
