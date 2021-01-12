/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

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
