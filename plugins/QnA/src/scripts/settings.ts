/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Trigger to activate/deactivate child elements
 *
 * @param e event
 */
export const triggerChildElements = (e) => {
    const target = e.target;
    const childElements = document.querySelectorAll("." + target.dataset.children);
    if (target.checked && childElements.length > 0) {
        toggleElementsDisplay(childElements, true);
        toggleElementsEnabled(childElements, true);
    } else {
        toggleElementsDisplay(childElements, false);
        toggleElementsEnabled(childElements, false);
    }
};

/**
 * Toggle display on elements array
 *
 * @param elements array
 * @param display boolean
 */
export const toggleElementsDisplay = (elements, display) => {
    [...elements].forEach((element) => {
        if (display) {
            element.style.removeProperty("display");
        } else {
            element.style.display = "none";
        }
    });
};

/**
 * Toggle disabled on elements array (used on inputs)
 *
 * @param elements array
 * @param enabled boolean
 */
export const toggleElementsEnabled = (elements, enabled) => {
    [...elements].forEach((element) => {
        const input = element.querySelector("input");
        if (input && enabled) {
            input.removeAttribute("disabled");
        } else {
            input.disabled = "disabled";
        }
    });
};
