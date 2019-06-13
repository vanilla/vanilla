/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Header from "quill/formats/header";
import { slugify } from "@vanilla/utils";

interface IValue {
    level: 2;
    ref: string;
}

interface IContext {
    headerIDCounts: {
        [id: string]: number;
    };
}

/**
 * Overridden heading blot that keeps a deterministicly calculated reference to its contents.
 */
export default class HeaderBlot extends Header {
    /**
     * Extend the existing header blot creation to allow for extra data saved.
     *
     * @param value - Either the basic header blot format (just the number of the level), or an expanded one with a ref.
     */
    public static create(value: IValue | number) {
        let level: number;
        if (typeof value === "number") {
            level = value;
        } else {
            level = value.level;
        }

        const heading = super.create(level) as Element;
        if (typeof value === "object") {
            heading.setAttribute("data-id", value.ref);
        }
        return heading;
    }

    /**
     * Calculate a "unique" ID for the header.
     * This is deterministic but there can be collisions if headings are identical.
     *
     * @param val
     */
    public static calcUniqueID(val: string): string {
        return encodeURIComponent(slugify(val));
    }

    /**
     * Override built in formats method to return a unique ID in addition to the heading level.
     *
     * @param domNode The element to pull a value out of.
     */
    public static formats(domNode: Element): IValue {
        return {
            level: super.formats(domNode),
            ref: domNode.getAttribute("data-id") || "",
        };
    }

    private static headerCounts = {};

    /**
     * Reset the counters for generating unique IDs.
     */
    public static resetCounters() {
        this.headerCounts = {};
    }

    /**
     * Set a contextually generated ID on the domNode.
     *
     * This will keep a reference to every generated ID to prevent duplicates.
     * Be sure to call the static `resetCounters()` to start fresh.
     */
    public setGeneratedID() {
        let id = HeaderBlot.calcUniqueID(this.domNode.textContent || "");
        let inc: number | null = null;
        if (!HeaderBlot.headerCounts[id]) {
            HeaderBlot.headerCounts[id] = 1;
        } else {
            inc = HeaderBlot.headerCounts[id];
            HeaderBlot.headerCounts[id]++;
        }

        if (inc !== null) {
            id += "-" + inc;
        }
        this.domNode.setAttribute("data-id", id);
    }
}
