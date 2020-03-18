/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { percent } from "csx";
import { borders, colorOut, fonts, unit } from "@library/styles/styleHelpers";
import { themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { globalVariables, IGlobalBorderStyles } from "@library/styles/globalStyleVars";

export const themeDropDownClasses = useThemeCache(() => {
    const style = styleFactory("themeDropDown");
    const builderVariables = themeBuilderVariables();
    const globalVars = globalVariables();

    const root = style({
        width: percent(100),
        background: colorOut(globalVars.mainColors.bg),
        $nest: {
            "& .input-wrap-right": {
                width: percent(100),
            },
            "& .SelectOne__menu": {
                ...borders(
                    {
                        ...(builderVariables.border as IGlobalBorderStyles),
                        radius: builderVariables.wrap.borderRadius,
                    },
                    {} as IGlobalBorderStyles,
                ),
            },
            "&&& .hasFocus .inputBlock-inputText": {
                borderRadius: builderVariables.wrap.borderRadius,
                borderColor: colorOut(globalVars.mainColors.primary),
                cursor: "pointer",
            },
            "& .suggestedTextInput-option": {
                minHeight: unit(builderVariables.input.height),
                paddingTop: 0,
                paddingBottom: 0,
            },
            "& .suggestedTextInput-valueContainer": {
                minHeight: unit(builderVariables.input.height),
                paddingTop: 0,
                paddingBottom: 0,
                backgroundColor: colorOut(globalVars.elementaryColors.white),
                fontSize: "inherit",
                ...borders(
                    {
                        ...(builderVariables.border as IGlobalBorderStyles),
                        radius: builderVariables.wrap.borderRadius,
                    },
                    {} as IGlobalBorderStyles,
                ),
                $nest: {
                    "&:hover, &:focus, &:active, &.focus-visible": {
                        borderRadius: builderVariables.wrap.borderRadius,
                        borderColor: colorOut(globalVars.mainColors.primary),
                        cursor: "pointer",
                    },
                },
            },
            "& .SelectOne__indicators": {
                height: unit(builderVariables.input.height),
            },
            "& .SelectOne__single-value.SelectOne__single-value": {
                ...fonts(builderVariables.input.fonts),
            },
        },
    });

    return {
        root,
    };
});
