/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssOut } from "@dashboard/compatibilityStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    borders,
    colorOut,
    EMPTY_BORDER,
    EMPTY_FONTS,
    fonts,
    IBordersWithRadius,
    placeholderStyles,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { percent } from "csx";
import merge from "lodash/merge";
import { NestedCSSProperties } from "typestyle/lib/types";

export const inputVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const formElementVars = formElementsVariables(forcedVars);
    const makeThemeVars = variableFactory("input", forcedVars);

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
        ...EMPTY_FONTS,
        size: globalVars.fonts.size.large,
        weight: globalVars.fonts.weights.normal,
        color: colors.fg,
    });

    const border = makeThemeVars("borders", {
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
        border: {
            ...EMPTY_BORDER,
            ...inputVars.border,
            ...(vars?.border ?? {}),
        },
    };

    const { sizing, font, colors, border } = variables;

    return {
        ...textInputSizingFromFixedHeight(sizing.height, font.size, border.width * 2),
        backgroundColor: colorOut(colors.bg),
        color: colorOut(colors.fg),
        ...borders(border),
        ...fonts(font),
        lineHeight: unit(font.size),
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

    const inputPaddingMixin: NestedCSSProperties = {
        padding: inputMixin().padding,
        paddingTop: inputMixin().paddingTop,
        paddingBottom: inputMixin().paddingBottom,
        paddingLeft: inputMixin().paddingLeft,
        paddingRight: inputMixin().paddingRight,
    };

    // Use as assignable unique style.
    const text = style("text", inputMixin());

    // Use as a global selector. This should be refactored in the future.
    const applyInputCSSRules = () => cssOut(" .inputText.inputText", inputMixin());

    const inputText = style("inputText", {
        ...inputMixin(),
        marginBottom: 0,
        $nest: {
            "&&": {
                marginTop: unit(globalVars.gutter.quarter),
            },
        },
    });

    return { text, inputText, inputPaddingMixin, inputMixin, applyInputCSSRules };
});
