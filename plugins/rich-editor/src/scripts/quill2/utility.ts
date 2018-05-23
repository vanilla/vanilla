/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Emitter from "quill/core/emitter";
import Quill, { RangeStatic, Blot, Container, BoundsStatic } from "quill/core";
import Delta from "quill-delta";
import Parchment from "parchment";
import LineBlot from "./blots/abstract/LineBlot";
import TextBlot from "quill/blots/text";
import MentionComboBoxBlot from "./blots/embeds/MentionComboBoxBlot";
import { matchAtMention } from "@dashboard/utility";

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
    endRange?: RangeStatic,
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
export function rangeContainsBlot(quill: Quill, range: RangeStatic, blotConstructor: any): boolean {
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
    quill: Quill,
    range: RangeStatic,
    blotConstructor: {
        new (): T;
    },
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
        quill.formatText(finalRange.index, finalRange.length, "link", false, Emitter.sources.USER);
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
export function closeEditorFlyouts(firingKey?: string) {
    const event = new CustomEvent(CLOSE_FLYOUT_EVENT, {
        detail: {
            firingKey,
        },
    });

    document.dispatchEvent(event);
}

/**
 * Determine if a Blot is the first Blot in the scroll (or first through descendant blots).
 *
 * @example Both of the following are valid.
 *
 * Scroll -> Blot
 * Scroll -> Container -> Container -> Container -> Blot
 *
 * @param blot - the blot to check.
 * @param quill - Your quill instance.
 *
 * @returns Whether or not the blot is in the first position.
 */
export function isBlotFirstInScroll(blot: Blot, quill: Quill) {
    const isFirstBlotInBlot = (childBlot, parentBlot) => {
        // Bail out if there are not more children.
        if (!parentBlot.children || !parentBlot.children.head) {
            return false;
        }

        // We found our match.
        if (childBlot === parentBlot.children.head) {
            return true;
        }

        // Recurse through children.
        return isFirstBlotInBlot(childBlot, parentBlot.children.head);
    };

    return isFirstBlotInBlot(blot, quill.scroll);
}

/**
 * Strips the formatting from the first blot in your quill instance.
 *
 * @param quill - Your quill instance
 */
export function stripFormattingFromFirstBlot(quill: Quill) {
    const [firstBlot] = quill.getLine(0);
    const blotName = (firstBlot.constructor as any).blotName;

    const delta = new Delta().retain(firstBlot.length(), { [blotName]: false });
    quill.updateContents(delta, Emitter.sources.USER);
}

/**
 * Normalize blots to what we consider a top level block.
 *
 * @param blot - The blot to normalize.
 *
 * Currently this means:
 * - LineBlot -> WrapperBlot
 */
export function normalizeBlotIntoBlock(blot: Blot): Blot {
    if ((blot as any).getWrapper) {
        return (blot as any).getWrapper(true);
    } else {
        return blot;
    }
}

/**
 * Insert a new line at the end of the current Blot and trim excess newlines.
 *
 * @param range - The range that was altered.
 * @param deleteAmount - The amount of lines to trim.
 */
export function insertNewLineAfterBlotAndTrim(quill, range: RangeStatic, deleteAmount = 1) {
    const [line, offset] = quill.getLine(range.index);

    const newBlot = Parchment.create("block", "");
    const thisBlot = line;

    const nextBlot = thisBlot.next;
    newBlot.insertInto(quill.scroll, nextBlot);

    // Now we need to clean up that extra newline.
    const positionUpToPreviousNewline = range.index + line.length() - offset;
    const deleteDelta = new Delta().retain(positionUpToPreviousNewline - deleteAmount).delete(deleteAmount);
    quill.updateContents(deleteDelta, Emitter.sources.USER);
    quill.setSelection(positionUpToPreviousNewline - deleteAmount, Emitter.sources.USER);
}

/**
 * Insert a newline at the end of the scroll. This is done through a delta like this because otherwise it gets optimized away :(
 *
 * @param quill - The quill instance.
 */
export function insertNewLineAtEndOfScroll(quill: Quill) {
    // const index = quill.
    const newContents = [
        ...(quill.getContents().ops || []),
        {
            insert: "\n",
        },
    ];
    quill.setContents(newContents);
    quill.setSelection(quill.scroll.length(), 0);
}

/**
 * Insert a newline at the start of the scroll. This is done through a delta like this because otherwise it gets optimized away :(
 *
 * @param quill - The quill instance.
 */
export function insertNewLineAtStartOfScroll(quill: Quill) {
    const newContents = [
        {
            insert: "\n",
        },
        ...(quill.getContents().ops || []),
    ];
    quill.setContents(newContents);
    quill.setSelection(0, 0);
}

/**
 * Get a Blot at a given index.
 *
 * @param quill - The Quill instance.
 * @param index - The index to look at.
 * @param blotClass - Optionally a blot class to filter by.
 */
export function getBlotAtIndex<T extends Blot>(
    quill: Quill,
    index: number,
    blotClass?: { new (value?: any): T },
): T | null {
    const condition = blotClass ? blot => blot instanceof blotClass : blot => true;
    return quill.scroll.descendant(condition, index)[0] as T;
}

const MIN_MENTION_LENGTH = 1;

/**
 * Get the range of text to convert to a mention.
 *
 * @param quill - A quill instance.
 * @param currentIndex - The current position in the document..
 *
 * @returns A range if a mention was matched, or null if one was not.
 */
export function getMentionRange(
    quill: Quill,
    currentIndex?: number,
    ignoreTrailingNewline = false,
): RangeStatic | null {
    if (!currentIndex) {
        currentIndex = quill.getSelection().index;
    }

    // Get details about our current leaf (likely a TextBlot).
    // This breaks the text to search every time there is a different DOM Node. Eg. A format, link, line break.
    const [leaf] = quill.getLeaf(currentIndex);
    const leafOffset = leaf.offset(quill.scroll);
    const length = currentIndex - leafOffset;
    const leafContentBeforeCursor = quill.getText(leafOffset, length);

    // See if the leaf's content contains an `@`.
    const leafAtSignIndex = leafContentBeforeCursor.lastIndexOf("@");
    if (leafAtSignIndex === -1) {
        return null;
    }
    const mentionIndex = leafOffset + leafAtSignIndex;
    let potentialMention = leafContentBeforeCursor.substring(leafAtSignIndex);
    if (ignoreTrailingNewline) {
        potentialMention = potentialMention.replace("\n", "");
    }

    const usernameLength = potentialMention.length - 1;
    const meetsLengthRequirements = usernameLength >= MIN_MENTION_LENGTH;
    if (!meetsLengthRequirements) {
        return null;
    }

    const isValidMention = matchAtMention(potentialMention);
    if (!isValidMention) {
        return null;
    }

    return {
        index: mentionIndex,
        length: potentialMention.length,
    };
}
