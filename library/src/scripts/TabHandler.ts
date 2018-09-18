/**
 * Utilities that have a hard dependency on the DOM.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import tabbable from "tabbable";
import { logError } from "@library/utility";

export default class TabHandler {
    private tabbableElements: HTMLElement[];

    /**
     * @param root - The root element to look in.
     * @param excludedElements - Elements to ignore.
     * @param excludedRoots - These element's children will be ignored.
     */
    public constructor(
        root: Element = document.documentElement,
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
        from: Element = document.activeElement,
        reverse: boolean = false,
        allowLooping: boolean = true,
    ): HTMLElement | null {
        if (!(from instanceof HTMLElement)) {
            logError("Unable to tab to next element, `fromElement` given is not valid: ", from);
            return null;
        }
        const tabbables = this.tabbableElements.filter(this.filterExcludedWithExcemption.bind(this, from));
        const currentTabIndex = this.tabbableElements.indexOf(from);

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

    public getInitial(): HTMLElement | null {
        const tabbables = this.tabbableElements.filter(this.filterAllExcluded);
        if (tabbables.length > 0) {
            return tabbables[0];
        } else {
            return null;
        }
    }

    private filterExcludedWithExcemption = (excemption: Element, elementToFilter: Element): boolean => {
        // We want to excempt items that are the active item or a parent of the active item
        // because otherwise we would not be able to tab away from them.
        const elementIsActiveOrChildOfActive = excemption === elementToFilter || elementToFilter.contains(excemption);

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
