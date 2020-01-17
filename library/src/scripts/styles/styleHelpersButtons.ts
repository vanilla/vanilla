/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILinkStates } from "@library/styles/styleHelpersLinks";
import { logDebug } from "@vanilla/utils/src/debugUtils";

// Similar to ILinkStates, but can be button or link, so we don't have link specific states here and not specific to colors
export interface IActionStates {
    noState?: object;
    allStates?: object; // Applies to all
    hover?: object;
    focus?: object;
    focusNotKeyboard?: object; // Focused, not through keyboard?: object;
    accessibleFocus?: object; // Optionally different state for keyboard accessed element. Will default to "focus" state if not set.
    active?: object;
}

export interface IButtonStates {
    allStates?: object; // Applies to all
    noState?: object; // Applies to stateless link
    hover?: object;
    focus?: object;
    focusNotKeyboard?: object; // Focused, not through keyboard
    accessibleFocus?: object; // Optionally different state for keyboard accessed element. Will default to "focus" state if not set.
    active?: object;
}

export const allLinkStates = (styles: ILinkStates) => {
    const output = allButtonStates(styles);
    const visited = styles.visited !== undefined ? styles.visited : styles.noState || {};
    output.$nest["&:visited"] = { ...styles.allStates, ...visited };
    return output;
};

export const allButtonStates = (styles: IButtonStates, nested?: object, debugMode?: boolean) => {
    const allStates = styles.allStates !== undefined ? styles.allStates : {};
    const noState = styles.noState !== undefined ? styles.noState : {};

    const output = {
        ...allStates,
        ...noState,
        $nest: {
            "&:hover:not(:disabled)": { ...allStates, ...styles.hover },
            "&:focus": { ...allStates, ...styles.focus },
            "&:focus:not(.focus-visible)": { ...allStates, ...styles.focusNotKeyboard },
            "&&.focus-visible": { ...allStates, ...styles.accessibleFocus },
            "&:active:not(:disabled)": { ...allStates, ...styles.active },
            "&:disabled": {
                opacity: 0.5,
            },
            ...nested,
        },
    };

    if (debugMode) {
        logDebug("allButtonStates: ");
        logDebug("style: ", styles);
        logDebug("nested: ", nested);
        logDebug("output: ", output);
    }

    return output;
};

/*
 * Helper to write CSS state styles. Note this one is for buttons or links
 * *** You must use this inside of a "$nest" ***
 */
export const buttonStates = (styles: IActionStates, nest?: object) => {
    const allStates = styles.allStates !== undefined ? styles.allStates : {};
    const hover = styles.hover !== undefined ? styles.hover : {};
    const focus = styles.focus !== undefined ? styles.focus : {};
    const focusNotKeyboard = styles.focusNotKeyboard !== undefined ? styles.focusNotKeyboard : {};
    const accessibleFocus = styles.accessibleFocus !== undefined ? styles.accessibleFocus : {};
    const active = styles.active !== undefined ? styles.active : {};
    const noState = styles.noState !== undefined ? styles.noState : {};

    return {
        "&": { ...allStates, ...noState },
        "&:hover": { ...allStates, ...hover },
        "&:focus": { ...allStates, ...focus },
        "&:focus:not(.focus-visible)": { ...allStates, ...focusNotKeyboard },
        "&.focus-visible": { ...allStates, ...accessibleFocus },
        "&&:active": { ...allStates, ...active },
        ...nest,
    };
};
