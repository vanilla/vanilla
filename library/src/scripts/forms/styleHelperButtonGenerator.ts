/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {formElementsVariables} from "@library/forms/formElementStyles";
import {styleFactory} from "@library/styles/styleUtils";
import merge from "lodash/merge";
import {calculateBorders} from "@library/forms/borderStylesCalculator";
import {borders} from "@library/styles/styleHelpersBorders";
import {percent} from "csx";
import {fonts} from "@library/styles/styleHelpersTypography";
import {colorOut} from "@library/styles/styleHelpersColors";
import {buttonGlobalVariables, buttonResetMixin, buttonSizing} from "@library/forms/buttonStyles";
import {IButtonType} from "@library/forms/styleHelperButtonInterface";

const generateButtonClass = (buttonTypeVars: IButtonType, setZIndexOnState = false) => {
    const formElVars = formElementsVariables();
    const buttonGlobals = buttonGlobalVariables();
    const style = styleFactory(`button-${buttonTypeVars.name}`);
    const zIndex = setZIndexOnState ? 1 : undefined;
    const buttonDimensions = buttonTypeVars.sizing || false;

    // Make sure we have the second level, if it was empty
    buttonTypeVars = merge(buttonTypeVars, {
        colors: {},
        hover: {},
        focus: {},
        active: {},
        borders: {},
        focusAccessible: {},
    });

    const debug = buttonTypeVars.name === "splashSearchButton";


    const defaultBorder = calculateBorders(buttonTypeVars.borders, debug);
    const hoverBorder = buttonTypeVars.hover && buttonTypeVars.hover.borders ? merge(defaultBorder, borders(buttonTypeVars.hover.borders)) : defaultBorder;
    const activeBorder = buttonTypeVars.active && buttonTypeVars.active.borders ? merge(defaultBorder, borders(buttonTypeVars.active.borders)) : defaultBorder;
    const focusBorder = buttonTypeVars.focus && buttonTypeVars.focus.borders ? merge(defaultBorder, borders(buttonTypeVars.focus.borders)) : defaultBorder;
    const focusAccessibleBorder = buttonTypeVars.focusAccessible && buttonTypeVars.focusAccessible.borders ? merge(defaultBorder, borders(buttonTypeVars.focusAccessible.borders)) : defaultBorder;


    return style({
        ...buttonResetMixin(),
        textOverflow: "ellipsis",
        overflow: "hidden",
        maxWidth: percent(100),
        ...borders(defaultBorder, debug),
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
        ...fonts({
            ...buttonGlobals.font,
            ...buttonTypeVars.fonts,
        }),
        backgroundColor: colorOut(
            buttonTypeVars.colors && buttonTypeVars.colors.bg ? buttonTypeVars.colors.bg : buttonGlobals.colors.bg,
        ),
        ...defaultBorder,
        $nest: {
            "&:not([disabled])": {
                $nest: {
                    "&:not(.focus-visible)": {
                        outline: 0,
                    },
                    "&:hover": {
                        zIndex,
                        backgroundColor: colorOut(
                            buttonTypeVars.hover && buttonTypeVars.hover.colors && buttonTypeVars.hover.colors.bg
                                ? buttonTypeVars.hover.colors.bg
                                : undefined,
                        ),
                        ...hoverBorder,
                        ...fonts(buttonTypeVars.hover && buttonTypeVars.hover.fonts ? buttonTypeVars.hover.fonts : {}),
                    },
                    "&:focus": {
                        zIndex,
                        backgroundColor: colorOut(
                            buttonTypeVars.focus!.colors && buttonTypeVars.focus!.colors.bg
                                ? buttonTypeVars.focus!.colors.bg
                                : undefined,
                        ),
                        color: colorOut(buttonTypeVars.focus!.fg),
                        ...focusBorder,
                        ...fonts(buttonTypeVars.focus && buttonTypeVars.focus.fonts ? buttonTypeVars.focus.fonts : {}),
                    },
                    "&:active": {
                        zIndex,
                        backgroundColor: colorOut(
                            buttonTypeVars.active!.colors && buttonTypeVars.active!.colors.bg
                                ? buttonTypeVars.active!.colors.bg
                                : undefined,
                        ),
                        color: colorOut(buttonTypeVars.active!.fg),
                        ...activeBorder,
                        ...fonts(
                            buttonTypeVars.active && buttonTypeVars.active.fonts ? buttonTypeVars.active.fonts : {},
                        ),
                    },
                    "&.focus-visible": {
                        zIndex,
                        backgroundColor: colorOut(
                            buttonTypeVars.focusAccessible!.colors && buttonTypeVars.focusAccessible!.colors.bg
                                ? buttonTypeVars.focusAccessible!.colors.bg
                                : undefined,
                        ),
                        color: colorOut(buttonTypeVars.focusAccessible!.fg),
                        ...focusAccessibleBorder,
                        ...fonts(
                            buttonTypeVars.focusAccessible && buttonTypeVars.focusAccessible.fonts
                                ? buttonTypeVars.focusAccessible.fonts
                                : {},
                        ),
                    },
                },
            },
            "&[disabled]": {
                opacity: 0.5,
            },
        },
    });
};

export default generateButtonClass;
