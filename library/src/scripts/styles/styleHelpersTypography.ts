/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { important, percent, px } from "csx";
import { ColorValues, paddings, unit } from "@library/styles/styleHelpers";
import {
    FontFamilyProperty,
    FontSizeProperty,
    FontWeightProperty,
    LineHeightProperty,
    MaxWidthProperty,
    OverflowXProperty,
    TextOverflowProperty,
    TextShadowProperty,
    TextTransformProperty,
    WhiteSpaceProperty,
    TextAlignProperty,
    LetterSpacingProperty,
    OverflowBlockProperty,
} from "csstype";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { colorOut } from "@library/styles/styleHelpersColors";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { paddingOffsetBasedOnBorderRadius } from "@library/forms/paddingOffsetFromBorderRadius";

export const fontFallbacks = [
    "-apple-system",
    "BlinkMacSystemFont",
    "HelveticaNeue-Light",
    "Segoe UI",
    "Helvetica Neue",
    "Helvetica",
    "Raleway",
    "Arial",
    "sans-serif",
    "Apple Color Emoji",
    "Segoe UI Emoji",
    "Segoe UI Symbol",
];

export const monoFallbacks = [
    "Consolas",
    "Andale Mono WT",
    "Andale Mono",
    "Lucida Console",
    "Lucida Sans Typewriter",
    "DejaVu Sans Mono",
    "Bitstream Vera Sans Mono",
    "Liberation Mono",
    "Nimbus Mono L",
    "Monaco",
    "Courier New",
    "Courier",
    "monospace",
];

export function fontFamilyWithDefaults(fontFamilies: string[], options: { isMonospaced?: boolean } = {}): string {
    return fontFamilies
        .concat(options.isMonospaced ? monoFallbacks : fontFallbacks)
        .map((font) => (font.includes(" ") && !font.includes('"') ? `"${font}"` : font))
        .join(", ");
}

export function inputLineHeight(height: number, paddingTop: number, fullBorderWidth: number) {
    return unit(height - (2 * paddingTop + fullBorderWidth));
}

export const textInputSizingFromSpacing = (fontSize: number, paddingTop: number, fullBorderWidth: number) => {
    return {
        fontSize: unit(fontSize),
        width: percent(100),
        lineHeight: 1.5,
        ...paddings({
            top: unit(paddingTop),
            bottom: unit(paddingTop),
            left: unit(paddingTop * 2),
            right: unit(paddingTop * 2),
        }),
    };
};

export const getVerticalPaddingForTextInput = (height: number, fontSize: number, fullBorderWidth: number) => {
    return (height - fullBorderWidth - fontSize * 1.5) / 2;
};

export const getHorizontalPaddingForTextInput = (height: number, fontSize: number, fullBorderWidth: number) => {
    return getVerticalPaddingForTextInput(height, fontSize, fullBorderWidth) * 2;
};

export const textInputSizingFromFixedHeight = (
    height: number,
    fontSize: number,
    fullBorderWidth: number,
    borderRadius?: number | string,
) => {
    const paddingVertical = getVerticalPaddingForTextInput(height, fontSize, fullBorderWidth);
    const paddingHorizontal = getHorizontalPaddingForTextInput(height, fontSize, fullBorderWidth);

    const formElementVars = formElementsVariables();

    const paddingOffsets = paddingOffsetBasedOnBorderRadius({
        radius: borderRadius ?? globalVariables().borderType.formElements.default.radius,
        extraPadding: formElementVars.spacing.fullBorderRadius.extraHorizontalPadding,
        height: height,
    });

    return {
        fontSize: unit(fontSize),
        width: percent(100),
        lineHeight: 1.5,
        minHeight: unit(height),
        ...paddings({
            vertical: unit(px(paddingVertical)),
            left: px(paddingHorizontal + paddingOffsets.left ?? 0),
            right: px(paddingHorizontal + paddingOffsets.right ?? 0),
        }),
    };
};

export interface IFont {
    color?: ColorValues;
    size?: FontSizeProperty<TLength>;
    weight?: FontWeightProperty | number;
    lineHeight?: LineHeightProperty<TLength>;
    shadow?: TextShadowProperty;
    align?: TextAlignProperty;
    family?: FontFamilyProperty[];
    transform?: TextTransformProperty;
    letterSpacing?: LetterSpacingProperty<TLength>;
}

export const EMPTY_FONTS: IFont = {
    color: undefined,
    size: undefined,
    weight: undefined,
    lineHeight: undefined,
    shadow: undefined,
    align: undefined,
    family: undefined,
    transform: undefined,
    letterSpacing: undefined,
};

export const fonts = (props: IFont): NestedCSSProperties => {
    const globalVars = globalVariables();
    const fontSize = props.size !== undefined ? unit(props.size) : undefined;
    const fontWeight = props.weight;
    const color = props.color !== undefined ? colorOut(props.color) : undefined;
    const lineHeight = props.lineHeight;
    const textAlign = props.align !== undefined ? props.align : undefined;
    const textShadow = props.shadow !== undefined ? props.shadow : undefined;
    const fontFamily = props.family !== undefined ? fontFamilyWithDefaults(props.family) : undefined;
    const textTransform = props.transform !== undefined ? props.transform : undefined;
    const letterSpacing = props.letterSpacing !== undefined ? props.letterSpacing : undefined;

    return {
        color,
        fontSize,
        fontWeight,
        lineHeight,
        textAlign,
        textShadow,
        fontFamily,
        textTransform,
        letterSpacing,
    };
};

// must be nested
export const placeholderStyles = (styles: NestedCSSProperties) => {
    return {
        "&::-webkit-input-placeholder": {
            $unique: true,
            ...styles,
        },
        "&::-moz-placeholder": {
            $unique: true,
            ...styles,
        },
        "&::-ms-input-placeholder": {
            $unique: true,
            ...styles,
        },
    };
};

export const autoFillReset = (fg: ColorValues, bg: ColorValues) => {
    return {
        "&&&:-webkit-autofill, &&&&:-webkit-autofill:hover, &&&&:-webkit-autofill:focus": {
            ["-webkit-text-fill-color"]: important(colorOut(fg) as string),
            ["-webkit-box-shadow"]: important(`0 0 0px 1000px ${colorOut(bg)} inset`),
            ["transition"]: important(`background-color 5000s ease-in-out 0s`),
        },
        "&&&:-webkit-autofill": {
            fontSize: important("inherit"),
        },
    };
};

export const singleLineEllipsis = () => {
    return {
        whiteSpace: "nowrap" as WhiteSpaceProperty,
        textOverflow: "ellipsis" as TextOverflowProperty,
        overflow: "hidden" as OverflowXProperty,
        maxWidth: percent(100) as MaxWidthProperty<TLength>,
    };
};

export const longWordEllipsis = (): NestedCSSProperties => {
    return {
        textOverflow: "ellipsis",
        overflowX: "hidden",
        maxWidth: percent(100),
    };
};
