/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import {
    IBackground,
    IBorderRadiusOptions,
    IBorderRadiusOutput,
    IBorderStyles,
    IBoxOptions,
    IClickableItemOptions,
    IFont,
    ILinkColorOverwritesWithOptions,
    IMixedBorderStyles,
    IContentBoxes,
    ISimpleBorderStyle,
    ISpacing,
    TLength,
} from "@library/styles/cssUtilsTypes";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorHelper, important, percent, px } from "csx";
import { CSSObject } from "@emotion/css";
import {
    BorderRadiusProperty,
    BorderStyleProperty,
    BorderWidthProperty,
    LeftProperty,
    PositionProperty,
    RightProperty,
    BottomProperty,
} from "csstype";
import merge from "lodash/merge";
import { getValueIfItExists } from "@library/forms/borderStylesCalculator";
import { globalVariables } from "@library/styles/globalStyleVars";
import { getBackgroundImage } from "@library/styles/styleHelpersBackgroundStyling";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { IGlobalBorderStyles } from "@library/styles/cssUtilsTypes";
import { monoFallbacks, fontFallbacks } from "@library/styles/fontFallbacks";
import { Variables } from "@library/styles/Variables";
import { BorderType, singleBorder } from "@library/styles/styleHelpersBorders";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { notEmpty } from "@vanilla/utils";

export class Mixins {
    constructor() {
        throw new Error("Not to be instantiated");
    }

    public static box = (boxOptions: IBoxOptions): CSSObject => {
        let { background, borderType, spacing, border, itemSpacingOnAllItems } = boxOptions;
        const globalVars = globalVariables();

        border = {
            ...globalVariables().borderType.contentBox,
            ...border,
        };

        const boxHasSetPaddings = Object.values(spacing).filter(notEmpty).length > 0;

        const hasBackground = (background.color || background.image) && !background.unsetBackground;

        // We have a clearly defined box of sometype.
        // Anything that makes the box stand out from the background on all side
        // Means we should apply some default behaviours, like paddings, and borderRadius.
        const hasFullOutline = [BorderType.BORDER, BorderType.SHADOW].includes(borderType) || hasBackground;
        if (!boxHasSetPaddings && hasFullOutline) {
            spacing = { horizontal: 16, vertical: borderType === BorderType.SEPARATOR ? 0 : 16 };
        }
        let itemSpacing = boxOptions.itemSpacing || (hasFullOutline ? 16 : 0);
        return {
            // Resets
            padding: 0,
            border: "none",
            boxShadow: "none",
            borderRadius: hasFullOutline ? ((border.radius ?? globalVars.border.radius) as any) : 0,
            background: "none",
            clear: "both",

            // Debugging
            "--border-type": borderType,

            "&:before": {
                display: "none",
            },
            "&:after": {
                display: "none",
            },

            // Apply styles
            ...Mixins.background(background),
            ...Mixins.borderType(borderType, { border }),
            ...Mixins.padding(spacing),
            ...(hasFullOutline || borderType === BorderType.SEPARATOR
                ? {
                      "& .pageBox:first-of-type:before": {
                          // Hide separator
                          display: "none",
                      },
                      "& .pageBox:last-of-type:after": {
                          // Hide separator
                          display: "none",
                      },
                  }
                : {}),
            ...(hasFullOutline
                ? {
                      "& + .pageBox": Mixins.margin({ top: itemSpacing }),
                  }
                : {}),
            ...(itemSpacingOnAllItems ? Mixins.margin({ vertical: itemSpacing }) : {}),
        };
    };

    public static borderType(borderType: BorderType, options?: { border?: IBorderStyles }): CSSObject {
        switch (borderType) {
            case BorderType.BORDER:
                return {
                    ...Mixins.border(options?.border),
                };
            case BorderType.SHADOW:
                return {
                    ...shadowHelper().embed(),
                };
            case BorderType.SEPARATOR:
                return {
                    "&:before": {
                        content: `""`,
                        display: "block",
                        height: 16,
                        width: "calc(100% + 16px)",
                        marginLeft: -8,
                        borderTop: singleBorder(),
                    },
                    "&:after": {
                        content: `""`,
                        display: "block",
                        height: 16,
                        width: "calc(100% + 16px)",
                        marginLeft: -8,
                        borderBottom: singleBorder(),
                    },
                    // & + & doesn't work.
                    // https://github.com/emotion-js/emotion/issues/1922
                    "& + .pageBox:before": {
                        borderTop: "none",
                    },
                };
            case BorderType.NONE:
            default:
                return {};
        }
    }

