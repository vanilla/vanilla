/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILinkStates } from "@library/styles/styleHelpersLinks";
import { logDebug } from "@vanilla/utils/src/debugUtils";
import { CSSObject } from "@emotion/css";

// Similar to ILinkStates, but can be button or link, so we don't have link specific states here and not specific to colors
export interface IActionStates {
    noState?: object;
    allStates?: object; // Applies to all
    hover?: object;
    focus?: object;
    clickFocus?: object; // Focused, not through keyboard?: object;
    keyboardFocus?: object; // Optionally different state for keyboard accessed element. Will default to "focus" state if not set.
    active?: object;
}

export interface IStateSelectors {
    noState?: string;
    allStates?: string; // Applies to all
    hover?: string;
    focus?: string;
    clickFocus?: string; // Focused, not through keyboard?: object;
    keyboardFocus?: string; // Optionally different state for keyboard accessed element. Will default to "focus" state if not set.
    active?: string;
}

export interface IButtonStates {
    allStates?: object; // Applies to all
    noState?: object; // Applies to stateless link
    hover?: object;
    focus?: object;
    clickFocus?: object; // Focused, not through keyboard
    keyboardFocus?: object; // Optionally different state for keyboard accessed element. Will default to "focus" state if not set.
    active?: object;
}

export const allLinkStates = (styles: ILinkStates, nested?: object): CSSObject => {
    const output: CSSObject = allButtonStates(styles, nested, true);
    const visited = styles.visited !== undefined ? styles.visited : styles.noState || {};
    output[":visited"] = { ...visited };
    return output;
};

export const allButtonStates = (
    styles: IButtonStates,
    nested?: object,
    isLink?: boolean,
    debugMode?: boolean,
): CSSObject => {
    const allStates = styles.allStates !== undefined ? styles.allStates : {};
    const noState = styles.noState !== undefined ? styles.noState : {};

    const disabledStyles = isLink
        ? {}
        : {
              "&:disabled": {
                  opacity: 0.5,
              },
          };

    const output = {
        ...allStates,
        ...noState,
        ...noState,
        "&:hover:not(:disabled)": { ...allStates, ...styles.hover },
        "&:focus": { ...allStates, ...styles.focus },
        "&:focus:not(.focus-visible)": { ...allStates, ...styles.clickFocus },
        "&&.focus-visible": { ...allStates, ...styles.keyboardFocus },
        "&:active:not(:disabled)": { ...allStates, ...styles.active },
        ...disabledStyles,
        ...nested,
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
export const buttonStates = (styles: IActionStates, nest?: object, classBasedStates?: IStateSelectors) => {
    const allStates = styles.allStates !== undefined ? styles.allStates : {};
    const hover = styles.hover !== undefined ? styles.hover : {};
    const focus = styles.focus !== undefined ? styles.focus : {};
    const clickFocus = styles.clickFocus !== undefined ? styles.clickFocus : {};
    const keyboardFocus = styles.keyboardFocus !== undefined ? styles.keyboardFocus : {};
    const active = styles.active !== undefined ? styles.active : {};
    const noState = styles.noState !== undefined ? styles.noState : {};

    if (!classBasedStates) {
        classBasedStates = {};
    }

    return {
        [appendExtraSelector("&", classBasedStates.allStates)]: { ...allStates, ...noState },
        [appendExtraSelector("&:hover", classBasedStates.hover)]: { ...allStates, ...hover },
        [appendExtraSelector("&:focus", classBasedStates.focus)]: { ...allStates, ...focus },
        [appendExtraSelector("&:focus:not(.focus-visible)", classBasedStates.clickFocus)]: {
            ...allStates,
            ...clickFocus,
        },
        [appendExtraSelector("&.focus-visible", classBasedStates.keyboardFocus)]: {
            ...allStates,
            ...keyboardFocus,
        },
        [appendExtraSelector("&&:active", classBasedStates.active)]: { ...allStates, ...active },
        ...nest,
    };
};

const appendExtraSelector = (currentSelector: string, additionnalSelector) => {
    if (additionnalSelector) {
        return `${currentSelector}, ${additionnalSelector}`;
    } else {
        return currentSelector;
    }
};
