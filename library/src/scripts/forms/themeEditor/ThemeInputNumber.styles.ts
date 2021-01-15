/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, percent, translateX, translateY } from "csx";
import {
    absolutePosition,
    negativeUnit,
    textInputSizingFromFixedHeight,
    userSelect,
} from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { themeBuilderClasses, themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { IGlobalBorderStyles } from "@library/styles/cssUtilsTypes";

export const themeInputNumberVariables = useThemeCache(() => {
    // Intentionally not overwritable with theming system.
    const builderVars = themeBuilderVariables();
    return {
        label: builderVars.label,
        sizing: {
            height: builderVars.input.height,
        },
        input: {
            font: {
                size: builderVars.label.fonts.size,
            },
            width: 80,
        },
        spinner: {
            width: 18,
            fonts: Variables.font({
                ...builderVars.defaultFont,
                size: 12,
            }),
        },
    };
});

export const themeInputNumberClasses = useThemeCache(() => {
    const vars = themeInputNumberVariables();
    const style = styleFactory("themeInputNumber");
    const builderVariables = themeBuilderVariables();
    const builderClasses = themeBuilderClasses();

    const spinner = style("spinner", {
        position: "relative",
        display: "flex",
        flexDirection: "column",
        justifyContent: "stretch",
        alignItems: "stretch",
        height: styleUnit(vars.sizing.height),
        width: styleUnit(vars.spinner.width + builderVariables.border.width),
        flexBasis: styleUnit(vars.spinner.width + builderVariables.border.width),
        transform: translateX(negativeUnit(builderVariables.border.width)),
    });

    const root = style({
        display: "flex",
        flexWrap: "nowrap",
        justifyContent: "stretch",
        alignItems: "stretch",
        maxWidth: styleUnit(vars.input.width),
        width: styleUnit(vars.input.width),
        position: "relative",
    });

    const textInput = style("textInput", {
        position: "relative",
        ...textInputSizingFromFixedHeight(
            vars.sizing.height,
            vars.label.fonts.size as number,
            builderVariables.border.width * 2, // 2
        ),
        height: styleUnit(vars.sizing.height),
        width: styleUnit(vars.input.width - vars.spinner.width),
        maxWidth: styleUnit(vars.input.width - vars.spinner.width),
        color: ColorsUtils.colorOut(builderVariables.defaultFont.color),
        flexBasis: styleUnit(builderVariables.input.width),
        ...Mixins.border({}, { fallbackBorderVariables: builderVariables.border as IGlobalBorderStyles }),
        borderRight: 0,
        borderTopRightRadius: 0,
        borderBottomRightRadius: 0,
        borderTopLeftRadius: styleUnit(builderVariables.wrap.borderRadius),
        borderBottomLeftRadius: styleUnit(builderVariables.wrap.borderRadius),
        transition: `color .2s ease-out, background .2s ease-out`,
        ...{
            ":not(.focus-visible)": {
                outline: 0,
            },
            "&:hover, &:focus, &:active, &.focus-visible": {
                zIndex: 1,
            },
            [`&.${builderClasses.invalidField}`]: {
                color: ColorsUtils.colorOut(builderVariables.error.color),
                background: ColorsUtils.colorOut(builderVariables.error.backgroundColor),
            },
        },
    });

    const stepUp = style("stepUp", {
        ...absolutePosition.topRight(),
        ...Mixins.border({}, { fallbackBorderVariables: builderVariables.border as IGlobalBorderStyles }),
        ...userSelect(),
        borderTopLeftRadius: 0,
        borderBottomLeftRadius: 0,
        borderBottomRightRadius: 0,
        borderTopRightRadius: styleUnit(builderVariables.wrap.borderRadius),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: 0,
        height: styleUnit(Math.ceil(vars.sizing.height / 2)),
        width: percent(100),
        ...Mixins.font(vars.spinner.fonts),
        ...{
            "&:hover, &:focus, &:active, &.focus-visible": {
                zIndex: 1,
            },
        },
    });
    const stepDown = style("stepDown", {
        ...absolutePosition.bottomRight(),
        ...Mixins.border({}, { fallbackBorderVariables: builderVariables.border }),
        ...userSelect(),
        borderTopLeftRadius: 0,
        borderBottomLeftRadius: 0,
        borderTopRightRadius: 0,
        borderBottomRightRadius: styleUnit(builderVariables.wrap.borderRadius),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: 0,
        ...Mixins.font(vars.spinner.fonts),
        height: styleUnit(Math.ceil(vars.sizing.height / 2)),
        width: percent(100),
        ...{
            "&:hover": {
                zIndex: 1,
            },
            "&:focus, &:active, &.focus-accessible": {
                zIndex: 2,
            },
        },
    });

    const spinnerSpacer = style("spinnerSpacer", {
        display: "block",
        position: "relative",
        height: styleUnit(vars.sizing.height),
        minHeight: styleUnit(vars.sizing.height),
        width: styleUnit(vars.spinner.width + builderVariables.border.width),
        flexBasis: styleUnit(vars.spinner.width + builderVariables.border.width),
    });

    const inputWrap = style("inputWrap", {
        width: styleUnit(vars.input.width),
        flexBasis: styleUnit(vars.input.width),
    });

    return {
        root,
        textInput,
        spinner,
        stepUp,
        stepDown,
        spinnerSpacer,
        inputWrap,
    };
});
