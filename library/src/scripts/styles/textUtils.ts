/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { em, quote } from "csx";
import { NestedCSSSelectors, TLength, NestedCSSProperties } from "typestyle/lib/types";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, negative, unit } from "@library/styles/styleHelpers";

/**
 * Many fonts don't set the capital letter to take the whole line height. This mixin is used to line up the top of the Text with the top of the container.
 *
 * @see https://medium.com/codyhouse/line-height-crop-a-simple-css-formula-to-remove-top-space-from-your-text-9c3de06d7c6f
 *
 * @param nestedStyles - Any aditionnal styles to add in the "$nest" key
 * @param letterToLineHeightRatio - The ratio is a value from 0 to 1 to set how much of
 *      the line height the capital takes compared to the line height
 *      Example, if it takes 3/4 of the line height, set .75
 * @param verticalOffset - Sometimes the base line isn't centered,
 *      0 remove from top, .5 use font default, 1 remove from bottom
 */
export function lineHeightAdjustment(
    nestedStyles?: NestedCSSProperties,
    letterToLineHeightRatio?: number,
    verticalOffset?: number,
) {
    const globalVars = globalVariables();
    const vOffset = verticalOffset ? verticalOffset : globalVars.fonts.alignment.headings.verticalOffset; // for now we only support one font, but if we add more than one, we'll need to distinguish between them
    const letterRatio = letterToLineHeightRatio
        ? letterToLineHeightRatio
        : globalVars.fonts.alignment.headings.capitalLetterRatio; // for now we only support one font, but if we add more than one, we'll need to distinguish between them
    enum Position {
        BEFORE = "before",
        AFTER = "after",
    }

    /**
     * Calculate the actual margin values.
     */
    const calculateOffset = (type: Position) => {
        const isBefore = type === Position.BEFORE;
        const offset = isBefore ? vOffset : 1 - vOffset!; // If the text is not centered in the line, we need to cheat it vertically
        const emptySpace = 1 - letterRatio; // This is the pixel value of the dead space in the line from top and/or bottom of line
        const margin = emptySpace * offset!; // Splits the empty space between top and bottom. .5 gives you even amount on both sides.
        const verticalMargin = em(emptySpace < 1 ? negative(margin) : margin);

        if (verticalMargin === 0) {
            return null;
        } else {
            return {
                content: quote(""),
                display: "block",
                position: "relative",
                height: 0,
                width: 0,
                ...margins({
                    top: isBefore ? verticalMargin : undefined,
                    bottom: !isBefore ? verticalMargin : undefined,
                }),
            };
        }
    };
    const result = nestedStyles ? nestedStyles : {};

    const before = calculateOffset(Position.BEFORE);
    if (before) {
        result["&::before"] = before;
    }

    const after = calculateOffset(Position.AFTER);
    if (after) {
        result["&::after"] = after;
    }

    return result as NestedCSSSelectors;
}

export function defaultHyphenation() {
    const vars = globalVariables().userContentHyphenation;
    return {
        "-ms-hyphens": "auto",
        "-webkit-hyphens": "auto",
        hyphens: "auto",
        /* legacy properties */
        "-webkit-hyphenate-limit-before": vars.minimumCharactersBeforeBreak,
        "-webkit-hyphenate-limit-after": vars.minimumCharactersAfterBreak,
        /* current proposal */
        "-moz-hyphenate-limit-chars": `${vars.minimumCharactersToHyphenate} ${vars.minimumCharactersBeforeBreak} ${
            vars.minimumCharactersAfterBreak
        }` /* not yet supported */,
        "-webkit-hyphenate-limit-chars": `${vars.minimumCharactersToHyphenate} ${vars.minimumCharactersBeforeBreak} ${
            vars.minimumCharactersAfterBreak
        }` /* not yet supported */,
        "-ms-hyphenate-limit-chars": `${vars.minimumCharactersToHyphenate} ${vars.minimumCharactersBeforeBreak} ${
            vars.minimumCharactersAfterBreak
        }`,
        "hyphenate-limit-chars": `${vars.minimumCharactersToHyphenate} ${vars.minimumCharactersBeforeBreak} ${
            vars.minimumCharactersAfterBreak
        }`,
        // Maximum consecutive lines to have hyphenation
        "-ms-hyphenate-limit-lines": vars.maximumConsecutiveBrokenLines,
        "-webkit-hyphenate-limit-lines": vars.maximumConsecutiveBrokenLines,
        "hyphenate-limit-lines": vars.maximumConsecutiveBrokenLines,
        // Limit "zone" to hyphenate
        "hyphenate-limit-zone": unit(vars.hyphenationZone),
    };
}