    private static spacing(property: "margin" | "padding", spacings?: ISpacing): CSSObject {
        if (!spacings) {
            return {};
        }

        const spacingVals: CSSObject = {};

        const propertyLeft = `${property}Left`;
        const propertyRight = `${property}Right`;
        const propertyTop = `${property}Top`;
        const propertyBottom = `${property}Bottom`;

        if (spacings.all !== undefined) {
            spacingVals[propertyTop] = styleUnit(spacings.all);
            spacingVals[propertyRight] = styleUnit(spacings.all);
            spacingVals[propertyBottom] = styleUnit(spacings.all);
            spacingVals[propertyLeft] = styleUnit(spacings.all);
        }

        if (spacings.vertical !== undefined) {
            spacingVals[propertyTop] = styleUnit(spacings.vertical);
            spacingVals[propertyBottom] = styleUnit(spacings.vertical);
        }

        if (spacings.horizontal !== undefined) {
            spacingVals[propertyLeft] = styleUnit(spacings.horizontal);
            spacingVals[propertyRight] = styleUnit(spacings.horizontal);
        }

        if (spacings.top !== undefined) {
            spacingVals[propertyTop] = styleUnit(spacings.top);
        }

        if (spacings.right !== undefined) {
            spacingVals[propertyRight] = styleUnit(spacings.right);
        }

        if (spacings.bottom !== undefined) {
            spacingVals[propertyBottom] = styleUnit(spacings.bottom);
        }

        if (spacings.left !== undefined) {
            spacingVals[propertyLeft] = styleUnit(spacings.left);
        }

        return spacingVals;
    }

    static padding(spacing: ISpacing): CSSObject {
        return Mixins.spacing("padding", spacing);
    }

    static margin(spacing: ISpacing): CSSObject {
        return Mixins.spacing("margin", spacing);
    }

    static font(vars: IFont): CSSObject {
        return {
            color: ColorsUtils.colorOut(vars.color),
            fontSize: vars.size !== undefined ? styleUnit(vars.size) : undefined,
            fontWeight: vars.weight,
            lineHeight: vars.lineHeight,
            textAlign: vars.align,
            textShadow: vars.shadow,
            fontFamily: vars.family ? Mixins.fontFamilyWithDefaults(vars.family) : vars.family,
            textTransform: vars.transform,
            letterSpacing: vars.letterSpacing,
            textDecoration: vars.textDecoration,
        };
    }

    static fontFamilyWithDefaults(fontFamilies: string[], options: { isMonospaced?: boolean } = {}): string {
        return fontFamilies
            .concat(options.isMonospaced ? monoFallbacks : fontFallbacks)
            .map((font) => (font.includes(" ") && !font.includes('"') ? `"${font}"` : font))
            .join(", ");
    }

    private static setAllRadii(radius: BorderRadiusProperty<TLength>, options?: IBorderRadiusOptions) {
        return {
            borderTopRightRadius: styleUnit(radius, options),
            borderBottomRightRadius: styleUnit(radius, options),
            borderBottomLeftRadius: styleUnit(radius, options),
            borderTopLeftRadius: styleUnit(radius, options),
        };
    }

    private static setAllBorders = (
        color: string,
        width: BorderWidthProperty<TLength>,
        style: BorderStyleProperty,
        radius?: IBorderRadiusOutput,
        debug = false as boolean | string,
    ) => {
        const output = {};

        if (color !== undefined) {
            merge(output, {
                borderTopColor: color,
                borderRightColor: color,
                borderBottomColor: color,
                borderLeftColor: color,
            });
        }

        if (width !== undefined) {
            merge(output, {
                borderTopWidth: styleUnit(width),
                borderRightWidth: styleUnit(width),
                borderBottomWidth: styleUnit(width),
                borderLeftWidth: styleUnit(width),
            });
        }

        if (style !== undefined) {
            merge(output, {
                borderTopStyle: style,
                borderRightStyle: style,
                borderBottomStyle: style,
                borderLeftStyle: style,
            });
        }

        if (radius !== undefined && typeof radius !== "object") {
            merge(output, Mixins.setAllRadii(radius));
        }

        return output;
    };

