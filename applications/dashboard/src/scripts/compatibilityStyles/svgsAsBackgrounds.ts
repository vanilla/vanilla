/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/*
SVG source:


 */

import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";

/*
    Helper function to get bookmark SVG as a background image.
    Note that the fillColor will default to color if undefined.

    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12.733 16.394">
        <path d="M1.05.5H11.683a.55.55,0,0,1,.55.55h0V15.341a.549.549,0,0,1-.9.426L6.714,12a.547.547,0,0,0-.7,0L1.4,15.767a.55.55,0,0,1-.9-.426V1.05A.55.55,0,0,1,1.05.5Z" style="stroke: #555a62"/>
    </svg>
 */
export function bookmarkBackground(bookmarked: boolean, color: ColorValues, fillColor?: ColorValues) {
    const fill = fillColor ?? color;
    const path =
        "M1.05.5H11.683a.55.55,0,0,1,.55.55h0V15.341a.549.549,0,0,1-.9.426L6.714,12a.547.547,0,0,0-.7,0L1.4,15.767a.55.55,0,0,1-.9-.426V1.05A.55.55,0,0,1,1.05.5z";

    return `"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12.733 16.394' %3E%3Cpath style='stroke-width: 1px; fill:${
        bookmarked ? colorOut(fill) : "none"
    }; stroke:${colorOut(color)};' d='${path}'/%3E%3C/svg%3E"`;
}
