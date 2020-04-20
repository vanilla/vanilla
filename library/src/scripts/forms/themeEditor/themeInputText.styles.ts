/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { inputMixin } from "@library/forms/inputStyles";
import { colorOut, importantColorOut } from "@library/styles/styleHelpersColors";
import { globalVariables } from "@library/styles/globalStyleVars";

export const themeInputTextClasses = useThemeCache(() => {
    const vars = themeBuilderVariables();
    const style = styleFactory("themeInputText");
    const classesInput = inputBlockClasses();
    const root = style({
        $nest: {
            [`& .${classesInput.inputWrap}`]: {
                margin: 0,
            },
            [`&& .${classesInput.errors}`]: {
                paddingLeft: 0,
                paddingRight: 0,
            },
            [`&&.hasError .${classesInput.inputText}`]: {
                borderColor: importantColorOut(globalVariables().messageColors.error.fg),
                color: colorOut(globalVariables().messageColors.error.fg),
                backgroundColor: colorOut(globalVariables().messageColors.error.bg),
            },
        },
    });
    const input = style("input", {
        $nest: {
            [`&&.${classesInput.inputText}`]: inputMixin({
                sizing: {
                    height: vars.input.height,
                },
                font: vars.input.fonts,
                border: vars.border,
            }),
        },
    });

    return {
        root,
        input,
    };
});
