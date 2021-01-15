/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { allButtonStates, flexHelper, spinnerLoaderAnimationProperties } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { CSSObject } from "@emotion/css";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent } from "csx";
import merge from "lodash/merge";
import generateButtonClass from "./styleHelperButtonGenerator";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { buttonResetMixin } from "./buttonMixins";
import { buttonVariables, buttonGlobalVariables } from "./Button.variables";

export const overwriteButtonClass = (
    buttonTypeVars: IButtonType,
    overwriteVars: IButtonType,
    setZIndexOnState = false,
) => {
    const buttonVars = merge(buttonTypeVars, overwriteVars);
    // append names for debugging purposes
    buttonVars.name = `${buttonTypeVars.name}-${overwriteVars.name}`;
    return generateButtonClass(buttonVars, {
        setZIndexOnState,
    });
};

export const buttonClasses = useThemeCache(() => {
    const vars = buttonVariables();
    return {
        primary: generateButtonClass(vars.primary),
        standard: generateButtonClass(vars.standard),
        transparent: generateButtonClass(vars.transparent),
        translucid: generateButtonClass(vars.translucid),
        icon: buttonUtilityClasses().buttonIcon,
        iconCompact: buttonUtilityClasses().buttonIconCompact,
        text: buttonUtilityClasses().buttonAsText,
        textPrimary: buttonUtilityClasses().buttonAsTextPrimary,
        radio: generateButtonClass(vars.radio),
        custom: "",
        notStandard: generateButtonClass(vars.notStandard),
    };
});

export const buttonUtilityClasses = useThemeCache(() => {
    const vars = buttonGlobalVariables();
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const style = styleFactory("buttonUtils");
    const mediaQueries = layoutVariables().mediaQueries();

    const pushLeft = style("pushLeft", {
        marginRight: important("auto"),
    });

    const pushRight = style("pushRight", {
        marginLeft: important("auto"),
    });

    const iconMixin = (dimension: number): CSSObject => ({
        ...buttonResetMixin(),
        alignItems: "center",
        display: "flex",
        height: styleUnit(dimension),
        minWidth: styleUnit(dimension),
        width: styleUnit(dimension),
        justifyContent: "center",
        border: "none",
        padding: 0,
        background: "transparent",
        ...allButtonStates({
            allStates: {
                color: ColorsUtils.colorOut(globalVars.mainColors.secondary),
            },
            hover: {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            clickFocus: {
                outline: 0,
            },
            keyboardFocus: {
                outline: "initial",
            },
        }),
        color: "inherit",
    });

    const buttonIcon = style(
        "buttonIcon",
        iconMixin(formElementVars.sizing.height),
        mediaQueries.oneColumnDown({
            height: vars.sizing.compactHeight,
            width: vars.sizing.compactHeight,
            minWidth: vars.sizing.compactHeight,
        }),
    );

    const buttonIconCompact = style("iconCompact", iconMixin(vars.sizing.compactHeight));

    const asTextStyles: CSSObject = {
        ...buttonResetMixin(),
        minWidth: important(0),
        padding: 0,
        overflow: "hidden",
        textAlign: "left",
        lineHeight: globalVars.lineHeights.base,
        fontWeight: globalVars.fonts.weights.semiBold,
        whiteSpace: "nowrap",
    };

    const buttonAsText = style("asText", asTextStyles, {
        color: "inherit",
        ...{
            "&:not(.focus-visible)": {
                outline: 0,
            },
            "&:focus, &:active, &:hover": {
                color: ColorsUtils.colorOut(globalVars.mainColors.secondary),
            },
        },
    });

    const buttonAsTextPrimary = style("asTextPrimary", asTextStyles, {
        ...{
            "&&": {
                color: ColorsUtils.colorOut(globalVars.links.colors.default),
            },
            "&&:not(.focus-visible)": {
                outline: 0,
            },
            "&&:hover, &&:focus, &&:active": {
                color: ColorsUtils.colorOut(globalVars.links.colors.active),
            },
        },
    });

    const buttonIconRightMargin = style("buttonIconRightMargin", {
        marginRight: styleUnit(6),
    });

    const buttonIconLeftMargin = style("buttonIconLeftMargin", {
        marginLeft: styleUnit(6),
    });

    const reset = style("reset", buttonResetMixin());

    return {
        pushLeft,
        buttonAsText,
        buttonAsTextPrimary,
        pushRight,
        iconMixin,
        buttonIconCompact,
        buttonIcon,
        buttonIconRightMargin,
        buttonIconLeftMargin,
        reset,
    };
});

export const buttonLoaderClasses = useThemeCache(() => {
    const flexUtils = flexHelper();
    const style = styleFactory("buttonLoader");

    const root = useThemeCache((alignment: "left" | "center" = "center") =>
        style({
            ...(alignment === "center" ? flexUtils.middle() : flexUtils.middleLeft),
            padding: styleUnit(4),
            height: percent(100),
            width: percent(100),
            ...{
                [`& + .suggestedTextInput-parentTag`]: {
                    display: "none",
                },
            },
        }),
    );

    const reducedPadding = style("reducedPadding", {
        ...{
            "&&": {
                padding: styleUnit(3),
            },
        },
    });

    const svg = style("svg", spinnerLoaderAnimationProperties());
    return { root, svg, reducedPadding };
});

export const buttonLabelWrapClass = useThemeCache(() => {
    const style = styleFactory("buttonLabelWrap");
    const root = style({
        maxWidth: percent(100),
        textOverflow: "ellipsis",
        overflow: "hidden",
    });

    return {
        root,
    };
});
