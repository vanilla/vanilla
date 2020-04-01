/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { borders, colorOut, unit } from "@library/styles/styleHelpers";
import { calc, percent } from "csx";
import { inputMixin } from "@library/forms/inputStyles";

export const selectOneVariables = useThemeCache(() => {
    const vars = variableFactory("selectOne");

    const padding = vars("padding", {
        right: 30,
        left: inputMixin().paddingRight,
    });

    return { padding };
});

export const selectOneClasses = useThemeCache(() => {
    const style = styleFactory("selectOne");
    const vars = selectOneVariables();
    const globalVars = globalVariables();

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
                maxWidth: calc(`100% - ${unit(vars.padding.right + 26)}`),
            },
            "& .SelectOne__value-container > *": {
                width: percent(100),
            },
            "& .SelectOne--is-disabled": {
                cursor: "pointer",
                opacity: 0.5,
            },
        },
    });

    const checkIcon = style("checkIcon", {
        color: colorOut(globalVars.mainColors.primary),
    });

    return { inputWrap, checkIcon };
});
