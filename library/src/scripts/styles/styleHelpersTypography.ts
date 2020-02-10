/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { important, percent } from "csx";
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
} from "csstype";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { colorOut } from "@library/styles/styleHelpersColors";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { Col } from "@jest/types/build/Global";

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

const monoFallbacks = [
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
        .map(font => (font.includes(" ") && !font.includes('"') ? `"${font}"` : font))
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

export const textInputSizingFromFixedHeight = (height: number, fontSize: number, fullBorderWidth: number) => {
    const paddingVertical = getVerticalPaddingForTextInput(height, fontSize, fullBorderWidth);
    const paddingHorizontal = getHorizontalPaddingForTextInput(height, fontSize, fullBorderWidth);
    return {
        fontSize: unit(fontSize),
        width: percent(100),
        lineHeight: 1.5,
        minHeight: unit(formElementsVariables().sizing.height),
        ...paddings({
            vertical: unit(paddingVertical),
            horizontal: unit(paddingHorizontal),
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
};

export const fonts = (props: IFont): NestedCSSProperties => {
    if (props) {
        const fontSize = props.size !== undefined ? unit(props.size) : undefined;
        const fontWeight = props.weight !== undefined ? props.weight : undefined;
        const color = props.color !== undefined ? colorOut(props.color) : undefined;
        const lineHeight = props.lineHeight !== undefined ? props.lineHeight : undefined;
        const textAlign = props.align !== undefined ? props.align : undefined;
        const textShadow = props.shadow !== undefined ? props.shadow : undefined;
        const fontFamily = props.family !== undefined ? fontFamilyWithDefaults(props.family) : undefined;
        const textTransform = props.transform !== undefined ? props.transform : undefined;
        return {
            color,
            fontSize,
            fontWeight,
            lineHeight,
            textAlign,
            textShadow,
            fontFamily,
            textTransform,
        };
    } else {
        return {};
    }
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
        overflowX: "hidden" as OverflowXProperty,
        maxWidth: percent(100) as MaxWidthProperty<TLength>,
    };
};

export const longWordEllipsis = () => {
    return {
        textOverflow: "ellipsis" as TextOverflowProperty,
        overflowX: "hidden" as OverflowXProperty,
        maxWidth: percent(100) as MaxWidthProperty<TLength>,
    };
};
