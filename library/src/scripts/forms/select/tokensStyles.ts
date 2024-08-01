/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { componentThemeVariables, styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent, px } from "csx";
import { metasVariables } from "@library/metas/Metas.variables";
import { css } from "@emotion/css";
import { inputMixin } from "@library/forms/inputStyles";

export const tokensVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();
    const themeVars = componentThemeVariables("tokens");

    const token = {
        fontSize: metasVars.font.size,
        bg: globalVars.mixBgAndFg(0.1),
        textShadow: `${globalVars.mainColors.bg} 0 0 1px`,
        minHeight: 26,
    };

    const clearIcon = {
        width: 8,
        ...themeVars.subComponentStyles("clearIcon"),
    };

    return {
        clearIcon,
        token,
    };
});

export const tokensClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = tokensVariables();
    const formElVars = formElementsVariables();
    const style = styleFactory("tokens");

    const root = style({
        ...{
            ".tokens__value-container": {
                display: "flex",
                flexWrap: "wrap",
                alignItems: "center",
                justifyContent: "flexStart",
                minHeight: styleUnit(formElVars.sizing.height),
                paddingTop: 0,
                paddingRight: px(12),
                paddingBottom: 0,
                paddingLeft: px(12),
                ...Mixins.border(globalVars.borderType.formElements.default),
                ...{
                    "&.tokens__value-container--has-value": {
                        ...Mixins.padding({
                            horizontal: 4,
                            vertical: 0,
                        }),
                    },
                    ".tokens__multi-value + div:not(.tokens__multi-value)": {
                        display: "flex",
                        flexWrap: "wrap",
                        alignItems: "center",
                        justifyContent: "flexStart",
                        flexGrow: 1,
                    },
                    ".tokens__input": {
                        flexGrow: 1,
                        display: important("inline-flex"),
                        alignItems: "center",
                        justifyContent: "stretch",
                        ...Mixins.margin({
                            vertical: 0,
                        }),
                        minHeight: styleUnit(vars.token.minHeight),
                        "@media(max-width: 600px)": {
                            fontSize: 16,
                        },
                    },
                    input: {
                        width: percent(100),
                        minWidth: styleUnit(45),
                        minHeight: 0,
                    },
                },
            },
            ".tokens__multi-value": {
                display: "flex",
                alignItems: "center",
                flexWrap: "nowrap",
                fontSize: styleUnit(vars.token.fontSize),
                fontWeight: globalVars.fonts.weights.bold,
                textShadow: vars.token.textShadow,
                margin: px((formElVars.sizing.height - vars.token.minHeight) / 2 - formElVars.border.width),
                backgroundColor: ColorsUtils.colorOut(vars.token.bg),
                minHeight: styleUnit(vars.token.minHeight),
                borderRadius: px(2),
                ...userSelect(),
            },
            ".tokens__multi-value__label": {
                paddingLeft: px(6),
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("small", "normal"),
                }),
            },
            ".tokens--is-disabled": {
                opacity: formElVars.disabled.opacity,
            },
            ".tokens-clear": {
                background: 0,
                border: 0,
                height: styleUnit(globalVars.icon.sizes.default),
                width: styleUnit(globalVars.icon.sizes.default),
                padding: 0,
                ...{
                    "&:hover, &:focus": {
                        color: globalVars.mainColors.primary.toString(),
                    },
                },
            },
            ".tokens__group": {
                ...Mixins.padding({ vertical: 8 }),
                ...Mixins.border({
                    width: 0,
                    top: {
                        width: 1,
                        radius: 0,
                    },
                }),
                "&:first-of-type": {
                    border: 0,
                },
                ".tokens__group-heading": {
                    width: "100%",
                    textAlign: "center",
                    fontSize: "0.875em",
                    color: "inherit",
                    opacity: 0.75,
                },
            },
            ".suggestedTextInput-option": {
                ...Mixins.padding({ all: 8 }),
                width: "100%",
                "& > .suggestedTextInput-head": {
                    display: "flex",
                    justifyContent: "space-between",
                },
                "&:hover": {
                    background: globalVars.states.hover.highlight.toString(),
                },
            },
        },
    });

    const inputWrap = style("inputWrap", {
        ...{
            "&.hasFocus .inputBlock-inputText": {
                ...Mixins.border({
                    ...globalVars.borderType.formElements.default,
                    color: globalVars.mainColors.primary,
                }),
            },
        },
    });

    const removeIcon = style("removeIcon", {
        ...{
            "&.icon": {
                width: styleUnit(vars.clearIcon.width),
                height: styleUnit(vars.clearIcon.width),
            },
        },
    });

    const withIndicator = style("withIndicator", {});

    const containerLegacyForm = css({
        "& label > span": {
            fontWeight: 700,
            marginBottom: 0,
        },
        "& label > p": {
            color: "#666",
            opacity: "unset",
            fontSize: "80%",
            marginTop: 3,
            marginBottom: -4,
        },
    });

    return { root, removeIcon, inputWrap, withIndicator, containerLegacyForm };
});
