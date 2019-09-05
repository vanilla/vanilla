/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { buttonGlobalVariables, buttonResetMixin, buttonSizing } from "@library/forms/buttonStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { borders } from "@library/styles/styleHelpersBorders";
import { colorOut } from "@library/styles/styleHelpersColors";
import { fonts } from "@library/styles/styleHelpersTypography";
import { styleFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import merge from "lodash/merge";
import { NestedCSSProperties } from "typestyle/lib/types";
import { globalVariables } from "@library/styles/globalStyleVars";
import cloneDeep from "lodash/cloneDeep";

export const generateButtonStyleProperties = (buttonTypeVars: IButtonType, setZIndexOnState = false) => {
    const formElVars = formElementsVariables();
    const buttonGlobals = buttonGlobalVariables();
    const zIndex = setZIndexOnState ? 1 : undefined;
    const buttonDimensions = buttonTypeVars.sizing || false;

    // Make sure we have the second level, if it was empty
    buttonTypeVars = merge(
        {
            colors: {},
            hover: {},
            focus: {},
            active: {},
            borders: {},
            focusAccessible: {},
        },
        buttonTypeVars,
    );

    // Remove debug and fallback
    const defaultBorder = borders(buttonTypeVars.borders, globalVariables().border);

    const hoverBorder =
        buttonTypeVars.hover && buttonTypeVars.hover.borders
            ? merge(cloneDeep(defaultBorder), borders(buttonTypeVars.hover.borders))
            : {};

    const activeBorder =
        buttonTypeVars.active && buttonTypeVars.active.borders
            ? merge(cloneDeep(defaultBorder), borders(buttonTypeVars.active.borders))
            : {};

    const focusBorder =
        buttonTypeVars.focus && buttonTypeVars.focus.borders
            ? merge(cloneDeep(defaultBorder), borders(buttonTypeVars.focus && buttonTypeVars.focus.borders))
            : defaultBorder;

    const focusAccessibleBorder =
        buttonTypeVars.focusAccessible && buttonTypeVars.focusAccessible.borders
            ? merge(cloneDeep(defaultBorder), borders(buttonTypeVars.focusAccessible.borders))
            : {};

    const result: NestedCSSProperties = {
        ...buttonResetMixin(),
        textOverflow: "ellipsis",
        overflow: "hidden",
        maxWidth: percent(100),
        color: colorOut(
            buttonTypeVars.colors && buttonTypeVars.colors.fg ? buttonTypeVars.colors.fg : buttonGlobals.colors.fg,
        ),
        backgroundColor: colorOut(
            buttonTypeVars.colors && buttonTypeVars.colors.bg ? buttonTypeVars.colors.bg : buttonGlobals.colors.bg,
        ),
        ...fonts({
            ...buttonGlobals.font,
            ...buttonTypeVars.fonts,
        }),
        ...defaultBorder,
        ...buttonSizing(
            buttonDimensions && buttonDimensions.minHeight
                ? buttonDimensions.minHeight
                : buttonGlobals.sizing.minHeight,
            buttonDimensions && buttonDimensions.minWidth ? buttonDimensions.minWidth : buttonGlobals.sizing.minWidth,
            buttonTypeVars.fonts && buttonTypeVars.fonts.size ? buttonTypeVars.fonts.size : buttonGlobals.font.size,
            buttonTypeVars.padding && buttonTypeVars.padding.side
                ? buttonTypeVars.padding.side
                : buttonGlobals.padding.side,
            formElVars,
        ),
        display: "inline-flex",
        alignItems: "center",
        position: "relative",
        textAlign: "center",
        whiteSpace: "nowrap",
        verticalAlign: "middle",
        justifyContent: "center",
        touchAction: "manipulation",
        cursor: "pointer",
        minWidth: buttonGlobals.sizing.minWidth,
        minHeight: buttonGlobals.sizing.minHeight,
        $nest: {
            "&:not([disabled])": {
                $nest: {
                    "&:not(.focus-visible)": {
                        outline: 0,
                    },
                    "&:hover": {
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
                        ...fonts(buttonTypeVars.hover && buttonTypeVars.hover.fonts ? buttonTypeVars.hover.fonts : {}),
                        ...hoverBorder,
                    },
                    "&:focus": {
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

                        ...fonts(buttonTypeVars.focus && buttonTypeVars.focus.fonts ? buttonTypeVars.focus.fonts : {}),
                        ...focusBorder,
                    },
                    "&:active": {
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
                        ...fonts(
                            buttonTypeVars.active && buttonTypeVars.active.fonts ? buttonTypeVars.active.fonts : {},
                        ),
                        ...activeBorder,
                    },
                    "&.focus-visible": {
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
                        ...fonts(
                            buttonTypeVars.focusAccessible && buttonTypeVars.focusAccessible.fonts
                                ? buttonTypeVars.focusAccessible.fonts
                                : {},
                        ),
                        ...focusAccessibleBorder,
                    },
                },
            },
            "&[disabled]": {
                opacity: formElVars.disabled.opacity,
            },
        },
    };

    return result;
};
const generateButtonClass = (buttonTypeVars: IButtonType, setZIndexOnState = false) => {
    const style = styleFactory(`button-${buttonTypeVars.name}`);
    return style(generateButtonStyleProperties(buttonTypeVars, setZIndexOnState));
};

export default generateButtonClass;
