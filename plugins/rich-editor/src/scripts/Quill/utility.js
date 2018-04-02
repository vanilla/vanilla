/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Emitter from "quill/core/emitter";
import Parchment from "parchment";
import WrapperBlot from "./Blots/Abstract/WrapperBlot";

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
 * Higher-order function to create a "wrapped" blot.
 *
 * Takes an existing Blot class and implements methods necessary to properly instantiate and cleanup it's parent Blot.
 * the passed Blot class must implement the static property parentName, which should reference a register Blot that is
 * and instance of WrapperBlot.
 *
 * @param {typeof Blot} BlotConstructor
 */
export function wrappedBlot(BlotConstructor) {
    return class extends BlotConstructor {

        constructor(domNode) {
            super(domNode);

            if (!this.constructor.parentName) {
                throw new Error("Attempted to instantiate wrapped Blot without setting static value parentName");
            }
        }

        attach() {
            super.attach();
            if (this.parent.constructor.blotName !== this.statics.parentName) {
                const Wrapper = Parchment.create(this.statics.parentName);

                if (!(Wrapper instanceof WrapperBlot)) {
                    throw new Error("The provided static parentName did not instantiate an instance of a WrapperBlot.");
                }

                this.wrap(Wrapper);
            }
        }

        /**
         * If this is the only child blot we want to delete the parent with it.
         */
        remove() {
            if (this.prev == null && this.next == null) {
                this.parent.remove();
            } else {
                super.remove();
            }
        }


        /**
         * Delete this blot it has no children. Wrap it if it doesn't have it's proper parent name.
         *
         * @param {Object} context - A shared context that is passed through all updated Blots.
         */
        optimize(context) {
            super.optimize(context);
            if (this.children.length === 0) {
                this.remove();
            }
        }

        /**
         * Replace this blot with another blot.
         *
         * @param {string} name - The name of the replacement Blot.
         * @param {any} value - The value for the replacement Blot.
         */
        replaceWith(name, value) {
            const topLevelWrapper = this.getWrapperBlot();
            const immediateWrapper = this.parent;

            immediateWrapper.children.forEach(child => {
                child.replaceWithIntoScroll(name, value, topLevelWrapper);
            });
            topLevelWrapper.remove();
        }

        /**
         * Replace this ContainerBlot with another one.
         *
         * Then attach that new Blot to the scroll in before the passed insertBefore Blot.
         * This is needed because we a normal replaceWith doesn't work (cyclicly recreates it's parents).
         *
         * @param {string} name - The name of the Blot to replace this one with.
         * @param {string} value - The initial value of the new blot.
         * @param {Blot} insertBefore - The Blot to insert this blot before in the ScrollBlot.
         */
        replaceWithIntoScroll(name, value, insertBefore) {
            const newBlot = Parchment.create(name, value);
            this.moveChildren(newBlot);

            newBlot.insertInto(this.scroll, insertBefore);
        }
    };
}
