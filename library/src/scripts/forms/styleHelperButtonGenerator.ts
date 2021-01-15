/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { buttonGlobalVariables } from "@library/forms/Button.variables";
import { buttonResetMixin, buttonSizing } from "@library/forms/buttonMixins";
import { ButtonPreset } from "@library/forms/ButtonPreset";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import merge from "lodash/merge";
import { CSSObject } from "@emotion/css";
import cloneDeep from "lodash/cloneDeep";
import { globalVariables } from "@library/styles/globalStyleVars";
import { defaultTransition } from "@library/styles/styleHelpersAnimation";

export const generateButtonStyleProperties = (props: {
    buttonTypeVars: IButtonType;
    setZIndexOnState?: boolean;
    stateSuffix?: string;
    debug?: boolean | string;
    globalVars?: any;
    formElementVars?: any;
    buttonGlobalVars?: any;
}) => {
    const {
        setZIndexOnState = false,
        stateSuffix,
        globalVars = globalVariables(),
        formElementVars = formElementsVariables(),
        buttonGlobalVars = buttonGlobalVariables(),
        debug = false,
    } = props;

    const zIndex = setZIndexOnState ? 1 : undefined;
    const buttonDimensions = props.buttonTypeVars.sizing || {};

    const state = props.buttonTypeVars.state ?? {};
    const colors = props.buttonTypeVars.colors ?? {
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
    };

    // Make sure we have the second level, if it was empty
    const buttonTypeVars = merge(
        {
            preset: ButtonPreset.ADVANCED,
            colors,
            state,
            hover: state,
            focus: state,
            active: state,
            focusAccessible: state,
        },
        props.buttonTypeVars,
    );

    let backgroundColor =
        buttonTypeVars.colors && buttonTypeVars.colors.bg ? buttonTypeVars.colors.bg : buttonGlobalVars.colors.bg;

    let fontColor =
        buttonTypeVars.fonts && buttonTypeVars.fonts.color
            ? buttonTypeVars.fonts.color
            : buttonTypeVars.colors && buttonTypeVars.colors.fg
            ? buttonTypeVars.colors.fg
            : undefined;

    if (!buttonTypeVars.borders) {
        buttonTypeVars.borders = {};
    }

    if (!buttonTypeVars.borders.color) {
        buttonTypeVars.borders = {};
    }

    const borderVars = {
        ...buttonGlobalVars.borders,
        ...buttonTypeVars.borders,
    };

    const defaultBorder = Mixins.border(borderVars, {
        fallbackBorderVariables: buttonGlobalVars.border,
        debug,
    });

    const hoverBorder =
        buttonTypeVars.hover && buttonTypeVars.hover.borders
            ? merge(
                  cloneDeep(defaultBorder),
                  Mixins.border(
                      { ...buttonTypeVars.hover.borders },
                      { fallbackBorderVariables: buttonGlobalVars.border },
                  ),
              )
            : {};

    const activeBorder =
        buttonTypeVars.active && buttonTypeVars.active.borders
            ? merge(
                  cloneDeep(defaultBorder),
                  Mixins.border(
                      { ...buttonTypeVars.active.borders },
                      { fallbackBorderVariables: buttonGlobalVars.border },
                  ),
              )
            : {};

    const focusBorder =
        buttonTypeVars.focus && buttonTypeVars.focus.borders
            ? merge(
                  cloneDeep(defaultBorder),
                  Mixins.border(
                      { ...(buttonTypeVars.focus && buttonTypeVars.focus.borders) },
                      { fallbackBorderVariables: buttonGlobalVars.border },
                  ),
              )
            : defaultBorder;

    const focusAccessibleBorder =
        buttonTypeVars.focusAccessible && buttonTypeVars.focusAccessible.borders
            ? merge(
                  cloneDeep(defaultBorder),
                  Mixins.border(
                      { ...buttonTypeVars.focusAccessible.borders },
                      { fallbackBorderVariables: buttonGlobalVars.border },
                  ),
              )
            : {};

    const fontVars = Variables.font({ ...buttonGlobalVars.font, ...buttonTypeVars.fonts });

    const paddingHorizontal =
        buttonTypeVars.padding && buttonTypeVars.padding.horizontal !== undefined
            ? buttonTypeVars.padding.horizontal
            : buttonGlobalVars.padding.horizontal;
    const fontSize =
        buttonTypeVars.fonts && buttonTypeVars.fonts.size !== undefined
            ? buttonTypeVars.fonts.size
            : buttonGlobalVars.font.size;

    const { minHeight, minWidth } = buttonDimensions;
    const { skipDynamicPadding = false } = buttonTypeVars;

    const result: CSSObject = {
        ...buttonResetMixin(),
        textOverflow: "ellipsis",
        overflow: "hidden",
        width: "auto",
        maxWidth: percent(100),
        backgroundColor: ColorsUtils.colorOut(backgroundColor),
        ...Mixins.font({
            ...fontVars,
            size: fontSize,
            color: fontColor,
            weight: fontVars.weight ?? undefined,
        }),
        WebkitFontSmoothing: "antialiased",
        ...defaultBorder,
        ...buttonSizing({
            minHeight,
            minWidth,
            fontSize,
            paddingHorizontal,
            formElementVars: formElementVars,
            borderRadius: borderVars["radius"] ?? undefined,
            skipDynamicPadding,
        }),
        display: "inline-flex",
        alignItems: "center",
        position: "relative",
        textAlign: "center",
        whiteSpace: "nowrap",
        verticalAlign: "middle",
        justifyContent: "center",
        touchAction: "manipulation",
        cursor: "pointer",
        ...defaultTransition("background", "color", "border"),
        ...{
            [`&:not([disabled]):not(.focus-visible)`]: {
                outline: 0,
            },
            [`&:not([disabled]):hover${stateSuffix ?? ""}`]: {
                zIndex,
                color: ColorsUtils.colorOut(
                    buttonTypeVars.hover && buttonTypeVars.hover.colors && buttonTypeVars.hover.colors.fg
                        ? buttonTypeVars.hover.colors.fg
                        : undefined,
                ),
                backgroundColor: ColorsUtils.colorOut(
                    buttonTypeVars.hover && buttonTypeVars.hover.colors && buttonTypeVars.hover.colors.bg
                        ? buttonTypeVars.hover.colors.bg
                        : undefined,
                ),
                ...hoverBorder,
            },
            [`&:not([disabled]):focus${stateSuffix ?? ""}`]: {
                zIndex,
                color: ColorsUtils.colorOut(
                    buttonTypeVars.focus!.colors && buttonTypeVars.focus!.colors.fg
                        ? buttonTypeVars.focus!.colors.fg
                        : undefined,
                ),
                backgroundColor: ColorsUtils.colorOut(
                    buttonTypeVars.focus!.colors && buttonTypeVars.focus!.colors.bg
                        ? buttonTypeVars.focus!.colors.bg
                        : undefined,
                ),
                ...focusBorder,
            },
            [`&:not([disabled]):active${stateSuffix ?? ""}`]: {
                zIndex,
                color: ColorsUtils.colorOut(
                    buttonTypeVars.active!.colors && buttonTypeVars.active!.colors.fg
                        ? buttonTypeVars.active!.colors.fg
                        : undefined,
                ),
                backgroundColor: ColorsUtils.colorOut(
                    buttonTypeVars.active!.colors && buttonTypeVars.active!.colors.bg
                        ? buttonTypeVars.active!.colors.bg
                        : undefined,
                ),
                ...activeBorder,
            },
            [`&:not([disabled]):focus-visible${stateSuffix ?? ""}`]: {
                zIndex,
                color: ColorsUtils.colorOut(
                    buttonTypeVars.focusAccessible!.colors && buttonTypeVars.focusAccessible!.colors.fg
                        ? buttonTypeVars.focusAccessible!.colors.fg
                        : undefined,
                ),
                backgroundColor: ColorsUtils.colorOut(
                    buttonTypeVars.focusAccessible!.colors && buttonTypeVars.focusAccessible!.colors.bg
                        ? buttonTypeVars.focusAccessible!.colors.bg
                        : undefined,
                ),
                ...focusAccessibleBorder,
            },
            "&[disabled]": {
                opacity: formElementVars.disabled.opacity,
            },
            ...(buttonTypeVars.extraNested ?? {}),
        },
    };

    return result;
};

const generateButtonClass = (
    buttonTypeVars: IButtonType,
    options?: { setZIndexOnState?: boolean; debug?: boolean | string },
) => {
    const { setZIndexOnState = false, debug = false } = options || {};
    const style = styleFactory(`button-${buttonTypeVars.name}`);
    const buttonStyles = generateButtonStyleProperties({
        buttonTypeVars,
        setZIndexOnState,
    });
    const buttonClass = style(buttonStyles);
    return buttonClass;
};

export default generateButtonClass;
