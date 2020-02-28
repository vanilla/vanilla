/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, translateX } from "csx";
import { borders, colorOut, negativeUnit, textInputSizingFromFixedHeight, unit } from "@library/styles/styleHelpers";
import { themeBuilderClasses, themeBuilderVariables } from "@library/forms/themeEditor/themeBuilderStyles";
import { IGlobalBorderStyles } from "@library/styles/globalStyleVars";

export const colorPickerVariables = useThemeCache(() => {
    // Intentionally not overwritable with theming system.
    return {
        sizing: {
            height: themeBuilderVariables().input.height,
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
    const builderClasses = themeBuilderClasses();
    const inputWidth = builderVariables.input.width - vars.swatch.width;

    const root = style({
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "stretch",
    });

    const textInput = style("textInput", {
        position: "relative",
        ...textInputSizingFromFixedHeight(vars.sizing.height, builderVariables.input.fonts.size, 2, vars.sizing.height),
        width: unit(inputWidth),
        color: colorOut(builderVariables.defaultFont.color),
        flexBasis: unit(inputWidth),
        borderTopLeftRadius: unit(builderVariables.wrap.borderRadius),
        borderBottomLeftRadius: unit(builderVariables.wrap.borderRadius),
        ...borders({}, builderVariables.border as IGlobalBorderStyles),
        zIndex: 1,
        transition: `color .2s ease-out, background .2s ease-out`,
        $nest: {
            [`&.${builderClasses.invalidField}`]: {
                color: colorOut(builderVariables.error.color),
                background: colorOut(builderVariables.error.backgroundColor),
            },
        },
    });

    const swatch = style("swatch", {
        display: "block",
        position: "relative",
        width: unit(vars.swatch.width),
        flexBasis: unit(vars.swatch.width),
        height: percent(100),
        ...borders(
            {
                top: builderVariables.border as IGlobalBorderStyles,
                right: builderVariables.border as IGlobalBorderStyles,
                bottom: builderVariables.border as IGlobalBorderStyles,
            },
            {} as IGlobalBorderStyles,
        ),
        borderTopRightRadius: unit(builderVariables.wrap.borderRadius),
        borderBottomRightRadius: unit(builderVariables.wrap.borderRadius),
        $nest: {
            "&:focus, &:active": {
                zIndex: 2,
            },
        },
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
    };
});
