/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Emitter from "quill/core/emitter";
import Quill, { RangeStatic, Blot, DeltaOperation } from "quill/core";
import Delta from "quill-delta";
import { matchAtMention } from "@vanilla/utils";
import uniqueId from "lodash/uniqueId";
import FocusableEmbedBlot from "@rich-editor/quill/blots/abstract/FocusableEmbedBlot";
import BlockBlot from "quill/blots/block";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import { logDebug } from "@vanilla/utils";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import Link from "quill/formats/link";

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
export function rangeContainsBlot(quill: Quill, blotConstructor: any, range: RangeStatic | null = null): boolean {
    if (range === null) {
        range = quill.getSelection();
    }

    if (!range) {
        return false;
    }

    if (range.length > 0) {
        const blots = quill.scroll.descendants(blotConstructor, range.index, range.length);
        return blots.length > 0;
    } else {
        const blot = quill.scroll.descendant(blotConstructor, range.index)[0];
        return !!blot;
    }
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
    blotConstructor:
        | {
              new (): T;
          }
        | typeof Blot,
    range: RangeStatic | null = null,
) {
    if (range === null) {
        range = quill.getSelection()!;
    }

    const currentBlots = quill.scroll.descendants(blotConstructor as any, range.index, range.length) as Blot[];
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

    const newBlot = new BlockBlot(BlockBlot.create(""));
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
    const newLineBlot = new BlockBlot(BlockBlot.create(""));
    quill.scroll.appendChild(newLineBlot);
    quill.update(Quill.sources.USER);
    quill.setSelection(quill.scroll.length(), 0);
}

/**
 * Insert a newline at the start of the scroll. This is done through a delta like this because otherwise it gets optimized away :(
 *
 * @param quill - The quill instance.
 */
export function insertNewLineAtStartOfScroll(quill: Quill) {
    const newLineBlot = new BlockBlot(BlockBlot.create(""));
    quill.scroll.insertBefore(newLineBlot, quill.scroll.children.head!);
    quill.update(Quill.sources.USER);
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
 * @param currentSelection - The current quill selection.
 *
 * @returns A range if a mention was matched, or null if one was not.
 */
export function getMentionRange(quill: Quill, currentSelection: RangeStatic | null): RangeStatic | null {
    // We can't check for focus here. Clicking an item in the mention list causes quill to "lose" focus.

    if (!currentSelection) {
        return null;
    }

    if (currentSelection.length > 0) {
        return null;
    }

    if (
        rangeContainsBlot(quill, CodeBlockBlot, currentSelection) ||
        rangeContainsBlot(quill, CodeBlot, currentSelection) ||
        rangeContainsBlot(quill, Link, currentSelection)
    ) {
        return null;
    }

    // Get details about our current leaf (likely a TextBlot).
    // This breaks the text to search every time there is a different DOM Node. Eg. A format, link, line break.
    const [leaf] = quill.getLeaf(currentSelection.index);
    const leafOffset = leaf.offset(quill.scroll);
    const length = currentSelection.index - leafOffset;
    const leafContentBeforeCursor = quill.getText(leafOffset, length);

    // See if the leaf's content contains an `@`.
    const leafAtSignIndex = leafContentBeforeCursor.lastIndexOf("@");
    if (leafAtSignIndex === -1) {
        return null;
    }
    const mentionIndex = leafOffset + leafAtSignIndex;
    const potentialMention = leafContentBeforeCursor.substring(leafAtSignIndex);

    const usernameLength = potentialMention.length - 1;
    const meetsLengthRequirements = usernameLength >= MIN_MENTION_LENGTH;
    if (!meetsLengthRequirements) {
        return null;
    }

    const isValidMention = matchAtMention(potentialMention, false, false);
    if (!isValidMention) {
        return null;
    }

    return {
        index: mentionIndex,
        length: potentialMention.length,
    };
}

const quillIDMap: Map<Quill, string> = new Map();

/**
 * Generate a unique ID for a quill instance and store it in a Map with that instance.
 *
 * This is useful for generating a string key for a given quill instance.
 *
 * @param quill The quill instance.
 */
export function getIDForQuill(quill: Quill) {
    if (quillIDMap.has(quill)) {
        return quillIDMap.get(quill);
    } else {
        quillIDMap.set(quill, uniqueId("editorInstance"));
        return getIDForQuill(quill);
    }
}

/**
 * Insert a blot into a quill instance at a given index.
 *
 * Why does this need to exist?
 * - The built in `blot.insertAt` method doesn't let you insert a premade blot.
 * - We need to calculate this offset anyways.
 * - Our scope is narrower because we are inserting only at the top level, where a blot is always a block.
 *
 * @param quill - A Quill instance.
 * @param index - The index to insert at.
 * @param blot - A blot already created.
 */
export function insertBlockBlotAt(quill: Quill, index: number, blot: Blot) {
    const line = quill.getLine(index)[0] as Blot;

    // Splitting lines is relative to the line start, the scroll start, so we need to calculate the
    // index within the blot to split at.
    const lineOffset = line.offset(quill.scroll);
    const ref = line.split(index - lineOffset);
    line.parent.insertBefore(blot, ref || undefined);
}

/**
 * Determine if and Embed inside of this class is focused.
 */
export function isEmbedSelected(quill: Quill, selection?: RangeStatic | null) {
    if (!selection) {
        return false;
    }
    const potentialEmbedBlot = getBlotAtIndex(quill, selection.index, FocusableEmbedBlot);
    return !!potentialEmbedBlot;
}

export const SELECTION_UPDATE = "[editor] force selection update";

/**
 * Force a selection update on all quill editors.
 */
export function forceSelectionUpdate() {
    document.dispatchEvent(new CustomEvent(SELECTION_UPDATE));
}

/**
 * Set the quill editor contents.
 *
 * @param quill The quill instance to work on.
 * @param content The delta to set.
 */
export function resetQuillContent(quill: Quill, content: DeltaOperation[]) {
    logDebug("Setting existing content as contents of editor");
    quill.setContents(content);
    // Clear the history so that you can't "undo" your initial content.
    quill.getModule("history").clear();
}
