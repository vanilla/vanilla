/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper } from "csx";
import { colorOut, ColorValues } from "@library/styles/styleHelpersColors";
import { IButtonStates } from "@library/styles/styleHelpersButtons";
import { EMPTY_STATE_COLORS } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { NestedCSSProperties } from "typestyle/lib/types";
import { globalVariables } from "@library/styles/globalStyleVars";
import { NestedCSSSelectors } from "typestyle/src/types";

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
        return specificOverwrite as undefined | ColorHelper | string;
    } else if (defaultOverwrite) {
        return defaultOverwrite as undefined | ColorHelper | string;
    } else {
        return globalDefault as undefined | ColorHelper | string;
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

export interface ILinkColorOverwritesWithOptions extends ILinkColorOverwrites {
    skipDefault?: boolean;
}

export const EMPTY_LINK_COLOR_OVERWRITES_WITH_OPTIONS = {
    ...EMPTY_STATE_COLORS,
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
            color: colorOut(mergedColors.hover) as string,
            cursor: "pointer",
        },
        focus: {
            color: colorOut(mergedColors.focus) as string,
        },
        clickFocus: {
            color: colorOut(mergedColors.focus) as string,
        },
        keyboardFocus: {
            color: colorOut(mergedColors.keyboardFocus) as string,
        },
        active: {
            color: colorOut(mergedColors.active) as string,
            cursor: "pointer",
        },
        visited: undefined as undefined | NestedCSSProperties | string,
    };

    if (mergedColors.visited) {
        styles.visited = colorOut(mergedColors.visited);
    }

    return {
        color: styles.default.color as ColorValues | string,
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
        } as NestedCSSSelectors,
    } as NestedCSSProperties;
};
