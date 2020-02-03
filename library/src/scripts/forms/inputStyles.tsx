/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import {
    borders,
    colorOut,
    IBordersWithRadius,
    placeholderStyles,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { cssRule } from "typestyle";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { NestedCSSProperties } from "typestyle/lib/types";

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
    });

    const border: IBordersWithRadius = makeThemeVars("borders", globalVars.border);

    return {
        colors,
        border,
        sizing,
        font,
    };
});

export const inputClasses = useThemeCache(() => {
    const vars = inputVariables();
    const style = styleFactory("input");
    const formElementVars = formElementsVariables();
    const globalVars = globalVariables();

    const inputMixin: NestedCSSProperties = {
        ...textInputSizingFromFixedHeight(vars.sizing.height, vars.font.size, formElementVars.border.fullWidth),
        backgroundColor: colorOut(vars.colors.bg),
        color: colorOut(vars.colors.fg),
        ...borders(vars.border),
        outline: 0,
        fontWeight: globalVars.fonts.weights.normal,
        $nest: {
            ...placeholderStyles({
                color: colorOut(vars.colors.placeholder),
            }),
            "&:active, &:hover, &:focus, &.focus-visible": {
                ...borders({
                    color: vars.colors.state.fg,
                }),
            },
        },
    };

    // Use as assignable unique style.
    const text = style("text", inputMixin);

    // Use as a global selector. This should be refactored in the future.
    const applyInputCSSRules = () => cssRule(" .inputText", inputMixin);

    const inputText = style("inputText", {
        marginBottom: 0,
        $nest: {
            "&&": {
                marginTop: unit(globalVars.gutter.quarter),
            },
        },
    });

    return { text, inputText, inputMixin, applyInputCSSRules };
});
