/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper } from "csx";
import { ColorValues } from "@library/styles/styleHelpersColors";
import { IButtonStates } from "@library/styles/styleHelpersButtons";
import { EMPTY_STATE_COLORS } from "@dashboard/compatibilityStyles/clickableItemHelpers";

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
