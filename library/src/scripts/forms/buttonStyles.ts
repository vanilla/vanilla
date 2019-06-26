/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    allButtonStates,
    borders,
    borderType,
    colorOut,
    flexHelper,
    fonts, IBorderRadii,
    IBordersWithRadius, IBottomBorderRadii,
    IFont, ILeftBorderRadii, IRightBorderRadii, ITopBorderRadii,
    modifyColorBasedOnLightness,
    spinnerLoader,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { TLength, NestedCSSProperties } from "typestyle/lib/types";
import { DEBUG_STYLES, styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent, px } from "csx";
import { ColorValues } from "@library/styles/styleHelpersColors";
import { ISingleBorderStyle, IBorderStyles} from "@library/styles/styleHelpersBorders";
import merge from "lodash/merge";
import {BorderRadiusProperty, BorderStyleProperty, BorderWidthProperty} from "csstype";
import {instanceOf} from "prop-types";
import TabButtonList from "@library/navigation/tabs/TabButtonList";

export const buttonGlobalVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const makeThemeVars = variableFactory("button");

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
    });

    const font = makeThemeVars("font", {
        color: globalVars.mainColors.fg,
        size: globalVars.fonts.size.medium,
    });

    const padding = makeThemeVars("padding", {
        top: 2,
        bottom: 3,
        side: 12,
    });

    const sizing = makeThemeVars("sizing", {
        minHeight: formElVars.sizing.height,
        minWidth: 104,
        compactHeight: 24,
    });

    const border = makeThemeVars("border", globalVars.border);

    return {
        padding,
        sizing,
        border,
        font,
        colors,
    };
});

export const transparentColor = "transparent" as ColorValues;

