/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

/**
 * @typedef {Object} BoundaryStatic
 * @property {number} start
 * @property {number} end
 */

/**
 * Convert a range to start/end from index/length.
 *
 * @param {RangeStatic} range - The range to convert.
 *
 * @returns {BoundaryStatic|null} - The converted boundary.
 */
export function convertRangeToBoundary(range) {
    if (!range) {
        return null;
    }

    return {
        start: range.index,
        end: range.index + range.length - 1,
    };
}

/**
 * Convert a boundary into a range.
 *
 * @param {BoundaryStatic} boundary - The boundary to convert.
 *
 * @returns {RangeStatic|null} - The converted range.
 */
export function convertBoundaryToRange(boundary) {
    if (!boundary) {
        return null;
    }

    return {
        index: boundary.start,
        length: boundary.end - boundary.start + 1,
    };
}

/**
 * Extend a quill range backwards and forwards to the start/end of sub-ranges.
 *
 * @param {RangeStatic} range - The range to expand.
 * @param {RangeStatic=} startRange - The range to extend the beginning with.
 * @param {RangeStatic=} endRange - The range to extend the end with.
 *
 * @returns {RangeStatic} - The expanded range.
 */
export function expandRange(range, startRange = null, endRange = null) {
    // Convert everything to start/end instead of index/length.
    const boundary = convertRangeToBoundary(range);
    const startBoundary = convertRangeToBoundary(startRange);
    const endBoundary = convertRangeToBoundary(endRange);

    if (startBoundary && startBoundary.start < boundary.start) {
        boundary.start = startBoundary.start;
    }

    if (endBoundary && endBoundary.end > boundary.end) {
        boundary.end = endBoundary.end;
    }

    return convertBoundaryToRange(boundary);
}
