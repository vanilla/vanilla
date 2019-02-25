/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper, unit } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { percent, px } from "csx";

export function tokensVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "tokens");

    const token = {
        fontSize: globalVars.meta.text.fontSize,
        bg: globalVars.mixBgAndFg(0.15),
        textShadow: `${globalVars.mainColors.bg} 0 0 1px`,
    };

    const clear = {
        width: 16,
        ...themeVars.subComponentStyles("clear"),
    };

    const clearIcon = {
        width: 8,
        ...themeVars.subComponentStyles("clearIcon"),
    };

    return { clearIcon, clear, token };
}

export function tokensClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = tokensVariables(theme);
    const formElVars = formElementsVariables(theme);
    const debug = debugHelper("tokens");

    const root = style({
        ...debug.name(),
        $nest: {
            ".tokens-clear": {
                height: unit(vars.clear.width),
                width: unit(vars.clear.width),
                padding: 0,
                borderRadius: percent(50),
                marginLeft: px(1),
                $nest: {
                    "&:hover, &:focus": {
                        backgroundColor: globalVars.mainColors.primary.toString(),
                        color: globalVars.mainColors.bg.toString(),
                    },
                },
            },
            ".tokens__value-container": {
                minHeight: unit(formElVars.sizing.height),
                paddingTop: 0,
                paddingRight: px(3),
                paddingBottom: 0,
                paddingLeft: px(3),
                $nest: {
                    "&.tokens__value-container--has-value": {
                        padding: px(3),
                    },
                },
            },
            ".tokens__multi-value": {
                fontSize: unit(vars.token.fontSize),
                fontWeight: globalVars.fonts.weights.bold,
                textShadow: vars.token.textShadow,
                paddingLeft: px(6),
                paddingRight: px(2),
                margin: px(3),
                backgroundColor: vars.token.bg.toString(),
                userSelect: "none",
            },
        },
    });

    const removeIcon = style({
        $nest: {
            "&.icon": {
                width: unit(vars.clearIcon.width),
                height: unit(vars.clearIcon.width),
            },
        },
        ...debug.name("removeIcon"),
    });

    return { root, removeIcon };
}
