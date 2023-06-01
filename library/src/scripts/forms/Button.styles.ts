/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { allButtonStates, flexHelper, spinnerLoaderAnimationProperties } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { CSSObject, css } from "@emotion/css";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent } from "csx";
import generateButtonClass from "./styleHelperButtonGenerator";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { buttonResetMixin } from "./buttonMixins";
import { buttonVariables, buttonGlobalVariables } from "./Button.variables";

export const buttonClasses = useThemeCache(() => {
    const vars = buttonVariables();
    return {
        primary: generateButtonClass(vars.primary),
        standard: generateButtonClass(vars.standard),
        outline: generateButtonClass(vars.outline),
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
    const mediaQueries = oneColumnVariables().mediaQueries();

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
        justifyContent: "center",
        border: "none",
        padding: 0,
        background: "transparent",
        color: "inherit",
        borderRadius: 3,
        "&:disabled, &[aria-disabled='true']": {
            opacity: 0.5,
            cursor: "not-allowed",
        },
        "&:not(:disabled):not([aria-disabled='true'])": {
            "&:hover, &:focus, &.hover, &:focus-visible, &.focus-visible": {
                background: ColorsUtils.colorOut(globalVars.mainColors.primary.fade(0.1)),
            },
        },
        "&.active": {
            background: ColorsUtils.colorOut(globalVars.mainColors.primary.fade(0.1)),
            color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        },
        "&&.focus-visible, &:focus-visible": {
            outline: "none",
            boxShadow: `0 0 0 1px ${globalVars.mainColors.primary}`,
        },
    });

    const buttonIcon = style(
        "buttonIcon",
        iconMixin(formElementVars.sizing.height),
        mediaQueries.oneColumnDown({
            minWidth: vars.sizing.compactHeight,
        }),
    );

    const buttonIconMenuBar = css({ ...iconMixin(formElementVars.sizing.height - 4), whiteSpace: "nowrap" });

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

    const buttonAsTextPrimary = css(asTextStyles, {
        "&&": {
            color: ColorsUtils.colorOut(globalVars.links.colors.default),
        },
        "&&:not(.focus-visible)": {
            outline: 0,
        },
        "&&:hover, &&:focus, &&:active": {
            color: ColorsUtils.colorOut(globalVars.links.colors.active),
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
        buttonIconMenuBar,
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
