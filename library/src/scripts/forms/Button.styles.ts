/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { flexHelper, spinnerLoaderAnimationProperties } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { CSSObject } from "@emotion/serialize";
import { css } from "@emotion/css";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent } from "csx";
import generateButtonClass from "./styleHelperButtonGenerator";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { buttonResetMixin } from "./buttonMixins";
import { buttonVariables, buttonGlobalVariables } from "./Button.variables";
import { ColorVar } from "@library/styles/CssVar";

export const buttonClasses = useThemeCache(() => {
    const vars = buttonVariables();

    const input = css({
        ...buttonResetMixin(),
        whiteSpace: "nowrap",
        background: ColorsUtils.var(ColorVar.InputBackground),
        padding: "2px 12px",
        border: `1px solid ${ColorsUtils.var(ColorVar.InputBorder)}`,
        borderRadius: 6,
        position: "relative",
        minHeight: 36,
        "&:hover, &:active, &:focus": {
            borderColor: ColorsUtils.var(ColorVar.InputBorderActive),
            outline: "none",
        },
        "&:focus-visible": {
            borderColor: ColorsUtils.var(ColorVar.Primary),
        },
    });

    return {
        primary: generateButtonClass(vars.primary),
        standard: generateButtonClass(vars.standard, {
            extra: {
                backgroundColor: ColorsUtils.varOverride(ColorVar.Background, vars.standard.colors?.bg),
                color: ColorsUtils.varOverride(ColorVar.Foreground, vars.standard.colors?.fg),
                borderColor: ColorsUtils.varOverride(ColorVar.Border, vars.standard.borders?.color),
            },
        }),
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
        input,
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
        textAlign: "start",
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
                color: ColorsUtils.varOverride(ColorVar.Link, globalVars.links.colors.active),
            },
        },
    });

    const buttonAsTextPrimary = css(asTextStyles, {
        "&&": {
            color: ColorsUtils.varOverride(ColorVar.Link, globalVars.links.colors.default),
        },
        "&&:not(.focus-visible)": {
            outline: 0,
        },
        "&&:hover, &&:focus, &&:active": {
            color: ColorsUtils.varOverride(ColorVar.LinkActive, globalVars.links.colors.active),
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

    const root = useThemeCache((alignment: "left" | "center" = "center") =>
        css({
            ...(alignment === "center" ? flexUtils.middle() : flexUtils.middleLeft),
            paddingLeft: 4,
            paddingRight: 4,
            height: percent(100),
            width: percent(100),
            ...{
                [`& + .suggestedTextInput-parentTag`]: {
                    display: "none",
                },
            },
        }),
    );

    const reducedPadding = css({
        "&&": {
            paddingLeft: 3,
            paddingRight: 3,
        },
    });

    const svg = css(spinnerLoaderAnimationProperties());
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
