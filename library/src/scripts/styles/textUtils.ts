/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { em } from "csx";
import { NestedCSSSelectors, TLength } from "typestyle/lib/types";

/**
 * Many fonts don't set the capital letter to take the whole line height. This mixin is used to line up the top of the Text with the top of the container.
 *
 * @see https://medium.com/codyhouse/line-height-crop-a-simple-css-formula-to-remove-top-space-from-your-text-9c3de06d7c6f
 *
 * @param lineHeight - The line height to work with.
 * @param capitalLetterRatio - The ratio is a value from 0 to 1 to set how much of
 *      the line height the capital takes.
 *      Example, if it takes 3/4 of the line height, set .75
 * @param baseLineOffset - Sometimes the base line isn't centered,
 *      0 remove from top, .5 use font default, 1 remove from bottom
 * @param bottomOffset - Also adjust bottom margin
 */
export function lineHeightAdjustment(
    lineHeight: number,
    baseLineOffset: number = 0.5,
    bottomOffset = false,
): NestedCSSSelectors {
    const capitalLetterRatio = 0.91;

    /**
     * Calculate the actual margin values.
     */
    const calculate = (type: "before" | "after"): TLength => {
        const ratio = type === "after" ? baseLineOffset : 1 - baseLineOffset;
        const emValue = capitalLetterRatio - lineHeight * ratio;
        return em(emValue);
    };

    const result: NestedCSSSelectors = {
        "&::before, &::after": {
            content: "''",
            display: "block",
            height: 0,
            width: 0,
        },
        "&::before": {
            marginTop: calculate("before"),
        },
    };

    if (bottomOffset) {
        result["&::after"] = {
            marginBottom: calculate("after"),
        };
    }

    return result;
}