    private static singleBorderStyle(
        borderStyles: ISimpleBorderStyle,
        fallbackVariables: IGlobalBorderStyles = globalVariables().border,
    ) {
        if (!borderStyles) {
            return;
        }
        const { color, width, style } = borderStyles;
        const output: ISimpleBorderStyle = {};
        output.color = color;
        output.width = styleUnit(borderStyles.width ? borderStyles.width : width);
        output.style = borderStyles.style ? borderStyles.style : style;

        if (Object.keys(output).length > 0) {
            return output;
        } else {
            return;
        }
    }

    static border(
        detailedStyles?: IBorderStyles | ISimpleBorderStyle | IMixedBorderStyles,
        options?: {
            fallbackBorderVariables?: IGlobalBorderStyles;
            debug?: boolean | string;
        },
    ): CSSObject {
        const { fallbackBorderVariables = globalVariables().border, debug = false } = options || {};
        const output: CSSObject = {};
        const style = getValueIfItExists(detailedStyles, "style", fallbackBorderVariables.style);
        const color = getValueIfItExists(detailedStyles, "color", fallbackBorderVariables.color).toString();
        const width = getValueIfItExists(detailedStyles, "width", fallbackBorderVariables.width);
        const radius = getValueIfItExists(detailedStyles, "radius", fallbackBorderVariables.radius);
        const defaultsAll = Mixins.setAllBorders(color, width, style, radius, debug);

        merge(output, defaultsAll);

        // Now we are sure to not have simple styles anymore.
        detailedStyles = detailedStyles as IBorderStyles;
        if (!detailedStyles) {
            // @NOTE: color from fallbackBorderVariables needs to be changed to string type
            // @ts-ignore
            detailedStyles = fallbackBorderVariables;
        }

        const all = getValueIfItExists(detailedStyles, "all");
        if (all) {
            const allStyles = Mixins.singleBorderStyle(all, fallbackBorderVariables);
            if (allStyles) {
                output.borderTopWidth = allStyles?.width ?? width;
                output.borderTopStyle = getValueIfItExists(allStyles, "style", style);
                output.borderTopColor = getValueIfItExists(allStyles, "color", color);
                output.borderTopRightRadius = getValueIfItExists(all, "radius", radius);
                output.borderBottomRightRadius = getValueIfItExists(all, "radius", radius);
                output.borderBottomLeftRadius = getValueIfItExists(all, "radius", radius);
                output.borderTopLeftRadius = getValueIfItExists(all, "radius", radius);
            }
        }

        const top = getValueIfItExists(detailedStyles, "top");
        if (top) {
            const topStyles = Mixins.singleBorderStyle(top, fallbackBorderVariables);
            if (topStyles) {
                output.borderTopWidth = getValueIfItExists(topStyles, "width", width);
                output.borderTopStyle = getValueIfItExists(topStyles, "style", style);
                output.borderTopColor = getValueIfItExists(topStyles, "color", color);
                output.borderTopLeftRadius = getValueIfItExists(top, "radius", radius);
                output.borderTopRightRadius = getValueIfItExists(top, "radius", radius);
            }
        }

        const right = getValueIfItExists(detailedStyles, "right");

        if (right) {
            const rightStyles = Mixins.singleBorderStyle(right, fallbackBorderVariables);
            if (rightStyles) {
                output.borderRightWidth = getValueIfItExists(rightStyles, "width", width);
                output.borderRightStyle = getValueIfItExists(rightStyles, "style", style);
                output.borderRightColor = getValueIfItExists(rightStyles, "color", color);

                output.borderBottomRightRadius = getValueIfItExists(right, "radius", radius);
                output.borderTopRightRadius = getValueIfItExists(right, "radius", radius);
            }
        }

        const bottom = getValueIfItExists(detailedStyles, "bottom");
        if (bottom) {
            const bottomStyles = Mixins.singleBorderStyle(bottom, fallbackBorderVariables);
            if (bottomStyles) {
                output.borderBottomWidth = getValueIfItExists(bottomStyles, "width", width);
                output.borderBottomStyle = getValueIfItExists(bottomStyles, "style", style);
                output.borderBottomColor = getValueIfItExists(bottomStyles, "color", color);
                output.borderBottomLeftRadius = getValueIfItExists(bottom, "radius", radius);
                output.borderBottomRightRadius = getValueIfItExists(bottom, "radius", radius);
            }
        }

        const left = getValueIfItExists(detailedStyles, "left");

        if (left) {
            const leftStyles = Mixins.singleBorderStyle(left, fallbackBorderVariables);
            if (leftStyles) {
                output.borderLeftWidth = getValueIfItExists(leftStyles, "width", width);
                output.borderLeftStyle = getValueIfItExists(leftStyles, "style", style);
                output.borderLeftColor = getValueIfItExists(leftStyles, "color", color);
                output.borderBottomLeftRadius = getValueIfItExists(left, "radius", radius);
                output.borderTopLeftRadius = getValueIfItExists(left, "radius", radius);
            }
        }

        return output;
    }