export interface IButtonType {
    name: string;
    colors?: {
        bg?: ColorValues;
        fg?: ColorValues;
    };
    borders?: IBorderStyles;
    sizing?: {
        minHeight?: TLength;
        minWidth?: TLength;
    };
    padding?: {
        top?: TLength;
        bottom?: TLength;
        side?: TLength;
    };
    fonts?: IFont;
    hover?: {
        fg?: ColorValues;
        colors?: {
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    focus?: {
        fg?: ColorValues;
        colors?: {
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    active?: {
        fg?: ColorValues;
        colors?: {
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    focusAccessible?: {
        fg?: ColorValues;
        colors?: {
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
}

export const buttonVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("button");

    const standard: IButtonType = makeThemeVars("basic", {
        name: ButtonTypes.STANDARD,
        spinnerColor: globalVars.mainColors.fg,
        colors: {
            bg: globalVars.mainColors.bg,
        },
        borders: {
            color: globalVars.mixBgAndFg(0.24),
            radius: globalVars.border.radius,
        },
        fonts: {
            color: globalVars.mainColors.fg,
        },
        hover: {
            colors: {
                bg: globalVars.mainColors.primary,
            },
            borders: {
                color: globalVars.mainColors.primary,
            },
            fonts: {
                color: globalVars.mainColors.bg,
            },
        },
        active: {
            colors: {
                bg: globalVars.mainColors.primary,
            },
            borders: {
                color: globalVars.mainColors.primary,
            },
            fonts: {
                color: globalVars.mainColors.bg,
            },
        },
        focus: {
            colors: {
                bg: globalVars.mainColors.primary,
            },
            borders: {
                color: globalVars.mainColors.primary,
            },
            fonts: {
                color: globalVars.mainColors.bg,
            },
        },
        focusAccessible: {
            colors: {
                bg: globalVars.mainColors.primary,
            },
            borders: {
                color: globalVars.mainColors.primary,
            },
            fonts: {
                color: globalVars.mainColors.bg,
            },
        },
    });

    const compact: IButtonType = makeThemeVars("compact", {
        name: ButtonTypes.COMPACT,
        colors: {
            bg: globalVars.mainColors.bg,
        },
        sizing: {
            minHeight: 24,
        },
        borders: {
            color: transparentColor,
            radius: globalVars.border.radius,
        },
        hover: {
            fonts: {
                color: globalVars.mainColors.primary,
            },
        },
        active: {
            fonts: {
                color: globalVars.mainColors.primary,
            },
        },
        focus: {
            fonts: {
                color: globalVars.mainColors.primary,
            },
        },
        focusAccessible: {
            fonts: {
                color: globalVars.mainColors.primary,
            },
        },
    });

    const compactPrimary: IButtonType = makeThemeVars("compactPrimary", {
        name: ButtonTypes.COMPACT_PRIMARY,
        colors: {
            bg: globalVars.mainColors.bg,
        },
        fonts: {
            color: globalVars.mainColors.primary.fade(0.7),
        },
        sizing: {
            minHeight: 24,
        },
        borders: {
            color: transparentColor,
            radius: globalVars.border.radius,
        },
        hover: {
            fonts: {
                color: globalVars.mainColors.primary,
            },
            borders: {
                color: globalVars.mainColors.primary,
            },
        },
        active: {
            fonts: {
                color: globalVars.mainColors.primary,
            },
            borders: {
                color: globalVars.mainColors.primary,
            },
        },
        focus: {
            fonts: {
                color: globalVars.mainColors.primary,
            },
            borders: {
                color: globalVars.mainColors.primary,
            },
        },
        focusAccessible: {
            fonts: {
                color: globalVars.mainColors.primary,
            },
            borders: {
                color: globalVars.mainColors.primary,
            },
        },
    });

    const primary: IButtonType = makeThemeVars("primary", {
        name: ButtonTypes.PRIMARY,
        colors: {
            bg: globalVars.mainColors.primary,
        },
        fonts: {
            color: globalVars.mainColors.bg,
        },
        spinnerColor: globalVars.mainColors.bg,
        borders: {
            color: globalVars.mainColors.primary,
            radius: globalVars.border.radius,
        },
        hover: {
            fonts: {
                color: globalVars.mainColors.bg,
            },
            colors: {
                bg: globalVars.mainColors.secondary,
            },
            borders: {
                color: globalVars.mainColors.secondary,
            },
        },
        active: {
            fonts: {
                color: globalVars.mainColors.bg,
            },
            colors: {
                bg: globalVars.mainColors.secondary,
            },
            borders: {
                color: globalVars.mainColors.secondary,
            },
        },
        focus: {
            fonts: {
                color: globalVars.mainColors.bg,
            },
            colors: {
                bg: globalVars.mainColors.secondary,
            },
            borders: {
                color: globalVars.mainColors.secondary,
            },
        },
        focusAccessible: {
            fonts: {
                color: globalVars.mainColors.bg,
            },
            colors: {
                bg: globalVars.mainColors.secondary,
            },
            borders: {
                color: globalVars.mainColors.secondary,
            },
        },
    });

    const transparent: IButtonType = makeThemeVars("transparent", {
        name: ButtonTypes.TRANSPARENT,
        colors: {
            bg: transparentColor,
        },
        fonts: {
            color: globalVars.mainColors.fg,
        },
        border: {
            color: modifyColorBasedOnLightness(globalVars.mainColors.fg, 1, true),
            radius: globalVars.border.radius,
        },
        hover: {
            colors: {
                bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, 0.9),
            },
        },
        active: {
            colors: {
                bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, 0.9),
            },
        },
        focus: {
            colors: {
                bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, 0.9),
            },
        },
        focusAccessible: {
            colors: {
                bg: modifyColorBasedOnLightness(globalVars.mainColors.fg, 0.9),
            },
        },
    });

    const translucid: IButtonType = makeThemeVars("translucid", {
        name: ButtonTypes.TRANSLUCID,
        colors: {
            bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, 1).fade(0.1),
        },
        fonts: {
            color: globalVars.mainColors.bg,
        },
        spinnerColor: globalVars.mainColors.bg,
        border: {
            color: globalVars.mainColors.bg,
            radius: globalVars.border.radius,
        },
        hover: {
            colors: {
                bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, 1).fade(0.2),
            },
            border: {
                color: globalVars.mainColors.bg,
            },
        },
        active: {
            colors: {
                bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, 1).fade(0.2),
            },
            border: {
                color: globalVars.mainColors.bg,
            },
        },
        focus: {
            colors: {
                bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, 1).fade(0.2),
            },
            border: {
                color: globalVars.mainColors.bg,
            },
        },
        focusAccessible: {
            colors: {
                bg: modifyColorBasedOnLightness(globalVars.mainColors.bg, 1).fade(0.2),
            },
            border: {
                color: globalVars.mainColors.bg,
            },
        },
    });

