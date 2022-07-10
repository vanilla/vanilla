/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { flexHelper, pointerEvents } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
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
    const rightSpacing = 6;

    const inputWrap = style("inputWrap", {
        ...{
            "&.hasFocus .inputBlock-inputText": {
                ...Mixins.border({
                    ...globalVars.borderType.formElements.default,
                    color: globalVars.mainColors.primary,
                }),
            },
            ".inputBlock-inputText": {
                paddingRight: styleUnit(vars.padding.right),
                paddingLeft: styleUnit(vars.padding.left),
                position: "relative",
            },
            ".SelectOne__indicators": {
                position: "absolute",
                top: 0,
                right: styleUnit(rightSpacing),
                bottom: 0,
                display: "flex",
                justifyContent: "flex-end",
                alignItems: "center",
                width: styleUnit(inputVariables().sizing.height),
            },
            ".SelectOne__indicator-separator": {
                display: "none",
            },
            ".SelectOne__indicator": {
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                width: styleUnit(inputVariables().sizing.height / 2),
                cursor: "pointer",
            },
            ".suggestedTextInput-head": {
                ...flexHelper().middleLeft(),
                justifyContent: "space-between",
            },
            ".SelectOne__single-value": {
                textOverflow: "ellipsis",
                maxWidth: calc(`100% - ${styleUnit(vars.padding.right + singleValueOffset)}`),
            },
            ".SelectOne__single-value + div": {
                textOverflow: "ellipsis",
                maxWidth: calc(`100% - ${styleUnit(vars.padding.right)}`),
            },
            ".SelectOne__value-container.inputText.inputText": {
                paddingRight: styleUnit(inputVariables().sizing.height + rightSpacing),
                ...pointerEvents(), // sometimes this element blocks the click to focus the input.
            },
            ".SelectOne__value-container > *": {
                width: percent(100),
                overflow: "hidden",
                lineHeight: "inherit",
            },
            ".SelectOne--is-disabled": {
                cursor: "pointer",
                opacity: 0.5,
            },
            ".SelectOne__menu-notice--no-options": {
                padding: 10,
                overflow: "hidden",
            },
            ".suggestedTextInput-option": {
                width: "100%",
                textAlign: "left",
                ...Mixins.padding({
                    vertical: 6,
                    horizontal: 12,
                }),
            },
            ".suggestedTextInput-option:hover": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
            },
            ".icon-selectedCategory": {
                ...Mixins.margin({
                    horizontal: 4,
                }),
            },
            ".inputBlock": inputMixin(),
        },
    });

    const checkIcon = style("checkIcon", {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
    });

    const checkBoxAfterInput = style("checkBoxAfterInput", {
        marginTop: styleUnit(12),
        ...{
            [`.${checkRadioClasses().root}`]: {
                paddingLeft: 0,
            },
        },
    });

    const chevron = style("chevron", {
        marginRight: 8,
    });

    return { inputWrap, checkIcon, checkBoxAfterInput, chevron };
});
