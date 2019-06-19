/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent } from "csx";
import { paddings, unit } from "@library/styles/styleHelpers";
import { ColorValues } from "@library/forms/buttonStyles";
import {
    FontFamilyProperty,
    FontSizeProperty,
    FontWeightProperty,
    LineHeightProperty,
    MaxWidthProperty,
    OverflowXProperty,
    TextAlignLastProperty,
    TextOverflowProperty,
    TextShadowProperty,
    TextTransformProperty,
    WhiteSpaceProperty,
} from "csstype";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { colorOut } from "@library/styles/styleHelpersColors";

const fontFallbacks = [
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

export function fontFamilyWithDefaults(fontFamilies: string[]): string {
    return fontFamilies
        .concat(fontFallbacks)
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

export const textInputSizingFromFixedHeight = (height: number, fontSize: number, fullBorderWidth: number) => {
    const paddingTop = (height - fullBorderWidth - fontSize * 1.5) / 2;
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

export interface IFont {
    color?: ColorValues;
    size?: FontSizeProperty<TLength>;
    weight?: FontWeightProperty | number;
    lineHeight?: LineHeightProperty<TLength>;
    shadow?: TextShadowProperty;
    align?: TextAlignLastProperty;
    family?: FontFamilyProperty[];
    transform?: TextTransformProperty;
}

export const fonts = (props: IFont) => {
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
        } as NestedCSSProperties;
    } else {
        return {} as NestedCSSProperties;
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
