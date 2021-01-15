/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper, important, percent, px } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { MaxWidthProperty, OverflowXProperty, TextOverflowProperty, WhiteSpaceProperty } from "csstype";
import { CSSObject } from "@emotion/css";
import { TLength } from "@library/styles/styleShim";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { paddingOffsetBasedOnBorderRadius } from "@library/forms/paddingOffsetFromBorderRadius";
import { EMPTY_SPACING } from "@library/styles/cssUtilsTypes";
import { Mixins } from "@library/styles/Mixins";

export function inputLineHeight(height: number, paddingTop: number, fullBorderWidth: number) {
    return styleUnit(height - (2 * paddingTop + fullBorderWidth));
}

export const textInputSizingFromSpacing = (fontSize: number, paddingTop: number, fullBorderWidth: number) => {
    return {
        fontSize: styleUnit(fontSize),
        width: percent(100),
        lineHeight: 1.5,
        ...Mixins.padding({
            ...EMPTY_SPACING,
            top: styleUnit(paddingTop),
            bottom: styleUnit(paddingTop),
            left: styleUnit(paddingTop * 2),
            right: styleUnit(paddingTop * 2),
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
): CSSObject => {
    const paddingVertical = getVerticalPaddingForTextInput(height, fontSize, fullBorderWidth);
    const paddingHorizontal = getHorizontalPaddingForTextInput(height, fontSize, fullBorderWidth);

    const formElementVars = formElementsVariables();

    const paddingOffsets = paddingOffsetBasedOnBorderRadius({
        radius: borderRadius ?? globalVariables().borderType.formElements.default.radius,
        extraPadding: formElementVars.spacing.fullBorderRadius.extraHorizontalPadding,
        height: height,
    });

    return {
        fontSize: styleUnit(fontSize),
        width: percent(100),
        lineHeight: 1.5,
        minHeight: styleUnit(height),
        ...Mixins.padding({
            vertical: styleUnit(px(paddingVertical)),
            left: px(paddingHorizontal + paddingOffsets.left ?? 0),
            right: px(paddingHorizontal + paddingOffsets.right ?? 0),
        }),
    };
};

// must be nested
export const placeholderStyles = (styles: CSSObject): CSSObject => {
    return {
        "&::-webkit-input-placeholder": {
            ...styles,
        },
        "&::-moz-placeholder": {
            ...styles,
        },
        "&::-ms-input-placeholder": {
            ...styles,
        },
    };
};

export const autoFillReset = (fg?: ColorHelper, bg?: ColorHelper) => {
    return {
        "&&&:-webkit-autofill, &&&&:-webkit-autofill:hover, &&&&:-webkit-autofill:focus": {
            ["-webkit-text-fill-color"]: important(ColorsUtils.colorOut(fg) as string),
            ["-webkit-box-shadow"]: important(`0 0 0px 1000px ${ColorsUtils.colorOut(bg)} inset`),
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

export const longWordEllipsis = (): CSSObject => {
    return {
        textOverflow: "ellipsis",
        overflowX: "hidden",
        maxWidth: percent(100),
    };
};
