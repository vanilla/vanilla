/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";
import { flexHelper } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IGlobalBorderStyles } from "@library/styles/cssUtilsTypes";

export const themeDropDownClasses = useThemeCache(() => {
    const style = styleFactory("themeDropDown");
    const builderVariables = themeBuilderVariables();
    const globalVars = globalVariables();

    const root = style({
        width: percent(100),
        background: ColorsUtils.colorOut(globalVars.mainColors.bg),
        ...{
            ".input-wrap-right": {
                width: percent(100),
            },
            ".SelectOne__menu": {
                ...Mixins.border(
                    {
                        ...(builderVariables.border as IGlobalBorderStyles),
                        radius: builderVariables.wrap.borderRadius,
                    },
                    {},
                ),
            },
            "&&& .hasFocus .inputBlock-inputText": {
                borderRadius: builderVariables.wrap.borderRadius,
                borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
                cursor: "pointer",
            },
            ".suggestedTextInput-option": {
                width: percent(100),
                textAlign: "left",
                minHeight: styleUnit(builderVariables.input.height),
                paddingTop: 0,
                paddingBottom: 0,
            },
            ".suggestedTextInput-head": {
                ...flexHelper().middleLeft(),
                justifyContent: "space-between",
            },
            ".suggestedTextInput-valueContainer.suggestedTextInput-valueContainer": {
                minHeight: styleUnit(builderVariables.input.height),
                paddingTop: 0,
                paddingBottom: 0,
                backgroundColor: ColorsUtils.colorOut(globalVars.elementaryColors.white),
                fontSize: "inherit",
                ...Mixins.border(
                    {
                        ...(builderVariables.border as IGlobalBorderStyles),
                        radius: builderVariables.wrap.borderRadius,
                    },
                    {},
                ),
                ...{
                    "&:hover, &:focus, &:active, &.focus-visible": {
                        borderRadius: builderVariables.wrap.borderRadius,
                        borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
                        cursor: "pointer",
                    },
                },
            },
            ".SelectOne__indicators": {
                height: styleUnit(builderVariables.input.height),
                width: styleUnit(builderVariables.input.height),
            },
            ".SelectOne__single-value.SelectOne__single-value": {
                ...Mixins.font(builderVariables.input.fonts),
            },
        },
    });

    return {
        root,
    };
});
