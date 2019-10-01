/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Add the hidden class and aria-hidden attribute to an Element.
 *
 * @param element - The DOM Element to modify.
 */
export function hideElement(element: Element) {
    element.classList.add("u-isHidden");
    element.setAttribute("aria-hidden", "true");
}

/**
 * Remove the hidden class and aria-hidden attribute to an Element.
 *
 * @param element - The DOM Element to modify.
 */
export function unhideElement(element: Element) {
    element.classList.remove("u-isHidden");
    element.removeAttribute("aria-hidden");
}

/**
 * Check if an element is visible or not.
 *
 * @param element - The element to check.
 *
 * @returns The visibility.
 */
export function elementIsVisible(element: HTMLElement): boolean {
    return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
}

/**
 * Calculate the height of element with simulated margin collapse.
 *
 * This is ideal for getting the calculate height of a fixed number of items. (not the entire parent).
 *
 * It considers:
 * - Element height
 * - Padding
 * - Borders
 * - Margins
 * - Margin collapsing.
 *
 * @param element - The element to measure
 * @param previousBottomMargin - The bottom margin of the previous element. You can use the returned bottom margin from this function to get this.
 */
export function getElementHeight(
    element: Element,
    previousBottomMargin: number = 0,
): {
    height: number;
    bottomMargin: number;
} {
    const height = element.getBoundingClientRect().height;
    const { marginTop, marginBottom } = window.getComputedStyle(element);

    let topHeight = marginTop ? parseInt(marginTop, 10) : 0;
    // Simulate a margin-collapsed height.
    topHeight = Math.max(topHeight - previousBottomMargin, 0);

    const bottomHeight = marginBottom ? parseInt(marginBottom, 10) : 0;
    const finalHeight = height + topHeight + bottomHeight;

    return {
        height: finalHeight,
        bottomMargin: bottomHeight,
    };
}
