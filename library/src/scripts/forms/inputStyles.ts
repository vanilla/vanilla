/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { placeholderStyles, textInputSizingFromFixedHeight } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { important, percent } from "csx";
import merge from "lodash-es/merge";
import { css } from "@emotion/css";
import { CSSObject } from "@emotion/css/types/create-instance";
import { IBorderStyles, ISimpleBorderStyle, IMixedBorderStyles } from "@library/styles/cssUtilsTypes";

export const inputVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const formElementVars = formElementsVariables(forcedVars);
    const makeThemeVars = variableFactory("input", forcedVars);

    const colors = makeThemeVars("colors", {
        placeholder: globalVars.mixBgAndFg(0.5),
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        state: {
            fg: globalVars.mainColors.primary,
        },
    });

    const sizing = makeThemeVars("sizing", {
        height: formElementVars.sizing.height,
    });

    const font = makeThemeVars(
        "font",
        Variables.font({
            ...globalVars.fontSizeAndWeightVars("large", "normal"),
            color: colors.fg,
        }),
    );

    const border = makeThemeVars("borders", { ...globalVars.borderType.formElements.default });

    return {
        colors,
        border,
        sizing,
        font,
    };
});

const inputMixinVars = (vars?: {
    sizing?: any;
    font?: any;
    colors?: any;
    border?: IBorderStyles | ISimpleBorderStyle | IMixedBorderStyles;
}) => {
    const inputVars = inputVariables();
    return {
        sizing: merge({ ...inputVars.sizing }, vars?.sizing ?? {}),
        font: Variables.font(merge({ ...inputVars.font }, vars?.font ?? {})),
        colors: merge({ ...inputVars.colors }, vars?.colors ?? {}),
        border: merge({ ...inputVars.border }, vars?.border ?? {}),
    };
};

export const inputMixin = (vars?: {
    sizing?: any;
    font?: any;
    colors?: any;
    border?: IBorderStyles | ISimpleBorderStyle | IMixedBorderStyles;
}): CSSObject => {
    const variables = inputMixinVars(vars);
    const globalVars = globalVariables();
    const { sizing, font, colors, border } = variables;

    return {
        ...textInputSizingFromFixedHeight(sizing.height, font.size as number, border.width * 2),
        backgroundColor: ColorsUtils.colorOut(colors.bg),
        color: ColorsUtils.colorOut(colors.fg),
        ...Mixins.border(border),
        ...Mixins.font(font),
        outline: 0,
        height: "auto",
        ...placeholderStyles({
            color: ColorsUtils.colorOut(colors.placeholder),
        }),
        ".SelectOne__input": {
            width: percent(100),
        },
        ".SelectOne__placeholder": {
            color: ColorsUtils.colorOut(formElementsVariables().placeholder.color),
        },
        ".tokens__placeholder": {
            color: ColorsUtils.colorOut(formElementsVariables().placeholder.color),
        },
        ".SelectOne__input input": {
            display: "inline-block",
            width: important(`100%`),
            overflow: "hidden",
            lineHeight: undefined,
            minHeight: 0,
        },
        "&:not(:disabled)": {
            "&:active, &:hover, &:focus, &.focus-visible, &:focus-within": {
                ...Mixins.border({
                    ...border,
                    color: colors.state.fg,
                }),
            },
        },
        "&.hasError": {
            borderColor: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
            backgroundColor: ColorsUtils.colorOut(globalVars.messageColors.error.bg),
            color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
        },
        "@media (max-width: 600px)": {
            // To prevent needing to zoom in safari.
            fontSize: globalVars.fonts.size.large,
        },
    };
};

export const inputClasses = useThemeCache(() => {
    const vars = inputVariables();
    const variables = inputMixinVars(vars);
    const style = styleFactory("input");
    const formElementVars = formElementsVariables();
    const globalVars = globalVariables();
    const { colors, border } = variables;

    const inputPaddingMixin: CSSObject = {
        padding: inputMixin().padding,
        paddingTop: inputMixin().paddingTop,
        paddingBottom: inputMixin().paddingBottom,
        paddingLeft: inputMixin().paddingLeft,
        paddingRight: inputMixin().paddingRight,
    };

    // Use as assignable unique style.
    const text = css({
        ...inputMixin(),
    });

    // Use as a global selector. This should be refactored in the future.
    const applyInputCSSRules = () => cssOut(" .inputText.inputText", inputMixin());

    const inputText = style("inputText", {
        ...inputMixin(),
        marginBottom: 0,
        ...{
            "&&": {
                marginTop: styleUnit(globalVars.gutter.quarter),
            },
        },
    });

    const inputWrapper = style("inputWrapper", {
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
    });

    const inputContainer = style("inputContainer", {
        flex: 1,
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        ...inputMixin(),
        ...Mixins.padding({ all: 0 }),
        "& input": {
            ...inputMixin({ border: { style: "none" } }),
            ...inputPaddingMixin,
            flex: 1,
            background: "none",
        },
    });

    const errorIcon = style("invalidIcon", {
        color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
        minWidth: globalVars.icon.sizes.large,
    });

    const hugRight = css({
        marginRight: -6,
    });

    return {
        text,
        inputText,
        inputPaddingMixin,
        applyInputCSSRules,
        inputWrapper,
        inputContainer,
        errorIcon,
        hugRight,
    };
});
