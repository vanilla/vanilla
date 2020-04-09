/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
interface IBookmarkBackgroundProps {
    bookmarked: boolean;
    color: ColorValues | string;
    fill?: ColorValues | string;
    loading?: boolean;
    loadingColor?: ColorValues;
}

/*
    Helper function to get bookmark SVG as a background image.
    Note that the fillColor will default to color if undefine

    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12.733 16.394">
        <path d="M1.05.5H11.683a.55.55,0,0,1,.55.55h0V15.341a.549.549,0,0,1-.9.426L6.714,12a.547.547,0,0,0-.7,0L1.4,15.767a.55.55,0,0,1-.9-.426V1.05A.55.55,0,0,1,1.05.5Z" style="stroke: #555a62"/>
    </svg>
 */
export function bookmarkBackground(props: IBookmarkBackgroundProps) {
    const { bookmarked, color, fill = color, loading = false, loadingColor = fill } = props;

    // This is the "half" background for the loading state.
    const loadingBackground = `%3Cpath d='M11.7,0.5H6.4v11.4c0.1,0,0.2,0,0.3,0.1l4.6,3.8c0.1,0.1,0.2,0.1,0.4,0.1c0.3,0,0.5-0.2,0.5-0.6V1.1C12.2,0.7,12,0.5,11.7,0.5z' style='stroke-width: 0; fill:${colorOut(
        loadingColor,
    )};'/%3E`;

    const mainPath = `%3Cpath style='stroke-width: 1px; fill:${bookmarked ? colorOut(fill) : "none"}; stroke:${colorOut(
        color,
    )};' d='M1.05.5H11.683a.55.55,0,0,1,.55.55h0V15.341a.549.549,0,0,1-.9.426L6.714,12a.547.547,0,0,0-.7,0L1.4,15.767a.55.55,0,0,1-.9-.426V1.05A.55.55,0,0,1,1.05.5z'/%3E`;

    return `"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12.733 16.394' %3E${mainPath}${loading &&
        loadingBackground}%3C/svg%3E"`;
}