    const inverted: IButtonType = makeThemeVars("inverted", {
        name: ButtonTypes.INVERTED,
        colors: {
            bg: globalVars.mainColors.fg,
        },
        fonts: {
            color: globalVars.mainColors.primary,
        },
        spinnerColor: globalVars.elementaryColors.white,
        border: {
            color: globalVars.mainColors.fg,
            radius: globalVars.border.radius,
        },
        hover: {
            colors: {
                bg: globalVars.mainColors.fg.fade(0.9),
            },
        },
        active: {
            colors: {
                bg: globalVars.mainColors.fg.fade(0.9),
            },
        },
        focus: {
            colors: {
                bg: globalVars.mainColors.fg.fade(0.9),
            },
        },
        focusAccessible: {
            colors: {
                bg: globalVars.mainColors.fg.fade(0.9),
            },
        },
    });

    return {
        standard,
        primary,
        transparent,
        compact,
        compactPrimary,
        translucid,
        inverted,
    };
});

export const buttonSizing = (height, minWidth, fontSize, paddingHorizontal, formElementVars) => {
    const borderWidth = formElementVars.borders ? formElementVars.borders : buttonGlobalVariables().border.width;
    return {
        minHeight: unit(formElementVars.sizing.minHeight),
        fontSize: unit(fontSize),
        padding: `${unit(0)} ${px(paddingHorizontal)}`,
        lineHeight: unit(formElementVars.sizing.height - borderWidth * 2),
    };
};

export const buttonResetMixin = (): NestedCSSProperties => ({
    ...userSelect(),
    "-webkit-appearance": "none",
    appearance: "none",
    border: 0,
    background: "none",
    cursor: "pointer",
    color: "inherit",
    font: "inherit",
});

export const overwriteButtonClass = (
    buttonTypeVars: IButtonType,
    overwriteVars: IButtonType,
    setZIndexOnState = false,
) => {
    const buttonVars = merge(buttonTypeVars, overwriteVars);
    // append names for debugging purposes
    buttonVars.name = `${buttonTypeVars.name}-${overwriteVars.name}`;
    return generateButtonClass(buttonVars, setZIndexOnState);
};


const cleanUpBorderValues = (values) => {
    if (values) {
        if (values.radius){
            if (values.radius.topLeft === undefined) {
                delete values.radius.topLeft;
            }
            if (values.radius.topRight === undefined) {
                delete values.radius.topRight;
            }
            if (values.radius.bottomLeft === undefined) {
                delete values.radius.bottomLeft;
            }
            if (values.radius.bottomRight === undefined) {
                delete values.radius.bottomRight;
            }
        }
        if (values.top) {
            if (values.top && values.top.color === undefined) {
                delete values.top.color;
            }
            if (values.top.width === undefined) {
                delete values.top.width;
            }
            if (values.top.style === undefined) {
                delete values.top.style;
            }
        }
        if (values.right) {
            if (values.right.color === undefined) {
                delete values.right.color;
            }
            if (values.right.width === undefined) {
                delete values.right.width;
            }
            if (values.right.style === undefined) {
                delete values.right.style;
            }
        }
        if (values.bottom) {
            if (values.bottom.color === undefined) {
                delete values.bottom.color;
            }
            if (values.bottom.width === undefined) {
                delete values.bottom.width;
            }
            if (values.bottom.style === undefined) {
                delete values.bottom.style;
            }
        }
        if (values.left) {
            if (values.left.color === undefined) {
                delete values.left.color;
            }
            if (values.left.width === undefined) {
                delete values.left.width;
            }
            if (values.left.style === undefined) {
                delete values.left.style;
            }
        }
    }
}

