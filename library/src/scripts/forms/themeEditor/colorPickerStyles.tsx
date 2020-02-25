/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, translateX } from "csx";
import { borders, colorOut, negativeUnit, textInputSizingFromFixedHeight, unit } from "@library/styles/styleHelpers";
import { themeBuilderVariables } from "@library/forms/themeEditor/themeBuilderStyles";
import { IGlobalBorderStyles } from "@library/styles/globalStyleVars";

export const colorPickerVariables = useThemeCache(() => {
    // Intentionally not overwritable with theming system.
    return {
        sizing: {
            height: 28,
        },
        swatch: {
            width: 39,
        },
    };
});

export const colorPickerClasses = useThemeCache(() => {
    const vars = colorPickerVariables();
    const style = styleFactory("colorPicker");
    const builderVariables = themeBuilderVariables();
    const inputWidth = builderVariables.width - vars.swatch.width;

    const root = style({
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "stretch",
    });

    const invalidColor = style("invalidColor", {});
    const textInput = style("textInput", {
        ...textInputSizingFromFixedHeight(vars.sizing.height, builderVariables.label.size, 0, vars.sizing.height),
        width: unit(inputWidth),
        flexBasis: unit(inputWidth),
        borderTopLeftRadius: unit(builderVariables.wrap.borderRadius),
        borderBottomLeftRadius: unit(builderVariables.wrap.borderRadius),
        ...borders({}, builderVariables.border as IGlobalBorderStyles),
        borderRightColor: "transparent",
        $nest: {
            [`&.${invalidColor}`]: {
                // borderRightColor: colorOut(builderVariables.outline.warning),
                // boxShadow: `inset 0 0 0 1px ${colorOut(builderVariables.outline.warning)}`,
                backgroundColor: colorOut(builderVariables.outline.warning),
            },
        },
    });

    const swatch = style("swatch", {
        display: "block",
        width: unit(vars.swatch.width),
        flexBasis: unit(vars.swatch.width),
        height: percent(100),
        border: 0,
        borderTopRightRadius: unit(builderVariables.wrap.borderRadius),
        borderBottomRightRadius: unit(builderVariables.wrap.borderRadius),
    });

    const realInput = style("realInput", {
        position: "absolute",
        outline: 0,
        transform: translateX(negativeUnit(10)),
        $nest: {
            [`&:focus + .${textInput}`]: {
                borderColor: colorOut(builderVariables.outline.color),
            },
        },
    });

    return {
        root,
        textInput,
        swatch,
        realInput,
        invalidColor,
    };
});
