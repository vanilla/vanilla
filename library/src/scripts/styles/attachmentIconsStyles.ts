/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    borderStyles,
    componentThemeVariables,
    debugHelper,
    allLinkStates,
    margins,
    absolutePosition,
    unit,
} from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { calc, px, percent } from "csx";

export function attachmentIconVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "attachmentIcon");

    const spacing = {
        default: 12,
        ...themeVars.subComponentStyles("spacing"),
    };

    const shadow = {
        color: globalVars.mixBgAndFg(0.1),
        ...themeVars.subComponentStyles("shadow"),
    };

    const icon = {
        size: 16,
        errorIconHeight: 14.39,
    };

    return { spacing, shadow, icon };
}

export function attachmentIconsClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const vars = attachmentIconVariables(theme);
    const debug = debugHelper("attachmentIcons");

    const root = style({
        display: "block",
        position: "relative",
        ...debug.name(),
    });

    const items = {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "flex-start",
        justifyContent: "flex-end",
        width: calc(`100% + ${px(vars.spacing.default * 2)}`),
        overflow: "hidden",
        ...debug.name("items"),
        ...margins({
            top: -vars.spacing.default,
            left: -vars.spacing.default,
            right: globalVars.meta.spacing.default,
        }),
    };

    const item = {
        margin: vars.spacing.default,
        ...debug.name("item"),
    };

    return { root, items, item };
}

export function attachmentIconClasses(theme?: object) {
    const vars = attachmentIconVariables(theme);
    const debug = debugHelper("attachmentIcon");

    const root = style({
        display: "block",
        width: px(vars.icon.size),
        height: px(vars.icon.size),
        boxShadow: `0 0 0 1px ${vars.shadow.color}`,
        ...debug.name(),
    });

    const error = style({
        width: px(vars.icon.size),
        height: px(vars.icon.errorIconHeight),
        ...debug.name("error"),
    });

    return { root, error };
}
