/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper } from "csx";
import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IButtonStates } from "@library/styles/styleHelpersButtons";

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

export interface ILinkColorOverwritesWithOptions extends ILinkColorOverwrites {
    skipDefault?: boolean;
}

export const EMPTY_LINK_COLOR_OVERWRITES_WITH_OPTIONS = {
    ...EMPTY_LINK_COLOR_OVERWRITES,
    skipDefault: undefined as undefined | boolean,
};

export const clickableItemStates = (overwriteColors?: ILinkColorOverwritesWithOptions) => {
    const vars = globalVariables();
    // We want to default to the standard styles and only overwrite what we want/need
    const linkColors = vars.links.colors;

    const overwrites = overwriteColors ? overwriteColors : {};

    const mergedColors = {
        default: !overwrites.skipDefault
            ? linkStyleFallbacks(overwrites.default, overwrites.allStates, linkColors.default)
            : undefined,
        hover: linkStyleFallbacks(overwrites.hover, overwrites.allStates, linkColors.hover),
        focus: linkStyleFallbacks(overwrites.focus, overwrites.allStates, linkColors.focus),
        clickFocus: linkStyleFallbacks(overwrites.clickFocus, overwrites.allStates, linkColors.focus),
        keyboardFocus: linkStyleFallbacks(overwrites.keyboardFocus, overwrites.allStates, linkColors.keyboardFocus),
        active: linkStyleFallbacks(overwrites.active, overwrites.allStates, linkColors.active),
        visited: linkStyleFallbacks(overwrites.visited, overwrites.allStates, linkColors.visited),
    };

    const styles = {
        default: {
            color: !overwrites.skipDefault ? colorOut(mergedColors.default) : undefined,
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
