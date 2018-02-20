/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Emitter from "quill/core/emitter";
import Container from "quill/blots/container";
import Parchment from "parchment";
import ContentBlockBlot from "./blots/ContentBlockBlot";

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

/**
 * Check if a given range contains a blot of a certain type. Could be more than one.
 *
 * @param {Quill} quill - A quill instance.
 * @param {RangeStatic} range - The range to check.
 * @param {Function} blotConstructor - A class constructor for a blot.
 *
 * @returns {boolean} -
 */
export function rangeContainsBlot(quill, range, blotConstructor) {
    const blots = quill.scroll.descendants(blotConstructor, range.index, range.length);
    return blots.length > 0;
}

/**
 * Format (or unformat) all blots in a given range. Will fully unformat a link even if the link is not entirely
 * inside of the current selection.
 *
 * @param {Quill} quill - A quill instance.
 * @param {RangeStatic} range - The range to check.
 * @param {Function} blotConstructor - A class constructor for a blot.
 */
export function disableAllBlotsInRange(quill, range, blotConstructor) {

    /** @type {Blot[]} */
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

    quill.formatText(finalRange.index, finalRange.length, 'link', false, Emitter.sources.USER);
}

export const CLOSE_FLYOUT_EVENT = "editor:close-flyouts";

/**
 * Fires an event to close the editor flyouts.
 *
 * @param {string} firingKey - A key to fire the event with. This will be attached to the event so that you do some
 * filtering when setting up you listeners.
 */
export function closeEditorFlyouts(firingKey = "") {
    const event = new CustomEvent(CLOSE_FLYOUT_EVENT, {
        detail: {
            firingKey,
        },
    });

    document.dispatchEvent(event);
}

/**
 * Create a new Blot class from a child class.
 *
 * This should basically sit is a wrapper around the child, but the blotName,
 * and className, and tagName, should all be set on this. The parent's class should be used as the formatName.
 *
 * @param {typeof ContentBlockBlot} ChildBlot - A class constructor for Block blot or a child of one.
 *
 * @returns {typeof Container}
 */
export function makeWrapperBlot(ChildBlot) {
    return class extends Container {

        static scope = Parchment.Scope.BLOCK_BLOT;
        static defaultChild = ChildBlot.blotName;
        static allowedChildren = [ChildBlot];

        static create() {
            const domNode = super.create();

            if (this.className) {
                domNode.classList.add(this.className);
            }
            return domNode;
        }

        static formats(domNode) {
            const classMatch = this.className && domNode.classList.contains(this.className);
            const tagMatch = domNode.tagName.toLowerCase() === this.blotName;

            if (this.className ? classMatch && tagMatch : tagMatch) {
                return true;
            }

            return undefined;
        }

        formats() {
            return {
                [this.constructor.blotName]: this.constructor.formats(this.domNode),
            };
        }

        insertBefore(blot, ref) {
            console.log(blot);
            console.log(ChildBlot);
            if (blot instanceof ChildBlot) {
                super.insertBefore(blot, ref);
            } else {
                const index = ref == null ? this.length() : ref.offset(this);
                const after = this.split(index);
                after.parent.insertBefore(blot, after);
            }
        }

        optimize(context) {
            super.optimize(context);
            const prev = this.prev;
            if (prev != null && prev.next === this &&
                prev.statics.blotName === this.statics.blotName &&
                prev.domNode.tagName === this.domNode.tagName) {
                prev.moveChildren(this);
                prev.remove();
            }
        }

        replace(target) {
            if (target.statics.blotName !== this.statics.blotName) {
                const item = Parchment.create(this.statics.defaultChild);
                target.moveChildren(item);
                this.appendChild(item);
            }
            super.replace(target);
        }
    };
}