export const calculateBorders = (borderStyles: IBorderStyles | undefined | null, debug = false) => {

    if (debug) {
        window.console.log("raw data: ", borderStyles);
    }

    if (borderStyles) {
        let output;
        let hasGlobalStyles = false;
        let hasDetailedStyles = false;
        const globalResult: any = {};
        const detailedResult: any = {
            top: {
                color: undefined,
                width: undefined,
                style: undefined,
                radius: undefined,
            },
            right: {
                color: undefined,
                width: undefined,
                style: undefined,
                radius: undefined,
            },
            bottom: {
                color: undefined,
                width: undefined,
                style: undefined,
                radius: undefined,
            },
            left: {
                color: undefined,
                width: undefined,
                style: undefined,
                radius: undefined,
            },
            radius: {
                topRight: undefined,
                bottomRight: undefined,
                topLeft: undefined,
                bottomLeft: undefined,
            },
        };
        //
        // if (debug) {
        //     window.console.log("calculateBorders == in == ", borderStyles);
        // }

        // Global - globalResult
        // ----------------------------
        if (borderStyles.color) {
            globalResult.color = borderStyles.color;
            hasGlobalStyles = true;
        }
        if (borderStyles.width) {
            globalResult.width = borderStyles.width;
            hasGlobalStyles = true;
        }
        if (borderStyles.style) {
            globalResult.style = borderStyles.style;
            hasGlobalStyles = true;
        }


        // Detailed - detailedResult
        // ----------------------------
        // All (can be redundat if both global and all are set)

        if (borderStyles.all) {
            const borderStylesAll = borderStyles.all;
            if (borderStylesAll.color) {
                detailedResult.top.color = borderStylesAll.color;
                detailedResult.right.color = borderStylesAll.color;
                detailedResult.bottom.color = borderStylesAll.color;
                detailedResult.left.color = borderStylesAll.color;
                hasDetailedStyles = true;
            }
            if (borderStylesAll.width) {
                detailedResult.top.width = borderStylesAll.width;
                detailedResult.right.width = borderStylesAll.width;
                detailedResult.bottom.width = borderStylesAll.width;
                detailedResult.left.width = borderStylesAll.width;
                hasDetailedStyles = true;
            }
            if (borderStylesAll.style) {
                detailedResult.top.style = borderStylesAll.style;
                detailedResult.right.style = borderStylesAll.style;
                detailedResult.bottom.style = borderStylesAll.style;
                detailedResult.left.style = borderStylesAll.style;
                hasDetailedStyles = true;
            }
        }

        if(debug) {
            window.console.log("2 ================ spot check: ", detailedResult);
        }

        // Detailed - 2 Sides
        // ----------------------------
        if (borderStyles.topBottom) {
            const stylesTopBottom = borderStyles.topBottom;
            if (stylesTopBottom.color) {
                detailedResult.top.color = stylesTopBottom.color;
                detailedResult.bottom.color = stylesTopBottom.color;
                hasDetailedStyles = true;
            }
            if (stylesTopBottom.width) {
                detailedResult.top.width = stylesTopBottom.width;
                detailedResult.bottom.width = stylesTopBottom.width;
                hasDetailedStyles = true;
            }
            if (stylesTopBottom.style) {
                detailedResult.top.style = stylesTopBottom.style;
                detailedResult.bottom.style = stylesTopBottom.style;
                hasDetailedStyles = true;
            }
        }

        // if(debug) {
        //     window.console.log("================ spot check: ", detailedResult);
        // }

        if (borderStyles.leftRight) {
            const stylesLeftRight = borderStyles.leftRight;
            if (stylesLeftRight.color) {
                detailedResult.left.color = stylesLeftRight.color;
                detailedResult.right.color = stylesLeftRight.color;
                hasDetailedStyles = true;
            }
            if (stylesLeftRight.width) {
                detailedResult.left.width = stylesLeftRight.width;
                detailedResult.right.width = stylesLeftRight.width;
                hasDetailedStyles = true;
            }
            if (stylesLeftRight.style) {
                detailedResult.left.style = stylesLeftRight.style;
                detailedResult.right.style = stylesLeftRight.style;
                hasDetailedStyles = true;
            }
        }

        // Detailed - 1 Side
        // ----------------------------
        if (borderStyles.top) {
            if (borderStyles.top.color) {
                detailedResult.top.color = borderStyles.top.color;
                hasDetailedStyles = true;
            }
            if (borderStyles.top.width) {
                detailedResult.top.width = borderStyles.top.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.top.style) {
                detailedResult.top.style = borderStyles.top.style;
                hasDetailedStyles = true;
            }
            if (borderStyles.top.radius) {
                const topRadius = borderStyles.top.radius as ITopBorderRadii;
                if (typeof topRadius === "object") {
                    detailedResult.radius.topRight = topRadius.right ? topRadius.right : undefined;
                    detailedResult.radius.topLeft = topRadius.left ? topRadius.left : undefined;
                } else {
                    detailedResult.radius.topRight = topRadius;
                    detailedResult.radius.topLeft = topRadius;
                }
                hasDetailedStyles = true;
            }
        }

        if (borderStyles.right) {
            if (borderStyles.right.color) {
                detailedResult.right.color = borderStyles.right.color;
                hasDetailedStyles = true;
            }
            if (borderStyles.right.width) {
                detailedResult.right.width = borderStyles.right.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.right.style) {
                detailedResult.right.style = borderStyles.right.style;
                hasDetailedStyles = true;
            }
            if (borderStyles.right.radius) {
                const rightBorderRadius = borderStyles.right.radius as IRightBorderRadii;
                if (typeof rightBorderRadius === "object") {
                    detailedResult.radius.topRight = rightBorderRadius.top ? rightBorderRadius.top : undefined;
                    detailedResult.radius.bottomtLeft = rightBorderRadius.bottom ? rightBorderRadius.bottom : undefined;
                } else {
                    detailedResult.radius.rightRight = rightBorderRadius;
                    detailedResult.radius.rightLeft = rightBorderRadius;
                }
                hasDetailedStyles = true;
            }
        }


        if (borderStyles.bottom) {
            if (borderStyles.bottom.color) {
                detailedResult.bottom.color = borderStyles.bottom.color;
                hasDetailedStyles = true;
            }
            if (borderStyles.bottom.width) {
                detailedResult.bottom.width = borderStyles.bottom.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.bottom.style) {
                detailedResult.bottom.style = borderStyles.bottom.style;
                hasDetailedStyles = true;
            }


            if (borderStyles.bottom.radius) {
                const bottomBorderRadius = borderStyles.bottom.radius as IBottomBorderRadii;
                if (typeof bottomBorderRadius === "object") {
                    detailedResult.radius.bottomRight = bottomBorderRadius.right ? bottomBorderRadius.right : undefined;
                    detailedResult.radius.bottomtLeft = bottomBorderRadius.left ? bottomBorderRadius.left : undefined;
                } else {
                    detailedResult.radius.bottomRight = bottomBorderRadius;
                    detailedResult.radius.bottomtLeft = bottomBorderRadius;
                }
                hasDetailedStyles = true;
            }
        }

        if (borderStyles.left) {
            if (borderStyles.left.color) {
                detailedResult.left.color = borderStyles.left.color;
                hasDetailedStyles = true;
            }
            if (borderStyles.left.width) {
                detailedResult.left.width = borderStyles.left.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.left.style) {
                detailedResult.left.style = borderStyles.left.style;
                hasDetailedStyles = true;
            }

            if (borderStyles.left.radius) {
                const leftRadius = borderStyles.left.radius as ILeftBorderRadii;
                if (typeof leftRadius === "object") {
                    detailedResult.radius.topLeft = leftRadius.top ? leftRadius.top : undefined;
                    detailedResult.radius.bottomtLeft = leftRadius.bottom ? leftRadius.bottom : undefined;
                } else {
                    detailedResult.radius.topLeft = leftRadius;
                    detailedResult.radius.rightLeft = leftRadius;
                }
                hasDetailedStyles = true;
            }
        }

        if (borderStyles.radius) {
            const radius = borderStyles.radius;
            if (typeof radius === "object") {
                if (radius.all) {
                    detailedResult.radius.topRight = radius.all;
                    detailedResult.radius.bottomRight = radius.all;
                    detailedResult.radius.bottomLeft = radius.all;
                    detailedResult.radius.topLeft = radius.all;
                    hasDetailedStyles = true;
                }

                if (radius.top) {
                    detailedResult.radius.topRight = radius.top;
                    detailedResult.radius.topLeft = radius.top;
                    hasDetailedStyles = true;
                }

                if (radius.bottom) {
                    detailedResult.radius.bottomRight = radius.bottom;
                    detailedResult.radius.bottomLeft = radius.bottom;
                    hasDetailedStyles = true;
                }

                if (radius.left) {
                    detailedResult.radius.topLeft = radius.left;
                    detailedResult.radius.bottomLeft = radius.left;
                    hasDetailedStyles = true;
                }

                if (radius.right) {
                    detailedResult.radius.topRight = radius.right;
                    detailedResult.radius.bottomRight = radius.right;
                    hasDetailedStyles = true;
                }

                if (radius.topRight) {
                    detailedResult.radius.topRight = radius.topRight;
                    hasDetailedStyles = true;
                }

                if (radius.topLeft) {
                    detailedResult.radius.topLeft = radius.topLeft;
                    hasDetailedStyles = true;
                }

                if (radius.bottomRight) {
                    detailedResult.radius.bottomRight = radius.bottomRight;
                    hasDetailedStyles = true;
                }

                if (radius.bottomLeft) {
                    detailedResult.radius.bottomLeft = radius.bottomLeft;
                    hasDetailedStyles = true;
                }
            } else {
                detailedResult.radius = {
                    topRight: radius,
                    bottomRight: radius,
                    topLeft: radius,
                    bottomLeft: radius,
                };
            }
        }

        // Clean up undefined
        cleanUpBorderValues(globalResult);
        cleanUpBorderValues(detailedResult);

        // if (debug) {
        //     window.console.log("globalResult == step == ", globalResult);
        //     window.console.log("detailedResult == step == ", detailedResult);
        // }

        if (hasGlobalStyles && !hasDetailedStyles) {
            output = globalResult;
        } else if (!hasGlobalStyles && hasDetailedStyles) {
            output = detailedResult;
        } else {
            output = merge(globalResult, detailedResult);
        }


        // if (debug) {
        //     window.console.log("output: ", output);
        // }
        return output;
    } else {
        return null;
    }
}

