/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { buttonGlobalVariables, ButtonPreset, buttonResetMixin, buttonSizing } from "@library/forms/buttonStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { borders, EMPTY_BORDER } from "@library/styles/styleHelpersBorders";
import { colorOut } from "@library/styles/styleHelpersColors";
import { EMPTY_FONTS, fonts } from "@library/styles/styleHelpersTypography";
import { styleFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import merge from "lodash/merge";
import { NestedCSSProperties } from "typestyle/lib/types";
import { globalVariables } from "@library/styles/globalStyleVars";
import cloneDeep from "lodash/cloneDeep";

export const generateButtonStyleProperties = (
    buttonTypeVars: IButtonType,
    setZIndexOnState = false,
    stateSuffix?: string,
) => {
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const buttonGlobals = buttonGlobalVariables();
    const zIndex = setZIndexOnState ? 1 : undefined;
    const buttonDimensions = buttonTypeVars.sizing || {};

    const state = buttonTypeVars.state ?? {};
    const colors = buttonTypeVars.colors ?? {
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
    };

    // Make sure we have the second level, if it was empty
    buttonTypeVars = merge(
        {
            preset: ButtonPreset.ADVANCED,
            colors,
            state,
            hover: state,
            focus: state,
            active: state,
            borders: state,
            focusAccessible: state,
        },
        buttonTypeVars,
    );

    let backgroundColor =
        buttonTypeVars.colors && buttonTypeVars.colors.bg ? buttonTypeVars.colors.bg : buttonGlobals.colors.bg;

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
        buttonTypeVars.borders = EMPTY_BORDER;
    }

    const borderVars = {
        ...EMPTY_BORDER,
        ...buttonGlobals.border,
        ...(buttonTypeVars.borders ?? {}),
    };

    let defaultBorder = borders(borderVars) as NestedCSSProperties;

    // Remove debug and fallback

    const hoverBorder =
        buttonTypeVars.hover && buttonTypeVars.hover.borders
            ? merge(
                  cloneDeep(defaultBorder),
                  borders({ ...EMPTY_BORDER, ...buttonTypeVars.hover.borders }, buttonGlobals.border),
              )
            : {};

    const activeBorder =
        buttonTypeVars.active && buttonTypeVars.active.borders
            ? merge(
                  cloneDeep(defaultBorder),
                  borders({ ...EMPTY_BORDER, ...buttonTypeVars.active.borders }, buttonGlobals.border),
              )
            : {};

    const focusBorder =
        buttonTypeVars.focus && buttonTypeVars.focus.borders
            ? merge(
                  cloneDeep(defaultBorder),
                  borders(
                      { ...EMPTY_BORDER, ...(buttonTypeVars.focus && buttonTypeVars.focus.borders) },
                      buttonGlobals.border,
                  ),
              )
            : defaultBorder;

    const focusAccessibleBorder =
        buttonTypeVars.focusAccessible && buttonTypeVars.focusAccessible.borders
            ? merge(
                  cloneDeep(defaultBorder),
                  borders({ ...EMPTY_BORDER, ...buttonTypeVars.focusAccessible.borders }, buttonGlobals.border),
              )
            : {};

    const fontVars = { ...EMPTY_FONTS, ...buttonGlobals.font, ...buttonTypeVars.fonts };

    const paddingHorizontal =
        buttonTypeVars.padding && buttonTypeVars.padding.horizontal !== undefined
            ? buttonTypeVars.padding.horizontal
            : buttonGlobals.padding.horizontal;
    const fontSize =
        buttonTypeVars.fonts && buttonTypeVars.fonts.size !== undefined
            ? buttonTypeVars.fonts.size
            : buttonGlobals.font.size;

    const { minHeight, minWidth } = buttonDimensions;
    const { skipDynamicPadding = false } = buttonTypeVars;

    const result: NestedCSSProperties = {
        ...buttonResetMixin(),
        textOverflow: "ellipsis",
        overflow: "hidden",
        width: "auto",
        maxWidth: percent(100),
        backgroundColor: colorOut(backgroundColor),
        ...fonts({
            ...fontVars,
            size: fontSize,
            color: fontColor,
            weight: fontVars.weight ?? undefined,
        }),
        ["-webkit-font-smoothing" as any]: "antialiased",
        ...defaultBorder,
        ...buttonSizing({
            minHeight,
            minWidth,
            fontSize,
            paddingHorizontal,
            formElementVars: formElVars,
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
        $nest: {
            [`&:not([disabled]):not(.focus-visible)`]: {
                outline: 0,
            },
            [`&:not([disabled]):hover${stateSuffix ?? ""}`]: {
                zIndex,
                color: colorOut(
                    buttonTypeVars.hover && buttonTypeVars.hover.colors && buttonTypeVars.hover.colors.fg
                        ? buttonTypeVars.hover.colors.fg
                        : undefined,
                ),
                backgroundColor: colorOut(
                    buttonTypeVars.hover && buttonTypeVars.hover.colors && buttonTypeVars.hover.colors.bg
                        ? buttonTypeVars.hover.colors.bg
                        : undefined,
                ),
                ...hoverBorder,
            },
            [`&:not([disabled]):focus${stateSuffix ?? ""}`]: {
                zIndex,
                color: colorOut(
                    buttonTypeVars.focus!.colors && buttonTypeVars.focus!.colors.fg
                        ? buttonTypeVars.focus!.colors.fg
                        : undefined,
                ),
                backgroundColor: colorOut(
                    buttonTypeVars.focus!.colors && buttonTypeVars.focus!.colors.bg
                        ? buttonTypeVars.focus!.colors.bg
                        : undefined,
                ),
                ...focusBorder,
            },
            [`&:not([disabled]):active${stateSuffix ?? ""}`]: {
                zIndex,
                color: colorOut(
                    buttonTypeVars.active!.colors && buttonTypeVars.active!.colors.fg
                        ? buttonTypeVars.active!.colors.fg
                        : undefined,
                ),
                backgroundColor: colorOut(
                    buttonTypeVars.active!.colors && buttonTypeVars.active!.colors.bg
                        ? buttonTypeVars.active!.colors.bg
                        : undefined,
                ),
                ...activeBorder,
            },
            [`&:not([disabled]):focus-visible${stateSuffix ?? ""}`]: {
                zIndex,
                color: colorOut(
                    buttonTypeVars.focusAccessible!.colors && buttonTypeVars.focusAccessible!.colors.fg
                        ? buttonTypeVars.focusAccessible!.colors.fg
                        : undefined,
                ),
                backgroundColor: colorOut(
                    buttonTypeVars.focusAccessible!.colors && buttonTypeVars.focusAccessible!.colors.bg
                        ? buttonTypeVars.focusAccessible!.colors.bg
                        : undefined,
                ),
                ...focusAccessibleBorder,
            },
            "&[disabled]": {
                opacity: formElVars.disabled.opacity,
            },
            ...(buttonTypeVars.extraNested ?? {}),
        },
    };

    return result;
};
const generateButtonClass = (buttonTypeVars: IButtonType, setZIndexOnState = false) => {
    const style = styleFactory(`button-${buttonTypeVars.name}`);
    return style(generateButtonStyleProperties(buttonTypeVars, setZIndexOnState));
};

export default generateButtonClass;