    static background(vars: IBackground): CSSObject {
        const image = getBackgroundImage(vars.image);
        if (vars.unsetBackground) {
            return {
                background: "none",
            };
        }
        return {
            backgroundColor: vars.color ? ColorsUtils.colorOut(vars.color) : undefined,
            backgroundAttachment: vars.attachment,
            backgroundPosition: vars.position || `50% 50%`,
            backgroundRepeat: vars.repeat || "no-repeat",
            backgroundSize: vars.size || "cover",
            backgroundImage: image,
            opacity: vars.opacity,
        };
    }

    static clickable = {
        itemState: (overwriteColors?: ILinkColorOverwritesWithOptions, options?: IClickableItemOptions): CSSObject => {
            const vars = globalVariables();
            const cssProperty = "color";
            const { disableTextDecoration } = options || { disableTextDecoration: false };
            // We want to default to the standard styles and only overwrite what we want/need
            const linkColors = vars.links.colors;

            // @NOTE: color from fallbackBorderVariables needs to be changed to string type
            // @ts-ignore
            const colors = Variables.clickable(overwriteColors ?? {});

            const mergedColors = {
                default: !overwriteColors?.skipDefault
                    ? Mixins.clickable.linkStyleFallbacks(colors.default, undefined, linkColors.default)
                    : undefined,
                hover: Mixins.clickable.linkStyleFallbacks(colors.hover, colors.allStates, linkColors.hover),
                focus: Mixins.clickable.linkStyleFallbacks(colors.focus, colors.allStates, linkColors.focus),
                clickFocus: Mixins.clickable.linkStyleFallbacks(colors.clickFocus, colors.allStates, linkColors.focus),
                keyboardFocus: Mixins.clickable.linkStyleFallbacks(
                    colors.keyboardFocus,
                    colors.allStates,
                    linkColors.keyboardFocus,
                ),
                active: Mixins.clickable.linkStyleFallbacks(colors.active, colors.allStates, linkColors.active),
                visited: Mixins.clickable.linkStyleFallbacks(colors.visited, colors.allStates, linkColors.visited),
            };

            const textDecoration = disableTextDecoration ? important("none") : undefined;

            const styles = {
                default: {
                    [cssProperty]: mergedColors.default?.toString(),
                    textDecoration,
                },
                hover: {
                    [cssProperty]: mergedColors.hover?.toString(),
                    cursor: "pointer",
                    textDecoration,
                },
                focus: {
                    [cssProperty]: mergedColors.focus?.toString(),
                    textDecoration,
                },
                clickFocus: {
                    [cssProperty]: mergedColors.focus?.toString(),
                    textDecoration,
                },
                keyboardFocus: {
                    [cssProperty]: mergedColors.keyboardFocus?.toString(),
                    textDecoration,
                },
                active: {
                    [cssProperty]: mergedColors.active?.toString(),
                    cursor: "pointer",
                    textDecoration,
                },
                visited: mergedColors.visited
                    ? {
                          [cssProperty]: mergedColors.visited?.toString(),
                          textDecoration,
                      }
                    : undefined,
            };

            const final = {
                [cssProperty]: styles.default.color?.toString(),
                "&:visited": styles.visited ?? undefined,
                "&:hover": styles.hover,
                "&:focus, &.isFocused": {
                    ...(styles.focus ?? {}),
                    ...(styles.clickFocus ?? {}),
                },
                "&.focus-visible": {
                    ...(styles.focus ?? {}),
                    ...(styles.keyboardFocus ?? {}),
                },
                "&:active": styles.active,
            };
            // @NOTE: color from fallbackBorderVariables needs to be changed to string type
            // @ts-ignore
            return final;
        },
        linkStyleFallbacks: (
            specificOverwrite?: ColorHelper | string,
            defaultOverwrite?: ColorHelper | string,
            globalDefault?: ColorHelper | string,
        ) => {
            if (specificOverwrite) {
                return specificOverwrite;
            } else if (defaultOverwrite) {
                return defaultOverwrite;
            } else {
                return globalDefault;
            }
        },
    };