export const generateButtonClass = (buttonTypeVars: IButtonType, setZIndexOnState = false) => {
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
    // if (debug) {
    //     window.console.log("buttonTypeVars.borders: ", calculateBorders(buttonTypeVars.borders, true));
    // }

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

export enum ButtonTypes {
    STANDARD = "standard",
    PRIMARY = "primary",
    TRANSPARENT = "transparent",
    COMPACT = "compact",
    COMPACT_PRIMARY = "compactPrimary",
    TRANSLUCID = "translucid",
    INVERTED = "inverted",
    CUSTOM = "custom",
    TEXT = "text",
    TEXT_PRIMARY = "textPrimary",
    ICON = "icon",
    ICON_COMPACT = "iconCompact",
}

export const buttonClasses = useThemeCache(() => {
    const vars = buttonVariables();
    return {
        primary: generateButtonClass(vars.primary),
        standard: generateButtonClass(vars.standard),
        transparent: generateButtonClass(vars.transparent),
        compact: generateButtonClass(vars.compact),
        compactPrimary: generateButtonClass(vars.compactPrimary),
        translucid: generateButtonClass(vars.translucid),
        inverted: generateButtonClass(vars.inverted),
        icon: buttonUtilityClasses().buttonIcon,
        iconCompact: buttonUtilityClasses().buttonIconCompact,
        text: buttonUtilityClasses().buttonAsText,
        textPrimary: buttonUtilityClasses().buttonAsTextPrimary,
        custom: "",
    };
});

export const buttonUtilityClasses = useThemeCache(() => {
    const vars = buttonGlobalVariables();
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const style = styleFactory("buttonUtils");

    const pushLeft = style("pushLeft", {
        marginRight: important("auto"),
    });

    const pushRight = style("pushRight", {
        marginLeft: important("auto"),
    });

    const iconMixin = (dimension: number): NestedCSSProperties => ({
        ...buttonResetMixin(),
        alignItems: "center",
        display: "flex",
        height: unit(dimension),
        minWidth: unit(dimension),
        width: unit(dimension),
        justifyContent: "center",
        border: "none",
        padding: 0,
        ...allButtonStates({
            hover: {
                color: colorOut(globalVars.mainColors.primary),
            },
            focusNotKeyboard: {
                outline: 0,
                color: colorOut(globalVars.mainColors.secondary),
            },
            focus: {
                color: colorOut(globalVars.mainColors.secondary),
            },
            accessibleFocus: {
                color: colorOut(globalVars.mainColors.secondary),
            },
            active: {
                color: colorOut(globalVars.mainColors.secondary),
            },
        }),
    });

    const buttonIcon = style("icon", iconMixin(formElementVars.sizing.height));

    const buttonIconCompact = style("iconCompact", iconMixin(vars.sizing.compactHeight));

    const asTextStyles: NestedCSSProperties = {
        ...buttonResetMixin(),
        minWidth: important(0),
        padding: 0,
        overflow: "hidden",
        textAlign: "left",
        lineHeight: globalVars.lineHeights.base,
        fontWeight: globalVars.fonts.weights.semiBold,
    };

    const buttonAsText = style("asText", asTextStyles, {
        color: "inherit",
        $nest: {
            "&:not(.focus-visible)": {
                outline: 0,
            },
            "&:focus, &:active, &:hover": {
                color: colorOut(globalVars.mainColors.secondary),
            },
        },
    });

    const buttonAsTextPrimary = style("asTextPrimary", asTextStyles, {
        color: colorOut(globalVars.mainColors.primary),
        $nest: {
            "&:not(.focus-visible)": {
                outline: 0,
            },
            "&:hover, &:focus, &:active": {
                color: colorOut(globalVars.mainColors.secondary),
            },
        },
    });

    return {
        pushLeft,
        buttonAsText,
        buttonAsTextPrimary,
        pushRight,
        buttonIconCompact,
        buttonIcon,
    };
});

export const buttonLoaderClasses = (buttonType: ButtonTypes) => {
    const globalVars = globalVariables();
    const flexUtils = flexHelper();
    const style = styleFactory("buttonLoader");
    const buttonVars = buttonVariables();
    let typeVars;

    switch (buttonType) {
        case ButtonTypes.PRIMARY:
            typeVars = buttonVars.primary;
            break;
        default:
            typeVars = buttonVars.standard;
            break;
    }

    const root = style({
        ...flexUtils.middle(),
        padding: unit(4),
        height: percent(100),
        width: percent(100),
        $nest: {
            "&:after": spinnerLoader({
                color: typeVars.spinnerColor || (globalVars.mainColors.primary as any),
                dimensions: 20,
            }),
        },
    });
    return { root };
};
