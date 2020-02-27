/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, translateX } from "csx";
import { borders, colorOut, negativeUnit, textInputSizingFromFixedHeight, unit } from "@library/styles/styleHelpers";
import { themeBuilderVariables } from "@library/forms/themeEditor/themeBuilderStyles";
import { globalVariables, IGlobalBorderStyles } from "@library/styles/globalStyleVars";

export const colorPickerVariables = useThemeCache(() => {
    // Intentionally not overwritable with theming system.
    return {
        sizing: {
            height: themeBuilderVariables().input.height,
        },
    };
});

export const numberInputClasses = useThemeCache(() => {
    const vars = colorPickerVariables();
    const style = styleFactory("colorPicker");
    const builderVariables = themeBuilderVariables();
    const inputWidth = builderVariables.width;

    const root = style({
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "stretch",
    });

    const textInput = style("textInput", {
        position: "relative",
        ...textInputSizingFromFixedHeight(vars.sizing.height, builderVariables.label.size, 2, vars.sizing.height),
        width: unit(inputWidth),
        color: colorOut(builderVariables.font.color),
        flexBasis: unit(inputWidth),
        borderTopLeftRadius: unit(builderVariables.wrap.borderRadius),
        borderBottomLeftRadius: unit(builderVariables.wrap.borderRadius),
        ...borders({}, builderVariables.border as IGlobalBorderStyles),
    });

    return {
        root,
        textInput,
    };
});
