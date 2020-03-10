/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import {
    borders,
    colorOut,
    EMPTY_BORDER,
    fonts,
    IBorderRadiusValue,
    IBordersWithRadius,
    placeholderStyles,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { cssRule } from "typestyle";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { percent } from "csx";
import merge from "lodash/merge";

export const inputVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const makeThemeVars = variableFactory("input");

    const colors = makeThemeVars("colors", {
        placeholder: globalVars.mixBgAndFg(0.5),
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        state: {
            fg: globalVars.mainColors.primary,
        },
    });

    const sizing = makeThemeVars("sizing", {
        height: formElementVars.sizing.height,
    });

    const font = makeThemeVars("font", {
        size: globalVars.fonts.size.large,
        weight: globalVars.fonts.weights.normal,
    });

    const border: IBordersWithRadius = makeThemeVars("borders", {
        ...EMPTY_BORDER,
        ...globalVars.borderType.formElements.default,
    });

    return {
        colors,
        border,
        sizing,
        font,
    };
});

export const inputMixin = (vars?: { sizing?: any; font?: any; colors?: any; border?: any }) => {
    const inputVars = inputVariables();
    const variables = {
        sizing: merge(inputVars.sizing, vars?.sizing ?? {}),
        font: merge(inputVars.font, vars?.font ?? {}),
        colors: merge(inputVars.colors, vars?.colors ?? {}),
        border: merge(inputVars.border, vars?.border ?? {}),
    };

    const { sizing, font, colors, border } = variables;

    return {
        ...textInputSizingFromFixedHeight(sizing.height, font.size, border.width * 2),
        backgroundColor: colorOut(colors.bg),
        color: colorOut(colors.fg),
        ...borders(border),
        ...fonts(font),
        outline: 0,
        $nest: {
            ...placeholderStyles({
                color: colorOut(colors.placeholder),
            }),
            "&. .SelectOne__input": {
                width: percent(100),
            },
            "&:active, &:hover, &:focus, &.focus-visible": {
                ...borders({
                    color: colors.state.fg,
                }),
            },
        },
    } as NestedCSSProperties;
};

export const inputClasses = useThemeCache(() => {
    const vars = inputVariables();
    const style = styleFactory("input");
    const formElementVars = formElementsVariables();
    const globalVars = globalVariables();

    // Use as assignable unique style.
    const text = style("text", inputMixin());

    // Use as a global selector. This should be refactored in the future.
    const applyInputCSSRules = () => cssRule(" .inputText.inputText", inputMixin());

    const inputText = style("inputText", {
        ...inputMixin(),
        marginBottom: 0,
        $nest: {
            "&&": {
                marginTop: unit(globalVars.gutter.quarter),
            },
        },
    });

    return { text, inputText, applyInputCSSRules };
});
