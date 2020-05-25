/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { borders, colorOut, pointerEvents, unit } from "@library/styles/styleHelpers";
import { calc, percent } from "csx";
import { inputMixin, inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";

export const selectOneVariables = useThemeCache(() => {
    const vars = variableFactory("selectOne");

    const padding = vars("padding", {
        right: 30,
        left: inputMixin().paddingRight as string,
    });

    return { padding };
});

export const selectOneClasses = useThemeCache(() => {
    const style = styleFactory("selectOne");
    const vars = selectOneVariables();
    const globalVars = globalVariables();

    const singleValueOffset = 26;

    const inputWrap = style("inputWrap", {
        $nest: {
            "&.hasFocus .inputBlock-inputText": {
                ...borders({
                    ...globalVars.borderType.formElements.default,
                    color: globalVars.mainColors.primary,
                }),
            },
            "& .inputBlock-inputText": {
                paddingRight: unit(vars.padding.right),
                paddingLeft: unit(vars.padding.left),
                position: "relative",
            },
            "& .SelectOne__indicators": {
                position: "absolute",
                top: 0,
                right: 6,
                bottom: 0,
            },
            "& .SelectOne__indicator-separator": {
                display: "none",
            },
            "& .SelectOne__indicator": {
                cursor: "pointer",
            },
            "& .SelectOne__single-value": {
                textOverflow: "ellipsis",
                maxWidth: calc(`100% - ${unit(vars.padding.right + singleValueOffset)}`),
            },
            "& .SelectOne__single-value + div": {
                textOverflow: "ellipsis",
                maxWidth: calc(`100% - ${unit(vars.padding.right)}`),
            },
            "& .SelectOne__value-container.inputText.inputText": {
                paddingRight: unit(inputVariables().sizing.height),
                ...pointerEvents(), // sometimes this element blocks the click to focus the input.
            },
            "& .SelectOne__value-container > *": {
                width: percent(100),
                overflow: "hidden",
                lineHeight: "inherit",
            },
            "& .SelectOne--is-disabled": {
                cursor: "pointer",
                opacity: 0.5,
            },
            "& .SelectOne__menu-notice--no-options": {
                padding: 10,
                overflow: "hidden",
            },
            "& .inputBlock": inputMixin(),
        },
    });

    const checkIcon = style("checkIcon", {
        color: colorOut(globalVars.mainColors.primary),
    });

    const checkBoxAfterInput = style("checkBoxAfterInput", {
        marginTop: unit(12),
        $nest: {
            [`& .${checkRadioClasses().root}`]: {
                paddingLeft: 0,
            },
        },
    });

    return { inputWrap, checkIcon, checkBoxAfterInput };
});
