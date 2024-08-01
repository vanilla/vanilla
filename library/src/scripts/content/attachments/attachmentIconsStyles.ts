/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, px } from "csx";
import { metasVariables } from "@library/metas/Metas.variables";

export const attachmentIconVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = componentThemeVariables("attachmentIcon");

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
});

export const attachmentIconsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = attachmentIconVariables();
    const style = styleFactory("attachmentIcons");
    const metasVars = metasVariables();

    const root = style({
        display: "block",
        position: "relative",
    });

    const items = style("items", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "flex-start",
        justifyContent: "flex-end",
        width: calc(`100% + ${px(vars.spacing.default * 2)}`),
        overflow: "hidden",
        ...Mixins.margin({
            top: -vars.spacing.default,
            left: -vars.spacing.default,
            right: metasVars.spacing.horizontal,
        }),
    });

    const item = style("item", {
        margin: vars.spacing.default,
    });

    return { root, items, item };
});

export const attachmentIconClasses = useThemeCache(() => {
    const vars = attachmentIconVariables();
    const style = styleFactory("attachmentIcon");

    const root = style({
        display: "block",
        width: px(vars.icon.size),
        height: px(vars.icon.size),
        boxShadow: `0 0 0 1px ${vars.shadow.color}`,
    });

    const error = style("error", {
        width: px(vars.icon.size),
        height: px(vars.icon.errorIconHeight),
    });

    return { root, error };
});
