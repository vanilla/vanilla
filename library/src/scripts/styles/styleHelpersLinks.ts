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
    accessibleFocus?: object;
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
    accessibleFocus?: ColorValues;
    active?: ColorValues;
    visited?: ColorValues;
    allStates?: ColorValues;
}

export const setAllLinkColors = (overwriteValues?: ILinkColorOverwrites) => {
    const vars = globalVariables();
    // We want to default to the standard styles and only overwrite what we want/need
    const linkColors = vars.links.colors;
    const overwrites = overwriteValues ? overwriteValues : {};
    const mergedColors = {
        default: linkStyleFallbacks(overwrites.default, overwrites.allStates, linkColors.default),
        hover: linkStyleFallbacks(overwrites.hover, overwrites.allStates, linkColors.hover),
        focus: linkStyleFallbacks(overwrites.focus, overwrites.allStates, linkColors.focus),
        accessibleFocus: linkStyleFallbacks(
            overwrites.accessibleFocus,
            overwrites.allStates,
            linkColors.accessibleFocus,
        ),
        active: linkStyleFallbacks(overwrites.active, overwrites.allStates, linkColors.active),
        visited: linkStyleFallbacks(overwrites.visited, overwrites.allStates, linkColors.visited),
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
        accessibleFocus: {
            color: colorOut(mergedColors.accessibleFocus),
        },
        active: {
            color: colorOut(mergedColors.active),
            cursor: "pointer",
        },
        visited: {
            color: colorOut(mergedColors.visited),
        },
    };

    const final = {
        color: styles.default.color,
        nested: {
            "&&:hover": styles.hover,
            "&&:focus": styles.focus,
            "&&.focus-visible": styles.accessibleFocus,
            "&&:active": styles.active,
            "&:visited": styles.visited,
        },
    };

    return final;
};
