/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper } from "csx";
import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IButtonStates } from "@library/styles/styleHelpersButtons";
import { NestedCSSProperties } from "typestyle/lib/types";
import { emptyObject } from "expect/build/utils";
import merge from "lodash/merge";

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

export const linkStyleFallbacks = (
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
export const clickStyleFallback = (
    specificOverwrite: undefined | NestedCSSProperties,
    defaultOverwrite: undefined | NestedCSSProperties,
) => {
    const mergedStyles = merge(specificOverwrite || {}, defaultOverwrite || {});
    return emptyObject(mergedStyles) ? undefined : mergedStyles;
};
export interface ILinkColorOverwrites {
    default?: ColorValues;
    hover?: ColorValues;
    focus?: ColorValues;
    clickFocus?: ColorValues;
    keyboardFocus?: ColorValues;
    active?: ColorValues;
    visited?: ColorValues;
    allStates?: ColorValues;
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
    default?: undefined;
    hover?: undefined;
    focus?: undefined;
    clickFocus?: undefined;
    keyboardFocus?: undefined;
    active?: undefined;
    visited?: undefined;
    allStates?: undefined;
}

export interface ILinkSpecialOverwritesEnforced {
    default: undefined;
    hover: undefined;
    focus: undefined;
    clickFocus: undefined;
    keyboardFocus: undefined;
    active: undefined;
    visited: undefined;
    allStates: undefined;
}

export const EMPTY_LINK_COLOR_OVERWRITES_WITH_OPTIONS = {
    ...EMPTY_LINK_COLOR_OVERWRITES,
    skipDefault: undefined as undefined | boolean,
};

export const clickableItemStates = (
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
            ? linkStyleFallbacks(overwriteColors.default, undefined, linkColors.default)
            : undefined,
        hover: linkStyleFallbacks(overwriteColors.hover, overwriteColors.allStates, linkColors.hover),
        focus: linkStyleFallbacks(overwriteColors.focus, overwriteColors.allStates, linkColors.focus),
        clickFocus: linkStyleFallbacks(overwriteColors.clickFocus, overwriteColors.allStates, linkColors.focus),
        keyboardFocus: linkStyleFallbacks(
            overwriteColors.keyboardFocus,
            overwriteColors.allStates,
            linkColors.keyboardFocus,
        ),
        active: linkStyleFallbacks(overwriteColors.active, overwriteColors.allStates, linkColors.active),
        visited: linkStyleFallbacks(overwriteColors.visited, overwriteColors.allStates, linkColors.visited),
    };

    const specialStyles = {
        default: overwritesSpecial ? overwritesSpecial.default : undefined,
        hover: clickStyleFallback(overwritesSpecial.hover, overwritesSpecial.allStates),
        focus: clickStyleFallback(overwritesSpecial.focus, overwritesSpecial.allStates),
        clickFocus: clickStyleFallback(overwritesSpecial.clickFocus, overwritesSpecial.allStates),
        keyboardFocus: clickStyleFallback(overwritesSpecial.keyboardFocus, overwritesSpecial.allStates),
        active: clickStyleFallback(overwritesSpecial.active, overwritesSpecial.allStates),
        visited: clickStyleFallback(overwritesSpecial.visited, overwritesSpecial.allStates),
    };

    const styles = {
        default: {
            color: colorOut(mergedColors.default),
        },
        hover: {
            color: colorOut(mergedColors.hover),
            cursor: "pointer",
        },
        focus: {
            color: colorOut(mergedColors.focus),
        },
        clickFocus: {
            color: colorOut(mergedColors.focus),
        },
        keyboardFocus: {
            color: colorOut(mergedColors.keyboardFocus),
        },
        active: {
            color: colorOut(mergedColors.active),
            cursor: "pointer",
        },
        visited: {
            color: mergedColors.visited ? colorOut(mergedColors.visited) : undefined,
        },
    };

    const final = {
        color: styles.default.color as ColorValues,
        $nest: {
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
            "&:visited": styles.visited ?? undefined,
        },
    };

    return final;
};
