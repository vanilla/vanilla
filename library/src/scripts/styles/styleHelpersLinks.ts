/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper } from "csx";
import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IButtonStates } from "@library/styles/styleHelpersButtons";
import { IBorderStyles } from "@library/styles/styleHelpersBorders";
import { NestedCSSProperties } from "typestyle/lib/types";
import merge from "lodash/merge";
import { emptyObject } from "expect/build/utils";

export interface ILinkStates {
    allStates?: object; // Applies to all
    noState?: object;
    hover?: object;
    focus?: object;
    keyboardFocus?: object;
    active?: object;
    visited?: object;
}

export interface ILinkStates extends IButtonStates {
    visited?: object;
}

export const linksColorFallbacks = (
    specificOverwrite: undefined | ColorHelper | string,
    defaultOverwrite: undefined | ColorHelper | string,
    globalDefault: undefined | ColorHelper | string,
) => {
    if (specificOverwrite) {
        return specificOverwrite as ColorValues;
    } else if (defaultOverwrite) {
        return defaultOverwrite as ColorValues;
    } else {
        return globalDefault as ColorValues;
    }
};

// These need to be strings as they could be any styles.
export const linkStyleFallbacks = (
    specificOverwrite: undefined | NestedCSSProperties,
    defaultOverwrite: undefined | NestedCSSProperties,
) => {
    return merge(specificOverwrite || {}, defaultOverwrite || {});
};

export interface ILinkColorOverwrites {
    default?: ColorValues | string;
    hover?: ColorValues | string;
    focus?: ColorValues | string;
    clickFocus?: ColorValues | string;
    keyboardFocus?: ColorValues | string;
    active?: ColorValues | string;
    visited?: ColorValues | string;
    allStates?: ColorValues | string;
}

export const EMPTY_LINK_COLOR_OVERWRITES = {
    default: undefined as undefined | ColorValues,
    hover: undefined as undefined | ColorValues,
    focus: undefined as undefined | ColorValues,
    clickFocus: undefined as undefined | ColorValues,
    keyboardFocus: undefined as undefined | ColorValues,
    active: undefined as undefined | ColorValues,
    visited: undefined as undefined | ColorValues,
    allStates: undefined as undefined | ColorValues,
};

export const EMPTY_LINK_OVERWRITES = {
    default: undefined as undefined | NestedCSSProperties,
    hover: undefined as undefined | NestedCSSProperties,
    focus: undefined as undefined | NestedCSSProperties,
    clickFocus: undefined as undefined | NestedCSSProperties,
    keyboardFocus: undefined as undefined | NestedCSSProperties,
    active: undefined as undefined | NestedCSSProperties,
    visited: undefined as undefined | NestedCSSProperties,
    allStates: undefined as undefined | NestedCSSProperties,
};

export interface ILinkColorOverwritesWithOptions extends ILinkColorOverwrites {
    skipDefault?: boolean;
}

// The "special" here is non standard styles for links. The text colors have presets and have more complicated inheritance
export interface ILinkSpecialOverwritesOptional {
    default?: undefined | NestedCSSProperties;
    hover?: undefined | NestedCSSProperties;
    focus?: undefined | NestedCSSProperties;
    clickFocus?: undefined | NestedCSSProperties;
    keyboardFocus?: undefined | NestedCSSProperties;
    active?: undefined | NestedCSSProperties;
    visited?: undefined | NestedCSSProperties;
    allStates?: undefined | NestedCSSProperties;
}

export interface ILinkSpecialOverwritesEnforced {
    default: undefined | NestedCSSProperties;
    hover: undefined | NestedCSSProperties;
    focus: undefined | NestedCSSProperties;
    clickFocus: undefined | NestedCSSProperties;
    keyboardFocus: undefined | NestedCSSProperties;
    active: undefined | NestedCSSProperties;
    visited: undefined | NestedCSSProperties;
    allStates: undefined | NestedCSSProperties;
}