    static absolute = {
        topRight: (top: string | number = "0", right: RightProperty<TLength> = px(0)): CSSObject => {
            return {
                position: "absolute" as PositionProperty,
                top: styleUnit(top),
                right: styleUnit(right),
            };
        },
        topLeft: (top: string | number = "0", left: LeftProperty<TLength> = px(0)): CSSObject => {
            return {
                position: "absolute" as PositionProperty,
                top: styleUnit(top),
                left: styleUnit(left),
            };
        },
        bottomRight: (bottom: BottomProperty<TLength> = px(0), right: RightProperty<TLength> = px(0)): CSSObject => {
            return {
                position: "absolute" as PositionProperty,
                bottom: styleUnit(bottom),
                right: styleUnit(right),
            };
        },
        bottomLeft: (bottom: BottomProperty<TLength> = px(0), left: LeftProperty<TLength> = px(0)): CSSObject => {
            return {
                position: "absolute" as PositionProperty,
                bottom: styleUnit(bottom),
                left: styleUnit(left),
            };
        },
        middleOfParent: (shrink: boolean = false): CSSObject => {
            if (shrink) {
                return {
                    position: "absolute" as PositionProperty,
                    display: "inline-block",
                    top: percent(50),
                    left: percent(50),
                    right: "initial",
                    bottom: "initial",
                    transform: "translate(-50%, -50%)",
                };
            } else {
                return {
                    position: "absolute" as PositionProperty,
                    display: "block",
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    maxHeight: percent(100),
                    maxWidth: percent(100),
                    margin: "auto",
                };
            }
        },
        middleLeftOfParent: (left: LeftProperty<TLength> = px(0)): CSSObject => {
            return {
                position: "absolute" as PositionProperty,
                display: "block",
                top: 0,
                left,
                bottom: 0,
                maxHeight: percent(100),
                maxWidth: percent(100),
                margin: "auto 0",
            };
        },
        middleRightOfParent: (right: RightProperty<TLength> = px(0)): CSSObject => {
            return {
                position: "absolute" as PositionProperty,
                display: "block",
                top: 0,
                right,
                bottom: 0,
                maxHeight: percent(100),
                maxWidth: percent(100),
                margin: "auto 0",
            };
        },
        fullSizeOfParent: (): CSSObject => {
            return {
                display: "block",
                position: "absolute" as PositionProperty,
                top: px(0),
                left: px(0),
                width: percent(100),
                height: percent(100),
            };
        },
        srOnly: (): CSSObject => {
            return {
                position: important("absolute"),
                display: important("block"),
                width: important(px(1).toString()),
                height: important(px(1).toString()),
                padding: important(px(0).toString()),
                margin: important(px(-1).toString()),
                overflow: important("hidden"),
                clip: important(`rect(0, 0, 0, 0)`),
                border: important(px(0).toString()),
            };
        },
    };

    static flex = {
        middle: (wrap = false): CSSObject => {
            return {
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                flexWrap: wrap ? "wrap" : "nowrap",
            };
        },
        spaceBetween: (wrap = false): CSSObject => {
            return {
                display: "flex",
                alignItems: "center",
                justifyContent: "space-between",
                flexWrap: wrap ? "wrap" : "nowrap",
            };
        },

        middleLeft: (wrap = false): CSSObject => {
            return {
                display: "flex",
                alignItems: "center",
                justifyContent: "flex-start",
                flexWrap: wrap ? "wrap" : "nowrap",
            };
        },

        inheritHeightClass: useThemeCache(() => {
            const style = styleFactory("inheritHeight");
            return style({
                display: "flex",
                flexDirection: "column",
                flexGrow: 1,
                position: "relative",
            });
        }),
    };
}
