/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, debugHelper, unit, userSelect } from "@library/styles/styleHelpers";
import { componentThemeVariables, styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent, px } from "csx";

export const tokensVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = componentThemeVariables("tokens");

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
});

export const tokensClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = tokensVariables();
    const formElVars = formElementsVariables();
    const style = styleFactory("tokens");

    const root = style({
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
                display: "flex",
                flexWrap: "wrap",
                alignItems: "center",
                justifyContent: "flexStart",
                minHeight: unit(formElVars.sizing.height),
                paddingTop: 0,
                paddingRight: px(12),
                paddingBottom: 0,
                paddingLeft: px(12),
                $nest: {
                    "&.tokens__value-container--has-value": {
                        padding: px(3),
                    },
                    "& .tokens__multi-value + div:not(.tokens__multi-value)": {
                        display: "flex",
                        flexWrap: "wrap",
                        alignItems: "center",
                        justifyContent: "flexStart",
                        flexGrow: 1,
                    },
                    ".tokens__input": {
                        flexGrow: 1,
                    },
                    input: {
                        width: percent(100),
                        minWidth: unit(45),
                    },
                },
            },
            ".tokens__multi-value": {
                fontSize: unit(vars.token.fontSize),
                fontWeight: globalVars.fonts.weights.bold,
                textShadow: vars.token.textShadow,
                paddingLeft: px(3),
                paddingRight: px(2),
                margin: px(3),
                backgroundColor: vars.token.bg.toString(),
                ...userSelect(),
            },
            "& .tokens__multi-value__label": {
                paddingLeft: px(3),
            },
        },
    });

    const inputWrap = style("inputWrarp", {
        $nest: {
            "&.hasFocus .inputBlock-inputText": {
                borderColor: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const removeIcon = style("removeIcon", {
        $nest: {
            "&.icon": {
                width: unit(vars.clearIcon.width),
                height: unit(vars.clearIcon.width),
            },
        },
    });

    return { root, removeIcon, inputWrap };
});