export const EMPTY_LINK_COLOR_OVERWRITES_WITH_OPTIONS = {
    ...EMPTY_LINK_COLOR_OVERWRITES,
    skipDefault: undefined as undefined | boolean,
};

export const setAllLinkStateStyles = (
    overwriteColors?: ILinkColorOverwritesWithOptions,
    overwritesSpecial?: ILinkSpecialOverwritesOptional,
) => {
    const vars = globalVariables();
    // We want to default to the standard styles and only overwrite what we want/need
    const linkColors = vars.links.colors;

    overwriteColors = { ...EMPTY_LINK_COLOR_OVERWRITES, ...(overwriteColors ?? {}) };
    overwritesSpecial = { ...EMPTY_LINK_OVERWRITES, ...(overwritesSpecial ?? {}) } as ILinkSpecialOverwritesEnforced;

    const mergedColors = {
        default: !overwriteColors.skipDefault
            ? linksColorFallbacks(overwriteColors.default, undefined, linkColors.default)
            : undefined,
        hover: linksColorFallbacks(overwriteColors.hover, overwriteColors.allStates, linkColors.hover),
        focus: linksColorFallbacks(overwriteColors.focus, overwriteColors.allStates, linkColors.focus),
        clickFocus: linksColorFallbacks(overwriteColors.clickFocus, overwriteColors.allStates, linkColors.focus),
        keyboardFocus: linksColorFallbacks(
            overwriteColors.keyboardFocus,
            overwriteColors.allStates,
            linkColors.keyboardFocus,
        ),
        active: linksColorFallbacks(overwriteColors.active, overwriteColors.allStates, linkColors.active),
        visited: linksColorFallbacks(overwriteColors.visited, overwriteColors.allStates, linkColors.visited),
    };

    const specialStyles = {
        default: overwritesSpecial.default || {},
        hover: linkStyleFallbacks(overwritesSpecial.hover, overwritesSpecial.allStates),
        focus: linkStyleFallbacks(overwritesSpecial.focus, overwritesSpecial.allStates),
        clickFocus: linkStyleFallbacks(overwritesSpecial.clickFocus, overwritesSpecial.allStates),
        keyboardFocus: linkStyleFallbacks(overwritesSpecial.keyboardFocus, overwritesSpecial.allStates),
        active: linkStyleFallbacks(overwritesSpecial.active, overwritesSpecial.allStates),
        visited: linkStyleFallbacks(overwritesSpecial.visited, overwritesSpecial.allStates),
    };

    const styles = {
        default: {
            color: colorOut(mergedColors.default),
            ...specialStyles.default,
        },
        hover: {
            color: colorOut(mergedColors.hover),
            cursor: "pointer",
            ...specialStyles.hover,
        },
        focus: {
            color: colorOut(mergedColors.focus),
            ...specialStyles.focus,
        },
        clickFocus: {
            color: colorOut(mergedColors.focus),
            ...specialStyles.clickFocus,
        },
        keyboardFocus: {
            color: colorOut(mergedColors.keyboardFocus),
            ...specialStyles.keyboardFocus,
        },
        active: {
            color: colorOut(mergedColors.active),
            ...specialStyles.active,
        },
        visited: {
            color: mergedColors.visited ? colorOut(mergedColors.visited) : undefined,
            ...specialStyles.visited,
        },
    };

    const final = {
        color: styles.default.color as ColorValues,
        nested: {
            "&&:hover": styles.hover,
            "&&:focus": {
                ...(styles.focus ?? {}),
                ...(styles.clickFocus ?? {}),
            },
            "&&.focus-visible": {
                ...(styles.focus ?? {}),
                ...(styles.keyboardFocus ?? {}),
            },
            "&&:active": styles.active,
            "&:visited": styles.visited && !emptyObject(styles.visited) ? styles.visited : undefined,
        },
    };

    return final;
};
