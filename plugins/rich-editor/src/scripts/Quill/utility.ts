/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Emitter from "quill/core/emitter";
import Parchment from "parchment";
import WrapperBlot from "./Blots/Abstract/WrapperBlot";
import { RangeStatic, Quill as QuillType, Blot, BlotConstructor } from "quill/core";

/**
 * @typedef {Object} BoundaryStatic
 * @property {number} start
 * @property {number} end
 */

interface IBoundary {
    start: number;
    end: number;
}

/**
 * Convert a range to start/end from index/length.
 *
 * @param range - The range to convert.
 *
 * @returns The converted boundary.
 */
export function convertRangeToBoundary(range?: RangeStatic): IBoundary | undefined {
    if (!range) {
        return;
    }

    return {
        start: range.index,
        end: range.index + range.length - 1,
    };
}

/**
 * Convert a boundary into a range.
 *
 * @param boundary - The boundary to convert.
 *
 * @returns The converted range.
 */
export function convertBoundaryToRange(boundary?: IBoundary): RangeStatic | undefined {
    if (!boundary) {
        return;
    }

    return {
        index: boundary.start,
        length: boundary.end - boundary.start + 1,
    };
}

/**
 * Extend a quill range backwards and forwards to the start/end of sub-ranges.
 *
 * @param range - The range to expand.
 * @param startRange - The range to extend the beginning with.
 * @param endRange - The range to extend the end with.
 *
 * @returns The expanded range.
 */
export function expandRange(
    range: RangeStatic,
    startRange?: RangeStatic,
    endRange?: RangeStatic
): RangeStatic | undefined {
    // Convert everything to start/end instead of index/length.
    const boundary = convertRangeToBoundary(range);
    const startBoundary = convertRangeToBoundary(startRange);
    const endBoundary = convertRangeToBoundary(endRange);

    if (boundary && startBoundary && startBoundary.start < boundary.start) {
        boundary.start = startBoundary.start;
    }

    if (boundary && endBoundary && endBoundary.end > boundary.end) {
        boundary.end = endBoundary.end;
    }

    return convertBoundaryToRange(boundary);
}

/**
 * Check if a given range contains a blot of a certain type. Could be more than one.
 *
 * @param quill - A quill instance.
 * @param range - The range to check.
 * @param blotConstructor - A class constructor for a blot.
 */
export function rangeContainsBlot(quill: QuillType, range: RangeStatic, blotConstructor: any): boolean {
    const blots = quill.scroll.descendants(blotConstructor, range.index, range.length);
    return blots.length > 0;
}

/**
 * Format (or unformat) all blots in a given range. Will fully unformat a link even if the link is not entirely
 * inside of the current selection.
 *
 * @param quill - A quill instance.
 * @param range - The range to check.
 * @param blotConstructor - A class constructor for a blot.
 */
export function disableAllBlotsInRange<T extends Blot>(
    quill: QuillType,
    range: RangeStatic,
    blotConstructor: {
        new(): T;
    }
) {
    const currentBlots = quill.scroll.descendants(blotConstructor, range.index, range.length);
    const firstBlot = currentBlots[0];
    const lastBlot = currentBlots[currentBlots.length - 1];

    const startRange = firstBlot && {
        index: firstBlot.offset(quill.scroll),
        length: firstBlot.length(),
    };

    const endRange = lastBlot && {
        index: lastBlot.offset(quill.scroll),
        length: lastBlot.length(),
    };
    const finalRange = expandRange(range, startRange, endRange);

    if (finalRange) {
    quill.formatText(finalRange.index, finalRange.length, 'link', false, Emitter.sources.USER);

    }
}

export const CLOSE_FLYOUT_EVENT = "editor:close-flyouts";

/**
 * Fires an event to close the editor flyouts.
 *
 * @todo replace this with a redux store.
 *
 * @param firingKey - A key to fire the event with. This will be attached to the event so that you do some
 * filtering when setting up you listeners.
 */
export function closeEditorFlyouts(firingKey: string) {
    const event = new CustomEvent(CLOSE_FLYOUT_EVENT, {
        detail: {
            firingKey,
        },
    });

    document.dispatchEvent(event);
}
