/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import tabbable from "tabbable";
import { logError } from "@vanilla/utils";

/**
 * A class for handling tabbing inside of a container with various exclusions.
 *
 * The goal is here is to be able to programatically implement various tabbing behaviours
 * required for accessibility.
 */
export class TabHandler {
    private tabbableElements: HTMLElement[];

    /**
     * Construct the handler. Don't be afraid to construct multiple of these.
     *
     * The elements in a particular TabHandler are very moment specific.
     * If the DOM changes underneath it it will likely no longer be valid.
     *
     * @param root - The root element to look in.
     * @param excludedElements - Elements to ignore.
     * @param excludedRoots - These element's children will be ignored.
     */
    public constructor(
        root: Element = document.documentElement!,
        private excludedElements: Element[] = [],
        private excludedRoots: Element[] = [],
    ) {
        this.tabbableElements = tabbable(root);
    }

    /**
     * Get the next tabbable item within a given tabindex.
     *
     * WARNING: Performance can be poor if you pass many excluded roots and do not
     * sufficiently narrow the tree your are looking in.
     *
     * @param from - The currently focused element.
     * @param reverse - True to get the previous element instead.
     * @param allowLooping - Whether or not the focus should loop around from beginning <-> end.
     */
    public getNext(
        from: Element | null = document.activeElement,
        reverse: boolean = false,
        allowLooping: boolean = true,
    ): HTMLElement | null {
        if (!(from instanceof HTMLElement)) {
            logError("Unable to tab to next element, `fromElement` given is not valid: ", from);
            return null;
        }
        const tabbables = this.tabbableElements.filter(this.createExcludeFilterWithExemption(from));
        const currentTabIndex = tabbables.indexOf(from);

        if (currentTabIndex < 0) {
            return null;
        }

        let targetIndex = reverse ? currentTabIndex - 1 : currentTabIndex + 1;

        if (allowLooping) {
            // Loop over the beginning and ends
            if (targetIndex < 0) {
                targetIndex = tabbables.length - 1;
            } else if (targetIndex >= tabbables.length) {
                targetIndex = 0;
            }
        }

        return tabbables[targetIndex] || null;
    }

    /**
     * Get all.
     */
    public getAll(from: Element | null = document.activeElement) {
        if (!(from instanceof HTMLElement)) {
            logError("Unable to tab to next element, `fromElement` given is not valid: ", from);
            return null;
        }
        return this.tabbableElements.filter(this.createExcludeFilterWithExemption(from));
    }

    /**
     * Get the first focusable element.
     */
    public getInitial(): HTMLElement | null {
        const tabbables = this.tabbableElements.filter(this.filterAllExcluded);
        if (tabbables.length > 0) {
            return tabbables[0];
        } else {
            return null;
        }
    }
    /**
     * Get the last focusable element.
     */
    public getLast(): HTMLElement | null {
        const tabbables = this.tabbableElements.filter(this.filterAllExcluded);
        if (tabbables.length > 0) {
            return tabbables[tabbables.length - 1];
        } else {
            return null;
        }
    }

    /**
     * Filter out all excluded elements. Allows 1 element and its parents to be exempted.
     *
     * The exemption is necessary so we can find our place
     * if the focus is already in an item that we are trying to exclude.
     *
     * @returns A filter for use with [].filter()
     */
    private createExcludeFilterWithExemption = (exemption: Element) => {
        return (elementToFilter: Element): boolean => {
            const elementIsActiveOrChildOfActive = exemption === elementToFilter || elementToFilter.contains(exemption);

            if (!elementIsActiveOrChildOfActive) {
                if (this.excludedElements.includes(elementToFilter)) {
                    return false;
                }
                for (const excludedRoot of this.excludedRoots) {
                    if (excludedRoot !== elementToFilter && excludedRoot.contains(elementToFilter)) {
                        return false;
                    }
                }
            }

            return true;
        };
    };

    /**
     * A filter for use with [].filter().
     *
     * Filters out all excluded elements and roots.
     */
    private filterAllExcluded = (elementToFilter: Element): boolean => {
        if (this.excludedElements.includes(elementToFilter)) {
            return false;
        }
        for (const excludedRoot of this.excludedRoots) {
            if (excludedRoot !== elementToFilter && excludedRoot.contains(elementToFilter)) {
                return false;
            }
        }

        return true;
    };
}
