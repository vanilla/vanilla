/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent, translateX } from "csx";
import { negativeUnit, textInputSizingFromFixedHeight } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { themeBuilderClasses, themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { IGlobalBorderStyles } from "@library/styles/cssUtilsTypes";

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
        ...textInputSizingFromFixedHeight(vars.sizing.height, builderVariables.input.fonts.size as number, 2, 0),
        width: styleUnit(inputWidth),
        maxWidth: styleUnit(inputWidth), // Needed for Firefox.
        color: ColorsUtils.colorOut(builderVariables.defaultFont.color),
        flexBasis: styleUnit(inputWidth),
        ...Mixins.border(builderVariables.border),
        borderTopRightRadius: 0,
        borderBottomRightRadius: 0,
        zIndex: 1,
        transition: `color .2s ease-out, background .2s ease-out`,
        ...{
            [`&.${builderClasses.invalidField}`]: {
                color: ColorsUtils.colorOut(builderVariables.error.color),
            },
        },
    });

    const swatch = style("swatch", {
        display: "block",
        position: "relative",
        width: styleUnit(vars.swatch.width),
        flexBasis: styleUnit(vars.swatch.width),
        height: percent(100),
        borderTopLeftRadius: 0,
        borderBottomLeftRadius: 0,
        borderTopRightRadius: styleUnit(builderVariables.wrap.borderRadius),
        borderBottomRightRadius: styleUnit(builderVariables.wrap.borderRadius),
        ...{
            "&:focus, &:active": {
                zIndex: 2,
            },
        },
    });

    const realInput = style("realInput", {
        position: "absolute",
        outline: 0,
        transform: translateX(negativeUnit(10)),
        ...{
            [`&:focus + .${textInput}`]: {
                borderColor: ColorsUtils.colorOut(builderVariables.outline.color),
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
