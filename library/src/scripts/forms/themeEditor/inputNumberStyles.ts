/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, translateX, translateY } from "csx";
import {
    borders,
    colorOut,
    fonts,
    negativeUnit,
    textInputSizingFromFixedHeight,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { themeBuilderVariables } from "@library/forms/themeEditor/themeBuilderStyles";
import { IGlobalBorderStyles } from "@library/styles/globalStyleVars";

export const inputNumberVariables = useThemeCache(() => {
    // Intentionally not overwritable with theming system.
    const builderVars = themeBuilderVariables();
    return {
        sizing: {
            height: builderVars.input.height,
        },
        spinner: {
            width: 18,
            fonts: {
                ...builderVars.defaultFont,
                size: 12,
            },
        },
    };
});

export const inputNumberClasses = useThemeCache(() => {
    const vars = inputNumberVariables();
    const style = styleFactory("numberInput");
    const builderVariables = themeBuilderVariables();

    const root = style({
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "stretch",
    });

    const textInput = style("textInput", {
        position: "relative",
        ...textInputSizingFromFixedHeight(vars.sizing.height, vars.sizing.height, 2, vars.sizing.height),
        width: unit(builderVariables.input.width),
        color: colorOut(builderVariables.defaultFont.color),
        flexBasis: unit(builderVariables.input.width),
        ...borders({}, builderVariables.border as IGlobalBorderStyles),
        borderRight: 0,
        borderTopLeftRadius: unit(builderVariables.wrap.borderRadius),
        borderBottomLeftRadius: unit(builderVariables.wrap.borderRadius),
        $nest: {
            "&:hover, &:focus, &:active, &.focus-visible": {
                zIndex: 1,
            },
        },
    });

    const spinner = style("spinner", {
        display: "flex",
        flexDirection: "column",
        justifyContent: "stretch",
        height: calc(`100% + ${unit(builderVariables.border.width)}`), // To offset the margin top on "stepDown"
        width: unit(vars.spinner.width + builderVariables.border.width),
        transform: translateX(negativeUnit(builderVariables.border.width)),
    });
    const stepUp = style("stepUp", {
        ...borders({}, builderVariables.border as IGlobalBorderStyles),
        ...userSelect(),
        borderTopRightRadius: unit(builderVariables.wrap.borderRadius),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: 0,
        ...fonts(vars.spinner.fonts),
        $nest: {
            "&:hover, &:focus, &:active, &.focus-visible": {
                zIndex: 1,
            },
        },
    });
    const stepDown = style("stepDown", {
        ...borders({}, builderVariables.border as IGlobalBorderStyles),
        ...userSelect(),
        borderBottomRightRadius: unit(builderVariables.wrap.borderRadius),
        transform: translateY(negativeUnit(builderVariables.border.width)),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: 0,
        ...fonts(vars.spinner.fonts),
        $nest: {
            "&:hover, &:focus, &:active, &.focus-visible": {
                zIndex: 1,
            },
        },
        width: unit(vars.spinner.width),
    });

    return {
        root,
        textInput,
        spinner,
        stepUp,
        stepDown,
    };
});
